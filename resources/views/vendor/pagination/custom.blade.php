{{-- Botón primera página --}}
<button class="pagination-btn" data-page="1" {{ $paginator->currentPage() == 1 ? 'disabled' : '' }}>
    <i class="fas fa-angle-double-left"></i>
</button>

{{-- Botón página anterior --}}
<button class="pagination-btn" data-page="{{ $paginator->currentPage() - 1 }}" {{ $paginator->currentPage() == 1 ? 'disabled' : '' }}>
    <i class="fas fa-angle-left"></i>
</button>

{{-- Números de página --}}
@php
    $start = max(1, $paginator->currentPage() - 2);
    $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
@endphp

@for($i = $start; $i <= $end; $i++)
    <button class="pagination-btn page-number {{ $i == $paginator->currentPage() ? 'active' : '' }}" data-page="{{ $i }}">
        {{ $i }}
    </button>
@endfor

{{-- Botón página siguiente --}}
<button class="pagination-btn" data-page="{{ $paginator->currentPage() + 1 }}" {{ $paginator->currentPage() == $paginator->lastPage() ? 'disabled' : '' }}>
    <i class="fas fa-angle-right"></i>
</button>

{{-- Botón última página --}}
<button class="pagination-btn" data-page="{{ $paginator->lastPage() }}" {{ $paginator->currentPage() == $paginator->lastPage() ? 'disabled' : '' }}>
    <i class="fas fa-angle-double-right"></i>
</button>
