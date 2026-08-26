<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Academic\SchoolClass;
use App\Models\Academic\Subject;
use App\Models\Academic\AcademicYear;
class TeacherAssignment extends Model
{
    protected $table = 'teacher_assignments';

    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'academic_year_id',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'class_id' => 'integer',
            'subject_id' => 'integer',
            'academic_year_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
