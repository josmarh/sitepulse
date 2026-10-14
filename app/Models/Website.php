<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $fillable = [
        'name',
        'url',
        'host',
        'check_interval_minutes',
        'timeout_seconds',
        'is_active',
        'current_status',
        'last_checked_at',
        'next_check_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
        ];
    }

    public function monitoringChecks(): HasMany
    {
        return $this->hasMany(MonitoringCheck::class);
    }
}
