<?php

namespace Database\Factories;

use App\Models\InsurancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InsurancePlan> */
final class InsurancePlanFactory extends Factory
{
    protected $model = InsurancePlan::class;

    public function definition(): array
    {
        return [
            'plan_code' => 'P'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'plan_name' => '医療保障'.$this->faker->word(),
            'category' => InsurancePlan::CATEGORY_LIFE,
            'plan_type' => '医療',
            'insurer_name' => $this->faker->company(),
            'billing_cycle' => 'monthly',
            'status' => InsurancePlan::STATUS_ACTIVE,
            'display_order' => 0,
            'notes' => null,
            'created_by' => User::factory()->admin(),
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => InsurancePlan::STATUS_DRAFT]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => InsurancePlan::STATUS_INACTIVE]);
    }

    public function nonLife(): static
    {
        return $this->state(fn () => [
            'category' => InsurancePlan::CATEGORY_NON_LIFE,
            'plan_name' => '自動車保険'.$this->faker->word(),
            'plan_type' => '自動車',
        ]);
    }

    public function corporate(): static
    {
        return $this->state(fn () => [
            'category' => InsurancePlan::CATEGORY_CORPORATE,
            'plan_name' => '法人総合補償'.$this->faker->word(),
            'plan_type' => '賠償責任',
        ]);
    }
}
