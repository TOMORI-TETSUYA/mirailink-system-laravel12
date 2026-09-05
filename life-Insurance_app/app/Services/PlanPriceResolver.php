<?php

namespace App\Services;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/** 契約日を基準に、その時点で有効な価格を1件だけ返します（仕様 15.1）。 */
final class PlanPriceResolver
{
    public function resolve(
        InsurancePlan $plan,
        CarbonInterface $contractDate
    ): ?InsurancePlanPrice {
        return $plan->prices()
            ->whereDate('effective_from', '<=', $contractDate)
            ->where(function (Builder $query) use ($contractDate): void {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $contractDate);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
