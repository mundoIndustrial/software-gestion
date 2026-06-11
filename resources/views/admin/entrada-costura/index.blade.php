@extends('layouts.base')

@section('title', 'Entrada Costura')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-spa.css') }}?v={{ time() }}">
    <style>
        .top-nav {
            display: none !important;
        }

        .entrada-costura-page {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .entrada-costura-filters-shell {
            border: 1px solid #dbe4f0;
            border-radius: 18px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.08), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
            padding: 18px;
        }

        .entrada-costura-filters-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 16px;
        }

        .entrada-costura-filters-head h2 {
            margin: 4px 0 6px;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
        }

        .entrada-costura-filters-head p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
        }

        .entrada-costura-filters-kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .entrada-costura-filter-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .entrada-costura-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            align-items: end;
        }

        .entrada-costura-filter-field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .entrada-costura-filter-field label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.01em;
        }

        .entrada-costura-filter-field-hidden {
            display: none;
        }

        .entrada-costura-control.gooey-search-input {
            min-height: 46px;
            padding: 11px 14px;
            border-radius: 14px;
            border-color: #d6e1ee;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }

        .entrada-costura-control.gooey-search-input:focus {
            transform: none;
            animation: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .entrada-costura-filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .entrada-costura-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 14px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease, border-color 0.18s ease;
        }

        .entrada-costura-btn:hover {
            transform: translateY(-1px);
        }

        .entrada-costura-btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.18);
        }

        .entrada-costura-btn-primary:hover {
            box-shadow: 0 14px 24px rgba(37, 99, 235, 0.24);
        }

        .entrada-costura-btn-secondary {
            background: #fff;
            color: #334155;
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .entrada-costura-btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .entrada-costura-btn .material-symbols-rounded {
            font-size: 18px;
            line-height: 1;
        }

        #viewEntradaCostura .table-talleres thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            border-bottom: 1px solid #dbe4f0;
            color: #475569;
        }

        #viewEntradaCostura .table-talleres tbody td {
            border-bottom: 1px solid #e5eaf2;
        }

        #viewEntradaCostura .table-talleres tbody tr:nth-child(even) {
            background: #fcfdff;
        }

        #viewEntradaCostura .table-talleres tbody tr:hover {
            background: #f5f9ff;
        }

        #viewEntradaCostura .table-talleres tbody tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 900px) {
            .entrada-costura-filters-head {
                flex-direction: column;
            }

            .entrada-costura-filter-actions {
                justify-content: stretch;
            }

            .entrada-costura-btn {
                width: 100%;
            }
        }
    </style>
@endpush

@section('body')
    <aside class="talleres-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="logo-wrapper" aria-label="Ir al Dashboard">
                <img src="{{ asset('images/logo2.png') }}"
                     alt="Logo Mundo Industrial"
                     class="header-logo"
                     data-logo-light="{{ asset('images/logo2.png') }}"
                     data-logo-dark="{{ asset('logo.png') }}">
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Colapsar menú">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>
        <nav class="sidebar-nav">
            @if(auth()->user()?->hasRole('admin'))
                <a href="{{ route('dashboard') }}" class="sidebar-item" id="navVolver" aria-label="Volver al Dashboard">
                    <span class="material-symbols-rounded">arrow_back</span>
                    <span class="nav-label">Volver</span>
                </a>
            @endif
            @if(auth()->user()?->hasRole('visualizador_talleres'))
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle expanded" id="navTalleresGroup">
                        <span class="material-symbols-rounded">factory</span>
                        <span class="nav-label">Talleres</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu" id="talleresSubmenu">
                        <a href="{{ route('talleres.index', ['status' => 'activos']) }}" class="sidebar-item sidebar-subitem" id="navTalleres">
                            <span class="nav-label">Activos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['status' => 'inactivos']) }}" class="sidebar-item sidebar-subitem" id="navTalleresInactivos">
                            <span class="nav-label">Inactivos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['view' => 'ordenes']) }}" class="sidebar-item sidebar-subitem" id="navOrdenes">
                            <span class="nav-label">Órdenes</span>
                        </a>
                    </div>
                </div>
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle expanded" id="navPrestamosGroup">
                        <span class="material-symbols-rounded">payment</span>
                        <span class="nav-label">Préstamos</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu" id="prestamosSubmenu">
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'insumos']) }}" class="sidebar-item sidebar-subitem" id="navPrestamosInsumos">
                            <span class="nav-label">Insumos</span>
                        </a>
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'contramuestra']) }}" class="sidebar-item sidebar-subitem" id="navPrestamosContramuestras">
                            <span class="nav-label">Contramuestras</span>
                        </a>
                    </div>
                </div>
            @endif
            <a href="{{ route('entrada.index') }}" class="sidebar-item active" id="navEntradaCostura" aria-label="Ir a Entrada Costura">
                <span class="material-symbols-rounded">assignment_return</span>
                <span class="nav-label">Entrada Costura</span>
            </a>
            <a href="{{ route('seguimiento-lavanderia.index') }}" class="sidebar-item" aria-label="Ir a Lavandería">
                <span class="material-symbols-rounded">local_laundry_service</span>
                <span class="nav-label">Lavandería</span>
            </a>
        </nav>
    </aside>

    <main class="main-container"
          data-csrf-token="{{ csrf_token() }}"
          data-route-toggle-status="{{ route('talleres.toggle-status', ':id') }}"
          data-route-api-search="{{ route('talleres.api.search') }}"
          data-route-api-recibos="{{ route('talleres.api.recibos', ':id') }}"
          data-route-api-entregas="{{ route('talleres.api.entregas', [':taller_id', ':recibo_id', ':es_parcial']) }}"
          data-route-actualizar-precio="{{ route('talleres.actualizar-precio', ':id') }}"
          data-route-store="{{ route('talleres.store') }}"
          data-route-update="{{ route('talleres.update', ':id') }}"
          data-route-api-ordenes="{{ route('talleres.api.ordenes') }}"
          data-route-api-recibo-completo="{{ route('talleres.api.recibo-completo') }}">
        <div class="entrada-costura-page">
            <div id="viewEntradaCostura" class="view-container">
            <div class="entrada-costura-filters-shell">
                <div class="entrada-costura-filters-head">
                    <div>
                        <span class="entrada-costura-filters-kicker">Historial de entregas</span>
                        <h2>Filtrar entrada</h2>
                        <p>Busca por día, mes, año o rango para revisar entregas completadas.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('entrada.index') }}" class="entrada-costura-filter-form" id="entradaCosturaFilterForm">
                    <div class="entrada-costura-filter-grid">
                        <div class="entrada-costura-filter-field">
                            <label for="entradaCosturaSearch">Buscar</label>
                            <input id="entradaCosturaSearch" type="text" name="search" value="{{ $searchEntradaCostura ?? '' }}" placeholder="Recibo, prenda, operario..." class="gooey-search-input entrada-costura-control">
                        </div>

                        <div class="entrada-costura-filter-field">
                            <label for="entradaCosturaFiltro">Filtro</label>
                            <select name="filtro" id="entradaCosturaFiltro" class="gooey-search-input entrada-costura-control">
                                <option value="all" {{ ($filtroEntradaCostura ?? 'all') === 'all' ? 'selected' : '' }}>Todo el historial</option>
                                <option value="day" {{ ($filtroEntradaCostura ?? '') === 'day' ? 'selected' : '' }}>Por día</option>
                                <option value="month" {{ ($filtroEntradaCostura ?? '') === 'month' ? 'selected' : '' }}>Por mes</option>
                                <option value="year" {{ ($filtroEntradaCostura ?? '') === 'year' ? 'selected' : '' }}>Por año</option>
                                <option value="range" {{ ($filtroEntradaCostura ?? '') === 'range' ? 'selected' : '' }}>Por rango</option>
                            </select>
                        </div>

                        <div id="entradaCosturaFieldDay" class="entrada-costura-filter-field entrada-costura-filter-field-hidden">
                            <label for="entradaCosturaFecha">Día</label>
                            <input id="entradaCosturaFecha" type="date" name="fecha" value="{{ $fechaEntradaCostura ?? '' }}" class="gooey-search-input entrada-costura-control">
                        </div>

                        <div id="entradaCosturaFieldMonth" class="entrada-costura-filter-field entrada-costura-filter-field-hidden">
                            <label for="entradaCosturaMes">Mes</label>
                            <input id="entradaCosturaMes" type="month" name="mes" value="{{ $mesEntradaCostura ?? '' }}" class="gooey-search-input entrada-costura-control">
                        </div>

                        <div id="entradaCosturaFieldYear" class="entrada-costura-filter-field entrada-costura-filter-field-hidden">
                            <label for="entradaCosturaAnio">Año</label>
                            <input id="entradaCosturaAnio" type="number" name="anio" min="2000" max="{{ now()->year + 1 }}" value="{{ $anioEntradaCostura ?? '' }}" class="gooey-search-input entrada-costura-control">
                        </div>

                        <div id="entradaCosturaFieldFrom" class="entrada-costura-filter-field entrada-costura-filter-field-hidden">
                            <label for="entradaCosturaDesde">Desde</label>
                            <input id="entradaCosturaDesde" type="date" name="desde" value="{{ $desdeEntradaCostura ?? '' }}" class="gooey-search-input entrada-costura-control">
                        </div>

                        <div id="entradaCosturaFieldTo" class="entrada-costura-filter-field entrada-costura-filter-field-hidden">
                            <label for="entradaCosturaHasta">Hasta</label>
                            <input id="entradaCosturaHasta" type="date" name="hasta" value="{{ $hastaEntradaCostura ?? '' }}" class="gooey-search-input entrada-costura-control">
                        </div>
                    </div>

                    <div class="entrada-costura-filter-actions">
                        <button type="submit" class="entrada-costura-btn entrada-costura-btn-primary">
                            <span class="material-symbols-rounded">search</span>
                            Aplicar filtro
                        </button>
                        <button type="button" id="entradaCosturaClearFilter" class="entrada-costura-btn entrada-costura-btn-secondary">
                            Limpiar filtro
                        </button>
                    </div>
                </form>
            </div>

            <div class="recibos-card">
                <div class="card-header">
                    <div class="icon">
                        <span class="material-symbols-rounded" style="font-size: 18px;">event_note</span>
                    </div>
                    <h2>Historial de entradas</h2>
                </div>

                <div class="table-container">
                    <table class="table-talleres" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Acciones</th>
                                <th>N° Recibo</th>
                                <th>Cliente</th>
                                <th>Prenda</th>
                                <th>Detalle de tallas</th>
                                <th>Cantidad total</th>
                                <th>Encargado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entradaCostura as $entrada)
                                @php
                                    $identificadorRecibo = trim((string) ($entrada['numero_recibo'] ?? ''));
                                    $pedidoReferencia = $entrada['numero_pedido'] ?? '-';
                                    $clienteReferencia = $entrada['cliente'] ?? '-';
                                    $encargadoReferencia = $entrada['encargado'] ?? ($entrada['nombre_operario'] ?? '-');
                                    $prendaReferencia = $entrada['nombre_prenda'] ?? ($entrada['descripcion_prenda'] ?? '-');
                                @endphp
                                <tr>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn-ver-recibo-completo"
                                            data-recibo-id="{{ $entrada['id_recibo'] ?? $entrada['id'] ?? '' }}"
                                            data-numero-recibo="{{ $identificadorRecibo }}"
                                            data-tipo-recibo="{{ $entrada['tipo_recibo'] ?? 'COSTURA' }}"
                                            data-pedido-produccion-id="{{ $entrada['numero_pedido'] ?? '' }}"
                                            data-prenda-id="{{ $entrada['prenda_id'] ?? '' }}"
                                            title="Ver recibo"
                                            aria-label="Ver recibo"
                                            style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border:none;border-radius:10px;background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);color:#fff;cursor:pointer;box-shadow:0 2px 8px rgba(37,99,235,.18);">
                                            <span class="material-symbols-rounded" style="font-size:20px;line-height:1;">visibility</span>
                                        </button>
                                    </td>
                                    <td>#{{ $identificadorRecibo }}</td>
                                    <td>{{ $clienteReferencia }}</td>
                                    <td>{{ $prendaReferencia }}</td>
                                    <td>
                                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                            @php
                                                $tallasAgrupadasPorGenero = collect($entrada['tallas'] ?? [])->groupBy(function ($talla) {
                                                    $genero = trim((string) ($talla['genero'] ?? ''));
                                                    return $genero !== '' ? $genero : 'Sin género';
                                                });
                                            @endphp
                                            @forelse($tallasAgrupadasPorGenero as $genero => $tallasGenero)
                                                <div style="display:flex;flex-direction:column;gap:6px;width:100%;">
                                                    <span style="display:inline-flex;align-items:center;gap:8px;padding:5px 10px;border-radius:999px;background:#ecfeff;color:#155e75;font-size:12px;font-weight:700;width:max-content;">
                                                        {{ $genero }}
                                                    </span>
                                                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                                        @foreach($tallasGenero as $talla)
                                                            @php
                                                                $etiquetaTalla = trim((string) ($talla['talla'] ?? ''));
                                                                $etiquetaTalla = $etiquetaTalla !== '' ? $etiquetaTalla : 'Sin talla';
                                                            @endphp
                                                            <span style="display:inline-flex;align-items:center;gap:8px;padding:5px 10px;border-radius:999px;background:#eef2ff;color:#3730a3;font-size:12px;font-weight:600;">
                                                                <span>{{ $etiquetaTalla }}</span>
                                                                <strong style="background:#3730a3;color:#fff;border-radius:999px;padding:2px 8px;font-size:11px;line-height:1;">
                                                                    {{ $talla['cantidad'] ?? 0 }}
                                                                </strong>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @empty
                                                <span style="color:#94a3b8;">Sin tallas registradas</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td><strong>{{ $entrada['total_unidades'] ?? 0 }}</strong></td>
                                    <td>{{ $encargadoReferencia }}</td>
                                    <td>{{ $entrada['fecha'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:24px;color:#94a3b8;">
                                        No hay entradas registradas para la fecha seleccionada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($entradaCostura instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div class="pagination-container" style="margin-top: 16px;">
                        {{ $entradaCostura->links('vendor.pagination.simple-clean') }}
                    </div>
                @endif
            </div>
            </div>
        </div>
    </main>

    @include('components.orders-components.recibo-corte-bodega-detail-modal')

    <div id="modal-overlay"
         style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;"
         onclick="closeModalOverlay()"></div>
    <div id="order-detail-modal-wrapper"
         style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
        <x-orders-components.order-detail-modal />
    </div>
    <x-orders-components.order-tracking-modal />
    <div id="dropdowns-container" style="position: fixed; top: 0; left: 0; z-index: 999999; pointer-events: none; width: 0; height: 0; overflow: visible;"></div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}?v={{ filemtime(public_path('js/modulos/pedidos-recibos/loader.js')) }}"></script>
    <script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}?v={{ filemtime(public_path('js/ordersjs/order-detail-modal-manager.js')) }}"></script>
    <script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
    <script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
    <script src="{{ asset('js/modulos/entrada-costura/entrada-costura.js') }}?v={{ time() }}"></script>
    <script>
        (function () {
            const form = document.getElementById('entradaCosturaFilterForm');
            const filtro = document.getElementById('entradaCosturaFiltro');
            const limpiar = document.getElementById('entradaCosturaClearFilter');
            const search = document.getElementById('entradaCosturaSearch');
            const campos = {
                day: document.getElementById('entradaCosturaFieldDay'),
                month: document.getElementById('entradaCosturaFieldMonth'),
                year: document.getElementById('entradaCosturaFieldYear'),
                rangeFrom: document.getElementById('entradaCosturaFieldFrom'),
                rangeTo: document.getElementById('entradaCosturaFieldTo'),
            };
            const inputs = {
                day: document.getElementById('entradaCosturaFecha'),
                month: document.getElementById('entradaCosturaMes'),
                year: document.getElementById('entradaCosturaAnio'),
                rangeFrom: document.getElementById('entradaCosturaDesde'),
                rangeTo: document.getElementById('entradaCosturaHasta'),
            };

            if (!form || !filtro) {
                return;
            }

            function ocultarCampos() {
                Object.values(campos).forEach((campo) => {
                    if (campo) {
                        campo.classList.add('entrada-costura-filter-field-hidden');
                    }
                });
            }

            function mostrarCampos() {
                ocultarCampos();
                const valor = filtro.value;

                if (valor === 'day') {
                    campos.day?.classList.remove('entrada-costura-filter-field-hidden');
                } else if (valor === 'month') {
                    campos.month?.classList.remove('entrada-costura-filter-field-hidden');
                } else if (valor === 'year') {
                    campos.year?.classList.remove('entrada-costura-filter-field-hidden');
                } else if (valor === 'range') {
                    campos.rangeFrom?.classList.remove('entrada-costura-filter-field-hidden');
                    campos.rangeTo?.classList.remove('entrada-costura-filter-field-hidden');
                }
            }

            filtro.addEventListener('change', mostrarCampos);

            limpiar?.addEventListener('click', function () {
                filtro.value = 'all';
                if (search) {
                    search.value = '';
                }
                Object.values(inputs).forEach((input) => {
                    if (input) {
                        input.value = '';
                    }
                });
                mostrarCampos();
                form.submit();
            });

            mostrarCampos();
        })();
    </script>
@endpush
