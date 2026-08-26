<?php

namespace App\Models\Facilities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use SoftDeletes;

    protected $table = 'inventories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'unit',
        'quantity',
        'minimum_stock',
        'location',
        'room_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'minimum_stock' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity <= $this->minimum_stock;
    }
}
