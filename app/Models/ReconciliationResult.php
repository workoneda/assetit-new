<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReconciliationResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_session_id',
        'reconciliation_import_id',
        'asset_id',
        'row_number',
        'kso_sn',
        'kso_model',
        'kso_brand',
        'kso_category',
        'kso_location',
        'kso_notes',
        'system_asset_code',
        'system_name',
        'system_sn',
        'system_model',
        'system_brand',
        'system_category',
        'system_location',
        'system_ownership',
        'system_partner_name',
        'system_notes',
        'status',
        'differences',
        'candidates',
        'action_status',
        'action_note',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'differences' => 'array',
            'candidates' => 'array',
            'resolved_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ReconciliationSession::class, 'reconciliation_session_id');
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ReconciliationImport::class, 'reconciliation_import_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id')->withTrashed();
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

