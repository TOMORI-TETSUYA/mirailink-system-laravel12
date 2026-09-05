@extends('layouts.app')

@section('title', 'プラン設定')

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
            <h1>プラン設定</h1>
            <p>プラン名と金額を登録し、契約入力で利用します。</p>
        </div>

        <button
            type="button"
            class="primary-button"
            data-plan-form-open
            aria-expanded="{{ $errors->any() && ! $errors->has('status') ? 'true' : 'false' }}"
            aria-controls="plan-quick-form"
        >
            プランを追加
        </button>
    </section>

    <section class="settings-grid">
        <form
            method="POST"
            action="{{ route('settings.plans.store') }}"
            class="plan-quick-form"
            id="plan-quick-form"
            data-plan-form
            data-single-submit
            @unless ($errors->any() && ! $errors->has('status')) hidden @endunless
        >
            @csrf

            <h2 class="plan-quick-form__title">プランを追加</h2>

            <x-category-choice />

            <x-field name="plan_name" label="プラン名" :required="true">
                <input
                    type="text"
                    id="plan_name"
                    name="plan_name"
                    maxlength="150"
                    value="{{ old('plan_name') }}"
                    required
                >
            </x-field>

            <x-money-input name="amount_yen" id="amount_yen" label="金額" :value="old('amount_yen')" />

            <x-field name="billing_cycle" label="支払単位" :required="true">
                <select id="billing_cycle" name="billing_cycle" required>
                    @foreach (App\Models\InsurancePlan::BILLING_CYCLES as $value => $label)
                        <option value="{{ $value }}" @selected(old('billing_cycle', 'monthly') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>

            <button type="button" class="text-button" data-plan-details-toggle aria-expanded="{{ old('plan_type') || old('insurer_name') || old('notes') ? 'true' : 'false' }}" aria-controls="plan-details">
                詳細項目を表示
            </button>

            <div class="plan-details" id="plan-details" data-plan-details @unless (old('plan_type') || old('insurer_name') || old('notes')) hidden @endunless>
                <x-field name="plan_type" label="プラン種類" help="医療、死亡、がん等。自由入力です。">
                    <input type="text" id="plan_type" name="plan_type" maxlength="100" value="{{ old('plan_type') }}">
                </x-field>

                <x-field name="insurer_name" label="保険会社名">
                    <input type="text" id="insurer_name" name="insurer_name" maxlength="150" value="{{ old('insurer_name') }}">
                </x-field>

                <x-field name="effective_from" label="適用開始日" :required="true">
                    <input
                        type="date"
                        id="effective_from"
                        name="effective_from"
                        value="{{ old('effective_from', now()->toDateString()) }}"
                        required
                    >
                </x-field>

                <x-field name="effective_to" label="適用終了日" help="空欄の場合は継続します。">
                    <input type="date" id="effective_to" name="effective_to" value="{{ old('effective_to') }}">
                </x-field>

                <x-field name="display_order" label="表示順">
                    <input type="number" id="display_order" name="display_order" inputmode="numeric" min="0" max="9999" value="{{ old('display_order', 0) }}">
                </x-field>

                <x-field name="status" id="plan_status" label="状態" :required="true">
                    <select id="plan_status" name="status" required>
                        @foreach (['draft', 'active', 'inactive'] as $value)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ App\Models\InsurancePlan::STATUSES[$value] }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field name="notes" label="備考" help="社内向け説明。">
                    <textarea id="notes" name="notes" maxlength="2000" rows="2">{{ old('notes') }}</textarea>
                </x-field>
            </div>

            <p class="plan-preview" aria-live="polite" data-plan-preview>
                金額を入力してください。
            </p>

            <button type="submit" class="primary-button">
                保存する
            </button>
        </form>

        <div class="plan-list">
            <form method="GET" action="{{ route('settings.plans.index') }}" class="filter-bar filter-bar--compact" role="search">
                <div class="form-field">
                    <label class="form-label" for="q">プラン名・保険会社名</label>
                    <input type="search" id="q" name="q" value="{{ $filters['q'] }}" maxlength="150" autocomplete="off">
                </div>
                <div class="form-field">
                    <label class="form-label" for="category">保険分類</label>
                    <select id="category" name="category">
                        <option value="">すべて</option>
                        @foreach (App\Models\InsurancePlan::CATEGORIES as $value => $label)
                            <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field form-field--checkbox">
                    <label class="checkbox-label" for="active_only">
                        <input type="checkbox" id="active_only" name="active_only" value="1" @checked($filters['active_only'])>
                        <span>有効中のみ表示</span>
                    </label>
                </div>
                <div class="filter-bar__actions">
                    <button type="submit" class="secondary-button">絞り込む</button>
                </div>
            </form>

            @error('status')
                <p class="notice notice--error" role="alert">
                    <span class="notice__icon" aria-hidden="true">■</span>
                    {{ $message }}
                </p>
            @enderror

            <dl class="category-legend">
                <p class="category-legend__title">保険分類</p>
                @foreach (App\Models\InsurancePlan::CATEGORIES as $value => $label)
                    <div class="category-legend__item">
                        <dt class="category-legend__term">{{ $label }}</dt>
                        <dd class="category-legend__description">
                            {{ App\Models\InsurancePlan::CATEGORY_DESCRIPTIONS[$value] }}
                        </dd>
                    </div>
                @endforeach
            </dl>

            <x-responsive-table caption="プラン一覧">
                <thead>
                    <tr>
                        <th scope="col">プランコード</th>
                        <th scope="col">保険分類</th>
                        <th scope="col">プラン名</th>
                        <th scope="col">種類</th>
                        <th scope="col">現在金額</th>
                        <th scope="col">支払単位</th>
                        <th scope="col">状態</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        <tr>
                            <td data-label="プランコード" class="is-nowrap"><span class="mono">{{ $plan->plan_code }}</span></td>
                            <td data-label="保険分類" class="is-nowrap" title="{{ $plan->category_description }}">{{ $plan->category_label }}</td>
                            <td data-label="プラン名" class="plan-name-cell">
                                {{ $plan->plan_name }}
                                @if ($plan->insurer_name)
                                    <span class="muted">{{ $plan->insurer_name }}</span>
                                @endif
                            </td>
                            <td data-label="種類" class="is-nowrap">{{ $plan->plan_type ?: '未設定' }}</td>
                            <td data-label="現在金額" class="is-nowrap">
                                @if ($plan->currentPrice)
                                    {{ number_format($plan->currentPrice->amount_yen) }}円
                                @else
                                    未設定
                                @endif
                            </td>
                            <td data-label="支払単位" class="is-nowrap">{{ $plan->billing_cycle_label }}</td>
                            <td data-label="状態" class="is-nowrap">
                                @php $planTone = ['active' => 'success', 'draft' => 'info', 'inactive' => 'neutral'][$plan->status] ?? 'danger'; @endphp
                                <x-status-badge :label="$plan->status_label" :tone="$planTone" />
                            </td>
                            <td data-label="操作" class="table-actions">
                                <a class="table-link" href="{{ route('settings.plans.edit', $plan) }}">
                                    編集
                                </a>
                                <button type="button" class="table-link" data-modal-open="price-history-{{ $plan->id }}">価格履歴</button>

                                @if ($plan->status === 'active')
                                    <form method="POST" action="{{ route('settings.plans.status.update', $plan) }}" data-confirm data-confirm-message="「{{ $plan->plan_name }}」を無効にします。契約画面で選択できなくなりますが、既存契約には影響しません。" data-confirm-label="無効にする">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="inactive">
                                        <button type="submit" class="table-link table-link--muted">無効化</button>
                                    </form>
                                @elseif ($plan->status !== 'deleted')
                                    <form method="POST" action="{{ route('settings.plans.status.update', $plan) }}" data-confirm data-confirm-message="「{{ $plan->plan_name }}」を有効にします。契約画面で選択できるようになります。" data-confirm-label="有効にする">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="table-link">有効化</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="empty-cell">プランはまだ登録されていません。「プランを追加」から登録してください。</td>
                        </tr>
                    @endforelse
                </tbody>
            </x-responsive-table>
        </div>
    </section>

    @foreach ($plans as $plan)
        <x-modal id="price-history-{{ $plan->id }}" :title="'価格履歴: '.$plan->plan_name">
            @include('settings.plans._price-history', ['plan' => $plan])
            <x-slot:footer>
                <a class="secondary-button" href="{{ route('settings.plans.edit', $plan) }}">金額を変更する</a>
                <button type="button" class="primary-button" data-modal-close>閉じる</button>
            </x-slot:footer>
        </x-modal>
    @endforeach
@endsection
