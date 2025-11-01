<nav role="navigation" aria-label="Pagination Navigation" class="pagination">
    {{-- Previous Page Link --}}
    @if ($paginator->onFirstPage())
        <button class="nav-btn" disabled>« Previous</button>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="nav-btn">« Previous</a>
    @endif

    {{-- Pagination Elements --}}
    @foreach ($elements as $element)
        {{-- "Three Dots" Separator --}}
        @if (is_string($element))
            <span class="dots">{{ $element }}</span>
        @endif

        {{-- Array Of Links --}}
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <button class="active">{{ $page }}</button>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="nav-btn">Next »</a>
    @else
        <button class="nav-btn" disabled>Next »</button>
    @endif
</nav>
