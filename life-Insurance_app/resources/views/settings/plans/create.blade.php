@extends('layouts.app')

@section('title', 'プラン登録')

@push('styles')
    <link rel="stylesheet" href="@appAsset('css/plan-settings.css')">
@endpush

@push('scripts')
    <script src="@appAsset('js/plan-settings.js')" defer></script>
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">PLAN SETTINGS</p>
            <h1>プラン登録</h1>
            <p>プランコードは自動発行されます。金額は円単位の整数で登録します。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('settings.plans.store') }}" class="form-card form-grid plan-quick-form plan-quick-form--page" data-plan-form data-single-submit>
        @csrf

        <x-category-choice />

        <x-field name="plan_name" label="プラン名" :required="true">
            <input type="text" id="plan_name" name="plan_name" maxlength="150" value="{{ old('plan_name') }}" required>
        </x-field>

        <x-money-input name="amount_yen" id="amount_yen" label="金額" :value="old('amount_yen')" />

        <x-field name="billing_cycle" label="支払単位" :required="true">
            <select id="billing_cycle" name="billing_cycle" required>
                @foreach (App\Models\InsurancePlan::BILLING_CYCLES as $value => $label)
                    <option value="{{ $value }}" @selected(old('billing_cycle', 'monthly') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field name="effective_from" label="適用開始日" :required="true">
            <input type="date" id="effective_from" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required>
        </x-field>

        <x-field name="effective_to" label="適用終了日" help="空欄の場合は継続します。">
            <input type="date" id="effective_to" name="effective_to" value="{{ old('effective_to') }}">
        </x-field>

        <x-field name="plan_type" label="プラン種類" help="医療、死亡、がん等。自由入力です。">
            <input type="text" id="plan_type" name="plan_type" maxlength="100" value="{{ old('plan_type') }}">
        </x-field>

        <x-field name="insurer_name" label="保険会社名">
            <input type="text" id="insurer_name" name="insurer_name" maxlength="150" value="{{ old('insurer_name') }}">
        </x-field>

        <x-field name="display_order" label="表示順">
            <input type="number" id="display_order" name="display_order" inputmode="numeric" min="0" max="9999" value="{{ old('display_order', 0) }}">
        </x-field>

        <x-field name="status" label="状態" :required="true">
            <select id="status" name="status" required>
                @foreach (['draft', 'active', 'inactive'] as $value)
                    <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ App\Models\InsurancePlan::STATUSES[$value] }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field name="notes" label="備考" full>
            <textarea id="notes" name="notes" maxlength="2000" rows="3">{{ old('notes') }}</textarea>
        </x-field>

        <p class="plan-preview form-field--full" aria-live="polite" data-plan-preview>
            金額を入力してください。
        </p>

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('settings.plans.index') }}">キャンセル</a>
            <button type="submit" class="primary-button">保存する</button>
        </div>
    </form>
@endsection
