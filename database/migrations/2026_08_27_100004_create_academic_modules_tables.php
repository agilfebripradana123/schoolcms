<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_academic_modules_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul akademik: semesters, curriculums, class_students,
-- teacher_assignments, schedules, periods, assignments, report_cards.
-- Semua id dummy seed memakai range 100+ agar tidak bentrok.
-- =====================================================================

-- 1) CURRICULUMS (tanpa dependensi)
CREATE TABLE IF NOT EXISTS `curriculums` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_curriculums_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `curriculums` (`id`,`name`,`description`,`is_active`,`created_at`,`updated_at`) VALUES
(101,'Kurikulum Merdeka','Kurikulum nasional generasi baru',1,NOW(),NOW()),
(102,'Kurikulum K13','Kurikulum 2013 revisi',0,NOW(),NOW());

-- 2) PERIODS (jam pelajaran, tanpa dependensi)
CREATE TABLE IF NOT EXISTS `periods` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_periods_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `periods` (`id`,`name`,`start_time`,`end_time`,`created_at`,`updated_at`) VALUES
(101,'Jam ke-1','07:00','07:40',NOW(),NOW()),
(102,'Jam ke-2','07:40','08:20',NOW(),NOW()),
(103,'Jam ke-3','08:20','09:00',NOW(),NOW()),
(104,'Jam ke-4','09:20','10:00',NOW(),NOW()),
(105,'Jam ke-5','10:00','10:40',NOW(),NOW()),
(106,'Jam ke-6','10:40','11:20',NOW(),NOW()),
(107,'Jam ke-7','12:30','13:10',NOW(),NOW()),
(108,'Jam ke-8','13:10','13:50',NOW(),NOW());

-- 3) SEMESTERS (FK academic_years)
CREATE TABLE IF NOT EXISTS `semesters` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `name` ENUM('1','2') NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_semesters_ay_name` (`academic_year_id`,`name`),
  CONSTRAINT `fk_semesters_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `semesters` (`id`,`academic_year_id`,`name`,`is_active`,`created_at`,`updated_at`) VALUES
(101,1,'1',0,NOW(),NOW()),
(102,1,'2',0,NOW(),NOW()),
(103,2,'1',1,NOW(),NOW()),
(104,2,'2',0,NOW(),NOW()),
(105,3,'1',0,NOW(),NOW());

-- 4) CLASS_STUDENTS (rombongan belajar per tahun ajaran)
CREATE TABLE IF NOT EXISTS `class_students` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `status` ENUM('active','moved','graduated') NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_class_students` (`class_id`,`student_id`,`academic_year_id`),
  KEY `class_students_student_id_index` (`student_id`),
  CONSTRAINT `fk_cs_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cs_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `class_students` (`id`,`class_id`,`student_id`,`academic_year_id`,`status`,`created_at`,`updated_at`) VALUES
(101,1,52,2,'active',NOW(),NOW()),
(102,1,53,2,'active',NOW(),NOW()),
(103,1,54,2,'active',NOW(),NOW()),
(104,1,55,2,'active',NOW(),NOW()),
(105,1,56,2,'active',NOW(),NOW());

-- 5) TEACHER_ASSIGNMENTS (guru mengajar mapel di kelas tertentu)
CREATE TABLE IF NOT EXISTS `teacher_assignments` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `teacher_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_assignments` (`teacher_id`,`class_id`,`subject_id`,`academic_year_id`),
  KEY `ta_class_subject_idx` (`class_id`,`subject_id`),
  CONSTRAINT `fk_ta_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ta_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `teacher_assignments` (`id`,`teacher_id`,`class_id`,`subject_id`,`academic_year_id`,`created_at`,`updated_at`) VALUES
(101,136,1,3,2,NOW(),NOW()),
(102,144,1,4,2,NOW(),NOW()),
(103,145,1,6,2,NOW(),NOW()),
(104,146,1,7,2,NOW(),NOW()),
(105,147,1,8,2,NOW(),NOW());

-- 6) SCHEDULES (jadwal pelajaran harian)
CREATE TABLE IF NOT EXISTS `schedules` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `teacher_id` INT(10) UNSIGNED DEFAULT NULL,
  `day` ENUM('senin','selasa','rabu','kamis','jumat','sabtu') NOT NULL,
  `period_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `semester_id` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_schedules_slot` (`class_id`,`day`,`period_id`,`academic_year_id`),
  KEY `schedules_teacher_idx` (`teacher_id`),
  CONSTRAINT `fk_sc_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_period` FOREIGN KEY (`period_id`) REFERENCES `periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sc_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `schedules` (`id`,`class_id`,`subject_id`,`teacher_id`,`day`,`period_id`,`academic_year_id`,`semester_id`,`created_at`,`updated_at`) VALUES
(101,1,3,136,'senin',101,2,103,NOW(),NOW()),
(102,1,4,144,'selasa',102,2,103,NOW(),NOW()),
(103,1,6,145,'rabu',103,2,103,NOW(),NOW()),
(104,1,7,146,'kamis',104,2,103,NOW(),NOW()),
(105,1,8,147,'jumat',105,2,103,NOW(),NOW());

-- 7) ASSIGNMENTS (tugas)
CREATE TABLE IF NOT EXISTS `assignments` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `teacher_id` INT(10) UNSIGNED DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assignments_class_idx` (`class_id`),
  CONSTRAINT `fk_as_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_as_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `assignments` (`id`,`title`,`description`,`subject_id`,`class_id`,`teacher_id`,`due_date`,`academic_year_id`,`created_at`,`updated_at`) VALUES
(101,'Latihan Aljabar Bab 1','Kerjakan soal halaman 12 nomor 1-10.',3,1,136,'2026-09-01',2,NOW(),NOW()),
(102,'Esai Teks Eksplanasi','Tulis esai 500 kata tentang bencana alam.',4,1,144,'2026-09-02',2,NOW(),NOW()),
(103,'Laporan Praktikum Ohm','Lengkapi tabel pengukuran dan kesimpulan.',6,1,145,'2026-09-03',2,NOW(),NOW()),
(104,'Resume Ikatan Kimia','Ringkas materi ion dan kovalen.',7,1,146,'2026-09-04',2,NOW(),NOW()),
(105,'Gambar Struktur Sel','Buat sketsa sel tumbuhan berlabel.',8,1,147,'2026-09-05',2,NOW(),NOW());

-- 8) REPORT_CARDS (rapor - header per siswa per semester)
CREATE TABLE IF NOT EXISTS `report_cards` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `semester_id` INT(10) UNSIGNED NOT NULL,
  `teacher_notes` TEXT DEFAULT NULL,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_report_cards` (`student_id`,`class_id`,`academic_year_id`,`semester_id`),
  CONSTRAINT `fk_rc_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rc_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rc_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rc_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `report_cards` (`id`,`student_id`,`class_id`,`academic_year_id`,`semester_id`,`teacher_notes`,`status`,`published_at`,`created_at`,`updated_at`) VALUES
(101,52,1,2,103,NULL,'draft',NULL,NOW(),NOW()),
(102,53,1,2,103,NULL,'draft',NULL,NOW(),NOW()),
(103,54,1,2,103,'Pertahankan prestasinya.','published','2026-08-25 10:00:00',NOW(),NOW()),
(104,55,1,2,103,NULL,'draft',NULL,NOW(),NOW()),
(105,56,1,2,103,NULL,'draft',NULL,NOW(),NOW());

-- =====================================================================
-- CLEANUP (hapus dari anak ke induk):
-- DROP TABLE IF EXISTS report_cards;
-- DROP TABLE IF EXISTS assignments;
-- DROP TABLE IF EXISTS schedules;
-- DROP TABLE IF EXISTS teacher_assignments;
-- DROP TABLE IF EXISTS class_students;
-- DROP TABLE IF EXISTS semesters;
-- DROP TABLE IF EXISTS periods;
-- DROP TABLE IF EXISTS curriculums;
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
        foreach (['report_cards', 'assignments', 'schedules', 'teacher_assignments', 'class_students', 'semesters', 'periods', 'curriculums'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
