{{-- Modal Intermedio: Selector de Prendas y Procesos --}}
{{-- Reutiliza el modal de recibo existente, solo cambia la forma de invocarlo --}}

<div id="recibos-process-selector-overlay" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9996; animation: fadeIn 0.3s ease-in-out;"
     onclick="if(event.target === this) cerrarSelectorRecibos()">
</div>

<div id="recibos-process-selector-modal" 
     style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); z-index: 9997;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #3b82f6, #0ea5e9); color: white; padding: 24px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;">
        <div>
            <h2 style="margin: 0; font-size: 20px; font-weight: 700;">Recibos de Producción</h2>
            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 14px;">Pedido <span id="selector-pedido-numero"></span></p>
        </div>
        <button onclick="cerrarSelectorRecibos()" style="background: rgba(255,255,255,0.3); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
            ✕
        </button>
    </div>

    <!-- Contenido -->
    <div style="padding: 24px;">
        
        <!-- Loading State -->
        <div id="selector-loading" style="display: none; text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 16px; color: #6b7280; font-size: 14px;">Cargando prendas y procesos...</p>
        </div>

        <!-- Error State -->
        <div id="selector-error" style="display: none; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; color: #991b1b;">
            <p style="margin: 0; font-weight: 600;">Error al cargar los recibos</p>
            <p id="selector-error-message" style="margin: 8px 0 0 0; font-size: 14px;"></p>
        </div>

        <!-- Prendas List -->
        <div id="selector-prendas-list"></div>

    </div>

</div>

<!-- Modal de Confirmación para Activar/Desactivar Recibo -->
<div id="confirmar-recibo-modal" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99998;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
        <h3 style="margin: 0 0 16px 0; color: #1f2937; font-size: 18px;">
            <span id="confirmar-titulo">Confirmar Acción</span>
        </h3>
        <p style="margin: 0 0 24px 0; color: #6b7280; line-height: 1.5;">
            <span id="confirmar-mensaje">¿Está seguro de realizar esta acción?</span>
        </p>
        <div id="confirmar-loading" style="display: none; text-align: center; margin: 20px 0;">
            <div style="display: inline-block; width: 20px; height: 20px; border: 2px solid #e5e7eb; border-top: 2px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin: 8px 0 0 0; color: #6b7280; font-size: 14px;">Procesando...</p>
        </div>
        <div id="confirmar-botones" style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="cerrarModalConfirmar()" 
                    style="background: #e5e7eb; color: #374151; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                Cancelar
            </button>
            <button id="confirmar-boton" 
                    style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 500;">
                Confirmar
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .prenda-accordion {
        margin-bottom: 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }

    .prenda-header {
        background: #f3f4f6;
        padding: 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
        user-select: none;
    }

    .prenda-header:hover {
        background: #e5e7eb;
    }

    .prenda-header.expanded {
        background: #dbeafe;
        border-bottom: 2px solid #3b82f6;
    }

    .prenda-title {
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        flex: 1;
    }

    .prenda-subtitle {
        font-size: 13px;
        color: #6b7280;
        margin-top: 4px;
    }

    .prenda-chevron {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
        color: #3b82f6;
    }

    .prenda-chevron.expanded {
        transform: rotate(180deg);
    }

    .prenda-processes {
        display: none;
        background: white;
        border-top: 1px solid #e5e7eb;
    }

    .prenda-processes.visible {
        display: block;
    }

    .proceso-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        transition: background 0.2s;
    }

    .proceso-item:hover {
        background: #f9fafb;
    }

    .proceso-item:last-child {
        border-bottom: none;
    }

    .proceso-info {
        flex: 1;
    }

    .proceso-name {
        font-weight: 500;
        color: #1f2937;
        margin: 0;
    }

    .proceso-estado {
        font-size: 13px;
        margin-top: 4px;
        font-weight: 600;
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        color: white;
    }

    .proceso-estado.pendiente {
        background: #ef4444; /* Rojo */
    }

    .proceso-estado.en-proceso {
        background: #f59e0b; /* Amarillo */
    }

    .proceso-estado.terminado {
        background: #10b981; /* Verde */
    }

    .proceso-arrow {
        color: #3b82f6;
        font-size: 20px;
        margin-left: 12px;
        transition: all 0.2s;
    }

    .proceso-item:hover .proceso-arrow {
        transform: translateX(4px);
    }

    .proceso-acciones {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .btn-activar-recibo {
        background: #10b981;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-activar-recibo:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .btn-desactivar-recibo {
        background: #ef4444;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-desactivar-recibo:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    .recibo-activo {
        background: #dcfce7;
        border-left: 3px solid #10b981;
    }

    .numero-recibo {
        font-size: 11px;
        font-weight: 600;
        color: #059669;
        background: #dcfce7;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 8px;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
</style>

<script>
    /**
     * Estado global del selector de recibos
     */
    window.selectorRecibosState = {
        pedidoId: null,
        prendas: [],
        isOpen: false,
        // Información del usuario actual
        usuarioRoles: {!! json_encode(auth()->user()?->roles->pluck('name')->toArray() ?? []) !!},
        esSupervisorPedidos: {{ auth()->user()?->hasRole('supervisor_pedidos') ? 'true' : 'false' }},
        esSupervisor: {{ auth()->user()?->hasRole('supervisor') ? 'true' : 'false' }}
    };
    
    // Contadores de clics para debugging
    let prendaAccordionClickCount = 0;
    let procesoClickCount = 0;
    let lastAccordionClickTime = 0;
    let lastProcesoClickTime = 0;

    /**
     * Abre el modal selector de recibos
     * @param {number} pedidoId - ID del pedido
     */
    window.abrirSelectorRecibos = async function(pedidoId) {
        window.selectorRecibosState.pedidoId = pedidoId;
        window.selectorRecibosState.isOpen = true;

        const overlay = document.getElementById('recibos-process-selector-overlay');
        const modal = document.getElementById('recibos-process-selector-modal');
        const loading = document.getElementById('selector-loading');
        const error = document.getElementById('selector-error');

        if (!overlay || !modal) {
            return;
        }

        // Mostrar modal con loading
        overlay.style.display = 'block';
        modal.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        document.getElementById('selector-prendas-list').innerHTML = '';

        // Resetear contadores al abrir modal
        prendaAccordionClickCount = 0;
        procesoClickCount = 0;
        console.log('[PRENDA-DEBUG] Modal abierto - Contadores reseteados');

        // Cargar datos de recibos
        try {
            // Determinar la ruta correcta según la página actual
            let apiUrl;
            if (window.location.pathname.includes('/registros')) {
                // Usar la ruta de registros recibos-datos
                apiUrl = `/registros/${pedidoId}/recibos-datos`;
            } else {
                apiUrl = `/api/pedidos/${pedidoId}`;
            }
            
            console.log('[abrirSelectorRecibos] Fetching URL:', apiUrl);
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            console.log('[abrirSelectorRecibos] API Response:', result);
            
            const datos = result.data || result;
            console.log('[abrirSelectorRecibos] datos:', datos);
            console.log('[abrirSelectorRecibos] prendas array:', datos.prendas);
            
            window.selectorRecibosState.prendas = datos.prendas || [];

            // Actualizar número de pedido (puede ser null)
            const numeroPedido = datos.numero || datos.numero_pedido || datos.id;
            document.getElementById('selector-pedido-numero').textContent = `#${numeroPedido}`;

            // Renderizar prendas
            console.log('[abrirSelectorRecibos] Renderizando prendas, cantidad:', (datos.prendas || []).length);
            renderizarPrendasEnSelector(datos.prendas);

            loading.style.display = 'none';

        } catch (err) {
            loading.style.display = 'none';
            error.style.display = 'block';
            document.getElementById('selector-error-message').textContent = err.message || 'Error desconocido';
        }

        // Cerrar con ESC
        document.addEventListener('keydown', manejarESCEnSelector);
    };

    /**
     * Renderiza las prendas en el selector
     * Construye la lista correcta: RECIBO BASE + Procesos adicionales
     * @param {Array} prendas - Lista de prendas
     */
    function renderizarPrendasEnSelector(prendas) {
        const container = document.getElementById('selector-prendas-list');
        
        if (!prendas || prendas.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #9ca3af; padding: 40px;">No hay prendas en este pedido</p>';
            return;
        }

        let html = '';

        prendas.forEach((prenda, prendaIdx) => {
            // PASO 1: Construir lista de recibos (BASE + ADICIONALES)
            const recibos = [];

            // CONDICIÓN ESPECIAL PARA VISUALIZADOR-LOGO: No mostrar recibo base
            const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
            
            // CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA-BODEGA en supervisor-pedidos y registros
            const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
            const esRegistros = window.location.pathname.includes('/registros');
            const excluirCosturaBodega = (esSupervisorPedidos || esRegistros) && prenda.de_bodega == 1;
            
            if (excluirCosturaBodega) {
                console.log('📋 [renderizarPrendasEnSelector] COSTURA-BODEGA EXCLUIDO para prenda:', prenda.nombre);
            }
            
            if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
                //  RECIBO BASE - SOLO EN OTRAS VISTAS
                const reciboBase = {
                    tipo: prenda.de_bodega == 1 ? "costura-bodega" : "costura",
                    nombre: prenda.de_bodega == 1 ? "Bodega" : "Costura",
                    estado: "",
                    es_base: true
                };
                
                // Agregar recibo base (permite tanto costura como costura-bodega)
                recibos.push(reciboBase);
            }

            //  PROCESOS ADICIONALES
            const procesos = prenda.procesos || [];
            procesos.forEach((proc) => {
                // Garantizar que tipo_proceso es STRING
                const tipoProceso = String(proc.tipo_proceso || proc.nombre_proceso || '');
                
                // Filtrar: excluir REFLECTIVO si de_bodega es false
                if (!prenda.de_bodega && tipoProceso.toLowerCase() === 'reflectivo') {
                    return; // Skip este proceso
                }
                
                // CONDICIÓN ESPECIAL PARA VISUALIZADOR-LOGO: Solo mostrar procesos específicos
                const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/pedidos-logo');
                if (esVistaVisualizadorLogo) {
                    // Solo mostrar procesos con tipo_proceso_id: 2 (Bordado), 3 (Estampado), 4 (DTF), 5 (Sublimado)
                    const procesosPermitidos = [2, 3, 4, 5];
                    if (!proc.tipo_proceso_id || !procesosPermitidos.includes(proc.tipo_proceso_id)) {
                        return; // Skip este proceso
                    }
                }
                
                recibos.push({
                    tipo: tipoProceso,
                    nombre: `${tipoProceso}`,
                    estado: proc.estado || "",
                    es_base: false
                });
            });

            const idAccordion = `prenda-${prenda.id || prendaIdx}`;
            const totalRecibos = recibos.length;
            const indicadorBodega = prenda.de_bodega == 1 ? ' <span style="color: #ef4444; font-size: 12px; font-weight: 600; margin-left: 8px;">(SE SACA DE BODEGA)</span>' : '';

            html += `
                <div class="prenda-accordion">
                    <div class="prenda-header" onclick="togglePrendaAccordion(this, '${idAccordion}')">
                        <div style="flex: 1;">
                            <p class="prenda-title">${prenda.nombre || 'Prenda sin nombre'}${indicadorBodega}</p>
                            <p class="prenda-subtitle">${totalRecibos} recibo(s)</p>
                        </div>
                        <div class="prenda-chevron">▼</div>
                    </div>
                    <div class="prenda-processes" id="${idAccordion}">
            `;

            if (totalRecibos === 0) {
                html += '<div style="padding: 16px; color: #9ca3af; text-align: center;">Sin recibos</div>';
            } else {
                recibos.forEach((recibo, reciboIdx) => {
                    const estadoClass = recibo.estado ? recibo.estado.toLowerCase().replace(' ', '-') : '';
                    const estadoLabel = recibo.estado || '';
                    
                    //  CRÍTICO: Pasar tipo como STRING puro
                    const tipoString = String(recibo.tipo);
                    
                    // Determinar si el recibo está activo (solo para procesos reales)
                    const estaActivo = !recibo.es_base && recibo.estado === 'APROBADO' && recibo.numero_recibo;
                    const puedeActivar = !recibo.es_base && recibo.estado === 'PENDIENTE';
                    
                    //  CRÍTICO: Solo supervisor_pedidos puede activar/desactivar recibos
                    const usuarioEsSupervisor = window.selectorRecibosState?.esSupervisorPedidos || window.selectorRecibosState?.esSupervisor;
                    const puedeModificarRecibo = puedeActivar && usuarioEsSupervisor;
                    const puedeDesactivarRecibo = estaActivo && usuarioEsSupervisor;
                    
                    const reciboClass = estaActivo ? 'recibo-activo' : '';
                    
                    html += `
                        <div class="proceso-item ${reciboClass}" onclick="seleccionarProceso(${prenda.id}, '${tipoString}')">
                            <div class="proceso-info">
                                <p class="proceso-name">${recibo.nombre}</p>
                                ${recibo.estado ? `<span class="proceso-estado ${estadoClass}">${estadoLabel}</span>` : ''}
                                ${recibo.numero_recibo ? `<span class="numero-recibo">${recibo.numero_recibo}</span>` : ''}
                            </div>
                            <div class="proceso-acciones">
                                ${puedeModificarRecibo ? `
                                    <button class="btn-activar-recibo" 
                                            onclick="event.stopPropagation(); toggleActivarRecibo(${prenda.id}, '${tipoString}', ${!estaActivo})"
                                            title="Activar recibo">
                                        <i class="fas fa-check"></i>
                                        Activar
                                    </button>
                                ` : ''}
                                ${puedeDesactivarRecibo ? `
                                    <button class="btn-desactivar-recibo" 
                                            onclick="event.stopPropagation(); toggleActivarRecibo(${prenda.id}, '${tipoString}', ${!estaActivo})"
                                            title="Desactivar recibo">
                                        <i class="fas fa-times"></i>
                                        Desactivar
                                    </button>
                                ` : ''}
                                <span class="proceso-arrow">→</span>
                            </div>
                        </div>
                    `;
                });
            }

            html += `
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * Toggle del acordeón de prenda
     * @param {HTMLElement} header - Elemento del header
     * @param {string} id - ID del acordeón
     */
    window.togglePrendaAccordion = function(header, id) {
        console.log(`[PRENDA-DEBUG] togglePrendaAccordion llamado con ID: ${id}`);
        
        // Incrementar contador de clics
        prendaAccordionClickCount++;
        const currentTime = Date.now();
        const timeSinceLastClick = lastAccordionClickTime ? currentTime - lastAccordionClickTime : 0;
        lastAccordionClickTime = currentTime;
        
        console.log(`[PRENDA-DEBUG] Accordion Click #${prendaAccordionClickCount} - ID: ${id} - Tiempo desde último: ${timeSinceLastClick}ms`);
        
        const processes = document.getElementById(id);
        const chevron = header.querySelector('.prenda-chevron');
        
        console.log(`[PRENDA-DEBUG] Elemento processes:`, processes);
        console.log(`[PRENDA-DEBUG] Elemento chevron:`, chevron);
        
        if (!processes) {
            console.log(`[PRENDA-DEBUG] Accordion Click #${prendaAccordionClickCount} - Procesos no encontrados para ID: ${id}`);
            return;
        }

        // Estado actual antes del toggle
        const estadoAnterior = {
            processesVisible: processes.classList.contains('visible'),
            headerExpanded: header.classList.contains('expanded'),
            chevronExpanded: chevron.classList.contains('expanded'),
            processesDisplay: processes.style.display
        };
        
        console.log(`[PRENDA-DEBUG] Accordion Click #${prendaAccordionClickCount} - Estado antes:`, estadoAnterior);

        // Realizar toggle usando display en lugar de clases
        const isVisible = processes.style.display === 'block';
        
        if (isVisible) {
            // Contraer
            processes.style.display = 'none';
            header.classList.remove('expanded');
            chevron.classList.remove('expanded');
        } else {
            // Expandir
            processes.style.display = 'block';
            header.classList.add('expanded');
            chevron.classList.add('expanded');
        }
        
        // Estado después del toggle
        const estadoDespues = {
            processesVisible: processes.classList.contains('visible'),
            headerExpanded: header.classList.contains('expanded'),
            chevronExpanded: chevron.classList.contains('expanded')
        };
        
        console.log(`[PRENDA-DEBUG] Accordion Click #${prendaAccordionClickCount} - Estado después:`, estadoDespues);
        
        // Contar procesos disponibles
        const procesoItems = processes.querySelectorAll('.proceso-item');
        console.log(`[PRENDA-DEBUG] Accordion Click #${prendaAccordionClickCount} - Procesos disponibles: ${procesoItems.length}`);
    };

    /**
     * Selecciona un proceso específico
     *  GARANTIZA que tipoProceso siempre sea STRING
     * @param {number} prendaId - ID de la prenda (DEBE ser número)
     * @param {string} tipoProceso - Tipo/nombre del proceso (DEBE ser STRING)
     */
    window.seleccionarProceso = function(prendaId, tipoProceso) {
        // Incrementar contador de clics
        procesoClickCount++;
        const currentTime = Date.now();
        const timeSinceLastClick = lastProcesoClickTime ? currentTime - lastProcesoClickTime : 0;
        lastProcesoClickTime = currentTime;
        
        console.log(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - PrendaID: ${prendaId} - Proceso: ${tipoProceso} - Tiempo desde último: ${timeSinceLastClick}ms`);
        
        //  CRÍTICO: Validación defensiva
        if (typeof tipoProceso !== 'string') {
            console.error(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - Error: tipoProceso no es string (${typeof tipoProceso})`);
            alert('Error: tipo de recibo debe ser texto (STRING)');
            return;
        }
        
        if (typeof prendaId !== 'number') {
            console.error(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - Error: prendaId no es número (${typeof prendaId})`);
            alert('Error: ID de prenda debe ser número');
            return;
        }
        
        const tipoString = String(tipoProceso);
        const pedidoId = window.selectorRecibosState.pedidoId;
        
        console.log(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - Datos válidos - PedidoID: ${pedidoId}, TipoString: ${tipoString}`);

        // Cerrar selector
        cerrarSelectorRecibos();

        // Abrir modal de recibo con el proceso específico
        //  Pasar como STRING puro
        window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipoString);
        
        console.log(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - Modal de recibo solicitado`);
    };

    /**
     * Cierra el selector de recibos
     */
    window.cerrarSelectorRecibos = function() {
        const overlay = document.getElementById('recibos-process-selector-overlay');
        const modal = document.getElementById('recibos-process-selector-modal');

        if (overlay) overlay.style.display = 'none';
        if (modal) modal.style.display = 'none';

        window.selectorRecibosState.isOpen = false;

        // Remover listener de ESC
        document.removeEventListener('keydown', manejarESCEnSelector);
    };

    /**
     * Maneja la tecla ESC en el selector
     */
    function manejarESCEnSelector(e) {
        if (e.key === 'Escape' && window.selectorRecibosState.isOpen) {
            cerrarSelectorRecibos();
        }
    }

    /**
     * Activa o desactiva un recibo de proceso
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoProceso - Tipo de proceso
     * @param {boolean} activar - true para activar, false para desactivar
     */
    window.toggleActivarRecibo = async function(prendaId, tipoProceso, activar) {
        try {
            // Mostrar modal de confirmación
            const accion = activar ? 'activar' : 'desactivar';
            const titulo = activar ? 'Activar Recibo' : 'Desactivar Recibo';
            const mensaje = `¿Está seguro de que desea ${accion} el recibo de ${tipoProceso}?`;
            const colorBoton = activar ? '#10b981' : '#ef4444';
            
            mostrarModalConfirmar(titulo, mensaje, colorBoton, async () => {
                await ejecutarActivarRecibo(prendaId, tipoProceso, activar);
            });

        } catch (error) {
            console.error('Error al actualizar recibo:', error);
            alert('Error al actualizar el recibo: ' + error.message);
        }
    };

    /**
     * Ejecuta la activación/desactivación del recibo
     */
    async function ejecutarActivarRecibo(prendaId, tipoProceso, activar) {
        try {
            // Buscar el proceso ID en los datos cargados
            let procesoId = null;
            const prenda = window.selectorRecibosState.prendas.find(p => p.id == prendaId);
            
            if (prenda && prenda.procesos) {
                const proceso = prenda.procesos.find(p => 
                    String(p.tipo_proceso || p.nombre_proceso || '') === tipoProceso
                );
                if (proceso) {
                    procesoId = proceso.id;
                }
            }

            if (!procesoId) {
                alert('Error: No se encontró el proceso para actualizar');
                return;
            }

            // Llamar a la API
            console.log('[DEBUG] Enviando petición:', {
                url: `/procesos/${procesoId}/activar-recibo`,
                procesoId: procesoId,
                activar: activar,
                csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            });

            const response = await fetch(`/procesos/${procesoId}/activar-recibo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    activar: activar
                })
            });

            console.log('[DEBUG] Respuesta recibida:', {
                status: response.status,
                statusText: response.statusText,
                ok: response.ok,
                headers: Object.fromEntries(response.headers.entries())
            });

            // Verificar si la respuesta es JSON antes de parsear
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('[DEBUG] Respuesta no es JSON:', textResponse.substring(0, 200));
                throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
            }

            const result = await response.json();
            console.log('[DEBUG] Respuesta JSON:', result);

            if (!response.ok) {
                throw new Error(result.message || 'Error al actualizar el recibo');
            }

            // Mostrar mensaje de éxito
            mostrarMensajeExito(result.message);

            // Recargar los datos para actualizar la vista
            const pedidoId = window.selectorRecibosState.pedidoId;
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos (pero la operación principal tuvo éxito):', recargaError);
                // No mostrar alert de error porque la operación principal funcionó
                mostrarMensajeExito('Recibo actualizado correctamente (la vista se actualizará en la próxima recarga)');
            }

        } catch (error) {
            console.error('Error al actualizar recibo:', error);
            console.error('Detalles del error:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            
            // Solo mostrar alert si es un error real de la API, no de recarga
            if (error.message && !error.message.includes('Error al recargar datos')) {
                alert('Error al actualizar el recibo: ' + error.message);
            } else {
                mostrarMensajeExito('Recibo actualizado correctamente');
            }
        }
    }

    /**
     * Muestra el modal de confirmación
     */
    function mostrarModalConfirmar(titulo, mensaje, colorBoton, onConfirm) {
        const modal = document.getElementById('confirmar-recibo-modal');
        const tituloEl = document.getElementById('confirmar-titulo');
        const mensajeEl = document.getElementById('confirmar-mensaje');
        const boton = document.getElementById('confirmar-boton');
        const loading = document.getElementById('confirmar-loading');
        const botones = document.getElementById('confirmar-botones');
        
        tituloEl.textContent = titulo;
        mensajeEl.textContent = mensaje;
        boton.style.background = colorBoton;
        
        // Resetear estado
        loading.style.display = 'none';
        botones.style.display = 'flex';
        
        // Remover listeners anteriores
        const nuevoBoton = boton.cloneNode(true);
        boton.parentNode.replaceChild(nuevoBoton, boton);
        
        // Agregar nuevo listener
        nuevoBoton.addEventListener('click', async () => {
            // Mostrar carga
            loading.style.display = 'block';
            botones.style.display = 'none';
            
            try {
                await onConfirm();
                cerrarModalConfirmar();
            } catch (error) {
                // Si hay error, volver a mostrar botones
                loading.style.display = 'none';
                botones.style.display = 'flex';
                throw error;
            }
        });
        
        modal.style.display = 'block';
    }

    /**
     * Cierra el modal de confirmación
     */
    window.cerrarModalConfirmar = function() {
        document.getElementById('confirmar-recibo-modal').style.display = 'none';
    }

    /**
     * Muestra un mensaje de éxito temporal
     */
    function mostrarMensajeExito(mensaje) {
        // Crear elemento temporal
        const mensajeEl = document.createElement('div');
        mensajeEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 99999;
            font-weight: 500;
            animation: slideIn 0.3s ease-out;
        `;
        mensajeEl.textContent = mensaje;
        
        document.body.appendChild(mensajeEl);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            mensajeEl.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(mensajeEl);
            }, 300);
        }, 3000);
    }

    /**
     * Carga los datos de recibos y actualiza la vista
     * @param {number} pedidoId - ID del pedido
     */
    async function cargarDatosRecibos(pedidoId) {
        try {
            let apiUrl;
            if (window.location.pathname.includes('/registros')) {
                apiUrl = `/registros/${pedidoId}`;
            } else {
                apiUrl = `/api/pedidos/${pedidoId}`;
            }
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const datos = result.data || result;
            window.selectorRecibosState.prendas = datos.prendas || [];

            // Actualizar número de pedido
            document.getElementById('selector-pedido-numero').textContent = `#${datos.numero_pedido}`;

            // Renderizar prendas con los datos actualizados
            renderizarPrendasEnSelector(datos.prendas);

        } catch (err) {
            console.error('Error al recargar datos:', err);
            alert('Error al recargar los datos: ' + err.message);
        }
    }

    /**
     * Event listener para clicks en elementos .proceso-name
     * Permite que al hacer click en la descripción del proceso se abra el recibo
     * Sin necesidad de hacer click en toda la fila
     */
    document.addEventListener('click', function(e) {
        // Detectar si se hizo click en un elemento .proceso-name o sus hijos
        const procesoName = e.target.closest('.proceso-name');
        if (procesoName) {
            console.log('[PROCESO-NAME-CLICK] Click detectado en .proceso-name');
            
            // Encontrar el elemento padre .proceso-item
            const procesoItem = procesoName.closest('.proceso-item');
            if (procesoItem) {
                console.log('[PROCESO-NAME-CLICK] .proceso-item encontrado, propagando click');
                
                // Simular el click en el padre para ejecutar su onclick
                procesoItem.click();
            } else {
                console.warn('[PROCESO-NAME-CLICK] .proceso-item no encontrado como padre');
            }
        }
    }, true); // Usar capture phase para mayor prioridad

</script>

