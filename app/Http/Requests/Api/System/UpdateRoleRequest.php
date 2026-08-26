<?php

namespace App\Http\Requests\Api\System;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
                'max:50',
                Rule::unique('roles', 'name')->ignore($this->route('role')),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'permission_ids' => [
                'sometimes',
                'array',
            ],
            'permission_ids.*' => [
                'integer',
                Rule::exists('permissions', 'id'),
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
