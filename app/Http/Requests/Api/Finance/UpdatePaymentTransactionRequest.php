<?php

namespace App\Http\Requests\Api\Finance;

use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentTransaction;
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
                $this->signedAmountRule(),
                $this->refundCapRule(),
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

    private function signedAmountRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $type = $this->effectiveType();

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
            if ($this->effectiveType() !== 'refund') {
                return;
            }

            $payment = $this->paymentToValidate();

            if ($payment?->billing === null) {
                return;
            }

            $net = $this->successfulTransactionTotal(
                $payment->billing,
                $this->route('payment_transaction')
            );
            $refundable = max(0.0, (float) $net);

            if (abs((float) $value) > $refundable) {
                $fail("The refund amount exceeds the refundable amount of {$refundable}.");
            }
        };
    }

    private function effectiveType(): ?string
    {
        if ($this->filled('type')) {
            return $this->input('type');
        }

        $currentId = $this->route('payment_transaction');
        $current = $currentId !== null ? PaymentTransaction::find($currentId) : null;

        return $current?->type;
    }

    private function paymentToValidate(): ?Payment
    {
        if ($this->filled('payment_id')) {
            return Payment::with('billing')->find($this->input('payment_id'));
        }

        $currentId = $this->route('payment_transaction');
        $current = $currentId !== null
            ? PaymentTransaction::with('payment.billing')->find($currentId)
            : null;

        return $current?->payment;
    }

    private function successfulTransactionTotal(Billing $billing, ?int $excludeTransactionId = null): float
    {
        $total = 0.0;

        foreach ($billing->payments as $payment) {
            foreach ($payment->transactions as $transaction) {
                if ($transaction->status !== 'success') {
                    continue;
                }

                if ($excludeTransactionId !== null && (int) $transaction->id === (int) $excludeTransactionId) {
                    continue;
                }

                $total += (float) $transaction->amount;
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
