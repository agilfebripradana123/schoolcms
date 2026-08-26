<?php

namespace App\Http\Resources\Facilities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'minimum_stock' => $this->minimum_stock,
            'location' => $this->location,
            'room_id' => $this->room_id,
            'status' => $this->status,
            'is_low_stock' => $this->is_low_stock,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
