<?php

namespace App\Http\Requests\Api\Students;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                Rule::in(['masuk', 'keluar']),
            ],
            'from_school' => [
                'nullable',
                'string',
                'max:150',
            ],
            'to_school' => [
                'nullable',
                'string',
                'max:150',
            ],
            'transfer_date' => [
                'nullable',
                'date',
            ],
            'reason' => [
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
