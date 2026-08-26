<?php

namespace App\Http\Requests\Api\Academic;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'class_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('classes', 'id'),
            ],
            'teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'academic_year_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
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
