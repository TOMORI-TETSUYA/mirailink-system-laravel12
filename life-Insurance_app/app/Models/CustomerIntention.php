<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerIntention extends Model
{
    public const CONFIRMATION_METHODS = [
        'in_person' => '対面',
        'phone' => '電話',
        'online' => 'オンライン',
        'document' => '書面',
    ];

    protected $fillable = [
        'customer_id',
        'created_by',
        'initial_intention',
        'final_intention',
        'protection_purpose',
        'budget',
        'desired_period',
        'proposed_reason',
        'differences',
        'confirmed_at',
        'confirmation_method',
    ];

    protected function casts(): array
    {
        return [
            'initial_intention' => 'encrypted',
            'final_intention' => 'encrypted',
            'protection_purpose' => 'encrypted',
            'budget' => 'encrypted',
            'desired_period' => 'encrypted',
            'proposed_reason' => 'encrypted',
            'differences' => 'encrypted',
            'confirmed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getConfirmationMethodLabelAttribute(): string
    {
        return self::CONFIRMATION_METHODS[$this->confirmation_method] ?? ($this->confirmation_method ?? '-');
    }
}
