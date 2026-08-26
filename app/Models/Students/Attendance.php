<?php

namespace App\Models\Students;

use Illuminate\Database\Eloquent\Model;

use App\Models\Academic\SchoolClass;
class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'status',
        'note',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }
}