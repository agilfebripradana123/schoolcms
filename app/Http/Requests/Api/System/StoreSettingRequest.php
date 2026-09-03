<?php

namespace App\Http\Requests\Api\System;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreSettingRequest extends FormRequest
{
    use ValidatesSettingValue;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group' => [
                'required',
                'string',
                'max:50',
            ],
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('settings', 'key'),
            ],
            'value' => $this->valueRuleByType($this->input('type', 'string')),
            'type' => [
                'required',
                'string',
                $this->validTypeRule(),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_encrypted' => [
                'nullable',
                'boolean',
            ],
            'is_public' => [
                'nullable',
                'boolean',
            ],
            'sort_order' => [
                'nullable',
                'integer',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_encrypted' => $this->boolean('is_encrypted'),
            'is_public' => $this->boolean('is_public'),
            'sort_order' => (int) ($this->input('sort_order', 0)),
        ]);
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
