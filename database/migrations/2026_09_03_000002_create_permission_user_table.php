<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration: direct user permission assignment.
 *
 * Adds the `permission_user` pivot table so an admin can assign permissions
 * directly to a user (independent of the user's role → permission_role).
 * Mirrors the existing `permission_role` pivot DDL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `permission_user` (
  `permission_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `user_id`),
  KEY `permission_user_user_id_index` (`user_id`),
  CONSTRAINT `fk_permission_user_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_permission_user_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL;

        $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)), fn ($s) => $s !== '');
        foreach ($statements as $statement) {
            DB::connection('mysql')->statement($statement);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_user');
    }
};
