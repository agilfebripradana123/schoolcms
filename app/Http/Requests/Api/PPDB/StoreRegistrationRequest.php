<?php

namespace App\Http\Requests\Api\PPDB;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik' => ['nullable', 'string', 'max:20'],
            'nisn' => ['nullable', 'string', 'max:20'],
            'full_name' => ['required', 'string', 'max:150'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:150', Rule::unique('registrants', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'string', Rule::in(['L', 'P'])],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'previous_school' => ['nullable', 'string', 'max:150'],
            'religion' => ['nullable', 'string', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
            'nationality' => ['nullable', 'string', 'max:50'],
            'marital_status' => ['nullable', 'string', Rule::in(['anak_kandung', 'anak_angkat', 'lainnya'])],
            'birth_order' => ['nullable', 'integer', 'min:1', 'max:20'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'blood_type' => ['nullable', 'string', Rule::in(['A', 'B', 'AB', 'O'])],
            'special_needs' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:5'],
            'rw' => ['nullable', 'string', 'max:5'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'previous_school_npsn' => ['nullable', 'string', 'max:20'],
            'previous_school_address' => ['nullable', 'string', 'max:200'],
            'graduation_year' => ['nullable', 'integer', 'min:2020', 'max:2030'],
            'diploma_number' => ['nullable', 'string', 'max:50'],
            'average_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:150'],
            'father_nik' => ['nullable', 'string', 'max:20'],
            'father_birth_place' => ['nullable', 'string', 'max:100'],
            'father_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'father_education' => ['nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'father_occupation' => ['nullable', 'string', 'max:100'],
            'father_income' => ['nullable', 'numeric', 'min:0'],
            'father_phone' => ['nullable', 'string', 'max:20'],
            'father_address' => ['nullable', 'string', 'max:200'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'mother_nik' => ['nullable', 'string', 'max:20'],
            'mother_birth_place' => ['nullable', 'string', 'max:100'],
            'mother_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'mother_education' => ['nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'mother_occupation' => ['nullable', 'string', 'max:100'],
            'mother_income' => ['nullable', 'numeric', 'min:0'],
            'mother_phone' => ['nullable', 'string', 'max:20'],
            'mother_address' => ['nullable', 'string', 'max:200'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_nik' => ['nullable', 'string', 'max:20'],
            'guardian_education' => ['nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
            'guardian_occupation' => ['nullable', 'string', 'max:100'],
            'guardian_income' => ['nullable', 'numeric', 'min:0'],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_address' => ['nullable', 'string', 'max:200'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'registration_path' => ['nullable', 'string', Rule::in(['prestasi', 'reguler', 'afirmasi', 'mutasi'])],
            'program_choice' => ['nullable', 'string', Rule::in(['ipa', 'ips', 'bahasa', 'lainnya'])],
            'registration_date' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'achievements' => ['nullable', 'string', 'max:1000'],
            'organizations' => ['nullable', 'string', 'max:1000'],
            'scholarship_info' => ['nullable', 'string', 'max:200'],
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
