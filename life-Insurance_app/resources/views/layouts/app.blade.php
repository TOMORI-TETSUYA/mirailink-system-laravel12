<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- 検索エンジンへの登録を全面的に禁止します（HTTP ヘッダー X-Robots-Tag と同内容）。 --}}
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">

    <title>@yield('title') | MiraiLink</title>

    <link rel="icon" type="image/x-icon" href="@appAsset('images/favicon.ico')">

    <link rel="stylesheet" href="@appAsset('css/app.css')">
    <link rel="stylesheet" href="@appAsset('css/responsive.css')">

    @stack('styles')

    <script src="@appAsset('js/app.js')" defer></script>
    <script
        src="@appAsset('js/responsive-navigation.js')"
        defer
    ></script>
    <script src="@appAsset('js/modal.js')" defer></script>

    @stack('scripts')
</head>
<body class="app-body">
    <a class="skip-link" href="#main-content">
        メインコンテンツへ移動
    </a>

    <x-mobile-navigation :title="trim($__env->yieldContent('title'))" />

    <div class="app-shell">
        <aside
            id="primary-navigation"
            class="app-sidebar"
            aria-label="メインメニュー"
            data-sidebar
        >
            <div class="sidebar-brand">
                <span class="sidebar-brand__name">MiraiLink</span>
                <span class="sidebar-brand__sub">保険顧客管理</span>
            </div>

            <nav>
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>ダッシュボード</a>
                <a href="{{ route('customers.index') }}" @if (request()->routeIs('customers.*')) aria-current="page" @endif>顧客管理</a>

                @can('manage-settings')
                    <a href="{{ route('settings.plans.index') }}" @if (request()->routeIs('settings.plans.*')) aria-current="page" @endif>
                        プラン設定
                    </a>
                    <a href="{{ route('settings.retention.edit') }}" @if (request()->routeIs('settings.retention.*')) aria-current="page" @endif>
                        保存期間設定
                    </a>
                @endcan

                @can('manage-users')
                    <a href="{{ route('users.index') }}" @if (request()->routeIs('users.*')) aria-current="page" @endif>ユーザー管理</a>
                @endcan

                @can('view-audit-logs')
                    <a href="{{ route('audit-logs.index') }}" @if (request()->routeIs('audit-logs.*')) aria-current="page" @endif>監査ログ</a>
                @endcan
            </nav>

            @auth
                <div class="sidebar-user">
                    <p class="sidebar-user__name">{{ auth()->user()->display_name }}</p>
                    <p class="sidebar-user__role">{{ auth()->user()->role_label }}</p>
                    <a class="sidebar-user__link" href="{{ route('password.edit') }}">パスワード変更</a>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="logout-button">
                        ログアウト
                    </button>
                </form>
            @endauth
        </aside>

        <button
            type="button"
            class="sidebar-overlay"
            aria-label="メニューを閉じる"
            data-sidebar-overlay
            hidden
        ></button>

        <main id="main-content" class="app-main" tabindex="-1">
            <div class="content-container">
                <x-flash />

                @yield('content')
            </div>
        </main>
    </div>

    <x-confirm-modal />
</body>
</html>
