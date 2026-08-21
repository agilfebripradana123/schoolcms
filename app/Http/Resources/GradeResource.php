<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'subject_id' => $this->subject_id,
            'class_id' => $this->class_id,
            'type' => $this->type,
            'score' => $this->score,
            'semester' => $this->semester,
            'academic_year' => $this->academic_year,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
        ];
    }
}
