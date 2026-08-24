<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
>>>>>>> origin/main

class ExamParticipant extends Model
{
    protected $table = 'exam_participants';

    protected $fillable = [
        'exam_id',
        'student_id',
        'exam_card_number',
        'status',
        'started_at',
        'completed_at',
        'is_blocked',
        'blocked_reason',
        'login_allowed',
        'current_session_id',
        'last_activity_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'is_blocked' => 'boolean',
            'login_allowed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Exam::class, 'exam_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

<<<<<<< HEAD
    public function result()
=======
    public function result(): HasOne
>>>>>>> origin/main
    {
        return $this->hasOne(ExamResult::class, 'participant_id');
    }

<<<<<<< HEAD
    public function answers()
=======
    public function answers(): HasMany
>>>>>>> origin/main
    {
        return $this->hasMany(ExamAnswer::class, 'participant_id');
    }
}
