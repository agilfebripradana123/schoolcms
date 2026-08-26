<?php

namespace App\Http\Requests\Api\Students;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreStudentParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
                Rule::unique('parents', 'student_id'),
            ],
            'father_name' => [
                'required',
                'string',
                'max:100',
            ],
            'mother_name' => [
                'required',
                'string',
                'max:100',
            ],
            'father_occupation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'mother_occupation' => [
                'nullable',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'address' => [
                'nullable',
                'string',
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
