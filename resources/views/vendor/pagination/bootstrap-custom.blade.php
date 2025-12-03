@if ($paginator->hasPages())
    <nav aria-label="Page navigation" style="margin-top: 30px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap; padding: 15px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 10px; border: 1px solid #e9ecef; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            
            {{-- Información de página --}}
            <span style="color: #666; font-size: 0.9rem; font-weight: 500; margin-right: 10px; padding: 0 10px;">
                Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
            </span>

            <div style="display: flex; gap: 4px; align-items: center;">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 12px; background: #f0f0f0; color: #bbb; border-radius: 6px; border: 1px solid #e0e0e0; cursor: not-allowed; font-weight: 500; font-size: 0.85rem;">
                        ← Anterior
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 12px; background: white; color: #1e40af; border-radius: 6px; border: 1px solid #d0d0d0; text-decoration: none; font-weight: 500; font-size: 0.85rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                        ← Anterior
                    </a>
                @endif

                {{-- Pagination Elements --}}
                <div style="display: flex; gap: 2px; align-items: center;">
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span style="color: #999; font-weight: 500; padding: 0 4px; font-size: 0.9rem;">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white; border-radius: 6px; border: 1px solid #1e3a8a; font-weight: 600; font-size: 0.9rem; box-shadow: 0 2px 6px rgba(30, 64, 175, 0.25);">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 10px; background: white; color: #1e40af; border-radius: 6px; border: 1px solid #d0d0d0; text-decoration: none; font-weight: 500; font-size: 0.9rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 12px; background: white; color: #1e40af; border-radius: 6px; border: 1px solid #d0d0d0; text-decoration: none; font-weight: 500; font-size: 0.85rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                        Siguiente →
                    </a>
                @else
                    <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 38px; height: 38px; padding: 0 12px; background: #f0f0f0; color: #bbb; border-radius: 6px; border: 1px solid #e0e0e0; cursor: not-allowed; font-weight: 500; font-size: 0.85rem;">
                        Siguiente →
                    </span>
                @endif
            </div>
        </div>
    </nav>

    <style>
        nav a {
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        nav a:hover {
            background: #1e40af !important;
            color: white !important;
            border-color: #1e40af !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.35) !important;
        }

        @media (max-width: 768px) {
            nav div {
                gap: 4px !important;
            }
            
            nav span, nav a {
                min-width: 34px !important;
                height: 34px !important;
                font-size: 0.8rem !important;
                padding: 0 8px !important;
            }

            nav span:first-child {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            nav span:first-child {
                display: none;
            }

            nav span, nav a {
                min-width: 32px !important;
                height: 32px !important;
                padding: 0 6px !important;
            }
        }
    </style>
@endif
