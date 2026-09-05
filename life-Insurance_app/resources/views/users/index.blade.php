@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">USERS</p>
            <h1>ユーザー管理</h1>
            <p>ユーザーは管理者だけが作成できます。停止したユーザーはログインできません。</p>
        </div>

        <a class="primary-button" href="{{ route('users.create') }}">ユーザーを追加</a>
    </section>

    <x-responsive-table caption="ユーザー一覧">
        <thead>
            <tr>
                <th scope="col">ログインID</th>
                <th scope="col">表示名</th>
                <th scope="col">権限</th>
                <th scope="col">状態</th>
                <th scope="col">最終ログイン</th>
                <th scope="col">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td data-label="ログインID"><span class="mono">{{ $user->login_id }}</span></td>
                    <td data-label="表示名">{{ $user->display_name }}</td>
                    <td data-label="権限">{{ $user->role_label }}</td>
                    <td data-label="状態">
                        @if ($user->is_active)
                            <x-status-badge label="有効" tone="success" />
                        @else
                            <x-status-badge label="停止中" tone="danger" />
                        @endif
                        @if ($user->must_change_password)
                            <x-status-badge label="初期パスワード" tone="warning" />
                        @endif
                    </td>
                    <td data-label="最終ログイン">{{ $user->last_login_at?->format('Y/m/d H:i') ?? '-' }}</td>
                    <td data-label="操作" class="table-actions">
                        <a class="table-link" href="{{ route('users.edit', $user) }}">編集</a>
                        @can('deactivate', $user)
                            @if ($user->is_active)
                                <form method="POST" action="{{ route('users.destroy', $user) }}" data-confirm data-confirm-message="{{ $user->display_name }}（{{ $user->login_id }}）を停止します。以後ログインできなくなります。" data-confirm-label="停止する">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="table-link table-link--muted">停止</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">ユーザーがいません。</td></tr>
            @endforelse
        </tbody>
    </x-responsive-table>

    {{ $users->links() }}
@endsection
