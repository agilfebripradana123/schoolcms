<?php

namespace App\Http\Requests\Api\Finance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateScholarshipRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'provider' => [
                'nullable',
                'string',
                'max:150',
            ],
            'amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['aktif', 'selesai', 'dibatalkan']),
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
