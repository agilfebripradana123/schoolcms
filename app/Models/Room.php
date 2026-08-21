<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = [
        'code',
        'name',
        'capacity',
        'location',
        'has_computer',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'has_computer' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
