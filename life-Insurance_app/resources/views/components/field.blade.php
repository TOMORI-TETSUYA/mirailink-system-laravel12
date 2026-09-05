{{-- 入力欄の共通ラッパー。ラベル・必須表示・エラーを一定の順序で出力します。 --}}
@props([
    'name',
    'label',
    'required' => false,
    'help' => null,
    'full' => false,
    'id' => null,
])

@php
    $id ??= $name;
@endphp

<div {{ $attributes->merge(['class' => 'form-field'.($full ? ' form-field--full' : '').($errors->has($name) ? ' has-error' : '')]) }}>
    <label class="form-label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span class="required-mark">必須</span>
        @endif
    </label>

    {{ $slot }}

    @if ($help)
        <p class="form-help" id="{{ $id }}-help">{{ $help }}</p>
    @endif

    @error($name)
        <p class="field-error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
