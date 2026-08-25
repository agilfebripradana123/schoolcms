<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_teacher_modules_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul kepegawaian guru: staff, teacher_attendance, teacher_leave,
-- teacher_documents. Seed id 100+, merujuk teachers yang sudah ada.
-- CATATAN: Teaching Assignments SUDAH ADA (tabel teacher_assignments).
-- =====================================================================

-- 1) STAFF (tenaga kependidikan)
CREATE TABLE IF NOT EXISTS `staff` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_number` VARCHAR(30) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_staff_number` (`staff_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `staff` (`id`,`staff_number`,`name`,`position`,`department`,`phone`,`email`,`is_active`,`created_at`,`updated_at`) VALUES
(101,'STF-001','Rina Marlina','Operator Sekolah','Tata Usaha','081400000001','rina@sekolah.sch.id',1,NOW(),NOW()),
(102,'STF-002','Agus Salim','Kepala Tata Usaha','Tata Usaha','081400000002','agus@sekolah.sch.id',1,NOW(),NOW()),
(103,'STF-003','Lilis Suryani','Pustakawan','Perpustakaan','081400000003',NULL,1,NOW(),NOW()),
(104,'STF-004','Darmawan','Laboran','Laboratorium','081400000004',NULL,1,NOW(),NOW()),
(105,'STF-005','Sukron','Petugas Keamanan','Umum',NULL,NULL,0,NOW(),NOW());

-- 2) TEACHER_ATTENDANCE (kehadiran harian guru)
CREATE TABLE IF NOT EXISTS `teacher_attendance` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(10) UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  `status` ENUM('hadir','sakit','izin','alfa','terlambat') NOT NULL DEFAULT 'hadir',
  `check_in` TIME DEFAULT NULL,
  `check_out` TIME DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_attendance` (`teacher_id`,`date`),
  CONSTRAINT `fk_tatt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `teacher_attendance` (`id`,`teacher_id`,`date`,`status`,`check_in`,`check_out`,`notes`,`created_at`,`updated_at`) VALUES
(101,136,'2026-08-24','hadir','06:45','15:30',NULL,NOW(),NOW()),
(102,144,'2026-08-25','terlambat','07:35','15:30','Terlambat 35 menit.',NOW(),NOW()),
(103,145,'2026-08-26','sakit',NULL,NULL,'Surat dokter menyusul.',NOW(),NOW()),
(104,146,'2026-08-27','izin','06:50','12:00','Izin acara keluarga.',NOW(),NOW()),
(105,147,'2026-08-28','hadir','06:40','15:35',NULL,NOW(),NOW());

-- 3) TEACHER_LEAVE (cuti / izin panjang)
CREATE TABLE IF NOT EXISTS `teacher_leave` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(10) UNSIGNED NOT NULL,
  `leave_type` ENUM('cuti','izin','sakit','dinas') NOT NULL DEFAULT 'izin',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  `approved_by` INT(10) UNSIGNED DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tleave_teacher_idx` (`teacher_id`),
  CONSTRAINT `fk_tleave_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tleave_approver` FOREIGN KEY (`approved_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `teacher_leave` (`id`,`teacher_id`,`leave_type`,`start_date`,`end_date`,`reason`,`status`,`approved_by`,`approved_at`,`created_at`,`updated_at`) VALUES
(101,144,'cuti','2026-09-01','2026-09-05','Cuti tahunan.','disetujui',136,'2026-08-26 09:00:00',NOW(),NOW()),
(102,145,'sakit','2026-08-26','2026-08-28','Demam berdarah.','menunggu',NULL,NULL,NOW(),NOW()),
(103,146,'dinas','2026-09-10','2026-09-11','Rapat dinas pendidikan provinsi.','disetujui',136,'2026-08-27 10:00:00',NOW(),NOW()),
(104,147,'izin','2026-09-03','2026-09-03','Acara pernikahan keluarga.','ditolak',136,'2026-08-28 08:00:00',NOW(),NOW());

-- 4) TEACHER_DOCUMENTS (arsip dokumen kepegawaian)
CREATE TABLE IF NOT EXISTS `teacher_documents` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `document_type` ENUM('sk','sertifikat','ijazah','kontrak','lainnya') NOT NULL DEFAULT 'lainnya',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `issued_date` DATE DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tdoc_teacher_idx` (`teacher_id`),
  CONSTRAINT `fk_tdoc_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `teacher_documents` (`id`,`teacher_id`,`title`,`document_type`,`file_path`,`issued_date`,`notes`,`created_at`,`updated_at`) VALUES
(101,136,'SK CPNS','sk',NULL,'2015-03-01',NULL,NOW(),NOW()),
(102,144,'Sertifikat Pendidik','sertifikat',NULL,'2018-11-20',NULL,NOW(),NOW()),
(103,145,'Ijazah S1 Pendidikan Fisika','ijazah',NULL,'2014-08-15',NULL,NOW(),NOW()),
(104,146,'Kontrak Kerja 2026','kontrak',NULL,'2026-01-05','Periode satu tahun.',NOW(),NOW()),
(105,147,'SK Kenaikan Pangkat','sk',NULL,'2025-04-01',NULL,NOW(),NOW());

-- CLEANUP (anak dulu):
-- DROP TABLE IF EXISTS teacher_documents;
-- DROP TABLE IF EXISTS teacher_leave;
-- DROP TABLE IF EXISTS teacher_attendance;
-- DROP TABLE IF EXISTS staff;
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
        foreach (['teacher_documents', 'teacher_leave', 'teacher_attendance', 'staff'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
