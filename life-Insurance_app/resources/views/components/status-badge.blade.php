{{-- 状態は色だけでなくアイコンと文字で示します（仕様 8）。 --}}
@props([
    'tone' => 'neutral',
    'label',
])

@php
    $icons = [
        'success' => '●',
        'warning' => '▲',
        'danger' => '■',
        'neutral' => '○',
        'info' => '◆',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge badge--'.$tone]) }}>
    <span class="badge__icon" aria-hidden="true">{{ $icons[$tone] ?? '○' }}</span>
    {{ $label }}
</span>
