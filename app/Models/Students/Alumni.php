<?php

namespace App\Models\Students;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alumni extends Model
{
    protected $table = 'alumni';

    protected $fillable = [
        'student_id',
        'name',
        'graduation_year',
        'phone',
        'email',
        'occupation',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
