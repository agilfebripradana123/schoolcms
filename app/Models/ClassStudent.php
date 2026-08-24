<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassStudent extends Model
{
    protected $table = 'class_students';

    protected $fillable = [
        'class_id',
        'student_id',
        'academic_year_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'class_id' => 'integer',
            'student_id' => 'integer',
            'academic_year_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
