<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 8 — Student identity & authorization foundation.
 *
 * Secures the users ↔ students 1:1 linkage that powers the future Student
 * Portal identity chain:
 *
 *   authenticated user → User::studentProfile() → students.user_id → Student
 *
 * 1. Adds a UNIQUE constraint on `students.user_id` so one account can never
 *    be linked to two student records. MySQL allows any number of NULLs, so
 *    existing unlinked student rows stay untouched.
 * 2. Rebinds the existing `students_user_id_foreign` FK from
 *    ON DELETE CASCADE to ON DELETE SET NULL (ON UPDATE SET NULL kept):
 *    hard-deleting a user must unlink the student instead of silently
 *    destroying the student record.
 *
 * Additive, guarded (idempotent) and reversible. Runs on the `mysql`
 * connection per this project's migration convention. No data rows are
 * touched — user/student linking is an explicit future operation.
 */
return new class extends Migration
{
    private const CONNECTION = 'mysql';

    public function up(): void
    {
        $db = DB::connection(self::CONNECTION);
        $schema = Schema::connection(self::CONNECTION);

        // 1) One user → at most one student (multiple NULLs allowed on MySQL).
        if ($schema->hasColumn('students', 'user_id')
            && ! $schema->hasIndex('students', 'students_user_id_unique')) {
            $schema->table('students', function (Blueprint $table): void {
                $table->unique('user_id', 'students_user_id_unique');
            });
        }

        // 2) FK: CASCADE → SET NULL (never destroy a student on user delete).
        if ($this->deleteRule($db) === 'CASCADE') {
            $db->statement('ALTER TABLE `students` DROP FOREIGN KEY `students_user_id_foreign`');
            $db->statement(
                'ALTER TABLE `students` '
                .'ADD CONSTRAINT `students_user_id_foreign` '
                .'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) '
                .'ON DELETE SET NULL ON UPDATE SET NULL'
            );
        }
    }

    public function down(): void
    {
        $db = DB::connection(self::CONNECTION);
        $schema = Schema::connection(self::CONNECTION);

        if ($schema->hasIndex('students', 'students_user_id_unique')) {
            $schema->table('students', fn (Blueprint $table) => $table->dropUnique('students_user_id_unique'));
        }

        if ($this->deleteRule($db) === 'SET NULL') {
            $db->statement('ALTER TABLE `students` DROP FOREIGN KEY `students_user_id_foreign`');
            $db->statement(
                'ALTER TABLE `students` '
                .'ADD CONSTRAINT `students_user_id_foreign` '
                .'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) '
                .'ON DELETE CASCADE ON UPDATE SET NULL'
            );
        }
    }

    private function deleteRule(Connection $db): ?string
    {
        return $db->table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $db->getDatabaseName())
            ->where('TABLE_NAME', 'students')
            ->where('CONSTRAINT_NAME', 'students_user_id_foreign')
            ->value('DELETE_RULE');
    }
};
