<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDocument extends Model
{
    protected $table = 'teacher_documents';

    protected $fillable = [
        'teacher_id',
        'title',
        'document_type',
        'file_path',
        'issued_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'teacher_id' => 'integer',
            'issued_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
