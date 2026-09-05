@extends('layouts.app')

@section('title', 'ページが見つかりません')

@section('content')
    <section class="page-header">
        <div>
            <h1>ページが見つかりません</h1>
            <p>URLが変更されたか、削除された可能性があります。</p>
        </div>
    </section>

    <a class="secondary-button" href="{{ route('dashboard') }}">ダッシュボードへ戻る</a>
@endsection
