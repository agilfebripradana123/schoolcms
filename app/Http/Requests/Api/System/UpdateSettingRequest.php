<?php

namespace App\Http\Requests\Api\System;

use App\Models\System\Setting;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    use ValidatesSettingValue;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $existingType = null;
        $id = $this->route('setting');
        if ($id !== null && is_numeric($id)) {
            $existing = Setting::find((int) $id);
            $existingType = $existing?->type;
        }

        $effectiveType = $this->input('type', $existingType ?? 'string');

        $rules = [
            'group' => [
                'sometimes',
                'required',
                'string',
                'max:50',
            ],
            'key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('settings', 'key')->ignore($id),
            ],
            'value' => $this->valueRuleByType($effectiveType),
            'type' => [
                'sometimes',
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

        // value is nullable on update so a secret can be left untouched.
        if ($this->has('type')) {
            $rules['value'] = $this->valueRuleByType($this->input('type'));
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_encrypted')) {
            $this->merge(['is_encrypted' => $this->boolean('is_encrypted')]);
        }
        if ($this->has('is_public')) {
            $this->merge(['is_public' => $this->boolean('is_public')]);
        }
        if ($this->has('sort_order')) {
            $this->merge(['sort_order' => (int) $this->input('sort_order')]);
        }
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
