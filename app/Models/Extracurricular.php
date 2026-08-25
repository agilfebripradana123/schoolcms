<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Extracurricular extends Model
{
    protected $table = 'extracurriculums';

    protected $fillable = [
        'name',
        'description',
        'supervisor_id',
        'schedule_day',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'supervisor_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'supervisor_id');
    }
}
