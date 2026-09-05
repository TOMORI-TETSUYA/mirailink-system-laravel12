{{-- 3桁区切りの表示補助付き金額入力。サーバーへは hidden の整数値だけを送信します（仕様 20.2）。 --}}
@props([
    'name' => 'amount_yen',
    'id' => 'amount_yen',
    'label' => '金額',
    'value' => null,
    'required' => true,
])

<div class="form-field @error($name) has-error @enderror">
    <label class="form-label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span class="required-mark">必須</span>
        @endif
    </label>
    <span class="money-control">
        <input
            type="text"
            id="{{ $id }}"
            inputmode="numeric"
            autocomplete="off"
            value="{{ $value !== null && $value !== '' ? number_format((int) $value) : '' }}"
            @if ($required) required @endif
            aria-describedby="{{ $id }}-help @error($name) {{ $id }}-error @enderror"
            data-money-display
        >
        <input
            type="hidden"
            name="{{ $name }}"
            value="{{ $value }}"
            data-money-value
        >
        <span class="money-control__unit" aria-hidden="true">円</span>
    </span>
    <p class="form-help" id="{{ $id }}-help">円単位の整数で入力します。</p>
    @error($name)
        <p class="field-error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
