<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $table = 'settings';

    public const SUPPORTED_TYPES = [
        'string',
        'text',
        'integer',
        'boolean',
        'select',
        'email',
        'url',
        'password',
        'timezone',
        'time',
        'color',
        'file',
    ];

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'description',
        'is_encrypted',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Decrypt and return the actual value. Returns null when the value is
     * empty. Never expose the result of this method through an API resource —
     * use accessor/masking for responses.
     */
    public function decryptValue(): ?string
    {
        if (! $this->is_encrypted || $this->value === null || $this->value === '') {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Normalize a stored value into a PHP scalar matching the configured type.
     * Used by controllers/services when reading values by type.
     */
    public function typedValue(): mixed
    {
        $value = $this->decryptValue();

        return match ($this->type) {
            'integer' => $value === null || $value === '' ? null : (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    public static function isSecretType(string $type): bool
    {
        return in_array($type, ['password'], true);
    }
}
