<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staffはユーザー管理画面を開けない(): void
    {
        $this->actingAs(User::factory()->create())->get(route('users.index'))->assertForbidden();
    }

    public function test_auditorは顧客を更新できない(): void
    {
        $auditor = User::factory()->auditor()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($auditor)
            ->put(route('customers.update', $customer), ['name' => '変更', 'assigned_user_id' => $customer->assigned_user_id, 'status' => 'lead'])
            ->assertForbidden();
    }

    public function test_担当外顧客をstaffが閲覧できない(): void
    {
        $staff = User::factory()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($staff)->get(route('customers.show', $customer))->assertForbidden();
    }

    public function test_auditorは監査ログを閲覧でき_staffは閲覧できない(): void
    {
        $this->actingAs(User::factory()->auditor()->create())->get(route('audit-logs.index'))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_顧客詳細にはno_storeヘッダーが付与され健康情報タブは権限がなければ出力されない(): void
    {
        $admin = User::factory()->admin()->create();
        $auditor = User::factory()->auditor()->create();
        $customer = Customer::factory()->create(['health_information' => '既往歴テキスト']);

        $response = $this->actingAs($admin)->get(route('customers.show', $customer));
        $response->assertOk()->assertSee('健康情報');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->actingAs($auditor)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertDontSee('既往歴テキスト');
    }

    public function test_削除済み顧客は通常一覧へ表示されない(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $admin->id]);

        $this->actingAs($admin)->delete(route('customers.destroy', $customer));

        $this->actingAs($admin)->get(route('customers.index'))->assertDontSee($customer->customer_code);
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
