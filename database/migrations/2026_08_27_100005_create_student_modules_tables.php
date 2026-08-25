<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_student_modules_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul kehidupan siswa: parents, guardians, student_histories,
-- achievements, violations, scholarships, transfers, alumni,
-- student_id_cards. Seed memakai id 100+, merujuk students 52-63.
-- =====================================================================

-- 1) PARENTS (data orang tua - satu baris per siswa)
CREATE TABLE IF NOT EXISTS `parents` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `father_name` VARCHAR(100) NOT NULL,
  `mother_name` VARCHAR(100) NOT NULL,
  `father_occupation` VARCHAR(100) DEFAULT NULL,
  `mother_occupation` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_parents_student` (`student_id`),
  CONSTRAINT `fk_parents_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `parents` (`id`,`student_id`,`father_name`,`mother_name`,`father_occupation`,`mother_occupation`,`phone`,`address`,`created_at`,`updated_at`) VALUES
(101,52,'Bapak Ahmad Setiawan','Ibu Siti Rahayu','Petani','Ibu Rumah Tangga','081200000001','Jl. Melati No. 1',NOW(),NOW()),
(102,53,'Bapak Budi Santoso','Ibu Dewi Lestari','Wiraswasta','Guru','081200000002','Jl. Mawar No. 2',NOW(),NOW()),
(103,54,'Bapak Eko Prasetyo','Ibu Rina Wulandari','Karyawan Swasta','Pedagang','081200000003','Jl. Anggrek No. 3',NOW(),NOW()),
(104,55,'Bapak Hendra Gunawan','Ibu Yuni Astuti','PNS','Ibu Rumah Tangga','081200000004','Jl. Kenanga No. 4',NOW(),NOW()),
(105,56,'Bapak Joko Susilo','Ibu Sri Handayani','Nelayan','Penjahit','081200000005','Jl. Dahlia No. 5',NOW(),NOW());

-- 2) GUARDIANS (wali, boleh lebih dari satu per siswa)
CREATE TABLE IF NOT EXISTS `guardians` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `relation` ENUM('ayah','ibu','kakek','nenek','paman','bibi','lainnya') NOT NULL DEFAULT 'lainnya',
  `phone` VARCHAR(20) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_student_idx` (`student_id`),
  CONSTRAINT `fk_guardians_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `guardians` (`id`,`student_id`,`name`,`relation`,`phone`,`occupation`,`address`,`created_at`,`updated_at`) VALUES
(101,52,'Bapak Ahmad Setiawan','ayah','081200000001','Petani',NULL,NOW(),NOW()),
(102,52,'Ibu Siti Rahayu','ibu','081200000001','Ibu Rumah Tangga',NULL,NOW(),NOW()),
(103,53,'Bapak Karta Wijaya','kakek','081200000006','Pensiunan',NULL,NOW(),NOW()),
(104,54,'Ibu Larasati','bibi','081200000007','Guru',NULL,NOW(),NOW()),
(105,55,'Bapak Wartono','paman','081200000008','Buruh',NULL,NOW(),NOW());

-- 3) STUDENT_HISTORIES (riwayat akademik per tahun ajaran)
CREATE TABLE IF NOT EXISTS `student_histories` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `status` ENUM('naik','tinggal','pindah','lulus','keluar') NOT NULL DEFAULT 'naik',
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_student_histories` (`student_id`,`academic_year_id`),
  CONSTRAINT `fk_sh_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sh_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sh_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `student_histories` (`id`,`student_id`,`class_id`,`academic_year_id`,`status`,`notes`,`created_at`,`updated_at`) VALUES
(101,52,1,2,'naik','Naik kelas dengan nilai baik.',NOW(),NOW()),
(102,53,1,2,'naik',NULL,NOW(),NOW()),
(103,54,1,2,'naik',NULL,NOW(),NOW()),
(104,55,1,2,'tinggal','Wajib mengulang ujian remedial.',NOW(),NOW()),
(105,56,1,2,'naik',NULL,NOW(),NOW());

-- 4) ACHIEVEMENTS (prestasi)
CREATE TABLE IF NOT EXISTS `achievements` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `level` ENUM('sekolah','kecamatan','kota','provinsi','nasional','internasional') NOT NULL DEFAULT 'sekolah',
  `organizer` VARCHAR(150) DEFAULT NULL,
  `achievement_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `achievements_student_idx` (`student_id`),
  CONSTRAINT `fk_ach_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `achievements` (`id`,`student_id`,`title`,`level`,`organizer`,`achievement_date`,`description`,`created_at`,`updated_at`) VALUES
(101,52,'Juara 1 Olimpiade Matematika','nasional','Kemendikbudristek','2026-05-10','Tingkat nasional di Jakarta.',NOW(),NOW()),
(102,53,'Juara 2 Lomba Pidato','provinsi','Dinas Pendidikan Provinsi','2026-04-18',NULL,NOW(),NOW()),
(103,54,'Juara 3 Fisika Tingkat Kota','kota','Dinas Pendidikan Kota','2026-03-22',NULL,NOW(),NOW()),
(104,55,'Harapan 1 Lomba Cerdas Cermat','kecamatan','Kecamatan Sukamaju','2026-02-14',NULL,NOW(),NOW()),
(105,56,'Juara 1 Lomba Poster Lingkungan','sekolah','SMA Negeri 1 Contoh','2026-01-20',NULL,NOW(),NOW());

-- 5) VIOLATIONS (pelanggaran tata tertib)
CREATE TABLE IF NOT EXISTS `violations` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `category` ENUM('ringan','sedang','berat') NOT NULL DEFAULT 'ringan',
  `description` VARCHAR(255) NOT NULL,
  `points` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `violated_at` DATE DEFAULT NULL,
  `handled_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `violations_student_idx` (`student_id`),
  CONSTRAINT `fk_vio_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vio_teacher` FOREIGN KEY (`handled_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `violations` (`id`,`student_id`,`category`,`description`,`points`,`violated_at`,`handled_by`,`created_at`,`updated_at`) VALUES
(101,52,'ringan','Terlambat masuk sekolah.',5,'2026-06-01',136,NOW(),NOW()),
(102,53,'ringan','Tidak membantu PR.',5,'2026-06-15',136,NOW(),NOW()),
(103,54,'sedang','Membolos jam pelajaran.',20,'2026-07-02',144,NOW(),NOW()),
(104,55,'berat','Melawan guru saat ditegur.',50,'2026-07-20',147,NOW(),NOW()),
(105,56,'sedang','Merokok di area sekolah.',30,'2026-08-01',144,NOW(),NOW());

-- 6) SCHOLARSHIPS (beasiswa)
CREATE TABLE IF NOT EXISTS `scholarships` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `provider` VARCHAR(150) DEFAULT NULL,
  `amount` DECIMAL(12,2) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scholarships_student_idx` (`student_id`),
  CONSTRAINT `fk_sch_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `scholarships` (`id`,`student_id`,`name`,`provider`,`amount`,`start_date`,`end_date`,`status`,`created_at`,`updated_at`) VALUES
(101,52,'Beasiswa PIP','Kemendikbudristek',600000.00,'2026-07-01','2027-06-30','aktif',NOW(),NOW()),
(102,53,'Beasiswa Prestasi Nasional','Yayasan Pendidikan',1200000.00,'2026-07-01','2027-06-30','aktif',NOW(),NOW()),
(103,54,'Beasiswa KIP','Pemerintah Desa',300000.00,'2025-07-01','2026-06-30','selesai',NOW(),NOW()),
(104,55,'Beasiswa BUMN','PT Listrik Negara',500000.00,'2026-07-01',NULL,'aktif',NOW(),NOW()),
(105,56,'Beasiswa Tahfidz','Yayasan Al-Hidayah',400000.00,'2026-01-01',NULL,'aktif',NOW(),NOW());

-- 7) TRANSFERS (mutasi masuk/keluar)
CREATE TABLE IF NOT EXISTS `transfers` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `type` ENUM('masuk','keluar') NOT NULL,
  `from_school` VARCHAR(150) DEFAULT NULL,
  `to_school` VARCHAR(150) DEFAULT NULL,
  `transfer_date` DATE DEFAULT NULL,
  `reason` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transfers_student_idx` (`student_id`),
  CONSTRAINT `fk_trf_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `transfers` (`id`,`student_id`,`type`,`from_school`,`to_school`,`transfer_date`,`reason`,`created_at`,`updated_at`) VALUES
(101,52,'keluar','SMA Negeri 1 Contoh','SMA Negeri 3 Bandung','2026-08-10','Orang tua pindah tugas.',NOW(),NOW()),
(102,53,'masuk','SMP Negeri 2 Sukamaju','SMA Negeri 1 Contoh','2026-07-15','Lanjut pendidikan.',NOW(),NOW()),
(103,54,'masuk','MTs Al-Hidayah','SMA Negeri 1 Contoh','2026-07-16',NULL,NOW(),NOW()),
(104,55,'keluar','SMA Negeri 1 Contoh','SMKN 1 Industri','2026-08-12','Menyesuaikan minat.',NOW(),NOW());

-- 8) ALUMNI
CREATE TABLE IF NOT EXISTS `alumni` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `graduation_year` INT(10) UNSIGNED NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `occupation` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alumni_student_idx` (`student_id`),
  CONSTRAINT `fk_alumni_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `alumni` (`id`,`student_id`,`name`,`graduation_year`,`phone`,`email`,`occupation`,`created_at`,`updated_at`) VALUES
(101,NULL,'Rudi Hartono',2024,'081300000001','rudi@example.com','Mahasiswa',NOW(),NOW()),
(102,NULL,'Maya Anggraini',2025,'081300000002','maya@example.com','Mahasiswa',NOW(),NOW()),
(103,60,'Andi Kurniawan',2025,'081300000003',NULL,'Wirausaha',NOW(),NOW()),
(104,61,'Putri Ramadhani',2025,'081300000004',NULL,NULL,NOW(),NOW());

-- 9) STUDENT_ID_CARDS (kartu pelajar - satu aktif per siswa)
CREATE TABLE IF NOT EXISTS `student_id_cards` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `card_number` VARCHAR(30) NOT NULL,
  `issued_date` DATE DEFAULT NULL,
  `valid_until` DATE DEFAULT NULL,
  `status` ENUM('aktif','hilang','rusak','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sidc_student` (`student_id`),
  UNIQUE KEY `uniq_sidc_card_number` (`card_number`),
  CONSTRAINT `fk_sidc_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `student_id_cards` (`id`,`student_id`,`card_number`,`issued_date`,`valid_until`,`status`,`created_at`,`updated_at`) VALUES
(101,52,'IDC-2026-0001','2026-07-15','2027-06-30','aktif',NOW(),NOW()),
(102,53,'IDC-2026-0002','2026-07-15','2027-06-30','aktif',NOW(),NOW()),
(103,54,'IDC-2026-0003','2026-07-15','2027-06-30','aktif',NOW(),NOW()),
(104,55,'IDC-2026-0004','2026-07-15','2027-06-30','hilang',NOW(),NOW()),
(105,56,'IDC-2026-0005','2026-07-15','2027-06-30','aktif',NOW(),NOW());

-- CLEANUP (anak dulu, baru induk):
-- DROP TABLE IF EXISTS student_id_cards;
-- DROP TABLE IF EXISTS alumni;
-- DROP TABLE IF EXISTS transfers;
-- DROP TABLE IF EXISTS scholarships;
-- DROP TABLE IF EXISTS violations;
-- DROP TABLE IF EXISTS achievements;
-- DROP TABLE IF EXISTS student_histories;
-- DROP TABLE IF EXISTS guardians;
-- DROP TABLE IF EXISTS parents;
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
        foreach (['student_id_cards', 'alumni', 'transfers', 'scholarships', 'violations', 'achievements', 'student_histories', 'guardians', 'parents'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
