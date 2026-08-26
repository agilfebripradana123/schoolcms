<?php

namespace App\Http\Requests\Api\Development;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreViolationRequest extends FormRequest
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
            ],
            'category' => [
                'required',
                'string',
                Rule::in(['ringan', 'sedang', 'berat']),
            ],
            'description' => [
                'required',
                'string',
                'max:255',
            ],
            'points' => [
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
