{{-- 削除・状態変更などの確認用カスタムモーダル。form[data-confirm] の送信をJSで捕捉して表示します。
     JavaScript無効時はフォームがそのまま送信されます。 --}}
<x-modal id="confirm-modal" title="確認" size="small">
    <p class="modal__message" data-confirm-message>この操作を実行しますか。</p>

    <x-slot:footer>
        <button type="button" class="secondary-button" data-modal-close>キャンセル</button>
        <button type="button" class="primary-button primary-button--danger" data-confirm-accept>実行する</button>
    </x-slot:footer>
</x-modal>
