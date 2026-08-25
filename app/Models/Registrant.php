<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registrant extends Model
{
    use SoftDeletes;

    protected $table = 'registrants';

    protected $fillable = [
        'student_id',
        'registration_number',
        'full_name',
        'email',
        'phone',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'previous_school',
        'notes',
        'nik',
        'nisn',
        'nickname',
        'religion',
        'nationality',
        'marital_status',
        'birth_order',
        'sibling_count',
        'blood_type',
        'special_needs',
        'photo',
        'rt',
        'rw',
        'village',
        'district',
        'city',
        'province',
        'postal_code',
        'previous_school_npsn',
        'previous_school_address',
        'graduation_year',
        'diploma_number',
        'average_score',
        'father_name',
        'father_nik',
        'father_birth_place',
        'father_birth_date',
        'father_education',
        'father_occupation',
        'father_income',
        'father_phone',
        'father_address',
        'mother_name',
        'mother_nik',
        'mother_birth_place',
        'mother_birth_date',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'mother_phone',
        'mother_address',
        'guardian_name',
        'guardian_nik',
        'guardian_education',
        'guardian_occupation',
        'guardian_income',
        'guardian_phone',
        'guardian_address',
        'academic_year_id',
        'registration_path',
        'program_choice',
        'registration_date',
        'verification_notes',
        'selection_notes',
        're_registration_notes',
        'document_kk',
        'document_birth_certificate',
        'document_diploma',
        'document_parent_ktp',
        'document_kip_kks',
        'document_photo',
        'document_other',
        'achievements',
        'organizations',
        'scholarship_info',
        'declaration',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'father_birth_date' => 'date',
            'mother_birth_date' => 'date',
            'registration_date' => 'date',
            'verified_at' => 'datetime',
            'selected_at' => 'datetime',
            're_registration_date' => 'datetime',
            're_registration_verified_at' => 'datetime',
            'average_score' => 'decimal:2',
            'selection_score' => 'decimal:2',
            'father_income' => 'decimal:2',
            'mother_income' => 'decimal:2',
            'guardian_income' => 'decimal:2',
            'declaration' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reRegistrationVerifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 're_registration_verified_by');
    }
}
