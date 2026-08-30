<?php

namespace App\Http\Requests\Api\Finance;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreFinancialReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:200',
            ],
            'report_type' => [
                'required',
                'string',
                Rule::in(['harian', 'bulanan', 'semester', 'tahunan', 'custom']),
            ],
            'period_start' => [
                'required',
                'date',
            ],
            'period_end' => [
                'required',
                'date',
                'after_or_equal:period_start',
            ],
            'notes' => [
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
