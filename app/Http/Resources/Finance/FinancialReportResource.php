<?php

namespace App\Http\Resources\Finance;

use App\Http\Resources\System\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'report_type' => $this->report_type,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'total_billed' => $this->total_billed,
            'total_paid' => $this->total_paid,
            'total_outstanding' => $this->total_outstanding,
            'generated_by' => $this->generated_by,
            'source_fingerprint' => $this->source_fingerprint,
            'notes' => $this->notes,
            'generator' => new UserResource($this->whenLoaded('generator')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
