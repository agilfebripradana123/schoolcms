<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'rooms';

        if (!$this->columnExists($table, 'code')) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `code` VARCHAR(20) NULL AFTER `id`");
        }

        DB::statement("UPDATE `{$table}` SET `code` = CONCAT('RM-', LPAD(`id`, 3, '0')) WHERE `code` IS NULL OR `code` = ''");

        if (!$this->indexExists($table, 'rooms_code_unique')) {
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `rooms_code_unique` (`code`)");
        }

        if ($this->columnIsNullable($table, 'code')) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `code` VARCHAR(20) NOT NULL");
        }

        if (!$this->columnExists($table, 'location')) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `location` VARCHAR(150) NULL AFTER `capacity`");
        }

        if (!$this->columnExists($table, 'has_computer')) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `has_computer` TINYINT(1) NOT NULL DEFAULT 0 AFTER `location`");
        }

        if (!$this->columnExists($table, 'deleted_at')) {
            DB::statement("ALTER TABLE `{$table}` ADD COLUMN `deleted_at` DATETIME NULL");
        }

        $statusType = $this->columnType($table, 'status');
        if (str_contains($statusType, 'available') || str_contains($statusType, 'occupied')) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('available', 'occupied', 'maintenance', 'active', 'inactive') NOT NULL DEFAULT 'available'");
            DB::statement("UPDATE `{$table}` SET `status` = 'active' WHERE `status` IN ('available', 'occupied')");
            DB::statement("UPDATE `{$table}` SET `status` = 'inactive' WHERE `status` = 'maintenance'");
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
        }

        if ($this->indexExists($table, 'rooms_name_unique')) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `rooms_name_unique`");
        }

        if ($this->columnExists($table, 'type')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `type`");
        }
    }

    public function down(): void
    {
        $table = 'rooms';

        if ($this->indexExists($table, 'rooms_code_unique')) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `rooms_code_unique`");
        }

        if ($this->columnExists($table, 'deleted_at')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `deleted_at`");
        }

        if ($this->columnExists($table, 'has_computer')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `has_computer`");
        }

        if ($this->columnExists($table, 'location')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `location`");
        }

        if ($this->columnExists($table, 'code')) {
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN `code`");
        }

        $statusType = $this->columnType($table, 'status');
        if (str_contains($statusType, 'active')) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('active', 'inactive', 'available', 'occupied', 'maintenance') NOT NULL DEFAULT 'active'");
            DB::statement("UPDATE `{$table}` SET `status` = 'available' WHERE `status` IN ('active', 'inactive')");
            DB::statement("ALTER TABLE `{$table}` MODIFY `status` ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available'");
        }
        DB::statement("ALTER TABLE `{$table}` ADD COLUMN `type` ENUM('classroom', 'lab', 'office', 'hall', 'other') NOT NULL DEFAULT 'classroom' AFTER `name`");
        if (!$this->indexExists($table, 'rooms_name_unique')) {
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `rooms_name_unique` (`name`)");
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return DB::selectOne(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        ) !== null;
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return $row !== null && $row->IS_NULLABLE === 'YES';
    }

    private function columnType(string $table, string $column): string
    {
        $row = DB::selectOne(
            "SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );
        return $row?->COLUMN_TYPE ?? '';
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$table, $index]
        ) !== null;
    }
};