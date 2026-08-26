<?php

namespace App\Http\Resources\Examination;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Facilities\RoomResource;
class ExamScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'room_id' => $this->room_id,
            'session_id' => $this->session_id,
            'exam_date' => $this->exam_date?->toDateString(),
            'exam' => new ExamResource($this->whenLoaded('exam')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'session' => new ExamSessionResource($this->whenLoaded('session')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
