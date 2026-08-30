<?php

namespace App\Http\Resources\Student\Finance;

use App\Http\Resources\Academic\AcademicYearResource;
use App\Http\Resources\Academic\SemesterResource;
use App\Http\Resources\Finance\FeeTypeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Student Portal billing snapshot.
 *
 * `paid` / `outstanding` are derived values — never stored columns:
 *
 *   paid        = SUM(successful payment_transactions.amount) across the
 *                 billing's active payments (signed ledger — refunds subtract)
 *   outstanding = billing.amount - paid
 *
 * The controller eager-loads `payments.transactions` so no per-row queries
 * are executed here.
 */
class StudentBillingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paid = $this->ledgerPaid();
        $outstanding = (float) $this->amount - $paid;

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
            'paid' => number_format($paid, 2, '.', ''),
            'outstanding' => number_format($outstanding, 2, '.', ''),
            'fee_type' => new FeeTypeResource($this->whenLoaded('feeType')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'semester' => new SemesterResource($this->whenLoaded('semester')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

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
