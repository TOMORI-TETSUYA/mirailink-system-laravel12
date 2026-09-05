@extends('layouts.app')

@section('title', '顧客登録')

@push('styles')
    <link rel="stylesheet" href="@appAsset('css/address.css')">
@endpush

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CUSTOMERS</p>
            <h1>顧客登録</h1>
            <p>顧客コードは保存時に自動発行されます。個人情報は暗号化して保存されます。</p>
        </div>
    </section>

    <form method="POST" action="{{ route('customers.store') }}" class="form-card form-grid" data-single-submit>
        @csrf

        @include('customers._form', ['customer' => null, 'canViewHealth' => true])

        <div class="form-actions mobile-sticky-actions">
            <a class="secondary-button" href="{{ route('customers.index') }}">キャンセル</a>
            <button type="submit" class="primary-button">顧客を登録する</button>
        </div>
    </form>
@endsection
