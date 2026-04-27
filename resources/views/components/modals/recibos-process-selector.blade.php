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

        <div id="selector-entrega-masiva-actions" style="display: none; margin-bottom: 14px; padding: 12px; border: 1px solid #d1fae5; border-radius: 10px; background: #ecfdf5; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
            <button id="btn-entregar-todas-prendas"
                    type="button"
                    class="btn-entregar-todas-prendas"
                    onclick="event.stopPropagation(); marcarTodasPrendasEntregadas(window.selectorRecibosState?.pedidoId, { fromSelector: true });">
                <i class="fas fa-check-double"></i>
                <span>Entregar todas</span>
            </button>
            <span id="selector-entrega-masiva-resumen" style="font-size: 12px; color: #065f46; font-weight: 600;"></span>
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

<!-- Modal de Observación por Proceso -->
<div id="modal-observacion-proceso"
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999;"
     onclick="if(event.target === this) cerrarModalObservacionProceso()">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 10px; padding: 20px; max-width: 520px; width: 92%; box-shadow: 0 10px 30px rgba(0,0,0,0.25);">
        <h3 style="margin: 0 0 12px 0; color: #1f2937; font-size: 18px;">Observación del proceso</h3>
        <p id="modal-observacion-proceso-subtitulo" style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;"></p>

        <input type="hidden" id="obs-pedido-id">
        <input type="hidden" id="obs-prenda-id">
        <input type="hidden" id="obs-tipo-proceso">

        <textarea id="obs-texto-proceso"
                  class="observacion-proceso-textarea"
                  placeholder="Escribe la observación..."
                  maxlength="2000"></textarea>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">
            <span id="obs-contador-proceso" style="font-size: 12px; color: #9ca3af;">0/2000</span>
            <div style="display: flex; gap: 8px;">
                <button type="button"
                        onclick="cerrarModalObservacionProceso()"
                        style="background: #e5e7eb; color: #374151; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    Cancelar
                </button>
                <button type="button"
                        id="btn-guardar-observacion-proceso"
                        onclick="guardarObservacionProcesoActual()"
                        style="background: #2563eb; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<div id="modal-historial-entregas"
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999;"
     onclick="if(event.target === this) cerrarModalHistorialEntregas()">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 12px; width: 92%; max-width: 760px; max-height: 80vh; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.25);">
        <div style="background: linear-gradient(135deg, #0f766e, #0ea5a4); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 id="historial-entregas-titulo" style="margin: 0; font-size: 20px; font-weight: 700;">Entregas parciales</h3>
                <p id="historial-entregas-subtitulo" style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;">Historial enviado a despacho</p>
            </div>
            <button onclick="cerrarModalHistorialEntregas()" style="background: rgba(255,255,255,0.24); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 18px;">×</button>
        </div>
        <div style="padding: 20px 24px; overflow-y: auto; max-height: calc(80vh - 84px);">
            <div id="historial-entregas-loading" style="display: none; text-align: center; padding: 32px; color: #6b7280;">Cargando historial...</div>
            <div id="historial-entregas-error" style="display: none; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; margin-bottom: 16px;"></div>
            <div id="historial-entregas-content"></div>
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

    .swal2-container {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-popup {
        margin: auto !important;
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
        background: #f59e0b;
        cursor: pointer;
    }

    .btn-entregar-prenda.entregado:hover {
        background: #d97706;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35);
    }

    .btn-entregar-todas-prendas {
        background: linear-gradient(135deg, #0f766e, #0d9488);
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-entregar-todas-prendas:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(13, 148, 136, 0.3);
    }

    .btn-entregar-todas-prendas:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        box-shadow: none;
        transform: none;
    }

    .btn-ver-entregas-prenda {
        background: #0f766e;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
        margin-left: 8px;
    }

    .btn-ver-entregas-prenda:hover {
        background: #0d9488;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(15, 118, 110, 0.28);
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

    .btn-anular-recibo {
        background: #ef4444;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-anular-recibo:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }

    .btn-recibo-parcial {
        background: #8b5cf6;
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

    .btn-recibo-parcial:hover {
        background: #7c3aed;
        transform: translateY(-1px);
    }

    .recibo-parcial-select-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        min-width: 165px;
    }

    .select-recibo-parcial {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        background: #8b5cf6;
        color: white;
        border: none;
        padding: 8px 34px 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .select-recibo-parcial:hover,
    .select-recibo-parcial:focus {
        background: #7c3aed;
        outline: none;
        transform: translateY(-1px);
    }

    .select-recibo-parcial option {
        color: #111827;
        background: white;
    }

    .recibo-parcial-select-icon,
    .recibo-parcial-select-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        color: white;
        pointer-events: none;
        font-size: 12px;
    }

    .recibo-parcial-select-icon {
        left: 10px;
    }

    .recibo-parcial-select-arrow {
        right: 10px;
        font-size: 10px;
    }

    .select-recibo-parcial.has-icon {
        padding-left: 30px;
    }

    .btn-observacion-proceso {
        background: #0ea5e9;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .btn-observacion-proceso:hover {
        background: #0284c7;
        transform: translateY(-1px);
    }

    .observacion-proceso-textarea {
        width: 100%;
        min-height: 120px;
        resize: vertical;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        color: #1f2937;
        outline: none;
    }

    .observacion-proceso-textarea:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
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
        uiState: {
            scrollTop: 0,
            expandedAccordions: []
        },
        // Información del usuario actual
        usuarioRoles: {!! json_encode(auth()->user()?->roles->pluck('name')->toArray() ?? []) !!},
        esSupervisorPedidos: {{ auth()->user()?->hasRole('supervisor_pedidos') ? 'true' : 'false' }},
        esSupervisor: {{ auth()->user()?->hasRole('supervisor') ? 'true' : 'false' }}
    };

    window.generarReciboBodega = function(prendaId, pedidoId, tipoProceso) {
        try {
            const resolvedPedidoId = pedidoId || window.selectorRecibosState?.pedidoId;
            const resolvedTipoProceso = String(tipoProceso || '').trim().toLowerCase();
            if (!resolvedPedidoId) {
                alert('Error: no se pudo determinar el pedido');
                return;
            }

            if (!resolvedTipoProceso) {
                alert('Selecciona el tipo de recibo que deseas generar');
                return;
            }

            if (typeof window.abrirModalReciboParcial !== 'function') {
                alert('Error: modal de recibo por talla no disponible');
                return;
            }

            // Forzar creación de parcial de COSTURA (sin consecutivo). Aplica solo a prendas de bodega.
            return window.abrirModalReciboParcial(prendaId, resolvedTipoProceso, resolvedPedidoId);
        } catch (e) {
            console.error('[generarReciboBodega] Error:', e);
            alert('Error al abrir el modal de recibo por talla');
        }
    };

    window.generarReciboCosturaBodega = function(prendaId, pedidoId) {
        return window.generarReciboBodega(prendaId, pedidoId, 'costura');
    };

    window.handleReciboBodegaSelect = function(selectEl, prendaId, pedidoId) {
        try {
            const tipoProceso = selectEl?.value || '';
            if (!tipoProceso) return;

            if (window.selectorRecibosState?.esSupervisorPedidos) {
                abrirModalEditarPrendaRecibo(prendaId, pedidoId, tipoProceso);
            } else {
                window.generarReciboBodega(prendaId, pedidoId, tipoProceso);
            }
        } finally {
            if (selectEl) {
                selectEl.value = '';
            }
        }
    };

    window.editarPrendaReciboState = {
        prendaId: null,
        pedidoId: null,
        tipoProceso: null,
    };

    window.abrirModalEditarPrendaRecibo = function(prendaId, pedidoId, tipoProceso) {
        const prenda = window.selectorRecibosState?.prendas?.find(p => p.id === prendaId);
        if (!prenda) {
            alert('Prenda no encontrada');
            return;
        }

        window.editarPrendaReciboState = { prendaId, pedidoId, tipoProceso };

        document.getElementById('modal-editar-prenda-nombre').textContent = `Prenda: ${prenda.nombre || 'Sin nombre'}`;
        document.getElementById('modal-editar-descripcion').value = prenda.descripcion || '';
        document.getElementById('modal-editar-de-bodega').value = prenda.de_bodega ? '1' : '0';

        const modal = document.getElementById('modal-editar-prenda-recibo');
        if (modal) {
            modal.style.display = 'flex';
        }
    };

    window.cancelarEditarPrendaRecibo = function() {
        const modal = document.getElementById('modal-editar-prenda-recibo');
        if (modal) {
            modal.style.display = 'none';
        }
        window.editarPrendaReciboState = { prendaId: null, pedidoId: null, tipoProceso: null };
    };

    window.guardarEditarPrendaRecibo = async function() {
        const estado = window.editarPrendaReciboState;
        if (!estado.prendaId || !estado.pedidoId) {
            alert('Error: datos incompletos');
            return;
        }

        const descripcion = document.getElementById('modal-editar-descripcion').value || null;
        const deBodega = document.getElementById('modal-editar-de-bodega').value === '1';

        try {
            const response = await fetch(`/api/supervisor-pedidos/prendas-pedido/${estado.prendaId}/editar-recibo`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    descripcion: descripcion,
                    de_bodega: deBodega,
                    pedido_id: estado.pedidoId,
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Error desconocido');
            }

            mostrarMensajeExito(result.message || 'Prenda actualizada correctamente');
            window.cancelarEditarPrendaRecibo();

            window.generarReciboBodega(estado.prendaId, estado.pedidoId, estado.tipoProceso);

        } catch (error) {
            console.error('Error al guardar prenda:', error);
            alert('Error al guardar los cambios: ' + error.message);
        }
    };

    // DEBUG: Mostrar roles en consola
    console.log(' User Roles:', window.selectorRecibosState.usuarioRoles);
    window.activarReciboCosturaBase = async function(prendaId) {
        try {
            const pedidoId = window.selectorRecibosState?.pedidoId;
            if (!pedidoId) {
                alert('Error: No se pudo determinar el pedido');
                return;
            }

            const titulo = 'Activar Recibo COSTURA';
            const mensaje = '¿Está seguro de que desea activar el recibo de COSTURA para esta prenda?';
            const colorBoton = '#10b981';

            mostrarModalConfirmar(titulo, mensaje, colorBoton, async () => {
                const response = await fetch(`/api/supervisor-pedidos/ordenes/${pedidoId}/costura/${prendaId}/activar-recibo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('[activarReciboCosturaBase] Respuesta no es JSON:', textResponse.substring(0, 200));
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
                }

                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Error al activar el recibo de COSTURA');
                }

                const consecutivoMsg = result.data?.consecutivo ? ` (Consecutivo: ${result.data.consecutivo})` : '';
                mostrarMensajeExito((result.message || 'Recibo activado') + consecutivoMsg);

                try {
                    await cargarDatosRecibos(pedidoId);
                } catch (recargaError) {
                    console.warn('Error al recargar datos (pero la activación tuvo éxito):', recargaError);
                }
            });

        } catch (error) {
            console.error('[activarReciboCosturaBase] Error:', error);
            alert('Error al activar el recibo: ' + error.message);
        }
    };

    console.log(' esSupervisorPedidos:', window.selectorRecibosState.esSupervisorPedidos);
    
    // Contadores de clics para debugging
    let prendaAccordionClickCount = 0;
    let procesoClickCount = 0;
    let lastAccordionClickTime = 0;
    let lastProcesoClickTime = 0;

    function normalizarTipoProcesoFrontend(tipoProceso) {
        return String(tipoProceso || '').trim().toUpperCase();
    }

    function actualizarContadorObservacionProceso() {
        const textarea = document.getElementById('obs-texto-proceso');
        const contador = document.getElementById('obs-contador-proceso');
        if (!textarea || !contador) return;
        contador.textContent = `${textarea.value.length}/2000`;
    }

    window.abrirModalObservacionReciboProceso = async function(prendaId, tipoProceso, pedidoId = null) {
        try {
            const pedido = pedidoId || window.selectorRecibosState?.pedidoId;
            if (!pedido) {
                alert('No se pudo determinar el pedido.');
                return;
            }

            const tipoNormalizado = normalizarTipoProcesoFrontend(tipoProceso);
            const modal = document.getElementById('modal-observacion-proceso');
            const subtitulo = document.getElementById('modal-observacion-proceso-subtitulo');
            const textarea = document.getElementById('obs-texto-proceso');

            document.getElementById('obs-pedido-id').value = String(pedido);
            document.getElementById('obs-prenda-id').value = String(prendaId);
            document.getElementById('obs-tipo-proceso').value = tipoNormalizado;

            if (subtitulo) {
                subtitulo.textContent = `Prenda #${prendaId} - ${tipoNormalizado}`;
            }
            if (textarea) {
                textarea.value = '';
                actualizarContadorObservacionProceso();
            }

            modal.style.display = 'block';

            const params = new URLSearchParams({
                pedido_id: String(pedido),
                prenda_id: String(prendaId),
                tipo_proceso: tipoNormalizado
            });

            const response = await fetch(`/api/supervisor-pedidos/recibos-procesos/observacion?${params.toString()}`);
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.includes('application/json')) return;

            const result = await response.json();
            if (result?.success && textarea) {
                textarea.value = result?.data?.observacion || '';
                actualizarContadorObservacionProceso();
            }
        } catch (error) {
            console.error('[abrirModalObservacionReciboProceso] Error:', error);
        }
    };

    window.cerrarModalObservacionProceso = function() {
        const modal = document.getElementById('modal-observacion-proceso');
        if (modal) modal.style.display = 'none';
    };

    window.guardarObservacionProcesoActual = async function() {
        const pedidoId = document.getElementById('obs-pedido-id')?.value;
        const prendaId = document.getElementById('obs-prenda-id')?.value;
        const tipoProceso = document.getElementById('obs-tipo-proceso')?.value;
        const observacion = document.getElementById('obs-texto-proceso')?.value || '';
        const btnGuardar = document.getElementById('btn-guardar-observacion-proceso');

        if (!pedidoId || !prendaId || !tipoProceso) {
            alert('Faltan datos para guardar la observación.');
            return;
        }

        try {
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';
            }

            const response = await fetch('/api/supervisor-pedidos/recibos-procesos/observacion', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    pedido_id: Number(pedidoId),
                    prenda_id: Number(prendaId),
                    tipo_proceso: tipoProceso,
                    observacion
                })
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Respuesta inválida del servidor (${response.status}).`);
            }

            const result = await response.json();
            if (!response.ok || !result?.success) {
                throw new Error(result?.message || 'No fue posible guardar la observación.');
            }

            mostrarMensajeExito(result.message || 'Observación guardada correctamente.');
            cerrarModalObservacionProceso();
        } catch (error) {
            console.error('[guardarObservacionProcesoActual] Error:', error);
            alert(error.message || 'Error al guardar observación.');
        } finally {
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.textContent = 'Guardar';
            }
        }
    };

    document.addEventListener('input', (e) => {
        if (e.target && e.target.id === 'obs-texto-proceso') {
            actualizarContadorObservacionProceso();
        }
    });

    function resolverEstadoEntregaPrenda(prenda, recibos = []) {
        const todosRecibosEnDespacho = Array.isArray(recibos)
            && recibos.length > 0
            && recibos.every((recibo) => String(recibo?.area || '').trim().toUpperCase() === 'DESPACHO');
        const estadoEntregaBackend = String(prenda?.entrega?.estado_entrega || '').toLowerCase();
        const entregadoFlag = prenda?.entrega?.entregado === true
            || prenda?.entrega?.entregado === 1
            || prenda?.entrega?.entregado === '1';

        if (entregadoFlag) {
            return 'completo';
        }

        if (estadoEntregaBackend) {
            return estadoEntregaBackend;
        }

        return todosRecibosEnDespacho ? 'completo' : 'pendiente';
    }

    function actualizarPanelEntregaMasiva(prendas = []) {
        const panel = document.getElementById('selector-entrega-masiva-actions');
        const resumen = document.getElementById('selector-entrega-masiva-resumen');
        const boton = document.getElementById('btn-entregar-todas-prendas');

        if (!panel || !resumen || !boton) {
            return;
        }

        const ocultarBotonEntregar = window.location.pathname.includes('/registros');
        if (ocultarBotonEntregar) {
            panel.style.display = 'none';
            return;
        }

        const total = Array.isArray(prendas) ? prendas.length : 0;
        const pendientes = (Array.isArray(prendas) ? prendas : []).filter((prenda) => {
            return resolverEstadoEntregaPrenda(prenda) !== 'completo';
        }).length;

        const mostrarPanel = total > 0 && pendientes > 0;
        panel.style.display = mostrarPanel ? 'flex' : 'none';
        boton.disabled = !mostrarPanel;
        resumen.textContent = mostrarPanel
            ? `${pendientes} prenda(s) pendiente(s) de ${total}`
            : '';
    }

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
        actualizarPanelEntregaMasiva([]);

        // Resetear contadores al abrir modal
        prendaAccordionClickCount = 0;
        procesoClickCount = 0;
        // Cargar datos de recibos
        try {
            // Usar siempre la ruta completa que retorna procesos pendientes
            // /pedidos-public/{id}/recibos-datos retorna datos completos (ObtenerDetalleCompletoUseCase)
            // /registros/{id}/recibos-datos retorna solo procesos aprobados (GetRecibosDatosUseCase)
            const apiUrl = `/pedidos-public/${pedidoId}/recibos-datos`;
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const datos = result.data || result;
            
            window.selectorRecibosState.prendas = datos.prendas || [];
            window.selectorRecibosState.pedidoEstado = datos.estado || null;

            // Actualizar número de pedido (puede ser null)
            const numeroPedido = datos.numero || datos.numero_pedido || datos.id;
            document.getElementById('selector-pedido-numero').textContent = `#${numeroPedido}`;
            window.selectorRecibosState.numeroPedido = numeroPedido;

            // Renderizar prendas
            renderizarPrendasEnSelector(datos.prendas);

            // Restaurar scroll del modal después de renderizar
            try {
                const modalEl = document.getElementById('recibos-process-selector-modal');
                const savedScroll = window.selectorRecibosState?.uiState?.scrollTop;
                if (modalEl && typeof savedScroll === 'number' && savedScroll > 0) {
                    modalEl.scrollTop = savedScroll;
                }
            } catch (e) {}

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

        // Filtrar prendas eliminadas (soft-deleted) por seguridad
        prendas = prendas.filter(p => !p.deleted_at);

        if (prendas.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #9ca3af; padding: 40px;">No hay prendas activas en este pedido</p>';
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
            
            if (!esVistaVisualizadorLogo && !excluirCosturaBodega) {
                //  RECIBO BASE - SOLO EN OTRAS VISTAS
                const reciboCosturaActual = prenda?.recibos?.COSTURA || prenda?.consecutivos?.COSTURA || null;
                const reciboCosturaBodegaActual = prenda?.recibos?.['COSTURA-BODEGA'] || prenda?.consecutivos?.['COSTURA-BODEGA'] || null;
                const reciboBaseActual = reciboCosturaActual || reciboCosturaBodegaActual || null;
                
                // Determinar el estado correcto usando el campo 'activo' de la BD
                let estadoRecibo = 'PENDIENTE';
                let numeroRecibo = null;
                let activoValue = 0;
                
                if (reciboBaseActual) {
                    // Nuevo formato: objeto con datos completos
                    if (typeof reciboBaseActual === 'object' && reciboBaseActual.activo !== undefined) {
                        estadoRecibo = reciboBaseActual.activo === 1 ? 'APROBADO' : 'PENDIENTE';
                        numeroRecibo = reciboBaseActual.consecutivo_actual || null;
                        activoValue = reciboBaseActual.activo;
                    } 
                    // Formato antiguo: solo el número de consecutivo
                    else if (reciboBaseActual) {
                        estadoRecibo = 'APROBADO';
                        numeroRecibo = reciboBaseActual;
                        activoValue = 1;
                    }
                }
                
                const reciboBase = {
                    tipo: prenda.de_bodega == 1 ? "costura-bodega" : "costura",
                    nombre: prenda.de_bodega == 1 ? "Bodega" : "Costura",
                    estado: estadoRecibo,
                    es_base: true,
                    numero_recibo: numeroRecibo,
                    activo: activoValue, // Agregar campo activo para referencia
                    consecutivo_recibo_id: reciboBaseActual?.id || null,
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
                    nombre: proc.nombre_proceso || tipoProceso,  // Usar nombre_proceso para anexos (ej: "BORDADO ANEXO 1")
                    estado: proc.estado || "",
                    es_base: false,
                    // Copiar propiedades de anexos si existen
                    es_parcial: proc.es_parcial || false,
                    pedido_parcial_id: proc.pedido_parcial_id || null,
                    numero_recibo: proc.numero_recibo || null,
                    consecutivo_recibo_id: proc.consecutivo_recibo_id || null
                });
            });

            //  RECIBOS PARCIALES (ANEXOS)
            const parciales = prenda.recibos?.parciales || [];
            
            parciales.forEach((parcial, index) => {
                // Determinar el estado del parcial
                const estadoParcial = parcial.activo === 1 ? 'APROBADO' : (parcial.estado || 'PENDIENTE');
                const tipoParcial = String(parcial.tipo_recibo || '').toUpperCase() === 'COSTURA-BODEGA'
                    ? 'COSTURA'
                    : String(parcial.tipo_recibo || '');
                
                // Crear nombre descriptivo para el parcial
                const nombreParcial = `${tipoParcial} ANEXO ${index + 1}`;
                
                recibos.push({
                    tipo: tipoParcial,
                    nombre: nombreParcial,
                    estado: estadoParcial,
                    es_base: false,
                    es_parcial: true,
                    pedido_parcial_id: parcial.id,
                    numero_recibo: parcial.consecutivo_actual || null,
                    activo: parcial.activo || 0,
                    consecutivo_recibo_id: parcial.consecutivo_recibo_id || null,
                    created_at: parcial.created_at,
                    origen: 'PARCIAL'
                });
            });

            const idAccordion = `prenda-${prenda.id || prendaIdx}`;
            const totalRecibos = recibos.length;
            const indicadorBodega = prenda.de_bodega == 1 ? ' <span style="color: #ef4444; font-size: 12px; font-weight: 600; margin-left: 8px;">(SE SACA DE BODEGA)</span>' : '';

            // Verificar si la prenda está entregada
            const estadoEntrega = resolverEstadoEntregaPrenda(prenda, recibos);
            const estaEntregada = estadoEntrega === 'completo';
            const entregaParcial = estadoEntrega === 'parcial';
            const claseEntregada = estaEntregada ? 'entregada' : '';
            const claseBotonEntregado = estaEntregada ? 'entregado' : '';
            const textoBoton = estaEntregada ? 'Deshacer' : (entregaParcial ? 'Parcial' : 'Entregar');
            const colorBoton = estaEntregada ? '#f59e0b' : (entregaParcial ? '#2563eb' : '#10b981');
            const iconoBoton = estaEntregada ? 'fa-rotate-left' : (entregaParcial ? 'fa-layer-group' : 'fa-check-circle');
            const ocultarBotonEntregar = window.location.pathname.includes('/registros');
            
            // Depuración completa de datos de la prenda
            console.log('[PRENDA-DEBUG] Datos completos de la prenda:', {
                prenda_id: prenda.id,
                nombre: prenda.nombre,
                entrega: prenda.entrega,
                entrega_existe: !!prenda.entrega,
                entregado: estaEntregada,
                estado_entrega: estadoEntrega,
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
                        <button class="btn-entregar-prenda ${claseBotonEntregado}" onclick="event.stopPropagation(); toggleEntregarPrenda(this, ${prenda.id || prendaIdx})" style="background: ${colorBoton};" title="${entregaParcial ? 'La prenda tiene entregas parciales registradas' : ''}">
                            <i class="fas ${iconoBoton}"></i>
                            <span>${textoBoton}</span>
                        </button>`;

            const botonVerEntregasHtml = `
                        <button class="btn-ver-entregas-prenda" onclick="event.stopPropagation(); abrirHistorialEntregasPrenda(${prenda.id || prendaIdx})" title="Ver entregas parciales enviadas a despacho">
                            <i class="fas fa-history"></i>
                            <span>Ver entregas</span>
                        </button>`;

            const botonGenerarReciboCosturaHtml = (ocultarBotonEntregar || prenda.de_bodega != 1) ? '' : `
                        <div class="recibo-parcial-select-wrapper" onclick="event.stopPropagation();" title="Selecciona el tipo de recibo por talla">
                            <i class="fas fa-file-alt recibo-parcial-select-icon"></i>
                            <select class="select-recibo-parcial has-icon"
                                    onclick="event.stopPropagation();"
                                    onchange="event.stopPropagation(); handleReciboBodegaSelect(this, ${prenda.id || prendaIdx}, ${window.selectorRecibosState?.pedidoId})">
                                <option value="">Generar recibo</option>
                                <option value="costura">Costura</option>
                                <option value="reflectivo">Reflectivo</option>
                            </select>
                            <span class="recibo-parcial-select-arrow">▼</span>
                        </div>`;

            html += `
                <div class="prenda-accordion">
                    <div class="prenda-header ${claseEntregada}" onclick="togglePrendaAccordion(this, '${idAccordion}')" data-prenda-id="${prenda.id || prendaIdx}" data-estado-entrega="${estadoEntrega}">
                        <div style="flex: 1;">
                            <p class="prenda-title">${prenda.nombre || 'Prenda sin nombre'}${indicadorBodega}</p>
                            <p class="prenda-subtitle">${totalRecibos} recibo(s)</p>
                        </div>
                        ${botonEntregarHtml}
                        ${botonVerEntregasHtml}
                        ${botonGenerarReciboCosturaHtml}
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
                    
                    // Determinar si es un anexo (recibo parcial)
                    const esParcial = recibo.es_parcial || false;
                    const parcialId = recibo.pedido_parcial_id || null;
                    
                    // Determinar si el recibo está activo
                    // Para procesos normales: estado APROBADO + numero_recibo
                    // Para parciales/anexos: estado APROBADO
                    // Para costura (base): usar el campo 'activo' o el estado
                    let estaActivo = false;
                    let puedeActivar = false;
                    
                    console.log('[renderizarPrendasEnSelector] DEBUG - Lógica de activación:', {
                        reciboTipo: recibo.tipo,
                        reciboEstado: recibo.estado,
                        reciboEsBase: recibo.es_base,
                        reciboActivo: recibo.activo,
                        reciboNumeroRecibo: recibo.numero_recibo,
                        esParcial
                    });
                    
                    if (recibo.es_base) {
                        // Recibo base (costura/costura-bodega)
                        if (recibo.activo !== undefined) {
                            // Nuevo formato: usar campo 'activo' explícito
                            estaActivo = recibo.activo === 1;
                            puedeActivar = recibo.activo === 0;
                            
                            console.log('[renderizarPrendasEnSelector] DEBUG - Recibo base con campo activo:', {
                                activo: recibo.activo,
                                estaActivo,
                                puedeActivar
                            });
                        } else {
                            // Formato antiguo: basado en estado y número de recibo
                            estaActivo = recibo.estado === 'APROBADO' && recibo.numero_recibo;
                            puedeActivar = recibo.estado === 'PENDIENTE';
                            
                            console.log('[renderizarPrendasEnSelector] DEBUG - Recibo base formato antiguo:', {
                                estado: recibo.estado,
                                numero_recibo: recibo.numero_recibo,
                                estaActivo,
                                puedeActivar
                            });
                        }
                    } else {
                        // Procesos adicionales
                        estaActivo = recibo.estado === 'APROBADO' && (recibo.numero_recibo || esParcial);
                        puedeActivar = recibo.estado === 'PENDIENTE';
                        
                        console.log('[renderizarPrendasEnSelector] DEBUG - Proceso adicional:', {
                            estado: recibo.estado,
                            numero_recibo: recibo.numero_recibo,
                            esParcial,
                            estaActivo,
                            puedeActivar
                        });
                    }
                    
                    //  CRÍTICO: Solo supervisor_pedidos puede activar/desactivar recibos
                    const usuarioEsSupervisor = window.selectorRecibosState?.esSupervisorPedidos || window.selectorRecibosState?.esSupervisor;
                    
                    // Variables básicas del recibo
                    const tipoStringLower = String(tipoString || '').toLowerCase();
                    
                    // Para recibos base (costura), no usar el botón general de activación
                    const esReciboBaseCostura = recibo.es_base && (tipoStringLower === 'costura' || tipoStringLower === 'costura-bodega');
                    const puedeModificarRecibo = puedeActivar && usuarioEsSupervisor && !esReciboBaseCostura;
                    
                    // Verificar si este proceso base tiene anexos/parciales creados
                    const tieneAnexos = !esParcial && recibos.some(r => r.es_parcial && String(r.tipo).toLowerCase() === tipoStringLower);
                    const puedeModificarReciboFinal = puedeModificarRecibo && !tieneAnexos;
                    
                    const puedeDesactivarRecibo = estaActivo && usuarioEsSupervisor;
                    
                    // Botón Anular aparece cuando estado es APROBADO (independientemente de numero_recibo)
                    const puedeAnularRecibo = recibo.estado === 'APROBADO' && usuarioEsSupervisor;
                    
                    const reciboClass = estaActivo ? 'recibo-activo' : '';
                    
                    // Verificar si el usuario puede crear recibos por talla (supervisor_pedidos o admin)
                    const esSupervisorRecibos = window.selectorRecibosState?.usuarioRoles?.includes('supervisor_pedidos') ||
                                               window.selectorRecibosState?.esSupervisorPedidos === 'true';

                    // No permitir recibo por talla en Costura (ni costura-bodega) ni si el proceso ya está APROBADO
                    const puedeCrearPorTalla = esSupervisorRecibos && (tipoStringLower !== 'costura' && tipoStringLower !== 'costura-bodega') && recibo.estado !== 'APROBADO';

                    const pedidoEstado = window.selectorRecibosState?.pedidoEstado;
                    const pedidoYaAprobado = pedidoEstado && String(pedidoEstado).toUpperCase() !== 'PENDIENTE_SUPERVISOR';

                    const puedeActivarBaseCostura = recibo.es_base && tipoStringLower === 'costura' && puedeActivar && pedidoYaAprobado && usuarioEsSupervisor;
                    
                    // DEBUG: Mostrar decisión de visibilidad del botón
                    console.log(`🔘 Prenda ${prenda.id}: esSupervisorRecibos=${esSupervisorRecibos}, esSupervisorPedidos=${window.selectorRecibosState?.esSupervisorPedidos}`);
                    
                    const pedidoId = window.selectorRecibosState?.pedidoId;
                    
                    html += `
                        <div class="proceso-item ${reciboClass}" 
                             data-prenda-id="${prenda.id}" 
                             data-tipo-string="${tipoString}"
                             data-es-parcial="${esParcial}"
                             data-pedido-parcial-id="${parcialId || ''}"
                             data-nombre-proceso="${(recibo.nombre || '').replace(/"/g, '&quot;')}"
                             onclick="seleccionarProcesoConDataAttributes(this)">
                            <div class="proceso-info">
                                <p class="proceso-name">${recibo.nombre}</p>
                                ${recibo.estado ? `<span class="proceso-estado ${estadoClass}">${estadoLabel}</span>` : ''}
                                ${''}
                            </div>
                            <div class="proceso-acciones">
                                <button class="btn-observacion-proceso"
                                        onclick="event.stopPropagation(); abrirModalObservacionReciboProceso(${prenda.id}, '${tipoString}', ${pedidoId})"
                                        title="Agregar observación">
                                    <i class="fas fa-comment-alt"></i>
                                    Observ.
                                </button>
                                ${puedeCrearPorTalla ? `
                                    <button class="btn-recibo-parcial" 
                                            onclick="event.stopPropagation(); abrirModalReciboParcial(${prenda.id}, '${tipoString}', ${pedidoId})"
                                            title="Crear recibo por talla">
                                        <i class="fas fa-layer-group"></i>
                                        Por Talla
                                    </button>
                                ` : ''}
                                ${puedeModificarReciboFinal ? `
                                    <button class="btn-activar-recibo" 
                                            onclick="event.stopPropagation(); toggleActivarRecibo(${prenda.id}, '${tipoString}', ${!estaActivo}, ${esParcial ? parcialId : 'null'})"
                                            title="Activar recibo">
                                        <i class="fas fa-check"></i>
                                        Activar
                                    </button>
                                ` : ''}
                                ${puedeActivarBaseCostura ? `
                                    <button class="btn-activar-recibo" 
                                            onclick="event.stopPropagation(); activarReciboCosturaBase(${prenda.id})"
                                            title="Activar recibo">
                                        <i class="fas fa-check"></i>
                                        Activar
                                    </button>
                                ` : ''}
                                ${puedeAnularRecibo ? `
                                    <button class="btn-anular-recibo" 
                                            onclick="event.stopPropagation(); anularRecibo(${prenda.id}, '${tipoString}', ${esParcial ? parcialId : 'null'})"
                                            title="Anular recibo">
                                        <i class="fas fa-ban"></i>
                                        Anular
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
        actualizarPanelEntregaMasiva(prendas);

        // Restaurar estado UI (acordeones abiertos) si existe
        try {
            const expanded = window.selectorRecibosState?.uiState?.expandedAccordions;
            if (Array.isArray(expanded) && expanded.length > 0) {
                expanded.forEach((id) => {
                    const processes = document.getElementById(id);
                    if (!processes) return;
                    processes.style.display = 'block';
                    const header = processes.previousElementSibling;
                    if (header && header.classList) {
                        header.classList.add('expanded');
                        const chevron = header.querySelector('.prenda-chevron');
                        if (chevron && chevron.classList) chevron.classList.add('expanded');
                    }
                });
            }
        } catch (e) {}
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
     * Selecciona un proceso leyendo datos desde data-* attributes
     * Wrapper seguro para evitar problemas de sintaxis en JSON inline
     * @param {HTMLElement} element - Elemento con los data-* attributes
     */
    window.seleccionarProcesoConDataAttributes = function(element) {
        try {
            const prendaId = parseInt(element.getAttribute('data-prenda-id'));
            const tipoString = element.getAttribute('data-tipo-string');
            const esParcial = element.getAttribute('data-es-parcial') === 'true';
            const pedidoParcialId = element.getAttribute('data-pedido-parcial-id');
            const nombreProceso = element.getAttribute('data-nombre-proceso')?.replace(/&quot;/g, '"') || '';

            console.log(`[seleccionarProcesoConDataAttributes] Leyendo datos:`, {
                prendaId,
                tipoString,
                esParcial,
                pedidoParcialId,
                nombreProceso
            });

            // Construir datosAdicionales
            const datosAdicionales = {
                es_parcial: esParcial,
                pedido_parcial_id: esParcial && pedidoParcialId ? parseInt(pedidoParcialId) : null,
                nombre_proceso: nombreProceso
            };

            // Llamar a la función original con los datos extraídos
            window.seleccionarProceso(prendaId, tipoString, datosAdicionales);

        } catch (error) {
            console.error('[seleccionarProcesoConDataAttributes] Error:', error);
            alert('Error al procesar la selección: ' + error.message);
        }
    };

    /**
     * Selecciona un proceso específico
     *  GARANTIZA que tipoProceso siempre sea STRING
     * @param {number} prendaId - ID de la prenda (DEBE ser número)
     * @param {string} tipoProceso - Tipo/nombre del proceso (DEBE ser STRING)
     */
    window.seleccionarProceso = function(prendaId, tipoProceso, datosAdicionales) {
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

        // Guardar estado UI antes de cerrar selector (scroll + acordeones abiertos)
        try {
            const modal = document.getElementById('recibos-process-selector-modal');
            const expandedAccordions = Array.from(document.querySelectorAll('#selector-prendas-list .prenda-processes'))
                .filter((el) => el && el.style && el.style.display === 'block')
                .map((el) => el.id)
                .filter(Boolean);
            window.selectorRecibosState.uiState = {
                scrollTop: modal ? (modal.scrollTop || 0) : 0,
                expandedAccordions
            };
        } catch (e) {}

        // Cerrar selector
        cerrarSelectorRecibos();

        // Si es un anexo (recibo parcial), usar datos diferentes
        if (datosAdicionales && datosAdicionales.es_parcial) {
            console.log(`[PRENDA-DEBUG] Abriendo anexo: pedido_parcial_id=${datosAdicionales.pedido_parcial_id}, nombre=${datosAdicionales.nombre_proceso}`);
            // Guardar nombre del proceso en estado
            window.selectorRecibosState.nombreProcesoAnexo = datosAdicionales.nombre_proceso;
            // Cargar datos del parcial desde endpoint específico
            // Pasar también prendaId y tipoString para generar HTML del proceso
            window.openOrderDetailModalWithParcial(datosAdicionales.pedido_parcial_id, prendaId, tipoString);
        } else {
            // Abrir modal de recibo normal
            window.openOrderDetailModalWithProcess(pedidoId, prendaId, tipoString);
        }
        
        console.log(`[PRENDA-DEBUG] Proceso Click #${procesoClickCount} - Modal de recibo solicitado`);
    };

    // openOrderDetailModalWithParcial ahora se define en PedidosRecibosModule.js
    // usando la pipeline de renderizado completa (sin hacks de DOM)

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
    window.toggleActivarRecibo = async function(prendaId, tipoProceso, activar, parcialId = null) {
        try {
            // Mostrar modal de confirmación
            const accion = activar ? 'activar' : 'desactivar';
            const titulo = activar ? 'Activar Recibo' : 'Desactivar Recibo';
            const esParcial = parcialId !== null && parcialId !== 'null';
            const tipoLabel = esParcial ? `anexo de ${tipoProceso}` : tipoProceso;
            const mensaje = `¿Está seguro de que desea ${accion} el recibo de ${tipoLabel}?`;
            const colorBoton = activar ? '#10b981' : '#ef4444';
            
            mostrarModalConfirmar(titulo, mensaje, colorBoton, async () => {
                if (esParcial && activar) {
                    await ejecutarActivarParcial(parcialId);
                } else {
                    await ejecutarActivarRecibo(prendaId, tipoProceso, activar);
                }
            });

        } catch (error) {
            console.error('Error al actualizar recibo:', error);
            alert('Error al actualizar el recibo: ' + error.message);
        }
    };

    /**
     * Anula un recibo activo (cambia estado a ANULADA)
     * @param {number} prendaId
     * @param {string} tipoProceso
     * @param {number|null} parcialId
     */
    window.anularRecibo = async function(prendaId, tipoProceso, parcialId = null) {
        try {
            const esParcial = parcialId !== null && parcialId !== 'null';
            const tipoLabel = esParcial ? `anexo de ${tipoProceso}` : tipoProceso;
            const titulo = 'Anular Recibo';
            const mensaje = `¿Está seguro de que desea anular el recibo de ${tipoLabel}?`;
            const colorBoton = '#ef4444';

            mostrarModalConfirmar(titulo, mensaje, colorBoton, async () => {
                if (esParcial) {
                    await ejecutarAnularParcial(parcialId);
                } else {
                    await ejecutarAnularRecibo(prendaId, tipoProceso);
                }
            });

        } catch (error) {
            console.error('Error al anular recibo:', error);
            alert('Error al anular el recibo: ' + error.message);
        }
    };

    /**
     * Ejecuta la anulación de un recibo normal
     */
    async function ejecutarAnularRecibo(prendaId, tipoProceso) {
        try {
            const pedidoId = window.selectorRecibosState.pedidoId;
            
            console.log('[ejecutarAnularRecibo] Iniciando anulación:', {
                prendaId,
                tipoProceso,
                pedidoId
            });
            
            // Determinar si es un recibo base (costura/costura-bodega) o un proceso
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();
            const esReciboBase = tipoProcesoLower === 'costura' || tipoProcesoLower === 'costura-bodega';
            
            if (esReciboBase) {
                // Para recibos base (costura), usar el endpoint específico
                console.log('[ejecutarAnularRecibo] Anulando recibo base (costura)');
                
                const response = await fetch(`/api/supervisor-pedidos/ordenes/${pedidoId}/costura/${prendaId}/anular-recibo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('[ejecutarAnularRecibo] Respuesta no es JSON:', textResponse.substring(0, 200));
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
                }
                
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Error al anular el recibo de costura');
                }
                
                mostrarMensajeExito(result.message);
                
            } else {
                // Para procesos adicionales, buscar el proceso ID
                console.log('[ejecutarAnularRecibo] Anulando proceso adicional');
                
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
                    alert('Error: No se encontró el proceso para anular');
                    return;
                }

                const response = await fetch(`/procesos/${procesoId}/anular-recibo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('[ejecutarAnularRecibo] Respuesta no es JSON:', textResponse.substring(0, 200));
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
                }

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Error al anular el recibo');
                }

                mostrarMensajeExito(result.message);
            }

            // Recargar datos en ambos casos
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos (pero la operación principal tuvo éxito):', recargaError);
                mostrarMensajeExito('Recibo anulado correctamente (la vista se actualizará en la próxima recarga)');
            }

        } catch (error) {
            console.error('[ejecutarAnularRecibo] Error:', error);
            alert('Error al anular el recibo: ' + error.message);
        }
    }

    /**
     * Ejecuta la anulación de un recibo parcial (anexo)
     */
    async function ejecutarAnularParcial(parcialId) {
        try {
            const response = await fetch(`/api/recibos-parciales/${parcialId}/anular`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('[ejecutarAnularParcial] Respuesta no es JSON:', textResponse.substring(0, 200));
                throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
            }

            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Error al anular el anexo');
            }

            mostrarMensajeExito(result.message);

            const pedidoId = window.selectorRecibosState.pedidoId;
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos (pero la operación principal tuvo éxito):', recargaError);
                mostrarMensajeExito('Anexo anulado correctamente (la vista se actualizará en la próxima recarga)');
            }

        } catch (error) {
            console.error('[ejecutarAnularParcial] Error:', error);
            alert('Error al anular el anexo: ' + error.message);
        }
    }

    /**
     * Ejecuta la activación/desactivación del recibo
     */
    async function ejecutarActivarRecibo(prendaId, tipoProceso, activar) {
        try {
            const pedidoId = window.selectorRecibosState.pedidoId;
            
            console.log('[ejecutarActivarRecibo] Iniciando activación:', {
                prendaId,
                tipoProceso,
                activar,
                pedidoId
            });
            
            // Determinar si es un recibo base (costura/costura-bodega) o un proceso
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();
            const esReciboBase = tipoProcesoLower === 'costura' || tipoProcesoLower === 'costura-bodega';
            
            if (esReciboBase) {
                // Para recibos base (costura), usar el endpoint específico de supervisor-pedidos
                console.log('[ejecutarActivarRecibo] Activando recibo base (costura)');
                
                const response = await fetch(`/api/supervisor-pedidos/ordenes/${pedidoId}/costura/${prendaId}/activar-recibo`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('[ejecutarActivarRecibo] Respuesta no es JSON:', textResponse.substring(0, 200));
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
                }
                
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Error al activar el recibo de costura');
                }
                
                mostrarMensajeExito(result.message);
                
            } else {
                // Para procesos adicionales, buscar el proceso ID
                console.log('[ejecutarActivarRecibo] Activando proceso adicional');
                
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
                    alert('Error: No se encontró el proceso para activar');
                    return;
                }

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

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const textResponse = await response.text();
                    console.error('[ejecutarActivarRecibo] Respuesta no es JSON:', textResponse.substring(0, 200));
                    throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
                }

                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Error al activar el recibo');
                }

                mostrarMensajeExito(result.message);
            }

            // Recargar datos en ambos casos
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos (pero la operación principal tuvo éxito):', recargaError);
                mostrarMensajeExito('Recibo activado correctamente (la vista se actualizará en la próxima recarga)');
            }

        } catch (error) {
            console.error('[ejecutarActivarRecibo] Error:', error);
            alert('Error al actualizar recibo: ' + error.message);
        }
    }

    /**
     * Ejecuta la activación de un recibo parcial (anexo)
     * Llama al endpoint específico para parciales que genera el consecutivo
     */
    async function ejecutarActivarParcial(parcialId) {
        try {
            console.log('[ejecutarActivarParcial] Activando parcial:', parcialId);

            const response = await fetch(`/api/recibos-parciales/${parcialId}/activar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const textResponse = await response.text();
                console.error('[ejecutarActivarParcial] Respuesta no es JSON:', textResponse.substring(0, 200));
                throw new Error('El servidor devolvió HTML en lugar de JSON. Posible problema de autenticación.');
            }

            const result = await response.json();
            console.log('[ejecutarActivarParcial] Respuesta:', result);

            if (!response.ok) {
                throw new Error(result.message || 'Error al activar el recibo parcial');
            }

            const consecutivoMsg = result.data?.consecutivo 
                ? ` (Consecutivo: ${result.data.consecutivo})` 
                : '';
            mostrarMensajeExito(result.message + consecutivoMsg);

            // Recargar datos para actualizar la vista
            const pedidoId = window.selectorRecibosState.pedidoId;
            try {
                await cargarDatosRecibos(pedidoId);
            } catch (recargaError) {
                console.warn('Error al recargar datos (pero la activación tuvo éxito):', recargaError);
                mostrarMensajeExito('Anexo activado correctamente' + consecutivoMsg);
            }

        } catch (error) {
            console.error('[ejecutarActivarParcial] Error:', error);
            alert('Error al activar el anexo: ' + error.message);
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

    function obtenerPrendaSelector(prendaId) {
        return window.selectorRecibosState?.prendas?.find((prenda) => Number(prenda.id) === Number(prendaId)) || null;
    }

    function obtenerRecibosEntregablesPrenda(prendaId) {
        const prenda = obtenerPrendaSelector(prendaId);
        if (!prenda) {
            console.warn('[Entrega Parcial][obtenerRecibosEntregablesPrenda] Prenda no encontrada en selector:', {
                prendaId,
                prendasDisponibles: window.selectorRecibosState?.prendas || [],
            });
            return [];
        }

        const recibos = [];
        const recibosData = prenda.recibos || {};
        const consecutivosData = prenda.consecutivos || {};
        const totalCantidadPrenda = Array.isArray(prenda.tallas)
            ? prenda.tallas.reduce((sum, talla) => sum + Number(talla.cantidad || 0), 0)
            : 1;

        const obtenerIdRecibo = (recibo) => {
            if (recibo == null) {
                return null;
            }

            if (typeof recibo === 'object') {
                return Number(
                    recibo.consecutivo_recibo_id
                    || recibo.id
                    || recibo.recibo_id
                    || 0
                ) || null;
            }

            return null;
        };

        const reciboCostura = recibosData.COSTURA || consecutivosData.COSTURA || null;
        console.log('[Entrega Parcial][obtenerRecibosEntregablesPrenda] Busqueda recibo COSTURA:', {
            prendaId,
            prendaNombre: prenda.nombre,
            recibosData,
            consecutivosData,
            reciboCostura,
            tipoReciboCostura: typeof reciboCostura,
            reciboCosturaId: obtenerIdRecibo(reciboCostura),
            tallas: prenda.tallas,
            totalCantidadPrenda,
        });

        if (reciboCostura) {
            const consecutivoReciboId = obtenerIdRecibo(reciboCostura);
            const numeroRecibo = (typeof reciboCostura === 'object'
                ? (reciboCostura.consecutivo_actual || reciboCostura.numero_recibo || reciboCostura.consecutivo_inicial)
                : reciboCostura) || 'S/N';

            recibos.push({
                key: `base-${consecutivoReciboId || prendaId}`,
                label: `Costura #${numeroRecibo}`,
                consecutivo_recibo_id: consecutivoReciboId,
                cantidad_sugerida: totalCantidadPrenda,
            });
        }

        console.log('[Entrega Parcial][obtenerRecibosEntregablesPrenda] Resultado recibos entregables:', {
            prendaId,
            prendaNombre: prenda.nombre,
            recibosEntregables: recibos,
        });

        return recibos;
    }

    async function abrirDecisionEntregaPrenda(prendaId) {
        const prenda = obtenerPrendaSelector(prendaId);
        const recibos = obtenerRecibosEntregablesPrenda(prendaId);
        const pedidoEstado = String(window.selectorRecibosState?.pedidoEstado || '').toUpperCase();
        const pedidoPendienteSupervisor = pedidoEstado === 'PENDIENTE_SUPERVISOR';

        if (!prenda) {
            throw new Error('No se encontro la prenda en el selector.');
        }

        console.log('[Entrega Parcial][abrirDecisionEntregaPrenda] Decision modal:', {
            prendaId,
            prendaNombre: prenda.nombre,
            recibosDetectados: recibos,
            tieneReciboCosturaParaParcial: recibos.length > 0,
        });

        if (typeof Swal === 'undefined') {
            return { modo: 'completo' };
        }

        const decision = await Swal.fire({
            title: `Entrega de ${prenda.nombre || 'prenda'}`,
            text: pedidoPendienteSupervisor
                ? 'El pedido todavía no ha sido aprobado. Solo puedes revisar la información por ahora.'
                : (recibos.length > 0
                ? 'Elige si vas a registrar toda la prenda o solo una entrega parcial.'
                : 'Esta prenda no tiene recibo de costura disponible para entrega parcial. Puedes entregarla completa.'),
            icon: 'question',
            showCancelButton: true,
            showDenyButton: recibos.length > 0 || pedidoPendienteSupervisor,
            confirmButtonText: 'Completo',
            denyButtonText: 'Parcial',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            confirmButtonColor: '#10b981',
            denyButtonColor: '#2563eb',
        });

        if (decision.isDismissed) {
            return null;
        }

        if (pedidoPendienteSupervisor) {
            await Swal.fire({
                icon: 'warning',
                title: 'Pedido sin aprobar',
                text: 'El pedido todavia no ha sido aprobado, por eso no se puede realizar una entrega.',
                confirmButtonColor: '#f59e0b',
            });
            return null;
        }

        if (decision.isConfirmed) {
            return { modo: 'completo' };
        }

        if (recibos.length === 0) {
            return null;
        }

        if (typeof window.abrirModalReciboParcial !== 'function') {
            throw new Error('El modal de entrega parcial no esta disponible.');
        }

        cerrarSelectorRecibos();
        await window.abrirModalReciboParcial(prendaId, 'costura', window.selectorRecibosState?.pedidoId, {
            mode: 'entrega_parcial',
            consecutivoReciboId: recibos[0]?.consecutivo_recibo_id || null,
        });

        return null;
    }

    window.cerrarModalHistorialEntregas = function() {
        const modal = document.getElementById('modal-historial-entregas');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    window.abrirHistorialEntregasPrenda = async function(prendaId) {
        const prenda = obtenerPrendaSelector(prendaId);
        const modal = document.getElementById('modal-historial-entregas');
        const loading = document.getElementById('historial-entregas-loading');
        const error = document.getElementById('historial-entregas-error');
        const content = document.getElementById('historial-entregas-content');
        const titulo = document.getElementById('historial-entregas-titulo');
        const subtitulo = document.getElementById('historial-entregas-subtitulo');

        if (!modal || !loading || !error || !content) {
            return;
        }

        titulo.textContent = `Entregas parciales de ${prenda?.nombre || 'la prenda'}`;
        subtitulo.textContent = 'Movimientos enviados a despacho';
        content.innerHTML = '';
        error.style.display = 'none';
        error.textContent = '';
        loading.style.display = 'block';
        modal.style.display = 'block';

        try {
            const response = await fetch(`/api/prendas-entregas/${prendaId}/movimientos`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || `HTTP ${response.status}`);
            }

            const movimientos = Array.isArray(result.data?.movimientos) ? result.data.movimientos : [];
            if (movimientos.length === 0) {
                content.innerHTML = `
                    <div style="padding: 20px; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; text-align: center;">
                        Esta prenda todavía no tiene entregas parciales registradas.
                    </div>
                `;
                return;
            }

            content.innerHTML = movimientos.map((movimiento, index) => {
                const detalleTallas = Array.isArray(movimiento.detalle_tallas) ? movimiento.detalle_tallas : [];
                const detalleHtml = detalleTallas.length > 0
                    ? detalleTallas.map((item) => {
                        const talla = item?.talla || 'Sin talla';
                        const genero = item?.genero ? ` - ${item.genero}` : '';
                        const color = item?.color_nombre ? ` - ${item.color_nombre}` : '';
                        const cantidad = Number(item?.cantidad || 0);
                        return `<span style="display:inline-flex; gap:4px; align-items:center; background:#f1f5f9; color:#0f172a; border-radius:999px; padding:4px 10px; font-size:12px; font-weight:600;">${talla}${genero}${color}: ${cantidad}</span>`;
                    }).join(' ')
                    : '<span style="font-size:12px; color:#6b7280;">Sin detalle por talla</span>';

                return `
                    <div style="border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 12px; background: ${index === 0 ? '#f8fafc' : 'white'};">
                        <div style="display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
                            <div>
                                <div style="font-size:14px; font-weight:700; color:#111827;">Recibo ${movimiento.tipo_recibo || 'COSTURA'} #${movimiento.numero_recibo || 'S/N'}</div>
                                <div style="font-size:12px; color:#6b7280;">Usuario: ${movimiento.usuario || `#${movimiento.usuario_id || 'N/A'}`}</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:13px; font-weight:700; color:#0f766e;">Cantidad: ${movimiento.cantidad_entregada}</div>
                                <div style="font-size:12px; color:#6b7280;">${movimiento.fecha_entrega || 'Sin fecha'}</div>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            ${detalleHtml}
                        </div>
                    </div>
                `;
            }).join('');
        } catch (err) {
            error.style.display = 'block';
            error.textContent = err.message || 'No se pudo cargar el historial de entregas';
        } finally {
            loading.style.display = 'none';
        }
    };

    /**
     * Toggle del estado de entrega de una prenda
     * @param {HTMLElement} button - Botón que se clickeó
     * @param {number} prendaId - ID de la prenda
     */
    window.marcarTodasPrendasEntregadas = async function(pedidoId, options = {}) {
        const pedidoIdNumerico = Number(pedidoId || window.selectorRecibosState?.pedidoId || 0);
        if (!pedidoIdNumerico) {
            throw new Error('No se pudo determinar el pedido para la entrega masiva.');
        }

        const numeroPedido = options?.numeroPedido || window.selectorRecibosState?.numeroPedido || pedidoIdNumerico;
        const btnMasivo = document.getElementById('btn-entregar-todas-prendas');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        let prendasActivas = [];
        let prendasPendientes = [];

        try {
            const datosResponse = await fetch(`/pedidos-public/${pedidoIdNumerico}/recibos-datos`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!datosResponse.ok) {
                throw new Error(`No se pudo obtener el detalle del pedido (HTTP ${datosResponse.status}).`);
            }

            const datosResult = await datosResponse.json();
            const datos = datosResult.data || datosResult || {};
            const pedidoEstado = String(datos.estado || window.selectorRecibosState?.pedidoEstado || '').toUpperCase();

            if (pedidoEstado === 'PENDIENTE_SUPERVISOR') {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Pedido sin aprobar',
                        text: 'No puedes entregar prendas hasta que el pedido sea aprobado por supervisor.',
                        confirmButtonColor: '#f59e0b',
                    });
                } else {
                    alert('No puedes entregar prendas hasta que el pedido sea aprobado por supervisor.');
                }
                return;
            }

            prendasActivas = (Array.isArray(datos.prendas) ? datos.prendas : [])
                .filter((prenda) => !prenda?.deleted_at && Number(prenda?.id || 0) > 0);
            prendasPendientes = prendasActivas.filter((prenda) => resolverEstadoEntregaPrenda(prenda) !== 'completo');

            if (prendasPendientes.length === 0) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Todo al dia',
                        text: 'Todas las prendas ya estaban marcadas como entregadas.',
                        confirmButtonColor: '#10b981',
                    });
                } else {
                    alert('Todas las prendas ya estaban marcadas como entregadas.');
                }
                actualizarPanelEntregaMasiva(prendasActivas);
                return;
            }
        } catch (error) {
            throw new Error(error?.message || 'No se pudo preparar la entrega masiva.');
        }

        const confirmarEntrega = typeof Swal !== 'undefined'
            ? await Swal.fire({
                icon: 'question',
                title: `Entrega masiva #${numeroPedido}`,
                text: `Se marcaran ${prendasPendientes.length} prenda(s) como entregadas. Deseas continuar?`,
                showCancelButton: true,
                confirmButtonText: 'Si, entregar todas',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d9488',
                reverseButtons: true,
            }).then((result) => result.isConfirmed)
            : confirm(`Se marcaran ${prendasPendientes.length} prenda(s) como entregadas en el pedido #${numeroPedido}. Deseas continuar?`);

        if (!confirmarEntrega) {
            return;
        }

        if (btnMasivo) {
            btnMasivo.disabled = true;
        }

        try {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Procesando entrega masiva',
                    html: `<p>Actualizando ${prendasPendientes.length} prenda(s)...</p>`,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => Swal.showLoading(),
                });
            }

            let procesadasOk = 0;
            const errores = [];

            for (const prenda of prendasPendientes) {
                try {
                    const response = await fetch(`/api/prendas-entregas/${prenda.id}/toggle`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            entregado: true,
                            modo: 'completo',
                        }),
                    });

                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || !result?.success) {
                        throw new Error(result?.message || `HTTP ${response.status}`);
                    }

                    procesadasOk += 1;
                } catch (error) {
                    errores.push(`Prenda ${prenda?.nombre || prenda?.id || 'N/A'}: ${error?.message || 'Error desconocido'}`);
                }
            }

            if (window.selectorRecibosState?.pedidoId && Number(window.selectorRecibosState.pedidoId) === pedidoIdNumerico) {
                await cargarDatosRecibos(pedidoIdNumerico);
            } else {
                actualizarPanelEntregaMasiva(prendasActivas);
            }

            if (typeof Swal !== 'undefined') {
                Swal.close();
            }

            const resumen = `Entregadas: ${procesadasOk}/${prendasPendientes.length}`;
            if (typeof Swal !== 'undefined') {
                if (errores.length > 0) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Entrega masiva parcial',
                        html: `<div style="text-align:left;"><p style="margin-bottom:8px;">${resumen}</p><p style="margin:0; font-size:12px; color:#6b7280;">${errores.slice(0, 4).join('<br>')}</p></div>`,
                        confirmButtonColor: '#f59e0b',
                    });
                } else {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Entrega masiva completada',
                        text: resumen,
                        confirmButtonColor: '#10b981',
                    });
                }
            } else if (errores.length > 0) {
                alert(`${resumen}\n\nErrores:\n${errores.slice(0, 4).join('\n')}`);
            } else {
                alert(`${resumen}`);
            }
        } finally {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            if (btnMasivo) {
                btnMasivo.disabled = false;
            }
        }
    };

    window.toggleEntregarPrenda = async function(button, prendaId) {
        const header = button.closest('.prenda-header');
        const buttonText = button.querySelector('span');
        const icon = button.querySelector('i');
        const estadoEntrega = String(header?.dataset?.estadoEntrega || 'pendiente').toLowerCase();
        const isEntregada = estadoEntrega === 'completo';
        const nuevoEstado = !isEntregada;
        let payload = {
            entregado: nuevoEstado
        };
        
        // Deshabilitar botón mientras se procesa
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
        
        try {
            if (nuevoEstado) {
                const decision = await abrirDecisionEntregaPrenda(prendaId);
                if (!decision) {
                    return;
                }

                payload = {
                    ...payload,
                    ...decision,
                };
            }

            // Guardar en base de datos
            const response = await fetch(`/api/prendas-entregas/${prendaId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.message || 'Error al actualizar estado');
            }
            
            // Actualizar UI solo si la petición fue exitosa
            if (nuevoEstado && result.data?.estado_entrega === 'parcial') {
                header.classList.remove('entregada');
                header.dataset.estadoEntrega = 'parcial';
                button.classList.remove('entregado');
                buttonText.textContent = 'Parcial';
                button.style.background = '#2563eb';
                icon.className = 'fas fa-layer-group';

                console.log(`[Prenda ${prendaId}] Estado cambiado a: PARCIAL`, result.data);
                mostrarMensajeExito(result.message || 'Entrega parcial registrada');
            } else if (nuevoEstado) {
                // Cambiar a entregada (en UI dejamos opcion de deshacer)
                header.classList.add('entregada');
                header.dataset.estadoEntrega = 'completo';
                button.classList.add('entregado');
                buttonText.textContent = 'Deshacer';
                button.style.background = '#f59e0b';
                icon.className = 'fas fa-rotate-left';
                
                console.log(`[Prenda ${prendaId}] Estado cambiado a: ENTREGADA`, result.data);
                mostrarMensajeExito(result.message || 'Prenda marcada como entregada');
            } else {
                // Cambiar a NO entregada
                header.classList.remove('entregada');
                header.dataset.estadoEntrega = 'pendiente';
                button.classList.remove('entregado');
                buttonText.textContent = 'Entregar';
                button.style.background = '#10b981';
                icon.className = 'fas fa-check-circle';
                
                console.log(`[Prenda ${prendaId}] Estado cambiado a: NO ENTREGADA`);
                mostrarMensajeExito(result.message || 'Prenda marcada como no entregada');
            }

            // Re-sincronizar desde backend para asegurar estado real del boton/areas.
            if (window.selectorRecibosState?.pedidoId) {
                await cargarDatosRecibos(window.selectorRecibosState.pedidoId);
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
                // Usar la ruta que incluye los consecutivos (obtenerDetalleCompleto)
                apiUrl = `/pedidos-public/${pedidoId}/recibos-datos`;
            }
            
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();
            const datos = result.data || result;
            window.selectorRecibosState.prendas = datos.prendas || [];
            window.selectorRecibosState.pedidoEstado = datos.estado || window.selectorRecibosState.pedidoEstado || null;

            // Actualizar número de pedido
            const numeroPedido = datos.numero || datos.numero_pedido || datos.id || pedidoId;
            window.selectorRecibosState.numeroPedido = numeroPedido;
            document.getElementById('selector-pedido-numero').textContent = `#${numeroPedido}`;

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

                // Frenar el click original para no duplicar la apertura:
                // el click natural ya terminaría ejecutando el onclick del padre.
                e.preventDefault();
                e.stopPropagation();

                // Simular un único click controlado en el padre.
                procesoItem.click();
            } else {
                console.warn('[PROCESO-NAME-CLICK] .proceso-item no encontrado como padre');
            }
        }
    }, true); // Usar capture phase para mayor prioridad

</script>

<!-- Modal de edición de prenda antes de generar recibo -->
<div id="modal-editar-prenda-recibo" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;">
    <div style="background: white; border-radius: 12px; box-shadow: 0 20px 25px rgba(0,0,0,0.15); width: 90%; max-width: 500px; padding: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: #1f2937;">Revisar prenda antes de generar recibo</h3>
            <button onclick="cancelarEditarPrendaRecibo()" style="background: none; border: none; font-size: 24px; color: #9ca3af; cursor: pointer; padding: 0;">&times;</button>
        </div>
        <p id="modal-editar-prenda-nombre" style="margin: 0 0 20px 0; color: #6b7280; font-size: 14px;"></p>

        <div style="margin-bottom: 16px;">
            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 13px;">Descripción</label>
            <textarea id="modal-editar-descripcion" placeholder="Ingresa la descripción de la prenda..." style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 14px; resize: vertical; min-height: 80px; box-sizing: border-box;" maxlength="1000"></textarea>
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 13px;">¿De dónde viene?</label>
            <select id="modal-editar-de-bodega" style="width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 14px; background-color: white; cursor: pointer;">
                <option value="1">De bodega</option>
                <option value="0">Confección</option>
            </select>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <button onclick="cancelarEditarPrendaRecibo()" style="padding: 10px 20px; border: 1px solid #d1d5db; background: white; color: #374151; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Cancelar</button>
            <button onclick="guardarEditarPrendaRecibo()" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">Guardar y continuar</button>
        </div>
    </div>
</div>

<!-- Incluir modal de recibos parciales por talla -->
@include('components.modals.recibos-parcial-por-talla')

