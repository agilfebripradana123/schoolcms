<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrants', function (Blueprint $table) {
            if (!Schema::hasColumn('registrants', 'student_id')) {
                $table->unsignedInteger('student_id')->nullable()->after('id');
                $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
                $table->index('student_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrants', function (Blueprint $table) {
            if (Schema::hasColumn('registrants', 'student_id')) {
                $table->dropForeign(['student_id']);
                $table->dropIndex(['student_id']);
                $table->dropColumn('student_id');
            }
        });
    }
};
