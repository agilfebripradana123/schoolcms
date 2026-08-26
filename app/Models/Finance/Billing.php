<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Students\Student;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Semester;
class Billing extends Model
{
    protected $table = 'billings';

    protected $fillable = [
        'student_id',
        'fee_type_id',
        'academic_year_id',
        'semester_id',
        'amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'fee_type_id' => 'integer',
            'academic_year_id' => 'integer',
            'semester_id' => 'integer',
            'amount' => 'decimal:2',
            'due_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function feeType(): BelongsTo
    {
        return $this->belongsTo(FeeType::class, 'fee_type_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}
