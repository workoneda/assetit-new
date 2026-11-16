<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'Menunggu',
        'Sedang Dikerjakan',
        'Selesai',
    ];

    protected $fillable = [
        'ticket_number',
        'asset_id',
        'maintenance_date',
        'completed_date',
        'job_type',
        'complaint',
        'action_taken',
        'technician',
        'cost',
        'status',
        'condition_after',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date:Y-m-d',
            'completed_date' => 'date:Y-m-d',
            'cost' => 'float',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateNextTicketNumber(): string
    {
        $year = date('Y');
        $prefix = "MNT-{$year}-";
        $maxNum = 0;

        $tickets = static::where('ticket_number', 'like', $prefix . '%')->pluck('ticket_number');
        foreach ($tickets as $ticket) {
            if (preg_match('/^MNT-\d{4}-(\d+)$/i', $ticket, $m)) {
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
