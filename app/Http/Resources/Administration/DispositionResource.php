<?php

namespace App\Http\Resources\Administration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DispositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'incoming_letter_id' => $this->incoming_letter_id,
            'assigned_to' => $this->assigned_to,
            'instruction' => $this->instruction,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toISOString(),
            'incoming_letter' => new IncomingLetterResource($this->whenLoaded('incomingLetter')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
