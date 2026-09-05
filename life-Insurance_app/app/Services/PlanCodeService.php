<?php

namespace App\Services;

use App\Models\InsurancePlan;
use Illuminate\Support\Str;

/** 重複しないプランコードを発行します。形式: P + 年月 + 6桁英数字。 */
final class PlanCodeService
{
    public function generate(): string
    {
        do {
            $code = 'P'.now()->format('Ym').'-'.strtoupper(Str::random(6));
        } while (InsurancePlan::withTrashed()->where('plan_code', $code)->exists());

        return $code;
    }
}
