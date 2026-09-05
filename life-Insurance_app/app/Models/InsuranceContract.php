<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InsuranceContract extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'applied' => '申込中',
        'in_force' => '有効',
        'lapsed' => '失効',
        'cancelled' => '解約',
        'matured' => '満期',
    ];

    protected $fillable = [
        'customer_id',
        'created_by',
        'insurance_plan_id',
        'insurance_plan_price_id',
        'insurer_name_snapshot',
        'plan_name_snapshot',
        'plan_type_snapshot',
        'premium_amount_snapshot',
        'billing_cycle_snapshot',
        'price_override_reason',
        'is_price_overridden',
        'policy_number',
        'coverage',
        'contract_date',
        'maturity_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'insurer_name_snapshot' => 'encrypted',
            'plan_name_snapshot' => 'encrypted',
            'plan_type_snapshot' => 'encrypted',
            'premium_amount_snapshot' => 'encrypted',
            'price_override_reason' => 'encrypted',
            'is_price_overridden' => 'boolean',
            'policy_number' => 'encrypted',
            'coverage' => 'encrypted',
            'contract_date' => 'date',
            'maturity_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(InsurancePlan::class, 'insurance_plan_id')->withTrashed();
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(InsurancePlanPrice::class, 'insurance_plan_price_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return InsurancePlan::BILLING_CYCLES[$this->billing_cycle_snapshot] ?? $this->billing_cycle_snapshot;
    }

    public function getFormattedPremiumAttribute(): string
    {
        return number_format((int) $this->premium_amount_snapshot).'円';
    }
}
