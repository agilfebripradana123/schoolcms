<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates grades table.
 * 
 * This migration creates the grades table that was previously created outside
 * the migration system. The schema matches the existing implementation in:
 * - app/Models/Academic/Grade.php
 * - app/Http/Controllers/Api/Academic/GradeController.php
 * - app/Http/Requests/Api/Academic/StoreGradeRequest.php
 * - app/Http/Requests/Api/Academic/UpdateGradeRequest.php
 * 
 * Dependencies:
 * - students (id)
 * - subjects (id)
 * - classes (id)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Check if table already exists (for existing installations)
        if (Schema::connection('mysql')->hasTable('grades')) {
            return;
        }

        $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `grades` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT(10) UNSIGNED NOT NULL,
  `subject_id` INT(10) UNSIGNED NOT NULL,
  `class_id` INT(10) UNSIGNED NOT NULL,
  `type` ENUM('tugas','uts','uas') NOT NULL,
  `score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `semester` VARCHAR(10) NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grades_student_id_index` (`student_id`),
  KEY `grades_subject_id_index` (`subject_id`),
  KEY `grades_class_id_index` (`class_id`),
  KEY `grades_lookup_index` (`student_id`,`subject_id`,`class_id`,`type`,`semester`,`academic_year`),
  CONSTRAINT `fk_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_grades_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
SQL;

        DB::connection('mysql')->statement($sql);
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('grades');
    }
};
