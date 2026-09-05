<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 住所の分割入力（郵便番号・都道府県・市区町村・住所1・住所2・建物名）。
 *
 * 住所は機微情報のため、各列とも暗号化して保存されることも併せて確認します（仕様 6.8）。
 */
final class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'postal_code' => '123-4567',
            'prefecture' => '東京都',
            'city' => '千代田区',
            'address_line1' => '丸の内1-1-1',
            'address_line2' => '2番地',
            'building' => 'ミライビル 101',
            'status' => 'lead',
            'consented' => '1',
        ], $overrides);
    }

    public function test_住所を分割して登録できる(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->payload(['assigned_user_id' => $admin->id]))
            ->assertRedirect();

        $customer = Customer::query()->firstOrFail();

        $this->assertSame('123-4567', $customer->postal_code);
        $this->assertSame('東京都', $customer->prefecture);
        $this->assertSame('千代田区', $customer->city);
        $this->assertSame('丸の内1-1-1', $customer->address_line1);
        $this->assertSame('2番地', $customer->address_line2);
        $this->assertSame('ミライビル 101', $customer->building);
    }

    public function test_住所の各列は暗号化して保存される(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->payload(['assigned_user_id' => $admin->id]));

        $raw = DB::table('customers')->first();

        foreach (['postal_code', 'prefecture', 'city', 'address_line1', 'address_line2', 'building'] as $column) {
            $this->assertNotNull($raw->{$column});
            // 平文がそのまま保存されていないことを確認します。
            $this->assertStringNotContainsString('東京都', $raw->prefecture);
            $this->assertStringNotContainsString('123-4567', $raw->postal_code);
            $this->assertStringNotContainsString('丸の内', $raw->address_line1);
        }
    }

    public function test_連結した住所が取得できる(): void
    {
        $customer = Customer::factory()->create([
            'prefecture' => '大阪府',
            'city' => '大阪市北区',
            'address_line1' => '梅田1-1',
            'address_line2' => null,
            'building' => 'テストビル',
            'postal_code' => '5300001',
        ]);

        $this->assertSame('大阪府 大阪市北区 梅田1-1 テストビル', $customer->full_address);
        $this->assertSame('〒5300001', $customer->postal_code_label);
    }

    public static function 不正な郵便番号(): array
    {
        return [
            '桁が少ない' => ['123-456'],
            '桁が多い' => ['123-45678'],
            '英字を含む' => ['12a-4567'],
            '全角' => ['１２３-４５６７'],
        ];
    }

    #[DataProvider('不正な郵便番号')]
    public function test_郵便番号の形式を検証する(string $postalCode): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->payload([
                'assigned_user_id' => $admin->id,
                'postal_code' => $postalCode,
            ]))
            ->assertSessionHasErrors('postal_code');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_郵便番号はハイフン無しでも登録できる(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->payload([
                'assigned_user_id' => $admin->id,
                'postal_code' => '1234567',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('1234567', Customer::query()->firstOrFail()->postal_code);
    }

    public function test_定義外の都道府県は拒否される(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->payload([
                'assigned_user_id' => $admin->id,
                'prefecture' => '存在しない県',
            ]))
            ->assertSessionHasErrors('prefecture');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_住所は任意項目のため空でも登録できる(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'name' => '住所なし太郎',
                'assigned_user_id' => $admin->id,
                'status' => 'lead',
                'consented' => '1',
            ])
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->firstOrFail();
        $this->assertNull($customer->postal_code);
        $this->assertSame('', $customer->full_address);
    }

    public function test_更新でも住所を分割して保存できる(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['assigned_user_id' => $admin->id]);

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), $this->payload([
                'assigned_user_id' => $admin->id,
                'prefecture' => '北海道',
                'city' => '札幌市中央区',
            ]))
            ->assertRedirect();

        $fresh = $customer->fresh();
        $this->assertSame('北海道', $fresh->prefecture);
        $this->assertSame('札幌市中央区', $fresh->city);
    }
}
