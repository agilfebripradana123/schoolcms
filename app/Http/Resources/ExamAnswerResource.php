<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'participant_id' => $this->participant_id,
            'question_id' => $this->question_id,
            'selected_option_id' => $this->selected_option_id,
            'essay_answer' => $this->essay_answer,
            'is_correct' => $this->is_correct,
            'answered_at' => $this->answered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
