<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Finance foundation (structural).
 *
 * `billings`
 *  - add `period_start` / `period_end` (billing window)
 *  - add `uniq_key` (soft-unique duplicate protection:
 *      `student_id|fee_type_id|period_start|period_end`, NULL when cancelled)
 *  - add `deleted_at` (soft delete)
 *  - duplicate protection moves from UNIQUE(student_id,fee_type_id,academic_year_id)
 *    to UNIQUE(uniq_key); the legacy unique index is dropped.
 *  - FK delete behavior becomes RESTRICT for student/fee_type/academic_year and
 *    SET NULL for semester (financial history must not cascade away).
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
        return Schema::connection(self::CONNECTION)->hasColumn('billings', $column);
    }

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        // 1) New columns ----------------------------------------------------
        if (! $this->hasColumn('period_start')) {
            $schema->table('billings', fn (Blueprint $t) => $t->date('period_start')->nullable()->after('semester_id'));
        }

        if (! $this->hasColumn('period_end')) {
            $schema->table('billings', fn (Blueprint $t) => $t->date('period_end')->nullable()->after('period_start'));
        }

        if (! $this->hasColumn('uniq_key')) {
            $schema->table('billings', fn (Blueprint $t) => $t->string('uniq_key', 255)->nullable()->after('period_end'));
        }

        if (! $this->hasColumn('deleted_at')) {
            $schema->table('billings', fn (Blueprint $t) => $t->dateTime('deleted_at')->nullable()->after('updated_at'));
        }

        // 2) Drop legacy FKs (financial-history protection) ----------------
        foreach (['fk_bill_student', 'fk_bill_feetype', 'fk_bill_ay', 'fk_bill_semester'] as $fk) {
            if ($this->fkExists('billings', $fk)) {
                $schema->table('billings', fn (Blueprint $t) => $t->dropForeign($fk));
            }
        }

        // 3) Drop legacy composite unique duplicate protection ---------------
        if ($this->indexExists('billings', 'uniq_billings')) {
            $schema->table('billings', fn (Blueprint $t) => $t->dropUnique('uniq_billings'));
        }

        // 4) Indexes supporting the new FKs + unique `uniq_key` ------------
        if (! $this->indexExists('billings', 'billings_student_idx')) {
            $schema->table('billings', fn (Blueprint $t) => $t->index('student_id', 'billings_student_idx'));
        }

        if (! $this->indexExists('billings', 'billings_fee_type_idx')) {
            $schema->table('billings', fn (Blueprint $t) => $t->index('fee_type_id', 'billings_fee_type_idx'));
        }

        if (! $this->indexExists('billings', 'fk_bill_ay')) {
            $schema->table('billings', fn (Blueprint $t) => $t->index('academic_year_id', 'fk_bill_ay'));
        }

        if (! $this->indexExists('billings', 'fk_bill_semester')) {
            $schema->table('billings', fn (Blueprint $t) => $t->index('semester_id', 'fk_bill_semester'));
        }

        if (! $this->indexExists('billings', 'uniq_billings_uniqkey')) {
            $schema->table('billings', fn (Blueprint $t) => $t->unique('uniq_key', 'uniq_billings_uniqkey'));
        }

        // 5) Re-add FKs with protected delete behavior -----------------------
        if (! $this->fkExists('billings', 'fk_bill_student')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('student_id', 'fk_bill_student')->references('id')->on('students')->onDelete('restrict'));
        }

        if (! $this->fkExists('billings', 'fk_bill_feetype')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('fee_type_id', 'fk_bill_feetype')->references('id')->on('fee_types')->onDelete('restrict'));
        }

        if (! $this->fkExists('billings', 'fk_bill_ay')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('academic_year_id', 'fk_bill_ay')->references('id')->on('academic_years')->onDelete('restrict'));
        }

        if (! $this->fkExists('billings', 'fk_bill_semester')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('semester_id', 'fk_bill_semester')->references('id')->on('semesters')->onDelete('set null'));
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        // 1) Drop phase-1 FKs first -----------------------------------------
        foreach (['fk_bill_student', 'fk_bill_feetype', 'fk_bill_ay', 'fk_bill_semester'] as $fk) {
            if ($this->fkExists('billings', $fk)) {
                $schema->table('billings', fn (Blueprint $t) => $t->dropForeign($fk));
            }
        }

        // 2) Drop phase-1 indexes ---------------------------------------------
        if ($this->indexExists('billings', 'uniq_billings_uniqkey')) {
            $schema->table('billings', fn (Blueprint $t) => $t->dropUnique('uniq_billings_uniqkey'));
        }

        if ($this->indexExists('billings', 'billings_student_idx')) {
            $schema->table('billings', fn (Blueprint $t) => $t->dropIndex('billings_student_idx'));
        }

        // 3) Restore legacy composite unique -----------------------------------
        if (! $this->indexExists('billings', 'uniq_billings')) {
            $schema->table('billings', fn (Blueprint $t) => $t->unique(['student_id', 'fee_type_id', 'academic_year_id'], 'uniq_billings'));
        }

        // 4) Restore original FKs (cascade) ------------------------------------
        if (! $this->fkExists('billings', 'fk_bill_student')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('student_id', 'fk_bill_student')->references('id')->on('students')->onDelete('cascade'));
        }

        if (! $this->fkExists('billings', 'fk_bill_feetype')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('fee_type_id', 'fk_bill_feetype')->references('id')->on('fee_types')->onDelete('cascade'));
        }

        if (! $this->fkExists('billings', 'fk_bill_ay')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('academic_year_id', 'fk_bill_ay')->references('id')->on('academic_years')->onDelete('cascade'));
        }

        if (! $this->fkExists('billings', 'fk_bill_semester')) {
            $schema->table('billings', fn (Blueprint $t) => $t->foreign('semester_id', 'fk_bill_semester')->references('id')->on('semesters')->onDelete('set null'));
        }

        // 5) Drop added columns --------------------------------------------------
        foreach (['uniq_key', 'period_end', 'period_start', 'deleted_at'] as $column) {
            if ($this->hasColumn($column)) {
                $schema->table('billings', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }
    }
};
