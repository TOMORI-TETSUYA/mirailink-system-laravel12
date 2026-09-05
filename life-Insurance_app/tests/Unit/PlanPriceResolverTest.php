<?php

namespace Tests\Unit;

use App\Models\InsurancePlan;
use App\Models\InsurancePlanPrice;
use App\Services\PlanPriceResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanPriceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_契約日時点で有効な価格を1件だけ返す(): void
    {
        $plan = InsurancePlan::factory()->create();

        InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => 10000,
            'effective_from' => '2026-08-01',
            'effective_to' => '2026-12-31',
        ]);
        InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => 12000,
            'effective_from' => '2027-01-01',
            'effective_to' => null,
        ]);

        $resolver = new PlanPriceResolver();

        $this->assertSame(10000, $resolver->resolve($plan, CarbonImmutable::parse('2026-10-15'))?->amount_yen);
        $this->assertSame(12000, $resolver->resolve($plan, CarbonImmutable::parse('2027-03-01'))?->amount_yen);
    }

    public function test_適用開始前または終了後の日付では価格を返さない(): void
    {
        $plan = InsurancePlan::factory()->create();

        InsurancePlanPrice::factory()->for($plan, 'plan')->create([
            'amount_yen' => 10000,
            'effective_from' => '2026-08-01',
            'effective_to' => '2026-12-31',
        ]);

        $resolver = new PlanPriceResolver();

        $this->assertNull($resolver->resolve($plan, CarbonImmutable::parse('2026-07-31')));
        $this->assertNull($resolver->resolve($plan, CarbonImmutable::parse('2027-01-01')));
    }
}
