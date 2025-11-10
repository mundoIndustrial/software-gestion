{{-- Botón primera página --}}
<button class="pagination-btn" data-page="1" {{ $paginator->currentPage() == 1 ? 'disabled' : '' }}>
    <span class="material-symbols-rounded" style="font-size: 20px;">keyboard_double_arrow_left</span>
</button>

{{-- Botón página anterior --}}
<button class="pagination-btn" data-page="{{ $paginator->currentPage() - 1 }}" {{ $paginator->currentPage() == 1 ? 'disabled' : '' }}>
    <span class="material-symbols-rounded" style="font-size: 20px;">chevron_left</span>
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
    <span class="material-symbols-rounded" style="font-size: 20px;">chevron_right</span>
</button>

{{-- Botón última página --}}
<button class="pagination-btn" data-page="{{ $paginator->lastPage() }}" {{ $paginator->currentPage() == $paginator->lastPage() ? 'disabled' : '' }}>
    <span class="material-symbols-rounded" style="font-size: 20px;">keyboard_double_arrow_right</span>
</button>
