<?php

namespace App\Http\Resources\Student\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Portal payment snapshot.
 *
 * The portal never exposes the `student` relation — student identity is
 * guaranteed by the `student` middleware. `billing.fee_type` is included as
 * a compact nested object.
 */
class StudentPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'billing_id' => $this->billing_id,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'method' => $this->method,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'billing' => $this->whenLoaded('billing', fn () => [
                'id' => $this->billing->id,
                'amount' => $this->billing->amount,
                'status' => $this->billing->status,
                'due_date' => $this->billing->due_date?->toDateString(),
                'fee_type' => $this->billing->feeType?->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
