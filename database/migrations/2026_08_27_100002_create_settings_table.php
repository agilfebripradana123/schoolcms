<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_settings_table.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul Settings - DDL + seed awal.
-- CATATAN: nama kolom `key` adalah reserved word MySQL - selalu pakai
-- backtick di SQL mentah; Eloquent/Query Builder mengutip otomatis.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(100) NOT NULL,
  `value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `settings` (`id`,`key`,`value`,`description`,`created_at`,`updated_at`) VALUES
(1,'app_name','SchoolCMS','Nama aplikasi',NOW(),NOW()),
(2,'school_name','SMA Negeri 1 Contoh','Nama sekolah',NOW(),NOW()),
(3,'timezone','Asia/Jakarta','Zona waktu aplikasi',NOW(),NOW()),
(4,'current_academic_year','2025/2026','Tahun ajaran berjalan',NOW(),NOW());

-- CLEANUP modul:
-- DROP TABLE IF EXISTS settings;
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
        foreach (['settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
