<?php

namespace App\Http\Requests\Api\Finance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateFinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'report_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['harian', 'bulanan', 'semester', 'tahunan', 'custom']),
            ],
            'period_start' => [
                'sometimes',
                'required',
                'date',
            ],
            'period_end' => [
                'sometimes',
                'required',
                'date',
                Rule::when($this->filled('period_start'), ['after_or_equal:period_start']),
            ],
            'generated_by' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'notes' => [
                'sometimes',
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
