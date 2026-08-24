<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'status' => $this->status,
            'class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'student' => new StudentResource($this->whenLoaded('student')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
