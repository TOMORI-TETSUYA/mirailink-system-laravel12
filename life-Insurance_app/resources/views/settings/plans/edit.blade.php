@extends('layouts.app')

@section('title', 'プラン編集')

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
            <h1>{{ $plan->plan_name }} <span class="mono heading-code">{{ $plan->plan_code }}</span></h1>
            <p>
                現在金額:
                @if ($plan->currentPrice)
                    <strong>{{ $plan->billing_cycle_label }} {{ $plan->currentPrice->formatted_amount }}</strong>
                    （{{ $plan->currentPrice->effective_from->format('Y年n月j日') }}〜）
                @else
                    <strong>未設定</strong>
                @endif
            </p>
        </div>

        <button type="button" class="primary-button" data-modal-open="price-change-modal">金額を変更</button>
    </section>

    <section class="settings-grid settings-grid--edit">
        <form method="POST" action="{{ route('settings.plans.update', $plan) }}" class="form-card form-grid" data-single-submit>
            @csrf
            @method('PUT')

            <h2 class="form-card__title form-field--full">基本情報</h2>

            @include('settings.plans._form', ['plan' => $plan])

            <div class="form-actions mobile-sticky-actions">
                <a class="secondary-button" href="{{ route('settings.plans.index') }}">一覧へ戻る</a>
                <button type="submit" class="primary-button">変更を保存する</button>
            </div>
        </form>

        <div class="form-card">
            <h2 class="form-card__title">価格履歴</h2>
            @include('settings.plans._price-history', ['plan' => $plan])
        </div>
    </section>

    @can('delete', $plan)
        <section class="danger-zone">
            <h2>プランの削除</h2>
            <p>論理削除です。既存契約は契約時スナップショットを保持するため影響を受けません。</p>
            <form method="POST" action="{{ route('settings.plans.destroy', $plan) }}" data-confirm data-confirm-message="「{{ $plan->plan_name }}」を削除します。契約画面で選択できなくなります。よろしいですか。" data-confirm-label="削除する">
                @csrf
                @method('DELETE')
                <button type="submit" class="secondary-button secondary-button--danger">このプランを削除する</button>
            </form>
        </section>
    @endcan

    <x-modal id="price-change-modal" title="金額を変更">
        <p class="muted">終了日のない現行金額は、新しい適用開始日の前日で自動的に終了します。過去の契約金額は変わりません。</p>
        <form method="POST" action="{{ route('settings.plans.prices.store', $plan) }}" class="form-grid" id="price-change-form" data-plan-form data-single-submit>
            @csrf

            <x-money-input name="amount_yen" id="price_amount_yen" label="新しい金額" :value="old('amount_yen')" />

            <input type="hidden" name="billing_cycle" value="{{ $plan->billing_cycle }}">

            <x-field name="effective_from" id="price_effective_from" label="適用開始日" :required="true">
                <input type="date" id="price_effective_from" name="effective_from" value="{{ old('effective_from', now()->toDateString()) }}" required>
            </x-field>

            <x-field name="effective_to" id="price_effective_to" label="適用終了日" help="空欄の場合は継続します。">
                <input type="date" id="price_effective_to" name="effective_to" value="{{ old('effective_to') }}">
            </x-field>

            <p class="plan-preview form-field--full" aria-live="polite" data-plan-preview>
                金額を入力してください。
            </p>
        </form>
        <x-slot:footer>
            <button type="button" class="secondary-button" data-modal-close>キャンセル</button>
            <button type="submit" class="primary-button" form="price-change-form">金額を変更する</button>
        </x-slot:footer>
    </x-modal>
@endsection
