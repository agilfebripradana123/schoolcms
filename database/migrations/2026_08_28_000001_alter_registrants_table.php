<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrants', function (Blueprint $table) {
            // Identity
            $table->string('nik', 20)->nullable()->after('id');
            $table->string('nisn', 20)->nullable()->after('nik');
            $table->string('nickname', 50)->nullable()->after('full_name');
            $table->enum('religion', ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])->nullable()->after('gender');
            $table->string('nationality', 50)->nullable()->after('religion');
            $table->enum('marital_status', ['anak_kandung', 'anak_angkat', 'lainnya'])->nullable()->after('nationality');
            $table->unsignedTinyInteger('birth_order')->nullable()->after('marital_status');
            $table->unsignedTinyInteger('sibling_count')->nullable()->after('birth_order');
            $table->enum('blood_type', ['A', 'B', 'AB', 'O'])->nullable()->after('sibling_count');
            $table->string('special_needs', 100)->nullable()->after('blood_type');
            $table->string('photo', 255)->nullable()->after('special_needs');

            // Address detail
            $table->string('rt', 5)->nullable()->after('address');
            $table->string('rw', 5)->nullable()->after('rt');
            $table->string('village', 100)->nullable()->after('rw');
            $table->string('district', 100)->nullable()->after('village');
            $table->string('city', 100)->nullable()->after('district');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');

            // School origin
            $table->string('previous_school_npsn', 20)->nullable()->after('previous_school');
            $table->string('previous_school_address', 200)->nullable()->after('previous_school_npsn');
            $table->unsignedSmallInteger('graduation_year')->nullable()->after('previous_school_address');
            $table->string('diploma_number', 50)->nullable()->after('graduation_year');
            $table->decimal('average_score', 5, 2)->nullable()->after('diploma_number');

            // Father
            $table->string('father_name', 150)->nullable()->after('average_score');
            $table->string('father_nik', 20)->nullable()->after('father_name');
            $table->string('father_birth_place', 100)->nullable()->after('father_nik');
            $table->date('father_birth_date')->nullable()->after('father_birth_place');
            $table->enum('father_education', ['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])->nullable()->after('father_birth_date');
            $table->string('father_occupation', 100)->nullable()->after('father_education');
            $table->decimal('father_income', 15, 2)->nullable()->after('father_occupation');
            $table->string('father_phone', 20)->nullable()->after('father_income');
            $table->string('father_address', 200)->nullable()->after('father_phone');

            // Mother
            $table->string('mother_name', 150)->nullable()->after('father_address');
            $table->string('mother_nik', 20)->nullable()->after('mother_name');
            $table->string('mother_birth_place', 100)->nullable()->after('mother_nik');
            $table->date('mother_birth_date')->nullable()->after('mother_birth_place');
            $table->enum('mother_education', ['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])->nullable()->after('mother_birth_date');
            $table->string('mother_occupation', 100)->nullable()->after('mother_education');
            $table->decimal('mother_income', 15, 2)->nullable()->after('mother_occupation');
            $table->string('mother_phone', 20)->nullable()->after('mother_income');
            $table->string('mother_address', 200)->nullable()->after('mother_phone');

            // Guardian
            $table->string('guardian_name', 150)->nullable()->after('mother_address');
            $table->string('guardian_nik', 20)->nullable()->after('guardian_name');
            $table->enum('guardian_education', ['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])->nullable()->after('guardian_nik');
            $table->string('guardian_occupation', 100)->nullable()->after('guardian_education');
            $table->decimal('guardian_income', 15, 2)->nullable()->after('guardian_occupation');
            $table->string('guardian_phone', 20)->nullable()->after('guardian_income');
            $table->string('guardian_address', 200)->nullable()->after('guardian_phone');

            // Registration
            $table->unsignedInteger('academic_year_id')->nullable()->after('guardian_address');
            $table->enum('registration_path', ['prestasi', 'reguler', 'afirmasi', 'mutasi'])->nullable()->after('academic_year_id');
            $table->enum('program_choice', ['ipa', 'ips', 'bahasa', 'lainnya'])->nullable()->after('registration_path');
            $table->date('registration_date')->nullable()->after('program_choice');

            // Verification
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('registration_date');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->unsignedInteger('verified_by')->nullable()->after('verification_notes');
            $table->datetime('verified_at')->nullable()->after('verified_by');

            // Selection
            $table->decimal('selection_score', 5, 2)->nullable()->after('verified_at');
            $table->enum('selection_status', ['pending', 'selected', 'not_selected'])->default('pending')->after('selection_score');
            $table->text('selection_notes')->nullable()->after('selection_status');
            $table->datetime('selected_at')->nullable()->after('selection_notes');

            // Re-registration
            $table->enum('re_registration_status', ['pending', 'completed', 'expired'])->default('pending')->after('selected_at');
            $table->datetime('re_registration_date')->nullable()->after('re_registration_status');
            $table->text('re_registration_notes')->nullable()->after('re_registration_date');
            $table->unsignedInteger('re_registration_verified_by')->nullable()->after('re_registration_notes');
            $table->datetime('re_registration_verified_at')->nullable()->after('re_registration_verified_by');

            // Documents
            $table->string('document_kk', 255)->nullable()->after('re_registration_verified_at');
            $table->string('document_birth_certificate', 255)->nullable()->after('document_kk');
            $table->string('document_diploma', 255)->nullable()->after('document_birth_certificate');
            $table->string('document_parent_ktp', 255)->nullable()->after('document_diploma');
            $table->string('document_kip_kks', 255)->nullable()->after('document_parent_ktp');
            $table->string('document_photo', 255)->nullable()->after('document_kip_kks');
            $table->string('document_other', 255)->nullable()->after('document_photo');

            // Additional
            $table->text('achievements')->nullable()->after('document_other');
            $table->text('organizations')->nullable()->after('achievements');
            $table->string('scholarship_info', 200)->nullable()->after('organizations');
            $table->boolean('declaration')->default(false)->after('scholarship_info');

            // Expand status enum
            $table->enum('status', [
                'draft', 'submitted', 'verified', 'selected',
                'not_selected', 're_registered', 'cancelled'
            ])->default('draft')->change();

            // Foreign keys
            $table->foreign('academic_year_id')
                ->references('id')->on('academic_years')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('verified_by')
                ->references('id')->on('users')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('re_registration_verified_by')
                ->references('id')->on('users')
                ->onDelete('set null')->onUpdate('cascade');

            // Unique constraints
            $table->unique('nik');
            $table->unique('nisn');

            // Indexes
            $table->index('academic_year_id');
            $table->index('registration_path');
            $table->index('program_choice');
            $table->index('verification_status');
            $table->index('selection_status');
            $table->index('re_registration_status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('registrants', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['re_registration_verified_by']);
            $table->dropIndex(['nik']);
            $table->dropIndex(['nisn']);
            $table->dropIndex(['academic_year_id']);
            $table->dropIndex(['registration_path']);
            $table->dropIndex(['program_choice']);
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['selection_status']);
            $table->dropIndex(['re_registration_status']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'nik', 'nisn', 'nickname', 'religion', 'nationality',
                'marital_status', 'birth_order', 'sibling_count', 'blood_type',
                'special_needs', 'photo', 'rt', 'rw', 'village', 'district',
                'city', 'province', 'postal_code', 'previous_school_npsn',
                'previous_school_address', 'graduation_year', 'diploma_number',
                'average_score', 'father_name', 'father_nik', 'father_birth_place',
                'father_birth_date', 'father_education', 'father_occupation',
                'father_income', 'father_phone', 'father_address', 'mother_name',
                'mother_nik', 'mother_birth_place', 'mother_birth_date',
                'mother_education', 'mother_occupation', 'mother_income',
                'mother_phone', 'mother_address', 'guardian_name', 'guardian_nik',
                'guardian_education', 'guardian_occupation', 'guardian_income',
                'guardian_phone', 'guardian_address', 'academic_year_id',
                'registration_path', 'program_choice', 'registration_date',
                'verification_status', 'verification_notes', 'verified_by',
                'verified_at', 'selection_score', 'selection_status',
                'selection_notes', 'selected_at', 're_registration_status',
                're_registration_date', 're_registration_notes',
                're_registration_verified_by', 're_registration_verified_at',
                'document_kk', 'document_birth_certificate', 'document_diploma',
                'document_parent_ktp', 'document_kip_kks', 'document_photo',
                'document_other', 'achievements', 'organizations',
                'scholarship_info', 'declaration',
            ]);

            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->change();
        });
    }
};
