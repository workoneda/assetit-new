<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KsoPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'contract_number',
        'contact_person',
        'phone',
        'email',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function reconciliationSessions(): HasMany
    {
        return $this->hasMany(ReconciliationSession::class, 'kso_partner_id');
    }
}

