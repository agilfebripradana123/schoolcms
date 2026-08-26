<?php

namespace App\Models\Communication;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'category',
        'attachment',
        'publish_date',
        'expired_date',
    ];

    protected $casts = [
        'publish_date' => 'date:Y-m-d',
        'expired_date' => 'date:Y-m-d',
    ];
}