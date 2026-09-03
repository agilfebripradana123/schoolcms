<?php

namespace App\Http\Requests\Api\System;

use App\Models\System\Setting;
use Illuminate\Validation\Rule;

trait ValidatesSettingValue
{
    /**
     * Build value validation rules based on the configured type.
     */
    protected function valueRuleByType(string $type): array
    {
        return match ($type) {
            'integer' => ['nullable', 'integer'],
            'boolean' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email'],
            'url' => ['nullable', 'url'],
            'timezone' => ['nullable', 'timezone'],
            'time' => ['nullable', 'date_format:H:i'],
            // password / string / text / select / color / file: no strict format
            default => ['nullable', 'string'],
        };
    }

    /**
     * Validate that `type` (if present) is a supported configuration type.
     */
    protected function validTypeRule(): string
    {
        return Rule::in(Setting::SUPPORTED_TYPES);
    }
}
