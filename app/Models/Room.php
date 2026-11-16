<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'building',
        'pic_name',
        'description',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'room_id');
    }

    public static function generateNextCode(): string
    {
        $last = static::orderByDesc('id')->first();
        if (!$last) {
            return 'RM-001';
        }
        if (preg_match('/RM-(\d+)/i', $last->code, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = $last->id + 1;
        }
        return 'RM-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
