<?php

namespace Tests\Feature;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InsurancePlanPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_金額変更で現行価格が自動的に閉じられ価格履歴が残る(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = InsurancePlan::factory()->create();
        $current = InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => 10000,
            'effective_from' => '2026-08-01',
            'effective_to' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('settings.plans.prices.store', $plan), [
                'amount_yen' => 12000,
                'effective_from' => '2027-01-01',
            ])
            ->assertRedirect(route('settings.plans.edit', $plan));

        $this->assertSame('2026-12-31', $current->fresh()->effective_to?->toDateString());
        $this->assertDatabaseHas('insurance_plan_prices', ['amount_yen' => 12000, 'insurance_plan_id' => $plan->id]);
        $this->assertSame(2, $plan->prices()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance_plan_price.changed']);
    }

    public function test_有効期間が重複する価格は登録できない(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = InsurancePlan::factory()->create();
        InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => 10000,
            'effective_from' => '2026-08-01',
            'effective_to' => '2026-12-31',
        ]);

        $this->actingAs($admin)
            ->post(route('settings.plans.prices.store', $plan), [
                'amount_yen' => 12000,
                'effective_from' => '2026-10-01',
                'effective_to' => '2026-11-30',
            ])
            ->assertSessionHasErrors('effective_from');

        $this->assertSame(1, $plan->prices()->count());
    }

    public function test_staffは金額を変更できない(): void
    {
        $staff = User::factory()->create();
        $plan = InsurancePlan::factory()->create();

        $this->actingAs($staff)
            ->post(route('settings.plans.prices.store', $plan), [
                'amount_yen' => 12000,
                'effective_from' => '2027-01-01',
            ])
            ->assertForbidden();
    }
}
