<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Finance foundation (structural).
 *
 * `financial_reports`
 *  - add `source_fingerprint` (hash of the aggregation that generated the
 *    snapshot, so stored totals remain verifiable against the source data).
 *  No other structural change (generated_by stays SET NULL).
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn('financial_reports', 'source_fingerprint')) {
            $schema->table('financial_reports', fn (Blueprint $t) => $t->string('source_fingerprint', 64)->nullable()->after('generated_by'));
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if ($schema->hasColumn('financial_reports', 'source_fingerprint')) {
            $schema->table('financial_reports', fn (Blueprint $t) => $t->dropColumn('source_fingerprint'));
        }
    }
};
