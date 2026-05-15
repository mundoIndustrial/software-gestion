@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-center gap-2">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-item disabled">
                <span class="material-symbols-rounded">chevron_left</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination-item" rel="prev">
                <span class="material-symbols-rounded">chevron_left</span>
            </a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="pagination-item disabled">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-item active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-item">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination-item" rel="next">
                <span class="material-symbols-rounded">chevron_right</span>
            </a>
        @else
            <span class="pagination-item disabled">
                <span class="material-symbols-rounded">chevron_right</span>
            </span>
        @endif
    </nav>
@endif
