<?php

namespace App\Http\Resources\Academic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Students\StudentResource;
class ReportCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class_id' => $this->class_id,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'teacher_notes' => $this->teacher_notes,
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
