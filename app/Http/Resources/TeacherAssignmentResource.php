<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
