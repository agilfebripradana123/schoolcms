<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_academic_years_table.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul Academic Year - DDL + seed awal.
-- Format name konsisten dengan grades.academic_year ('2025/2026').
-- =====================================================================

CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(20) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_academic_years_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `academic_years` (`id`,`name`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'2024/2025',0,NOW(),NOW()),
(2,'2025/2026',1,NOW(),NOW()),
(3,'2026/2027',0,NOW(),NOW());

-- CLEANUP total modul:
-- DROP TABLE IF EXISTS academic_years;
-- =====================================================================
SQL;

        $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)), fn ($s) => $s !== '');
        foreach ($statements as $statement) {
            DB::connection('mysql')->statement($statement);
        }
    }

    public function down(): void
    {
        // drops in REVERSE dependency order (children first)
        foreach (['academic_years'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
