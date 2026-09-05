@extends('layouts.app')

@section('title', '保存期間設定')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">RETENTION</p>
            <h1>顧客情報の保存期間</h1>
            <p>法令、保険会社との契約、苦情・紛争対応、社内規程を確認したうえで設定します。根拠確認前は未設定のままにできます。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('settings.retention.update') }}" class="form-card form-grid" data-single-submit>
        @csrf
        @method('PUT')

        <x-field name="customer_retention_years" label="保存期間（年）" help="空欄の場合は未設定です。保存期限を過ぎた情報は削除または匿名化の対象になります。">
            <input type="number" id="customer_retention_years" name="customer_retention_years" inputmode="numeric" min="1" max="100" value="{{ old('customer_retention_years', $retentionYears) }}">
        </x-field>

        <div class="form-actions mobile-sticky-actions">
            <button type="submit" class="primary-button">設定を保存する</button>
        </div>
    </form>
@endsection
