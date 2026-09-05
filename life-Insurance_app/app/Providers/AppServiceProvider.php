<?php

namespace App\Providers;

use App\Models\User;
use App\View\Composers\DashboardComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('manage-settings', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('view-audit-logs', fn (User $user): bool => $user->isAdmin() || $user->isAuditor());

        // パスワード規約（仕様 6.3）: 12文字以上、最大128文字は FormRequest 側の max:128 で制限。
        // 加えて大文字・小文字・数字・記号をすべて必須にします。
        // ここを変えると、管理者によるパスワード再設定・本人のパスワード変更・
        // app:create-admin のすべてに同じ規約が適用されます。
        Password::defaults(
            fn () => Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
        );

        // CSS / JS を更新日時付き URL で読み込み、ブラウザに古い内容が残らないようにします。
        // 使い方: <link rel="stylesheet" href="@appAsset('css/app.css')">
        Blade::directive(
            'appAsset',
            static fn (string $expression): string => "<?php echo e(\App\Support\Asset::url({$expression})); ?>"
        );

        // CSP（style-src 'self'）に合わせ、インラインスタイルを含まない自前のページ送りビューを使用します。
        Paginator::defaultView('vendor.pagination.mirailink');
        Paginator::defaultSimpleView('vendor.pagination.mirailink');

        // Route::view('/', 'index') のダッシュボードへ集計値を供給します。
        View::composer('index', DashboardComposer::class);
    }
}
