<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 — Finance foundation (data backfill).
 *
 * `payments.ref_key` mirrors `reference_number` for existing rows:
 *   reference_number IS NOT NULL  → ref_key = reference_number
 *   reference_number IS NULL      → ref_key = NULL
 *
 * Idempotent: only rows with a reference number that still miss `ref_key`
 * are touched.
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    public function up(): void
    {
        $db = DB::connection(self::CONNECTION);

        $rows = $db->table('payments')
            ->whereNotNull('reference_number')
            ->whereNull('ref_key')
            ->get(['id', 'reference_number']);

        foreach ($rows as $payment) {
            $db->table('payments')
                ->where('id', $payment->id)
                ->update(['ref_key' => $payment->reference_number]);
        }
    }

    public function down(): void
    {
        $db = DB::connection(self::CONNECTION);

        $rows = $db->table('payments')->get(['id', 'reference_number', 'ref_key']);

        foreach ($rows as $payment) {
            if ($payment->reference_number !== null && $payment->ref_key === $payment->reference_number) {
                $db->table('payments')
                    ->where('id', $payment->id)
                    ->update(['ref_key' => null]);
            }
        }
    }
};
