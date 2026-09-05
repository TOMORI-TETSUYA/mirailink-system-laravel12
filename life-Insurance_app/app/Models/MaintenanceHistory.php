<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MaintenanceHistory extends Model
{
    public const TYPES = [
        'address_change' => '住所変更',
        'beneficiary_change' => '受取人変更',
        'claim' => '給付金請求',
        'surrender' => '解約',
        'other' => 'その他',
    ];

    public const STATUSES = [
        'requested' => '受付',
        'in_progress' => '処理中',
        'completed' => '完了',
    ];

    protected $fillable = [
        'customer_id',
        'user_id',
        'type',
        'description',
        'status',
        'requested_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'encrypted',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
