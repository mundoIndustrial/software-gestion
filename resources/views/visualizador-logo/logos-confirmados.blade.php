@extends('layouts.visualizador-logo')

@section('title', 'Logos Confirmados')

@section('page-title', 'Logos Confirmados')

@section('content')
<div style="padding: 1rem 1rem 2rem 1rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); min-height: calc(100vh - 60px); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <div style="display: flex; justify-content: center;">
        <div style="width: 100%; max-width: 1200px;">
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
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
                    <div style="color: #cbd5e1;">Observaciones</div>
                    <div style="text-align: center; color: #cbd5e1;">Acción</div>
                </div>

                <div id="disenos-logo-body">
                    <div style="padding: 3rem 2rem; text-align: center; color: #64748b; background: #f8fafc;">
                        <div style="font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem;">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <p style="margin: 0; font-size: 1rem; font-weight: 500;">Cargando logos confirmados...</p>
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
        searchInput.placeholder = 'Buscar por cliente, recibo, prenda o observación...';

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

        fetch(`{{ route('visualizador-logo.logos-confirmados.data') }}?${params.toString()}`)
            .then(r => r.json())
            .then(json => {
                if (!json || json.success !== true) {
                    throw new Error('Respuesta inválida');
                }

                // Actualizar badge en sidebar si es necesario
                if (typeof window.__actualizarBadgeLogos === 'function') {
                    window.__actualizarBadgeLogos(json.conteo_no_revisados || 0);
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
                        ${searchTerm ? 'No se encontraron logos confirmados para tu búsqueda' : 'No hay logos confirmados'}
                    </p>
                </div>
            `;
            if (pagContainer) pagContainer.innerHTML = '';
            return;
        }

        body.innerHTML = data.map((group) => {
            const cliente = group.cliente || '-';
            const numeroRecibo = group.numero_recibo || '-';
            const obs = (group.observacio_diseño && String(group.observacio_diseño).trim() !== '') ? group.observacio_diseño : 'Sin observación';
            const logosCount = group.logos.length;
            const todosRevisados = group.todos_revisados;

            const clienteHtml = escapeHtml(cliente);
            const numeroReciboHtml = escapeHtml(numeroRecibo);
            const obsHtml = escapeHtml(obs);

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
                    <div style="color: #64748b; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${obsHtml}">${obsHtml}</div>
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

    window.__verDisenoLogo = function(logosArray) {
        const logos = Array.isArray(logosArray) ? logosArray : [];
        if (!logos.length) return;

        const existing = document.getElementById('modal-diseno-logo-overlay');
        if (existing) existing.remove();

        let currentIndex = 0;

        function renderCurrentImage() {
            const imgContainer = document.getElementById('modal-diseno-logo-img-container');
            const imgCounter = document.getElementById('modal-diseno-logo-counter');
            const btnRevisar = document.getElementById('modal-diseno-logo-revisar');
            if (!imgContainer || !imgCounter || !btnRevisar) return;

            const currentLogo = logos[currentIndex];
            
            imgContainer.innerHTML = `<img src="${escapeAttr(currentLogo.url)}" alt="Imagen ${currentIndex + 1}" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 10px; background: white;" />`;
            imgCounter.textContent = `Imagen #${currentIndex + 1} de ${logos.length}`;
            
            if (currentLogo.revisada) {
                btnRevisar.innerHTML = '<i class="fas fa-check-circle"></i> Revisado';
                btnRevisar.style.background = 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)';
                btnRevisar.disabled = true;
                btnRevisar.style.opacity = '0.7';
                btnRevisar.style.cursor = 'not-allowed';
            } else {
                btnRevisar.innerHTML = '<i class="fas fa-check"></i> Marcar como revisado';
                btnRevisar.style.background = 'linear-gradient(135deg, #2563eb 0%, #1e40af 100%)';
                btnRevisar.disabled = false;
                btnRevisar.style.opacity = '1';
                btnRevisar.style.cursor = 'pointer';
            }
        }

        const overlay = document.createElement('div');
        overlay.id = 'modal-diseno-logo-overlay';
        overlay.style.cssText = 'position: fixed; inset: 0; background: rgba(0,0,0,0.75); z-index: 999999; display:flex; align-items:center; justify-content:center; padding: 16px;';

        const modal = document.createElement('div');
        modal.style.cssText = 'width: 950px; max-width: 100%; background: white; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden;';

        modal.innerHTML = `
            <div style="padding: 14px 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; font-weight: 900; letter-spacing: 0.5px; display:flex; justify-content: space-between; align-items:center;">
                <div id="modal-diseno-logo-counter">Imagen #1 de ${logos.length}</div>
                <button id="modal-diseno-logo-close" type="button" style="border:none; background: rgba(255,255,255,0.18); color:white; font-weight:900; width:34px; height:34px; border-radius: 10px; cursor:pointer;">×</button>
            </div>
            <div style="display:flex; align-items:center; background: #ffffff; flex-direction: column;">
                <div style="display:flex; align-items:center; width: 100%;">
                    <button id="modal-diseno-logo-prev" type="button" style="background: #f3f4f6; border: none; color: #1f2937; padding: 20px; cursor: pointer; font-size: 24px; border-radius: 8px; margin: 10px; ${logos.length <= 1 ? 'opacity: 0.3; cursor: not-allowed;' : ''}">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div id="modal-diseno-logo-img-container" style="flex: 1; padding: 14px; display:flex; align-items:center; justify-content:center;">
                        <img src="${escapeAttr(logos[0].url)}" alt="Imagen 1" style="max-width: 100%; max-height: 70vh; object-fit: contain; border-radius: 10px; background: white;" />
                    </div>
                    <button id="modal-diseno-logo-next" type="button" style="background: #f3f4f6; border: none; color: #1f2937; padding: 20px; cursor: pointer; font-size: 24px; border-radius: 8px; margin: 10px; ${logos.length <= 1 ? 'opacity: 0.3; cursor: not-allowed;' : ''}">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <div style="padding: 14px; background: white; width:100%; display:flex; justify-content:center;">
                    <button id="modal-diseno-logo-revisar" type="button" style="
                        border: none;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 10px;
                        cursor: pointer;
                        font-weight: 800;
                        display: inline-flex;
                        align-items: center;
                        gap: 8px;
                        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.18);
                    ">
                        <i class="fas fa-check"></i> Marcar como revisado
                    </button>
                </div>
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
            if (e.key === 'ArrowLeft' && logos.length > 1) {
                currentIndex = (currentIndex - 1 + logos.length) % logos.length;
                renderCurrentImage();
            }
            if (e.key === 'ArrowRight' && logos.length > 1) {
                currentIndex = (currentIndex + 1) % logos.length;
                renderCurrentImage();
            }
        };
        document.addEventListener('keydown', onKey);

        const btnPrev = overlay.querySelector('#modal-diseno-logo-prev');
        const btnNext = overlay.querySelector('#modal-diseno-logo-next');
        const btnRevisar = overlay.querySelector('#modal-diseno-logo-revisar');

        if (btnPrev && logos.length > 1) {
            btnPrev.addEventListener('click', () => {
                currentIndex = (currentIndex - 1 + logos.length) % logos.length;
                renderCurrentImage();
            });
        }

        if (btnNext && logos.length > 1) {
            btnNext.addEventListener('click', () => {
                currentIndex = (currentIndex + 1) % logos.length;
                renderCurrentImage();
            });
        }
        
        if (btnRevisar) {
            btnRevisar.addEventListener('click', () => {
                const currentLogo = logos[currentIndex];
                if (!currentLogo.revisada) {
                    marcarDisenoComoRevisado(currentLogo.id);
                    logos[currentIndex].revisada = true;
                    renderCurrentImage();
                }
            });
        }

        renderCurrentImage();
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
