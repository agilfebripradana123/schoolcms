<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'billing_id' => $this->billing_id,
            'student_id' => $this->student_id,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => $this->amount,
            'method' => $this->method,
            'reference_number' => $this->reference_number,
            'received_by' => $this->received_by,
            'notes' => $this->notes,
            'billing' => new BillingResource($this->whenLoaded('billing')),
            'student' => new StudentResource($this->whenLoaded('student')),
            'cashier' => new UserResource($this->whenLoaded('receivedBy')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
