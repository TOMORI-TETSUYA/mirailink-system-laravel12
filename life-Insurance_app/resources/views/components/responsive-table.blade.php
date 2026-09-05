{{-- スマートフォンではカード、PCではテーブルへ切り替わる一覧。監査ログ等は cards=false で横スクロールにします。 --}}
@props([
    'cards' => true,
    'caption' => null,
])

<div class="table-scroll {{ $cards ? '' : 'table-scroll--wide' }}">
    <table {{ $attributes->merge(['class' => 'data-table '.($cards ? 'responsive-table' : 'wide-table')]) }}>
        @if ($caption)
            <caption class="visually-hidden">{{ $caption }}</caption>
        @endif
        {{ $slot }}
    </table>
</div>
