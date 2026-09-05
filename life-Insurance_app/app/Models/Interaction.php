<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Interaction extends Model
{
    public const CHANNELS = [
        'visit' => '訪問',
        'phone' => '電話',
        'email' => 'メール',
        'online' => 'オンライン',
        'other' => 'その他',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'channel',
        'summary',
        'next_action',
        'contacted_at',
        'next_action_at',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'encrypted',
            'next_action' => 'encrypted',
            'contacted_at' => 'datetime',
            'next_action_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }
}
