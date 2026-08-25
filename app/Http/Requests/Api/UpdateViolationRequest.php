<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateViolationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'category' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['ringan', 'sedang', 'berat']),
            ],
            'description' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'points' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
            ],
            'violated_at' => [
                'nullable',
                'date',
            ],
            'handled_by' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
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
