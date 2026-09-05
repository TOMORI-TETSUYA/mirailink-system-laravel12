@extends('layouts.app')

@section('title', '監査ログ')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">AUDIT LOGS</p>
            <h1>監査ログ</h1>
            <p>誰が、いつ、どの情報を閲覧・変更したかの記録です。値そのものは保持しません。</p>
        </div>
    </section>

    <form method="GET" action="{{ route('audit-logs.index') }}" class="filter-bar" role="search">
        <div class="form-field">
            <label class="form-label" for="action">操作種別（前方一致）</label>
            <input type="search" id="action" name="action" value="{{ $filters['action'] }}" maxlength="100" placeholder="customer." autocomplete="off">
        </div>
        <div class="form-field">
            <label class="form-label" for="user_id">操作者ID</label>
            <input type="number" id="user_id" name="user_id" inputmode="numeric" min="1" value="{{ $filters['user_id'] }}">
        </div>
        <div class="filter-bar__actions">
            <button type="submit" class="secondary-button">絞り込む</button>
            <a class="text-button" href="{{ route('audit-logs.index') }}">条件をクリア</a>
        </div>
    </form>

    <p class="muted">表形式で確認するため、スマートフォンでは横スクロールします。</p>

    <x-responsive-table caption="監査ログ" :cards="false">
        <thead>
            <tr>
                <th scope="col">日時</th>
                <th scope="col">操作者</th>
                <th scope="col">操作種別</th>
                <th scope="col">対象</th>
                <th scope="col">対象ID</th>
                <th scope="col">変更項目</th>
                <th scope="col">結果</th>
                <th scope="col">IPアドレス</th>
                <th scope="col">リクエストID</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y/m/d H:i:s') }}</td>
                    <td>{{ $log->user?->display_name ?? '-' }}<span class="muted"> (#{{ $log->user_id ?? '-' }})</span></td>
                    <td class="mono">{{ $log->action }}</td>
                    <td>{{ $log->target_label ?: '-' }}</td>
                    <td>{{ $log->target_id ?? '-' }}</td>
                    <td>{{ implode(', ', $log->changed_fields ?? []) ?: '-' }}</td>
                    <td>
                        @if ($log->succeeded)
                            <x-status-badge label="成功" tone="success" />
                        @else
                            <x-status-badge label="失敗" tone="danger" />
                        @endif
                    </td>
                    <td class="mono">{{ $log->ip_address ?? '-' }}</td>
                    <td class="mono">{{ $log->request_id }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-cell">該当する監査ログはありません。</td></tr>
            @endforelse
        </tbody>
    </x-responsive-table>

    {{ $logs->links() }}
@endsection
