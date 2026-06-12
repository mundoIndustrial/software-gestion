@extends('operario.layout')

@section('title', 'Recibos Préstamo')
@section('page-title')
    <span style="display: inline-flex; align-items: center; gap: 0.6rem;">
        <a href="{{ url('/operario/dashboard') }}"
           style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;border:1px solid #cbd5e1;background:#fff;color:#0f172a;text-decoration:none;"
           title="Volver a dashboard">
            <span class="material-symbols-rounded" style="font-size:19px;">arrow_back</span>
        </a>
        <span class="material-symbols-rounded">receipt_long</span>
        <span>RECIBOS PRÉSTAMO</span>
    </span>
@endsection

@push('styles')
<style>
    .prestamos-wrap {
        width: 100%;
        max-width: 980px;
        margin: 0 auto;
        display: grid;
        gap: 1rem;
    }
    .prestamos-form-card,
    .prestamos-list-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e6ebf3;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        padding: 0.9rem;
    }
    .tipo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem;
        background: #f1f5f9;
        border-radius: 14px;
        padding: 0.3rem;
    }
    .tipo-btn {
        border: 0;
        background: transparent;
        border-radius: 14px;
        padding: 0.7rem 0.65rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        -webkit-text-decoration: none;
    }
    .tipo-btn:link,
    .tipo-btn:visited,
    .tipo-btn:hover,
    .tipo-btn:active {
        text-decoration: none;
    }
    .tipo-btn.active {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        box-shadow: 0 8px 18px rgba(3, 105, 161, 0.28);
    }
    .tipo-btn strong {
        color: #0f172a;
        display: block;
        font-size: 0.74rem;
        letter-spacing: 0.02em;
    }
    .tipo-btn span {
        color: #475569;
        font-size: 0.69rem;
        line-height: 1.2;
    }
    .tipo-btn.active strong,
    .tipo-btn.active span {
        color: #ffffff;
    }
    .tab-panel {
        display: none;
    }
    .tab-panel.active {
        display: block;
    }
    .add-btn {
        width: 100%;
        border: 0;
        border-radius: 12px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        font-weight: 700;
        font-size: 0.84rem;
        padding: 0.78rem 0.9rem;
        margin-bottom: 0.75rem;
        cursor: pointer;
    }
    .list-title {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-bottom: 0.65rem;
    }
    .list-title h3 {
        margin: 0;
        font-size: 0.88rem;
        color: #0f172a;
        font-weight: 700;
    }
    .toolbar-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-bottom: 0.75rem;
    }
    .search-form {
        display: flex;
        gap: 8px;
        margin: 0;
    }
    .search-clear-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 0 12px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #334155;
        background: #f8fafc;
        text-decoration: none;
        white-space: nowrap;
    }
    .search-field {
        width: 100%;
    }
    .search-box {
        flex: 1;
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 12px;
        font-size: 0.82rem;
        color: #0f172a;
        background: #ffffff;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .search-box:focus {
        outline: none;
        border-color: #0ea5e9;
        box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
    }
    .pagination-wrap {
        margin-top: 10px;
    }
    .recibos-cards {
        display: grid;
        gap: 0.75rem;
    }
    .flash-success {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        border-radius: 12px;
        padding: 0.7rem 0.8rem;
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 0.75rem;
    }
    .recibo-card-mobile {
        border: 1px solid #e4eaf3;
        border-radius: 16px;
        padding: 0.9rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .recibo-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.45rem;
    }
    .recibo-num {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }
    .recibo-meta {
        margin: 0.2rem 0 0;
        color: #334155;
        font-size: 0.82rem;
        line-height: 1.35;
    }
    .recibo-actions {
        margin-top: 0.8rem;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.45rem;
    }
    .recibo-actions button,
    .recibo-actions a {
        border: 1px solid #d2dceb;
        border-radius: 10px;
        background: #ffffff;
        color: #0f172a;
        font-size: 0.77rem;
        font-weight: 600;
        padding: 0.5rem 0.55rem;
        cursor: pointer;
        text-align: center;
        text-decoration: none;
    }
    .recibo-actions .full-row {
        grid-column: 1 / -1;
    }
    .recibo-actions button:last-child,
    .recibo-actions a:last-child {
        background: #fff7ed;
        border-color: #fed7aa;
        color: #9a3412;
    }
    .badge-inline {
        display: inline-block;
        margin-top: 6px;
        margin-right: 6px;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #334155;
    }
    .modal-backdrop-entrada {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.45);
        z-index: 3000;
    }
    .modal-entrada {
        width: min(92vw, 560px);
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        padding: 16px;
    }
    .modal-entrada textarea {
        width: 100%;
        min-height: 96px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px;
        font-size: 13px;
        resize: vertical;
    }
    .modal-entrada-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 10px;
    }
    .modal-btn {
        border: 0;
        border-radius: 10px;
        padding: 9px 14px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
    }
    .modal-btn:hover {
        transform: translateY(-1px);
    }
    .modal-btn:active {
        transform: translateY(0);
    }
    .modal-btn-cancel {
        background: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .modal-btn-confirm {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        color: #fff;
        box-shadow: 0 8px 18px rgba(21, 128, 61, 0.3);
    }
    .toast-prestamo {
        position: fixed;
        right: 14px;
        bottom: 14px;
        z-index: 5000;
        min-width: 240px;
        max-width: 320px;
        border-radius: 12px;
        padding: 11px 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #fff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.28);
        opacity: 0;
        transform: translateY(12px);
        transition: opacity 0.2s ease, transform 0.2s ease;
        pointer-events: none;
    }
    .toast-prestamo.show {
        opacity: 1;
        transform: translateY(0);
    }
    .toast-prestamo.success {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
    }
    .toast-prestamo.error {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    }
    .toast-prestamo.info {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }
    @media (min-width: 768px) {
        .prestamos-form-card,
        .prestamos-list-card {
            padding: 1.2rem;
        }
        .toolbar-row {
            grid-template-columns: 1fr auto;
            align-items: center;
        }
        .add-btn {
            width: auto;
            margin-bottom: 0;
            white-space: nowrap;
        }
        .recibos-cards {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>
@endpush

@section('content')
    @php
        $tabActiva = $tabActiva ?? request()->query('tab', 'insumos');
        $tabActiva = in_array($tabActiva, ['insumos', 'contramuestra'], true) ? $tabActiva : 'insumos';
    @endphp

    <div class="prestamos-wrap">
        <section class="prestamos-form-card">
            <div class="tipo-grid">
                <a href="{{ route('operario.recibos-prestamo.index', ['tab' => 'insumos']) }}" class="tipo-btn {{ $tabActiva === 'insumos' ? 'active' : '' }}" data-tipo="insumos">
                    <strong>PRESTAMO DE INSUMOS</strong>
                    <span>Para salida temporal de insumos del área.</span>
                </a>
                <a href="{{ route('operario.recibos-prestamo.index', ['tab' => 'contramuestra']) }}" class="tipo-btn {{ $tabActiva === 'contramuestra' ? 'active' : '' }}" data-tipo="contramuestra">
                    <strong>PRESTAMO DE CONTRAMUESTRA COSTURA</strong>
                    <span>Para contramuestras solicitadas a costura.</span>
                </a>
            </div>
        </section>

        <section class="prestamos-list-card">
            <div class="list-title">
                <h3>Recibos Registrados</h3>
            </div>
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            <div class="tab-panel {{ $tabActiva === 'insumos' ? 'active' : '' }}" data-panel="insumos">
                <div class="toolbar-row">
                    <div class="search-form" data-live-search-form="insumos">
                        <div class="search-field">
                            <input type="text" id="searchInputInsumos" name="search_insumos" class="search-box" value="{{ $searchInsumos ?? '' }}" placeholder="Buscar por Consecutivo o Costurero...">
                        </div>
                        @if(!empty($searchInsumos))
                            <button type="button" class="search-clear-btn" data-clear-search="searchInputInsumos">Limpiar</button>
                        @endif
                    </div>
                    <a href="{{ route('operario.recibos-prestamo.insumos.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de insumos</a>
                </div>
                <div class="recibos-cards">
                    @forelse(($recibosInsumos ?? []) as $recibo)
                        <article class="recibo-card-mobile"
                            data-search="{{ strtolower(trim((string) $recibo->numero_orden . ' ' . $recibo->nombre_costurero)) }}">
                            <div class="recibo-top">
                                <p class="recibo-num">N° {{ $recibo->numero_orden }}</p>
                            </div>
                            <p class="recibo-meta"><strong>Tipo:</strong> Préstamo de Insumos</p>
                            <p class="recibo-meta"><strong>Costurero:</strong> {{ $recibo->nombre_costurero }}</p>
                            <p class="recibo-meta"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</p>
                            @if($recibo->anulado)
                                <span class="badge-inline" style="background:#fef2f2;color:#991b1b;">ANULADO</span>
                                @if($recibo->anulado_en)
                                    <span class="badge-inline" style="background:#fef2f2;color:#7f1d1d;">{{ \Carbon\Carbon::parse($recibo->anulado_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            @if($recibo->confirmado_entrada)
                                <span class="badge-inline" style="background:#ecfdf5;color:#166534;">ENTRADA CONFIRMADA</span>
                                @if($recibo->confirmado_entrada_en)
                                    <span class="badge-inline" style="background:#f8fafc;color:#334155;">{{ \Carbon\Carbon::parse($recibo->confirmado_entrada_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', $recibo->id) }}" class="full-row">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.insumos.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
                                @if(!$recibo->anulado)
                                    <button type="button" class="full-row"
                                        data-action="anular"
                                        data-url="{{ route('operario.recibos-prestamo.insumos.anular', $recibo->id) }}">
                                        Anular
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="recibo-meta">Sin recibos registrados.</p>
                    @endforelse
                </div>
                <div class="pagination-wrap">
                    {{ $recibosInsumos->links('vendor.pagination.simple-clean') }}
                </div>
            </div>

            <div class="tab-panel {{ $tabActiva === 'contramuestra' ? 'active' : '' }}" data-panel="contramuestra">
                <div class="toolbar-row">
                    <div class="search-form" data-live-search-form="contramuestra">
                        <div class="search-field">
                            <input type="text" id="searchInputContramuestra" name="search_contramuestra" class="search-box" value="{{ $searchContramuestra ?? '' }}" placeholder="Buscar por Consecutivo o Costurero...">
                        </div>
                        @if(!empty($searchContramuestra))
                            <button type="button" class="search-clear-btn" data-clear-search="searchInputContramuestra">Limpiar</button>
                        @endif
                    </div>
                    <a href="{{ route('operario.recibos-prestamo.contramuestra.crear') }}" class="add-btn" style="display:inline-block;text-align:center;text-decoration:none;">+ Agregar préstamo de contramuestra</a>
                </div>
                <div class="recibos-cards">
                    @forelse(($recibosContramuestra ?? []) as $recibo)
                        <article class="recibo-card-mobile"
                            data-search="{{ strtolower(trim((string) $recibo->numero_orden . ' ' . $recibo->nombre_costurero)) }}">
                            <div class="recibo-top">
                                <p class="recibo-num">N° {{ $recibo->numero_orden }}</p>
                            </div>
                            <p class="recibo-meta"><strong>Tipo:</strong> Préstamo de Contramuestra Costura</p>
                            <p class="recibo-meta"><strong>Costurero:</strong> {{ $recibo->nombre_costurero }}</p>
                            <p class="recibo-meta"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($recibo->fecha)->format('d/m/Y') }}</p>
                            @if($recibo->anulado)
                                <span class="badge-inline" style="background:#fef2f2;color:#991b1b;">ANULADO</span>
                                @if($recibo->anulado_en)
                                    <span class="badge-inline" style="background:#fef2f2;color:#7f1d1d;">{{ \Carbon\Carbon::parse($recibo->anulado_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            @if($recibo->confirmado_entrada)
                                <span class="badge-inline" style="background:#ecfdf5;color:#166534;">ENTRADA CONFIRMADA</span>
                                @if($recibo->confirmado_entrada_en)
                                    <span class="badge-inline" style="background:#f8fafc;color:#334155;">{{ \Carbon\Carbon::parse($recibo->confirmado_entrada_en)->format('d/m/Y H:i') }}</span>
                                @endif
                            @endif
                            <div class="recibo-actions">
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', $recibo->id) }}" class="full-row">Ver</a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'costurero']) }}">
                                    {{ !empty($recibo->firma_costurero) ? 'Actualizar firma costurero' : 'Pendiente firma costurero' }}
                                </a>
                                <a href="{{ route('operario.recibos-prestamo.contramuestra.show', ['id' => $recibo->id, 'firmante' => 'mensajero']) }}">
                                    {{ !empty($recibo->firma_mensajero) ? 'Actualizar firma mensajero' : 'Pendiente firma mensajero' }}
                                </a>
                                @if(!$recibo->anulado)
                                    <button type="button" class="full-row"
                                        data-action="anular"
                                        data-url="{{ route('operario.recibos-prestamo.contramuestra.anular', $recibo->id) }}">
                                        Anular
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="recibo-meta">Sin recibos registrados.</p>
                    @endforelse
                </div>
                <div class="pagination-wrap">
                    {{ $recibosContramuestra->links('vendor.pagination.simple-clean') }}
                </div>
            </div>
        </section>
    </div>

    <div id="modal-confirmar-anular" class="modal-backdrop-entrada">
        <div class="modal-entrada">
            <h3 style="margin:0 0 8px;">Anular Recibo</h3>
            <p style="margin:0 0 10px;font-size:13px;color:#475569;">¿Seguro que deseas anular este recibo?</p>
            <div class="modal-entrada-actions">
                <button type="button" id="btn-cancelar-anular" class="modal-btn modal-btn-cancel">Cancelar</button>
                <button type="button" id="btn-confirmar-anular" class="modal-btn modal-btn-confirm" style="background:linear-gradient(135deg,#dc2626 0%,#b91c1c 100%);box-shadow:0 8px 18px rgba(185,28,28,.3);">Anular</button>
            </div>
        </div>
    </div>

    <script>
    (() => {
        const csrf = "{{ csrf_token() }}";
        const modalAnular = document.getElementById('modal-confirmar-anular');
        const btnCancelarAnular = document.getElementById('btn-cancelar-anular');
        const btnConfirmarAnular = document.getElementById('btn-confirmar-anular');
        let urlAnular = '';
        function showToast(message, type = 'info', ms = 2200) {
            const el = document.createElement('div');
            el.className = `toast-prestamo ${type}`;
            el.textContent = message;
            document.body.appendChild(el);
            requestAnimationFrame(() => el.classList.add('show'));
            setTimeout(() => {
                el.classList.remove('show');
                setTimeout(() => el.remove(), 220);
            }, ms);
        }

        function closeModalAnular() {
            modalAnular.style.display = 'none';
            urlAnular = '';
        }

        document.querySelectorAll('[data-action="anular"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                urlAnular = btn.dataset.url;
                modalAnular.style.display = 'flex';
            });
        });
        btnCancelarAnular?.addEventListener('click', closeModalAnular);
        btnConfirmarAnular?.addEventListener('click', async () => {
            if (!urlAnular) return;
            const response = await fetch(urlAnular, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                showToast(data.message || 'No se pudo anular.', 'error');
                return;
            }
            closeModalAnular();
            showToast('Recibo anulado correctamente.', 'success', 1200);
            setTimeout(() => location.reload(), 900);
        });
        modalAnular?.addEventListener('click', (e) => {
            if (e.target === modalAnular) closeModalAnular();
        });

        function setupLiveSearch(inputId, panelSelector) {
            const input = document.getElementById(inputId);
            const panel = document.querySelector(panelSelector);
            if (!input || !panel) return;

            const form = input.closest('[data-live-search-form]');
            const cards = Array.from(panel.querySelectorAll('.recibo-card-mobile'));
            const paginationWrap = panel.querySelector('.pagination-wrap');
            const emptyId = `${inputId}-empty`;

            form?.addEventListener('submit', (e) => e.preventDefault());
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });

            const ensureEmptyNode = () => {
                let node = document.getElementById(emptyId);
                if (!node) {
                    node = document.createElement('p');
                    node.id = emptyId;
                    node.className = 'recibo-meta';
                    node.style.display = 'none';
                    node.textContent = 'Sin resultados para esta búsqueda.';
                    const list = panel.querySelector('.recibos-cards');
                    if (list) list.appendChild(node);
                }
                return node;
            };

            input.addEventListener('input', () => {
                const q = input.value.trim().toLowerCase();
                let visibles = 0;

                cards.forEach((card) => {
                    const text = (card.dataset.search || '').toLowerCase();
                    const match = q === '' || text.includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) visibles += 1;
                });

                const emptyNode = ensureEmptyNode();
                emptyNode.style.display = visibles === 0 ? '' : 'none';

                if (paginationWrap) {
                    paginationWrap.style.display = q === '' ? '' : 'none';
                }
            });
        }

        setupLiveSearch('searchInputInsumos', '[data-panel="insumos"]');
        setupLiveSearch('searchInputContramuestra', '[data-panel="contramuestra"]');

        document.querySelectorAll('[data-clear-search]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const inputId = btn.getAttribute('data-clear-search');
                const input = inputId ? document.getElementById(inputId) : null;
                if (!input) return;
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });
    })();
    </script>
@endsection
