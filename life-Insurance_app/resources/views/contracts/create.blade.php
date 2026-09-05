@extends('layouts.app')

@section('title', '契約登録')

@push('scripts')
    <script src="@appAsset('js/contract-plan-selector.js')" defer></script>
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CONTRACT</p>
            <h1>契約登録 <span class="mono heading-code">{{ $customer->customer_code }}</span></h1>
            <p>{{ $customer->name }} さんの契約。プランを選ぶと契約日時点の金額を自動入力します。</p>
        </div>
    </section>

    {{-- 契約日とキーワードで選択肢を絞り込みます。JavaScript無効時もこのフォームで再読み込みできます。 --}}
    <form method="GET" action="{{ route('customers.contracts.create', $customer) }}" class="filter-bar" data-plan-filter>
        <div class="form-field">
            <label class="form-label" for="filter_contract_date">契約日（金額の判定基準）</label>
            <input type="date" id="filter_contract_date" name="contract_date" value="{{ $contractDate->toDateString() }}" data-filter-contract-date>
        </div>
        <div class="form-field">
            <label class="form-label" for="plan_keyword">保険会社名・プラン名で検索</label>
            <input type="search" id="plan_keyword" name="plan_keyword" value="{{ $planKeyword }}" maxlength="150" autocomplete="off">
        </div>
        <div class="filter-bar__actions">
            <button type="submit" class="secondary-button">プランを再検索</button>
        </div>
    </form>

    <form method="POST" action="{{ route('customers.contracts.store', $customer) }}" class="form-card contract-grid" data-contract-form data-single-submit>
        @csrf

        <x-field name="contract_date" label="契約日" :required="true" help="契約日を変更した場合は「プランを再検索」で金額を再判定してください。">
            <input type="date" id="contract_date" name="contract_date" value="{{ old('contract_date', $contractDate->toDateString()) }}" required data-contract-date>
        </x-field>

        <x-field name="status" label="契約状態" :required="true">
            <select id="status" name="status" required>
                @foreach (App\Models\InsuranceContract::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', 'applied') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <div class="form-field--full">
            @if ($plans->isEmpty())
                <p class="notice notice--warning">
                    <span class="notice__icon" aria-hidden="true">▲</span>
                    選択できる有効プランがありません。管理者がプラン設定画面で登録・有効化してください。
                </p>
            @endif
            <x-plan-selector :plans="$plans" />
        </div>

        @if ($canOverride)
            <details class="override-panel form-field--full" data-override-panel @if (old('override_price')) open @endif>
                <summary class="override-panel__summary">管理者による契約時金額の上書き</summary>
                <div class="form-grid">
                    <div class="form-field form-field--full">
                        <label class="checkbox-label" for="override_price">
                            <input type="checkbox" id="override_price" name="override_price" value="1" @checked(old('override_price')) data-override-toggle>
                            <span>自動入力された金額をこの契約だけ上書きする（プランマスタの金額は変更しません）</span>
                        </label>
                    </div>
                    <x-money-input name="override_amount_yen" id="override_amount_yen" label="上書き後金額" :value="old('override_amount_yen')" :required="false" />
                    <x-field name="price_override_reason" label="上書き理由" help="上書き時は必須です。前後の金額と理由が監査ログへ記録されます。">
                        <input type="text" id="price_override_reason" name="price_override_reason" maxlength="500" value="{{ old('price_override_reason') }}">
                    </x-field>
                </div>
            </details>
        @endif

        <x-field name="policy_number" label="証券番号">
            <input type="text" id="policy_number" name="policy_number" maxlength="50" value="{{ old('policy_number') }}" autocomplete="off">
        </x-field>

        <x-field name="maturity_date" label="満期日">
            <input type="date" id="maturity_date" name="maturity_date" value="{{ old('maturity_date') }}">
        </x-field>

        <x-field name="coverage" label="保障内容" full>
            <textarea id="coverage" name="coverage" maxlength="1000" rows="3">{{ old('coverage') }}</textarea>
        </x-field>

        <div class="contract-summary" aria-live="polite" data-contract-summary>
            <p class="contract-summary__title">保存前の確認</p>
            <p class="contract-summary__text" data-contract-summary-text>プランと契約日を選択すると、適用金額をここに表示します。</p>
        </div>

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('customers.show', ['customer' => $customer, 'tab' => 'contracts']) }}">キャンセル</a>
            <button type="submit" class="primary-button" @if ($plans->isEmpty()) disabled @endif>契約を登録する</button>
        </div>
    </form>
@endsection
