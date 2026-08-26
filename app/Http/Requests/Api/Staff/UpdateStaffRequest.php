<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'staff_number' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('staff', 'staff_number')->ignore($this->route('staff')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'position' => [
                'sometimes',
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
