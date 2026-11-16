<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetTransfer extends Model
{
    use HasFactory;

    public const REASONS = [
        'Mutasi ruangan',
        'Kebutuhan operasional',
        'Pergantian pengguna',
        'Renovasi ruangan',
        'Penggantian perangkat',
        'Penataan ulang perangkat',
        'Lainnya',
    ];

    public const STATUSES = [
        'Menunggu',
        'Selesai',
        'Dibatalkan',
    ];

    protected $fillable = [
        'transfer_number',
        'asset_id',
        'from_room_id',
        'to_room_id',
        'previous_pic',
        'new_pic',
        'transfer_date',
        'transfer_reason',
        'description',
        'status',
        'created_by',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'transfer_date' => 'datetime:Y-m-d H:i:s',
            'cancelled_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id')->withTrashed();
    }

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetTransferDocument::class, 'asset_transfer_id');
    }

    public static function generateNextTransferNumber(): string
    {
        $year = date('Y');
        $prefix = "TRF-{$year}-";
        $maxNum = 0;

        $numbers = static::where('transfer_number', 'like', $prefix . '%')->pluck('transfer_number');
        foreach ($numbers as $num) {
            if (preg_match('/^TRF-\d{4}-(\d+)$/i', $num, $m)) {
                $val = (int) $m[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        $next = $maxNum + 1;
        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

