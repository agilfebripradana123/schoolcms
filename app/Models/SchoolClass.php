<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'teacher_id',
        'level',
        'academic_year',
    ];

    protected $casts = [
        'teacher_id' => 'integer',
    ];
}