<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_counseling_extracurricular_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul Counseling/BK + Extracurricular.
-- Seed id 100+, merujuk students 52-63 dan teachers 136-147 yang ada.
-- =====================================================================

-- 1) COUNSELINGS (sesi bimbingan konseling)
CREATE TABLE IF NOT EXISTS `counselings` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `counselor_id` INT(10) UNSIGNED NOT NULL,
  `counseling_date` DATE NOT NULL,
  `topic` VARCHAR(200) NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `follow_up` TEXT DEFAULT NULL,
  `status` ENUM('terjadwal','selesai','dibatalkan') NOT NULL DEFAULT 'terjadwal',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counselings_student_idx` (`student_id`),
  KEY `counselings_counselor_idx` (`counselor_id`),
  CONSTRAINT `fk_cou_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cou_teacher` FOREIGN KEY (`counselor_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `counselings` (`id`,`student_id`,`counselor_id`,`counseling_date`,`topic`,`notes`,`follow_up`,`status`,`created_at`,`updated_at`) VALUES
(101,52,136,'2026-08-20','Manajemen waktu belajar','Siswa kesulitan membagi waktu antara latihan dan belajar.','Evaluasi dua minggu lagi.','selesai',NOW(),NOW()),
(102,53,136,'2026-08-22','Komunikasi dengan teman','Konflik ringan dengan teman sebangku.','Mediasi bersama wali kelas.','selesai',NOW(),NOW()),
(103,54,144,'2026-08-25','Persiapan olimpiade','Pendampingan mental menjelang lomba.',NULL,'terjadwal',NOW(),NOW()),
(104,55,144,'2026-08-27','Motivasi belajar','Semangat menurun setelah remedial.','Libatkan orang tua.','terjadwal',NOW(),NOW()),
(105,56,136,'2026-08-10','Kehadiran','Diskusi pola keterlambatan.','Pantau kehadiran harian.','dibatalkan',NOW(),NOW());

-- 2) EXTRACURRICULARS (ekstrakurikuler)
CREATE TABLE IF NOT EXISTS `extracurriculums` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `supervisor_id` INT(10) UNSIGNED DEFAULT NULL,
  `schedule_day` ENUM('senin','selasa','rabu','kamis','jumat','sabtu') DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_extracurriculums_name` (`name`),
  CONSTRAINT `fk_exc_teacher` FOREIGN KEY (`supervisor_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `extracurriculums` (`id`,`name`,`description`,`supervisor_id`,`schedule_day`,`is_active`,`created_at`,`updated_at`) VALUES
(101,'Pramuka','Ekstrakurikuler wajib kepanduan.',136,'jumat',1,NOW(),NOW()),
(102,'Futsal','Klub olahraga futsal.',144,'sabtu',1,NOW(),NOW()),
(103,'English Club','Praktik percakapan bahasa Inggris.',145,'rabu',1,NOW(),NOW()),
(104,'Paskibra','Pasukan pengibar bendera.',146,'selasa',1,NOW(),NOW()),
(105,'Robotik','Klub robotik dan coding.',147,'kamis',0,NOW(),NOW());

-- CLEANUP modul:
-- DROP TABLE IF EXISTS extracurriculums;
-- DROP TABLE IF EXISTS counselings;
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
        foreach (['extracurriculums', 'counselings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
