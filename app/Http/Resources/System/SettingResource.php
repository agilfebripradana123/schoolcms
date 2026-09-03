<?php

namespace App\Http\Resources\System;

use App\Models\System\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Mask for encrypted/secret configuration values.
     */
    private const SECRET_MASK = '********';

    public function toArray(Request $request): array
    {
        $isSecret = $this->is_encrypted || Setting::isSecretType($this->type ?? '');

        return [
            'id' => $this->id,
            'group' => $this->group,
            'key' => $this->key,
            'type' => $this->type,
            'value' => $isSecret ? self::SECRET_MASK : $this->value,
            'description' => $this->description,
            'is_encrypted' => $this->is_encrypted,
            'is_public' => $this->is_public,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
