<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateIncomingLetterRequest extends FormRequest
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
                Rule::unique('incoming_letters', 'letter_number')->ignore($this->route('incoming_letter')),
            ],
            'sender' => [
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
            'received_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'letter_date' => [
                'nullable',
                'date',
            ],
            'category' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['undangan', 'permohonan', 'pemberitahuan', 'lainnya']),
            ],
            'is_important' => [
                'sometimes',
                'boolean',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['baru', 'diproses', 'selesai', 'diarsipkan']),
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
