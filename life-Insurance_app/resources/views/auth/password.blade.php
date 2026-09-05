@extends('layouts.app')

@section('title', 'パスワード変更')

@push('styles')
    <link rel="stylesheet" href="@appAsset('css/credentials.css')">
@endpush

@push('scripts')
    <script src="@appAsset('js/password-tools.js')" defer></script>
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">ACCOUNT</p>
            <h1>パスワード変更</h1>
            @if (auth()->user()->must_change_password)
                <p>初期パスワードを変更してから利用を開始してください。</p>
            @else
                <p>12文字以上128文字以内、大文字・小文字・数字・記号をすべて含めて設定します。</p>
            @endif
        </div>
    </section>

    <form method="POST" action="{{ route('password.update') }}" class="form-card form-grid" data-single-submit>
        @csrf
        @method('PUT')

        <div class="credential-summary form-field--full">
            <p class="credential-summary__title">アカウント情報</p>
            <div class="credential-summary__row">
                <span class="credential-summary__label">ログインID</span>
                <span class="credential-summary__value mono" id="account_login_id">{{ auth()->user()->login_id }}</span>
            </div>
            <div class="credential-summary__row">
                <span class="credential-summary__label">表示名</span>
                <span class="credential-summary__value" id="account_display_name">{{ auth()->user()->display_name }}</span>
            </div>
        </div>

        <x-field name="current_password" label="現在のパスワード" :required="true" full>
            <div class="credential-control">
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" required maxlength="128">
                <button
                    type="button"
                    class="credential-button"
                    data-credential-visibility="current_password"
                    aria-pressed="false"
                    aria-label="パスワードを表示する"
                >
                    表示
                </button>
            </div>
        </x-field>

        <x-password-fields
            label="新しいパスワード"
            confirm-label="新しいパスワード（再入力）"
            :required="true"
            help="12文字以上128文字以内。大文字・小文字・数字・記号をすべて含めてください。"
            :copy-sources="[
                'ログインID' => 'account_login_id',
                '表示名' => 'account_display_name',
                'パスワード' => 'password',
            ]"
        />

        <div class="form-actions mobile-sticky-actions">
            <button type="submit" class="primary-button">パスワードを変更する</button>
        </div>
    </form>
@endsection
