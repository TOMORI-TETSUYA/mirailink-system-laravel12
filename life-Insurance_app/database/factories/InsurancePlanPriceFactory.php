<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InsurancePlanPrice> */
final class InsurancePlanPriceFactory extends Factory
{
    protected $model = InsurancePlanPrice::class;

    public function definition(): array
    {
        return [
            'insurance_plan_id' => InsurancePlan::factory(),
            'amount_yen' => 12000,
            'effective_from' => now()->subMonth()->toDateString(),
            'effective_to' => null,
            'created_by' => User::factory()->admin(),
        ];
    }
}
