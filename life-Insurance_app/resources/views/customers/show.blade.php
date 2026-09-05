@extends('layouts.app')

@section('title', '顧客詳細')

@php
    $tabs = [
        'basic' => '基本情報',
        'intention' => '顧客意向',
        'contracts' => '契約情報',
        'interactions' => '対応履歴',
        'maintenance' => '保全・給付履歴',
        'audit' => '操作履歴',
    ];

    if ($canViewHealth) {
        $tabs = array_slice($tabs, 0, 1, true) + ['health' => '健康情報'] + array_slice($tabs, 1, null, true);
    }

    if (! array_key_exists($tab, $tabs)) {
        $tab = 'basic';
    }

    $canEdit = auth()->user()->can('update', $customer);
    $customerTone = ['active' => 'success', 'lead' => 'info'][$customer->status] ?? 'neutral';
@endphp

@section('content')
    <section class="page-header">
        <div>
            <p class="page-eyebrow">CUSTOMER</p>
            <h1>{{ $customer->name }} <span class="mono heading-code">{{ $customer->customer_code }}</span></h1>
            <p>
                担当: {{ $customer->assignedUser?->display_name ?? '未割当' }}
                <x-status-badge :label="$customer->status_label" :tone="$customerTone" />
            </p>
        </div>

        @if ($canEdit)
            <div class="page-header__actions">
                <a class="secondary-button" href="{{ route('customers.edit', $customer) }}">基本情報を編集</a>
                <a class="primary-button" href="{{ route('customers.contracts.create', $customer) }}">契約を登録</a>
            </div>
        @endif
    </section>

    <nav class="tabs" aria-label="顧客情報の切り替え">
        @foreach ($tabs as $key => $label)
            <a
                href="{{ route('customers.show', ['customer' => $customer, 'tab' => $key]) }}"
                class="tabs__link {{ $tab === $key ? 'is-active' : '' }}"
                @if ($tab === $key) aria-current="page" @endif
            >{{ $label }}</a>
        @endforeach
    </nav>

    <section class="tab-panel" aria-labelledby="tab-title">
        <h2 id="tab-title" class="visually-hidden">{{ $tabs[$tab] }}</h2>

        @if ($tab === 'basic')
            <dl class="definition-list">
                <div class="definition-list__row"><dt>顧客コード</dt><dd class="mono">{{ $customer->customer_code }}</dd></div>
                <div class="definition-list__row"><dt>氏名</dt><dd>{{ $customer->name }}</dd></div>
                <div class="definition-list__row"><dt>氏名カナ</dt><dd>{{ $customer->name_kana ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>生年月日</dt><dd>{{ $customer->birth_date ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>住所</dt><dd>{{ $customer->address ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>電話番号</dt><dd>{{ $customer->phone ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>メールアドレス</dt><dd>{{ $customer->email ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>勤務先・職業</dt><dd>{{ $customer->occupation ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>家族構成</dt><dd>{{ $customer->family ?: '-' }}</dd></div>
                <div class="definition-list__row"><dt>担当者</dt><dd>{{ $customer->assignedUser?->display_name ?? '未割当' }}</dd></div>
                <div class="definition-list__row"><dt>顧客状態</dt><dd>{{ $customer->status_label }}</dd></div>
                <div class="definition-list__row"><dt>同意取得日時</dt><dd>{{ $customer->consented_at?->format('Y/m/d H:i') ?? '-' }}</dd></div>
                <div class="definition-list__row"><dt>登録日</dt><dd>{{ $customer->created_at?->format('Y/m/d') }}</dd></div>
            </dl>
        @endif

        @if ($tab === 'health' && $canViewHealth)
            <p class="notice notice--warning">
                <span class="notice__icon" aria-hidden="true">▲</span>
                要配慮個人情報です。閲覧は監査ログへ記録されます。
            </p>
            <dl class="definition-list">
                <div class="definition-list__row"><dt>健康・病歴情報</dt><dd class="preformatted">{{ $customer->health_information ?: '登録されていません。' }}</dd></div>
            </dl>
        @endif

        @if ($tab === 'intention')
            <div class="panel-actions">
                @if ($canEdit)
                    <button type="button" class="primary-button" data-modal-open="intention-modal">顧客意向を登録</button>
                @endif
            </div>

            @forelse ($customer->intentions as $intention)
                <article class="record-card">
                    <header class="record-card__header">
                        <h3>{{ $intention->created_at->format('Y/m/d') }} 登録（{{ $intention->creator?->display_name }}）</h3>
                        @php
                            $intentionLabel = $intention->confirmed_at
                                ? '確認済 '.$intention->confirmed_at->format('Y/m/d').'（'.$intention->confirmation_method_label.'）'
                                : '確認未完了';
                        @endphp
                        <x-status-badge :label="$intentionLabel" :tone="$intention->confirmed_at ? 'success' : 'warning'" />
                    </header>
                    <dl class="definition-list definition-list--compact">
                        <div class="definition-list__row"><dt>当初意向</dt><dd class="preformatted">{{ $intention->initial_intention }}</dd></div>
                        <div class="definition-list__row"><dt>最終意向</dt><dd class="preformatted">{{ $intention->final_intention ?: '-' }}</dd></div>
                        <div class="definition-list__row"><dt>保障目的</dt><dd>{{ $intention->protection_purpose ?: '-' }}</dd></div>
                        <div class="definition-list__row"><dt>予算</dt><dd>{{ $intention->budget ?: '-' }}</dd></div>
                        <div class="definition-list__row"><dt>希望期間</dt><dd>{{ $intention->desired_period ?: '-' }}</dd></div>
                        <div class="definition-list__row"><dt>提案理由（意向との適合）</dt><dd class="preformatted">{{ $intention->proposed_reason ?: '-' }}</dd></div>
                        <div class="definition-list__row"><dt>当初意向との相違点</dt><dd class="preformatted">{{ $intention->differences ?: '-' }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="empty-state">顧客意向はまだ登録されていません。「顧客意向を登録」から当初意向を記録してください。</p>
            @endforelse

            @if ($canEdit)
                <x-modal id="intention-modal" title="顧客意向を登録" size="large">
                    <form method="POST" action="{{ route('customers.intentions.store', $customer) }}" class="form-grid" id="intention-form" data-single-submit>
                        @csrf
                        <x-field name="initial_intention" label="当初意向" :required="true" full>
                            <textarea id="initial_intention" name="initial_intention" maxlength="2000" rows="3" required>{{ old('initial_intention') }}</textarea>
                        </x-field>
                        <x-field name="final_intention" label="最終意向" full>
                            <textarea id="final_intention" name="final_intention" maxlength="2000" rows="3">{{ old('final_intention') }}</textarea>
                        </x-field>
                        <x-field name="protection_purpose" label="保障目的">
                            <input type="text" id="protection_purpose" name="protection_purpose" maxlength="500" value="{{ old('protection_purpose') }}">
                        </x-field>
                        <x-field name="budget" label="予算">
                            <input type="text" id="budget" name="budget" maxlength="100" value="{{ old('budget') }}">
                        </x-field>
                        <x-field name="desired_period" label="希望期間">
                            <input type="text" id="desired_period" name="desired_period" maxlength="100" value="{{ old('desired_period') }}">
                        </x-field>
                        <x-field name="confirmation_method" label="確認方法">
                            <select id="confirmation_method" name="confirmation_method">
                                <option value="">未選択</option>
                                @foreach (App\Models\CustomerIntention::CONFIRMATION_METHODS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('confirmation_method') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field name="proposed_reason" label="提案理由（意向との適合）" full>
                            <textarea id="proposed_reason" name="proposed_reason" maxlength="2000" rows="3">{{ old('proposed_reason') }}</textarea>
                        </x-field>
                        <x-field name="differences" label="当初意向との相違点" full>
                            <textarea id="differences" name="differences" maxlength="2000" rows="2">{{ old('differences') }}</textarea>
                        </x-field>
                        <div class="form-field form-field--full">
                            <label class="checkbox-label" for="confirmed">
                                <input type="checkbox" id="confirmed" name="confirmed" value="1" @checked(old('confirmed'))>
                                <span>顧客本人に最終意向を確認済み</span>
                            </label>
                        </div>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="secondary-button" data-modal-close>キャンセル</button>
                        <button type="submit" class="primary-button" form="intention-form">登録する</button>
                    </x-slot:footer>
                </x-modal>
            @endif
        @endif

        @if ($tab === 'contracts')
            <div class="panel-actions">
                @if ($canEdit)
                    <a class="primary-button" href="{{ route('customers.contracts.create', $customer) }}">契約を登録</a>
                @endif
            </div>

            <x-responsive-table caption="契約一覧">
                <thead>
                    <tr>
                        <th scope="col">契約日</th>
                        <th scope="col">保険会社</th>
                        <th scope="col">プラン</th>
                        <th scope="col">契約時金額</th>
                        <th scope="col">状態</th>
                        <th scope="col">証券番号</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customer->contracts as $contract)
                        <tr>
                            <td data-label="契約日">{{ $contract->contract_date->format('Y/m/d') }}</td>
                            <td data-label="保険会社">{{ $contract->insurer_name_snapshot ?: '-' }}</td>
                            <td data-label="プラン">{{ $contract->plan_name_snapshot }}@if ($contract->plan_type_snapshot)<span class="muted">（{{ $contract->plan_type_snapshot }}）</span>@endif</td>
                            <td data-label="契約時金額">
                                {{ $contract->billing_cycle_label }} {{ $contract->formatted_premium }}
                                @if ($contract->is_price_overridden)
                                    <x-status-badge label="上書き" tone="warning" />
                                @endif
                            </td>
                            @php $contractTone = ['in_force' => 'success', 'applied' => 'info'][$contract->status] ?? 'neutral'; @endphp
                            <td data-label="状態"><x-status-badge :label="$contract->status_label" :tone="$contractTone" /></td>
                            <td data-label="証券番号">{{ $contract->policy_number ?: '-' }}</td>
                            <td data-label="操作" class="table-actions">
                                @if ($canEdit)
                                    <a class="table-link" href="{{ route('customers.contracts.edit', [$customer, $contract]) }}">編集</a>
                                @else
                                    <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-cell">契約はまだ登録されていません。</td></tr>
                    @endforelse
                </tbody>
            </x-responsive-table>
        @endif

        @if ($tab === 'interactions')
            <div class="panel-actions">
                @if ($canEdit)
                    <button type="button" class="primary-button" data-modal-open="interaction-modal">対応履歴を登録</button>
                @endif
            </div>

            @forelse ($customer->interactions as $interaction)
                <article class="record-card">
                    <header class="record-card__header">
                        <h3>{{ $interaction->contacted_at->format('Y/m/d H:i') }} {{ $interaction->channel_label }}</h3>
                        <span class="muted">{{ $interaction->user?->display_name }}</span>
                    </header>
                    <p class="preformatted">{{ $interaction->summary }}</p>
                    @if ($interaction->next_action)
                        <p class="record-card__next">次回: {{ $interaction->next_action }}@if ($interaction->next_action_at)（{{ $interaction->next_action_at->format('Y/m/d H:i') }}）@endif</p>
                    @endif
                </article>
            @empty
                <p class="empty-state">対応履歴はまだ登録されていません。</p>
            @endforelse

            @if ($canEdit)
                <x-modal id="interaction-modal" title="対応履歴を登録">
                    <form method="POST" action="{{ route('customers.interactions.store', $customer) }}" class="form-grid" id="interaction-form" data-single-submit>
                        @csrf
                        <x-field name="channel" label="対応手段" :required="true">
                            <select id="channel" name="channel" required>
                                @foreach (App\Models\Interaction::CHANNELS as $value => $label)
                                    <option value="{{ $value }}" @selected(old('channel') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field name="contacted_at" label="対応日時" :required="true">
                            <input type="datetime-local" id="contacted_at" name="contacted_at" value="{{ old('contacted_at', now()->format('Y-m-d\TH:i')) }}" required>
                        </x-field>
                        <x-field name="summary" label="対応内容" :required="true" full>
                            <textarea id="summary" name="summary" maxlength="2000" rows="4" required>{{ old('summary') }}</textarea>
                        </x-field>
                        <x-field name="next_action" label="次回対応">
                            <input type="text" id="next_action" name="next_action" maxlength="500" value="{{ old('next_action') }}">
                        </x-field>
                        <x-field name="next_action_at" label="次回対応日時">
                            <input type="datetime-local" id="next_action_at" name="next_action_at" value="{{ old('next_action_at') }}">
                        </x-field>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="secondary-button" data-modal-close>キャンセル</button>
                        <button type="submit" class="primary-button" form="interaction-form">登録する</button>
                    </x-slot:footer>
                </x-modal>
            @endif
        @endif

        @if ($tab === 'maintenance')
            <div class="panel-actions">
                @if ($canEdit)
                    <button type="button" class="primary-button" data-modal-open="maintenance-modal">保全・給付履歴を登録</button>
                @endif
            </div>

            <x-responsive-table caption="保全・給付履歴">
                <thead>
                    <tr>
                        <th scope="col">受付日</th>
                        <th scope="col">種別</th>
                        <th scope="col">内容</th>
                        <th scope="col">状態</th>
                        <th scope="col">完了日</th>
                        <th scope="col">担当</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customer->maintenanceHistories as $history)
                        <tr>
                            <td data-label="受付日">{{ $history->requested_at->format('Y/m/d') }}</td>
                            <td data-label="種別">{{ $history->type_label }}</td>
                            <td data-label="内容" class="preformatted">{{ $history->description }}</td>
                            @php $historyTone = ['completed' => 'success', 'in_progress' => 'info'][$history->status] ?? 'warning'; @endphp
                            <td data-label="状態"><x-status-badge :label="$history->status_label" :tone="$historyTone" /></td>
                            <td data-label="完了日">{{ $history->completed_at?->format('Y/m/d') ?? '-' }}</td>
                            <td data-label="担当">{{ $history->user?->display_name }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-cell">保全・給付履歴はまだ登録されていません。</td></tr>
                    @endforelse
                </tbody>
            </x-responsive-table>

            @if ($canEdit)
                <x-modal id="maintenance-modal" title="保全・給付履歴を登録">
                    <form method="POST" action="{{ route('customers.maintenance-histories.store', $customer) }}" class="form-grid" id="maintenance-form" data-single-submit>
                        @csrf
                        <x-field name="type" label="種別" :required="true">
                            <select id="type" name="type" required>
                                @foreach (App\Models\MaintenanceHistory::TYPES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field name="status" label="状態" :required="true">
                            <select id="status" name="status" required>
                                @foreach (App\Models\MaintenanceHistory::STATUSES as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'requested') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </x-field>
                        <x-field name="requested_at" label="受付日" :required="true">
                            <input type="date" id="requested_at" name="requested_at" value="{{ old('requested_at', now()->toDateString()) }}" required>
                        </x-field>
                        <x-field name="completed_at" label="完了日">
                            <input type="date" id="completed_at" name="completed_at" value="{{ old('completed_at') }}">
                        </x-field>
                        <x-field name="description" label="内容" :required="true" full>
                            <textarea id="description" name="description" maxlength="2000" rows="4" required>{{ old('description') }}</textarea>
                        </x-field>
                    </form>
                    <x-slot:footer>
                        <button type="button" class="secondary-button" data-modal-close>キャンセル</button>
                        <button type="submit" class="primary-button" form="maintenance-form">登録する</button>
                    </x-slot:footer>
                </x-modal>
            @endif
        @endif

        @if ($tab === 'audit')
            <p class="muted">この顧客に対する直近50件の操作です。値そのものは記録せず、変更項目名のみ保持します。</p>
            <x-responsive-table caption="操作履歴" :cards="false">
                <thead>
                    <tr>
                        <th scope="col">日時</th>
                        <th scope="col">操作者</th>
                        <th scope="col">操作</th>
                        <th scope="col">変更項目</th>
                        <th scope="col">結果</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($auditLogs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y/m/d H:i:s') }}</td>
                            <td>{{ $log->user?->display_name ?? '-' }}</td>
                            <td class="mono">{{ $log->action }}</td>
                            <td>{{ implode(', ', $log->changed_fields ?? []) ?: '-' }}</td>
                            <td>{{ $log->succeeded ? '成功' : '失敗' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">操作履歴はありません。</td></tr>
                    @endforelse
                </tbody>
            </x-responsive-table>
        @endif
    </section>
@endsection
