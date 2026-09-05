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

    /** 都道府県（JIS X 0401 の順）。入力揺れを防ぐため選択式にします。 */
    public const PREFECTURES = [
        '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
        '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
        '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県',
        '岐阜県', '静岡県', '愛知県', '三重県',
        '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県',
        '鳥取県', '島根県', '岡山県', '広島県', '山口県',
        '徳島県', '香川県', '愛媛県', '高知県',
        '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
    ];

    protected $fillable = [
        'customer_code',
        'name',
        'name_kana',
        'birth_date',
        'postal_code',
        'prefecture',
        'city',
        'address_line1',
        'address_line2',
        'building',
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
            // 住所は機微情報のため、分割した各列もすべて暗号化します（仕様 6.8）。
            'postal_code' => 'encrypted',
            'prefecture' => 'encrypted',
            'city' => 'encrypted',
            'address_line1' => 'encrypted',
            'address_line2' => 'encrypted',
            'building' => 'encrypted',
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

    /** 郵便番号を除いた住所を 1 行へ連結します。未入力の項目は詰めます。 */
    public function getFullAddressAttribute(): string
    {
        return implode(' ', array_filter([
            $this->prefecture,
            $this->city,
            $this->address_line1,
            $this->address_line2,
            $this->building,
        ], static fn (?string $part): bool => $part !== null && $part !== ''));
    }

    /** 「〒123-4567」形式。未入力なら空文字を返します。 */
    public function getPostalCodeLabelAttribute(): string
    {
        return $this->postal_code === null || $this->postal_code === ''
            ? ''
            : '〒'.$this->postal_code;
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
