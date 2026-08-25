<?php

namespace App\Http\Requests\Api;

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
                Rule::unique('billings')->where(
                    fn ($q) => $q->where('student_id', $this->input('student_id'))
                        ->where('fee_type_id', $this->input('fee_type_id'))
                        ->where('academic_year_id', $this->input('academic_year_id'))
                )->ignore($this->route('billing')),
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
                'nullable',
                'date',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['unpaid', 'partial', 'paid', 'cancelled']),
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
