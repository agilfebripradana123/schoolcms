<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'teacher_code' => $this->teacher_code,
            'nip' => $this->nip,
            'full_name' => $this->full_name,
            'prefix_title' => $this->prefix_title,
            'suffix_title' => $this->suffix_title,
            'phone' => $this->phone,
            'email' => $this->email,
            'last_education' => $this->last_education,
            'major' => $this->major,
            'employment_status' => $this->employment_status,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'photo' => $this->photo,
            'is_active' => $this->is_active,
            'address' => $this->address,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'religion' => $this->religion,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'classes' => SchoolClassResource::collection($this->whenLoaded('classes')),
            'class_subjects' => ClassSubjectResource::collection($this->whenLoaded('classSubjects')),
        ];
    }
}
