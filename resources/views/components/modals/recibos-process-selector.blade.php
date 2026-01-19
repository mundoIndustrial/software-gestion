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
</style>

<script>
    /**
     * Estado global del selector de recibos
     */
    window.selectorRecibosState = {
        pedidoId: null,
        prendas: [],
        isOpen: false
    };

    /**
     * Abre el modal selector de recibos
     * @param {number} pedidoId - ID del pedido
     */
    window.abrirSelectorRecibos = async function(pedidoId) {
        console.log('%c[SELECTOR] Abriendo selector para pedido: ' + pedidoId, 'color: #3b82f6; font-weight: bold; font-size: 14px;');
        
        window.selectorRecibosState.pedidoId = pedidoId;
        window.selectorRecibosState.isOpen = true;

        const overlay = document.getElementById('recibos-process-selector-overlay');
        const modal = document.getElementById('recibos-process-selector-modal');
        const loading = document.getElementById('selector-loading');
        const error = document.getElementById('selector-error');

        if (!overlay || !modal) {
            console.error('[SELECTOR] Elementos del selector no encontrados en DOM');
            return;
        }

        // Mostrar modal con loading
        overlay.style.display = 'block';
        modal.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        document.getElementById('selector-prendas-list').innerHTML = '';

        // Cargar datos de recibos
        try {
            const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const datos = await response.json();
            console.log('[SELECTOR] Datos recibidos:', datos);

            window.selectorRecibosState.prendas = datos.prendas || [];

            // Actualizar número de pedido
            document.getElementById('selector-pedido-numero').textContent = `#${datos.numero_pedido}`;

            // Renderizar prendas
            renderizarPrendasEnSelector(datos.prendas);

            loading.style.display = 'none';

        } catch (err) {
            console.error('[SELECTOR] Error cargando datos:', err);
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

            // ✅ RECIBO BASE - SIEMPRE PRIMERO
            const reciboBase = {
                tipo: prenda.de_bodega == 1 ? "costura-bodega" : "costura",
                nombre: prenda.de_bodega == 1 ? "Costura - Bodega" : "Costura",
                estado: "Pendiente",
                es_base: true
            };
            recibos.push(reciboBase);

            // ✅ PROCESOS ADICIONALES
            const procesos = prenda.procesos || [];
            procesos.forEach((proc) => {
                // Garantizar que tipo_proceso es STRING
                const tipoProceso = String(proc.tipo_proceso || proc.nombre_proceso || '');
                
                recibos.push({
                    tipo: tipoProceso,
                    nombre: `${tipoProceso}`,
                    estado: proc.estado || "Pendiente",
                    es_base: false
                });
            });

            const idAccordion = `prenda-${prenda.id || prendaIdx}`;
            const totalRecibos = recibos.length;

            html += `
                <div class="prenda-accordion">
                    <div class="prenda-header" onclick="togglePrendaAccordion(this, '${idAccordion}')">
                        <div style="flex: 1;">
                            <p class="prenda-title">${prenda.nombre || 'Prenda sin nombre'}</p>
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
                    const estadoClass = recibo.estado ? recibo.estado.toLowerCase().replace(' ', '-') : 'pendiente';
                    const estadoLabel = recibo.estado || 'Pendiente';
                    
                    // ⚠️ CRÍTICO: Pasar tipo como STRING puro
                    const tipoString = String(recibo.tipo);
                    
                    html += `
                        <div class="proceso-item" onclick="seleccionarProceso(${prenda.id}, '${tipoString}')">
                            <div class="proceso-info">
                                <p class="proceso-name">${recibo.nombre}</p>
                                <span class="proceso-estado ${estadoClass}">${estadoLabel}</span>
                            </div>
                            <span class="proceso-arrow">→</span>
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
        console.log('%c[SELECTOR] Prendas renderizadas con recibos base + procesos', 'color: #10b981; font-weight: bold;', prendas.length);
    }

    /**
     * Toggle del acordeón de prenda
     * @param {HTMLElement} header - Elemento del header
     * @param {string} id - ID del acordeón
     */
    window.togglePrendaAccordion = function(header, id) {
        const processes = document.getElementById(id);
        const chevron = header.querySelector('.prenda-chevron');
        
        if (!processes) return;

        processes.classList.toggle('visible');
        header.classList.toggle('expanded');
        chevron.classList.toggle('expanded');

        console.log('[SELECTOR] Acordeón ' + id + ' toggled');
    };

    /**
     * Selecciona un proceso específico
     * ⚠️ GARANTIZA que tipoProceso siempre sea STRING
     * @param {number} prendaId - ID de la prenda (DEBE ser número)
     * @param {string} tipoProceso - Tipo/nombre del proceso (DEBE ser STRING)
     */
    window.seleccionarProceso = function(prendaId, tipoProceso) {
        console.log('%c[SELECTOR] ===== SELECCIONANDO RECIBO =====', 'color: #10b981; font-weight: bold; font-size: 12px;');
        
        // ⚠️ CRÍTICO: Validación defensiva
        if (typeof tipoProceso !== 'string') {
            console.error('%c[SELECTOR] ❌ ERROR: tipoProceso DEBE ser STRING', 'color: #ef4444; font-weight: bold;', 'Recibido:', typeof tipoProceso, tipoProceso);
            alert('Error: tipo de recibo debe ser texto (STRING)');
            return;
        }
        
        if (typeof prendaId !== 'number') {
            console.error('%c[SELECTOR] ❌ ERROR: prendaId DEBE ser NÚMERO', 'color: #ef4444; font-weight: bold;', 'Recibido:', typeof prendaId, prendaId);
            alert('Error: ID de prenda debe ser número');
            return;
        }
        
        const tipoString = String(tipoProceso);
        const pedidoId = window.selectorRecibosState.pedidoId;
        
        console.log('%c[SELECTOR] Parámetros correctos:', 'color: #10b981;', {
            pedidoId: pedidoId,
            prendaId: prendaId,
            tipoProceso: tipoString
        });
        console.log('%c[SELECTOR] Tipos:', 'color: #10b981;', {
            pedidoId_type: typeof pedidoId,
            prendaId_type: typeof prendaId,
            tipoProceso_type: typeof tipoString
        });
        
        // Cerrar selector
        cerrarSelectorRecibos();

        // Abrir modal de recibo con el proceso específico
        // ✅ Pasar como STRING puro
        window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipoString);
    };

    /**
     * Cierra el selector de recibos
     */
    window.cerrarSelectorRecibos = function() {
        console.log('%c[SELECTOR] Cerrando selector', 'color: #3b82f6;');
        
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

</script>
