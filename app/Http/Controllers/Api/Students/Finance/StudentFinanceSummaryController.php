<?php

namespace App\Http\Controllers\Api\Students\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
use App\Models\Finance\Scholarship;
use App\Services\Finance\BillingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student Portal dashboard summary (read-only).
 *
 * All totals come from the signed successful ledger:
 *   total_billed  = SUM(non-cancelled billing.amount)
 *   total_paid    = SUM(successful payment_transactions.amount)
 *   outstanding   = total_billed - total_paid
 *
 * `payments.amount` and `billing.status` are never used for the totals.
 */
class StudentFinanceSummaryController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $request->validate(['student_id' => ['prohibited']]);

        $billingService = app(BillingService::class);

        $billings = Billing::with(['feeType'])
            ->where('student_id', $student->id)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->keyBy('id');

        $paidByBilling = [];
        $totalBilled = 0.0;
        $totalPaid = 0.0;

        foreach ($billings as $billing) {
            $paid = $billingService->netPaid($billing);
            $paidByBilling[$billing->id] = $paid;
            $totalBilled += (float) $billing->amount;
            $totalPaid += $paid;
        }

        $totalOutstanding = $totalBilled - $totalPaid;

        $today = Carbon::today()->toDateString();

        $upcomingCandidates = collect();
        $overdue = collect();

        foreach ($billings as $billing) {
            if ((float) $billing->amount - $paidByBilling[$billing->id] <= 0) {
                continue;
            }

            $dueDate = $billing->due_date?->toDateString();

            if ($dueDate === null) {
                continue;
            }

            if ($dueDate >= $today) {
                $upcomingCandidates->push($billing);
            } else {
                $overdue->push($billing);
            }
        }

        $upcoming = $upcomingCandidates
            ->sortBy(fn ($billing) => $billing->due_date->toDateString())
            ->first();

        $overdueBillings = $overdue
            ->sortBy(fn ($billing) => $billing->due_date->toDateString())
            ->map(fn ($billing) => [
                'id' => $billing->id,
                'amount' => (float) $billing->amount,
                'due_date' => $billing->due_date->toDateString(),
                'outstanding' => round((float) $billing->amount - $paidByBilling[$billing->id], 2),
            ])
            ->values()
            ->all();

        $recentPayments = Payment::where('student_id', $student->id)
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'payment_date' => $payment->payment_date?->toDateString(),
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
            ])
            ->values()
            ->all();

        $activeScholarships = Scholarship::where('student_id', $student->id)
            ->where('status', 'aktif')
            ->orderBy('id')
            ->get()
            ->map(fn ($scholarship) => [
                'id' => $scholarship->id,
                'name' => $scholarship->name,
                'provider' => $scholarship->provider,
                'amount' => (float) $scholarship->amount,
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Student finance summary retrieved successfully',
            'data' => [
                'totals' => [
                    'total_billed' => round($totalBilled, 2),
                    'total_paid' => round($totalPaid, 2),
                    'total_outstanding' => round($totalOutstanding, 2),
                ],
                'upcoming_billing' => $upcoming === null ? null : [
                    'id' => $upcoming->id,
                    'fee_type' => $upcoming->feeType?->name,
                    'amount' => (float) $upcoming->amount,
                    'due_date' => $upcoming->due_date->toDateString(),
                ],
                'overdue_billings' => $overdueBillings,
                'recent_payments' => $recentPayments,
                'active_scholarships' => $activeScholarships,
            ],
        ]);
    }
}
