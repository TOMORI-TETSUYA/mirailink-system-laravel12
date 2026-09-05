@extends('layouts.app')

@section('title', 'アクセス権限がありません')

@section('content')
    <section class="page-header">
        <div>
            <h1>この画面を表示する権限がありません</h1>
            <p>必要な権限がある場合は、管理者へお問い合わせください。</p>
        </div>
    </section>

    <a class="secondary-button" href="{{ route('dashboard') }}">ダッシュボードへ戻る</a>
@endsection
