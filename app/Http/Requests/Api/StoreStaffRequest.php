<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('staff', 'staff_number'),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'position' => [
                'required',
                'string',
                'max:100',
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
            ],
            'is_active' => [
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
