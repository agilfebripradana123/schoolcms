<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'date' => $this->date?->toDateString(),
            'status' => $this->status,
            'check_in' => $this->check_in,
            'check_out' => $this->check_out,
            'notes' => $this->notes,
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
