<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomingLetter extends Model
{
    protected $table = 'incoming_letters';

    protected $fillable = [
        'letter_number',
        'sender',
        'subject',
        'received_date',
        'letter_date',
        'category',
        'is_important',
        'status',
        'file_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_important' => 'boolean',
            'received_date' => 'date:Y-m-d',
            'letter_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function dispositions(): HasMany
    {
        return $this->hasMany(Disposition::class, 'incoming_letter_id');
    }
}
