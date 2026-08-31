<?php

namespace App\Services\Finance;

use App\Models\Finance\FinancialReport;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

/**
 * Financial report generation & summary aggregation.
 *
 * A financial report is a generated snapshot/cache over the real Finance
 * ledger. All totals are derived server-side — client-supplied totals are
 * never accepted:
 *
 *   total_billed = SUM(billings.amount) over non-cancelled billings whose
 *                  billing period window overlaps the report period
 *                  (NULL-period rows are excluded, never guessed).
 *   total_paid   = SUM(payment_transactions.amount) WHERE status='success'
 *                  joined through payments -> billings (signed ledger —
 *                  refunds subtract, adjustments apply by sign).
 *   total_outstanding = total_billed - total_paid
 *
 * `payments.amount` is never used as the source of truth.
 *
 * source_fingerprint is a deterministic SHA-256 (64 hex chars) over the
 * report scope + totals + underlying billing/ledger rows, so any relevant
 * data mutation produces a different fingerprint.
 */
class FinancialReportService
{
    public function generate(array $data): FinancialReport
    {
        $periodStart = $this->toDate($data['period_start']);
        $periodEnd = $this->toDate($data['period_end']);

        $scope = $this->computeScope($periodStart, $periodEnd, $data['report_type']);

        return FinancialReport::create([
            'title' => $data['title'],
            'report_type' => $data['report_type'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_billed' => $scope['total_billed'],
            'total_paid' => $scope['total_paid'],
            'total_outstanding' => $scope['total_outstanding'],
            'source_fingerprint' => $this->fingerprint($scope),
            'generated_by' => $data['generated_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Update a generated report. Only metadata may be updated directly; the
     * financial totals + fingerprint are regenerated whenever the report
     * scope (period_start / period_end / report_type) changes.
     */
    public function regenerate(FinancialReport $report, array $data): FinancialReport
    {
        $periodStart = $this->toDate($data['period_start'] ?? $report->period_start);
        $periodEnd = $this->toDate($data['period_end'] ?? $report->period_end);
        $reportType = $data['report_type'] ?? $report->report_type;

        if ($periodEnd < $periodStart) {
            $this->fail('period_end', 'The period_end must be a date after or equal to period_start.');
        }

        $scopeChanged = array_key_exists('period_start', $data)
            || array_key_exists('period_end', $data)
            || array_key_exists('report_type', $data);

        $updates = collect($data)->only(['title', 'notes'])->all();

        if ($scopeChanged) {
            $scope = $this->computeScope($periodStart, $periodEnd, $reportType);

            $updates += [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'report_type' => $reportType,
                'total_billed' => $scope['total_billed'],
                'total_paid' => $scope['total_paid'],
                'total_outstanding' => $scope['total_outstanding'],
                'source_fingerprint' => $this->fingerprint($scope),
            ];
        }

        $report->update($updates);

        return $report->load('generator');
    }

    /**
     * Deterministic SHA-256 fingerprint of the source aggregation.
     *
     * @param  array  $scope  result of computeScope()
     */
    public function fingerprint(array $scope): string
    {
        $payload = collect([
            $scope['period_start'],
            $scope['period_end'],
            $scope['report_type'],
            number_format($scope['total_billed'], 2, '.', ''),
            number_format($scope['total_paid'], 2, '.', ''),
            number_format($scope['total_outstanding'], 2, '.', ''),
        ])->implode('|');

        $payload .= "\n".implode("\n", $scope['billing_rows']);
        $payload .= "\n".implode("\n", $scope['transaction_rows']);

        return hash('sha256', $payload);
    }

    /**
     * Consistent summary aggregation for /reports/finance/summary.
     * Billed, paid and outstanding all share the same billing scope.
     */
    public function summary(array $filters): array
    {
        $academicYearId = $filters['academic_year_id'] ?? null;
        $semesterId = $filters['semester_id'] ?? null;
        $feeTypeId = $filters['fee_type_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $billings = DB::table('billings')
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->when($feeTypeId, fn ($q) => $q->where('fee_type_id', $feeTypeId))
            ->when($dateFrom || $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereNotNull('period_start')
                    ->whereNotNull('period_end');

                if ($dateFrom) {
                    $q->where('period_end', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $q->where('period_start', '<=', $dateTo);
                }
            });

        $totalBilled = (float) (clone $billings)->sum('amount');
        $billingIds = (clone $billings)->pluck('id')->all();

        $totalPaid = 0.0;
        $perFeeType = collect();
        $monthlyTrend = collect();

        if ($billingIds !== []) {
            $paidQuery = $this->ledgerQuery($billingIds);

            $totalPaid = (float) (clone $paidQuery)->sum('pt.amount');

            $monthlyTrend = (clone $paidQuery)
                ->selectRaw("DATE_FORMAT(pt.transaction_date, '%Y-%m') AS month, SUM(pt.amount) AS total_paid")
                ->groupBy(DB::raw("DATE_FORMAT(pt.transaction_date, '%Y-%m')"))
                ->orderBy('month')
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'total_paid' => (float) $row->total_paid,
                ])
                ->values();

            $paidByBilling = (clone $paidQuery)
                ->selectRaw('p.billing_id, SUM(pt.amount) AS paid_amount')
                ->groupBy('p.billing_id');

            $perFeeType = DB::table('billings')
                ->join('fee_types', 'fee_types.id', '=', 'billings.fee_type_id')
                ->leftJoinSub($paidByBilling, 'ppb', 'ppb.billing_id', '=', 'billings.id')
                ->whereIn('billings.id', $billingIds)
                ->where('billings.status', '!=', 'cancelled')
                ->whereNull('billings.deleted_at')
                ->groupBy('fee_types.id', 'fee_types.name')
                ->orderBy('fee_types.name')
                ->get([
                    'fee_types.name as fee_type_name',
                    DB::raw('COALESCE(SUM(billings.amount), 0) AS total_billed'),
                    DB::raw('COALESCE(SUM(ppb.paid_amount), 0) AS total_paid'),
                ])
                ->map(fn ($row) => [
                    'fee_type_name' => $row->fee_type_name,
                    'total_billed' => (float) $row->total_billed,
                    'total_paid' => (float) $row->total_paid,
                ])
                ->values();
        }

        return [
            'totals' => [
                'total_billed' => $totalBilled,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalBilled - $totalPaid,
            ],
            'per_fee_type' => $perFeeType,
            'monthly_trend' => $monthlyTrend,
        ];
    }

    private function computeScope(string $periodStart, string $periodEnd, string $reportType): array
    {
        // Non-cancelled, active billings whose period window overlaps the
        // report period. NULL-period legacy rows are handled deterministically
        // by exclusion — no financial value is invented for them.
        $billings = DB::table('billings')
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereNotNull('period_start')
            ->whereNotNull('period_end')
            ->where('period_start', '<=', $periodEnd)
            ->where('period_end', '>=', $periodStart);

        $totalBilled = (float) (clone $billings)->sum('amount');
        $billingIds = (clone $billings)->orderBy('id')->pluck('id')->all();

        $billingRows = collect();

        if ($billingIds !== []) {
            $billingRows = (clone $billings)
                ->orderBy('id')
                ->get(['id', 'amount'])
                ->map(fn ($row) => ['id' => (int) $row->id, 'amount' => (string) $row->amount])
                ->sortBy('id')
                ->map(fn ($row) => sprintf('%d|%s', $row['id'], $row['amount']))
                ->values();
        }

        $transactionRows = collect();
        $totalPaid = 0.0;

        if ($billingIds !== []) {
            $rows = $this->ledgerQuery($billingIds)
                ->orderBy('pt.id')
                ->get(['pt.id as txn_id', 'pt.amount']);

            foreach ($rows as $row) {
                $totalPaid += (float) $row->amount;
            }

            $transactionRows = $rows
                ->map(fn ($row) => ['id' => (int) $row->txn_id, 'amount' => (string) $row->amount])
                ->sortBy('id')
                ->map(fn ($row) => sprintf('%d|%s', $row['id'], $row['amount']))
                ->values();
        }

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'report_type' => $reportType,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalBilled - $totalPaid,
            'billing_rows' => $billingRows->all(),
            'transaction_rows' => $transactionRows->all(),
        ];
    }

    /**
     * Signed successful ledger joined through payments -> billings.
     */
    private function ledgerQuery(array $billingIds): Builder
    {
        return DB::table('payment_transactions as pt')
            ->join('payments as p', 'p.id', '=', 'pt.payment_id')
            ->whereIn('p.billing_id', $billingIds)
            ->where('pt.status', 'success')
            ->whereNull('pt.deleted_at')
            ->whereNull('p.deleted_at');
    }

    private function toDate(mixed $value): string
    {
        return Carbon::parse($value)->toDateString();
    }

    private function fail(string $field, string $message): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => [$field => [$message]],
        ], 422));
    }
}
