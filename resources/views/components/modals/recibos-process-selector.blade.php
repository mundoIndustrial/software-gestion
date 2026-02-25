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

<!-- Modal de Selección de Tallas para Activar Recibo -->
<div id="activar-recibo-tallas-modal" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999;"
     onclick="if(event.target === this) cerrarModalActivarReciboTallas()">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 20px; max-width: 560px; width: 92%; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px;">
            <h3 style="margin: 0; color: #1f2937; font-size: 18px;">Activar Recibo - Seleccionar Tallas</h3>
            <button onclick="cerrarModalActivarReciboTallas()" style="background: #e5e7eb; border: none; color: #111827; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; line-height: 32px;">✕</button>
        </div>

        <div id="activar-recibo-tallas-subtitle" style="color: #6b7280; font-size: 13px; margin-bottom: 14px;"></div>

        <div id="activar-recibo-tallas-loading" style="display:none; text-align:center; padding: 18px;">
            <div style="display: inline-block; width: 28px; height: 28px; border: 3px solid #e5e7eb; border-top: 3px solid #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <div style="margin-top: 10px; color: #6b7280; font-size: 13px;">Cargando tallas...</div>
        </div>

        <div id="activar-recibo-tallas-error" style="display:none; background:#fee2e2; border:1px solid #fecaca; border-radius: 8px; padding: 12px; color:#991b1b; font-size: 13px; margin-bottom: 12px;"></div>

        <div id="activar-recibo-tallas-container"></div>

        <div style="display:flex; justify-content:flex-end; gap: 10px; margin-top: 16px;">
            <button onclick="cerrarModalActivarReciboTallas()" style="background:#e5e7eb; color:#374151; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:600;">Cancelar</button>
            <button id="activar-recibo-tallas-confirmar" onclick="confirmarActivarReciboConTallas()" style="background:#10b981; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:700;">Activar Recibo</button>
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
        position: relative;
    }

    .prenda-header:hover {
        background: #e5e7eb;
    }

    .prenda-header.expanded {
        background: #dbeafe;
        border-bottom: 2px solid #3b82f6;
    }

    .prenda-header.entregada {
        background: #dbeafe !important;
        border-left: 4px solid #3b82f6;
    }

    .btn-entregar-prenda {
        background: #10b981;
        color: white;
        border: none;
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 12px;
    }

    .btn-entregar-prenda:hover {
        background: #059669;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .btn-entregar-prenda.entregado {
        background: #3b82f6;
        cursor: default;
    }

    .btn-entregar-prenda.entregado:hover {
        background: #3b82f6;
        transform: none;
        box-shadow: none;
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
                console.log(' [renderizarPrendasEnSelector] COSTURA-BODEGA EXCLUIDO para prenda:', prenda.nombre);
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
            const procesos = prenda.procesos || prenda.procesos_prenda || prenda.procesosPrenda || [];
            procesos.forEach((proc) => {
                // Garantizar que tipo_proceso es STRING
                const tipoProceso = String(proc.tipo_proceso || proc.nombre_proceso || '');
                const procesoId = proc.id || proc.proceso_id || proc.proceso_prenda_detalle_id || null;
                
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
                    es_base: false,
                    proceso_id: procesoId
                });
            });

            const idAccordion = `prenda-${prenda.id || prendaIdx}`;
            const totalRecibos = recibos.length;
            const indicadorBodega = prenda.de_bodega == 1 ? ' <span style="color: #ef4444; font-size: 12px; font-weight: 600; margin-left: 8px;">(SE SACA DE BODEGA)</span>' : '';

            // Verificar si la prenda está entregada
            const estaEntregada = prenda.entrega?.entregado || false;
            const claseEntregada = estaEntregada ? 'entregada' : '';
            const claseBotonEntregado = estaEntregada ? 'entregado' : '';
            const textoBoton = estaEntregada ? 'Entregado' : 'Entregar';
            const colorBoton = estaEntregada ? '#3b82f6' : '#10b981';
            const iconoBoton = estaEntregada ? 'fa-check-double' : 'fa-check-circle';
            const ocultarBotonEntregar = window.location.pathname.includes('/registros');
            
            // Depuración completa de datos de la prenda
            console.log('[PRENDA-DEBUG] Datos completos de la prenda:', {
                prenda_id: prenda.id,
                nombre: prenda.nombre,
                entrega: prenda.entrega,
                entrega_existe: !!prenda.entrega,
                entregado: estaEntregada,
                usuario: prenda.entrega?.usuario,
                usuario_id: prenda.entrega?.usuario_id,
                usuario_nombre: prenda.entrega?.usuario?.name,
                fecha_entrega: prenda.entrega?.fecha_entrega
            });
            
            // Obtener fecha de entrega si está entregada
            let fechaEntregaHtml = '';
            if (estaEntregada && prenda.entrega?.fecha_entrega) {
                const fechaEntrega = new Date(prenda.entrega.fecha_entrega);
                const fechaFormateada = fechaEntrega.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true // Usar formato AM/PM
                });
                
                // Depuración: mostrar datos disponibles
                console.log('[DEBUG] Datos de entrega:', {
                    entrega: prenda.entrega,
                    usuario: prenda.entrega?.usuario,
                    usuario_id: prenda.entrega?.usuario_id,
                    nombre_usuario: prenda.entrega?.usuario?.name
                });
                
                // Obtener nombre real del usuario con múltiples fallbacks
                let nombreUsuario = 'Usuario desconocido';
                
                // Caso 1: usuario es un objeto con propiedad name
                if (prenda.entrega?.usuario?.name) {
                    nombreUsuario = prenda.entrega.usuario.name;
                }
                // Caso 2: usuario es directamente un string (el nombre)
                else if (typeof prenda.entrega?.usuario === 'string' && prenda.entrega.usuario.trim() !== '') {
                    nombreUsuario = prenda.entrega.usuario;
                }
                // Caso 3: tenemos usuario_id pero no el nombre
                else if (prenda.entrega?.usuario_id) {
                    nombreUsuario = `Usuario #${prenda.entrega.usuario_id}`;
                }
                // Caso 4: es el administrador
                else if (prenda.entrega?.usuario_id === 1) {
                    nombreUsuario = 'Administrador';
                }
                
                console.log('[DEBUG] Nombre final de usuario:', nombreUsuario);
                
                fechaEntregaHtml = `
                    <div style="margin-left: 12px; font-size: 11px; color: #6b7280; display: flex; flex-direction: column; gap: 2px;">
                        <span style="font-weight: 600; color: #3b82f6;">Entregado: ${fechaFormateada}</span>
                        <span style="font-size: 10px; color: #9ca3af;">Por: ${nombreUsuario}</span>
                    </div>
                `;
            }
            
            const botonEntregarHtml = ocultarBotonEntregar ? '' : `
                        <button class="btn-entregar-prenda ${claseBotonEntregado}" onclick="event.stopPropagation(); toggleEntregarPrenda(this, ${prenda.id || prendaIdx})" style="background: ${colorBoton};">
                            <i class="fas ${iconoBoton}"></i>
                            <span>${textoBoton}</span>
                        </button>`;

            html += `
                <div class="prenda-accordion">
                    <div class="prenda-header ${claseEntregada}" onclick="togglePrendaAccordion(this, '${idAccordion}')" data-prenda-id="${prenda.id || prendaIdx}">
                        <div style="flex: 1;">
                            <p class="prenda-title">${prenda.nombre || 'Prenda sin nombre'}${indicadorBodega}</p>
                            <p class="prenda-subtitle">${totalRecibos} recibo(s)</p>
                        </div>
                        ${botonEntregarHtml}
                        ${fechaEntregaHtml}
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
                    
                    // Determinar si el recibo está activo
                    // (para procesos reales viene de backend; para base normalmente viene vacío)
                    const estaActivo = !recibo.es_base && recibo.estado === 'APROBADO' && recibo.numero_recibo;
                    
                    // En pedidos aún no aprobados, algunos procesos pueden venir con estado vacío/null.
                    // Para el selector, considerar eso como equivalente a PENDIENTE para poder activar el recibo.
                    const estadoRecibo = (recibo.estado ?? '').toString().trim().toUpperCase();
                    const puedeActivar = (estadoRecibo === 'PENDIENTE' || estadoRecibo === '');
                    
                    //  CRÍTICO: Solo supervisor_pedidos puede activar/desactivar recibos
                    const usuarioEsSupervisor = window.selectorRecibosState?.esSupervisorPedidos || window.selectorRecibosState?.esSupervisor;
                    const puedeModificarRecibo = puedeActivar && usuarioEsSupervisor;
                    const puedeDesactivarRecibo = estaActivo && usuarioEsSupervisor;
                    
                    const reciboClass = estaActivo ? 'recibo-activo' : '';
                    
                    const procesoId = recibo.proceso_id || null;
                    const procesoIdNum = (procesoId !== null && procesoId !== undefined && procesoId !== '' && !Number.isNaN(Number.parseInt(procesoId, 10)))
                        ? Number.parseInt(procesoId, 10)
                        : null;

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
                                            onclick="event.stopPropagation(); toggleActivarRecibo(${prenda.id}, '${tipoString}', ${!estaActivo}, ${procesoIdNum !== null ? procesoIdNum : 'null'})"
                                            title="Activar recibo">
                                        <i class="fas fa-check"></i>
                                        Activar
                                    </button>
                                ` : ''}
                                ${puedeDesactivarRecibo ? `
                                    <button class="btn-desactivar-recibo" 
                                            onclick="event.stopPropagation(); toggleActivarRecibo(${prenda.id}, '${tipoString}', ${!estaActivo}, ${procesoIdNum !== null ? procesoIdNum : 'null'})"
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
    window.toggleActivarRecibo = async function(prendaId, tipoProceso, activar, procesoId = null) {
        try {
            console.log('[toggleActivarRecibo] params:', { prendaId, tipoProceso, activar, procesoId });

            if (procesoId !== null && procesoId !== undefined && procesoId !== '' && !Number.isNaN(Number.parseInt(procesoId, 10))) {
                procesoId = Number.parseInt(procesoId, 10);
            } else {
                procesoId = null;
            }

            const prenda = window.selectorRecibosState?.prendas?.find(p => p.id == prendaId) || null;
            const esBodega = prenda ? (String(prenda.de_bodega) === '1' || prenda.de_bodega === 1 || prenda.de_bodega === true) : false;
            const tipoProcesoNorm = String(tipoProceso || '').trim().toLowerCase();
            const procesosConTallas = new Set(['dtf', 'sublimado', 'estampado', 'bordado', 'reflectivo']);
            const debeSeleccionarTallas = activar && esBodega && procesosConTallas.has(tipoProcesoNorm);

            if (debeSeleccionarTallas) {
                if (!procesoId) {
                    alert('No se encontró el proceso para activar recibo con tallas.');
                    return;
                }
                await abrirModalActivarReciboTallas(prendaId, tipoProceso, procesoId);
                return;
            }

            // Para NO-bodega o tipos distintos, mantener flujo antiguo (confirmación simple)
            const accion = activar ? 'activar' : 'desactivar';
            const titulo = activar ? 'Activar Recibo' : 'Desactivar Recibo';
            const mensaje = `¿Está seguro de que desea ${accion} el recibo de ${tipoProceso}?`;
            const colorBoton = activar ? '#10b981' : '#ef4444';

            mostrarModalConfirmar(titulo, mensaje, colorBoton, async () => {
                await ejecutarActivarRecibo(prendaId, tipoProceso, activar, procesoId);
            });

        } catch (error) {
            console.error('Error al actualizar recibo:', error);
            alert('Error al actualizar el recibo: ' + error.message);
        }
    };

    // Estado modal tallas
    window.activarReciboTallasState = {
        procesoId: null,
        prendaId: null,
        tipoProceso: null,
        tallas: []
    };

    window.cerrarModalActivarReciboTallas = function() {
        const modal = document.getElementById('activar-recibo-tallas-modal');
        if (modal) modal.style.display = 'none';
        window.activarReciboTallasState = { procesoId: null, prendaId: null, tipoProceso: null, tallas: [] };
        const cont = document.getElementById('activar-recibo-tallas-container');
        if (cont) cont.innerHTML = '';
        const err = document.getElementById('activar-recibo-tallas-error');
        if (err) { err.style.display = 'none'; err.textContent = ''; }
    };

    async function abrirModalActivarReciboTallas(prendaId, tipoProceso, procesoId = null) {
        const modal = document.getElementById('activar-recibo-tallas-modal');
        const loading = document.getElementById('activar-recibo-tallas-loading');
        const error = document.getElementById('activar-recibo-tallas-error');
        const container = document.getElementById('activar-recibo-tallas-container');
        const subtitle = document.getElementById('activar-recibo-tallas-subtitle');

        if (!modal || !loading || !error || !container || !subtitle) {
            alert('Error: modal de tallas no disponible');
            return;
        }

        // Resolver procesoId: preferir el que viene desde el botón (recibo.proceso_id)
        // y solo si no viene, intentar buscarlo en la prenda.
        if (!procesoId) {
            const prenda = window.selectorRecibosState.prendas.find(p => p.id == prendaId);
            const procesos = prenda ? (prenda.procesos || prenda.procesos_prenda || prenda.procesosPrenda || []) : [];
            if (prenda && procesos) {
                const tipoBuscado = String(tipoProceso || '').trim().toLowerCase();
                const proceso = procesos.find(p => {
                    const tipoActual = String(p.tipo_proceso || p.nombre_proceso || '').trim().toLowerCase();
                    return tipoActual === tipoBuscado;
                });
                if (proceso) procesoId = proceso.id || proceso.proceso_id || proceso.proceso_prenda_detalle_id || null;
            }
        }

        if (procesoId !== null && procesoId !== undefined && procesoId !== '' && !Number.isNaN(Number.parseInt(procesoId, 10))) {
            procesoId = Number.parseInt(procesoId, 10);
        } else {
            procesoId = null;
        }

        if (!procesoId) {
            alert('Error: No se encontró el proceso para activar recibo');
            return;
        }

        window.activarReciboTallasState.procesoId = procesoId;
        window.activarReciboTallasState.prendaId = prendaId;
        window.activarReciboTallasState.tipoProceso = tipoProceso;

        modal.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        error.textContent = '';
        container.innerHTML = '';
        subtitle.textContent = `Proceso: ${tipoProceso}`;

        try {
            const resp = await fetch(`/api/procesos/${procesoId}/tallas-disponibles`, {
                headers: {
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                throw new Error(data.message || `HTTP ${resp.status}`);
            }

            const tallas = (data.data && Array.isArray(data.data.tallas)) ? data.data.tallas : [];
            window.activarReciboTallasState.tallas = tallas;

            if (tallas.length === 0) {
                container.innerHTML = '<div style="color:#6b7280; font-size: 13px; padding: 10px 0;">Este proceso no tiene tallas disponibles para activar.</div>';
                return;
            }

            const grupos = {};
            tallas.forEach(t => {
                const g = String(t.genero || 'UNISEX');
                if (!grupos[g]) grupos[g] = [];
                grupos[g].push(t);
            });

            let html = '';
            Object.keys(grupos).forEach(genero => {
                html += `
                    <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                        <div style="font-weight: 800; color:#111827; margin-bottom: 10px;">${genero}</div>
                        <div style="display:grid; grid-template-columns: 1fr 120px; gap: 10px; align-items: center;">
                `;
                grupos[genero].forEach(row => {
                    const talla = String(row.talla);
                    const max = Number.parseInt(row.cantidad, 10) || 0;
                    const inputId = `talla-input-${genero}-${talla}`.replace(/[^a-zA-Z0-9_-]/g, '_');
                    html += `
                        <div style="color:#374151; font-weight:600;">${talla} <span style="color:#9ca3af; font-weight:700;">(max ${max})</span></div>
                        <input id="${inputId}" type="number" min="0" max="${max}" value="0" data-genero="${genero}" data-talla="${talla}" data-max="${max}"
                               style="width: 120px; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                    `;
                });
                html += `</div></div>`;
            });

            container.innerHTML = html;

            // Validación en vivo de máximos
            container.querySelectorAll('input[type="number"]').forEach(inp => {
                inp.addEventListener('input', function() {
                    const max = Number.parseInt(this.dataset.max, 10) || 0;
                    let val = Number.parseInt(this.value, 10);
                    if (Number.isNaN(val)) val = 0;
                    if (val < 0) val = 0;
                    if (val > max) val = max;
                    this.value = String(val);
                });
            });
        } catch (e) {
            error.style.display = 'block';
            error.textContent = e.message || 'Error cargando tallas';
        } finally {
            loading.style.display = 'none';
        }
    }

    window.confirmarActivarReciboConTallas = async function() {
        const { procesoId } = window.activarReciboTallasState;
        const container = document.getElementById('activar-recibo-tallas-container');
        const error = document.getElementById('activar-recibo-tallas-error');
        const btn = document.getElementById('activar-recibo-tallas-confirmar');
        if (!procesoId || !container) {
            return;
        }

        const inputs = Array.from(container.querySelectorAll('input[type="number"]'));
        const seleccion = inputs
            .map(i => ({
                genero: i.dataset.genero,
                talla: i.dataset.talla,
                cantidad: Number.parseInt(i.value, 10) || 0,
                max: Number.parseInt(i.dataset.max, 10) || 0,
            }))
            .filter(x => x.cantidad > 0);

        if (seleccion.length === 0) {
            if (error) {
                error.style.display = 'block';
                error.textContent = 'Selecciona al menos una talla con cantidad > 0.';
            }
            return;
        }

        const invalida = seleccion.find(x => x.cantidad < 0 || x.cantidad > x.max);
        if (invalida) {
            if (error) {
                error.style.display = 'block';
                error.textContent = `Cantidad inválida para ${invalida.genero} ${invalida.talla}. Máximo: ${invalida.max}`;
            }
            return;
        }

        try {
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Activando...';
            }
            if (error) {
                error.style.display = 'none';
                error.textContent = '';
            }

            const resp = await fetch(`/api/procesos/${procesoId}/activar-recibo-con-tallas`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    tallas: seleccion.map(s => ({ genero: s.genero, talla: s.talla, cantidad: s.cantidad }))
                })
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) {
                throw new Error(data.message || `HTTP ${resp.status}`);
            }

            mostrarMensajeExito(data.message || 'Recibo activado correctamente');
            cerrarModalActivarReciboTallas();

            // Recargar lista para reflejar cambios
            const pedidoId = window.selectorRecibosState.pedidoId;
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos:', recargaError);
            }
        } catch (e) {
            if (error) {
                error.style.display = 'block';
                error.textContent = e.message || 'Error activando recibo';
            }
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Activar Recibo';
            }
        }
    };

    /**
     * Ejecuta la activación/desactivación del recibo
     */
    async function ejecutarActivarRecibo(prendaId, tipoProceso, activar, procesoId = null) {
        try {
            // Buscar el proceso ID en los datos cargados
            if (!procesoId) {
                const prenda = window.selectorRecibosState.prendas.find(p => p.id == prendaId);
                const procesos = prenda ? (prenda.procesos || prenda.procesos_prenda || prenda.procesosPrenda || []) : [];
                
                if (prenda && procesos) {
                    const tipoBuscado = String(tipoProceso || '').trim().toLowerCase();
                    const proceso = procesos.find(p => {
                        const tipoActual = String(p.tipo_proceso || p.nombre_proceso || '').trim().toLowerCase();
                        return tipoActual === tipoBuscado;
                    });
                    if (proceso) {
                        procesoId = proceso.id;
                    }
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
     * Toggle del estado de entrega de una prenda
     * @param {HTMLElement} button - Botón que se clickeó
     * @param {number} prendaId - ID de la prenda
     */
    window.toggleEntregarPrenda = async function(button, prendaId) {
        const header = button.closest('.prenda-header');
        const buttonText = button.querySelector('span');
        const icon = button.querySelector('i');
        const isEntregada = header.classList.contains('entregada');
        const nuevoEstado = !isEntregada;
        
        // Deshabilitar botón mientras se procesa
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
        
        try {
            // Guardar en base de datos
            const response = await fetch(`/api/prendas-entregas/${prendaId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    entregado: nuevoEstado
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error al actualizar estado');
            }
            
            // Actualizar UI solo si la petición fue exitosa
            if (nuevoEstado) {
                // Cambiar a entregada
                header.classList.add('entregada');
                button.classList.add('entregado');
                buttonText.textContent = 'Entregado';
                button.style.background = '#3b82f6';
                icon.className = 'fas fa-check-double';
                
                console.log(`[Prenda ${prendaId}] Estado cambiado a: ENTREGADA`, result.data);
                mostrarMensajeExito(result.message || 'Prenda marcada como entregada');
            } else {
                // Cambiar a NO entregada
                header.classList.remove('entregada');
                button.classList.remove('entregado');
                buttonText.textContent = 'Entregar';
                button.style.background = '#10b981';
                icon.className = 'fas fa-check-circle';
                
                console.log(`[Prenda ${prendaId}] Estado cambiado a: NO ENTREGADA`);
                mostrarMensajeExito(result.message || 'Prenda marcada como no entregada');
            }
            
        } catch (error) {
            console.error(`[Prenda ${prendaId}] Error al actualizar estado:`, error);
            alert('Error al actualizar el estado de entrega: ' + error.message);
        } finally {
            // Rehabilitar botón
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
        }
    };

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

