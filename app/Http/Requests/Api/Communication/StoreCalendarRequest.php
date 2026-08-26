<?php

namespace App\Http\Requests\Api\Communication;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreCalendarRequest extends FormRequest
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
            'description' => [
                'nullable',
                'string',
            ],
            'event_date' => [
                'required',
                'date',
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['umum', 'ujian', 'libur', 'kegiatan', 'rapat']),
            ],
            'academic_year_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_years', 'id'),
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
