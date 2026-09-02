<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Communication: announcements (pengumuman sekolah).
 * Mengikuti schema tabel `announcements` yang sudah ada di database
 * (tabel ini belum tercakup migration manapun sehingga perlu
 * reproducibility untuk environment baru). Non-destructive:
 * CREATE TABLE IF NOT EXISTS + INSERT IGNORE.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- ANNOUNCEMENTS (pengumuman sekolah).
-- Seed id 100+ agar tidak bentrok dengan data produksi.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT NOT NULL,
  `category` ENUM('umum','guru','siswa') NOT NULL DEFAULT 'umum',
  `attachment` VARCHAR(255) DEFAULT NULL,
  `publish_date` DATE DEFAULT NULL,
  `expired_date` DATE DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `announcements` (`id`,`title`,`content`,`category`,`attachment`,`publish_date`,`expired_date`,`created_at`,`updated_at`,`deleted_at`) VALUES
(101,'Pembagian Rapor Semester Ganjil','Rapor semester ganjil dibagikan kepada siswa melalui wali kelas.','umum',NULL,'2026-06-20',NULL,NOW(),NOW(),NULL),
(102,'Pengumuman MPLS','Masa Pengenalan Lingkungan Sekolah untuk siswa baru dimulai pekan pertama.','siswa',NULL,'2026-07-06','2026-07-10',NOW(),NOW(),NULL),
(103,'Rapat Koordinasi Dewan Guru','Rapat koordinasi dewan guru membahas jadwal ujian tengah semester.','guru',NULL,'2026-08-25',NULL,NOW(),NOW(),NULL),
(104,'Jadwal PTS Semester Ganjil','Jadwal penilaian tengah semester ganjil telah diterbitkan.','siswa',NULL,'2026-09-10','2026-09-20',NOW(),NOW(),NULL),
(105,'Libur Hari Raya','Kegiatan belajar mengajar ditiadakan selama libur hari raya.','umum',NULL,'2026-09-15','2026-09-17',NOW(),NOW(),NULL);
SQL;

        $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)), fn ($s) => $s !== '');
        foreach ($statements as $statement) {
            DB::connection('mysql')->statement($statement);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};