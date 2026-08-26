<?php

namespace App\Http\Requests\Api\Students;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreStudentIdCardRequest extends FormRequest
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
                Rule::unique('student_id_cards', 'student_id'),
            ],
            'card_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('student_id_cards', 'card_number'),
            ],
            'issued_date' => [
                'nullable',
                'date',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:issued_date',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['aktif', 'hilang', 'rusak', 'nonaktif']),
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
