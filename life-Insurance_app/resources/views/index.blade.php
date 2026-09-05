@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">DASHBOARD</p>
            <h1>顧客管理ダッシュボード</h1>
            <p>
                {{ auth()->user()->display_name }}さん、
                本日の対応状況を確認できます。
            </p>
        </div>

        @can('create', App\Models\Customer::class)
            <a class="primary-button" href="{{ route('customers.create') }}">顧客を登録</a>
        @endcan
    </section>

    @if (! empty($securityNotices))
        <section class="notice-list" aria-label="セキュリティ通知">
            @foreach ($securityNotices as $notice)
                <p class="notice notice--warning">
                    <span class="notice__icon" aria-hidden="true">▲</span>
                    {{ $notice }}
                </p>
            @endforeach
        </section>
    @endif

    <section class="bento-grid" aria-label="主要指標">
        <article class="metric-card">
            <p class="metric-label">総顧客数</p>
            <p class="metric-value">{{ $customerCount ?? 0 }}</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">意向確認未完了</p>
            <p class="metric-value">{{ $pendingIntentionCount ?? 0 }}</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">今月の新規顧客</p>
            <p class="metric-value">{{ $monthlyCustomerCount ?? 0 }}</p>
        </article>

        <article class="metric-card">
            <p class="metric-label">更新予定契約（90日以内）</p>
            <p class="metric-value">{{ $renewalContractCount ?? 0 }}</p>
        </article>

        <article class="metric-card metric-card--wide">
            <div class="card-heading">
                <h2>最近の対応</h2>
                <a href="{{ route('customers.index') }}">
                    顧客一覧を見る
                </a>
            </div>

            @if (($recentInteractions ?? collect())->isEmpty())
                <p class="empty-state">
                    対応履歴が登録されると、ここに表示されます。
                </p>
            @else
                <ul class="timeline">
                    @foreach ($recentInteractions as $interaction)
                        <li class="timeline__item">
                            <time class="timeline__time" datetime="{{ $interaction->contacted_at->toIso8601String() }}">
                                {{ $interaction->contacted_at->format('n/j H:i') }}
                            </time>
                            <div class="timeline__body">
                                <a href="{{ route('customers.show', ['customer' => $interaction->customer, 'tab' => 'interactions']) }}">
                                    {{ $interaction->customer->customer_code }}
                                </a>
                                <span class="timeline__meta">{{ $interaction->channel_label }} / {{ $interaction->user->display_name }}</span>
                                <p class="timeline__text">{{ \Illuminate\Support\Str::limit($interaction->summary, 80) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="metric-card metric-card--wide">
            <div class="card-heading">
                <h2>自分の担当顧客</h2>
            </div>

            @if (($myCustomers ?? collect())->isEmpty())
                <p class="empty-state">担当顧客が割り当てられると、ここに表示されます。</p>
            @else
                <ul class="link-list">
                    @foreach ($myCustomers as $customer)
                        <li class="link-list__item">
                            <a href="{{ route('customers.show', $customer) }}">{{ $customer->customer_code }} {{ $customer->name }}</a>
                            <span class="link-list__meta">
                                最終対応: {{ $customer->latestInteraction?->contacted_at?->format('Y/m/d') ?? '未対応' }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>
@endsection
