<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_正しいIDとパスワードでログインできる(): void
    {
        $user = User::factory()->create(['login_id' => 'staff01']);

        $response = $this->post(route('login.store'), [
            'login_id' => 'staff01',
            'password' => 'TestPassword-12345',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_誤った情報では共通エラーになる(): void
    {
        User::factory()->create(['login_id' => 'staff01']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'login_id' => 'staff01',
            'password' => 'WrongPassword-12345',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['login_id' => 'ログインIDまたはパスワードを確認してください。']);
        $this->assertGuest();
        $this->assertSame(1, AuditLog::query()->where('action', 'auth.login_failed')->count());
    }

    public function test_無効ユーザーはログインできない(): void
    {
        User::factory()->inactive()->create(['login_id' => 'staff01']);

        $this->post(route('login.store'), [
            'login_id' => 'staff01',
            'password' => 'TestPassword-12345',
        ]);

        $this->assertGuest();
    }

    public function test_ログイン成功時にセッションIDが変わる(): void
    {
        User::factory()->create(['login_id' => 'staff01']);

        $this->get(route('login'));
        $before = session()->getId();

        $this->post(route('login.store'), [
            'login_id' => 'staff01',
            'password' => 'TestPassword-12345',
        ]);

        $this->assertNotSame($before, session()->getId());
    }

    public function test_レート制限が動作する(): void
    {
        User::factory()->create(['login_id' => 'staff01']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'login_id' => 'staff01',
                'password' => 'WrongPassword-12345',
            ]);
        }

        $this->post(route('login.store'), [
            'login_id' => 'staff01',
            'password' => 'TestPassword-12345',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_failed']);
    }

    public function test_初期パスワードのままではダッシュボードへ入れない(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('password.edit'));
    }

    public function test_ログアウト後は認証済み画面へ戻れない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
