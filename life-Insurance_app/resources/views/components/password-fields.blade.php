{{--
    パスワード入力欄（本体＋再入力）と、自動生成・表示切替・一括コピーをまとめた部品。

    レイアウト方針:
    フォーム全体（.form-grid）は 2 カラムのため、この部品を通常の項目として置くと
    自動生成ボタンや注意書きのぶんだけ行が高くなり、隣の列（アカウント状態・権限など）に
    大きな余白ができます。そのため全幅の 1 行として独立させ、本体と再入力を
    この中で横並びにして高さを揃えます。

    自動生成した値は生成直後だけ平文で表示し、タブを離れる・別画面へ移るときに
    伏せ字へ戻します（js/password-tools.js）。値はブラウザ保存領域へ残しません。
--}}
@props([
    'name' => 'password',
    'confirmName' => 'password_confirmation',
    'label' => 'パスワード',
    'confirmLabel' => 'パスワード（再入力）',
    'required' => false,
    'help' => null,
    'length' => 16,
    'copySources' => [],
])

<div class="form-field--full credential-block">
    <div class="credential-block__fields">
        <x-field :name="$name" :label="$label" :required="$required">
            <div class="credential-control">
                <input
                    type="password"
                    id="{{ $name }}"
                    name="{{ $name }}"
                    minlength="12"
                    maxlength="128"
                    autocomplete="new-password"
                    @if ($required) required @endif
                >
                <button
                    type="button"
                    class="credential-button"
                    data-credential-visibility="{{ $name }}"
                    aria-pressed="false"
                    aria-label="パスワードを表示する"
                >
                    表示
                </button>
            </div>
        </x-field>

        <x-field :name="$confirmName" :label="$confirmLabel" :required="$required">
            <div class="credential-control">
                <input
                    type="password"
                    id="{{ $confirmName }}"
                    name="{{ $confirmName }}"
                    minlength="12"
                    maxlength="128"
                    autocomplete="new-password"
                    @if ($required) required @endif
                >
                <button
                    type="button"
                    class="credential-button"
                    data-credential-visibility="{{ $confirmName }}"
                    aria-pressed="false"
                    aria-label="パスワードを表示する"
                >
                    表示
                </button>
            </div>
        </x-field>
    </div>

    <div class="credential-block__actions">
        <button
            type="button"
            class="secondary-button"
            data-credential-generate
            data-credential-targets="{{ $name }} {{ $confirmName }}"
            data-credential-length="{{ $length }}"
        >
            パスワードを自動生成
        </button>

        @if ($copySources !== [])
            {{-- 1 つのボタンでログインID・表示名・パスワードをまとめてコピーします。 --}}
            <button
                type="button"
                class="secondary-button"
                data-credential-copy-set="{{ json_encode($copySources, JSON_UNESCAPED_UNICODE) }}"
            >
                {{ implode('・', array_keys($copySources)) }}をコピー
            </button>
        @endif
    </div>

    <p class="credential-note" data-credential-note hidden>
        自動生成しました。大文字・小文字・数字・記号を含む{{ $length }}文字です。
        いまコピーしてお渡しください。画面を離れると伏せ字に戻ります。
    </p>

    @if ($help)
        <p class="form-help">{{ $help }}</p>
    @endif

    {{-- コピー結果・生成結果を読み上げへ伝えます。 --}}
    <p class="visually-hidden" role="status" aria-live="polite" data-credential-status></p>
</div>
