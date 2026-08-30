<?php

namespace App\Http\Requests\Api\Finance;

use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
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
                Rule::exists('billings', 'id')->whereNull('deleted_at'),
            ],
            'student_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
                $this->studentMatchesBillingRule(),
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
                'gt:0',
                $this->amountWithinOutstandingRule(),
            ],
            'method' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['cash', 'transfer', 'qris', 'lainnya']),
            ],
            'reference_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                $this->uniqueReferenceRule(),
            ],
            'received_by' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    private function studentMatchesBillingRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $billing = $this->billingToValidate();

            if ($billing !== null && (int) $billing->student_id !== (int) $value) {
                $fail('The student_id does not match the billing.student_id.');
            }
        };
    }

    private function amountWithinOutstandingRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $billing = $this->billingToValidate();

            if ($billing === null) {
                return;
            }

            $outstanding = $this->outstandingFor($billing);

            if ((float) $value > $outstanding) {
                $fail("The amount exceeds the billing outstanding amount of {$outstanding}.");
            }
        };
    }

    private function uniqueReferenceRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $currentId = $this->route('payment');

            $exists = Payment::query()
                ->whereNull('deleted_at')
                ->where(function ($q) use ($value) {
                    $q->where('reference_number', $value)
                        ->orWhere('ref_key', $value);
                })
                ->when($currentId !== null, fn ($q) => $q->where('id', '!=', $currentId))
                ->exists();

            if ($exists) {
                $fail('The reference_number has already been taken.');
            }
        };
    }

    private function billingToValidate(): ?Billing
    {
        if ($this->filled('billing_id')) {
            return Billing::find($this->input('billing_id'));
        }

        $currentId = $this->route('payment');
        $current = $currentId !== null ? Payment::with('billing')->find($currentId) : null;

        return $current?->billing;
    }

    private function outstandingFor(Billing $billing): float
    {
        $outstanding = $billing->amount - $this->successfulTransactionTotal($billing);

        return max(0.0, (float) $outstanding);
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
