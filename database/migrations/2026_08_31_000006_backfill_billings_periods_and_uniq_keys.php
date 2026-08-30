<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 — Finance foundation (data backfill).
 *
 * For every existing billing lacking a window:
 *  - SPP-style fee types (name starting with "SPP") get a monthly window
 *    derived from `due_date` (fallback `created_at`).
 *  - All other fee types have no safely derivable window (no semester/academic
 *    year dates exist in the data) and are left untouched — their rows are
 *    reported on stdout instead of being guessed.
 *
 * `uniq_key` is computed as `student_id|fee_type_id|period_start|period_end`
 * for every non-cancelled billing (empty string for NULL period parts).
 * Cancelled billings always keep `uniq_key = NULL`.
 *
 * Idempotent: only rows that still have no `uniq_key` AND no period are touched.
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    public function up(): void
    {
        $db = DB::connection(self::CONNECTION);

        $feeTypes = $db->table('fee_types')->select('id', 'name')->get()->keyBy('id');

        $rows = $db->table('billings')
            ->select('id', 'student_id', 'fee_type_id', 'due_date', 'created_at', 'status')
            ->whereNull('uniq_key')
            ->whereNull('period_start')
            ->whereNull('period_end')
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        $reported = [];

        foreach ($rows as $billing) {
            $feeName = isset($feeTypes[$billing->fee_type_id]) ? $feeTypes[$billing->fee_type_id]->name : '';
            $periodStart = null;
            $periodEnd = null;

            if (str_starts_with($feeName, 'SPP')) {
                $reference = $billing->due_date ?: $billing->created_at;
                $ts = $reference ? strtotime((string) $reference) : false;

                if ($ts !== false) {
                    $periodStart = date('Y-m-01', $ts);
                    $periodEnd = date('Y-m-t', $ts);
                }
            }

            if (! $periodStart) {
                $reported[] = sprintf(
                    'billing id=%d (student=%d, fee_type=%d "%s"): period window cannot be derived safely from existing data — left NULL (uniq_key computed with empty period).',
                    $billing->id,
                    $billing->student_id,
                    $billing->fee_type_id,
                    $feeName
                );
            }

            $db->table('billings')
                ->where('id', $billing->id)
                ->update([
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'uniq_key' => sprintf(
                        '%d|%d|%s|%s',
                        $billing->student_id,
                        $billing->fee_type_id,
                        $periodStart ?? '',
                        $periodEnd ?? ''
                    ),
                ]);
        }

        if ($reported !== []) {
            fwrite(STDOUT, "[2026_08_31_000006] Billing period backfill — rows left without a period window:\n");
            foreach ($reported as $line) {
                fwrite(STDOUT, "  - {$line}\n");
            }
        }
    }

    /**
     * Revert the phase-1 backfill. Only restores the pre-foundation state
     * (periods + uniq_key cleared for all rows). Documented limitation:
     * this cannot distinguish phase-1 values from values written by later
     * phases, so rollback must run before any later phase has written data.
     */
    public function down(): void
    {
        $db = DB::connection(self::CONNECTION);

        $db->table('billings')->update([
            'uniq_key' => null,
            'period_end' => null,
            'period_start' => null,
        ]);
    }
};
