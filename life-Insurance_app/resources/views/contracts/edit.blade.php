@extends('layouts.app')

@section('title', '契約更新')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CONTRACT</p>
            <h1>契約更新 <span class="mono heading-code">{{ $customer->customer_code }}</span></h1>
            <p>契約時スナップショット（プラン名・金額）は変更できません。状態・証券番号・保障内容を更新します。</p>
        </div>
    </section>

    <dl class="definition-list definition-list--compact snapshot-box">
        <div class="definition-list__row"><dt>契約日</dt><dd>{{ $contract->contract_date->format('Y年n月j日') }}</dd></div>
        <div class="definition-list__row"><dt>保険会社</dt><dd>{{ $contract->insurer_name_snapshot ?: '-' }}</dd></div>
        <div class="definition-list__row"><dt>プラン</dt><dd>{{ $contract->plan_name_snapshot }}@if ($contract->plan_type_snapshot)（{{ $contract->plan_type_snapshot }}）@endif</dd></div>
        <div class="definition-list__row"><dt>契約時金額</dt><dd class="amount-large">{{ $contract->billing_cycle_label }} {{ $contract->formatted_premium }}</dd></div>
        @if ($contract->is_price_overridden)
            <div class="definition-list__row"><dt>上書き理由</dt><dd>{{ $contract->price_override_reason }}</dd></div>
        @endif
    </dl>

    <form method="POST" action="{{ route('customers.contracts.update', [$customer, $contract]) }}" class="form-card form-grid" data-single-submit>
        @csrf
        @method('PUT')

        <x-field name="status" label="契約状態" :required="true">
            <select id="status" name="status" required>
                @foreach (App\Models\InsuranceContract::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $contract->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field name="maturity_date" label="満期日">
            <input type="date" id="maturity_date" name="maturity_date" value="{{ old('maturity_date', $contract->maturity_date?->toDateString()) }}">
        </x-field>

        <x-field name="policy_number" label="証券番号">
            <input type="text" id="policy_number" name="policy_number" maxlength="50" value="{{ old('policy_number', $contract->policy_number) }}" autocomplete="off">
        </x-field>

        <x-field name="coverage" label="保障内容" full>
            <textarea id="coverage" name="coverage" maxlength="1000" rows="3">{{ old('coverage', $contract->coverage) }}</textarea>
        </x-field>

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('customers.show', ['customer' => $customer, 'tab' => 'contracts']) }}">キャンセル</a>
            <button type="submit" class="primary-button">変更を保存する</button>
        </div>
    </form>
@endsection
