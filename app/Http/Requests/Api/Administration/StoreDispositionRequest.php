<?php

namespace App\Http\Requests\Api\Administration;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDispositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'incoming_letter_id' => [
                'required',
                'integer',
                Rule::exists('incoming_letters', 'id'),
            ],
            'assigned_to' => [
                'required',
                'string',
                'max:150',
            ],
            'instruction' => [
                'nullable',
                'string',
            ],
            'due_date' => [
                'nullable',
                'date',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['belum', 'proses', 'selesai']),
            ],
            'completed_at' => [
                'nullable',
                'date',
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
