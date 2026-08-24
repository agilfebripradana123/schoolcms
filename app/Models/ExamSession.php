<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $table = 'exam_sessions';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'session_id');
    }
}
