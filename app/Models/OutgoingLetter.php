<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingLetter extends Model
{
    protected $table = 'outgoing_letters';

    protected $fillable = [
        'letter_number',
        'recipient',
        'subject',
        'letter_date',
        'sent_date',
        'category',
        'status',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date:Y-m-d',
            'sent_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
