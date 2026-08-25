<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateCounselingRequest extends FormRequest
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
            'counselor_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'counseling_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'topic' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'follow_up' => [
                'nullable',
                'string',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['terjadwal', 'selesai', 'dibatalkan']),
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
