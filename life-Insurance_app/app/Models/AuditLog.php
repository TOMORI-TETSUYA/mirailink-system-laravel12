<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'request_id',
        'action',
        'target_type',
        'target_id',
        'changed_fields',
        'ip_address',
        'user_agent',
        'succeeded',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'ip_address' => 'encrypted',
            'user_agent' => 'encrypted',
            'succeeded' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTargetLabelAttribute(): string
    {
        return class_basename((string) $this->target_type);
    }
}
