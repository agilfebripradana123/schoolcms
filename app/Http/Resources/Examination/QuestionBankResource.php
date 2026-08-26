<?php

namespace App\Http\Resources\Examination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Academic\SubjectResource;
class QuestionBankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'instruction_id' => $this->instruction_id,
            'question_text' => $this->question_text,
            'question_image' => $this->question_image,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'explanation' => $this->explanation,
            'points' => $this->points,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
