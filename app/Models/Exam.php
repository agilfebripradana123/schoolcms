<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use SoftDeletes;

    protected $table = 'exams';

    protected $fillable = [
        'subject_id',
        'title',
        'description',
        'duration_minutes',
        'total_questions',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
        'shuffle_options',
        'show_result',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
<<<<<<< HEAD
            'description' => 'string',
=======
>>>>>>> origin/main
            'duration_minutes' => 'integer',
            'total_questions' => 'integer',
            'passing_score' => 'integer',
            'max_attempts' => 'integer',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_result' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'exam_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ExamParticipant::class, 'exam_id');
    }
}
