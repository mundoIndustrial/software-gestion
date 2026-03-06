@extends('layouts.app-without-sidebar')

@section('content')
<style>
    .tableros-ordenes-container {
        width: 100%;
        padding: 16px;
        transform: scale(0.8);
        transform-origin: top left;
        width: 125%;
    }

    .tableros-ordenes-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        align-items: start;
    }

    .tablero-modulo {
        border: 2px solid #3b82f6;
        background: #ffffff;
        padding: 12px;
        min-height: 75vh;
        display: flex;
        flex-direction: column;
    }

    .tablero-modulo-header {
        border: 1px solid #9ca3af;
        background: #e5e7eb;
        text-align: center;
        font-weight: 700;
        padding: 6px 8px;
        margin-bottom: 12px;
    }

    .tablero-modulo-controls {
        display: block;
        gap: 12px;
        margin-bottom: 12px;
    }

    .tablero-modulo-control label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #111827;
    }

    .tablero-modulo-control select,
    .tablero-modulo-control input {
        width: 100%;
        border: 1px solid #9ca3af;
        background: #ffffff;
        height: 28px;
        padding: 4px 8px;
        font-size: 12px;
    }

    .tablero-modulo-body {
        border: 1px solid #6b7280;
        background: #e5e7eb;
        flex: 1;
        min-height: 420px;
    }

    /* Ocultar botones flotantes en los tableros */
    .order-detail-modal-wrapper-tablero #floating-buttons-container {
        display: none !important;
    }
    
    /* Ocultar botones de cerrar en los tableros */
    .order-detail-modal-wrapper-tablero .modal-close,
    .order-detail-modal-wrapper-tablero .close-btn,
    .order-detail-modal-wrapper-tablero [onclick*="closeModal"],
    .order-detail-modal-wrapper-tablero .btn-close {
        display: none !important;
    }

    @media (max-width: 1100px) {
        .tableros-ordenes-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tableros-ordenes-container">
    <div class="tableros-ordenes-grid">
        <div class="tablero-modulo" data-module="1">
            <div class="tablero-modulo-header">MODULO 1</div>
            <div class="tablero-modulo-controls">
                <div class="tablero-modulo-control">
                    <label>Encargado: MÓDULO 1</label>
                </div>
                <div class="tablero-modulo-control">
                    <label>Seleccionar Orden</label>
                    <div class="orden-search-wrapper">
                        <input type="text" class="orden-search" data-module="1" placeholder="Buscar por recibo o cliente..." autocomplete="off">
                        <div class="orden-results" data-module="1" style="display:none;"></div>
                    </div>
                </div>
                <div class="tablero-modulo-control">
                    <button type="button" class="btn btn-sm btn-secondary limpiar-recibo-btn" data-module="1">Limpiar</button>
                </div>
            </div>
            <div class="tablero-modulo-body">
                <div class="tablero-recibo-host" data-module="1">
                    <div class="tablero-modal-overlay" data-module="1" style="display:none;" onclick=""></div>
                    <div class="order-detail-modal-wrapper-tablero" data-module="1" style="display:none;">
                        <x-orders-components.order-detail-modal />
                    </div>
                </div>
            </div>
        </div>

        <div class="tablero-modulo" data-module="2">
            <div class="tablero-modulo-header">MODULO 2</div>
            <div class="tablero-modulo-controls">
                <div class="tablero-modulo-control">
                    <label>Encargado: MÓDULO 2</label>
                </div>
                <div class="tablero-modulo-control">
                    <label>Seleccionar Orden</label>
                    <div class="orden-search-wrapper">
                        <input type="text" class="orden-search" data-module="2" placeholder="Buscar por recibo o cliente..." autocomplete="off">
                        <div class="orden-results" data-module="2" style="display:none;"></div>
                    </div>
                </div>
                <div class="tablero-modulo-control">
                    <button type="button" class="btn btn-sm btn-secondary limpiar-recibo-btn" data-module="2">Limpiar</button>
                </div>
            </div>
            <div class="tablero-modulo-body">
                <div class="tablero-recibo-host" data-module="2">
                    <div class="tablero-modal-overlay" data-module="2" style="display:none;" onclick=""></div>
                    <div class="order-detail-modal-wrapper-tablero" data-module="2" style="display:none;">
                        <x-orders-components.order-detail-modal />
                    </div>
                </div>
            </div>
        </div>

        <div class="tablero-modulo" data-module="3">
            <div class="tablero-modulo-header">MODULO 3</div>
            <div class="tablero-modulo-controls">
                <div class="tablero-modulo-control">
                    <label>Encargado: MÓDULO 3</label>
                </div>
                <div class="tablero-modulo-control">
                    <label>Seleccionar Orden</label>
                    <div class="orden-search-wrapper">
                        <input type="text" class="orden-search" data-module="3" placeholder="Buscar por recibo o cliente..." autocomplete="off">
                        <div class="orden-results" data-module="3" style="display:none;"></div>
                    </div>
                </div>
                <div class="tablero-modulo-control">
                    <button type="button" class="btn btn-sm btn-secondary limpiar-recibo-btn" data-module="3">Limpiar</button>
                </div>
            </div>
            <div class="tablero-modulo-body">
                <div class="tablero-recibo-host" data-module="3">
                    <div class="tablero-modal-overlay" data-module="3" style="display:none;" onclick=""></div>
                    <div class="order-detail-modal-wrapper-tablero" data-module="3" style="display:none;">
                        <x-orders-components.order-detail-modal />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const selectedReciboByModule = {};
        let moduleEncargados = {};
        
        // Obtener los nombres de los encargados y asignarlos a los módulos
        async function initializeModuleEncargados() {
            try {
                console.log('Cargando encargados desde API...');
                const json = await fetchJson('/tableros_ordenes/api/costureros');
                const costureros = Array.isArray(json?.data) ? json.data : [];
                
                console.log('Costureros encontrados:', costureros);
                
                // Filtrar solo los 3 usuarios específicos
                const costurerosFiltrados = costureros.filter(c => 
                    c.name === 'MODULO 1' || 
                    c.name === 'MODULO 2' || 
                    c.name === 'MODULO 3'
                );
                
                console.log('Costureros filtrados (solo MODULO 1, 2, 3):', costurerosFiltrados);
                
                if (costurerosFiltrados.length === 3) {
                    // Asignar los módulos específicos
                    moduleEncargados = {
                        '1': 'MODULO 1'.toLowerCase(),
                        '2': 'MODULO 2'.toLowerCase(), 
                        '3': 'MODULO 3'.toLowerCase()
                    };
                    
                    console.log('Mapeo de encargados:', moduleEncargados);
                    
                    // Actualizar las etiquetas en la interfaz
                    updateEncargadoLabels();
                } else {
                    console.warn('No se encontraron los 3 módulos requeridos. Encontrados:', costurerosFiltrados.length);
                    // Asignar valores por defecto si no se encuentran los 3
                    moduleEncargados = {
                        '1': 'modulo1',
                        '2': 'modulo2', 
                        '3': 'modulo3'
                    };
                }
            } catch (error) {
                console.error('Error cargando encargados:', error);
                // Valores por defecto si hay error
                moduleEncargados = {
                    '1': 'modulo1',
                    '2': 'modulo2', 
                    '3': 'modulo3'
                };
            }
        }
        
        // Actualizar las etiquetas de encargado en la interfaz
        function updateEncargadoLabels() {
            Object.keys(moduleEncargados).forEach(module => {
                const label = document.querySelector(`.tablero-modulo[data-module="${module}"] .tablero-modulo-control:first-child label`);
                if (label) {
                    const encargadoName = moduleEncargados[module];
                    label.textContent = `Encargado: ${encargadoName.charAt(0).toUpperCase() + encargadoName.slice(1)}`;
                }
            });
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function getCookie(name) {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()\[\]\\\/\+^]/g, '\\$&') + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
        }

        async function fetchJson(url) {
            const xsrf = getCookie('XSRF-TOKEN');
            const res = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                }
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return await res.json();
        }

        async function fetchJsonWithMethod(url, method, bodyObj) {
            const xsrf = getCookie('XSRF-TOKEN');
            const res = await fetch(url, {
                method,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {})
                },
                body: bodyObj ? JSON.stringify(bodyObj) : undefined,
            });
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return await res.json();
        }

        async function apiFijarRecibo(encargadoNombre, idRecibo) {
            return await fetchJsonWithMethod('/tableros_ordenes/api/recibos/fijar', 'POST', {
                encargado_nombre: encargadoNombre,
                id_recibo: idRecibo,
            });
        }

        async function apiObtenerReciboFijado(encargadoNombre) {
            const url = new URL(window.location.origin + '/tableros_ordenes/api/recibos/fijado');
            url.searchParams.set('encargado_nombre', encargadoNombre);
            return await fetchJson(url.toString());
        }

        async function apiLimpiarReciboFijado(encargadoNombre) {
            const url = new URL(window.location.origin + '/tableros_ordenes/api/recibos/fijado');
            url.searchParams.set('encargado_nombre', encargadoNombre);
            return await fetchJsonWithMethod(url.toString(), 'DELETE');
        }

        async function apiObtenerReciboPorId(reciboId) {
            const url = new URL(window.location.origin + '/tableros_ordenes/api/recibos/por-id');
            url.searchParams.set('id_recibo', String(reciboId));
            return await fetchJson(url.toString());
        }

        async function openReciboByIdForModule(module, reciboId) {
            const encargado = moduleEncargados[module];
            if (!encargado) return;

            const json = await apiObtenerReciboPorId(reciboId);
            const match = json?.data;
            if (!match) return;

            // Validar que el recibo pertenezca al encargado/módulo actual
            // (evita que un recibo fijado de otro módulo se abra en este)
            const urlValidar = new URL(window.location.origin + '/tableros_ordenes/api/recibos/buscar');
            urlValidar.searchParams.set('encargado_nombre', encargado);
            urlValidar.searchParams.set('q', String(match.numero_recibo));
            const jsonValidar = await fetchJson(urlValidar.toString());
            const itemsValidar = Array.isArray(jsonValidar?.data) ? jsonValidar.data : [];
            const pertenece = itemsValidar.some(i => Number(i.recibo_id) === Number(match.recibo_id));
            if (!pertenece) return;

            const { searchInput } = getModuleEls(module);
            if (searchInput) {
                searchInput.value = `#${match.numero_recibo} - ${match.cliente || ''}`;
            }

            selectedReciboByModule[module] = match;
            await openReciboInModule(module, match);
        }

        async function limpiarModuloUI(module) {
            const { searchInput, results } = getModuleEls(module);
            if (searchInput) searchInput.value = '';
            if (results) hideResults(results);

            delete selectedReciboByModule[module];

            const wrapper = document.querySelector(`.order-detail-modal-wrapper-tablero[data-module="${module}"]`);
            if (wrapper) {
                wrapper.style.display = 'none';
            }
        }

        function getModuleEls(module) {
            const searchInput = document.querySelector(`.orden-search[data-module="${module}"]`);
            const results = document.querySelector(`.orden-results[data-module="${module}"]`);
            
            return {
                searchInput: searchInput,
                results: results,
            };
        }

        function hideResults(resultsEl) {
            if (!resultsEl) return;
            resultsEl.style.display = 'none';
            resultsEl.innerHTML = '';
        }

        function renderResults(resultsEl, items, module) {
            if (!resultsEl) return;

            if (!items || items.length === 0) {
                hideResults(resultsEl);
                return;
            }

            resultsEl.innerHTML = items.map((it) => {
                const label = `#${escapeHtml(it.numero_recibo)} - ${escapeHtml(it.cliente || '')}`;
                return `
                    <div class="orden-result-item" data-module="${module}" data-recibo='${escapeHtml(JSON.stringify(it))}'>
                        ${label}
                    </div>
                `;
            }).join('');

            resultsEl.style.display = 'block';
        }

        function debounce(fn, wait) {
            let t;
            return function (...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        async function searchRecibos(module) {
            const { searchInput, results } = getModuleEls(module);
            if (!searchInput || !results) return;

            const encargado = moduleEncargados[module];
            const q = searchInput.value.trim();

            if (!encargado) {
                hideResults(results);
                return;
            }

            if (q.length < 1) {
                hideResults(results);
                return;
            }

            const url = new URL(window.location.origin + '/tableros_ordenes/api/recibos/buscar');
            url.searchParams.set('encargado_nombre', encargado);
            url.searchParams.set('q', q);

            try {
                const json = await fetchJson(url.toString());
                renderResults(results, Array.isArray(json?.data) ? json.data : [], module);
            } catch (error) {
                console.error('Error en la búsqueda:', error);
                hideResults(results);
            }
        }

        async function setupModule(module) {
            const { searchInput, results } = getModuleEls(module);
            if (!searchInput || !results) return;

            const debounced = debounce(() => {
                searchRecibos(module).catch((error) => {
                    console.error(`Error en búsqueda del módulo ${module}:`, error);
                    hideResults(results);
                });
            }, 250);

            searchInput.addEventListener('input', debounced);

            searchInput.addEventListener('focus', () => {
                if (results.innerHTML.trim() !== '') {
                    results.style.display = 'block';
                }
            });

            document.addEventListener('click', (e) => {
                if (!results.contains(e.target) && e.target !== searchInput) {
                    hideResults(results);
                }
            });

            results.addEventListener('click', async (e) => {
                const item = e.target.closest('.orden-result-item');
                if (!item) return;

                const payload = item.getAttribute('data-recibo');
                if (!payload) return;

                let recibo;
                try {
                    recibo = JSON.parse(payload);
                } catch (error) {
                    console.error('Error parseando recibo:', error);
                    return;
                }

                selectedReciboByModule[module] = recibo;
                searchInput.value = `#${recibo.numero_recibo} - ${recibo.cliente || ''}`;
                hideResults(results);

                const encargado = moduleEncargados[module];
                if (encargado && recibo?.recibo_id) {
                    try {
                        await apiFijarRecibo(encargado, recibo.recibo_id);
                    } catch (error) {
                        console.error('Error fijando recibo:', error);
                    }
                }

                try {
                    await openReciboInModule(module, recibo);
                } catch (error) {
                    console.error(`Error abriendo recibo en módulo ${module}:`, error);
                }
            });
        }

        async function openReciboInModule(module, recibo) {
            const host = document.querySelector(`.tablero-recibo-host[data-module="${module}"]`);
            const wrapper = document.querySelector(`.order-detail-modal-wrapper-tablero[data-module="${module}"]`);
            if (!host || !wrapper) return;

            const pedidoId = recibo?.pedido_produccion_id;
            const prendaId = recibo?.prenda_id;
            if (!pedidoId || !prendaId) {
                console.error(`Datos incompletos para módulo ${module}:`, { pedidoId, prendaId, recibo });
                return;
            }

            // Mostrar contenedor (sin overlay global)
            wrapper.style.display = 'block';

            // Ocultar botones flotantes específicos de este módulo
            setTimeout(() => {
                const floatingButtons = wrapper.querySelector('#floating-buttons-container');
                if (floatingButtons) {
                    floatingButtons.style.display = 'none';
                }
                
                // También ocultar cualquier botón de cerrar (X) que pueda existir
                const closeButtons = wrapper.querySelectorAll('.modal-close, .close-btn, [onclick*="closeModal"], .btn-close');
                closeButtons.forEach(btn => {
                    btn.style.display = 'none';
                });
            }, 100);

            // SOLUCIÓN AGRESIVA: Eliminar temporalmente todos los otros modales
            // para forzar al sistema a usar solo el del módulo actual
            const otrosWrappers = document.querySelectorAll('.order-detail-modal-wrapper-tablero[data-module]:not([data-module="' + module + '"])');
            const otrosWrappersData = [];
            
            console.log(`Eliminando ${otrosWrappers.length} otros wrappers para módulo ${module}`);
            
            // Guardar los otros wrappers y eliminarlos del DOM temporalmente
            otrosWrappers.forEach(w => {
                otrosWrappersData.push({
                    element: w,
                    parent: w.parentNode,
                    nextSibling: w.nextSibling
                });
                w.remove();
            });

            // Cambiar el ID del wrapper actual para que el sistema lo encuentre
            wrapper.id = 'order-detail-modal-wrapper';
            console.log(`Wrapper del módulo ${module} ahora tiene ID 'order-detail-modal-wrapper'`);

            // Reusar pipeline existente (renderiza dentro del wrapper)
            if (typeof window.openOrderDetailModalWithProcess === 'function') {
                try {
                    await window.openOrderDetailModalWithProcess(Number(pedidoId), Number(prendaId), 'costura');
                    
                    // Restaurar todo después de que se llene el modal
                    setTimeout(() => {
                        // Restaurar ID original
                        wrapper.id = 'order-detail-modal-wrapper-tablero';
                        wrapper.setAttribute('data-module', module);
                        
                        // Restaurar los otros wrappers
                        otrosWrappersData.forEach(({ element, parent, nextSibling }) => {
                            if (nextSibling) {
                                parent.insertBefore(element, nextSibling);
                            } else {
                                parent.appendChild(element);
                            }
                        });
                        
                        console.log(`Restaurados ${otrosWrappersData.length} wrappers para módulo ${module}`);
                    }, 1000);
                } catch (error) {
                    console.error(`Error abriendo modal para módulo ${module}:`, error);
                    
                    // Restaurar todo en caso de error
                    wrapper.id = 'order-detail-modal-wrapper-tablero';
                    wrapper.setAttribute('data-module', module);
                    
                    otrosWrappersData.forEach(({ element, parent, nextSibling }) => {
                        if (nextSibling) {
                            parent.insertBefore(element, nextSibling);
                        } else {
                            parent.appendChild(element);
                        }
                    });
                    
                    // Intentar cargar los datos manualmente
                    cargarDatosManualmente(module, pedidoId, prendaId, recibo);
                }
            } else {
                console.error('La función openOrderDetailModalWithProcess no está disponible');
                
                // Restaurar todo
                wrapper.id = 'order-detail-modal-wrapper-tablero';
                wrapper.setAttribute('data-module', module);
                
                otrosWrappersData.forEach(({ element, parent, nextSibling }) => {
                    if (nextSibling) {
                        parent.insertBefore(element, nextSibling);
                    } else {
                        parent.appendChild(element);
                    }
                });
                
                // Cargar datos manualmente
                cargarDatosManualmente(module, pedidoId, prendaId, recibo);
            }
        }

        // Función fallback para cargar datos manualmente si openOrderDetailModalWithProcess falla
        async function cargarDatosManualmente(module, pedidoId, prendaId, recibo) {
            console.log(`Intentando cargar datos manualmente para módulo ${module}:`, { pedidoId, prendaId });
            
            try {
                // Usar la misma API que usa el sistema normal
                const response = await fetchJson(`/pedidos-public/${pedidoId}/recibos-datos`);
                console.log(`Datos del pedido obtenidos para módulo ${module}:`, response);
                
                if (response && response.cliente) {
                    const wrapper = document.querySelector(`.order-detail-modal-wrapper-tablero[data-module="${module}"]`);
                    if (!wrapper) return;
                    
                    // Llenar los campos básicos del modal
                    const pedidoElement = wrapper.querySelector('#order-pedido');
                    const clienteElement = wrapper.querySelector('#cliente-value');
                    const asesoraElement = wrapper.querySelector('#asesora-value');
                    const formaPagoElement = wrapper.querySelector('#forma-pago-value');
                    const descripcionElement = wrapper.querySelector('#descripcion-text');
                    
                    if (pedidoElement) {
                        pedidoElement.textContent = `#${recibo.numero_recibo}`;
                    }
                    
                    if (clienteElement) {
                        clienteElement.textContent = response.cliente || '';
                    }
                    
                    if (asesoraElement) {
                        asesoraElement.textContent = response.asesor || '';
                    }
                    
                    if (formaPagoElement) {
                        formaPagoElement.textContent = response.formaPago || '';
                    }
                    
                    // Mostrar información básica si no hay descripción
                    if (descripcionElement && !descripcionElement.textContent.trim()) {
                        descripcionElement.innerHTML = `
                            <div style="text-align: center; margin: 20px 0;">
                                <h4>RECIBO #${recibo.numero_recibo}</h4>
                                <p><strong>Cliente:</strong> ${response.cliente || ''}</p>
                                <p><strong>Asesor:</strong> ${response.asesor || ''}</p>
                                <p><strong>Forma de pago:</strong> ${response.formaPago || ''}</p>
                                <p><strong>Número pedido:</strong> ${response.numeroPedido || ''}</p>
                            </div>
                        `;
                    }
                    
                    console.log(`Datos cargados manualmente para módulo ${module}`);
                }
            } catch (error) {
                console.error(`Error cargando datos manualmente para módulo ${module}:`, error);
                
                // Último recurso: mostrar información básica del recibo
                const wrapper = document.querySelector(`.order-detail-modal-wrapper-tablero[data-module="${module}"]`);
                if (wrapper) {
                    const descripcionElement = wrapper.querySelector('#descripcion-text');
                    if (descripcionElement) {
                        descripcionElement.innerHTML = `
                            <div style="text-align: center; margin: 20px 0;">
                                <h4>RECIBO #${recibo.numero_recibo}</h4>
                                <p><strong>Cliente:</strong> ${recibo.cliente || ''}</p>
                                <p><em>Información básica del recibo</em></p>
                            </div>
                        `;
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', async () => {
            console.log('DOM cargado, iniciando configuración...');
            
            // Primero inicializar los encargados
            await initializeModuleEncargados();
            
            console.log('Encargados inicializados, configurando módulos...');
            
            // Luego configurar cada módulo
            ['1', '2', '3'].forEach(m => {
                console.log(`Configurando módulo ${m}...`);
                setupModule(m).catch((error) => {
                    console.error(`Error configurando módulo ${m}:`, error);
                });
            });

            // Botones limpiar
            document.querySelectorAll('.limpiar-recibo-btn').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const module = btn.getAttribute('data-module');
                    const encargado = moduleEncargados[module];
                    if (!encargado) return;
                    try {
                        await apiLimpiarReciboFijado(encargado);
                    } catch (error) {
                        console.error('Error limpiando recibo fijado:', error);
                    }
                    await limpiarModuloUI(module);
                });
            });

            // Cargar recibos fijados por módulo
            for (const m of ['1', '2', '3']) {
                const encargado = moduleEncargados[m];
                if (!encargado) continue;
                try {
                    const json = await apiObtenerReciboFijado(encargado);
                    const fijado = json?.data;
                    if (fijado?.id_recibo) {
                        await openReciboByIdForModule(m, fijado.id_recibo);
                    }
                } catch (error) {
                    console.error('Error cargando recibo fijado:', error);
                }
            }

            // Tiempo real (Reverb): escuchar cambios de fijar/limpiar entre computadores
            if (typeof window.waitForEcho === 'function') {
                window.waitForEcho(() => {
                    const echo = window.EchoInstance || window.Echo;
                    if (!echo || typeof echo.channel !== 'function') {
                        return;
                    }

                    echo.channel('tableros-ordenes')
                        .listen('.recibo.fijado.actualizado', async (payload) => {
                            const encargado = String(payload?.encargado || '').toLowerCase().trim();
                            const accion = String(payload?.accion || '').toLowerCase().trim();
                            const idRecibo = payload?.id_recibo;

                            const module = Object.keys(moduleEncargados).find(m => String(moduleEncargados[m]).toLowerCase().trim() === encargado);
                            if (!module) return;

                            if (accion === 'limpiar') {
                                await limpiarModuloUI(module);
                                return;
                            }

                            if (accion === 'fijar' && idRecibo) {
                                await openReciboByIdForModule(module, idRecibo);
                            }
                        });
                });
            }
        });
    })();
</script>

<script type="module" src="/js/modulos/pedidos-recibos/PedidosRecibosModule.js"></script>

<style>
    .orden-search-wrapper {
        position: relative;
    }

    .orden-results {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #9ca3af;
        max-height: 220px;
        overflow: auto;
        z-index: 50;
    }

    .orden-result-item {
        padding: 8px;
        font-size: 12px;
        cursor: pointer;
        border-bottom: 1px solid #e5e7eb;
    }

    .orden-result-item:hover {
        background: #f3f4f6;
    }

    .tablero-recibo-host {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 8px;
    }

    .order-detail-modal-wrapper-tablero {
        width: 100%;
        max-width: 672px;
    }

    .tablero-modulo {
        min-width: 0;
        overflow: hidden;
    }

    .tablero-modulo-body {
        min-width: 0;
        overflow-x: hidden;
        overflow-y: auto;
    }

    .order-detail-modal-wrapper-tablero {
        max-width: 100%;
        margin-left: -100px; /* Mover hacia la izquierda */
        margin-top: -120px; /* Subir 30px hacia arriba */
    }
</style>
@endsection
