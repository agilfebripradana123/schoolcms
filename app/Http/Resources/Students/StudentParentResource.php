<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentParentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'father_name' => $this->father_name,
            'mother_name' => $this->mother_name,
            'father_occupation' => $this->father_occupation,
            'mother_occupation' => $this->mother_occupation,
            'phone' => $this->phone,
            'address' => $this->address,
            'student' => new StudentResource($this->whenLoaded('student')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
