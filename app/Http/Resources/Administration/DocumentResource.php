<?php

namespace App\Http\Resources\Administration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_number' => $this->document_number,
            'category' => $this->category,
            'file_path' => $this->file_path,
            'document_date' => $this->document_date?->toDateString(),
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
