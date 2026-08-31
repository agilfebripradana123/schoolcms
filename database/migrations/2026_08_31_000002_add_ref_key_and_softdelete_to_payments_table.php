<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Finance foundation (structural).
 *
 * `payments`
 *  - add `ref_key` (nullable soft-unique mirror of `reference_number`)
 *  - add `deleted_at` (soft delete)
 *  - unique index on `ref_key` prevents duplicate payment references
 *  - FK delete behavior becomes RESTRICT for billing_id/student_id
 *    (`received_by` stays SET NULL).
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    private function fkExists(string $table, string $fk): bool
    {
        $db = DB::connection(self::CONNECTION)->getDatabaseName();

        $row = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$db, $table, $fk]
        );

        return (int) $row[0]->c > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = DB::connection(self::CONNECTION)->getDatabaseName();

        $row = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db, $table, $index]
        );

        return (int) $row[0]->c > 0;
    }

    private function hasColumn(string $column): bool
    {
        return Schema::connection(self::CONNECTION)->hasColumn('payments', $column);
    }

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        // 1) New columns ----------------------------------------------------
        if (! $this->hasColumn('ref_key')) {
            $schema->table('payments', fn (Blueprint $t) => $t->string('ref_key', 50)->nullable()->after('reference_number'));
        }

        if (! $this->hasColumn('deleted_at')) {
            $schema->table('payments', fn (Blueprint $t) => $t->dateTime('deleted_at')->nullable()->after('updated_at'));
        }

        // 2) Drop legacy FKs (financial-history protection) ----------------
        foreach (['fk_pay_billing', 'fk_pay_student'] as $fk) {
            if ($this->fkExists('payments', $fk)) {
                $schema->table('payments', fn (Blueprint $t) => $t->dropForeign($fk));
            }
        }

        // 3) Ensure supporting indexes exist (explicit FK columns) ----------
        if (! $this->indexExists('payments', 'payments_billing_idx')) {
            $schema->table('payments', fn (Blueprint $t) => $t->index('billing_id', 'payments_billing_idx'));
        }

        if (! $this->indexExists('payments', 'payments_student_idx')) {
            $schema->table('payments', fn (Blueprint $t) => $t->index('student_id', 'payments_student_idx'));
        }

        if (! $this->indexExists('payments', 'uniq_payments_refkey')) {
            $schema->table('payments', fn (Blueprint $t) => $t->unique('ref_key', 'uniq_payments_refkey'));
        }

        // 4) Re-add FKs with protected delete behavior -----------------------
        if (! $this->fkExists('payments', 'fk_pay_billing')) {
            $schema->table('payments', fn (Blueprint $t) => $t->foreign('billing_id', 'fk_pay_billing')->references('id')->on('billings')->onDelete('restrict'));
        }

        if (! $this->fkExists('payments', 'fk_pay_student')) {
            $schema->table('payments', fn (Blueprint $t) => $t->foreign('student_id', 'fk_pay_student')->references('id')->on('students')->onDelete('restrict'));
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        foreach (['fk_pay_student', 'fk_pay_billing'] as $fk) {
            if ($this->fkExists('payments', $fk)) {
                $schema->table('payments', fn (Blueprint $t) => $t->dropForeign($fk));
            }
        }

        if ($this->indexExists('payments', 'uniq_payments_refkey')) {
            $schema->table('payments', fn (Blueprint $t) => $t->dropUnique('uniq_payments_refkey'));
        }

        if (! $this->fkExists('payments', 'fk_pay_billing')) {
            $schema->table('payments', fn (Blueprint $t) => $t->foreign('billing_id', 'fk_pay_billing')->references('id')->on('billings')->onDelete('cascade'));
        }

        if (! $this->fkExists('payments', 'fk_pay_student')) {
            $schema->table('payments', fn (Blueprint $t) => $t->foreign('student_id', 'fk_pay_student')->references('id')->on('students')->onDelete('cascade'));
        }

        foreach (['ref_key', 'deleted_at'] as $column) {
            if ($this->hasColumn($column)) {
                $schema->table('payments', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }
};
