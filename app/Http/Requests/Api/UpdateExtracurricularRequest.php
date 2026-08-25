<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateExtracurricularRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('extracurriculums', 'name')->ignore($this->route('extracurricular')),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'supervisor_id' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'schedule_day' => [
                'nullable',
                'string',
                Rule::in(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu']),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
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
