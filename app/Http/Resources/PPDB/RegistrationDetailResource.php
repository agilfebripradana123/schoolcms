<?php

namespace App\Http\Resources\PPDB;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'student_id' => $this->student_id,

            // Identity
            'nik' => $this->nik,
            'nisn' => $this->nisn,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'religion' => $this->religion,
            'nationality' => $this->nationality,
            'marital_status' => $this->marital_status,
            'birth_order' => $this->birth_order,
            'sibling_count' => $this->sibling_count,
            'blood_type' => $this->blood_type,
            'special_needs' => $this->special_needs,
            'photo' => $this->photo,

            // Address
            'address' => $this->address,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'village' => $this->village,
            'district' => $this->district,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,

            // School origin
            'previous_school' => $this->previous_school,
            'previous_school_npsn' => $this->previous_school_npsn,
            'previous_school_address' => $this->previous_school_address,
            'graduation_year' => $this->graduation_year,
            'diploma_number' => $this->diploma_number,
            'average_score' => $this->average_score,

            // Father
            'father_name' => $this->father_name,
            'father_nik' => $this->father_nik,
            'father_birth_place' => $this->father_birth_place,
            'father_birth_date' => $this->father_birth_date?->format('Y-m-d'),
            'father_education' => $this->father_education,
            'father_occupation' => $this->father_occupation,
            'father_income' => $this->father_income,
            'father_phone' => $this->father_phone,
            'father_address' => $this->father_address,

            // Mother
            'mother_name' => $this->mother_name,
            'mother_nik' => $this->mother_nik,
            'mother_birth_place' => $this->mother_birth_place,
            'mother_birth_date' => $this->mother_birth_date?->format('Y-m-d'),
            'mother_education' => $this->mother_education,
            'mother_occupation' => $this->mother_occupation,
            'mother_income' => $this->mother_income,
            'mother_phone' => $this->mother_phone,
            'mother_address' => $this->mother_address,

            // Guardian
            'guardian_name' => $this->guardian_name,
            'guardian_nik' => $this->guardian_nik,
            'guardian_education' => $this->guardian_education,
            'guardian_occupation' => $this->guardian_occupation,
            'guardian_income' => $this->guardian_income,
            'guardian_phone' => $this->guardian_phone,
            'guardian_address' => $this->guardian_address,

            // Registration
            'academic_year_id' => $this->academic_year_id,
            'registration_path' => $this->registration_path,
            'program_choice' => $this->program_choice,
            'registration_date' => $this->registration_date?->format('Y-m-d'),

            // Status
            'status' => $this->status,
            'notes' => $this->notes,

            // Verification
            'verification_status' => $this->verification_status,
            'verification_notes' => $this->verification_notes,
            'verified_by' => $this->verified_by,
            'verified_at' => $this->verified_at?->toISOString(),

            // Selection
            'selection_score' => $this->selection_score,
            'selection_status' => $this->selection_status,
            'selection_notes' => $this->selection_notes,
            'selected_at' => $this->selected_at?->toISOString(),

            // Re-registration
            're_registration_status' => $this->re_registration_status,
            're_registration_date' => $this->re_registration_date?->format('Y-m-d'),
            're_registration_notes' => $this->re_registration_notes,
            're_registration_verified_by' => $this->re_registration_verified_by,
            're_registration_verified_at' => $this->re_registration_verified_at?->toISOString(),

            // Documents
            'document_kk' => $this->document_kk ? true : false,
            'document_birth_certificate' => $this->document_birth_certificate ? true : false,
            'document_diploma' => $this->document_diploma ? true : false,
            'document_parent_ktp' => $this->document_parent_ktp ? true : false,
            'document_kip_kks' => $this->document_kip_kks ? true : false,
            'document_photo' => $this->document_photo ? true : false,
            'document_other' => $this->document_other ? true : false,

            // Additional
            'achievements' => $this->achievements,
            'organizations' => $this->organizations,
            'scholarship_info' => $this->scholarship_info,
            'declaration' => $this->declaration,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
