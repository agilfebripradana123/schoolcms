<?php

namespace App\Http\Requests\Api\Finance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdatePaymentTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('payments', 'id')->whereNull('deleted_at'),
            ],
            'transaction_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('payment_transactions', 'transaction_code')
                    ->ignore($this->route('payment_transaction')),
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['payment', 'refund', 'adjustment']),
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
            ],
            'method' => [
                'sometimes',
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
                'sometimes',
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
