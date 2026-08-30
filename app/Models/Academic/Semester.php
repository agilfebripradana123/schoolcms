<?php

namespace App\Models\Academic;

use App\Models\Finance\Billing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    protected $table = 'semesters';

    protected $fillable = [
        'academic_year_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'academic_year_id' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class, 'semester_id');
    }
}
