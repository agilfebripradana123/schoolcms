<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTeacherDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],
            'document_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['sk', 'sertifikat', 'ijazah', 'kontrak', 'lainnya']),
            ],
            'file_path' => [
                'nullable',
                'string',
                'max:255',
            ],
            'issued_date' => [
                'nullable',
                'date',
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
