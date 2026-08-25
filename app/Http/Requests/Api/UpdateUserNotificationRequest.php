<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'message' => [
                'sometimes',
                'required',
                'string',
            ],
            'type' => [
                'nullable',
                'string',
                'max:50',
            ],
            'is_read' => [
                'sometimes',
                'boolean',
            ],
            'read_at' => [
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
