<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_notification_calendar_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul Notifications + Calendar (kalender akademik).
-- Seed id 100+, merujuk users (1,2,4,55,70) dan academic_years id=2.
-- =====================================================================

-- 1) NOTIFICATIONS (notifikasi per user)
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_user_idx` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `notifications` (`id`,`user_id`,`title`,`message`,`type`,`is_read`,`read_at`,`created_at`,`updated_at`) VALUES
(101,1,'Ujian Baru Dibuat','Quiz Fisika Listrik dijadwalkan 2026-08-29 pada Sesi Siang 1.','exam',0,NULL,NOW(),NOW()),
(102,2,'Tugas Baru','Latihan Aljabar Bab 1 diberikan untuk kelas X.','assignment',0,NULL,NOW(),NOW()),
(103,4,'Rapor Semester Diterbitkan','Rapor semester 1 tahun ajaran 2025/2026 sudah tersedia.','info',1,'2026-08-20 10:00:00',NOW(),NOW()),
(104,55,'Pelanggaran Dicatat','Poin pelanggaran ditambahkan: terlambat masuk sekolah.','violation',0,NULL,NOW(),NOW()),
(105,70,'Beasiswa Aktif','Beasiswa PIP Anda aktif untuk tahun ajaran 2025/2026.','info',0,NULL,NOW(),NOW());

-- 2) CALENDARS (kalender akademik / agenda sekolah)
CREATE TABLE IF NOT EXISTS `calendars` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `event_date` DATE NOT NULL,
  `type` ENUM('umum','ujian','libur','kegiatan','rapat') NOT NULL DEFAULT 'umum',
  `academic_year_id` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `calendars_event_date_idx` (`event_date`),
  CONSTRAINT `fk_cal_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `calendars` (`id`,`title`,`description`,`event_date`,`type`,`academic_year_id`,`created_at`,`updated_at`) VALUES
(101,'Ujian Quiz Fisika','Quiz hukum Ohm untuk kelas X.','2026-08-29','ujian',2,NOW(),NOW()),
(102,'Rapat Dewan Guru','Evaluasi bulanan dewan guru.','2026-09-01','rapat',2,NOW(),NOW()),
(103,'Libur Hari Raya','Libur bersama hari raya.','2026-09-15','libur',NULL,NOW(),NOW()),
(104,'Pentas Seni Sekolah','Pentas seni tahunan siswa.','2026-09-20','kegiatan',2,NOW(),NOW()),
(105,'UAS Semester Ganjil','Ujian akhir semester ganjil.','2026-12-01','ujian',2,NOW(),NOW());

-- CLEANUP modul:
-- DROP TABLE IF EXISTS calendars;
-- DROP TABLE IF EXISTS notifications;
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
        foreach (['calendars', 'notifications'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
