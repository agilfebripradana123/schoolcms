<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FinanceReportController extends Controller
{
    public function summary(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'academic_year_id' => 'nullable|integer',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;
        $academicYearId = $validated['academic_year_id'] ?? null;

        // Billings: only academic_year_id filter (never by created_at)
        $billingsQuery = DB::table('billings')->where('status', '!=', 'cancelled');
        if ($academicYearId) {
            $billingsQuery->where('academic_year_id', $academicYearId);
        }
        $totalBilled = (float) (clone $billingsQuery)->sum('amount');

        // Payments: filtered by payment_date range when provided
        $paymentsQuery = DB::table('payments');
        if ($dateFrom) {
            $paymentsQuery->whereDate('payment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $paymentsQuery->whereDate('payment_date', '<=', $dateTo);
        }

        // Per fee type: billed from billings, paid via payments joined on billing
        $paidByBillingSub = DB::table('payments')
            ->selectRaw('billing_id, SUM(amount) AS paid_amount')
            ->when($dateFrom, fn ($q) => $q->whereDate('payment_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('payment_date', '<=', $dateTo))
            ->groupBy('billing_id');

        $perFeeType = DB::table('billings')
            ->join('fee_types', 'fee_types.id', '=', 'billings.fee_type_id')
            ->leftJoinSub($paidByBillingSub, 'ppb', 'ppb.billing_id', '=', 'billings.id')
            ->where('billings.status', '!=', 'cancelled')
            ->when($academicYearId, fn ($q) => $q->where('billings.academic_year_id', $academicYearId))
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
            ]);

        $totalPaid = (float) (clone $paymentsQuery)->sum('amount');

        $monthlyTrend = (clone $paymentsQuery)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total_paid")
            ->groupBy(DB::raw("DATE_FORMAT(payment_date, '%Y-%m')"))
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total_paid' => (float) $row->total_paid,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Finance report retrieved successfully',
            'data' => [
                'totals' => [
                    'total_billed' => $totalBilled,
                    'total_paid' => $totalPaid,
                    'total_outstanding' => max(0, $totalBilled - $totalPaid),
                ],
                'per_fee_type' => $perFeeType,
                'monthly_trend' => $monthlyTrend,
            ],
        ]);
    }
}
