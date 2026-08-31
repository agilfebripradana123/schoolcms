<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Academic\AcademicYearResource;
use App\Http\Resources\Academic\SemesterResource;
class BillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasPayments = $this->relationLoaded('payments');
        $paid = $hasPayments ? $this->ledgerPaid() : null;
        $outstanding = $hasPayments ? (float) $this->amount - $paid : null;

        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'fee_type_id' => $this->fee_type_id,
            'academic_year_id' => $this->academic_year_id,
            'semester_id' => $this->semester_id,
            'amount' => $this->amount,
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            $this->mergeWhen($hasPayments, [
                'paid' => number_format($paid, 2, '.', ''),
                'outstanding' => number_format($outstanding, 2, '.', ''),
            ]),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'student' => new StudentResource($this->whenLoaded('student')),
            'fee_type' => new FeeTypeResource($this->whenLoaded('feeType')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Sum of successful payment transaction amounts linked to the billing
     * through its payments. Mirrors BillingService::netPaid() and the
     * StudentBillingResource ledger derivation — the ledger is the source of
     * truth for financial reconciliation, not the payment rows themselves.
     * Pending/failed transactions are excluded.
     */
    private function ledgerPaid(): float
    {
        $paid = 0.0;

        foreach ($this->resource->payments as $payment) {
            foreach ($payment->transactions as $transaction) {
                if ($transaction->status === 'success') {
                    $paid += (float) $transaction->amount;
                }
            }
        }

        return $paid;
    }
}
