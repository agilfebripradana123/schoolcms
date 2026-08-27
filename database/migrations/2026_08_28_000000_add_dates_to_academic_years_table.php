<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the date-range columns to `academic_years`.
 *
 * These columns are part of the target AcademicYear schema and are expected by
 * the AcademicYear model ($fillable + casts). They are added additively (not
 * in the create migration) so that databases already migrated before this
 * change are upgraded without dropping/recreating the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
