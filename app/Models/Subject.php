<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $table = 'subjects';

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }
}
