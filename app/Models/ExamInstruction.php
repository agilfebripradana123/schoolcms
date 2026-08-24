<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamInstruction extends Model
{
    protected $table = 'exam_instructions';

    protected $fillable = [
        'title',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
