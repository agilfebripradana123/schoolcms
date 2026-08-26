<?php

namespace App\Http\Requests\Api\PPDB;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'nisn' => ['sometimes', 'nullable', 'string', 'max:20'],
            'full_name' => ['sometimes', 'required', 'string', 'max:150'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'required', 'email', 'max:150', Rule::unique('registrants', 'email')->ignore($this->route('registration'))],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'gender' => ['sometimes', 'required', 'string', Rule::in(['L', 'P'])],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'previous_school' => ['sometimes', 'nullable', 'string', 'max:150'],
            'religion' => ['sometimes', 'nullable', 'string', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
            'nationality' => ['sometimes', 'nullable', 'string', 'max:50'],
            'marital_status' => ['sometimes', 'nullable', 'string', Rule::in(['anak_kandung', 'anak_angkat', 'lainnya'])],
            'birth_order' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:20'],
            'sibling_count' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20'],
            'blood_type' => ['sometimes', 'nullable', 'string', Rule::in(['A', 'B', 'AB', 'O'])],
            'special_needs' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rt' => ['sometimes', 'nullable', 'string', 'max:5'],
            'rw' => ['sometimes', 'nullable', 'string', 'max:5'],
            'village' => ['sometimes', 'nullable', 'string', 'max:100'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'previous_school_npsn' => ['sometimes', 'nullable', 'string', 'max:20'],
            'previous_school_address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'graduation_year' => ['sometimes', 'nullable', 'integer', 'min:2020', 'max:2030'],
            'diploma_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'average_score' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'father_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'father_nik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'father_birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'father_birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'father_education' => ['sometimes', 'nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'father_occupation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'father_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'father_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'father_address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'mother_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'mother_nik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mother_birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mother_birth_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'mother_education' => ['sometimes', 'nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'mother_occupation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'mother_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'mother_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'mother_address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'guardian_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'guardian_nik' => ['sometimes', 'nullable', 'string', 'max:20'],
            'guardian_education' => ['sometimes', 'nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'guardian_occupation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'guardian_income' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'guardian_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'guardian_address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'academic_year_id' => ['sometimes', 'nullable', 'integer', 'exists:academic_years,id'],
            'registration_path' => ['sometimes', 'nullable', 'string', Rule::in(['prestasi', 'reguler', 'afirmasi', 'mutasi'])],
            'program_choice' => ['sometimes', 'nullable', 'string', Rule::in(['ipa', 'ips', 'bahasa', 'lainnya'])],
            'registration_date' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'achievements' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'organizations' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'scholarship_info' => ['sometimes', 'nullable', 'string', 'max:200'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
