<?php

namespace App\Models\Facilities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    use SoftDeletes;

    protected $table = 'maintenance';

    protected $fillable = [
        'code',
        'title',
        'description',
        'asset_id',
        'room_id',
        'reported_by',
        'maintenance_type',
        'priority',
        'status',
        'scheduled_date',
        'started_date',
        'completed_date',
        'estimated_cost',
        'actual_cost',
        'notes',
        'resolution',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'started_date' => 'date',
            'completed_date' => 'date',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
