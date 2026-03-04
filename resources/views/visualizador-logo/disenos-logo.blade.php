@extends('layouts.visualizador-logo')

@section('title', 'Diseños de logo')

@section('page-title', 'Diseños de logo')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 1100px;">
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
                <div style="
                    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
                    color: white;
                    padding: 1rem 1.5rem;
                    display: grid;
                    grid-template-columns: 240px 160px 1fr 120px;
                    gap: 1rem;
                    font-weight: 700;
                    font-size: 0.9rem;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                ">
                    <div style="color: #cbd5e1;">Cliente</div>
                    <div style="color: #cbd5e1;">Fecha creación</div>
                    <div style="color: #cbd5e1;">Observaciones</div>
                    <div style="text-align: center; color: #cbd5e1;">Imagen</div>
                </div>

                <div id="disenos-logo-body">
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                        <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando diseños...</p>
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

    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');

    cargarDisenos();

    if (searchInput) {
        searchInput.placeholder = 'Buscar por cliente u observación...';

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
        });

        if (searchTerm) {
            params.append('search', searchTerm);
        }

        fetch(`{{ route('visualizador-logo.disenos-logo.data') }}?${params.toString()}`)
            .then(r => r.json())
            .then(json => {
                if (!json || json.success !== true) {
                    throw new Error('Respuesta inválida');
                }

                renderizarDisenos(json.items, searchTerm);
            })
            .catch(() => {
                mostrarError();
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
                        ${searchTerm ? 'No se encontraron diseños para tu búsqueda' : 'No se encontraron diseños'}
                    </p>
                </div>
            `;
            if (pagContainer) pagContainer.innerHTML = '';
            return;
        }

        body.innerHTML = data.map((it) => {
            const cliente = it.cliente || '-';
            const obs = (it.observacio_diseño && String(it.observacio_diseño).trim() !== '') ? it.observacio_diseño : 'Sin observación';
            const fecha = formatearFechaISO(it.created_at);
            const url = it.url || '';

            const clienteHtml = escapeHtml(cliente);
            const obsHtml = escapeHtml(obs);

            return `
                <div style="
                    display: grid;
                    grid-template-columns: 240px 160px 1fr 120px;
                    gap: 1rem;
                    padding: 1rem 1.5rem;
                    align-items: center;
                    transition: all 0.3s ease;
                    background: white;
                    border-bottom: 1px solid #e2e8f0;
                " onmouseover="this.style.background='#f8fafc'; this.style.boxShadow='inset 0 0 0 1px #e2e8f0'" onmouseout="this.style.background='white'; this.style.boxShadow='none'">
                    <div style="color: #334155; font-size: 0.95rem; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clienteHtml}</div>
                    <div style="color: #64748b; font-size: 0.95rem;">${fecha}</div>
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${obsHtml}</div>
                    <div style="display:flex; justify-content:center;">
                        <button type="button" onclick="window.__verDisenoLogo('${escapeAttr(url)}')" title="Ver" style="
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
                            <i class="fas fa-eye"></i>
                            Ver
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        if (pagContainer) {
            pagContainer.innerHTML = construirPaginacion(paginacion);
        }
    }

    function mostrarError() {
        const body = document.getElementById('disenos-logo-body');
        if (!body) return;
        body.innerHTML = `
            <div style="padding: 3rem 2rem; text-align: center; color: #dc2626; background: #fef2f2;">
                <i class="fas fa-triangle-exclamation" style="font-size: 2.5rem; color: #dc2626; margin-bottom: 1rem; display: block;"></i>
                <p style="margin: 0; font-size: 1rem; font-weight: 700;">Error cargando diseños</p>
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

    window.__verDisenoLogo = function(url) {
        const u = String(url || '').trim();
        if (!u) return;

        const existing = document.getElementById('modal-diseno-logo-overlay');
        if (existing) existing.remove();

        const overlay = document.createElement('div');
        overlay.id = 'modal-diseno-logo-overlay';
        overlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 999999; display:flex; align-items:center; justify-content:center; padding: 16px;';

        const modal = document.createElement('div');
        modal.style.cssText = 'width: 900px; max-width: 100%; background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden;';

        modal.innerHTML = `
            <div style="padding: 14px 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center;">
                <div>DISEÑO</div>
                <button id="modal-diseno-logo-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:34px; height:34px; border-radius: 10px; cursor:pointer;">×</button>
            </div>
            <div style="padding: 14px; background: #0b1220; display:flex; align-items:center; justify-content:center;">
                <img src="${escapeAttr(u)}" alt="Diseño" style="max-width: 100%; max-height: 75vh; object-fit: contain; border-radius: 10px; background: white;" />
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
    };

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
