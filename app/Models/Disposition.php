<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposition extends Model
{
    protected $table = 'dispositions';

    protected $fillable = [
        'incoming_letter_id',
        'assigned_to',
        'instruction',
        'due_date',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'incoming_letter_id' => 'integer',
            'due_date' => 'date:Y-m-d',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class, 'incoming_letter_id');
    }
}
