<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the Examination module tables.
 *
 * Dependencies follow the source of truth (models + form requests):
 *   exam_instructions            (no FK)
 *   exam_sessions                (no FK)
 *   exams                        (FK -> subjects)
 *   exam_schedules               (FK -> exams, rooms, exam_sessions)
 *   question_banks               (FK -> subjects, exam_instructions)
 *   question_options             (FK -> question_banks)
 *   exam_participants            (FK -> exams, students)
 *   exam_answers                 (FK -> exam_participants, question_banks, question_options)
 *   exam_results                 (FK -> exam_participants)
 *
 * Down() drops in reverse dependency order (children first).
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = <<<'SQL'
-- =====================================================================
-- Modul examination: bank soal, ujian, jadwal, sesi, peserta, jawaban.
-- Referensi FK pada tabel existing: subjects, students, rooms (id INT UNSIGNED).
-- Nama constraint mengikuti convention project (awalan fk_ex_*).
-- =====================================================================

-- 1) EXAM_INSTRUCTIONS (instruksi pengerjaan, tanpa dependensi)
CREATE TABLE IF NOT EXISTS `exam_instructions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_instructions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) EXAM_SESSIONS (sesi ujian, tanpa dependensi)
CREATE TABLE IF NOT EXISTS `exam_sessions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) EXAMS (ujian utama, FK -> subjects)
CREATE TABLE IF NOT EXISTS `exams` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `duration_minutes` INT(10) UNSIGNED NOT NULL,
  `total_questions` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `passing_score` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `max_attempts` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `shuffle_questions` TINYINT(1) NOT NULL DEFAULT 0,
  `shuffle_options` TINYINT(1) NOT NULL DEFAULT 0,
  `show_result` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('draft','published','ongoing','completed','archived') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exams_subject_id_index` (`subject_id`),
  KEY `exams_status_index` (`status`),
  CONSTRAINT `fk_ex_exam_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) EXAM_SCHEDULES (jadwal ujian, FK -> exams, rooms, exam_sessions)
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` INT(10) UNSIGNED NOT NULL,
  `room_id` INT(10) UNSIGNED NOT NULL,
  `session_id` INT(10) UNSIGNED NOT NULL,
  `exam_date` DATE NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_schedules_exam_id_index` (`exam_id`),
  KEY `exam_schedules_room_id_index` (`room_id`),
  KEY `exam_schedules_session_id_index` (`session_id`),
  KEY `exam_schedules_exam_date_index` (`exam_date`),
  CONSTRAINT `fk_ex_sch_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_sch_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_sch_session` FOREIGN KEY (`session_id`) REFERENCES `exam_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) QUESTION_BANKS (bank soal, FK -> subjects, exam_instructions)
CREATE TABLE IF NOT EXISTS `question_banks` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `instruction_id` INT(10) UNSIGNED DEFAULT NULL,
  `question_text` TEXT NOT NULL,
  `question_image` VARCHAR(255) DEFAULT NULL,
  `type` ENUM('multiple_choice','true_false','essay') NOT NULL,
  `difficulty` ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `explanation` TEXT DEFAULT NULL,
  `points` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question_banks_subject_id_index` (`subject_id`),
  KEY `question_banks_instruction_id_index` (`instruction_id`),
  KEY `question_banks_type_index` (`type`),
  KEY `question_banks_difficulty_index` (`difficulty`),
  CONSTRAINT `fk_ex_qb_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_qb_instruction` FOREIGN KEY (`instruction_id`) REFERENCES `exam_instructions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6) QUESTION_OPTIONS (opsi jawaban, FK -> question_banks)
CREATE TABLE IF NOT EXISTS `question_options` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT(10) UNSIGNED NOT NULL,
  `option_text` VARCHAR(5000) NOT NULL,
  `option_image` VARCHAR(255) DEFAULT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question_options_question_id_index` (`question_id`),
  CONSTRAINT `fk_ex_qo_question` FOREIGN KEY (`question_id`) REFERENCES `question_banks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7) EXAM_PARTICIPANTS (peserta ujian, FK -> exams, students)
CREATE TABLE IF NOT EXISTS `exam_participants` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id` INT(10) UNSIGNED NOT NULL,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `exam_card_number` VARCHAR(30) NOT NULL,
  `status` ENUM('registered','started','completed','blocked') NOT NULL DEFAULT 'registered',
  `started_at` DATETIME DEFAULT NULL,
  `completed_at` DATETIME DEFAULT NULL,
  `is_blocked` TINYINT(1) NOT NULL DEFAULT 0,
  `blocked_reason` TEXT DEFAULT NULL,
  `login_allowed` TINYINT(1) NOT NULL DEFAULT 1,
  `current_session_id` INT(10) UNSIGNED DEFAULT NULL,
  `last_activity_at` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_exam_participants_card` (`exam_card_number`),
  KEY `exam_participants_exam_id_index` (`exam_id`),
  KEY `exam_participants_student_id_index` (`student_id`),
  KEY `exam_participants_status_index` (`status`),
  KEY `exam_participants_current_session_id_index` (`current_session_id`),
  CONSTRAINT `fk_ex_pa_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_pa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8) EXAM_ANSWERS (jawaban peserta, FK -> exam_participants, question_banks, question_options)
CREATE TABLE IF NOT EXISTS `exam_answers` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_id` INT(10) UNSIGNED NOT NULL,
  `question_id` INT(10) UNSIGNED NOT NULL,
  `selected_option_id` INT(10) UNSIGNED DEFAULT NULL,
  `essay_answer` TEXT DEFAULT NULL,
  `is_correct` TINYINT(1) DEFAULT NULL,
  `answered_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `exam_answers_participant_id_index` (`participant_id`),
  KEY `exam_answers_question_id_index` (`question_id`),
  KEY `exam_answers_selected_option_id_index` (`selected_option_id`),
  CONSTRAINT `fk_ex_ans_participant` FOREIGN KEY (`participant_id`) REFERENCES `exam_participants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_ans_question` FOREIGN KEY (`question_id`) REFERENCES `question_banks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ex_ans_option` FOREIGN KEY (`selected_option_id`) REFERENCES `question_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9) EXAM_RESULTS (hasil ujian, FK -> exam_participants)
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `participant_id` INT(10) UNSIGNED NOT NULL,
  `total_score` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `correct_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `wrong_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `unanswered_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `grade` VARCHAR(5) DEFAULT NULL,
  `status` ENUM('pending','graded') NOT NULL DEFAULT 'pending',
  `graded_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_exam_results_participant` (`participant_id`),
  KEY `exam_results_status_index` (`status`),
  CONSTRAINT `fk_ex_res_participant` FOREIGN KEY (`participant_id`) REFERENCES `exam_participants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- CLEANUP (anak dulu, baru induk):
-- DROP TABLE IF EXISTS exam_results;
-- DROP TABLE IF EXISTS exam_answers;
-- DROP TABLE IF EXISTS exam_participants;
-- DROP TABLE IF EXISTS question_options;
-- DROP TABLE IF EXISTS question_banks;
-- DROP TABLE IF EXISTS exam_schedules;
-- DROP TABLE IF EXISTS exams;
-- DROP TABLE IF EXISTS exam_sessions;
-- DROP TABLE IF EXISTS exam_instructions;
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
        foreach (['exam_results', 'exam_answers', 'exam_participants', 'question_options', 'question_banks', 'exam_schedules', 'exams', 'exam_sessions', 'exam_instructions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
