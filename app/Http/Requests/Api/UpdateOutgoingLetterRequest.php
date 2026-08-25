<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'letter_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('outgoing_letters', 'letter_number')->ignore($this->route('outgoing_letter')),
            ],
            'recipient' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'subject' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'letter_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'sent_date' => [
                'nullable',
                'date',
                'after_or_equal:letter_date',
            ],
            'category' => [
                'sometimes',
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
