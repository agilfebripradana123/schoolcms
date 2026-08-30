<?php

namespace App\Http\Requests\Api\Finance;

use App\Models\Finance\Billing;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateBillingRequest extends FormRequest
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
                $this->duplicateActiveBillingRule(),
            ],
            'fee_type_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('fee_types', 'id'),
            ],
            'academic_year_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],
            'semester_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('semesters', 'id'),
            ],
            'amount' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
            ],
            'due_date' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'period_start' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'period_end' => [
                'sometimes',
                'nullable',
                'date',
                Rule::when($this->filled('period_start'), ['after_or_equal:period_start']),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['unpaid', 'partial', 'paid', 'cancelled']),
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Application-level guard mirroring the Phase 1 `billings.uniq_key`
     * protection: the business identity is
     * (student_id, fee_type_id, period_start, period_end) and only
     * non-cancelled billings participate.
     */
    private function duplicateActiveBillingRule(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if ($this->input('fee_type_id') === null) {
                return;
            }

            $periodStart = $this->normalizeDate($this->input('period_start'));
            $periodEnd = $this->normalizeDate($this->input('period_end'));

            // Unparseable date input is left to the date rules — never let it
            // reach the DB comparison (MySQL would reject it as a 500).
            if ($periodStart === false || $periodEnd === false) {
                return;
            }

            $currentId = $this->route('billing');

            $query = Billing::query()
                ->where('student_id', $value)
                ->where('fee_type_id', $this->input('fee_type_id'))
                ->where('status', '!=', 'cancelled')
                ->when($currentId !== null, fn ($q) => $q->where('id', '!=', $currentId));

            $this->applyPeriodEquality($query, 'period_start', $periodStart);
            $this->applyPeriodEquality($query, 'period_end', $periodEnd);

            if ($query->exists()) {
                $fail('The student already has an active billing for this fee type and period.');
            }
        };
    }

    private function applyPeriodEquality($query, string $field, ?string $value): void
    {
        if ($value === null) {
            $query->whereNull($field);
        } else {
            $query->where($field, $value);
        }
    }

    /**
     * @return null|false|string Y-m-d when parseable, null when absent/empty, false when invalid.
     */
    private function normalizeDate(mixed $value): string|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return false;
        }
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
