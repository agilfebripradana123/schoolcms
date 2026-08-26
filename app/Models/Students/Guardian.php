<?php

namespace App\Models\Students;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Guardian extends Model
{
    protected $table = 'guardians';

    protected $fillable = [
        'student_id',
        'name',
        'relation',
        'phone',
        'occupation',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
