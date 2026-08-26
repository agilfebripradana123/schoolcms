<?php

namespace App\Http\Resources\PPDB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'previous_school' => $this->previous_school,
            'status' => $this->status,
            'academic_year_id' => $this->academic_year_id,
            'registration_path' => $this->registration_path,
            'program_choice' => $this->program_choice,
            'registration_date' => $this->registration_date?->format('Y-m-d'),
            'verification_status' => $this->verification_status,
            'selection_status' => $this->selection_status,
            're_registration_status' => $this->re_registration_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
