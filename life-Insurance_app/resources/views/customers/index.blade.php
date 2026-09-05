@extends('layouts.app')

@section('title', '顧客一覧')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CUSTOMERS</p>
            <h1>顧客一覧</h1>
            <p>顧客コードで検索します。住所・電話番号・病歴は一覧に表示しません。</p>
        </div>

        <div class="page-header__actions">
            @can('export', App\Models\Customer::class)
                <a class="secondary-button" href="{{ route('customers.export') }}">CSV出力</a>
            @endcan
            @can('create', App\Models\Customer::class)
                <a class="primary-button" href="{{ route('customers.create') }}">顧客を登録</a>
            @endcan
        </div>
    </section>

    <form method="GET" action="{{ route('customers.index') }}" class="filter-bar" role="search">
        <div class="form-field">
            <label class="form-label" for="code">顧客コード</label>
            <input type="search" id="code" name="code" value="{{ $filters['code'] }}" maxlength="32" autocomplete="off" placeholder="C202609-">
        </div>

        <div class="form-field">
            <label class="form-label" for="status">顧客状態</label>
            <select id="status" name="status">
                <option value="">すべて</option>
                @foreach (App\Models\Customer::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="filter-bar__actions">
            <button type="submit" class="secondary-button">検索</button>
            <a class="text-button" href="{{ route('customers.index') }}">条件をクリア</a>
        </div>
    </form>

    <x-responsive-table caption="顧客一覧">
        <thead>
            <tr>
                <th scope="col">顧客コード</th>
                <th scope="col">氏名</th>
                <th scope="col">顧客状態</th>
                <th scope="col">担当者</th>
                <th scope="col">最終対応日</th>
                <th scope="col">意向確認</th>
                <th scope="col">契約状態</th>
                <th scope="col">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customers as $customer)
                <tr>
                    <td data-label="顧客コード"><span class="mono">{{ $customer->customer_code }}</span></td>
                    <td data-label="氏名">{{ $customer->name }}</td>
                    <td data-label="顧客状態">
                        @php $statusTone = ['active' => 'success', 'lead' => 'info'][$customer->status] ?? 'neutral'; @endphp
                        <x-status-badge :label="$customer->status_label" :tone="$statusTone" />
                    </td>
                    <td data-label="担当者">{{ $customer->assignedUser?->display_name ?? '未割当' }}</td>
                    <td data-label="最終対応日">{{ $customer->latestInteraction?->contacted_at?->format('Y/m/d') ?? '未対応' }}</td>
                    <td data-label="意向確認">
                        @php $intention = $customer->intention_status_label; @endphp
                        <x-status-badge :label="$intention" :tone="$intention === '確認済' ? 'success' : ($intention === '未完了' ? 'warning' : 'neutral')" />
                    </td>
                    <td data-label="契約状態">{{ $customer->contracts_count > 0 ? "契約 {$customer->contracts_count}件" : '契約なし' }}</td>
                    <td data-label="操作" class="table-actions">
                        <a class="table-link" href="{{ route('customers.show', $customer) }}">詳細</a>
                        @can('update', $customer)
                            <a class="table-link" href="{{ route('customers.edit', $customer) }}">編集</a>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty-cell">該当する顧客はありません。条件を変更するか、新しい顧客を登録してください。</td>
                </tr>
            @endforelse
        </tbody>
    </x-responsive-table>

    {{ $customers->links() }}
@endsection
