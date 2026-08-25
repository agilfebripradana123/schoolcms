<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'billing_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('billings', 'id'),
            ],
            'student_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'payment_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'method' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['cash', 'transfer', 'qris', 'lainnya']),
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'received_by' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
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
