@extends('layouts.app')

@section('title', 'ユーザー編集')

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
            <h1>{{ $user->display_name }} <span class="mono heading-code" id="login_id_value">{{ $user->login_id }}</span></h1>
            <p>パスワードを再設定すると、次回ログイン時に本人へ変更を求めます。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('users.update', $user) }}" class="form-card form-grid" data-single-submit autocomplete="off">
        @csrf
        @method('PUT')

        <x-field name="display_name" label="表示名" :required="true">
            <input type="text" id="display_name" name="display_name" maxlength="100" value="{{ old('display_name', $user->display_name) }}" required>
        </x-field>

        <x-field name="role" label="権限" :required="true">
            <select id="role" name="role" required>
                @foreach (App\Models\User::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field name="is_active" label="アカウント状態" :required="true" help="停止中のユーザーはログインできません。自分自身は停止できません。">
            <select id="is_active" name="is_active" required @if (auth()->id() === $user->id) aria-disabled="true" @endif>
                <option value="1" @selected((string) old('is_active', $user->is_active ? '1' : '0') === '1')>有効</option>
                <option value="0" @selected((string) old('is_active', $user->is_active ? '1' : '0') === '0')>停止</option>
            </select>
        </x-field>

        <x-password-fields
            label="パスワード再設定"
            confirm-label="パスワード再設定（再入力）"
            help="変更しない場合は空欄のままにします。12文字以上128文字以内で、大文字・小文字・数字・記号をすべて含めてください。"
            :copy-sources="[
                'ログインID' => 'login_id_value',
                '表示名' => 'display_name',
                'パスワード' => 'password',
            ]"
        />

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('users.index') }}">キャンセル</a>
            <button type="submit" class="primary-button">変更を保存する</button>
        </div>
    </form>
@endsection
