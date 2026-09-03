<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration for Configuration Management (Phase 1).
 *
 * Extends the existing `settings` table with structured Configuration
 * Management fields. Existing rows (key/value/description) are preserved and
 * mapped deterministically to the new schema.
 *
 * NOTE: `current_academic_year` (id=4) is academic-domain data. It is left
 * with `group` = NULL because it does not belong to any configuration group
 * and mapping it to one would fabricate meaning. It should be migrated to the
 * Academic module's source of truth separately (out of phase-1 scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('group', 50)->nullable()->after('key');
            $table->string('type', 30)->default('string')->after('value');
            $table->boolean('is_encrypted')->default(false)->after('type');
            $table->boolean('is_public')->default(false)->after('is_encrypted');
            $table->integer('sort_order')->default(0)->after('is_public');
        });

        // Backfill deterministic group/type for existing settings.
        $mapping = [
            'app_name' => ['general', 'string', 10],
            'school_name' => ['general', 'string', 20],
            'timezone' => ['general', 'timezone', 30],
        ];

        foreach ($mapping as $key => [$group, $type, $sort]) {
            DB::connection('mysql')->table('settings')
                ->where('key', $key)
                ->whereNull('group')
                ->update([
                    'group' => $group,
                    'type' => $type,
                    'sort_order' => $sort,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['group', 'type', 'is_encrypted', 'is_public', 'sort_order']);
        });
    }
};
