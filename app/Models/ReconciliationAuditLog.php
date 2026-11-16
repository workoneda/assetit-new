<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_session_id',
        'reconciliation_result_id',
        'asset_id',
        'action_type',
        'description',
        'before_values',
        'after_values',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(ReconciliationResult::class, 'reconciliation_result_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

