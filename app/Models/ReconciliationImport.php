<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_session_id',
        'row_number',
        'item_number',
        'raw_sn',
        'normalized_sn',
        'raw_model',
        'normalized_model',
        'brand',
        'category',
        'location',
        'notes',
        'raw_payload',
        'is_valid',
        'validation_note',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'is_valid' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }
}

