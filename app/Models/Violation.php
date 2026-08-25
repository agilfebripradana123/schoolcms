<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Violation extends Model
{
    protected $table = 'violations';

    protected $fillable = [
        'student_id',
        'category',
        'description',
        'points',
        'violated_at',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'violated_at' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'handled_by');
    }
}
