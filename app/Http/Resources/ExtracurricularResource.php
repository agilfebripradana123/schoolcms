<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtracurricularResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'supervisor_id' => $this->supervisor_id,
            'schedule_day' => $this->schedule_day,
            'is_active' => (bool) $this->is_active,
            'supervisor' => new TeacherResource($this->whenLoaded('supervisor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
