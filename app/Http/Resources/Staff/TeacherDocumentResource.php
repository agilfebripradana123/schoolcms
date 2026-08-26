<?php

namespace App\Http\Resources\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'file_path' => $this->file_path,
            'issued_date' => $this->issued_date?->toDateString(),
            'notes' => $this->notes,
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
