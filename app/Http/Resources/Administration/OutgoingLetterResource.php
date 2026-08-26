<?php

namespace App\Http\Resources\Administration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutgoingLetterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'letter_number' => $this->letter_number,
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'letter_date' => $this->letter_date?->toDateString(),
            'sent_date' => $this->sent_date?->toDateString(),
            'category' => $this->category,
            'status' => $this->status,
            'file_path' => $this->file_path,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
