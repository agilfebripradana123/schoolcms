<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Finance foundation (structural).
 *
 * `payment_transactions`
 *  - add `deleted_at` (soft delete)
 *  - FK delete behavior becomes RESTRICT on payments.id
 *    (ledger entries are financial history and must not cascade away).
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    private function fkExists(string $fk): bool
    {
        $db = DB::connection(self::CONNECTION)->getDatabaseName();

        $row = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$db, 'payment_transactions', $fk]
        );

        return (int) $row[0]->c > 0;
    }

    private function indexExists(string $index): bool
    {
        $db = DB::connection(self::CONNECTION)->getDatabaseName();

        $row = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db, 'payment_transactions', $index]
        );

        return (int) $row[0]->c > 0;
    }

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn('payment_transactions', 'deleted_at')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->dateTime('deleted_at')->nullable()->after('updated_at'));
        }

        if ($this->fkExists('fk_ptx_payment')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->dropForeign('fk_ptx_payment'));
        }

        // The supporting index is normally auto-created by MySQL along with the
        // constraint; re-create it deterministically if MySQL removed it.
        if (! $this->indexExists('fk_ptx_payment')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->index('payment_id', 'fk_ptx_payment'));
        }

        if (! $this->fkExists('fk_ptx_payment')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->foreign('payment_id', 'fk_ptx_payment')->references('id')->on('payments')->onDelete('restrict'));
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if ($this->fkExists('fk_ptx_payment')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->dropForeign('fk_ptx_payment'));
        }

        if (! $this->fkExists('fk_ptx_payment')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->foreign('payment_id', 'fk_ptx_payment')->references('id')->on('payments')->onDelete('cascade'));
        }

        if ($schema->hasColumn('payment_transactions', 'deleted_at')) {
            $schema->table('payment_transactions', fn (Blueprint $t) => $t->dropColumn('deleted_at'));
        }
    }
};
