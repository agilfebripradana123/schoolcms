<?php

namespace App\Http\Resources\Facilities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'quantity' => $this->quantity,
            'condition' => $this->condition,
            'location' => $this->location,
            'room_id' => $this->room_id,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
