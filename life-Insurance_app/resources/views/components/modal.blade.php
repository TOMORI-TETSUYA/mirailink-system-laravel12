{{-- カスタムモーダル。ブラウザ標準の alert/confirm は使用しません。 --}}
@props([
    'id',
    'title',
    'size' => 'medium',
])

<div
    class="modal modal--{{ $size }}"
    id="{{ $id }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    data-modal
    hidden
>
    <div class="modal__backdrop" data-modal-close></div>

    <div class="modal__panel" role="document" tabindex="-1" data-modal-panel>
        <div class="modal__header">
            <h2 class="modal__title" id="{{ $id }}-title">{{ $title }}</h2>
            <button type="button" class="modal__close" aria-label="閉じる" data-modal-close>×</button>
        </div>

        <div class="modal__body">
            {{ $slot }}
        </div>

        @isset($footer)
            <div class="modal__footer">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
