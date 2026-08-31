<?php

namespace App\Models\Students;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParent extends Model
{
    protected $table = 'parents';

    protected $fillable = [
        'student_id',
        'father_name',
        'father_birth_year',
        'father_education',
        'father_occupation',
        'father_income',
        'father_nik',
        'mother_name',
        'mother_birth_year',
        'mother_education',
        'mother_occupation',
        'mother_income',
        'mother_nik',
        'phone',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
