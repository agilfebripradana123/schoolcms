<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class_id' => $this->class_id,
            'date' => $this->date,
            'status' => $this->status,
            'note' => $this->note,
        ];
    }
}