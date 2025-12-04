@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="pagination-wrapper">
        <ul class="pagination">
            {{-- First Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">
                        <span class="material-symbols-rounded">first_page</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url(1) }}" title="Primera página">
                        <span class="material-symbols-rounded">first_page</span>
                    </a>
                </li>
            @endif

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" title="Página anterior">
                        <span class="material-symbols-rounded">chevron_left</span>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">
                                    {{ $page }}
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" title="Página siguiente">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">
                        <span class="material-symbols-rounded">chevron_right</span>
                    </span>
                </li>
            @endif

            {{-- Last Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" title="Última página">
                        <span class="material-symbols-rounded">last_page</span>
                    </a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link" aria-hidden="true">
                        <span class="material-symbols-rounded">last_page</span>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
