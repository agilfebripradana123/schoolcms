<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CounselingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'counselor_id' => $this->counselor_id,
            'counseling_date' => $this->counseling_date?->toDateString(),
            'topic' => $this->topic,
            'notes' => $this->notes,
            'follow_up' => $this->follow_up,
            'status' => $this->status,
            'student' => new StudentResource($this->whenLoaded('student')),
            'counselor' => new TeacherResource($this->whenLoaded('counselor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
