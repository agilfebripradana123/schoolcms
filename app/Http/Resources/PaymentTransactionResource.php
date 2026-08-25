<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTransactionResource extends JsonResource
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
            'payment' => new \App\Http\Resources\PaymentResource($this->whenLoaded('payment')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
