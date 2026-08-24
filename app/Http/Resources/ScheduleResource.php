<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'day' => $this->day,
            'period_id' => $this->period_id,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'period' => new PeriodResource($this->whenLoaded('period')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
