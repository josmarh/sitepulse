<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringCheck extends Model
{
    protected $fillable = [
        'website_id',
        'check_type',
        'status',
        'http_status_code',
        'response_time_ms',
        'error_message',
        'metadata',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
