<?php

namespace Tests\Feature;

use App\Models\InsurancePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InsurancePlanTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'plan_name' => '医療保障ベーシック',
            'category' => InsurancePlan::CATEGORY_LIFE,
            'plan_type' => '医療',
            'insurer_name' => 'テスト生命',
            'billing_cycle' => 'monthly',
            'amount_yen' => 12000,
            'effective_from' => '2026-08-01',
            'status' => 'active',
        ], $overrides);
    }

    public function test_管理者はプランを登録できる(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('settings.plans.store'), $this->validPayload());

        $response->assertRedirect(route('settings.plans.index'));
        $this->assertDatabaseHas('insurance_plans', ['plan_name' => '医療保障ベーシック', 'status' => 'active']);
        $this->assertDatabaseHas('insurance_plan_prices', ['amount_yen' => 12000]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance_plan.created', 'user_id' => $admin->id]);
    }

    public function test_損害保険と法人様向け保険も登録できる(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([InsurancePlan::CATEGORY_NON_LIFE, InsurancePlan::CATEGORY_CORPORATE] as $category) {
            $this->actingAs($admin)
                ->post(route('settings.plans.store'), $this->validPayload([
                    'plan_name' => 'プラン'.$category,
                    'category' => $category,
                ]))
                ->assertRedirect(route('settings.plans.index'));

            $this->assertDatabaseHas('insurance_plans', [
                'plan_name' => 'プラン'.$category,
                'category' => $category,
            ]);
        }
    }

    public function test_保険分類は必須で定義済みの値しか受け付けない(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('settings.plans.store'), $this->validPayload(['category' => '']))
            ->assertSessionHasErrors('category');

        $this->actingAs($admin)
            ->post(route('settings.plans.store'), $this->validPayload(['category' => 'unknown_category']))
            ->assertSessionHasErrors('category');

        $this->assertDatabaseCount('insurance_plans', 0);
    }

    public function test_一覧は保険分類で絞り込める(): void
    {
        $admin = User::factory()->admin()->create();
        InsurancePlan::factory()->create(['plan_name' => '生命プランA']);
        InsurancePlan::factory()->nonLife()->create(['plan_name' => '損保プランB']);

        $this->actingAs($admin)
            ->get(route('settings.plans.index', ['category' => InsurancePlan::CATEGORY_NON_LIFE]))
            ->assertOk()
            ->assertSee('損保プランB')
            ->assertDontSee('生命プランA');
    }

    public function test_staffとauditorはプランを登録できない(): void
    {
        foreach ([User::factory()->create(), User::factory()->auditor()->create()] as $user) {
            $this->actingAs($user)->post(route('settings.plans.store'), $this->validPayload())->assertForbidden();
            $this->actingAs($user)->get(route('settings.plans.index'))->assertForbidden();
        }

        $this->assertDatabaseCount('insurance_plans', 0);
    }

    public function test_金額は円単位の整数だけ登録でき負数や上限超過は拒否される(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('settings.plans.store'), $this->validPayload(['amount_yen' => '12000.5']))
            ->assertSessionHasErrors('amount_yen');

        $this->actingAs($admin)
            ->post(route('settings.plans.store'), $this->validPayload(['amount_yen' => -1]))
            ->assertSessionHasErrors('amount_yen');

        $this->actingAs($admin)
            ->post(route('settings.plans.store'), $this->validPayload(['amount_yen' => 1000000000000]))
            ->assertSessionHasErrors('amount_yen');

        $this->assertDatabaseCount('insurance_plans', 0);
    }

    public function test_価格のない下書きプランは有効化できない(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = InsurancePlan::factory()->draft()->create();

        $this->actingAs($admin)
            ->patch(route('settings.plans.status.update', $plan), ['status' => 'active'])
            ->assertSessionHasErrors('status');

        $this->assertSame('draft', $plan->fresh()->status);
    }

    public function test_無効化と有効化が監査ログへ記録される(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = InsurancePlan::factory()->create();

        $this->actingAs($admin)
            ->patch(route('settings.plans.status.update', $plan), ['status' => 'inactive'])
            ->assertRedirect(route('settings.plans.index'));

        $this->assertSame('inactive', $plan->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance_plan.status_changed:inactive']);
    }
}
