/**
 * Insumos Materiales Page Loader
 *
 * Punto de entrada unico para el modulo de insumos/materiales.
 * - Carga los scripts del modulo en orden controlado
 * - Expone un contrato estable en window.insumos
 * - Reemplaza handlers inline por delegacion de eventos
 */
(function () {
    'use strict';

    const CRITICAL_SCRIPTS = [
        { src: '/js/insumos/index.js', type: 'module' },
    ];

    const MAIN_SCRIPTS = [
        { src: '/js/insumos/modal-handlers-insumos.js' },
        { src: '/js/insumos/filter-manager-no-url.js' },
        { src: '/js/insumos/material-operations-insumos.js' },
        { src: '/js/insumos/form-handlers-insumos.js' },
        { src: '/js/insumos/status-actions-insumos.js' },
        { src: '/js/insumos/modal-ancho-metraje-insumos.js' },
        { src: '/js/insumos/insumos-modal-management.js' },
        { src: '/js/insumos/notifications-realtime-insumos.js' },
        { src: '/js/insumos/recibos-selector-insumos.js' },
        { src: '/js/insumos/pasar-a-revisar-insumos.js' },
        { src: '/js/insumos/dropdown-handlers-insumos.js' },
        { src: '/js/insumos/search-debounce.js' },
        { src: '/js/insumos/insumos-galeria.js' },
    ];

    const LAZY_SCRIPTS = [
        { src: '/js/insumos/pagination.js' },
    ];

    const state = {
        initialized: false,
        loadingPromise: null,
    };

    function getLoadingOverlay() {
        return document.getElementById('loadingOverlay');
    }

    function showPageLoading() {
        const overlay = getLoadingOverlay();
        if (overlay) {
            overlay.classList.add('active');
        }
    }

    function hidePageLoading() {
        const overlay = getLoadingOverlay();
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function loadScript(scriptDef) {
        return new Promise((resolve, reject) => {
            const timeStart = performance.now();
            const script = document.createElement('script');
            script.src = scriptDef.src;
            if (scriptDef.src.includes('filter-manager')) {
                script.src += '?t=' + Date.now();
            }
            script.defer = false;
            script.async = false;
            if (scriptDef.type === 'module') {
                script.type = 'module';
            }
            script.onload = () => {
                const duration = (performance.now() - timeStart).toFixed(2);
                console.log(`  ✓ ${scriptDef.src.split('/').pop()} (${duration}ms)`);
                resolve(scriptDef.src);
            };
            script.onerror = () => reject(new Error(`No se pudo cargar: ${scriptDef.src}`));
            document.body.appendChild(script);
        });
    }

    async function loadModuleScripts() {
        const timeStart = performance.now();
        console.log('\n═══ CARGA DE SCRIPTS FRONTEND (OPTIMIZADO) ═══');
        console.log(`Cargando ${CRITICAL_SCRIPTS.length + MAIN_SCRIPTS.length + LAZY_SCRIPTS.length} scripts...\n`);

        console.log(`Fase 1: Scripts críticos (${CRITICAL_SCRIPTS.length})...`);
        for (const scriptDef of CRITICAL_SCRIPTS) {
            await loadScript(scriptDef);
        }

        console.log(`\nFase 2: Scripts principales (${MAIN_SCRIPTS.length} en paralelo)...`);
        await Promise.all(MAIN_SCRIPTS.map(scriptDef => loadScript(scriptDef)));

        const mainDuration = (performance.now() - timeStart).toFixed(2);
        console.log(`\n✓ Scripts principales cargados en ${mainDuration}ms`);

        console.log(`\nFase 3: Scripts lazy-load (${LAZY_SCRIPTS.length} en background)...`);
        const lazyLoad = async () => {
            for (const scriptDef of LAZY_SCRIPTS) {
                await loadScript(scriptDef);
            }
            const totalDuration = (performance.now() - timeStart).toFixed(2);
            console.log(`✓ Lazy scripts cargados - Total: ${totalDuration}ms\n`);
        };
        lazyLoad();

        return mainDuration;
    }

    function resolveInsumosHandler(functionName) {
        const registry = window.insumosHandlers;
        if (!registry || typeof registry !== 'object') {
            return null;
        }

        for (const moduleHandlers of Object.values(registry)) {
            if (!moduleHandlers || typeof moduleHandlers !== 'object') {
                continue;
            }
            const fn = moduleHandlers[functionName];
            if (typeof fn === 'function') {
                return fn;
            }
        }

        return null;
    }

    function safeCall(globalFnName, args, fallbackMessage) {
        const fn = resolveInsumosHandler(globalFnName);
        if (typeof fn === 'function') {
            return fn(...args);
        }
        console.warn(`[insumos] ${fallbackMessage || `Funcion no disponible: ${globalFnName}`}`);
        return null;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function decodeHtmlEntities(value) {
        const textarea = document.createElement('textarea');
        let current = String(value || '');

        // Algunos textos llegan doble-escapados (ej: &amp;quot;).
        for (let i = 0; i < 2; i += 1) {
            textarea.innerHTML = current;
            const decoded = textarea.value;
            if (decoded === current) {
                break;
            }
            current = decoded;
        }

        return current;
    }

    function abrirModalNovedadesRecibo(btn) {
        const numeroRecibo = btn.getAttribute('data-numero-recibo') || 'N/A';
        const numeroPedido = btn.getAttribute('data-numero-pedido') || 'N/A';
        const estadoRecibo = (btn.getAttribute('data-estado-recibo') || '').trim();
        const motivoDevolucion = decodeHtmlEntities((btn.getAttribute('data-motivo-devolucion') || '').trim());
        const ultimaNovedadAsesora = decodeHtmlEntities((btn.getAttribute('data-ultima-novedad-asesora') || '').trim());
        const etiquetaMotivo = estadoRecibo === 'Anulada'
            ? 'Motivo de anulacion'
            : 'Motivo de devolucion';

        const motivoHtml = motivoDevolucion
            ? `<div style="margin-bottom: 1rem;">
                    <h4 style="margin: 0 0 .35rem 0; color: #991b1b; font-size: .95rem;">${escapeHtml(etiquetaMotivo)}</h4>
                    <div style="white-space: pre-wrap; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: .75rem; color: #7f1d1d; font-size: .9rem;">${escapeHtml(motivoDevolucion)}</div>
               </div>`
            : `<div style="margin-bottom: 1rem;">
                    <h4 style="margin: 0 0 .35rem 0; color: #991b1b; font-size: .95rem;">${escapeHtml(etiquetaMotivo)}</h4>
                    <div style="background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; padding: .75rem; color: #6b7280; font-size: .9rem;">Sin motivo registrado.</div>
               </div>`;

        const asesoraHtml = ultimaNovedadAsesora
            ? `<div>
                    <h4 style="margin: 0 0 .35rem 0; color: #1e3a8a; font-size: .95rem;">Última novedad de asesora</h4>
                    <div style="white-space: pre-wrap; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: .75rem; color: #1e3a8a; font-size: .9rem;">${escapeHtml(ultimaNovedadAsesora)}</div>
               </div>`
            : `<div>
                    <h4 style="margin: 0 0 .35rem 0; color: #1e3a8a; font-size: .95rem;">Última novedad de asesora</h4>
                    <div style="background: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; padding: .75rem; color: #6b7280; font-size: .9rem;">No hay novedades recientes de asesora para este pedido.</div>
               </div>`;

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                title: `Novedades del recibo #${escapeHtml(numeroRecibo)}`,
                html: `
                    <div style="text-align: left;">
                        <div style="margin-bottom: .75rem; color: #4b5563; font-size: .9rem;">
                            <strong>Pedido:</strong> ${escapeHtml(numeroPedido)}<br>
                            <strong>Recibo:</strong> ${escapeHtml(numeroRecibo)}
                        </div>
                        ${motivoHtml}
                        ${asesoraHtml}
                    </div>
                `,
                width: 760,
                confirmButtonText: 'Cerrar',
                confirmButtonColor: '#2563eb'
            });
            return;
        }

        alert(`Recibo #${numeroRecibo}\n\n${etiquetaMotivo}:\n${motivoDevolucion || 'Sin motivo registrado.'}\n\nUltima novedad de asesora:\n${ultimaNovedadAsesora || 'Sin novedades recientes.'}`);
    }

    function bindDelegatedActions() {
        if (document.documentElement.dataset.insumosDelegated === '1') {
            return;
        }
        document.documentElement.dataset.insumosDelegated = '1';

        document.addEventListener('click', function (event) {
            const btn = event.target.closest('[data-insumos-action]');
            if (!btn) return;

            const action = btn.getAttribute('data-insumos-action');
            if (!action) return;

            switch (action) {
                case 'toggle-row-check': {
                    event.preventDefault();
                    safeCall('toggleRowCheck', [btn, event], 'toggleRowCheck no esta disponible');
                    break;
                }
                case 'ver-recibo-dropdown': {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    safeCall('crearDropdownVerRecibo', [event, btn], 'crearDropdownVerRecibo no esta disponible');
                    break;
                }
                case 'acciones-dropdown': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('crearDropdownAcciones', [event, btn], 'crearDropdownAcciones no esta disponible');
                    break;
                }
                case 'enviar-produccion': {
                    event.preventDefault();
                    event.stopPropagation();
                    const reciboId = btn.getAttribute('data-recibo-id');
                    const consecutivo = btn.getAttribute('data-consecutivo');
                    safeCall('cambiarEstadoRecibo', [reciboId, consecutivo], 'cambiarEstadoRecibo no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'close-modal-overlay': {
                    event.preventDefault();
                    safeCall('closeModalOverlay', [], 'closeModalOverlay no esta disponible');
                    break;
                }
                case 'dropdown-ver-detalle-recibo': {
                    event.preventDefault();
                    event.stopPropagation();
                    const pedidoId = btn.getAttribute('data-pedido-id');
                    const prendaId = btn.getAttribute('data-prenda-id');
                    const tipoRecibo = btn.getAttribute('data-tipo-recibo') || 'COSTURA';
                    const esParcial = btn.getAttribute('data-es-parcial') === '1';
                    const pedidoParcialId = btn.getAttribute('data-pedido-parcial-id');
                    safeCall('abrirDetalleRecibo', [pedidoId, prendaId, tipoRecibo, esParcial, pedidoParcialId], 'abrirDetalleRecibo no esta disponible');
                    safeCall('cerrarDropdownVerRecibo', [], 'cerrarDropdownVerRecibo no esta disponible');
                    break;
                }
                case 'dropdown-ver-seguimiento': {
                    event.preventDefault();
                    event.stopPropagation();
                    const pedidoId = btn.getAttribute('data-pedido-id');
                    const prendaId = btn.getAttribute('data-prenda-id');
                    safeCall('abrirSeguimientoRecibo', [pedidoId, prendaId], 'abrirSeguimientoRecibo no esta disponible');
                    safeCall('cerrarDropdownVerRecibo', [], 'cerrarDropdownVerRecibo no esta disponible');
                    break;
                }
                case 'dropdown-acciones-gestionar-insumos': {
                    event.preventDefault();
                    event.stopPropagation();
                    const pedidoProduccionId = btn.getAttribute('data-pedido-produccion-id');
                    const prendaId = btn.getAttribute('data-prenda-id');
                    const consecutivo = btn.getAttribute('data-consecutivo');
                    const estado = btn.getAttribute('data-estado');
                    const tipoRecibo = btn.getAttribute('data-tipo-recibo');
                    safeCall('abrirModalInsumos', [pedidoProduccionId, prendaId, consecutivo, estado, tipoRecibo], 'abrirModalInsumos no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'dropdown-acciones-ancho-metraje': {
                    event.preventDefault();
                    event.stopPropagation();
                    const pedidoProduccionId = btn.getAttribute('data-pedido-produccion-id');
                    const prendaId = btn.getAttribute('data-prenda-id');
                    safeCall('abrirModalAnchoMetraje', [pedidoProduccionId, prendaId], 'abrirModalAnchoMetraje no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'dropdown-acciones-anular-recibo': {
                    event.preventDefault();
                    event.stopPropagation();
                    const reciboId = btn.getAttribute('data-recibo-id');
                    const consecutivo = btn.getAttribute('data-consecutivo');
                    safeCall('anularReciboInsumos', [reciboId, consecutivo], 'anularReciboInsumos no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'dropdown-acciones-pasar-revisar': {
                    event.preventDefault();
                    event.stopPropagation();
                    const reciboId = btn.getAttribute('data-recibo-id');
                    const pedidoProduccionId = btn.getAttribute('data-pedido-produccion-id');
                    safeCall('abrirModalPasarRevisar', [reciboId, pedidoProduccionId], 'abrirModalPasarRevisar no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'material-open-observaciones': {
                    event.preventDefault();
                    event.stopPropagation();
                    const materialId = btn.getAttribute('data-material-id');
                    const materialName = btn.getAttribute('data-material-name') || '';
                    safeCall('abrirModalObservaciones', [materialId, materialName], 'abrirModalObservaciones no esta disponible');
                    break;
                }
                case 'material-delete-row': {
                    event.preventDefault();
                    event.stopPropagation();
                    const materialId = btn.getAttribute('data-material-id');
                    safeCall('eliminarFilaMaterial', [materialId], 'eliminarFilaMaterial no esta disponible');
                    break;
                }
                case 'material-add-row': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('agregarMaterialModal', [], 'agregarMaterialModal no esta disponible');
                    break;
                }
                case 'material-save-changes': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('guardarInsumosModal', [], 'guardarInsumosModal no esta disponible');
                    break;
                }
                case 'modal-insumos-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalInsumos', [], 'cerrarModalInsumos no esta disponible');
                    break;
                }
                case 'observaciones-save': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('guardarObservaciones', [], 'guardarObservaciones no esta disponible');
                    break;
                }
                case 'observaciones-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalObservaciones', [], 'cerrarModalObservaciones no esta disponible');
                    break;
                }
                case 'ancho-metraje-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalAnchoMetraje', [], 'cerrarModalAnchoMetraje no esta disponible');
                    break;
                }
                case 'ancho-metraje-open-delete-confirm': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('abrirModalConfirmacionEliminar', [], 'abrirModalConfirmacionEliminar no esta disponible');
                    break;
                }
                case 'ancho-metraje-save': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('guardarAnchoMetraje', [], 'guardarAnchoMetraje no esta disponible');
                    break;
                }
                case 'confirm-eliminar-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalConfirmacionEliminar', [], 'cerrarModalConfirmacionEliminar no esta disponible');
                    break;
                }
                case 'confirm-eliminar-submit': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('confirmarEliminarAnchoMetraje', [], 'confirmarEliminarAnchoMetraje no esta disponible');
                    break;
                }
                case 'produccion-confirm-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalConfirmarProduccion', [], 'cerrarModalConfirmarProduccion no esta disponible');
                    break;
                }
                case 'produccion-confirm-submit': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('confirmarEnvioProduccion', [], 'confirmarEnvioProduccion no esta disponible');
                    break;
                }
                case 'pasar-revisar-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarModalPasarRevisar', [], 'cerrarModalPasarRevisar no esta disponible');
                    break;
                }
                case 'filter-close-modal': {
                    event.preventDefault();
                    event.stopPropagation();
                    const modal = document.getElementById('filterModalInsumos');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                    break;
                }
                case 'filter-apply': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('applyFilters', [], 'applyFilters no esta disponible');
                    break;
                }
                case 'filter-select-all': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('selectAllFilters', [], 'selectAllFilters no esta disponible');
                    break;
                }
                case 'filter-deselect-all': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('deselectAllFilters', [], 'deselectAllFilters no esta disponible');
                    break;
                }
                case 'notif-ver-recibo-campana': {
                    event.preventDefault();
                    event.stopPropagation();
                    const ordenId = btn.getAttribute('data-orden-id');
                    safeCall('verReciboDesdeCampana', [ordenId], 'verReciboDesdeCampana no esta disponible');
                    break;
                }
                case 'selector-close-prendas': {
                    event.preventDefault();
                    event.stopPropagation();
                    safeCall('cerrarSelectorPrendas', [], 'cerrarSelectorPrendas no esta disponible');
                    break;
                }
                case 'selector-seleccionar-prenda': {
                    event.preventDefault();
                    event.stopPropagation();
                    const pedidoId = btn.getAttribute('data-pedido-id');
                    const prendaIndex = Number(btn.getAttribute('data-prenda-index'));
                    safeCall('seleccionarPrendaRecibo', [pedidoId, prendaIndex], 'seleccionarPrendaRecibo no esta disponible');
                    break;
                }
                case 'open-novedades-modal': {
                    event.preventDefault();
                    event.stopPropagation();
                    abrirModalNovedadesRecibo(btn);
                    break;
                }
                case 'galeria-mostrar-imagen': {
                    event.preventDefault();
                    event.stopPropagation();
                    const imageIndex = Number(btn.getAttribute('data-image-index'));
                    safeCall('mostrarImagen', [imageIndex], 'galeria.mostrarImagen no esta disponible');
                    break;
                }
                default:
                    break;
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('[data-insumos-action]');
            if (!form) return;

            const action = form.getAttribute('data-insumos-action');
            if (action === 'pasar-revisar-submit') {
                event.preventDefault();
                safeCall('confirmarPasarRevisar', [event], 'confirmarPasarRevisar no esta disponible');
            }
        });

        document.addEventListener('keydown', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) return;

            const action = target.getAttribute('data-insumos-action');
            if (action === 'observaciones-textarea' && event.ctrlKey && event.key === 'Enter') {
                event.preventDefault();
                safeCall('guardarObservaciones', [], 'guardarObservaciones no esta disponible');
            }
        });
    }

    function buildContract() {
        const current = window.insumos || {};

        const contract = {
            ...current,
            version: 'materiales-page-v1',
            ready: true,
            csrfToken: getCsrfToken(),
            services: {
                get core() {
                    return window.coreServices || null;
                },
                get insumoService() {
                    return window.insumoService || null;
                },
            },
            ui: {
                toggleRowCheck(button, event) {
                    return safeCall('toggleRowCheck', [button, event], 'toggleRowCheck no esta disponible');
                },
                openVerDropdown(button, event) {
                    return safeCall('crearDropdownVerRecibo', [event, button], 'crearDropdownVerRecibo no esta disponible');
                },
                openAccionesDropdown(button, event) {
                    return safeCall('crearDropdownAcciones', [event, button], 'crearDropdownAcciones no esta disponible');
                },
                closeAccionesDropdown() {
                    return safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                },
            },
            actions: {
                sendToProduction(reciboId, consecutivo) {
                    return safeCall('cambiarEstadoRecibo', [reciboId, consecutivo], 'cambiarEstadoRecibo no esta disponible');
                },
                openInsumosModal(pedidoProduccionId, prendaId, consecutivo, estado, tipoRecibo) {
                    return safeCall('abrirModalInsumos', [pedidoProduccionId, prendaId, consecutivo, estado, tipoRecibo], 'abrirModalInsumos no esta disponible');
                },
            },
            diagnostics: {
                loadedScripts: [
                    ...CRITICAL_SCRIPTS.map(s => s.src),
                    ...MAIN_SCRIPTS.map(s => s.src),
                    ...LAZY_SCRIPTS.map(s => s.src),
                ],
            },
        };

        window.insumos = contract;
        document.dispatchEvent(new CustomEvent('insumos:ready', { detail: { version: contract.version } }));
    }

    async function init() {
        if (state.initialized) return;
        if (state.loadingPromise) {
            await state.loadingPromise;
            return;
        }

        const pageTimeStart = performance.now();
        console.log('\n╔════════════════════════════════════════╗');
        console.log('║  INICIO CARGA PÁGINA - INSUMOS         ║');
        console.log('╚════════════════════════════════════════╝');
        console.log(`Timestamp: ${new Date().toLocaleTimeString()}`);
        console.log(`DOM Ready: ${document.readyState}`);

        showPageLoading();

        state.loadingPromise = (async () => {
            try {
                const timeScriptsStart = performance.now();
                const mainDuration = await loadModuleScripts();
                const timeScriptsEnd = performance.now();
                const durationScripts = (timeScriptsEnd - timeScriptsStart).toFixed(2);

                console.log(`\n✓ Scripts cargados: ${durationScripts}ms`);

                const timeFestivosStart = performance.now();
                await safeCall('inicializarFestivos', [], 'inicializarFestivos no esta disponible');
                const timeFestivosEnd = performance.now();
                const durationFestivos = (timeFestivosEnd - timeFestivosStart).toFixed(2);

                console.log(`✓ Festivos inicializados: ${durationFestivos}ms`);

                const timeBindStart = performance.now();
                bindDelegatedActions();
                buildContract();
                const timeBindEnd = performance.now();
                const durationBind = (timeBindEnd - timeBindStart).toFixed(2);

                console.log(`✓ Event handlers vinculados: ${durationBind}ms`);

                const pageTimeEnd = performance.now();
                const pageTotal = (pageTimeEnd - pageTimeStart).toFixed(2);

                state.initialized = true;

                console.log('\n╔════════════════════════════════════════╗');
                console.log('║  CARGA COMPLETADA - RESUMEN            ║');
                console.log('╚════════════════════════════════════════╝');
                console.log(`Scripts:           ${durationScripts}ms`);
                console.log(`Festivos:          ${durationFestivos}ms`);
                console.log(`Event Handlers:    ${durationBind}ms`);
                console.log(`───────────────────────────────────────`);
                console.log(`TOTAL JS FRONTEND: ${pageTotal}ms\n`);

            } catch (error) {
                console.error('[insumos] Error inicializando materiales-page-loader:', error);
                throw error;
            } finally {
                hidePageLoading();
            }
        })();

        await state.loadingPromise;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
