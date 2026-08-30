<?php

namespace App\Http\Requests\Api\Finance;

use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
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
                Rule::exists('payments', 'id')->whereNull('deleted_at'),
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
                $this->signedAmountRule(),
                $this->refundCapRule(),
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

    private function signedAmountRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $type = $this->input('type');

            if ($type === 'payment' && (float) $value <= 0) {
                $fail('A payment transaction amount must be greater than zero.');
            }

            if ($type === 'refund' && (float) $value >= 0) {
                $fail('A refund transaction amount must be negative.');
            }

            if ($type === 'adjustment' && (float) $value == 0) {
                $fail('An adjustment transaction amount cannot be zero.');
            }
        };
    }

    private function refundCapRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($this->input('type') !== 'refund') {
                return;
            }

            $payment = $this->paymentToValidate();

            if ($payment?->billing === null) {
                return;
            }

            $net = $this->successfulTransactionTotal($payment->billing);
            $refundable = max(0.0, (float) $net);

            if (abs((float) $value) > $refundable) {
                $fail("The refund amount exceeds the refundable amount of {$refundable}.");
            }
        };
    }

    private function paymentToValidate(): ?Payment
    {
        $paymentId = $this->input('payment_id');

        return $paymentId !== null ? Payment::with('billing')->find($paymentId) : null;
    }

    private function successfulTransactionTotal(Billing $billing): float
    {
        $total = 0.0;

        foreach ($billing->payments as $payment) {
            foreach ($payment->transactions as $transaction) {
                if ($transaction->status === 'success') {
                    $total += (float) $transaction->amount;
                }
            }
        }

        return $total;
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
