<?php

namespace App\Http\Requests\Api\System;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'username' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'photo' => [
                'nullable',
                'string',
                'max:255',
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
