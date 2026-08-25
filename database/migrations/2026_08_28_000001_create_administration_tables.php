<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Administration: incoming_letters, outgoing_letters,
 * documents, dispositions. Mirror dari desain modul persuratan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `incoming_letters` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_number` VARCHAR(50) NOT NULL,
  `sender` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `received_date` DATE NOT NULL,
  `letter_date` DATE DEFAULT NULL,
  `category` ENUM('undangan','permohonan','pemberitahuan','lainnya') NOT NULL DEFAULT 'lainnya',
  `is_important` TINYINT(1) NOT NULL DEFAULT 0,
  `status` ENUM('baru','diproses','selesai','diarsipkan') NOT NULL DEFAULT 'baru',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_incoming_letters_number` (`letter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `incoming_letters` (`id`,`letter_number`,`sender`,`subject`,`received_date`,`letter_date`,`category`,`is_important`,`status`,`file_path`,`notes`,`created_at`,`updated_at`) VALUES
(101,'SM-2026-001','Dinas Pendidikan Provinsi','Undangan rapat koordinasi kepala sekolah','2026-08-20','2026-08-18','undangan',1,'diproses',NULL,NULL,NOW(),NOW()),
(102,'SM-2026-002','Kemendikbudristek','Pemberitahuan jadwal ANBK 2026','2026-08-22','2026-08-20','pemberitahuan',1,'baru',NULL,NULL,NOW(),NOW()),
(103,'SM-2026-003','Pemerintah Desa Sukamaju','Permohonan kerja sama program belajar','2026-08-24','2026-08-23','permohonan',0,'selesai',NULL,NULL,NOW(),NOW()),
(104,'SM-2026-004','PT Telkom Indonesia','Penawaran internet dedicated','2026-08-26','2026-08-25','lainnya',0,'baru',NULL,NULL,NOW(),NOW()),
(105,'SM-2026-005','Polsek Sukamaju','Pemberitahuan jadwal sosialisasi','2026-08-27','2026-08-26','pemberitahuan',0,'diarsipkan',NULL,NULL,NOW(),NOW());

CREATE TABLE IF NOT EXISTS `outgoing_letters` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `letter_number` VARCHAR(50) NOT NULL,
  `recipient` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `letter_date` DATE NOT NULL,
  `sent_date` DATE DEFAULT NULL,
  `category` ENUM('undangan','permohonan','pemberitahuan','lainnya') NOT NULL DEFAULT 'lainnya',
  `status` ENUM('draft','terkirim','diarsipkan') NOT NULL DEFAULT 'draft',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_outgoing_letters_number` (`letter_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `outgoing_letters` (`id`,`letter_number`,`recipient`,`subject`,`letter_date`,`sent_date`,`category`,`status`,`file_path`,`notes`,`created_at`,`updated_at`) VALUES
(101,'SK-2026-001','Dinas Pendidikan Provinsi','Laporan bulanan kegiatan sekolah','2026-08-05','2026-08-05','pemberitahuan','terkirim',NULL,NULL,NOW(),NOW()),
(102,'SK-2026-002','Orang Tua/Wali Siswa Kelas X','Undangan sosialisasi kurikulum','2026-08-15','2026-08-16','undangan','terkirim',NULL,NULL,NOW(),NOW()),
(103,'SK-2026-003','Kemendikbudristek','Permohonan bantuan laboratorium komputer','2026-08-20',NULL,'permohonan','draft',NULL,NULL,NOW(),NOW()),
(104,'SK-2026-004','Polsek Sukamaju','Surat tugas pendampingan sosialisasi','2026-08-28','2026-08-28','lainnya','terkirim',NULL,NULL,NOW(),NOW()),
(105,'SK-2026-005','Yayasan Pendidikan','Laporan pencairan beasiswa semester 1','2026-08-29',NULL,'pemberitahuan','diarsipkan',NULL,NULL,NOW(),NOW());

CREATE TABLE IF NOT EXISTS `documents` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `document_number` VARCHAR(50) DEFAULT NULL,
  `category` ENUM('sk','peraturan','sop','laporan','formulir','lainnya') NOT NULL DEFAULT 'lainnya',
  `file_path` VARCHAR(255) DEFAULT NULL,
  `document_date` DATE DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_documents_number` (`document_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `documents` (`id`,`title`,`document_number`,`category`,`file_path`,`document_date`,`description`,`created_at`,`updated_at`) VALUES
(101,'SK Pembagian Tugas Mengajar 2026/2027','DOC-SK-001','sk',NULL,'2026-07-15','SK internal pembagian beban mengajar.',NOW(),NOW()),
(102,'Peraturan Tata Tertib Siswa','DOC-PR-002','peraturan',NULL,'2025-07-01','Tata tertib berlaku satu tahun ajaran.',NOW(),NOW()),
(103,'SOP Penerimaan Peserta Didik Baru','DOC-SOP-003','sop',NULL,'2025-06-01','Alur PPDB tahunan.',NOW(),NOW()),
(104,'Laporan Bulanan Agustus','DOC-LP-004','laporan',NULL,'2026-08-31',NULL,NOW(),NOW()),
(105,'Formulir Permohonan Izin Guru','DOC-FR-005','formulir',NULL,'2025-01-10','Formulir standar izin dan cuti.',NOW(),NOW());

CREATE TABLE IF NOT EXISTS `dispositions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `incoming_letter_id` INT(10) UNSIGNED NOT NULL,
  `assigned_to` VARCHAR(150) NOT NULL,
  `instruction` TEXT DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('belum','proses','selesai') NOT NULL DEFAULT 'belum',
  `completed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dispositions_letter_idx` (`incoming_letter_id`),
  CONSTRAINT `fk_disp_letter` FOREIGN KEY (`incoming_letter_id`) REFERENCES `incoming_letters` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `dispositions` (`id`,`incoming_letter_id`,`assigned_to`,`instruction`,`due_date`,`status`,`completed_at`,`created_at`,`updated_at`) VALUES
(101,102,'Wakil Kurikulum','Siapkan pelaksanaan ANBK dan kirim jadwalnya.','2026-09-05','proses',NULL,NOW(),NOW()),
(102,103,'Kepala TU','Tindak lanjuti permohonan kerja sama.','2026-09-02','selesai','2026-09-01 10:00:00',NOW(),NOW()),
(103,104,'Operator Sekolah','Kaji penawaran dan buat perbandingan harga.',NULL,'belum',NULL,NOW(),NOW()),
(104,105,'Wakil Kesiswaan','Koordinasi jadwal sosialisasi dengan wali kelas.','2026-09-03','belum',NULL,NOW(),NOW());
SQL;

        $statements = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)), fn ($s) => $s !== '');
        foreach ($statements as $statement) {
            DB::connection('mysql')->statement($statement);
        }
    }

    public function down(): void
    {
        foreach (['dispositions', 'documents', 'outgoing_letters', 'incoming_letters'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
