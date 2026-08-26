<?php

namespace App\Models\Administration;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'documents';

    protected $fillable = [
        'title',
        'document_number',
        'category',
        'file_path',
        'document_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date:Y-m-d',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
