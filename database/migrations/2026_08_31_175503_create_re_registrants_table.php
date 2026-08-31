<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('re_registrants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('registration_number', 50);
            $table->string('nik', 20);
            $table->string('nisn', 20)->nullable();
            $table->string('full_name', 150);
            $table->string('nickname', 100)->nullable();
            $table->enum('gender', ['L', 'P']);
            $table->string('religion', 30)->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('previous_school', 150)->nullable();
            $table->string('previous_school_npsn', 30)->nullable();
            $table->year('graduation_year')->nullable();
            $table->string('father_name', 150)->nullable();
            $table->string('father_nik', 20)->nullable();
            $table->string('father_education', 100)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->decimal('father_income', 15, 2)->nullable();
            $table->string('father_phone', 30)->nullable();
            $table->string('mother_name', 150)->nullable();
            $table->string('mother_nik', 20)->nullable();
            $table->string('mother_education', 100)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->decimal('mother_income', 15, 2)->nullable();
            $table->string('mother_phone', 30)->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('registration_path', 50)->nullable();
            $table->string('program_choice', 100)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('document_kk', 255)->nullable();
            $table->string('document_birth_certificate', 255)->nullable();
            $table->string('document_diploma', 255)->nullable();
            $table->string('document_parent_ktp', 255)->nullable();
            $table->string('document_photo', 255)->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('verification_notes')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->datetime('verified_at')->nullable();
            $table->decimal('selection_score', 5, 2)->nullable();
            $table->enum('selection_status', ['pending', 'selected', 'not_selected'])->default('pending');
            $table->text('selection_notes')->nullable();
            $table->datetime('selected_at')->nullable();
            $table->enum('re_registration_status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->datetime('re_registration_date')->nullable();
            $table->text('re_registration_notes')->nullable();
            $table->unsignedBigInteger('re_registration_verified_by')->nullable();
            $table->datetime('re_registration_verified_at')->nullable();
            $table->boolean('data_completed')->default(false);
            $table->datetime('data_completed_at')->nullable();
            $table->boolean('declaration')->default(false);
            $table->string('photo', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('registration_number');
            $table->index('nik');
            $table->index('nisn');
            $table->index('academic_year_id');
            $table->index('verification_status');
            $table->index('selection_status');
            $table->index('re_registration_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_registrants');
    }
};
