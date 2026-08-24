<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'photo' => $this->photo,
            'is_active' => $this->is_active,
            'role' => new \App\Http\Resources\RoleResource($this->whenLoaded('role')),
        ];
    }
}
