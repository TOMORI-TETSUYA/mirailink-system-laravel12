@if ($paginator->hasPages())
    <nav class="pagination" aria-label="ページ送り">
        @if ($paginator->onFirstPage())
            <span class="pagination__link is-disabled" aria-disabled="true">前へ</span>
        @else
            <a class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">前へ</a>
        @endif

        <span class="pagination__status">
            {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }} ページ（全{{ $paginator->total() }}件）
        </span>

        @if ($paginator->hasMorePages())
            <a class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">次へ</a>
        @else
            <span class="pagination__link is-disabled" aria-disabled="true">次へ</span>
        @endif
    </nav>
@endif
