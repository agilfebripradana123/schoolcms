<?php

namespace App\Http\Resources\Students;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'class_id' => $this->class_id,
            'nisn' => $this->nisn,
            'nis' => $this->nis,
            'name' => $this->name,
            'nik' => $this->nik,
            'religion' => $this->religion,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'address' => $this->address,
            'rt' => $this->rt, 'rw' => $this->rw,
            'hamlet' => $this->hamlet, 'village' => $this->village, 'district' => $this->district, 'postal_code' => $this->postal_code,
            'residence_type' => $this->residence_type, 'transportation' => $this->transportation, 'telephone' => $this->telephone,
            'family_card_number' => $this->family_card_number, 'birth_certificate_registration_number' => $this->birth_certificate_registration_number,
            'skhun' => $this->skhun, 'previous_school' => $this->previous_school, 'national_exam_number' => $this->national_exam_number, 'diploma_serial_number' => $this->diploma_serial_number,
            'special_needs' => $this->special_needs, 'birth_order' => $this->birth_order, 'sibling_count' => $this->sibling_count,
            'weight' => $this->weight, 'height' => $this->height, 'head_circumference' => $this->head_circumference,
            'school_distance' => $this->school_distance, 'latitude' => $this->latitude, 'longitude' => $this->longitude,
            'kps_recipient' => (bool)$this->kps_recipient, 'kps_number' => $this->kps_number,
            'kip_recipient' => (bool)$this->kip_recipient, 'kip_number' => $this->kip_number, 'kip_name' => $this->kip_name, 'kks_number' => $this->kks_number,
            'pip_eligible' => (bool)$this->pip_eligible, 'pip_reason' => $this->pip_reason,
            'bank_name' => $this->bank_name, 'bank_account_number' => $this->bank_account_number, 'bank_account_holder' => $this->bank_account_holder,
            'phone' => $this->phone, 'email' => $this->email, 'photo' => $this->photo ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo) : null,
            'photo_path' => $this->photo,
            'class_name' => $this->relationLoaded('schoolClass') ? $this->schoolClass?->name : null,
            'parent' => new StudentParentResource($this->whenLoaded('parent')),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
