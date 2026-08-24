<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id'),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'day' => [
                'required',
                'string',
                Rule::in(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']),
            ],
            'period_id' => [
                'required',
                'integer',
                Rule::exists('periods', 'id'),
            ],
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],
            'semester_id' => [
                'nullable',
                'integer',
                Rule::exists('semesters', 'id'),
            ],
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
