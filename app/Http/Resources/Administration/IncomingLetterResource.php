<?php

namespace App\Http\Resources\Administration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IncomingLetterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'letter_number' => $this->letter_number,
            'sender' => $this->sender,
            'subject' => $this->subject,
            'received_date' => $this->received_date?->toDateString(),
            'letter_date' => $this->letter_date?->toDateString(),
            'category' => $this->category,
            'is_important' => (bool) $this->is_important,
            'status' => $this->status,
            'file_path' => $this->file_path,
            'notes' => $this->notes,
            'dispositions' => DispositionResource::collection($this->whenLoaded('dispositions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
