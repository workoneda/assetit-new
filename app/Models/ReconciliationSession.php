<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReconciliationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_number',
        'kso_partner_id',
        'partner_name',
        'file_name',
        'period',
        'total_system_assets',
        'total_kso_items',
        'valid_kso_items',
        'invalid_kso_items',
        'match_count',
        'model_mismatch_count',
        'missing_in_system_count',
        'missing_in_kso_count',
        'duplicate_sn_count',
        'invalid_data_count',
        'manual_review_count',
        'status',
        'notes',
        'created_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(KsoPartner::class, 'kso_partner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(ReconciliationImport::class, 'reconciliation_session_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(ReconciliationResult::class, 'reconciliation_session_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ReconciliationAuditLog::class, 'reconciliation_session_id')->latest();
    }

    public static function generateNextNumber(): string
    {
        $year = date('Y');
        $prefix = "REC-{$year}-";
        $maxNum = 0;

        $numbers = static::where('reconciliation_number', 'like', $prefix . '%')->pluck('reconciliation_number');
        foreach ($numbers as $num) {
            if (preg_match('/^REC-\d{4}-(\d+)$/i', $num, $m)) {
                $val = (int) $m[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        return $prefix . str_pad((string) ($maxNum + 1), 4, '0', STR_PAD_LEFT);
    }
}

