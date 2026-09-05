@extends('layouts.app')

@section('title', 'ユーザー追加')

@push('styles')
    <link rel="stylesheet" href="@appAsset('css/credentials.css')">
@endpush

@push('scripts')
    <script src="@appAsset('js/password-tools.js')" defer></script>
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">USERS</p>
            <h1>ユーザー追加</h1>
            <p>初期パスワードは初回ログイン時に本人が変更します。メールでの平文送信は行わず、安全な方法で伝えてください。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('users.store') }}" class="form-card form-grid" data-single-submit autocomplete="off">
        @csrf

        <x-field name="login_id" label="ログインID" :required="true" help="4〜64文字。半角英小文字、数字、. _ - が使用できます。">
            <input type="text" id="login_id" name="login_id" minlength="4" maxlength="64" value="{{ old('login_id') }}" autocapitalize="none" spellcheck="false" autocomplete="off" required>
        </x-field>

        <x-field name="display_name" label="表示名" :required="true">
            <input type="text" id="display_name" name="display_name" maxlength="100" value="{{ old('display_name') }}" required>
        </x-field>

        <x-field name="role" label="権限" :required="true">
            <select id="role" name="role" required>
                @foreach (App\Models\User::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', 'staff') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-password-fields
            label="初期パスワード"
            confirm-label="初期パスワード（再入力）"
            :required="true"
            help="12文字以上128文字以内。大文字・小文字・数字・記号をすべて含めてください。"
            :copy-sources="[
                'ログインID' => 'login_id',
                '表示名' => 'display_name',
                'パスワード' => 'password',
            ]"
        />

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('users.index') }}">キャンセル</a>
            <button type="submit" class="primary-button">ユーザーを作成する</button>
        </div>
    </form>
@endsection
