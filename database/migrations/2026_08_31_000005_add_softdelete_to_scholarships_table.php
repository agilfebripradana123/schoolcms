<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Finance foundation (structural).
 *
 * `scholarships`
 *  - add `deleted_at` (soft delete)
 *  - FK delete behavior becomes RESTRICT on students.id.
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
            [$db, 'scholarships', $fk]
        );

        return (int) $row[0]->c > 0;
    }

    private function indexExists(string $index): bool
    {
        $db = DB::connection(self::CONNECTION)->getDatabaseName();

        $row = DB::select(
            'SELECT COUNT(*) AS c FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$db, 'scholarships', $index]
        );

        return (int) $row[0]->c > 0;
    }

    public function up(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if (! $schema->hasColumn('scholarships', 'deleted_at')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->dateTime('deleted_at')->nullable()->after('updated_at'));
        }

        if ($this->fkExists('fk_sch_student')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->dropForeign('fk_sch_student'));
        }

        if (! $this->indexExists('scholarships_student_idx')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->index('student_id', 'scholarships_student_idx'));
        }

        if (! $this->fkExists('fk_sch_student')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->foreign('student_id', 'fk_sch_student')->references('id')->on('students')->onDelete('restrict'));
        }
    }

    public function down(): void
    {
        $schema = Schema::connection(self::CONNECTION);

        if ($this->fkExists('fk_sch_student')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->dropForeign('fk_sch_student'));
        }

        if (! $this->fkExists('fk_sch_student')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->foreign('student_id', 'fk_sch_student')->references('id')->on('students')->onDelete('cascade'));
        }

        if ($schema->hasColumn('scholarships', 'deleted_at')) {
            $schema->table('scholarships', fn (Blueprint $t) => $t->dropColumn('deleted_at'));
        }
    }
};
