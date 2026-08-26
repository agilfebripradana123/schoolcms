<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Staff\TeacherResource;
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'subject_id' => $this->subject_id,
            'class_id' => $this->class_id,
            'teacher_id' => $this->teacher_id,
            'due_date' => $this->due_date?->toDateString(),
            'academic_year_id' => $this->academic_year_id,
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
