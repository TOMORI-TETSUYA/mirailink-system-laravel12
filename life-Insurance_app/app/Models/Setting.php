<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 保存期間などの運用設定。値をソースコードへ固定しないための key-value テーブルです。 */
final class Setting extends Model
{
    public const KEY_RETENTION_YEARS = 'customer_retention_years';

    protected $fillable = ['key', 'value', 'updated_by'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = self::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    public static function put(string $key, ?string $value, ?int $userId): self
    {
        return self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $userId],
        );
    }

    public static function retentionYears(): ?int
    {
        $value = self::get(self::KEY_RETENTION_YEARS);

        if ($value !== null && $value !== '') {
            return (int) $value;
        }

        return config('mirailink.customer_retention_years');
    }
}
