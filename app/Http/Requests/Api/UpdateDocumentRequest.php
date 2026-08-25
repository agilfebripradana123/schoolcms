<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
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
            'document_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('documents', 'document_number')->ignore($this->route('document')),
            ],
            'category' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['sk', 'peraturan', 'sop', 'laporan', 'formulir', 'lainnya']),
            ],
            'file_path' => [
                'nullable',
                'string',
                'max:255',
            ],
            'document_date' => [
                'nullable',
                'date',
            ],
            'description' => [
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
