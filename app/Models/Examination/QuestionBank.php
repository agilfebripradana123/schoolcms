<?php

namespace App\Models\Examination;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Academic\Subject;
class QuestionBank extends Model
{
    use SoftDeletes;

    protected $table = 'question_banks';

    protected $fillable = [
        'subject_id',
        'instruction_id',
        'question_text',
        'question_image',
        'type',
        'difficulty',
        'explanation',
        'points',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(ExamInstruction::class, 'instruction_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }
}
