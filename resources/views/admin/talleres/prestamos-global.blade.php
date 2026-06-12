@extends('layouts.base')

@section('title', 'Préstamos Confirmados')
@section('page-title', 'Préstamos Confirmados')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/top-nav.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modulos/talleres/talleres-admin.css') }}?v={{ time() }}">
@endpush

@section('body')
    <!-- Custom Header for Prestamos -->
    <header style="position:sticky;top:0;z-index:220;background:#fff;border-bottom:1px solid #e2e8f0;padding:16px 32px;margin-left:250px;display:flex;justify-content:space-between;align-items:center;transition:margin-left 0.25s ease;gap:20px;">
        <h1 style="margin:0;font-size:24px;font-weight:700;color:#0f172a;white-space:nowrap;">{{ $tab === 'insumos' ? 'Préstamos de Insumos' : 'Préstamos de Contramuestras' }}</h1>
        
        <div class="gooey-search-wrapper" style="flex:1;max-width:400px;">
            <input type="text" id="searchInput" class="gooey-search-input" placeholder="Buscar por recibo, taller..." 
                   value="{{ request('search', '') }}">
            <span class="material-symbols-rounded gooey-search-icon">search</span>
            <button type="button" id="clearSearchBtn" class="gooey-search-clear">
                <span class="material-symbols-rounded">close</span>
            </button>
        </div>
        
        <div style="white-space:nowrap;">
            <span id="totalRegistros" style="font-size:13px;color:#64748b;background:#f1f5f9;padding:6px 12px;border-radius:8px;">{{ $registros->total() }} registros</span>
        </div>
    </header>

    <style>
        body.talleres-sidebar-collapsed header[style*="margin-left:250px"] {
            margin-left: 84px;
        }
        .prestamo-confirmar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 36px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.15s ease;
        }
        .prestamo-confirmar-toggle:hover {
            transform: translateY(-1px);
        }
        .prestamo-confirmar-toggle.is-confirmed {
            background: #dcfce7;
            color: #166534;
            border-color: #86efac;
            cursor: default;
        }
        .prestamo-confirmar-toggle .material-symbols-rounded {
            font-size: 18px;
            line-height: 1;
        }
        .prestamo-novedad-button {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            color: #0f172a;
            text-align: center;
            padding: 0.58rem 0.7rem;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            line-height: 1.45;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.2s ease, background-color 0.2s ease;
        }
        .prestamo-novedad-button:hover {
            border-color: #94a3b8;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .prestamo-novedad-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }
        .prestamo-novedad-button .material-symbols-rounded {
            font-size: 18px;
            line-height: 1;
            color: #0369a1;
            flex: 0 0 auto;
        }
        .modal-confirmar-entrada-admin {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            z-index: 3000;
        }
        .modal-confirmar-entrada-admin .modal-card {
            width: min(92vw, 540px);
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            padding: 16px;
        }
        .modal-confirmar-entrada-admin textarea {
            width: 100%;
            min-height: 96px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
            resize: vertical;
        }
        .modal-confirmar-entrada-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }
        .modal-confirmar-btn {
            border: 0;
            border-radius: 10px;
            padding: 9px 14px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
        }
        .modal-confirmar-btn-cancel {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .modal-confirmar-btn-confirm {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
            color: #fff;
            box-shadow: 0 8px 18px rgba(21, 128, 61, 0.3);
        }
        .modal-prestamo-novedades {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.48);
            z-index: 3100;
        }
        .modal-prestamo-novedades .modal-card {
            width: min(92vw, 720px);
            max-height: min(86vh, 760px);
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.24);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .modal-prestamo-novedades-body {
            display: grid;
            gap: 14px;
            padding: 16px;
            overflow: auto;
        }
        .modal-prestamo-novedades-lista {
            display: grid;
            gap: 10px;
            max-height: 280px;
            overflow: auto;
            padding-right: 4px;
        }
        .novedad-item {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px;
        }
        .novedad-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
        }
        .novedad-item-text {
            font-size: 13px;
            color: #0f172a;
            white-space: pre-wrap;
            line-height: 1.45;
        }
        .modal-prestamo-novedades-form {
            display: grid;
            gap: 10px;
            border-top: 1px solid #e2e8f0;
            padding-top: 14px;
        }
        .modal-prestamo-novedades-form textarea {
            width: 100%;
            min-height: 110px;
            resize: vertical;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            color: #0f172a;
        }
        .modal-prestamo-novedades-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>

    <!-- Sidebar Navigation -->
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
            @if(auth()->user()->hasRole('visualizador_talleres'))
                <!-- Menú anidado para visualizador_talleres -->
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle" id="navTalleresGroup">
                        <span class="material-symbols-rounded">factory</span>
                        <span class="nav-label">Talleres</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu" id="talleresSubmenu">
                        <a href="{{ route('talleres.index', ['status' => 'activos']) }}" class="sidebar-item sidebar-subitem" data-view="viewTalleres" data-status="activos" id="navTalleres">
                            <span class="nav-label">Activos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['status' => 'inactivos']) }}" class="sidebar-item sidebar-subitem" data-view="viewTalleres" data-status="inactivos" id="navTalleresInactivos">
                            <span class="nav-label">Inactivos</span>
                        </a>
                        <a href="{{ route('talleres.index', ['view' => 'ordenes']) }}" class="sidebar-item sidebar-subitem" data-view="viewOrdenes" id="navOrdenes">
                            <span class="nav-label">Órdenes</span>
                        </a>
                    </div>
                </div>
                <div class="sidebar-group">
                    <button type="button" class="sidebar-item sidebar-group-toggle" id="navPrestamosGroup">
                        <span class="material-symbols-rounded">payment</span>
                        <span class="nav-label">Préstamos</span>
                        <span class="material-symbols-rounded expand-icon">expand_more</span>
                    </button>
                    <div class="sidebar-submenu collapsed" id="prestamosSubmenu">
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'insumos']) }}" class="sidebar-item sidebar-subitem {{ $tab === 'insumos' ? 'active' : '' }}" id="navPrestamosInsumos">
                            <span class="nav-label">Insumos</span>
                        </a>
                        <a href="{{ route('talleres.prestamos-global', ['tab' => 'contramuestra']) }}" class="sidebar-item sidebar-subitem {{ $tab === 'contramuestra' ? 'active' : '' }}" id="navPrestamosContramuestras">
                            <span class="nav-label">Contramuestras</span>
                        </a>
                    </div>
                </div>
                <a href="{{ route('entrada.index') }}"
                   class="sidebar-item {{ request()->routeIs('entrada.*') ? 'active' : '' }}"
                   id="navEntradaCostura"
                   aria-label="Ir a Entrada Costura">
                    <span class="material-symbols-rounded">assignment_return</span>
                    <span class="nav-label">Entrada Costura</span>
                </a>
                <a href="{{ route('seguimiento-lavanderia.index') }}"
                   class="sidebar-item"
                   aria-label="Ir a Lavandería">
                    <span class="material-symbols-rounded">local_laundry_service</span>
                    <span class="nav-label">Lavandería</span>
                </a>
            @endif
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-container" data-csrf-token="{{ csrf_token() }}" data-current-user-id="{{ $currentUserId ?? auth()->id() }}">
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;">
                <thead style="background:#f8fafc;">
                    <tr>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Acciones</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">N° Recibo</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Costurero/Taller</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha salida</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Fecha entrada</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Estado</th>
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #e2e8f0;">Novedad</th>
                    </tr>
                </thead>
                <tbody id="prestamosTableBody">
                @forelse($registros as $r)
                    <tr data-prestamo-id="{{ $r->id }}" @if($r->confirmado_entrada) style="background:#dcfce7;" @endif>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap;white-space:nowrap;">
                                <button type="button"
                                        class="prestamo-confirmar-toggle {{ $r->confirmado_entrada ? 'is-confirmed' : '' }}"
                                        data-action="confirmar-entrada"
                                        data-tipo="{{ $tab }}"
                                        data-id="{{ $r->id }}"
                                        data-confirmed="{{ $r->confirmado_entrada ? '1' : '0' }}"
                                        data-url="{{ route('talleres.api.prestamos-global.confirmar-entrada', ['tipo' => $tab, 'id' => $r->id]) }}"
                                        title="{{ $r->confirmado_entrada ? 'Deshacer entrada' : 'Confirmar entrada' }}">
                                    <span class="material-symbols-rounded" style="font-size:18px;line-height:1;">done</span>
                                </button>
                                <button type="button" class="btn-ver-prestamo" data-tipo="{{ $tab }}" data-id="{{ $r->id }}" style="background:#3b82f6;color:#fff;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;">Ver</button>
                            </div>
                        </td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">#{{ $r->numero_orden }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->nombre_costurero }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ \Carbon\Carbon::parse($r->fecha_salida)->format('d/m/Y h:i A') }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">{{ $r->fecha_entrada ? \Carbon\Carbon::parse($r->fecha_entrada)->format('d/m/Y h:i A') : '-' }}</td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                            @if($r->anulado)
                                <span style="background:#fee2e2;color:#dc2626;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">ANULADO</span>
                            @elseif($r->confirmado_entrada)
                                <span style="background:#dcfce7;color:#16a34a;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">CONFIRMADO</span>
                            @else
                                <span style="background:#fef3c7;color:#ea8c55;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600;">PENDIENTE</span>
                            @endif
                        </td>
                        <td style="padding:10px;border-bottom:1px solid #f1f5f9;">
                                <button type="button"
                                    class="prestamo-novedad-button"
                                    data-action="abrir-novedades"
                                    data-tipo="{{ $tab }}"
                                    data-id="{{ $r->id }}"
                                    data-url="{{ route('talleres.api.prestamos-global.novedades', ['tipo' => $tab, 'id' => $r->id]) }}"
                                    data-save-url="{{ route('talleres.api.prestamos-global.novedades.guardar', ['tipo' => $tab, 'id' => $r->id]) }}">
                                <span class="material-symbols-rounded" aria-hidden="true" style="font-size:18px;">ads_click</span>
                                <span>Ver novedades</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="padding:16px;text-align:center;color:#64748b;">Sin registros de préstamos confirmados.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:10px;" id="paginationContainer">
            {{ $registros->links('vendor.pagination.simple-clean') }}
        </div>
    </main>

    <!-- Modal para ver detalles del préstamo -->
    <div id="modal-overlay"
         style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: none;"
         onclick="closeModalOverlay()"></div>
    <div id="order-detail-modal-wrapper"
         style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
        <x-orders-components.order-detail-modal />
    </div>
    <div id="modal-confirmar-entrada-admin" class="modal-confirmar-entrada-admin">
        <div class="modal-card">
            <h3 style="margin:0 0 8px;">Confirmar Entrada</h3>
            <p style="margin:0 0 10px;font-size:13px;color:#475569;">¿Estás seguro de que este préstamo corresponde con lo entregado?</p>
            <label style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                <input type="checkbox" id="entrada-no-corresponde-admin">
                <span style="font-size:13px;">No corresponde</span>
            </label>
            <textarea id="entrada-novedad-admin" placeholder="Escribe la novedad si no corresponde..."></textarea>
            <div class="modal-confirmar-entrada-actions">
                <button type="button" id="btn-cancelar-entrada-admin" class="modal-confirmar-btn modal-confirmar-btn-cancel">Cancelar</button>
                <button type="button" id="btn-guardar-entrada-admin" class="modal-confirmar-btn modal-confirmar-btn-confirm">Confirmar</button>
            </div>
        </div>
    </div>
    <div id="modal-prestamo-novedades" class="modal-prestamo-novedades">
        <div class="modal-card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:16px 16px 0;">
                <div>
                    <h3 id="modal-prestamo-novedades-title" style="margin:0 0 6px;">Novedades del préstamo</h3>
                    <p id="modal-prestamo-novedades-subtitle" style="margin:0;color:#64748b;font-size:13px;">Registra una nota con fecha, hora y usuario.</p>
                </div>
                <button type="button" id="btn-cerrar-novedades" class="modal-confirmar-btn modal-confirmar-btn-cancel">Cerrar</button>
            </div>
            <div class="modal-prestamo-novedades-body">
                <div id="modal-prestamo-novedades-lista" class="modal-prestamo-novedades-lista"></div>
                <div class="modal-prestamo-novedades-form">
                    <textarea id="prestamo-novedad-texto" placeholder="Escribe una novedad..."></textarea>
                    <div class="modal-prestamo-novedades-actions">
                        <button type="button" id="btn-guardar-novedad" class="modal-confirmar-btn modal-confirmar-btn-confirm">Guardar novedad</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        #order-detail-modal-wrapper #order-pedido {
            transform: translateY(20px) !important;
        }
        #order-detail-modal-wrapper #btn-galeria {
            display: none !important;
        }
        #order-detail-modal-wrapper #btn-factura {
            display: none !important;
        }
        #floating-buttons-container {
            position: fixed !important;
            right: 30px !important;
            top: 50% !important;
            left: auto !important;
            transform: translateY(-50%) !important;
            z-index: 10000 !important;
        }
    </style>

    <script src="{{ asset('js/ordersjs/order-detail-modal-manager.js') }}"></script>
    <script src="{{ asset('js/modulos/talleres/prestamo-modal-handler.js') }}"></script>
    <script src="{{ asset('js/modulos/talleres/talleres-admin.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearchBtn');
            const tab = '{{ $tab }}';
            const apiUrl = '{{ route("talleres.api.prestamos-global") }}';
            const modalConfirmarEntrada = document.getElementById('modal-confirmar-entrada-admin');
            const entradaNoCorresponde = document.getElementById('entrada-no-corresponde-admin');
            const entradaNovedad = document.getElementById('entrada-novedad-admin');
            const btnCancelarEntrada = document.getElementById('btn-cancelar-entrada-admin');
            const btnGuardarEntrada = document.getElementById('btn-guardar-entrada-admin');
            const entradaNoCorrespondeLabel = entradaNoCorresponde?.closest('label');
            const modalConfirmarEntradaTitle = modalConfirmarEntrada?.querySelector('h3');
            const modalConfirmarEntradaMessage = modalConfirmarEntrada?.querySelector('p');
            const modalNovedades = document.getElementById('modal-prestamo-novedades');
            const modalNovedadesLista = document.getElementById('modal-prestamo-novedades-lista');
            const modalNovedadesTitulo = document.getElementById('modal-prestamo-novedades-title');
            const modalNovedadesSubtitulo = document.getElementById('modal-prestamo-novedades-subtitle');
            const modalNovedadesTexto = document.getElementById('prestamo-novedad-texto');
            const btnCerrarNovedades = document.getElementById('btn-cerrar-novedades');
            const btnGuardarNovedad = document.getElementById('btn-guardar-novedad');
            const currentUserId = Number(document.querySelector('.main-container')?.dataset.currentUserId || 0);
            const debugPrefix = '[PrestamosGlobal]';
            let confirmContext = { url: '', accion: 'confirmar' };
            let novedadesContext = { url: '', saveUrl: '', tipo: '', id: '' };
            let novedadEnEdicionId = null;
            let novedadEnEdicionTexto = '';
            let currentSearchQuery = searchInput?.value || '';
            let currentPage = Number(new URLSearchParams(window.location.search).get('page') || 1);

            function showToast(message, type = 'info', ms = 2200) {
                const el = document.createElement('div');
                el.textContent = message;
                el.style.position = 'fixed';
                el.style.right = '14px';
                el.style.bottom = '14px';
                el.style.zIndex = '5000';
                el.style.minWidth = '240px';
                el.style.maxWidth = '320px';
                el.style.borderRadius = '12px';
                el.style.padding = '11px 12px';
                el.style.fontSize = '12.5px';
                el.style.fontWeight = '600';
                el.style.color = '#fff';
                el.style.boxShadow = '0 10px 24px rgba(15, 23, 42, 0.28)';
                el.style.opacity = '0';
                el.style.transform = 'translateY(12px)';
                el.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                el.style.pointerEvents = 'none';

                const backgrounds = {
                    success: 'linear-gradient(135deg, #16a34a 0%, #15803d 100%)',
                    error: 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
                    info: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
                };
                el.style.background = backgrounds[type] || backgrounds.info;

                document.body.appendChild(el);
                requestAnimationFrame(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                });
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(12px)';
                    setTimeout(() => el.remove(), 220);
                }, ms);
            }

            async function recargarTablaPrestamos() {
                const params = new URLSearchParams({
                    tab: tab,
                    search: currentSearchQuery || '',
                    page: String(currentPage || 1),
                });

                const response = await fetch(`${apiUrl}?${params}`);
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo actualizar la tabla.');
                }

                const tableBody = document.getElementById('prestamosTableBody');
                const paginationContainer = document.getElementById('paginationContainer');
                const totalRegistros = document.getElementById('totalRegistros');

                if (tableBody) {
                    tableBody.innerHTML = data.html || '';
                }
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination_html || '';
                }
                if (totalRegistros) {
                    totalRegistros.textContent = (data.total ?? 0) + ' registros';
                }

                clearBtn.style.display = currentSearchQuery ? 'block' : 'none';
                attachPrestamoRowListeners();
                attachPaginationListeners(apiUrl, tab, currentSearchQuery);
            }

            console.log(debugPrefix, 'init', {
                tab,
                currentUrl: window.location.href
            });

            document.querySelectorAll('.sidebar-item').forEach((item) => {
                item.addEventListener('click', function(event) {
                    console.log(debugPrefix, 'sidebar click', {
                        id: this.id || null,
                        tag: this.tagName,
                        href: this.getAttribute('href'),
                        dataView: this.dataset.view || null,
                        dataStatus: this.dataset.status || null,
                        defaultPrevented: event.defaultPrevented,
                        targetClasses: event.target?.className || null
                    });
                });
            });

            // Búsqueda con debounce
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                console.log(debugPrefix, 'search input', {
                    value: this.value
                });
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch(this.value);
                }, 300);
            });

            // Botón limpiar
            clearBtn.addEventListener('click', function() {
                console.log(debugPrefix, 'clear search');
                searchInput.value = '';
                performSearch('');
            });

            // Función para realizar búsqueda
            function performSearch(query) {
                console.log(debugPrefix, 'performSearch:start', {
                    query,
                    apiUrl
                });
                currentSearchQuery = query || '';
                currentPage = 1;
                const params = new URLSearchParams({
                    tab: tab,
                    search: query
                });

                fetch(`${apiUrl}?${params}`)
                    .then(response => response.json())
                    .then(data => {
                        console.log(debugPrefix, 'performSearch:success', {
                            query,
                            success: data.success,
                            total: data.total ?? null
                        });
                        if (data.success) {
                            document.getElementById('prestamosTableBody').innerHTML = data.html;
                            document.getElementById('totalRegistros').textContent = data.total + ' registros';
                            document.getElementById('paginationContainer').innerHTML = data.pagination_html;
                            clearBtn.style.display = query ? 'block' : 'none';
                            
                            // Re-attach button listeners and pagination
                            attachPrestamoRowListeners();
                            attachPaginationListeners(apiUrl, tab, query);
                        }
                    })
                    .catch(error => console.error(debugPrefix, 'performSearch:error', error));
            }

            // Función para attachar listeners a la paginación
            function attachPaginationListeners(apiUrl, tab, query) {
                const paginationLinks = document.querySelectorAll('#paginationContainer a');
                console.log(debugPrefix, 'attachPaginationListeners', {
                    count: paginationLinks.length,
                    query
                });
                paginationLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        console.log(debugPrefix, 'pagination click', {
                            href: this.href,
                            query
                        });
                        const page = new URL(this.href).searchParams.get('page');
                        currentPage = Number(page || 1);
                        currentSearchQuery = query || '';
                        const params = new URLSearchParams({
                            tab: tab,
                            search: query,
                            page: page || 1
                        });

                        fetch(`${apiUrl}?${params}`)
                            .then(response => response.json())
                            .then(data => {
                                console.log(debugPrefix, 'pagination success', {
                                    page: page || 1,
                                    success: data.success
                                });
                                if (data.success) {
                                    document.getElementById('prestamosTableBody').innerHTML = data.html;
                                    document.getElementById('paginationContainer').innerHTML = data.pagination_html;
                                    window.scrollTo(0, 0);
                                    attachPrestamoRowListeners();
                                    attachPaginationListeners(apiUrl, tab, query);
                                }
                            });
                    });
                });
            }

            function attachPrestamoRowListeners() {
                attachPrestamoBtnListeners();
                attachConfirmarEntradaListeners();
                attachNovedadesListeners();
            }

            function setConfirmarEntradaMode(accion) {
                const isUndo = accion === 'deshacer';
                if (modalConfirmarEntradaTitle) {
                    modalConfirmarEntradaTitle.textContent = isUndo ? 'Deshacer Entrada' : 'Confirmar Entrada';
                }
                if (modalConfirmarEntradaMessage) {
                    modalConfirmarEntradaMessage.textContent = isUndo
                        ? 'Vas a revertir la confirmación de este préstamo. ¿Deseas continuar?'
                        : '¿Estás seguro de que este préstamo corresponde con lo entregado?';
                }
                if (entradaNoCorrespondeLabel) {
                    entradaNoCorrespondeLabel.style.display = isUndo ? 'none' : 'flex';
                }
                if (entradaNovedad) {
                    entradaNovedad.style.display = isUndo ? 'none' : '';
                    entradaNovedad.value = '';
                }
                if (btnGuardarEntrada) {
                    btnGuardarEntrada.textContent = isUndo ? 'Deshacer' : 'Confirmar';
                }
            }

            function openConfirmarEntradaModal(url, accion = 'confirmar') {
                confirmContext = { url: url || '', accion };
                if (entradaNoCorresponde) {
                    entradaNoCorresponde.checked = false;
                }
                if (entradaNovedad) {
                    entradaNovedad.value = '';
                }
                setConfirmarEntradaMode(accion);
                if (modalConfirmarEntrada) {
                    modalConfirmarEntrada.style.display = 'flex';
                }
            }

            function closeConfirmarEntradaModal() {
                if (modalConfirmarEntrada) {
                    modalConfirmarEntrada.style.display = 'none';
                }
                confirmContext = { url: '', accion: 'confirmar' };
                if (entradaNoCorresponde) {
                    entradaNoCorresponde.checked = false;
                }
                if (entradaNovedad) {
                    entradaNovedad.value = '';
                }
                setConfirmarEntradaMode('confirmar');
            }

            function formatFechaHora(value) {
                if (!value) {
                    return '-';
                }

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return new Intl.DateTimeFormat('es-CO', {
                    dateStyle: 'short',
                    timeStyle: 'short',
                }).format(date);
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            }

            function renderNovedadesLista(items) {
                if (!modalNovedadesLista) {
                    return;
                }

                if (!Array.isArray(items) || items.length === 0) {
                    modalNovedadesLista.innerHTML = '<p style="margin:0;color:#64748b;font-size:13px;">Sin novedades registradas.</p>';
                    return;
                }

                modalNovedadesLista.innerHTML = items.map((item) => {
                    const novedadId = Number(item.id || 0);
                    const puedeEditar = currentUserId && Number(item.usuario_id || 0) === currentUserId;
                    const usuario = escapeHtml(item.usuario_nombre || 'Usuario desconocido');
                    const fecha = formatFechaHora(item.created_at);
                    const fechaEditada = formatFechaHora(item.updated_at);
                    const fueEditada = item.updated_at && item.created_at && new Date(item.updated_at).getTime() > new Date(item.created_at).getTime();
                    if (novedadEnEdicionId === novedadId) {
                        const textoEdicion = escapeHtml(novedadEnEdicionTexto || item.novedad || '');
                        return `
                            <article class="novedad-item" data-novedad-id="${novedadId}">
                                <div class="novedad-item-meta">
                                    <span>${usuario}</span>
                                    <span>${fecha}</span>
                                </div>
                                <textarea class="novedad-edit-textarea" data-novedad-edit-input="1" style="width:100%;min-height:92px;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:13px;color:#0f172a;resize:vertical;">${textoEdicion}</textarea>
                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:10px;flex-wrap:wrap;">
                                    <button type="button" class="modal-confirmar-btn modal-confirmar-btn-cancel" data-action="cancelar-editar-novedad" data-novedad-id="${novedadId}">Cancelar</button>
                                    <button type="button" class="modal-confirmar-btn modal-confirmar-btn-confirm" data-action="guardar-editar-novedad" data-novedad-id="${novedadId}">Guardar</button>
                                </div>
                            </article>
                        `;
                    }

                    const texto = escapeHtml(item.novedad || '');
                    const estadoEdicion = fueEditada ? `
                        <div style="margin-top:4px;font-size:11.5px;font-weight:700;color:#b45309;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <span class="material-symbols-rounded" style="font-size:14px;line-height:1;">edit</span>
                            <span>Editada</span>
                            <span style="font-weight:600;color:#64748b;">${fechaEditada}</span>
                        </div>
                    ` : '';
                    const acciones = puedeEditar ? `
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                            <button type="button" class="novedad-mini-action" data-action="editar-novedad" data-novedad-id="${novedadId}" style="border:1px solid #cbd5e1;background:#fff;color:#0f172a;border-radius:999px;padding:6px 10px;font-size:11.5px;font-weight:700;cursor:pointer;">
                                <span class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">edit</span>
                                Editar
                            </button>
                            <button type="button" class="novedad-mini-action" data-action="eliminar-novedad" data-novedad-id="${novedadId}" style="border:1px solid #fecaca;background:#fff;color:#b91c1c;border-radius:999px;padding:6px 10px;font-size:11.5px;font-weight:700;cursor:pointer;">
                                <span class="material-symbols-rounded" style="font-size:14px;vertical-align:middle;">delete</span>
                                Eliminar
                            </button>
                        </div>
                    ` : '';
                    return `
                        <article class="novedad-item" data-novedad-id="${novedadId}">
                            <div class="novedad-item-meta">
                                <span>${usuario}</span>
                                <span>${fecha}</span>
                            </div>
                            <div class="novedad-item-text">${texto}</div>
                            ${estadoEdicion}
                            ${acciones}
                        </article>
                    `;
                }).join('');
            }

            function setNovedadEdicion(itemId, texto = '') {
                novedadEnEdicionId = Number(itemId || 0) || null;
                novedadEnEdicionTexto = texto || '';
            }

            function obtenerRutaNovedad(novedadId, accion) {
                const baseUrl = novedadesContext.url || '';
                if (!baseUrl || !novedadId) {
                    return '';
                }

                return `${baseUrl}/${novedadId}`;
            }

            async function recargarNovedadesActuales() {
                if (!novedadesContext.url) {
                    return;
                }

                await cargarNovedadesPrestamo(
                    novedadesContext.url,
                    novedadesContext.saveUrl,
                    `Novedades del préstamo #${novedadesContext.id}`,
                    'Registra una nota con fecha, hora y usuario.'
                );
            }

            async function guardarNovedadEditada(novedadId, nuevoTexto) {
                const ruta = obtenerRutaNovedad(novedadId);
                if (!ruta) {
                    return;
                }

                const response = await fetch(ruta, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('.main-container')?.dataset.csrfToken || ''
                    },
                    body: JSON.stringify({ novedad: nuevoTexto })
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo actualizar la novedad.');
                }
            }

            async function eliminarNovedadGuardada(novedadId) {
                const ruta = obtenerRutaNovedad(novedadId);
                if (!ruta) {
                    return;
                }

                const response = await fetch(ruta, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('.main-container')?.dataset.csrfToken || ''
                    }
                });

                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'No se pudo eliminar la novedad.');
                }
            }

            function obtenerNovedadLocal(novedadId) {
                const lista = Array.isArray(novedadesContext.items) ? novedadesContext.items : [];
                return lista.find((item) => Number(item.id || 0) === Number(novedadId || 0)) || null;
            }

            async function cargarNovedadesPrestamo(url, saveUrl, titulo, subtitulo) {
                if (!modalNovedades) {
                    return;
                }

                modalNovedades.style.display = 'flex';
                if (modalNovedadesTitulo && titulo) {
                    modalNovedadesTitulo.textContent = titulo;
                }
                if (modalNovedadesSubtitulo && subtitulo) {
                    modalNovedadesSubtitulo.textContent = subtitulo;
                }
                if (modalNovedadesTexto) {
                    modalNovedadesTexto.value = '';
                }
                if (btnGuardarNovedad) {
                    btnGuardarNovedad.disabled = false;
                }
                setNovedadEdicion(null, '');
                if (modalNovedadesLista) {
                    modalNovedadesLista.innerHTML = '<p style="margin:0;color:#64748b;font-size:13px;">Cargando novedades...</p>';
                }

                try {
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('.main-container')?.dataset.csrfToken || ''
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'No se pudieron cargar las novedades.');
                    }

                    novedadesContext.url = url;
                    novedadesContext.saveUrl = saveUrl || url;
                    novedadesContext.items = data.novedades || [];
                    renderNovedadesLista(data.novedades || []);
                } catch (error) {
                    console.error(debugPrefix, 'cargarNovedades:error', error);
                    if (modalNovedadesLista) {
                        modalNovedadesLista.innerHTML = `<p style="margin:0;color:#b91c1c;font-size:13px;">${error.message || 'No se pudieron cargar las novedades.'}</p>`;
                    }
                }
            }

            function cerrarModalNovedades() {
                if (modalNovedades) {
                    modalNovedades.style.display = 'none';
                }
                novedadesContext = { url: '', saveUrl: '', tipo: '', id: '' };
                setNovedadEdicion(null, '');
                if (modalNovedadesTexto) {
                    modalNovedadesTexto.value = '';
                }
            }

            function attachConfirmarEntradaListeners() {
                document.querySelectorAll('[data-action="confirmar-entrada"]').forEach((button) => {
                    if (button.dataset.bound === '1') {
                        return;
                    }

                    button.dataset.bound = '1';
                    button.addEventListener('click', function() {
                        const accion = this.dataset.confirmed === '1' ? 'deshacer' : 'confirmar';
                        openConfirmarEntradaModal(this.dataset.url || '', accion);
                    });
                });
            }

            function attachNovedadesListeners() {
                document.querySelectorAll('[data-action="abrir-novedades"]').forEach((button) => {
                    if (button.dataset.boundNovedad === '1') {
                        return;
                    }

                    button.dataset.boundNovedad = '1';
                    button.addEventListener('click', function() {
                        const url = this.dataset.url || '';
                        const saveUrl = this.dataset.saveUrl || '';
                        const tipo = this.dataset.tipo || '';
                        const id = this.dataset.id || '';
                        novedadesContext = { url, saveUrl, tipo, id };
                        cargarNovedadesPrestamo(url, saveUrl, `Novedades del préstamo #${id}`, 'Registra una nota con fecha, hora y usuario.');
                    });
                });
            }

            btnCerrarNovedades?.addEventListener('click', cerrarModalNovedades);
            modalNovedades?.addEventListener('click', (event) => {
                if (event.target === modalNovedades) {
                    cerrarModalNovedades();
                }
            });

            modalNovedadesLista?.addEventListener('click', async (event) => {
                const actionButton = event.target.closest('[data-action]');
                if (!actionButton) {
                    return;
                }

                const novedadId = Number(actionButton.dataset.novedadId || 0);
                const action = actionButton.dataset.action || '';

                if (action === 'editar-novedad') {
                    const item = obtenerNovedadLocal(novedadId);
                    if (!item) {
                        return;
                    }

                    setNovedadEdicion(novedadId, item.novedad || '');
                    renderNovedadesLista(novedadesContext.items || []);
                    const input = modalNovedadesLista.querySelector('[data-novedad-edit-input="1"]');
                    if (input) {
                        input.focus();
                        input.setSelectionRange(input.value.length, input.value.length);
                    }
                    return;
                }

                if (action === 'cancelar-editar-novedad') {
                    setNovedadEdicion(null, '');
                    renderNovedadesLista(novedadesContext.items || []);
                    return;
                }

                if (action === 'guardar-editar-novedad') {
                    const textarea = actionButton.closest('.novedad-item')?.querySelector('[data-novedad-edit-input="1"]');
                    const nuevoTexto = (textarea?.value || '').trim();
                    if (!nuevoTexto) {
                        showToast('La novedad no puede quedar vacia.', 'error');
                        return;
                    }

                    try {
                        actionButton.disabled = true;
                        await guardarNovedadEditada(novedadId, nuevoTexto);
                        showToast('Novedad actualizada correctamente.', 'success', 1200);
                        setNovedadEdicion(null, '');
                        await recargarNovedadesActuales();
                    } catch (error) {
                        console.error(debugPrefix, 'actualizarNovedad:error', error);
                        showToast(error.message || 'No se pudo actualizar la novedad.', 'error');
                    } finally {
                        actionButton.disabled = false;
                    }
                    return;
                }

                if (action === 'eliminar-novedad') {
                    const item = obtenerNovedadLocal(novedadId);
                    if (!item) {
                        return;
                    }

                    if (!window.confirm('¿Seguro que deseas eliminar esta novedad?')) {
                        return;
                    }

                    try {
                        actionButton.disabled = true;
                        await eliminarNovedadGuardada(novedadId);
                        showToast('Novedad eliminada correctamente.', 'success', 1200);
                        setNovedadEdicion(null, '');
                        await recargarNovedadesActuales();
                    } catch (error) {
                        console.error(debugPrefix, 'eliminarNovedad:error', error);
                        showToast(error.message || 'No se pudo eliminar la novedad.', 'error');
                    } finally {
                        actionButton.disabled = false;
                    }
                }
            });

            btnGuardarNovedad?.addEventListener('click', async () => {
                if (!novedadesContext.saveUrl || !modalNovedadesTexto) {
                    return;
                }

                const novedad = modalNovedadesTexto.value.trim();
                if (!novedad) {
                    showToast('Escribe una novedad antes de guardar.', 'error');
                    return;
                }

                try {
                    btnGuardarNovedad.disabled = true;
                    const response = await fetch(novedadesContext.saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('.main-container')?.dataset.csrfToken || ''
                        },
                        body: JSON.stringify({ novedad })
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'No se pudo registrar la novedad.');
                    }

                    showToast('Novedad registrada correctamente.', 'success', 1200);
                    await cargarNovedadesPrestamo(novedadesContext.url, novedadesContext.saveUrl, `Novedades del préstamo #${novedadesContext.id}`, 'Registra una nota con fecha, hora y usuario.');
                } catch (error) {
                    console.error(debugPrefix, 'guardarNovedad:error', error);
                    showToast(error.message || 'No se pudo registrar la novedad.', 'error');
                } finally {
                    btnGuardarNovedad.disabled = false;
                }
            });

            btnCancelarEntrada?.addEventListener('click', closeConfirmarEntradaModal);
            modalConfirmarEntrada?.addEventListener('click', (event) => {
                if (event.target === modalConfirmarEntrada) {
                    closeConfirmarEntradaModal();
                }
            });

            btnGuardarEntrada?.addEventListener('click', async () => {
                if (!confirmContext.url) {
                    return;
                }

                const novedadValue = (entradaNovedad?.value || '').trim();
                const corresponde = confirmContext.accion === 'deshacer' ? true : !(entradaNoCorresponde?.checked);
                const payload = {
                    corresponde,
                    accion: confirmContext.accion,
                    novedades: confirmContext.accion === 'deshacer'
                        ? null
                        : (corresponde ? (novedadValue || null) : (novedadValue || 'No aplica'))
                };

                try {
                    const response = await fetch(confirmContext.url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('.main-container')?.dataset.csrfToken || ''
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'No se pudo actualizar la entrada.');
                    }

                    const accionActual = confirmContext.accion;
                    closeConfirmarEntradaModal();
                    showToast(accionActual === 'deshacer' ? 'Entrada deshecha correctamente.' : 'Entrada confirmada correctamente.', 'success', 1200);
                    await recargarTablaPrestamos();
                } catch (error) {
                    console.error(debugPrefix, 'confirmarEntrada:error', error);
                    showToast(error.message || 'No se pudo actualizar la entrada.', 'error');
                }
            });

            attachPrestamoRowListeners();
            attachPaginationListeners(apiUrl, tab, searchInput.value || '');
        });
    </script>
