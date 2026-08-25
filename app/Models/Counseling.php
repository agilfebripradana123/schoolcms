<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Counseling extends Model
{
    protected $table = 'counselings';

    protected $fillable = [
        'student_id',
        'counselor_id',
        'counseling_date',
        'topic',
        'notes',
        'follow_up',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'counselor_id' => 'integer',
            'counseling_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'counselor_id');
    }
}
