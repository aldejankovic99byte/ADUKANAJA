@if ($paginator->hasPages())
    <nav class="pager" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="pager-btn disabled">‹ Sebelumnya</span>
        @else
            <a class="pager-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Sebelumnya</a>
        @endif

        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="pager-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya ›</a>
        @else
            <span class="pager-btn disabled">Berikutnya ›</span>
        @endif
    </nav>
@endif