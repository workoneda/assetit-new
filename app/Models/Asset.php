<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    public const CATEGORIES = [
        'Komputer / PC',
        'Laptop',
        'Printer',
        'Switch',
        'Router',
        'Server',
        'Access Point',
        'UPS',
        'Perangkat IT lainnya',
    ];

    public const OWNERSHIPS = [
        'Milik RSUD',
        'KSO',
        'Milik Pihak Ketiga',
    ];

    public const CONDITIONS = [
        'Baik',
        'Rusak',
        'Maintenance',
    ];

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'ip_address',
        'mac_address',
        'subnet_mask',
        'vlan',
        'uplink_asset_id',
        'network_status',
        'latency_ms',
        'last_checked_at',
        'specifications',
        'room_id',
        'pic',
        'ownership',
        'partner_name',
        'ownership_notes',
        'condition',
        'acquisition_year',
        'photo_path',
        'notes',
        'needs_followup',
        'followup_note',
        'created_by',
        'updated_by',
    ];

    protected $appends = [
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_year' => 'integer',
            'latency_ms' => 'integer',
            'needs_followup' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        if (preg_match('#drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#i', $this->photo_path, $m)) {
            return 'https://drive.google.com/thumbnail?id=' . $m[1] . '&sz=w800';
        }
        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://') || str_starts_with($this->photo_path, '/')) {
            return $this->photo_path;
        }
        return Storage::url($this->photo_path);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function uplink(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'uplink_asset_id');
    }

    public function downlinks(): HasMany
    {
        return $this->hasMany(Asset::class, 'uplink_asset_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class, 'asset_id')->orderByDesc('created_at');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(Maintenance::class, 'asset_id')->orderByDesc('maintenance_date')->orderByDesc('id');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class, 'asset_id')
            ->orderBy('transfer_date', 'asc')
            ->orderBy('id', 'asc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function generateNextCode(): string
    {
        $maxNum = 0;
        $codes = static::withTrashed()
            ->where('asset_code', 'like', 'AST-%')
            ->pluck('asset_code');

        foreach ($codes as $code) {
            if (preg_match('/^AST-(\d+)$/i', $code, $m)) {
                $val = (int) $m[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        $next = $maxNum + 1;
        return 'AST-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
