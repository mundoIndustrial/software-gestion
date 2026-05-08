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

    const DEBUG = false;
    const shouldProfile = () => false;
    const debugLog = (...args) => {
        if (DEBUG) console.log(...args);
    };

    function measureStart() {
        return shouldProfile() ? performance.now() : 0;
    }

    function measureEnd(label, startedAt, meta = null) {
        if (!shouldProfile()) return;
        const duration = (performance.now() - startedAt).toFixed(2);
        if (meta) {
            console.log(`[insumos][perf] ${label}: ${duration}ms`, meta);
        } else {
            console.log(`[insumos][perf] ${label}: ${duration}ms`);
        }
    }

    const CRITICAL_SCRIPTS = [
        { src: '/js/insumos/index.js', type: 'module' },
    ];

    const MAIN_SCRIPTS = [];

    const LAZY_SCRIPTS = [];

    const FEATURE_SCRIPTS = {
        rowCheck: [
            { src: '/js/insumos/form-handlers-insumos.js' },
        ],
        tracking: [
            { src: '/js/ordersjs/tracking-modal-utils.js' },
            { src: '/js/ordersjs/tracking-modal-handler.js', type: 'module' },
        ],
        invoice: [
            { src: '/js/modulos/invoice/InvoiceLazyLoader.js' },
            { src: '/js/asesores/invoice-from-list.js' },
            { src: '/js/asesores/receipt-manager.js' },
        ],
        modalHandlers: [
            { src: '/js/insumos/modal-handlers-insumos.js' },
        ],
        insumosModals: [
            { src: '/js/insumos/insumos-modal-management.js' },
            { src: '/js/insumos/material-operations-insumos.js' },
            { src: '/js/insumos/form-handlers-insumos.js' },
            { src: '/js/insumos/modal-ancho-metraje-insumos.js' },
        ],
        pasarRevisar: [
            { src: '/js/insumos/pasar-a-revisar-insumos.js' },
        ],
        gallery: [
            { src: '/js/insumos/insumos-galeria.js' },
        ],
    };

    const state = {
        initialized: false,
        loadingPromise: null,
        featureLoadPromises: {},
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
            const absoluteSrc = new URL(scriptDef.src, window.location.origin).pathname;
            const alreadyLoaded = Array.from(document.scripts).some((existing) => {
                try {
                    return new URL(existing.src, window.location.origin).pathname === absoluteSrc;
                } catch (_error) {
                    return false;
                }
            });

            if (alreadyLoaded) {
                resolve(scriptDef.src);
                return;
            }

            const script = document.createElement('script');
            script.src = scriptDef.src;
            script.defer = false;
            script.async = false;
            if (scriptDef.type === 'module') {
                script.type = 'module';
            }
            script.onload = () => {
                const duration = (performance.now() - timeStart).toFixed(2);
                debugLog(`  ✓ ${scriptDef.src.split('/').pop()} (${duration}ms)`);
                resolve(scriptDef.src);
            };
            script.onerror = () => reject(new Error(`No se pudo cargar: ${scriptDef.src}`));
            document.body.appendChild(script);
        });
    }

    async function ensureFeatureScripts(featureName) {
        if (!FEATURE_SCRIPTS[featureName] || FEATURE_SCRIPTS[featureName].length === 0) {
            return;
        }

        if (state.featureLoadPromises[featureName]) {
            await state.featureLoadPromises[featureName];
            return;
        }

        state.featureLoadPromises[featureName] = (async () => {
            const perfStart = measureStart();
            const scripts = FEATURE_SCRIPTS[featureName];
            debugLog(`[insumos] Cargando feature scripts: ${featureName} (${scripts.length})`);
            for (const scriptDef of scripts) {
                await loadScript(scriptDef);
            }
            debugLog(`[insumos] Feature scripts cargados: ${featureName}`);
            measureEnd(`feature:${featureName}`, perfStart, { scripts: scripts.length });
        })();

        await state.featureLoadPromises[featureName];
    }

    async function ensureFeatures(featureNames) {
        for (const featureName of featureNames) {
            await ensureFeatureScripts(featureName);
        }
    }

    async function loadModuleScripts() {
        const timeStart = performance.now();
        debugLog('\n═══ CARGA DE SCRIPTS FRONTEND (OPTIMIZADO) ═══');
        debugLog(`Cargando ${CRITICAL_SCRIPTS.length + MAIN_SCRIPTS.length + LAZY_SCRIPTS.length} scripts...\n`);

        debugLog(`Fase 1: Scripts críticos (${CRITICAL_SCRIPTS.length})...`);
        for (const scriptDef of CRITICAL_SCRIPTS) {
            await loadScript(scriptDef);
        }

        debugLog(`\nFase 2: Scripts principales (${MAIN_SCRIPTS.length} en paralelo)...`);
        await Promise.all(MAIN_SCRIPTS.map(scriptDef => loadScript(scriptDef)));

        const mainDuration = (performance.now() - timeStart).toFixed(2);
        debugLog(`\n✓ Scripts principales cargados en ${mainDuration}ms`);

        debugLog(`\nFase 3: Scripts lazy-load (${LAZY_SCRIPTS.length} en background/idle)...`);
        const lazyLoad = async () => {
            const lazyStart = measureStart();
            for (const scriptDef of LAZY_SCRIPTS) {
                await loadScript(scriptDef);
            }
            const totalDuration = (performance.now() - timeStart).toFixed(2);
            debugLog(`✓ Lazy scripts cargados - Total: ${totalDuration}ms\n`);
            measureEnd('lazy-scripts', lazyStart, { count: LAZY_SCRIPTS.length });
        };

        const runLazyLoad = () => {
            lazyLoad().catch((error) => {
                console.error('[insumos] Error cargando lazy scripts:', error);
            });
        };

        if (typeof window.requestIdleCallback === 'function') {
            window.requestIdleCallback(runLazyLoad, { timeout: 3000 });
        } else {
            setTimeout(runLazyLoad, 1200);
        }

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

    function setActionButtonLoading(button, isLoading, loadingText = 'Cargando...') {
        if (!button) return;

        if (isLoading) {
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }

            button.disabled = true;
            button.style.opacity = '0.7';
            button.style.pointerEvents = 'none';
            button.innerHTML = `
                <i class="fas fa-spinner fa-spin" style="color:#0284c7;font-size:1rem;"></i>
                <span>${escapeHtml(loadingText)}</span>
            `;
            return;
        }

        const originalHtml = button.dataset.originalHtml;
        if (originalHtml) {
            button.innerHTML = originalHtml;
            delete button.dataset.originalHtml;
        }

        button.disabled = false;
        button.style.opacity = '';
        button.style.pointerEvents = '';
    }

    function showTrackingLoadingState(message = 'Cargando seguimiento...') {
        const trackingModal = document.getElementById('orderTrackingModal');
        const trackingOverlay = document.getElementById('trackingModalOverlay');
        const trackingHeader = document.getElementById('trackingPrendaReciboHeader');
        const timelineContainer = document.getElementById('trackingTimelineContainer');

        if (trackingOverlay) {
            trackingOverlay.style.display = 'block';
        }

        if (trackingModal) {
            trackingModal.style.setProperty('display', 'flex', 'important');
            trackingModal.classList.add('show');
        }

        if (trackingHeader) {
            trackingHeader.textContent = message;
        }

        if (timelineContainer) {
            timelineContainer.innerHTML = `
                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    gap:.75rem;
                    min-height:180px;
                    color:#334155;
                    font-weight:600;
                ">
                    <i class="fas fa-spinner fa-spin" style="color:#0284c7;font-size:1.25rem;"></i>
                    <span>${escapeHtml(message)}</span>
                </div>
            `;
        }
    }

    function closeTrackingLoadingStateIfError() {
        const trackingModal = document.getElementById('orderTrackingModal');
        const trackingOverlay = document.getElementById('trackingModalOverlay');

        if (trackingOverlay) {
            trackingOverlay.style.display = 'none';
        }

        if (trackingModal) {
            trackingModal.style.setProperty('display', 'none', 'important');
            trackingModal.classList.remove('show');
        }
    }

    async function withTimeout(promise, timeoutMs, timeoutMessage) {
        let timeoutId;
        const timeoutPromise = new Promise((_, reject) => {
            timeoutId = setTimeout(() => reject(new Error(timeoutMessage)), timeoutMs);
        });

        try {
            return await Promise.race([promise, timeoutPromise]);
        } finally {
            clearTimeout(timeoutId);
        }
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

        document.addEventListener('click', async function (event) {
            const btn = event.target.closest('[data-insumos-action]');
            if (!btn) return;

            const action = btn.getAttribute('data-insumos-action');
            if (!action) return;

            switch (action) {
                case 'toggle-row-check': {
                    event.preventDefault();
                    await ensureFeatureScripts('rowCheck');
                    safeCall('toggleRowCheck', [btn, event], 'toggleRowCheck no esta disponible');
                    break;
                }
                case 'ver-recibo-dropdown': {
                    event.preventDefault();
                    event.stopPropagation();
                    if (typeof event.stopImmediatePropagation === 'function') {
                        event.stopImmediatePropagation();
                    }
                    // Warm-up en background: adelantar carga de tracking antes del click en "Seguimiento".
                    ensureFeatureScripts('tracking').catch((error) => {
                        console.warn('[insumos] No se pudo precargar tracking en background:', error);
                    });
                    safeCall('crearDropdownVerRecibo', [event, btn], 'crearDropdownVerRecibo no esta disponible');
                    break;
                }
                case 'acciones-dropdown': {
                    event.preventDefault();
                    event.stopPropagation();
                    // Warm-up en background para que las acciones abran instantaneo al primer click.
                    ensureFeatures(['modalHandlers', 'insumosModals', 'pasarRevisar']).catch((error) => {
                        console.warn('[insumos] No se pudo precargar acciones en background:', error);
                    });
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
                case 'enviar-produccion-reflectivo': {
                    event.preventDefault();
                    event.stopPropagation();
                    const reciboId = btn.getAttribute('data-recibo-id');
                    const consecutivo = btn.getAttribute('data-consecutivo');
                    safeCall('enviarProduccionReflectivo', [reciboId, consecutivo], 'enviarProduccionReflectivo no esta disponible');
                    break;
                }
                case 'close-modal-overlay': {
                    event.preventDefault();
                    await ensureFeatureScripts('modalHandlers');
                    safeCall('closeModalOverlay', [], 'closeModalOverlay no esta disponible');
                    break;
                }
                case 'dropdown-ver-detalle-recibo': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('modalHandlers');
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
                    setActionButtonLoading(btn, true, 'Cargando seguimiento...');
                    showTrackingLoadingState('Preparando seguimiento...');
                    try {
                        await withTimeout(
                            ensureFeatureScripts('tracking'),
                            10000,
                            'Tiempo de espera agotado cargando el módulo de seguimiento'
                        );
                        const pedidoId = btn.getAttribute('data-pedido-id');
                        const prendaId = btn.getAttribute('data-prenda-id');
                        const prendaBodegaId = btn.getAttribute('data-prenda-bodega-id');
                        const reciboId = btn.getAttribute('data-recibo-id') || null;
                        const numeroRecibo = btn.getAttribute('data-numero-recibo') || null;
                        const tipoRecibo = btn.getAttribute('data-tipo-recibo') || 'REFLECTIVO';
                        const esParcial = btn.getAttribute('data-es-parcial') === '1';
                        const pedidoParcialId = btn.getAttribute('data-pedido-parcial-id') || null;
                        await Promise.resolve(
                            safeCall(
                                'abrirSeguimientoRecibo',
                                [pedidoId, prendaId, numeroRecibo, null, tipoRecibo, esParcial, pedidoParcialId, prendaBodegaId, reciboId],
                                'abrirSeguimientoRecibo no esta disponible'
                            )
                        );
                        safeCall('cerrarDropdownVerRecibo', [], 'cerrarDropdownVerRecibo no esta disponible');
                    } catch (error) {
                        console.error('[insumos] Error al abrir seguimiento:', error);
                        closeTrackingLoadingStateIfError();
                        alert('No se pudo abrir el seguimiento. Intenta nuevamente.');
                    } finally {
                        setActionButtonLoading(btn, false);
                    }
                    break;
                }
                case 'dropdown-acciones-gestionar-insumos': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatures(['modalHandlers', 'insumosModals']);
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
                    await ensureFeatures(['modalHandlers', 'insumosModals']);
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
                    await ensureFeatureScripts('pasarRevisar');
                    const reciboId = btn.getAttribute('data-recibo-id');
                    const pedidoProduccionId = btn.getAttribute('data-pedido-produccion-id');
                    safeCall('abrirModalPasarRevisar', [reciboId, pedidoProduccionId], 'abrirModalPasarRevisar no esta disponible');
                    safeCall('cerrarDropdownAcciones', [], 'cerrarDropdownAcciones no esta disponible');
                    break;
                }
                case 'material-open-observaciones': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    const materialId = btn.getAttribute('data-material-id');
                    const materialName = btn.getAttribute('data-material-name') || '';
                    safeCall('abrirModalObservaciones', [materialId, materialName], 'abrirModalObservaciones no esta disponible');
                    break;
                }
                case 'material-delete-row': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    const materialId = btn.getAttribute('data-material-id');
                    safeCall('eliminarFilaMaterial', [materialId], 'eliminarFilaMaterial no esta disponible');
                    break;
                }
                case 'material-add-row': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('agregarMaterialModal', [], 'agregarMaterialModal no esta disponible');
                    break;
                }
                case 'material-save-changes': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('guardarInsumosModal', [], 'guardarInsumosModal no esta disponible');
                    break;
                }
                case 'modal-insumos-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('cerrarModalInsumos', [], 'cerrarModalInsumos no esta disponible');
                    break;
                }
                case 'observaciones-save': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('guardarObservaciones', [], 'guardarObservaciones no esta disponible');
                    break;
                }
                case 'observaciones-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('cerrarModalObservaciones', [], 'cerrarModalObservaciones no esta disponible');
                    break;
                }
                case 'ancho-metraje-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('cerrarModalAnchoMetraje', [], 'cerrarModalAnchoMetraje no esta disponible');
                    break;
                }
                case 'ancho-metraje-open-delete-confirm': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('abrirModalConfirmacionEliminar', [], 'abrirModalConfirmacionEliminar no esta disponible');
                    break;
                }
                case 'ancho-metraje-save': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('guardarAnchoMetraje', [], 'guardarAnchoMetraje no esta disponible');
                    break;
                }
                case 'confirm-eliminar-close': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
                    safeCall('cerrarModalConfirmacionEliminar', [], 'cerrarModalConfirmacionEliminar no esta disponible');
                    break;
                }
                case 'confirm-eliminar-submit': {
                    event.preventDefault();
                    event.stopPropagation();
                    await ensureFeatureScripts('insumosModals');
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
                    await ensureFeatureScripts('pasarRevisar');
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
                    await ensureFeatureScripts('invoice');
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
                    await ensureFeatureScripts('gallery');
                    const imageIndex = Number(btn.getAttribute('data-image-index'));
                    safeCall('mostrarImagen', [imageIndex], 'galeria.mostrarImagen no esta disponible');
                    break;
                }
                default:
                    break;
            }
        });

        document.addEventListener('submit', async function (event) {
            const form = event.target.closest('[data-insumos-action]');
            if (!form) return;

            const action = form.getAttribute('data-insumos-action');
            if (action === 'pasar-revisar-submit') {
                event.preventDefault();
                await ensureFeatureScripts('pasarRevisar');
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
        debugLog('\n╔════════════════════════════════════════╗');
        debugLog('║  INICIO CARGA PÁGINA - INSUMOS         ║');
        debugLog('╚════════════════════════════════════════╝');
        debugLog(`Timestamp: ${new Date().toLocaleTimeString()}`);
        debugLog(`DOM Ready: ${document.readyState}`);

        showPageLoading();

        state.loadingPromise = (async () => {
            try {
                const timeScriptsStart = performance.now();
                const mainDuration = await loadModuleScripts();
                const timeScriptsEnd = performance.now();
                const durationScripts = (timeScriptsEnd - timeScriptsStart).toFixed(2);

                debugLog(`\n✓ Scripts cargados: ${durationScripts}ms`);

                const timeFestivosStart = performance.now();
                await safeCall('inicializarFestivos', [], 'inicializarFestivos no esta disponible');
                const timeFestivosEnd = performance.now();
                const durationFestivos = (timeFestivosEnd - timeFestivosStart).toFixed(2);

                debugLog(`✓ Festivos inicializados: ${durationFestivos}ms`);

                const timeBindStart = performance.now();
                bindDelegatedActions();
                buildContract();
                const timeBindEnd = performance.now();
                const durationBind = (timeBindEnd - timeBindStart).toFixed(2);

                debugLog(`✓ Event handlers vinculados: ${durationBind}ms`);

                const pageTimeEnd = performance.now();
                const pageTotal = (pageTimeEnd - pageTimeStart).toFixed(2);

                state.initialized = true;

                debugLog('\n╔════════════════════════════════════════╗');
                debugLog('║  CARGA COMPLETADA - RESUMEN            ║');
                debugLog('╚════════════════════════════════════════╝');
                debugLog(`Scripts:           ${durationScripts}ms`);
                debugLog(`Festivos:          ${durationFestivos}ms`);
                debugLog(`Event Handlers:    ${durationBind}ms`);
                debugLog(`───────────────────────────────────────`);
                debugLog(`TOTAL JS FRONTEND: ${pageTotal}ms\n`);

                measureEnd('boot-total', pageTimeStart, {
                    criticalScripts: CRITICAL_SCRIPTS.length,
                    mainScripts: MAIN_SCRIPTS.length,
                    lazyScripts: LAZY_SCRIPTS.length,
                });

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
