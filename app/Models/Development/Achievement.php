<?php

namespace App\Models\Development;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Models\Students\Student;
class Achievement extends Model
{
    protected $table = 'achievements';

    protected $fillable = [
        'student_id',
        'title',
        'level',
        'organizer',
        'achievement_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
