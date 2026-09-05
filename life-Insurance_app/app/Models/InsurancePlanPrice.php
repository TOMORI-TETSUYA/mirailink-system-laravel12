<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InsurancePlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_plan_id',
        'amount_yen',
        'effective_from',
        'effective_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_yen' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount_yen).'円';
    }
}
