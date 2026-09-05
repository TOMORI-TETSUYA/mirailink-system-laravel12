<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * パスワード規約（仕様 6.3 + 大文字・小文字・数字・記号の必須化）。
 *
 * 規約は AppServiceProvider の Password::defaults() 一箇所で定義しているため、
 * 本人によるパスワード変更・管理者によるユーザー作成／再設定のすべてに適用されます。
 */
final class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** JS の自動生成が満たす条件と同じ、有効なパスワードです。 */
    private const VALID = 'Kx7#mQp2$wRt9Zb4';

    public static function 規約違反のパスワード(): array
    {
        return [
            '大文字がない' => ['kx7#mqp2$wrt9zb4'],
            '小文字がない' => ['KX7#MQP2$WRT9ZB4'],
            '数字がない' => ['KxA#mQpB$wRtCZbD'],
            '記号がない' => ['Kx7amQp2BwRt9Zb4'],
            '12文字未満' => ['Kx7#mQp2$w'],
        ];
    }

    #[DataProvider('規約違反のパスワード')]
    public function test_本人のパスワード変更は規約違反を拒否する(string $password): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'TestPassword-12345',
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('TestPassword-12345', $user->fresh()->password));
    }

    public function test_規約を満たすパスワードへは変更できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'TestPassword-12345',
                'password' => self::VALID,
                'password_confirmation' => self::VALID,
            ])
            ->assertRedirect(route('dashboard'));

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check(self::VALID, $fresh->password));
        $this->assertFalse($fresh->must_change_password);
    }

    public function test_管理者が作るユーザーの初期パスワードにも同じ規約が適用される(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'login_id' => 'newstaff',
                'display_name' => '新任担当者',
                'role' => 'staff',
                'password' => 'onlylowercase123',
                'password_confirmation' => 'onlylowercase123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['login_id' => 'newstaff']);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'login_id' => 'newstaff',
                'display_name' => '新任担当者',
                'role' => 'staff',
                'password' => self::VALID,
                'password_confirmation' => self::VALID,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['login_id' => 'newstaff', 'must_change_password' => true]);
    }

    public function test_管理者によるパスワード再設定にも同じ規約が適用される(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $payload = [
            'display_name' => $target->display_name,
            'role' => $target->role,
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->put(route('users.update', $target), $payload + [
                'password' => 'nosymbolshere123A',
                'password_confirmation' => 'nosymbolshere123A',
            ])
            ->assertSessionHasErrors('password');

        $this->actingAs($admin)
            ->put(route('users.update', $target), $payload + [
                'password' => self::VALID,
                'password_confirmation' => self::VALID,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::VALID, $target->fresh()->password));
    }
}
