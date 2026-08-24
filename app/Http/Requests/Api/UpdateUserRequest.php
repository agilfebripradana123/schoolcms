<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('roles', 'id'),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'username' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'username')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('user')),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('user')),
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:6',
            ],
            'photo' => [
                'nullable',
                'string',
                'max:255',
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
