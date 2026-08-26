<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $table = 'periods';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
