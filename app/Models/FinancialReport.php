<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialReport extends Model
{
    protected $table = 'financial_reports';

    protected $fillable = [
        'title',
        'report_type',
        'period_start',
        'period_end',
        'total_billed',
        'total_paid',
        'total_outstanding',
        'generated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date:Y-m-d',
            'period_end' => 'date:Y-m-d',
            'total_billed' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'total_outstanding' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
