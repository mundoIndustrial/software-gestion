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
        justify-content: flex-start;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        transition: background 0.2s;
        user-select: none;
        position: relative;
    }

    .prenda-header > div:first-child {
        min-width: 220px;
        margin-right: auto;
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

    .prenda-header.devuelta {
        background: #cbd5e1 !important;
        border-left: 5px solid #475569;
        box-shadow: inset 0 0 0 1px #94a3b8;
    }

    .prenda-header.devuelta.expanded {
        background: #bfc9d8 !important;
        border-bottom: 2px solid #475569;
    }

    .prenda-header.devuelta .prenda-title {
        color: #111827;
        font-weight: 700;
    }

    .prenda-header.devuelta .prenda-subtitle {
        color: #334155;
        font-weight: 600;
    }

    .btn-entregar-prenda {
        background: #10b981;
        color: white;
        border: none;
        padding: 0;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0;
        margin-left: 0;
        width: 34px;
        height: 34px;
        min-height: 34px;
        justify-content: center;
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
        padding: 0;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0;
        margin-left: 0;
        width: 34px;
        height: 34px;
        min-height: 34px;
        justify-content: center;
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

    .badge-prenda-devuelta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 8px;
        padding: 2px 8px;
        border-radius: 999px;
        background: #b91c1c;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2px;
        vertical-align: middle;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .prenda-chevron {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
        color: #3b82f6;
        margin-left: auto;
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

    .proceso-estado.anulado {
        background: #6b7280; /* Gris */
    }

    .proceso-estado.devuelto_asesor {
        background: #6b7280;
    }

    .proceso-estado.anulado s {
        text-decoration: line-through;
        text-decoration-color: currentColor;
        text-decoration-thickness: 2px;
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

    .btn-devolver-asesora {
        background: #b91c1c;
        color: white;
        border: none;
        padding: 0;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0;
        min-height: 34px;
        width: 34px;
        height: 34px;
        justify-content: center;
    }

    .btn-devolver-asesora:hover {
        background: #991b1b;
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
        min-width: 150px;
    }

    .select-recibo-parcial {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        width: 100%;
        background: #8b5cf6;
        color: white;
        border: none;
        padding: 7px 34px 7px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 34px;
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

    @media (max-width: 768px) {
        .prenda-header {
            padding: 12px;
            gap: 6px;
        }

        .prenda-header > div:first-child {
            min-width: 100%;
            margin-right: 0;
        }

        .btn-entregar-prenda,
        .btn-ver-entregas-prenda,
        .btn-devolver-asesora {
            width: 30px;
            height: 30px;
            min-height: 30px;
            font-size: 12px;
        }

        .recibo-parcial-select-wrapper {
            min-width: 135px;
        }

        .select-recibo-parcial {
            font-size: 11px;
            min-height: 30px;
        }
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

            // Permite generar recibos por talla para el tipo seleccionado (costura/reflectivo).
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

            window.generarReciboBodega(prendaId, pedidoId, tipoProceso);
        } finally {
            if (selectEl) {
                selectEl.value = '';
            }
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

        const ocultarBotonEntregar = window.location.pathname.includes('/registros') || window.location.pathname.includes('/visualizador-logo/');
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
            const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/');
            
            if (!esVistaVisualizadorLogo) {
                //  RECIBO BASE - SOLO EN OTRAS VISTAS
                const reciboCosturaActual = prenda?.recibos?.COSTURA || prenda?.consecutivos?.COSTURA || null;
                const reciboBaseActual = reciboCosturaActual;
                
                // Determinar el estado correcto usando el campo 'activo' de la BD
                let estadoRecibo = 'PENDIENTE';
                let numeroRecibo = null;
                let activoValue = 0;

                console.log('[DEBUG RECIBO BASE] reciboBaseActual:', reciboBaseActual);

                    if (reciboBaseActual) {
                        // Nuevo formato: objeto con datos completos
                        if (typeof reciboBaseActual === 'object' && reciboBaseActual.activo !== undefined) {
                            const estadoNormalizado = String(reciboBaseActual.estado || '').toUpperCase().trim();
                            const estaAnulado = estadoNormalizado === 'ANULADO' || estadoNormalizado === 'ANULADA';
                            const estaDevueltoAsesor = estadoNormalizado === 'DEVUELTO_ASESOR';
                            console.log('[DEBUG RECIBO BASE] estadoNormalizado:', estadoNormalizado, 'activo:', reciboBaseActual.activo);
                            estadoRecibo = estaAnulado
                                ? 'ANULADO'
                                : (estaDevueltoAsesor ? 'DEVUELTO_ASESOR' : (reciboBaseActual.activo === 1 ? 'APROBADO' : 'PENDIENTE'));
                            console.log('[DEBUG RECIBO BASE] estadoRecibo calculado:', estadoRecibo);
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
                
                const esPrendaBodega = prenda.de_bodega == 1;
                if (!esPrendaBodega) {
                    const reciboBase = {
                        tipo: "costura",
                        nombre: "Costura",
                        estado: estadoRecibo,
                        es_base: true,
                        numero_recibo: numeroRecibo,
                        activo: activoValue, // Agregar campo activo para referencia
                        consecutivo_recibo_id: reciboBaseActual?.id || null,
                    };
                    console.log('[DEBUG] reciboBase agregado:', reciboBase);
                    recibos.push(reciboBase);
                }
            }

            //  PROCESOS ADICIONALES
            const procesos = prenda.procesos || [];
            procesos.forEach((proc) => {
                // Garantizar que tipo_proceso es STRING
                const tipoProceso = String(proc.tipo_proceso || proc.nombre_proceso || '');
                
                // CONDICIÓN ESPECIAL PARA VISUALIZADOR-LOGO: Solo mostrar procesos específicos
                const esVistaVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/');
                if (esVistaVisualizadorLogo) {
                    // Solo mostrar procesos con tipo_proceso_id: 2 (Bordado), 3 (Estampado), 4 (DTF), 5 (Sublimado)
                    const procesosPermitidos = [2, 3, 4, 5];
                    if (!proc.tipo_proceso_id || !procesosPermitidos.includes(proc.tipo_proceso_id)) {
                        return; // Skip este proceso
                    }
                }
                
                // CONDICIÓN ESPECIAL: No mostrar recibo de COSTURA en prendas de bodega en supervisor-pedidos y registros
                const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
                const esRegistros = window.location.pathname.includes('/registros');
                const esCostura = String(tipoProceso || '').toUpperCase() === 'COSTURA';
                if ((esSupervisorPedidos || esRegistros) && prenda.de_bodega == 1 && esCostura) {
                    return; // Skip costura en prendas de bodega
                }
                
                const estadoProceso = String(proc.estado || '').toUpperCase();
                console.log('[DEBUG PROCESO ADICIONAL] proc:', proc, 'estadoProceso:', estadoProceso);

                    recibos.push({
                        tipo: tipoProceso,
                        nombre: proc.nombre_proceso || tipoProceso,  // Usar nombre_proceso para anexos (ej: "BORDADO ANEXO 1")
                        estado: estadoProceso,
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
            const contadorAnexosPorTipo = {};
            
            parciales.forEach((parcial) => {
                // Determinar el estado del parcial
                console.log('[DEBUG PARCIAL] parcial:', parcial);
                const estadoNormalizadoParcial = String(parcial.estado || '').toUpperCase().trim();
                const parcialAnulado = estadoNormalizadoParcial === 'ANULADO' || estadoNormalizadoParcial === 'ANULADA';
                console.log('[DEBUG PARCIAL] estadoNormalizadoParcial:', estadoNormalizadoParcial, 'activo:', parcial.activo);
                const estadoParcial = parcialAnulado ? 'ANULADO' : (parcial.activo === 1 ? 'APROBADO' : (estadoNormalizadoParcial || 'PENDIENTE'));
                console.log('[DEBUG PARCIAL] estadoParcial calculado:', estadoParcial);
                const tipoParcial = String(parcial.tipo_recibo || '').toUpperCase() === 'COSTURA-BODEGA'
                    ? 'COSTURA'
                    : String(parcial.tipo_recibo || '');
                
                const esCostura = String(tipoParcial || '').toUpperCase() === 'COSTURA';
                
                const tipoParcialKey = String(tipoParcial || '').toUpperCase();
                contadorAnexosPorTipo[tipoParcialKey] = (contadorAnexosPorTipo[tipoParcialKey] || 0) + 1;
                const numeroAnexoPorTipo = contadorAnexosPorTipo[tipoParcialKey];

                // Crear nombre descriptivo para el parcial
                const nombreParcial = `${tipoParcial} ANEXO ${numeroAnexoPorTipo}`;
                
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

            // Mantener visible el recibo base (costura) aunque existan anexos,
            // para que no desaparezca del acordeón después de aprobar y siga disponible en UI.
            const recibosFiltered = recibos;

            const idAccordion = `prenda-${prenda.id || prendaIdx}`;
            const totalRecibos = recibosFiltered.length;
            const indicadorBodega = prenda.de_bodega == 1 ? ' <span style="color: #ef4444; font-size: 12px; font-weight: 600; margin-left: 8px;">(SE SACA DE BODEGA)</span>' : '';

            // Verificar si la prenda está entregada
            const estadoEntrega = resolverEstadoEntregaPrenda(prenda, recibos);
            const estaEntregada = estadoEntrega === 'completo';
            const entregaParcial = estadoEntrega === 'parcial';
            const prendaDevueltaDesdeLista = recibosFiltered.some((r) => String(r?.estado || '').toUpperCase() === 'DEVUELTO_ASESOR');
            const prendaDevueltaDesdeDatos = (() => {
                const estadoEsDevuelto = (valor) => String(valor || '').toUpperCase() === 'DEVUELTO_ASESOR';
                const recibosObj = prenda?.recibos || {};

                // Recibos base u otros objetos dentro de prenda.recibos
                for (const value of Object.values(recibosObj)) {
                    if (!value) continue;
                    if (Array.isArray(value)) continue;
                    if (typeof value === 'object' && estadoEsDevuelto(value.estado)) return true;
                }

                // Parciales dentro de prenda.recibos.parciales
                const parciales = Array.isArray(recibosObj.parciales) ? recibosObj.parciales : [];
                if (parciales.some((p) => estadoEsDevuelto(p?.estado))) return true;

                // Procesos adicionales de la prenda
                const procesos = Array.isArray(prenda?.procesos) ? prenda.procesos : [];
                if (procesos.some((p) => estadoEsDevuelto(p?.estado))) return true;

                return false;
            })();
            const prendaDevuelta = prendaDevueltaDesdeLista || prendaDevueltaDesdeDatos;
            const claseEntregada = estaEntregada ? 'entregada' : '';
            const claseDevuelta = prendaDevuelta ? 'devuelta' : '';
            const badgeDevueltaHtml = prendaDevuelta
                ? '<span class="badge-prenda-devuelta"><i class="fas fa-rotate-left"></i>Devuelta a asesora</span>'
                : '';
            const claseBotonEntregado = estaEntregada ? 'entregado' : '';
            const textoBoton = estaEntregada ? 'Deshacer' : (entregaParcial ? 'Parcial' : 'Entregar');
            const colorBoton = estaEntregada ? '#f59e0b' : (entregaParcial ? '#2563eb' : '#10b981');
            const iconoBoton = estaEntregada ? 'fa-rotate-left' : (entregaParcial ? 'fa-layer-group' : 'fa-check-circle');
            const ocultarBotonEntregar = window.location.pathname.includes('/registros') || window.location.pathname.includes('/visualizador-logo/');
            const rolesUsuarioPrenda = Array.isArray(window.selectorRecibosState?.usuarioRoles)
                ? window.selectorRecibosState.usuarioRoles.map((r) => String(r || '').toLowerCase())
                : [];
            const usuarioEsSupervisorPrenda = !!window.selectorRecibosState?.esSupervisorPedidos || !!window.selectorRecibosState?.esSupervisor;
            const esVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/');
            const puedeGestionarDevolucion = !esVisualizadorLogo && (usuarioEsSupervisorPrenda
                || rolesUsuarioPrenda.includes('supervisor_pedidos')
                || rolesUsuarioPrenda.includes('supervisor')
                || rolesUsuarioPrenda.includes('supervisor-admin')
                || rolesUsuarioPrenda.includes('supervisor_produccion')
                || rolesUsuarioPrenda.includes('lider_produccion')
                || rolesUsuarioPrenda.includes('admin')
                || window.location.pathname.includes('/supervisor-pedidos'));
            const pedidoEstadoPrenda = String(window.selectorRecibosState?.pedidoEstado || '').toUpperCase();
            const pedidoAprobadoParaDevolucion = pedidoEstadoPrenda !== 'PENDIENTE_SUPERVISOR';
            
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
                        <button class="btn-entregar-prenda ${claseBotonEntregado}" onclick="event.stopPropagation(); toggleEntregarPrenda(this, ${prenda.id || prendaIdx})" style="background: ${colorBoton};" title="${entregaParcial ? 'La prenda tiene entregas parciales registradas' : textoBoton}">
                            <i class="fas ${iconoBoton}"></i>
                        </button>`;

            const botonVerEntregasHtml = (ocultarBotonEntregar) ? '' : `
                        <button class="btn-ver-entregas-prenda" onclick="event.stopPropagation(); abrirHistorialEntregasPrenda(${prenda.id || prendaIdx})" title="Ver entregas parciales enviadas a despacho">
                            <i class="fas fa-history"></i>
                        </button>`;

            const botonDevolverPrendaHtml = (puedeGestionarDevolucion && pedidoAprobadoParaDevolucion) ? `
                        <button class="btn-devolver-asesora" onclick="event.stopPropagation(); devolverPrendaAAsesora(${prenda.id || prendaIdx})" title="Devolver prenda completa a asesora para revisión">
                            <i class="fas fa-arrow-rotate-left"></i>
                        </button>` : '';

            const botonGenerarReciboCosturaHtml = ocultarBotonEntregar ? '' : `
                        <div class="recibo-parcial-select-wrapper" onclick="event.stopPropagation();" title="Selecciona el tipo de recibo por talla">
                            <i class="fas fa-file-alt recibo-parcial-select-icon"></i>
                            <select class="select-recibo-parcial has-icon"
                                    onclick="event.stopPropagation();"
                                    onchange="event.stopPropagation(); handleReciboBodegaSelect(this, ${prenda.id || prendaIdx}, ${window.selectorRecibosState?.pedidoId})">
                                <option value="">Generar recibo</option>
                                <option value="costura">Costura</option>
                                <option value="reflectivo">Reflectivo</option>
                                <option value="bordado">Bordado</option>
                                <option value="estampado">Estampado</option>
                                <option value="dtf">DTF</option>
                                <option value="sublimado">Sublimado</option>
                            </select>
                            <span class="recibo-parcial-select-arrow">▼</span>
                        </div>`;

            html += `
                <div class="prenda-accordion">
                    <div class="prenda-header ${claseEntregada} ${claseDevuelta}" onclick="togglePrendaAccordion(this, '${idAccordion}')" data-prenda-id="${prenda.id || prendaIdx}" data-estado-entrega="${estadoEntrega}" data-estado-devolucion="${prendaDevuelta ? 'devuelta' : 'normal'}">
                        <div style="flex: 1;">
                            <p class="prenda-title">${prenda.nombre || 'Prenda sin nombre'}${indicadorBodega}${badgeDevueltaHtml}</p>
                            <p class="prenda-subtitle">${totalRecibos} recibo(s)</p>
                        </div>
                        ${botonEntregarHtml}
                        ${botonVerEntregasHtml}
                        ${botonDevolverPrendaHtml}
                        ${botonGenerarReciboCosturaHtml}
                        ${fechaEntregaHtml}
                        <div class="prenda-chevron">▼</div>
                    </div>
                    <div class="prenda-processes" id="${idAccordion}">
            `;

            if (totalRecibos === 0) {
                html += '<div style="padding: 16px; color: #9ca3af; text-align: center;">Sin recibos</div>';
            } else {
                recibosFiltered.forEach((recibo, reciboIdx) => {
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
                        // Recibo base (costura)
                        if (recibo.activo !== undefined) {
                            // Nuevo formato: usar campo 'activo' explícito
                            estaActivo = recibo.activo === 1;
                            puedeActivar = recibo.activo === 0 && String(recibo.estado).toUpperCase() !== 'ANULADO';
                            
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
                    const rolesUsuario = Array.isArray(window.selectorRecibosState?.usuarioRoles)
                        ? window.selectorRecibosState.usuarioRoles.map((r) => String(r || '').toLowerCase())
                        : [];
                    const usuarioEsSupervisor = !!window.selectorRecibosState?.esSupervisorPedidos || !!window.selectorRecibosState?.esSupervisor;
                    const esVisualizadorLogo = window.location.pathname.includes('/visualizador-logo/');
                    const puedeGestionarDevolucion = !esVisualizadorLogo && (usuarioEsSupervisor
                        || rolesUsuario.includes('supervisor_pedidos')
                        || rolesUsuario.includes('supervisor')
                        || rolesUsuario.includes('supervisor-admin')
                        || rolesUsuario.includes('supervisor_produccion')
                        || rolesUsuario.includes('lider_produccion')
                        || rolesUsuario.includes('admin')
                        || window.location.pathname.includes('/supervisor-pedidos'));
                    
                    // Variables básicas del recibo
                    const tipoStringLower = String(tipoString || '').toLowerCase();
                    
                    // Para recibos base (costura), no usar el botón general de activación
                    const esReciboBaseCostura = recibo.es_base && tipoStringLower === 'costura';
                    const puedeModificarRecibo = puedeActivar && puedeGestionarDevolucion && !esReciboBaseCostura;
                    
                    // Verificar si este proceso base tiene anexos/parciales creados
                    const tieneAnexos = !esParcial && recibos.some(r => r.es_parcial && String(r.tipo).toLowerCase() === tipoStringLower);
                    const puedeModificarReciboFinal = puedeModificarRecibo && !tieneAnexos;
                    
                    const puedeDesactivarRecibo = estaActivo && puedeGestionarDevolucion;
                    
                    // Botón Anular aparece cuando estado es APROBADO (independientemente de numero_recibo)
                    const puedeAnularRecibo = recibo.estado === 'APROBADO' && puedeGestionarDevolucion;
                    const reciboId = Number(recibo.consecutivo_recibo_id || 0);
                    const puedeDevolverAAsesora = puedeGestionarDevolucion
                        && reciboId > 0
                        && String(recibo.estado || '').toUpperCase() !== 'ANULADO';
                    
                    const reciboClass = estaActivo ? 'recibo-activo' : '';
                    
                    // Verificar si el usuario puede crear recibos por talla (supervisor_pedidos o admin)
                    const esSupervisorRecibos = window.selectorRecibosState?.usuarioRoles?.includes('supervisor_pedidos') ||
                                               window.selectorRecibosState?.esSupervisorPedidos === 'true';

                    // No permitir recibo por talla en Costura ni si el proceso ya está APROBADO
                    const puedeCrearPorTalla = esSupervisorRecibos && tipoStringLower !== 'costura' && recibo.estado !== 'APROBADO';

                    const pedidoEstado = window.selectorRecibosState?.pedidoEstado;
                    const pedidoYaAprobado = pedidoEstado && String(pedidoEstado).toUpperCase() !== 'PENDIENTE_SUPERVISOR';

                    const puedeActivarBaseCostura = recibo.es_base && tipoStringLower === 'costura' && puedeActivar && pedidoYaAprobado && puedeGestionarDevolucion;
                    
                    // DEBUG: Mostrar decisión de visibilidad del botón
                    console.log(`🔘 Prenda ${prenda.id}: esSupervisorRecibos=${esSupervisorRecibos}, esSupervisorPedidos=${window.selectorRecibosState?.esSupervisorPedidos}`);
                    
                    const pedidoId = window.selectorRecibosState?.pedidoId;

                    // Construir contenido del estado con fecha si está anulado
                    let estadoContent = estadoLabel;
                    if (String(recibo.estado).toUpperCase() === 'ANULADO' && recibo.updated_at) {
                        const fechaAnulado = new Date(recibo.updated_at);
                        const fechaFormato = fechaAnulado.toLocaleDateString('es-ES', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                        const horaFormato = fechaAnulado.toLocaleTimeString('es-ES', {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        estadoContent = `<s>${estadoLabel}</s> ${fechaFormato} ${horaFormato}`;
                    }

                    html += `
                        <div class="proceso-item ${reciboClass}"
                             data-prenda-id="${prenda.id}"
                        data-tipo-string="${tipoString}"
                        data-es-parcial="${esParcial}"
                        data-pedido-parcial-id="${parcialId || ''}"
                        data-numero-recibo="${recibo.numero_recibo || ''}"
                        data-recibo-id="${recibo.consecutivo_recibo_id || recibo.id || recibo.recibo_id || ''}"
                        data-nombre-proceso="${(recibo.nombre || '').replace(/"/g, '&quot;')}"
                        onclick="seleccionarProcesoConDataAttributes(this)">
                            <div class="proceso-info">
                                <p class="proceso-name">${recibo.nombre}</p>
                                ${recibo.estado ? `<span class="proceso-estado ${estadoClass}">${estadoContent}</span>` : ''}
                                ${''}
                            </div>
                            <div class="proceso-acciones">
                                ${!esVisualizadorLogo ? `
                                <button class="btn-observacion-proceso"
                                        onclick="event.stopPropagation(); abrirModalObservacionReciboProceso(${prenda.id}, '${tipoString}', ${pedidoId})"
                                        title="Agregar observación">
                                    <i class="fas fa-comment-alt"></i>
                                    Observ.
                                </button>
                                ` : ''}
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
            const numeroRecibo = element.getAttribute('data-numero-recibo') || '';
            const reciboId = element.getAttribute('data-recibo-id') || '';
            const nombreProceso = element.getAttribute('data-nombre-proceso')?.replace(/&quot;/g, '"') || '';

            console.log(`[seleccionarProcesoConDataAttributes] Leyendo datos:`, {
                prendaId,
                tipoString,
                esParcial,
                pedidoParcialId,
                numeroRecibo,
                reciboId,
                nombreProceso
            });

            // Construir datosAdicionales
            const datosAdicionales = {
                es_parcial: esParcial,
                pedido_parcial_id: esParcial && pedidoParcialId ? parseInt(pedidoParcialId) : null,
                numero_recibo: numeroRecibo,
                recibo_id: reciboId,
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
            const targetConsecutivo = datosAdicionales?.numero_recibo || null;
            const targetReciboId = datosAdicionales?.recibo_id || null;
            window.openOrderDetailModalWithProcess(
                pedidoId,
                prendaId,
                tipoString,
                null,
                targetConsecutivo,
                targetReciboId,
                { esParcial: false }
            );
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

    window.devolverReciboAAsesora = async function(reciboId, prendaId, tipoProceso) {
        try {
            if (!reciboId) {
                throw new Error('No se encontró el recibo a devolver.');
            }

            if (typeof Swal === 'undefined') {
                const motivoFallback = prompt('Ingresa el motivo para devolver esta prenda a asesora:');
                if (!motivoFallback || motivoFallback.trim().length < 10) {
                    return;
                }
                await ejecutarDevolucionAAsesora(reciboId, motivoFallback.trim());
                return;
            }

            const result = await Swal.fire({
                title: 'Devolver a asesora',
                html: `<p style="margin:0 0 8px 0; color:#4b5563;">Prenda #${prendaId} - ${tipoProceso}</p>`,
                input: 'textarea',
                inputLabel: 'Motivo de la revisión',
                inputPlaceholder: 'Ej: Ajustar cantidades o especificaciones del proceso...',
                inputAttributes: {
                    maxlength: 500,
                    minlength: 10,
                },
                inputValidator: (value) => {
                    const motivo = String(value || '').trim();
                    if (!motivo) return 'Debes ingresar un motivo.';
                    if (motivo.length < 10) return 'El motivo debe tener al menos 10 caracteres.';
                    return null;
                },
                showCancelButton: true,
                confirmButtonText: 'Devolver',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#b91c1c',
                reverseButtons: true,
            });

            if (!result.isConfirmed) {
                return;
            }

            await ejecutarDevolucionAAsesora(reciboId, String(result.value || '').trim());
        } catch (error) {
            console.error('[devolverReciboAAsesora] Error:', error);
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'error',
                    title: 'No se pudo devolver',
                    text: error?.message || 'Error inesperado al devolver el recibo.',
                    confirmButtonColor: '#dc2626',
                });
            } else {
                alert(error?.message || 'Error inesperado al devolver el recibo.');
            }
        }
    };

    async function resolverReciboBaseBodega(prenda) {
        const recibosObj = prenda?.recibos || {};
        const candidatos = [];
        const tiposBase = new Set(['COSTURA-BODEGA']);

        for (const [clave, recibo] of Object.entries(recibosObj)) {
            if (!recibo || Array.isArray(recibo) || typeof recibo !== 'object') {
                continue;
            }

            const tipoNormalizado = String(recibo.tipo_recibo || recibo.tipo || clave || '').trim().toUpperCase();
            if (!tiposBase.has(tipoNormalizado)) {
                continue;
            }

            const reciboId = Number(recibo.consecutivo_recibo_id || recibo.id || recibo.recibo_id || 0);
            if (reciboId > 0) {
                candidatos.push(reciboId);
            }
        }

        const procesos = Array.isArray(prenda?.procesos) ? prenda.procesos : [];
        for (const proceso of procesos) {
            if (!proceso || typeof proceso !== 'object') {
                continue;
            }

            const tipoNormalizado = String(proceso.tipo_proceso || proceso.tipo_recibo || proceso.nombre_proceso || '').trim().toUpperCase();
            if (!tiposBase.has(tipoNormalizado)) {
                continue;
            }

            const reciboId = Number(proceso.consecutivo_recibo_id || proceso.id || proceso.recibo_id || 0);
            if (reciboId > 0) {
                candidatos.push(reciboId);
            }
        }

        const unico = Array.from(new Set(candidatos));
        if (unico.length > 0) {
            return unico[0];
        }

        const pedidoProduccionId = Number(window.selectorRecibosState?.pedidoId || 0);
        const prendaId = Number(prenda?.id || prenda?.prenda_id || 0);
        if (pedidoProduccionId <= 0 || prendaId <= 0) {
            throw new Error('No se pudo resolver la prenda de bodega para crear el costura-bodega.');
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/recibo-corte-bodega/resolver-base', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                pedido_produccion_id: pedidoProduccionId,
                prenda_id: prendaId,
                tipo_recibo: 'COSTURA-BODEGA',
            }),
        });

        let result = {};
        try {
            result = await response.json();
        } catch (e) {}

        if (!response.ok || !result?.success) {
            throw new Error(result?.message || `No se pudo crear/resolver el costura-bodega (HTTP ${response.status}).`);
        }

        const reciboId = Number(result?.data?.recibo_id || 0);
        if (reciboId <= 0) {
            throw new Error('No se pudo obtener el recibo costura-bodega.');
        }

        return reciboId;
    }

    function marcarPrendaComoDevueltaEnSelector(prendaId, reciboId = null) {
        const prenda = obtenerPrendaSelector(prendaId);
        if (!prenda) {
            return;
        }

        if (!prenda.recibos || typeof prenda.recibos !== 'object') {
            prenda.recibos = {};
        }

        const clavesBase = ['COSTURA-BODEGA', 'COSTURA'];
        let actualizo = false;

        for (const clave of clavesBase) {
            if (prenda.recibos[clave] && typeof prenda.recibos[clave] === 'object') {
                prenda.recibos[clave] = {
                    ...prenda.recibos[clave],
                    id: reciboId || prenda.recibos[clave].id || null,
                    consecutivo_recibo_id: reciboId || prenda.recibos[clave].consecutivo_recibo_id || null,
                    estado: 'DEVUELTO_ASESOR',
                    activo: prenda.recibos[clave].activo ?? 0,
                };
                actualizo = true;
            }
        }

        if (!actualizo) {
            prenda.recibos['COSTURA-BODEGA'] = {
                id: reciboId || null,
                consecutivo_recibo_id: reciboId || null,
                estado: 'DEVUELTO_ASESOR',
                activo: 0,
                tipo_recibo: 'COSTURA-BODEGA',
            };
        }
    }

    window.devolverPrendaAAsesora = async function(prendaId) {
        try {
            const prenda = obtenerPrendaSelector(prendaId);
            if (!prenda) {
                throw new Error('No se encontró la prenda en el selector.');
            }

            let reciboIds = [];
            if (prenda.de_bodega == 1) {
                const reciboBaseId = await resolverReciboBaseBodega(prenda);
                reciboIds = reciboBaseId > 0 ? [reciboBaseId] : [];
            } else {
                reciboIds = obtenerReciboIdsDePrenda(prendaId, prenda);
            }

            if (reciboIds.length === 0) {
                throw new Error('La prenda no tiene recibos asociados para devolver.');
            }

            if (typeof Swal !== 'undefined') {
                const confirmacionExcepcional = await Swal.fire({
                    icon: 'warning',
                    title: 'Acción para casos excepcionales',
                    html: `<p style="margin:0 0 8px 0; color:#4b5563;">Esta opción se usa solo en situaciones especiales.</p><p style="margin:0; color:#6b7280; font-size:13px;">La prenda será devuelta a la asesora para que la corrija y continúe el proceso.</p>`,
                    showCancelButton: true,
                    confirmButtonText: 'Sí, devolver prenda',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#b91c1c',
                    reverseButtons: true,
                });

                if (!confirmacionExcepcional.isConfirmed) {
                    return;
                }
            } else {
                const confirmar = confirm('Este botón es para casos excepcionales. La prenda será devuelta a la asesora para que la corrija. ¿Deseas continuar?');
                if (!confirmar) {
                    return;
                }
            }

            let motivo = '';
            if (typeof Swal === 'undefined') {
                const motivoFallback = prompt(`Ingresa el motivo para devolver la prenda "${prenda.nombre || prendaId}":`);
                if (!motivoFallback || motivoFallback.trim().length < 10) {
                    return;
                }
                motivo = motivoFallback.trim();
            } else {
                const result = await Swal.fire({
                    title: 'Devolver prenda completa',
                    html: `<p style="margin:0 0 8px 0; color:#4b5563;">${prenda.nombre || `Prenda #${prendaId}`}</p><p style="margin:0; color:#6b7280; font-size:13px;">Se devolverán ${reciboIds.length} recibo(s)/proceso(s) asociados.</p>`,
                    input: 'textarea',
                    inputLabel: 'Motivo de la revisión',
                    inputPlaceholder: 'Ej: Ajustar diseño, cantidades o especificaciones de la prenda...',
                    inputAttributes: {
                        maxlength: 500,
                        minlength: 10,
                    },
                    inputValidator: (value) => {
                        const text = String(value || '').trim();
                        if (!text) return 'Debes ingresar un motivo.';
                        if (text.length < 10) return 'El motivo debe tener al menos 10 caracteres.';
                        return null;
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Devolver prenda',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#b91c1c',
                    reverseButtons: true,
                });

                if (!result.isConfirmed) {
                    return;
                }

                motivo = String(result.value || '').trim();
            }

            let ok = 0;
            const errores = [];
            for (const reciboId of reciboIds) {
                try {
                    await ejecutarDevolucionAAsesora(reciboId, motivo, { silentSuccess: true });
                    ok += 1;
                } catch (error) {
                    errores.push(`Recibo #${reciboId}: ${error?.message || 'Error desconocido'}`);
                }
            }

            if (typeof Swal !== 'undefined') {
                if (errores.length > 0) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Devolución parcial',
                        html: `<div style="text-align:left;"><p style="margin-bottom:8px;">Se devolvieron ${ok} de ${reciboIds.length} recibos.</p><p style="margin:0; font-size:12px; color:#6b7280;">${errores.slice(0, 4).join('<br>')}</p></div>`,
                        confirmButtonColor: '#f59e0b',
                    });
                } else {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Prenda devuelta',
                        text: 'La prenda completa fue devuelta a asesora para revisión.',
                        confirmButtonColor: '#16a34a',
                    });
                }
            } else if (errores.length > 0) {
                alert(`Se devolvieron ${ok} de ${reciboIds.length} recibos.\n\n${errores.slice(0, 4).join('\n')}`);
            }

            if (prenda.de_bodega == 1) {
                marcarPrendaComoDevueltaEnSelector(prendaId, reciboIds[0] || null);
            }

            const pedidoId = Number(window.selectorRecibosState?.pedidoId || 0);
            if (pedidoId > 0) {
                await cargarDatosRecibos(pedidoId);
            }
        } catch (error) {
            console.error('[devolverPrendaAAsesora] Error:', error);
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'error',
                    title: 'No se pudo devolver la prenda',
                    text: error?.message || 'Error inesperado al devolver la prenda.',
                    confirmButtonColor: '#dc2626',
                });
            } else {
                alert(error?.message || 'Error inesperado al devolver la prenda.');
            }
        }
    };

    function obtenerReciboIdsDePrenda(prendaId, prenda = null) {
        const ids = new Set();
        const prendaIdNum = Number(prendaId || prenda?.id || 0);

        // Fuente principal (segura): solo recibos renderizados dentro del acordeón de esta prenda.
        const contenedor = document.getElementById(`prenda-${prendaIdNum}`);
        if (contenedor) {
            const items = contenedor.querySelectorAll('.proceso-item[data-prenda-id][data-recibo-id]');
            items.forEach((item) => {
                const itemPrendaId = Number(item.getAttribute('data-prenda-id') || 0);
                if (itemPrendaId !== prendaIdNum) {
                    return;
                }
                const reciboId = Number(item.getAttribute('data-recibo-id') || 0);
                if (reciboId > 0) {
                    ids.add(reciboId);
                }
            });
        }

        // Fallback mínimo: usar el recibo visible de la prenda actual si aún no hay items renderizados.
        if (ids.size === 0 && prenda && prenda.recibos) {
            const recibosObj = prenda.recibos;
            const candidatos = prenda.de_bodega == 1
                ? Object.values(recibosObj)
                : [recibosObj.COSTURA || null];

            candidatos.forEach((recibo) => {
                if (!recibo || typeof recibo !== 'object') {
                    return;
                }
                const rid = Number(recibo.consecutivo_recibo_id || recibo.id || recibo.recibo_id || 0);
                if (rid > 0) {
                    ids.add(rid);
                }
            });
        }

        return Array.from(ids);
    }

    async function ejecutarDevolucionAAsesora(reciboId, motivo, options = {}) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const enSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
        const endpoint = enSupervisorPedidos
            ? `/api/supervisor-pedidos/recibos/${reciboId}/pasar-revisar`
            : `/insumos/materiales/${reciboId}/pasar-revisar`;
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ motivo }),
        });

        let result = {};
        try {
            result = await response.json();
        } catch (e) {}

        if (!response.ok || !result?.success) {
            throw new Error(result?.message || `No se pudo devolver el recibo (HTTP ${response.status}).`);
        }

        if (!options?.silentSuccess && typeof Swal !== 'undefined') {
            await Swal.fire({
                icon: 'success',
                title: 'Prenda devuelta',
                text: result.message || 'Se devolvió correctamente a asesora para revisión.',
                confirmButtonColor: '#16a34a',
            });
        }

        const pedidoId = Number(window.selectorRecibosState?.pedidoId || 0);
        if (!options?.silentSuccess && pedidoId > 0) {
            await cargarDatosRecibos(pedidoId);
        }
    }

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
            
            // Determinar si es un recibo base (costura) o un proceso
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();
            const esReciboBase = tipoProcesoLower === 'costura';
            
            if (esReciboBase) {
                // Para recibos base (costura), usar el endpoint específico
                console.log('[ejecutarAnularRecibo] Anulando recibo base (costura)');
                
                const response = await fetch(`/api/supervisor-pedidos/ordenes/${pedidoId}/costura/${prendaId}/anular-recibo`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
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
                        'Accept': 'application/json',
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
            
            // Determinar si es un recibo base (costura) o un proceso
            const tipoProcesoLower = String(tipoProceso || '').toLowerCase();
            const esReciboBase = tipoProcesoLower === 'costura';
            
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

<!-- Incluir modal de recibos parciales por talla -->
@include('components.modals.recibos-parcial-por-talla')
