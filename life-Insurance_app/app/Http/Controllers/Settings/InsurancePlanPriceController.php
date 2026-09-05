<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Plans\ChangePlanPrice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plans\StoreInsurancePlanPriceRequest;
use App\Models\InsurancePlan;
use Illuminate\Http\RedirectResponse;

final class InsurancePlanPriceController extends Controller
{
    public function store(
        StoreInsurancePlanPriceRequest $request,
        InsurancePlan $plan,
        ChangePlanPrice $action
    ): RedirectResponse {
        $price = $action->execute($plan, $request->validated(), $request->user()->id);

        return redirect()
            ->route('settings.plans.edit', $plan)
            ->with('status', "金額を {$price->formatted_amount}（{$price->effective_from->format('Y年n月j日')}〜）へ変更しました。");
    }
}
