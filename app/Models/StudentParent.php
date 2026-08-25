<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentParent extends Model
{
    protected $table = 'parents';

    protected $fillable = [
        'student_id',
        'father_name',
        'mother_name',
        'father_occupation',
        'mother_occupation',
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
