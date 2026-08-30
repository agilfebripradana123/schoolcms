<?php

namespace App\Http\Resources\Student\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Portal ledger transaction snapshot.
 *
 * Transactions are scoped to the authenticated student through their parent
 * payment (`transaction → payment → student`). `payment.billing.fee_type` is
 * included compactly; the `student` relation is never exposed.
 */
class StudentPaymentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'transaction_code' => $this->transaction_code,
            'type' => $this->type,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date?->toISOString(),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'billing_id' => $this->payment->billing_id,
                'payment_date' => $this->payment->payment_date?->toDateString(),
                'billing' => $this->payment->relationLoaded('billing')
                    ? [
                        'id' => $this->payment->billing->id,
                        'amount' => $this->payment->billing->amount,
                        'status' => $this->payment->billing->status,
                        'fee_type' => $this->payment->billing->feeType?->name,
                    ]
                    : null,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
