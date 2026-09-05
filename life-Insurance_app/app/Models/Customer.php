<?php

namespace App\Models;

use App\Casts\EncryptedSearchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUSES = [
        'lead' => '見込み',
        'active' => '取引中',
        'inactive' => '取引終了',
    ];

    protected $fillable = [
        'customer_code',
        'name',
        'name_kana',
        'birth_date',
        'address',
        'phone',
        'phone_hmac',
        'email',
        'email_hmac',
        'occupation',
        'family',
        'health_information',
        'assigned_user_id',
        'status',
        'consented_at',
    ];

    protected $hidden = [
        'phone_hmac',
        'email_hmac',
        'health_information',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'encrypted',
            'name_kana' => 'encrypted',
            'birth_date' => 'encrypted',
            'address' => 'encrypted',
            'phone' => EncryptedSearchable::class.':phone',
            'email' => EncryptedSearchable::class.':email',
            'occupation' => 'encrypted',
            'family' => 'encrypted',
            'health_information' => 'encrypted',
            'consented_at' => 'datetime',
        ];
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function intentions(): HasMany
    {
        return $this->hasMany(CustomerIntention::class)->latest();
    }

    public function latestIntention(): HasOne
    {
        return $this->hasOne(CustomerIntention::class)->latestOfMany();
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(InsuranceContract::class)->latest('contract_date');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class)->latest('contacted_at');
    }

    public function latestInteraction(): HasOne
    {
        return $this->hasOne(Interaction::class)->latestOfMany('contacted_at');
    }

    public function maintenanceHistories(): HasMany
    {
        return $this->hasMany(MaintenanceHistory::class)->latest('requested_at');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'target_id')
            ->where('target_type', self::class)
            ->latest();
    }

    /** 顧客コードによる検索（前方一致）。暗号化列は検索対象にしません。 */
    public function scopeSearchCode(Builder $query, ?string $code): Builder
    {
        if ($code === null || trim($code) === '') {
            return $query;
        }

        return $query->where('customer_code', 'like', strtoupper(trim($code)).'%');
    }

    /** 担当者スコープ。staff は自分の担当顧客のみ。 */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isStaff()) {
            return $query->where('assigned_user_id', $user->id);
        }

        return $query;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIntentionStatusLabelAttribute(): string
    {
        $intention = $this->relationLoaded('latestIntention')
            ? $this->latestIntention
            : $this->latestIntention()->first();

        if ($intention === null) {
            return '未登録';
        }

        return $intention->confirmed_at ? '確認済' : '未完了';
    }

    public function getContractStatusLabelAttribute(): string
    {
        $count = $this->relationLoaded('contracts')
            ? $this->contracts->count()
            : $this->contracts()->count();

        return $count > 0 ? "契約 {$count}件" : '契約なし';
    }
}
