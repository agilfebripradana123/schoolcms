<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StorePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => [
                'required',
                'integer',
                Rule::exists('payments', 'id'),
            ],
            'transaction_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_transactions', 'transaction_code'),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['payment', 'refund', 'adjustment']),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0',
            ],
            'method' => [
                'required',
                'string',
                Rule::in(['cash', 'transfer', 'qris', 'lainnya']),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['success', 'pending', 'failed']),
            ],
            'transaction_date' => [
                'required',
                'date',
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
