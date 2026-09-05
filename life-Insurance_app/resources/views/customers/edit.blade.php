@extends('layouts.app')

@section('title', '顧客更新')

@push('styles')
    <link rel="stylesheet" href="@appAsset('css/address.css')">
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CUSTOMERS</p>
            <h1>顧客更新 <span class="mono heading-code">{{ $customer->customer_code }}</span></h1>
            <p>変更内容は監査ログに記録されます（変更項目名のみ。値は記録しません）。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="form-card form-grid" data-single-submit>
        @csrf
        @method('PUT')

        @include('customers._form', ['customer' => $customer, 'canViewHealth' => $canViewHealth])

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('customers.show', $customer) }}">キャンセル</a>
            <button type="submit" class="primary-button">変更を保存する</button>
        </div>
    </form>

    @can('delete', $customer)
        <section class="danger-zone">
            <h2>顧客の削除</h2>
            <p>論理削除です。データは保存期間の規程に従って管理され、物理削除は管理者と監査担当者の承認対象です。</p>
            <form method="POST" action="{{ route('customers.destroy', $customer) }}" data-confirm data-confirm-message="顧客 {{ $customer->customer_code }} を削除します。一覧には表示されなくなります。よろしいですか。" data-confirm-label="削除する">
                @csrf
                @method('DELETE')
                <button type="submit" class="secondary-button secondary-button--danger">この顧客を削除する</button>
            </form>
        </section>
    @endcan
@endsection
