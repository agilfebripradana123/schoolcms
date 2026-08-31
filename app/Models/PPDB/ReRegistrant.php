<?php

namespace App\Models\PPDB;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReRegistrant extends Model
{
    use SoftDeletes;

    protected $table = 're_registrants';

    protected $fillable = [
        'student_id',
        'registration_number',
        'nik',
        'nisn',
        'full_name',
        'nickname',
        'gender',
        'religion',
        'birth_place',
        'birth_date',
        'email',
        'phone',
        'address',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'previous_school',
        'previous_school_npsn',
        'graduation_year',
        'father_name',
        'father_nik',
        'father_education',
        'father_occupation',
        'father_income',
        'father_phone',
        'mother_name',
        'mother_nik',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'mother_phone',
        'academic_year_id',
        'registration_path',
        'program_choice',
        'registration_date',
        'document_kk',
        'document_birth_certificate',
        'document_diploma',
        'document_parent_ktp',
        'document_photo',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'selection_score',
        'selection_status',
        'selection_notes',
        'selected_at',
        're_registration_status',
        're_registration_date',
        're_registration_notes',
        're_registration_verified_by',
        're_registration_verified_at',
        'data_completed',
        'data_completed_at',
        'declaration',
        'photo',
        'notes',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'registration_date' => 'date',
        'verified_at' => 'datetime',
        'selected_at' => 'datetime',
        're_registration_date' => 'datetime',
        're_registration_verified_at' => 'datetime',
        'data_completed_at' => 'datetime',
        'data_completed' => 'boolean',
        'declaration' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}