<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors database/sql/create_finance_modules_tables.sql verbatim.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul keuangan: fee_types, billings, payments,
-- payment_transactions, financial_reports.
-- Seed id 100+, merujuk students 52-56, academic_years id=2, users id=1.
-- =====================================================================

-- 1) FEE_TYPES (jenis biaya)
CREATE TABLE IF NOT EXISTS `fee_types` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `description` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_fee_types_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `fee_types` (`id`,`name`,`amount`,`description`,`is_active`,`created_at`,`updated_at`) VALUES
(101,'SPP Bulanan',350000.00,'Sumbangan pendidikan per bulan.',1,NOW(),NOW()),
(102,'Biaya Ujian',150000.00,'Biaya ujian tengah dan akhir semester.',1,NOW(),NOW()),
(103,'Seragam Sekolah','450000.00','Satu set seragam lengkap.',1,NOW(),NOW()),
(104,'Kegiatan Ekstrakurikuler',200000.00,'Per semester.',1,NOW(),NOW()),
(105,'Dana Buku',125000.00,'Pinjaman buku paket.',1,NOW(),NOW());

-- 2) BILLINGS (tagihan)
CREATE TABLE IF NOT EXISTS `billings` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `fee_type_id` INT(10) UNSIGNED NOT NULL,
  `academic_year_id` INT(10) UNSIGNED NOT NULL,
  `semester_id` INT(10) UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_billings` (`student_id`,`fee_type_id`,`academic_year_id`),
  KEY `billings_fee_type_idx` (`fee_type_id`),
  CONSTRAINT `fk_bill_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bill_feetype` FOREIGN KEY (`fee_type_id`) REFERENCES `fee_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bill_ay` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bill_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `billings` (`id`,`student_id`,`fee_type_id`,`academic_year_id`,`semester_id`,`amount`,`due_date`,`status`,`notes`,`created_at`,`updated_at`) VALUES
(101,52,101,2,103,350000.00,'2026-08-10','paid','SPP Agustus 2025/2026.',NOW(),NOW()),
(102,53,101,2,103,350000.00,'2026-08-10','partial','SPP Agustus - cicilan pertama.',NOW(),NOW()),
(103,54,102,2,NULL,150000.00,'2026-09-15','unpaid',NULL,NOW(),NOW()),
(104,55,101,2,103,350000.00,'2026-08-10','paid','SPP Agustus 2025/2026.',NOW(),NOW()),
(105,56,103,2,NULL,450000.00,'2026-07-20','cancelled','Peserta membeli seragam sendiri.',NOW(),NOW());

-- 3) PAYMENTS (pembayaran)
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `billing_id` INT(10) UNSIGNED NOT NULL,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `method` ENUM('cash','transfer','qris','lainnya') NOT NULL DEFAULT 'cash',
  `reference_number` VARCHAR(50) DEFAULT NULL,
  `received_by` INT(10) UNSIGNED DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_billing_idx` (`billing_id`),
  KEY `payments_student_idx` (`student_id`),
  CONSTRAINT `fk_pay_billing` FOREIGN KEY (`billing_id`) REFERENCES `billings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `payments` (`id`,`billing_id`,`student_id`,`payment_date`,`amount`,`method`,`reference_number`,`received_by`,`notes`,`created_at`,`updated_at`) VALUES
(101,101,52,'2026-08-05',350000.00,'cash','KWT-001',1,'Lunas SPP Agustus.',NOW(),NOW()),
(102,102,53,'2026-08-06',200000.00,'transfer','TRF-889',1,'Cicilan pertama.',NOW(),NOW()),
(103,104,55,'2026-08-04',350000.00,'qris','QR-771',1,NULL,NOW(),NOW());

-- 4) PAYMENT_TRANSACTIONS (mutasi transaksi)
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT(10) UNSIGNED NOT NULL,
  `transaction_code` VARCHAR(50) NOT NULL,
  `type` ENUM('payment','refund','adjustment') NOT NULL DEFAULT 'payment',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `method` ENUM('cash','transfer','qris','lainnya') NOT NULL DEFAULT 'cash',
  `status` ENUM('success','pending','failed') NOT NULL DEFAULT 'success',
  `transaction_date` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ptx_code` (`transaction_code`),
  CONSTRAINT `fk_ptx_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `payment_transactions` (`id`,`payment_id`,`transaction_code`,`type`,`amount`,`method`,`status`,`transaction_date`,`created_at`,`updated_at`) VALUES
(101,101,'TRX-2026-0001','payment',350000.00,'cash','success','2026-08-05 09:00:00',NOW(),NOW()),
(102,102,'TRX-2026-0002','payment',200000.00,'transfer','success','2026-08-06 10:30:00',NOW(),NOW()),
(103,103,'TRX-2026-0003','payment',350000.00,'qris','success','2026-08-04 14:15:00',NOW(),NOW()),
(104,102,'TRX-2026-0004','refund',50000.00,'transfer','pending','2026-08-07 11:00:00',NOW(),NOW());

-- 5) FINANCIAL_REPORTS (laporan keuangan - snapshot angka saat dibuat)
CREATE TABLE IF NOT EXISTS `financial_reports` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `report_type` ENUM('harian','bulanan','semester','tahunan','custom') NOT NULL DEFAULT 'bulanan',
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `total_billed` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_paid` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_outstanding` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `generated_by` INT(10) UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_fr_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `financial_reports` (`id`,`title`,`report_type`,`period_start`,`period_end`,`total_billed`,`total_paid`,`total_outstanding`,`generated_by`,`notes`,`created_at`,`updated_at`) VALUES
(101,'Laporan Pembayaran Agustus 2026','bulanan','2026-08-01','2026-08-31',1300000.00,900000.00,400000.00,1,'Snapshot manual untuk testing.',NOW(),NOW()),
(102,'Rekap Semester Ganjil 2025/2026','semester','2026-07-01','2026-12-31',2600000.00,1750000.00,850000.00,1,NULL,NOW(),NOW()),
(103,'Laporan Tahunan 2025/2026','tahunan','2025-07-01','2026-06-30',5200000.00,4900000.00,300000.00,1,NULL,NOW(),NOW());

-- CLEANUP (anak dulu):
-- DROP TABLE IF EXISTS financial_reports;
-- DROP TABLE IF EXISTS payment_transactions;
-- DROP TABLE IF EXISTS payments;
-- DROP TABLE IF EXISTS billings;
-- DROP TABLE IF EXISTS fee_types;
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
        foreach (['financial_reports', 'payment_transactions', 'payments', 'billings', 'fee_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
