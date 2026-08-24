<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'exam_card_number' => $this->exam_card_number,
            'status' => $this->status,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'is_blocked' => (bool) $this->is_blocked,
            'blocked_reason' => $this->blocked_reason,
            'login_allowed' => (bool) $this->login_allowed,
            'current_session_id' => $this->current_session_id,
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'ip_address' => $this->ip_address,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'exam' => new \App\Http\Resources\ExamResource($this->whenLoaded('exam')),
            'student' => new StudentResource($this->whenLoaded('student')),
        ];
    }
}
