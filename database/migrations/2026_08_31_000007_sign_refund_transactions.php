<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 — Finance foundation (data backfill).
 *
 * Signed-ledger semantics: `refund` transactions reduce the net balance, so
 * their stored amount becomes negative (`50000 → -50000`).
 *
 * Only `type = 'refund'` rows with a positive amount are touched.
 * `payment` and `adjustment` rows are left untouched in this phase.
 * Idempotent: after this migration no refund row holds `amount >= 0`.
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    public function up(): void
    {
        DB::connection(self::CONNECTION)
            ->table('payment_transactions')
            ->where('type', 'refund')
            ->where('amount', '>', 0)
            ->update(['amount' => DB::raw('-amount')]);
    }

    public function down(): void
    {
        DB::connection(self::CONNECTION)
            ->table('payment_transactions')
            ->where('type', 'refund')
            ->where('amount', '<', 0)
            ->update(['amount' => DB::raw('ABS(amount)')]);
    }
};
