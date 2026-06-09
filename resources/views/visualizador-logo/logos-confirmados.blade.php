@extends('layouts.visualizador-logo')

@section('title', 'Logos Confirmados')

@section('page-title', 'Logos Confirmados y Devueltos')

@push('styles')
<style>
    #modal-historial-novedades-overlay .table-talleres {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
    }

    #modal-historial-novedades-overlay .table-talleres thead th {
        padding: 14px 16px;
        text-align: left;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        color: #475569;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        position: sticky;
        top: 0;
        z-index: 1;
    }

    #modal-historial-novedades-overlay .table-talleres tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        font-size: 14px;
        vertical-align: middle;
    }

    #modal-historial-novedades-overlay .table-talleres tbody tr:last-child td {
        border-bottom: none;
    }

    #modal-historial-novedades-overlay .table-talleres tbody tr:hover {
        background: #f8fafc;
    }
</style>
@endpush

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 1200px;">
            <!-- TABS + HISTORIAL -->
            <div style="display: flex; gap: 12px; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap;">
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <button id="btn-tab-confirmados" type="button" data-tab="confirmados" onclick="setTabLogos('confirmados')" style="
                    position: relative;
                    padding: 10px 14px;
                    border-radius: 10px;
                    border: 2px solid #0ea5e9;
                    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                    color: white;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.2s;
                    box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);
                ">
                    <i class="fas fa-check-circle" style="margin-right: 6px;"></i>
                    LOGOS CONFIRMADOS
                    <span id="badge-confirmados" class="conteo-pendiente-badge" style="
                        position: absolute;
                        top: -8px;
                        right: -8px;
                        background: #ef4444;
                        color: white;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.75rem;
                        font-weight: 700;
                        border: 2px solid white;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                        display: none;
                    ">0</span>
                </button>
                <button id="btn-tab-devueltos" type="button" data-tab="devueltos" onclick="setTabLogos('devueltos')" style="
                    position: relative;
                    padding: 10px 14px;
                    border-radius: 10px;
                    border: 2px solid #e2e8f0;
                    background: white;
                    color: #334155;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.2s;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                ">
                    <i class="fas fa-undo" style="margin-right: 6px;"></i>
                    DEVUELTOS A DISEÑO
                    <span id="badge-devueltos" class="conteo-pendiente-badge" style="
                        position: absolute;
                        top: -8px;
                        right: -8px;
                        background: #ef4444;
                        color: white;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.75rem;
                        font-weight: 700;
                        border: 2px solid white;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                        display: none;
                    ">0</span>
                </button>
                </div>

                <button id="btn-historial-novedades" type="button" title="Historial de novedades" onclick="window.__abrirHistorialNovedadesLogos()" style="
                    background: white;
                    border: 2px solid #e2e8f0;
                    color: #334155;
                    padding: 10px 14px;
                    border-radius: 10px;
                    cursor: pointer;
                    font-weight: 700;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                    transition: all 0.2s;
                " onmouseover="this.style.borderColor='#0ea5e9'; this.style.color='#0ea5e9';" onmouseout="this.style.borderColor='#e2e8f0'; this.style.color='#334155';">
                    <i class="fas fa-history" style="font-size: 1.1rem;"></i>
                    Historial
                </button>
            </div>

            <div style="background: white; border-radius: 0 0 12px 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; border-top: none;">
                <div style="
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    display: grid;
                    grid-template-columns: 100px 300px 1fr 140px;
                    gap: 1.5rem;
                    font-weight: 700;
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                ">
                    <div style="color: #cbd5e1;">Recibo</div>
                    <div style="color: #cbd5e1;">Cliente</div>
                    <div style="color: #cbd5e1;">Prenda</div>
                    <div style="text-align: center; color: #cbd5e1;">Acción</div>
                </div>

                <div id="disenos-logo-body">
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                        <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando logos...</p>
                    </div>
                </div>
            </div>

            <div id="disenos-logo-paginacion" style="margin-top: 1.5rem; text-align: center;"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let paginaActual = 1;
    let searchTimeout;
    let tabActivo = 'confirmados';

    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    const btnTabConfirmados = document.getElementById('btn-tab-confirmados');
    const btnTabDevueltos = document.getElementById('btn-tab-devueltos');

    cargarDisenos();

    window.__refrescarVistaLogosConfirmados = function() {
        const term = searchInput ? searchInput.value.trim() : '';
        cargarDisenos(term);
    };

    window.__actualizarBadgesTabsLogosConfirmados = function(conteoNoRevisados) {
        const badgeConfirmados = document.getElementById('badge-confirmados');
        const badgeDevueltos = document.getElementById('badge-devueltos');

        if (badgeConfirmados && conteoNoRevisados) {
            const cantidad = conteoNoRevisados.confirmados || 0;
            badgeConfirmados.textContent = String(cantidad);
            badgeConfirmados.style.display = cantidad > 0 ? 'flex' : 'none';
        }

        if (badgeDevueltos && conteoNoRevisados) {
            const cantidad = conteoNoRevisados.devueltos || 0;
            badgeDevueltos.textContent = String(cantidad);
            badgeDevueltos.style.display = cantidad > 0 ? 'flex' : 'none';
        }
    };

    // Función global para cambiar tabs
    window.setTabLogos = function(nuevoTab) {
        const tabsValidos = ['confirmados', 'devueltos'];
        if (!tabsValidos.includes(nuevoTab)) return;
        
        tabActivo = nuevoTab;
        paginaActual = 1;
        actualizarTabUI();
        cargarDisenos(searchInput ? searchInput.value.trim() : '');
    };

    function actualizarTabUI() {
        if (btnTabConfirmados) {
            if (tabActivo === 'confirmados') {
                btnTabConfirmados.style.background = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
                btnTabConfirmados.style.borderColor = '#0ea5e9';
                btnTabConfirmados.style.color = 'white';
                btnTabConfirmados.style.boxShadow = '0 2px 8px rgba(14, 165, 233, 0.18)';
            } else {
                btnTabConfirmados.style.background = 'white';
                btnTabConfirmados.style.borderColor = '#e2e8f0';
                btnTabConfirmados.style.color = '#334155';
                btnTabConfirmados.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.06)';
            }
        }

        if (btnTabDevueltos) {
            if (tabActivo === 'devueltos') {
                btnTabDevueltos.style.background = 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)';
                btnTabDevueltos.style.borderColor = '#0ea5e9';
                btnTabDevueltos.style.color = 'white';
                btnTabDevueltos.style.boxShadow = '0 2px 8px rgba(14, 165, 233, 0.18)';
            } else {
                btnTabDevueltos.style.background = 'white';
                btnTabDevueltos.style.borderColor = '#e2e8f0';
                btnTabDevueltos.style.color = '#334155';
                btnTabDevueltos.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.06)';
            }
        }
    }

    // Inicializar UI
    actualizarTabUI();

    if (searchInput) {
        searchInput.placeholder = 'Buscar por cliente, recibo, prenda o novedad...';

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();

            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm ? 'block' : 'none';
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                paginaActual = 1;
                cargarDisenos(searchTerm);
            }, 300);
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            this.style.display = 'none';
            paginaActual = 1;
            cargarDisenos('');
        });
    }

    function cargarDisenos(searchTerm = '') {
        const params = new URLSearchParams({
            page: paginaActual,
            per_page: 20,
            tab: tabActivo,
        });

        if (searchTerm) {
            params.append('search', searchTerm);
        }

        fetch(`{{ route('visualizador-logo.logos-confirmados.data') }}?${params.toString()}`)
            .then(r => r.json())
            .then(json => {
                if (!json || json.success !== true) {
                    throw new Error('Respuesta inválida');
                }

                // Actualizar badges en sidebar si es necesario
                if (typeof window.__actualizarBadgeLogos === 'function' && json.conteo_no_revisados) {
                    window.__actualizarBadgeLogos(json.conteo_no_revisados.total || 0);
                }

                if (json.conteo_no_revisados) {
                    window.__actualizarBadgesTabsLogosConfirmados(json.conteo_no_revisados);
                }

                renderizarDisenos(json.items, searchTerm);
            })
            .catch(() => {
                mostrarError();
            });
    }

    function marcarDisenoComoRevisado(disenoId) {
        fetch(`{{ route('visualizador-logo.logos-confirmados.marcar-revisado', 0) }}`.replace('/0/', `/${disenoId}/`), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(json => {
            if (json && json.success === true) {
                const term = searchInput ? searchInput.value.trim() : '';
                cargarDisenos(term);
            }
        });
    }

    function renderizarDisenos(paginacion, searchTerm = '') {
        const body = document.getElementById('disenos-logo-body');
        const pagContainer = document.getElementById('disenos-logo-paginacion');

        if (!body) return;

        const data = paginacion && paginacion.data ? paginacion.data : [];

        if (data.length === 0) {
            body.innerHTML = `
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-size: 1rem; font-weight: 500;">
                        ${searchTerm ? 'No se encontraron logos para tu búsqueda' : (tabActivo === 'confirmados' ? 'No hay logos confirmados' : 'No hay logos devueltos a diseño')}
                    </p>
                </div>
            `;
            if (pagContainer) pagContainer.innerHTML = '';
            return;
        }

        body.innerHTML = data.map((group) => {
            const cliente = group.cliente || '-';
            const numeroRecibo = group.numero_recibo || '-';
            const prenda = group.nombre_prenda || '-';
            const logosCount = group.logos.length;
            const todosRevisados = group.todos_revisados;

            const clienteHtml = escapeHtml(cliente);
            const numeroReciboHtml = escapeHtml(numeroRecibo);
            const prendaHtml = escapeHtml(prenda);

            return `
                <div style="
                    display: grid;
                    grid-template-columns: 100px 300px 1fr 200px;
                    gap: 1.5rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: ${todosRevisados ? '#f0fdf4' : 'white'};
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='${todosRevisados ? '#dcfce7' : '#f8fafc'}'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='${todosRevisados ? '#f0fdf4' : 'white'}'; this.style.boxShadow='none'">
                    <div style="color: ${todosRevisados ? '#16a34a' : '#1e40af'}; font-size: 0.95rem; font-weight: 700; display:flex; align-items:center; gap: 8px;" title="${numeroReciboHtml}">
                        ${todosRevisados ? '<i class="fas fa-check-circle" style="color:#22c55e;"></i>' : ''}
                        #${numeroReciboHtml}
                    </div>
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${clienteHtml}">${clienteHtml}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${prendaHtml}">${prendaHtml}</div>
                    <div style="display:flex; justify-content:center; gap: 10px;">
                        <button type="button" onclick='window.__verDisenoLogo(${JSON.stringify(group.logos).replace(/'/g, "\\'")})' title="Ver ${logosCount} logo(s)" style="
                            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                            border: none;
                            color: white;
                            padding: 10px 12px;
                            border-radius: 10px;
                            cursor: pointer;
                            font-weight: 800;
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);
                        ">
                            <i class="fas fa-images"></i>
                            Ver ${logosCount}
                        </button>
                        ${!todosRevisados ? `
                            <button type="button" onclick='window.__marcarGrupoRevisado(${JSON.stringify(group.logos).replace(/'/g, "\\'")})' title="Marcar todos como revisados" style="
                                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                                border: none;
                                color: white;
                                padding: 10px 12px;
                                border-radius: 10px;
                                cursor: pointer;
                                font-weight: 800;
                                display: inline-flex;
                                align-items: center;
                                gap: 0px;
                                box-shadow: 0 2px 8px rgba(34, 197, 94, 0.18);
                            ">
                                <i class="fas fa-check-double"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        if (pagContainer) {
            pagContainer.innerHTML = construirPaginacion(paginacion);
        }
    }
    
    window.__marcarGrupoRevisado = function(logosArray) {
        const logos = Array.isArray(logosArray) ? logosArray : [];
        logos.forEach(logo => {
            if (!logo.revisada) {
                marcarDisenoComoRevisado(logo.id);
            }
        });
    };

    function mostrarError() {
        const body = document.getElementById('disenos-logo-body');
        if (!body) return;
        body.innerHTML = `
            <div style="padding: 3rem 2rem; text-align: center; color: #dc2626; background: #fef2f2;">
                <i class="fas fa-triangle-exclamation" style="font-size: 2.5rem; color: #dc2626; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1rem; font-weight: 700;">Error cargando logos</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #7f1d1d;">Intenta recargar la página</p>
            </div>
        `;
    }

    function construirPaginacion(paginacion) {
        if (!paginacion || !Array.isArray(paginacion.links)) return '';

        const links = paginacion.links;
        const html = links.map((l) => {
            const isActive = l.active;
            const isDisabled = !l.url;
            const label = l.label
                .replace('&laquo;', '«')
                .replace('&raquo;', '»');

            const baseStyle = `
                color: ${isActive ? 'white' : (isDisabled ? '#cbd5e1' : '#0ea5e9')};
                text-decoration: none;
                padding: 0.5rem 0.8rem;
                border: 2px solid ${isActive ? '#0ea5e9' : '#e2e8f0'};
                border-radius: 6px;
                transition: all 0.3s;
                font-weight: 700;
                display:inline-block;
                background: ${isActive ? 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)' : 'white'};
                opacity: ${isDisabled ? '0.5' : '1'};
                cursor: ${isDisabled ? 'not-allowed' : 'pointer'};
            `;

            if (isDisabled) {
                return `<span style="${baseStyle}">${label}</span>`;
            }

            return `<a href="#" style="${baseStyle}" onclick="window.__irPaginaDisenosLogo(${l.url ? new URL(l.url).searchParams.get('page') : '1'}); return false;">${label}</a>`;
        }).join(' ');

        return `<div style="display:flex; gap: 0.5rem; justify-content:center; flex-wrap: wrap;">${html}</div>`;
    }

    window.__irPaginaDisenosLogo = function(page) {
        const p = parseInt(page || '1');
        if (Number.isNaN(p) || p < 1) return;
        paginaActual = p;
        const term = searchInput ? searchInput.value.trim() : '';
        cargarDisenos(term);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    window.__abrirHistorialNovedadesLogos = function() {
        const existing = document.getElementById('modal-historial-novedades-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'modal-historial-novedades-overlay';
        overlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 999998; display:flex; align-items:center; justify-content:center; padding: 16px;';

        const modal = document.createElement('div');
        modal.style.cssText = 'width: 100%; max-width: 900px; background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden; max-height: 85vh; display: flex; flex-direction: column;';

        modal.innerHTML = `
            <div style="padding: 14px 16px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center; flex-shrink: 0;">
                <span><i class="fas fa-history" style="margin-right: 8px;"></i>Historial de Novedades</span>
                <button id="modal-historial-novedades-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:34px; height:34px; border-radius: 10px; cursor:pointer;">×</button>
            </div>
            <div id="modal-historial-novedades-toolbar" style="display: none; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0;">
                <div style="position: relative; max-width: 320px;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.9rem;"></i>
                    <input id="modal-historial-novedades-search" type="text" placeholder="Buscar por número de recibo..." style="
                        width: 100%;
                        padding: 10px 36px 10px 36px;
                        border: 2px solid #e2e8f0;
                        border-radius: 10px;
                        font-size: 0.9rem;
                        font-weight: 500;
                        color: #334155;
                        background: white;
                        box-sizing: border-box;
                        outline: none;
                    " onfocus="this.style.borderColor='#0ea5e9'" onblur="this.style.borderColor='#e2e8f0'">
                    <button id="modal-historial-novedades-clear" type="button" title="Limpiar búsqueda" style="
                        display: none;
                        position: absolute;
                        right: 8px;
                        top: 50%;
                        transform: translateY(-50%);
                        border: none;
                        background: transparent;
                        color: #94a3b8;
                        cursor: pointer;
                        font-size: 1rem;
                        padding: 4px;
                    ">×</button>
                </div>
            </div>
            <div id="modal-historial-novedades-body" style="overflow-y: auto; flex: 1;">
                <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                    <p style="margin: 0; font-weight: 500;">Cargando historial...</p>
                </div>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const cerrar = () => overlay.remove();
        modal.querySelector('#modal-historial-novedades-close').addEventListener('click', cerrar);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) cerrar();
        });

        const onKey = (e) => {
            if (!document.getElementById('modal-historial-novedades-overlay')) {
                document.removeEventListener('keydown', onKey);
                return;
            }
            if (e.key === 'Escape') {
                cerrar();
                document.removeEventListener('keydown', onKey);
            }
        };
        document.addEventListener('keydown', onKey);

        function historialTipoBadge(tipoNovedad) {
            const tipo = String(tipoNovedad || '').toLowerCase();
            if (tipo === 'confirmado') {
                return {
                    label: 'Confirmación',
                    bg: '#dcfce7',
                    color: '#166534',
                    border: '#86efac',
                    icon: 'fa-check-circle',
                };
            }
            if (tipo === 'devuelto') {
                return {
                    label: 'Devolución',
                    bg: '#ffedd5',
                    color: '#c2410c',
                    border: '#fdba74',
                    icon: 'fa-undo',
                };
            }
            if (tipo === 'reemplazo_imagen') {
                return {
                    label: 'Reemplazo',
                    bg: '#e0f2fe',
                    color: '#0369a1',
                    border: '#7dd3fc',
                    icon: 'fa-image',
                };
            }
            return {
                label: 'Otro',
                bg: '#f1f5f9',
                color: '#475569',
                border: '#cbd5e1',
                icon: 'fa-info-circle',
            };
        }

        function renderHistorialNovedadesRows(items, searchTerm = '') {
            const term = String(searchTerm || '').trim().toLowerCase();
            const filtered = term
                ? items.filter((item) => String(item.numero_recibo || '').toLowerCase().includes(term))
                : items;

            if (filtered.length === 0) {
                return `
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b;">
                        <i class="fas fa-search" style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin: 0; font-weight: 500;">
                            ${term ? 'No se encontraron novedades para ese recibo' : 'No hay novedades registradas'}
                        </p>
                    </div>
                `;
            }

            const rowsHtml = filtered.map((item) => {
                const fecha = formatearFechaHora12h(item.fecha);
                const logosJson = JSON.stringify(item.logos || []).replace(/'/g, "\\'");
                const tieneLogos = Array.isArray(item.logos) && item.logos.length > 0;
                const badge = historialTipoBadge(item.tipo_novedad);

                return `
                    <tr>
                        <td style="font-weight: 700; color: #1e293b;">#${escapeHtml(item.numero_recibo || '-')}</td>
                        <td>
                            <span style="
                                display: inline-flex;
                                align-items: center;
                                gap: 5px;
                                padding: 4px 10px;
                                border-radius: 999px;
                                font-size: 12px;
                                font-weight: 600;
                                white-space: nowrap;
                                background: ${badge.bg};
                                color: ${badge.color};
                                border: 1px solid ${badge.border};
                            ">
                                <i class="fas ${badge.icon}"></i>
                                ${badge.label}
                            </span>
                        </td>
                        <td style="color: #64748b; font-size: 13px; white-space: nowrap;">${escapeHtml(fecha)}</td>
                        <td style="color: #64748b; font-size: 13px; max-width: 320px; word-wrap: break-word; white-space: normal; line-height: 1.5;">${escapeHtml(item.observacion || '-')}</td>
                        <td>
                            ${tieneLogos ? `
                                <button type="button" onclick='window.__verDisenoLogo(${logosJson}, true)' style="
                                    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                                    border: none;
                                    color: white;
                                    padding: 8px 12px;
                                    border-radius: 8px;
                                    cursor: pointer;
                                    font-weight: 700;
                                    font-size: 0.85rem;
                                ">Ver</button>
                            ` : '<span style="color:#94a3b8; font-size:12px;">—</span>'}
                        </td>
                    </tr>
                `;
            }).join('');

            return `
                <div style="padding: 0 16px 16px;">
                    <table class="table-talleres" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Recibo</th>
                                <th>Tipo</th>
                                <th>Fecha</th>
                                <th>Observación</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            `;
        }

        fetch(`{{ route('visualizador-logo.logos-confirmados.historial-novedades') }}`)
            .then(r => r.json())
            .then(json => {
                const body = document.getElementById('modal-historial-novedades-body');
                const toolbar = document.getElementById('modal-historial-novedades-toolbar');
                const searchInput = document.getElementById('modal-historial-novedades-search');
                const clearBtn = document.getElementById('modal-historial-novedades-clear');
                if (!body) return;

                if (!json || json.success !== true) {
                    throw new Error('Respuesta inválida');
                }

                const items = Array.isArray(json.items) ? json.items : [];

                if (toolbar) {
                    toolbar.style.display = items.length > 0 ? 'block' : 'none';
                }

                body.innerHTML = renderHistorialNovedadesRows(items);

                if (searchInput) {
                    let searchTimeout;
                    searchInput.addEventListener('input', function() {
                        const value = this.value.trim();
                        if (clearBtn) {
                            clearBtn.style.display = value ? 'block' : 'none';
                        }
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            body.innerHTML = renderHistorialNovedadesRows(items, value);
                        }, 200);
                    });
                }

                if (clearBtn && searchInput) {
                    clearBtn.addEventListener('click', function() {
                        searchInput.value = '';
                        this.style.display = 'none';
                        body.innerHTML = renderHistorialNovedadesRows(items);
                        searchInput.focus();
                    });
                }
            })
            .catch(() => {
                const body = document.getElementById('modal-historial-novedades-body');
                if (!body) return;
                body.innerHTML = `
                    <div style="padding: 3rem 2rem; text-align: center; color: #dc2626;">
                        <i class="fas fa-triangle-exclamation" style="font-size: 2.5rem; margin-bottom: 1rem; display: block;"></i>
                        <p style="margin: 0; font-weight: 700;">Error cargando el historial</p>
                    </div>
                `;
            });
    };

    window.__verDisenoLogo = function(logosArray, soloLectura = false) {
        const logos = Array.isArray(logosArray) ? logosArray : [];
        if (!logos.length) return;

        const existing = document.getElementById('modal-diseno-logo-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'modal-diseno-logo-overlay';
        overlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 999999; display:flex; align-items:flex-start; justify-content:center; padding: 32px 16px; overflow-y: auto;';

        const modal = document.createElement('div');
        modal.style.cssText = 'width: 100%; max-width: 1200px; background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden;';

        const imagesHtml = logos.map((logo, index) => {
            const novedadesJson = JSON.stringify(logo.novedades || []);
            return `
                <div style="width: 30%; margin-bottom: 16px; background: #e2e8f0; border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1;">
                    <img src="${escapeAttr(logo.url)}" alt="Imagen ${index + 1}" class="gallery-image" data-url="${escapeAttr(logo.url)}" style="width: 100%; height: 200px; object-fit: contain; border-radius: 8px; background: white; margin-bottom: 8px; cursor: zoom-in;" />
                    <div style="display:flex; gap: 8px; justify-content:center; flex-wrap: wrap;">
                        <button type="button" class="ver-obs-btn" data-novedades="${escapeAttr(novedadesJson)}" style="
                            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
                            border: none;
                            color: white;
                            padding: 8px 10px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 0.85rem;
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.18);
                        ">
                            <i class="fas fa-eye"></i> Ver Novedades
                        </button>
                        ${soloLectura ? '' : `
                        <button type="button" class="reemplazar-btn" data-id="${logo.id}" style="
                            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                            border: none;
                            color: white;
                            padding: 8px 10px;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 700;
                            font-size: 0.85rem;
                            display: inline-flex;
                            align-items: center;
                            gap: 6px;
                            box-shadow: 0 2px 8px rgba(14, 165, 233, 0.18);
                        ">
                            <i class="fas fa-upload"></i> Reemplazar
                        </button>
                        <input type="file" id="file-${logo.id}" accept="image/*" style="display: none;" />
                        `}
                    </div>
                </div>
            `;
        }).join('');

        modal.innerHTML = `
            <div style="padding: 14px 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center; position: sticky; top: 0; z-index: 10;">
                <div id="modal-diseno-logo-counter">${logos.length} Imagen(es)</div>
                <button id="modal-diseno-logo-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:34px; height:34px; border-radius: 10px; cursor:pointer;">×</button>
            </div>
            <div style="padding: 20px; display:flex; flex-wrap: wrap; gap: 2%; justify-content:center; background: #d1d5db;">
                ${imagesHtml}
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const cerrar = () => {
            const ov = document.getElementById('modal-diseno-logo-overlay');
            if (ov) ov.remove();
        };

        const btnClose = overlay.querySelector('#modal-diseno-logo-close');
        if (btnClose) btnClose.addEventListener('click', cerrar);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) cerrar();
        });

        const onKey = (e) => {
            const ov = document.getElementById('modal-diseno-logo-overlay');
            if (!ov) {
                document.removeEventListener('keydown', onKey);
                return;
            }
            if (e.key === 'Escape') {
                cerrar();
                document.removeEventListener('keydown', onKey);
            }
        };
        document.addEventListener('keydown', onKey);

        // Add event listeners to "Ver Novedades" buttons
        overlay.querySelectorAll('.ver-obs-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const novedadesJson = btn.getAttribute('data-novedades');
                showNovedadesModal(novedadesJson);
            });
        });

        if (!soloLectura) {
            overlay.querySelectorAll('.reemplazar-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id = parseInt(btn.getAttribute('data-id'));
                    document.getElementById(`file-${id}`).click();
                });
            });

            overlay.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;
                    const id = parseInt(input.id.replace('file-', ''));
                    await reemplazarImagen(id, file);
                });
            });
        }

        // Add double-click event to images for full view
        overlay.querySelectorAll('.gallery-image').forEach(img => {
            img.addEventListener('dblclick', () => {
                showFullImage(img.getAttribute('data-url'));
            });
        });

        function showNovedadesModal(novedadesJson) {
            const existingObsModal = document.getElementById('modal-obs-overlay');
            if (existingObsModal) existingObsModal.remove();

            let novedades = [];
            try {
                novedades = JSON.parse(novedadesJson) || [];
            } catch (e) {
                novedades = [];
            }

            // Sort novedades by created_at descending (newest first)
            novedades.sort((a, b) => {
                const dateA = new Date(a.created_at || 0);
                const dateB = new Date(b.created_at || 0);
                return dateB - dateA;
            });

            const obsOverlay = document.createElement('div');
            obsOverlay.id = 'modal-obs-overlay';
            obsOverlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000000; display:flex; align-items:center; justify-content:center; padding: 16px;';
            
            const obsModal = document.createElement('div');
            obsModal.style.cssText = 'width: 550px; max-width: 100%; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden;';
            
            const novedadesHtml = novedades.length > 0 
                ? novedades.map((novedad) => {
                    const fecha = formatearFechaHora12h(novedad.created_at) || 'Fecha desconocida';
                    const usuario = novedad.usuario?.name || 'Usuario desconocido';
                    return `
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <div style="font-weight: 700; color: #1e40af; font-size: 0.9rem;">
                                    <i class="fas fa-user-circle" style="margin-right: 6px;"></i>
                                    ${escapeHtml(usuario)}
                                </div>
                                <div style="font-size: 0.8rem; color: #64748b;">
                                    <i class="fas fa-calendar-alt" style="margin-right: 4px;"></i>
                                    ${escapeHtml(fecha)}
                                </div>
                            </div>
                            <div style="font-size: 0.95rem; color: #334155; line-height: 1.4;">
                                ${escapeHtml(novedad.novedad)}
                            </div>
                        </div>
                    `;
                }).join('')
                : `
                    <div style="padding: 40px 20px; text-align: center; color: #64748b;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 12px; display: block;"></i>
                        No hay novedades registradas para este diseño
                    </div>
                `;

            obsModal.innerHTML = `
                <div style="padding: 12px 14px; background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center;">
                    <span>Historial de Novedades</span>
                    <button id="modal-obs-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:30px; height:30px; border-radius: 8px; cursor:pointer;">×</button>
                </div>
                <div style="max-height: 450px; overflow-y: auto;">
                    ${novedadesHtml}
                </div>
            `;
            
            obsOverlay.appendChild(obsModal);
            document.body.appendChild(obsOverlay);

            const cerrarObs = () => obsOverlay.remove();
            obsModal.querySelector('#modal-obs-close').addEventListener('click', cerrarObs);
            obsOverlay.addEventListener('click', e => e.target === obsOverlay && cerrarObs());
        }

        function showFullImage(url) {
            const existingFullModal = document.getElementById('modal-full-image-overlay');
            if (existingFullModal) existingFullModal.remove();

            const fullOverlay = document.createElement('div');
            fullOverlay.id = 'modal-full-image-overlay';
            fullOverlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000001; display:flex; align-items:center; justify-content:center; padding: 16px;';
            
            const fullModal = document.createElement('div');
            fullModal.style.cssText = 'width: auto; height: auto; max-width: 95vw; max-height: 95vh;';
            
            fullModal.innerHTML = `
                <img src="${escapeAttr(url)}" alt="Imagen Completa" style="max-width: 100%; max-height: 90vh; border-radius: 8px;" />
                <button id="modal-full-close" type="button" style="position: absolute; top: 16px; right: 16px; border:none; background: rgba(255,255,255,0.2); color:white; font-weight:900; width:40px; height:40px; border-radius: 50%; cursor:pointer; font-size:1.5rem;">×</button>
            `;
            
            fullOverlay.appendChild(fullModal);
            document.body.appendChild(fullOverlay);

            const cerrarFull = () => fullOverlay.remove();
            fullOverlay.querySelector('#modal-full-close').addEventListener('click', cerrarFull);
            fullOverlay.addEventListener('click', e => e.target === fullOverlay && cerrarFull());
        }

        async function reemplazarImagen(id, file) {
            // Add custom CSS to set high z-index for SweetAlert
            if (!document.getElementById('swal-z-index-style')) {
                const style = document.createElement('style');
                style.id = 'swal-z-index-style';
                style.textContent = `
                    .swal2-container {
                        z-index: 10000000 !important;
                    }
                `;
                document.head.appendChild(style);
            }

            // Show confirmation dialog first
            const confirmed = await Swal.fire({
                title: '¿Reemplazar imagen del diseño?',
                text: 'La imagen anterior será reemplazada por la nueva y el estado pasará a "pendiente por confirmar". ¿Deseas continuar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, reemplazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0ea5e9',
                cancelButtonColor: '#6b7280',
            });

            if (!confirmed.isConfirmed) {
                return;
            }

            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                const response = await fetch(`/visualizador-logo/logos-confirmados/${id}/reemplazar`, {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();
                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Imagen reemplazada exitosamente!',
                        timer: 1500,
                        showConfirmButton: false,
                    });
                    cerrar();
                    cargarDisenos();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al reemplazar la imagen: ' + (result.message || 'Error desconocido'),
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al reemplazar la imagen: ' + error.message,
                });
            }
        }
    };

    function formatearFechaHora12h(value) {
        if (!value) return '-';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '-';

        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0');
        const anio = date.getFullYear();
        const minutos = String(date.getMinutes()).padStart(2, '0');

        let horas = date.getHours();
        const periodo = horas >= 12 ? 'PM' : 'AM';
        horas = horas % 12;
        horas = horas === 0 ? 12 : horas;

        return `${dia}/${mes}/${anio}, ${horas}:${minutos} ${periodo}`;
    }

    function formatearFechaISO(value) {
        if (!value) return '-';
        const str = String(value);
        const datePart = str.includes('T') ? str.split('T')[0] : str.substring(0, 10);
        return datePart || '-';
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replaceAll('`', '&#096;');
    }
});
</script>
@endsection
