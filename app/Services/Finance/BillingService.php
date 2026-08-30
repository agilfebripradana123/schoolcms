<?php

namespace App\Services\Finance;

use App\Models\Finance\Billing;
use App\Models\Finance\Payment;

/**
 * Billing financial reconciliation.
 *
 * The billing status is derived exclusively from the currently persisted
 * successful payment transactions (payment_transactions.status = 'success')
 * linked through payment -> billing.
 *
 *   netPaid     = SUM(successful payment transaction amounts)
 *   outstanding = billing.amount - netPaid
 *
 *   status: unpaid | partial | paid  computed from netPaid against
 *          billing.amount.  A billing explicitly marked `cancelled` is never
 *          overwritten by normal reconciliation.
 *
 * All values returned are derived — no paid/outstanding columns are written.
 */
class BillingService
{
    /**
     * Sum of successful transaction amounts linked to the billing through
     * payments. Deleted payments and deleted transactions are excluded via
     * their SoftDeletes scopes.
     */
    public function netPaid(Billing $billing): float
    {
        return (float) $billing->payments()
            ->with('transactions')
            ->get()
            ->sum(function (Payment $payment) {
                return (float) $payment->transactions
                    ->where('status', 'success')
                    ->sum('amount');
            });
    }

    /**
     * Reconcile a single billing and persist its derived status.
     *
     * @return array{billing: Billing, net_paid: float, outstanding: float, status: string}
     */
    public function reconcile(Billing $billing): array
    {
        $amount = (float) $billing->amount;
        $netPaid = $this->netPaid($billing);
        $outstanding = $amount - $netPaid;

        $status = $billing->status === 'cancelled'
            ? 'cancelled'
            : match (true) {
                $netPaid <= 0 => 'unpaid',
                $netPaid < $amount => 'partial',
                default => 'paid',
            };

        if ($billing->status !== $status) {
            $billing->status = $status;
            $billing->save();
        }

        return [
            'billing' => $billing,
            'net_paid' => $netPaid,
            'outstanding' => $outstanding,
            'status' => $status,
        ];
    }

    /**
     * Reconcile several billings (old + new) atomically within the caller's
     * DB transaction. Rows are locked in deterministic (id) order to reduce
     * deadlock risk. Soft-deleted billings are skipped.
     */
    public function reconcileMany(array $billingIds): void
    {
        $ids = collect($billingIds)
            ->filter(fn ($id) => $id !== null)
            ->unique()
            ->sort()
            ->values();

        foreach ($ids as $id) {
            $billing = Billing::whereKey($id)->lockForUpdate()->first();

            if ($billing !== null) {
                $this->reconcile($billing);
            }
        }
    }
}
