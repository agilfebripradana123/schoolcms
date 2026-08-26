<?php

namespace App\Http\Resources\Facilities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'asset_id' => $this->asset_id,
            'room_id' => $this->room_id,
            'reported_by' => $this->reported_by,
            'maintenance_type' => $this->maintenance_type,
            'priority' => $this->priority,
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date?->format('Y-m-d'),
            'started_date' => $this->started_date?->format('Y-m-d'),
            'completed_date' => $this->completed_date?->format('Y-m-d'),
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'notes' => $this->notes,
            'resolution' => $this->resolution,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
