<?php

namespace App\Services\Finance;

use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentTransaction;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

/**
 * Payment transaction (ledger) operations.
 *
 * The payment_transactions table is the financial ledger. Ledger semantics:
 *
 *   payment:   amount > 0
 *   refund:    amount < 0
 *   adjustment: amount != 0 (positive or negative)
 *
 * Only status = "success" participates in billing reconciliation, refund
 * capacity and balance calculations. Pending/failed rows are stored but never
 * affect the ledger state.
 *
 * Every mutation runs inside a DB transaction with row locking and re-runs
 * BillingService::reconcile() for the affected billings — no operation may
 * leave a billing in a stale state.
 */
class PaymentTransactionService
{
    public function __construct(
        private readonly BillingService $billingService,
    ) {}

    /**
     * Refundable amount for a billing, derived from the successful ledger and
     * floored at zero (a billing with no successful positive flow cannot be
     * refunded). Deleted payments/transactions are excluded.
     */
    public function refundableFor(Billing $billing, ?int $excludeTransactionId = null): float
    {
        $net = 0.0;

        foreach ($billing->payments as $payment) {
            foreach ($payment->transactions as $transaction) {
                if ($transaction->status !== 'success') {
                    continue;
                }

                if ($excludeTransactionId !== null && (int) $transaction->id === (int) $excludeTransactionId) {
                    continue;
                }

                $net += (float) $transaction->amount;
            }
        }

        return max(0.0, $net);
    }

    /**
     * True when the payment has any ledger transaction rows — including
     * soft-deleted ones, so financial history is never silently discarded.
     */
    public function hasLedgerHistory(Payment $payment): bool
    {
        return $payment->transactions()->withTrashed()->exists();
    }

    public function create(array $data): PaymentTransaction
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::whereKey($data['payment_id'])->lockForUpdate()->first();

            if ($payment === null) {
                $this->fail('payment_id', 'The selected payment is not available.');
            }

            $billing = Billing::whereKey($payment->billing_id)->lockForUpdate()->first();

            $this->assertValidLedgerAmount($data['type'], (float) $data['amount']);

            if ($billing !== null) {
                $this->assertRefundWithinCapacity($billing, $data['type'], (float) $data['amount']);
            }

            $transaction = PaymentTransaction::create($data);

            if ($billing !== null) {
                $this->billingService->reconcile($billing);
            }

            return $transaction;
        });
    }

    public function update(PaymentTransaction $transaction, array $data): PaymentTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();

            $oldPayment = $transaction->payment;
            $oldBillingId = $oldPayment?->billing_id;

            $newPaymentId = $data['payment_id'] ?? $transaction->payment_id;
            $newType = $data['type'] ?? $transaction->type;
            $newAmount = (float) ($data['amount'] ?? $transaction->amount);

            $this->assertValidLedgerAmount($newType, $newAmount);

            $lockedPayments = $this->lockPayments([$oldPayment?->id, $newPaymentId]);
            $newPayment = $newPaymentId !== null ? ($lockedPayments[$newPaymentId] ?? null) : null;
            $newBillingId = $newPayment?->billing_id;

            $newBilling = $newBillingId !== null ? Billing::whereKey($newBillingId)->lockForUpdate()->first() : null;

            if ($newBilling !== null) {
                $this->assertRefundWithinCapacity($newBilling, $newType, $newAmount, (int) $transaction->id);
            }

            $transaction->update($data);

            $this->reconcileBillings([$oldBillingId, $newBillingId]);

            return $transaction->fresh(['payment']);
        });
    }

    public function delete(PaymentTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();

            if ($transaction === null) {
                return;
            }

            $billingId = $transaction->payment?->billing_id;

            $transaction->delete();

            $this->billingService->reconcileMany([$billingId]);
        });
    }

    /**
     * @param  array<int, int|null>  $paymentIds
     * @return array<int, Payment>
     */
    private function lockPayments(array $paymentIds): array
    {
        $locked = [];

        foreach (collect($paymentIds)->filter()->unique()->sort()->values() as $paymentId) {
            $payment = Payment::whereKey($paymentId)->lockForUpdate()->first();

            if ($payment !== null) {
                $locked[$payment->id] = $payment;
            }
        }

        return $locked;
    }

    /**
     * @param  array<int, int|null>  $billingIds
     */
    private function reconcileBillings(array $billingIds): void
    {
        foreach (collect($billingIds)->filter()->unique()->sort()->values() as $billingId) {
            $billing = Billing::whereKey($billingId)->lockForUpdate()->first();

            if ($billing !== null) {
                $this->billingService->reconcile($billing);
            }
        }
    }

    private function assertValidLedgerAmount(string $type, float $amount): void
    {
        if ($type === 'payment' && $amount <= 0) {
            $this->fail('amount', 'A payment transaction amount must be greater than zero.');
        }

        if ($type === 'refund' && $amount >= 0) {
            $this->fail('amount', 'A refund transaction amount must be negative.');
        }

        if ($type === 'adjustment' && $amount == 0) {
            $this->fail('amount', 'An adjustment transaction amount cannot be zero.');
        }
    }

    private function assertRefundWithinCapacity(
        Billing $billing,
        string $type,
        float $amount,
        ?int $excludeTransactionId = null
    ): void {
        if ($type !== 'refund') {
            return;
        }

        $refundable = $this->refundableFor($billing, $excludeTransactionId);

        if (abs($amount) > $refundable) {
            $this->fail('amount', "The refund amount exceeds the refundable amount of {$refundable}.");
        }
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
