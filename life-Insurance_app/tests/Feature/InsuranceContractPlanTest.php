<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InsuranceContract;
use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InsuranceContractPlanTest extends TestCase
{
    use RefreshDatabase;

    private function preparePlanWithPrice(int $amount = 12000): InsurancePlan
    {
        $plan = InsurancePlan::factory()->create(['plan_name' => '医療保障ベーシック']);
        InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => $amount,
            'effective_from' => '2026-08-01',
            'effective_to' => null,
        ]);

        return $plan;
    }

    public function test_契約登録時にプランを選ぶだけで契約日時点の金額がスナップショット保存される(): void
    {
        $staff = User::factory()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $staff->id]);
        $plan = $this->preparePlanWithPrice();

        $this->actingAs($staff)
            ->post(route('customers.contracts.store', $customer), [
                'insurance_plan_id' => $plan->id,
                'contract_date' => '2026-09-01',
                'status' => 'applied',
            ])
            ->assertRedirect(route('customers.show', ['customer' => $customer, 'tab' => 'contracts']));

        $contract = InsuranceContract::query()->firstOrFail();
        $this->assertSame('12000', $contract->premium_amount_snapshot);
        $this->assertSame('医療保障ベーシック', $contract->plan_name_snapshot);
        $this->assertFalse($contract->is_price_overridden);
    }

    public function test_プラン金額を変更しても既存契約の金額は変わらない(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $admin->id]);
        $plan = $this->preparePlanWithPrice(10000);

        $this->actingAs($admin)->post(route('customers.contracts.store', $customer), [
            'insurance_plan_id' => $plan->id,
            'contract_date' => '2026-09-01',
            'status' => 'in_force',
        ]);

        $this->actingAs($admin)->post(route('settings.plans.prices.store', $plan), [
            'amount_yen' => 15000,
            'effective_from' => '2027-01-01',
        ]);

        $this->assertSame('10000', InsuranceContract::query()->firstOrFail()->premium_amount_snapshot);
    }

    public function test_契約日時点で有効な金額がないプランは契約登録できない(): void
    {
        $staff = User::factory()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $staff->id]);
        $plan = $this->preparePlanWithPrice();

        $this->actingAs($staff)
            ->post(route('customers.contracts.store', $customer), [
                'insurance_plan_id' => $plan->id,
                'contract_date' => '2026-07-01',
                'status' => 'applied',
            ])
            ->assertSessionHasErrors('insurance_plan_id');

        $this->assertDatabaseCount('insurance_contracts', 0);
    }

    public function test_無効プランは新規契約で選択できない(): void
    {
        $staff = User::factory()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $staff->id]);
        $plan = $this->preparePlanWithPrice();
        $plan->forceFill(['status' => InsurancePlan::STATUS_INACTIVE])->save();

        $this->actingAs($staff)
            ->post(route('customers.contracts.store', $customer), [
                'insurance_plan_id' => $plan->id,
                'contract_date' => '2026-09-01',
                'status' => 'applied',
            ])
            ->assertSessionHasErrors('insurance_plan_id');
    }

    public function test_管理者の金額上書きには理由が必要で監査ログへ記録される(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $admin->id]);
        $plan = $this->preparePlanWithPrice();

        $this->actingAs($admin)
            ->post(route('customers.contracts.store', $customer), [
                'insurance_plan_id' => $plan->id,
                'contract_date' => '2026-09-01',
                'status' => 'applied',
                'override_price' => 1,
                'override_amount_yen' => 11000,
            ])
            ->assertSessionHasErrors('price_override_reason');

        $this->actingAs($admin)
            ->post(route('customers.contracts.store', $customer), [
                'insurance_plan_id' => $plan->id,
                'contract_date' => '2026-09-01',
                'status' => 'applied',
                'override_price' => 1,
                'override_amount_yen' => 11000,
                'price_override_reason' => '既契約者向け特約割引',
            ])
            ->assertSessionHasNoErrors();

        $contract = InsuranceContract::query()->firstOrFail();
        $this->assertTrue($contract->is_price_overridden);
        $this->assertSame('11000', $contract->premium_amount_snapshot);
        $this->assertDatabaseHas('audit_logs', ['action' => 'insurance_contract.price_overridden']);
    }

    public function test_staffは金額を上書きできない(): void
    {
        $staff = User::factory()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $staff->id]);
        $plan = $this->preparePlanWithPrice();

        $this->actingAs($staff)->post(route('customers.contracts.store', $customer), [
            'insurance_plan_id' => $plan->id,
            'contract_date' => '2026-09-01',
            'status' => 'applied',
            'override_price' => 1,
            'override_amount_yen' => 1,
            'price_override_reason' => '不正な上書き',
        ]);

        $this->assertSame('12000', InsuranceContract::query()->firstOrFail()->premium_amount_snapshot);
    }

    public function test_顧客の氏名と健康情報はDB上で平文になっていない(): void
    {
        $customer = Customer::factory()->create([
            'name' => '山田 太郎',
            'health_information' => '既往歴あり',
        ]);

        $row = DB::table('customers')->where('id', $customer->id)->first();

        $this->assertNotSame('山田 太郎', $row->name);
        $this->assertNotSame('既往歴あり', $row->health_information);
        $this->assertStringNotContainsString('山田', $row->name);
    }
}
