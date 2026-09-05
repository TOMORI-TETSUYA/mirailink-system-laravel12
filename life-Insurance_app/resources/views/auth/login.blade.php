<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- 検索エンジンへの登録を全面的に禁止します（HTTP ヘッダー X-Robots-Tag と同内容）。 --}}
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <meta name="googlebot" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
    <title>ログイン | MiraiLink</title>

    <link rel="icon" type="image/x-icon" href="@appAsset('images/favicon.ico')">

    <link rel="stylesheet" href="@appAsset('css/app.css')">
    <link rel="stylesheet" href="@appAsset('css/responsive.css')">
    <script src="@appAsset('js/app.js')" defer></script>
</head>
<body class="login-page">
    <main class="login-shell">
        <section
            class="login-card"
            aria-labelledby="login-title"
            data-login-card
        >
            <p class="login-eyebrow">SECURE CUSTOMER MANAGEMENT</p>

            <h1 id="login-title" class="login-title">
                MiraiLink
            </h1>

            <p class="login-description">
                保険顧客管理システム。許可された社内担当者のみ利用できます。
            </p>

            <form
                method="POST"
                action="{{ route('login.store') }}"
                class="login-form"
                data-login-form
            >
                @csrf

                <label class="form-field">
                    <span class="form-label">ログインID</span>
                    <input
                        type="text"
                        name="login_id"
                        value="{{ old('login_id') }}"
                        minlength="4"
                        maxlength="64"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        autofocus
                    >
                </label>

                <label class="form-field">
                    <span class="form-label">パスワード</span>
                    <span class="password-control">
                        <input
                            type="password"
                            name="password"
                            minlength="12"
                            maxlength="128"
                            autocomplete="current-password"
                            required
                            data-password-input
                        >
                        <button
                            type="button"
                            class="password-toggle"
                            aria-label="パスワードを表示"
                            aria-pressed="false"
                            data-password-toggle
                        >
                            表示
                        </button>
                    </span>
                </label>

                @if ($errors->any())
                    <p class="form-error" role="alert">
                        ログインIDまたはパスワードを確認してください。
                    </p>
                @endif

                <button type="submit" class="primary-button" data-submit-button>
                    ログイン
                </button>
            </form>

            <p class="security-note">
                パスワードや顧客情報を第三者と共有しないでください。
            </p>
        </section>
    </main>
</body>
</html>
