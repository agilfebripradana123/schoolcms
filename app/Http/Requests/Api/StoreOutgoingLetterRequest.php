<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'letter_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('outgoing_letters', 'letter_number'),
            ],
            'recipient' => [
                'required',
                'string',
                'max:150',
            ],
            'subject' => [
                'required',
                'string',
                'max:200',
            ],
            'letter_date' => [
                'required',
                'date',
            ],
            'sent_date' => [
                'nullable',
                'date',
                'after_or_equal:letter_date',
            ],
            'category' => [
                'required',
                'string',
                Rule::in(['undangan', 'permohonan', 'pemberitahuan', 'lainnya']),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['draft', 'terkirim', 'diarsipkan']),
            ],
            'file_path' => [
                'nullable',
                'string',
                'max:255',
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
