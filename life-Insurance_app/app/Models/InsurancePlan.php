<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class InsurancePlan extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_DELETED = 'deleted';

    public const STATUSES = [
        self::STATUS_DRAFT => '下書き',
        self::STATUS_ACTIVE => '有効',
        self::STATUS_INACTIVE => '無効',
        self::STATUS_DELETED => '削除済',
    ];

    public const BILLING_CYCLES = [
        'monthly' => '月額',
        'annual' => '年額',
        'single' => '一時払',
        'other' => 'その他',
    ];

    public const CATEGORY_LIFE = 'life';
    public const CATEGORY_NON_LIFE = 'non_life';
    public const CATEGORY_CORPORATE = 'corporate';

    /** 保険分類。表示順もこの並びに従います。 */
    public const CATEGORIES = [
        self::CATEGORY_LIFE => '生命保険',
        self::CATEGORY_NON_LIFE => '損害保険',
        self::CATEGORY_CORPORATE => '法人様向け保険',
    ];

    /** 分類ごとの説明。登録画面と一覧で担当者向けに表示します。 */
    public const CATEGORY_DESCRIPTIONS = [
        self::CATEGORY_LIFE => '必要な生命保険や医療保険など、お客様のお暮らしに合った最適な保険',
        self::CATEGORY_NON_LIFE => '損害保険は自動車や住宅といった「家財」など財産の事故や損害に備える保険です。お客様のご要望に応じて最適な損害保険。',
        self::CATEGORY_CORPORATE => '信頼と実績の豊富なファイナンシャルプランナーが、様々なリスクを回避する為の法人様向け保険',
    ];

    protected $fillable = [
        'plan_code',
        'plan_name',
        'plan_type',
        'category',
        'insurer_name',
        'billing_cycle',
        'status',
        'display_order',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function prices(): HasMany
    {
        return $this->hasMany(InsurancePlanPrice::class)->orderByDesc('effective_from');
    }

    /** 本日時点で適用中の価格。 */
    public function currentPrice(): HasOne
    {
        $today = now()->toDateString();

        return $this->hasOne(InsurancePlanPrice::class)
            ->whereDate('effective_from', '<=', $today)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from');
    }

    /** 本日以降に適用される、または適用中の価格が存在するか。 */
    public function hasCurrentOrFuturePrice(): bool
    {
        $today = now()->toDateString();

        return $this->prices()
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->exists();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(InsuranceContract::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('plan_name');
    }

    /** 保険分類での絞り込み。未指定や不正値は絞り込みません。 */
    public function scopeOfCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || ! array_key_exists($category, self::CATEGORIES)) {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeSearchName(Builder $query, ?string $keyword): Builder
    {
        $keyword = trim((string) $keyword);

        if ($keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword): void {
            $q->where('plan_name', 'like', "%{$keyword}%")
                ->orWhere('insurer_name', 'like', "%{$keyword}%");
        });
    }

    public function isSelectable(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return self::BILLING_CYCLES[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getCategoryDescriptionAttribute(): string
    {
        return self::CATEGORY_DESCRIPTIONS[$this->category] ?? '';
    }
}
