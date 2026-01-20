/**
 * CONSTANTES DE HTML - GESTIÓN DE ÍTEMS PEDIDO
 * 
 * Contiene plantillas HTML reutilizables para:
 * - Modal de éxito de creación de pedido
 * - Estructura base para vistas previas
 */

// ========== MODAL DE ÉXITO - CREACIÓN DE PEDIDO ==========
const MODAL_EXITO_PEDIDO_HTML = `
    <div id="modalExitoPedido" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
    ">
        <!-- Backdrop -->
        <div id="modalBackdrop" style="
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1;
        "></div>
        
        <!-- Modal Content -->
        <div style="
            position: relative;
            z-index: 2;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
        ">
            <!-- Header -->
            <div style="
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            ">
                <span class="material-symbols-rounded" style="font-size: 1.5rem;">check_circle</span>
                <h5 style="margin: 0; font-size: 1.25rem; font-weight: 600;">¡Pedido Creado Exitosamente!</h5>
            </div>
            
            <!-- Body -->
            <div style="
                padding: 2rem;
                text-align: center;
                color: #374151;
            ">
                <p style="font-size: 1.1rem; margin: 0;">
                    El pedido ha sido creado correctamente y está listo para procesarse.
                </p>
            </div>
            
            <!-- Footer -->
            <div style="
                padding: 1.5rem;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
                text-align: center;
            ">
                <button id="btnVolverAPedidos" type="button" style="
                    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
                    color: white;
                    border: none;
                    padding: 0.75rem 1.5rem;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin: 0 auto;
                    transition: transform 0.2s;
                " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <span class="material-symbols-rounded" style="font-size: 1rem;">arrow_back</span>
                    Volver a Pedidos
                </button>
            </div>
        </div>
    </div>
`;

// ========== ESTRUCTURA INFORMACIÓN DEL PEDIDO - VISTA PREVIA ==========
// Nota: Esta es una plantilla dinámica que se completa en el código
const VISTA_PREVIA_INFO_PEDIDO_TEMPLATE = `
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
        <div>
            <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Cliente</p>
            <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">{{cliente}}</p>
        </div>
        <div>
            <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Asesora</p>
            <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">{{asesora}}</p>
        </div>
        <div>
            <p style="margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Forma de Pago</p>
            <p style="margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;">{{forma}}</p>
        </div>
    </div>
`;

// ========== ITEM INDIVIDUAL VISTA PREVIA ==========
const VISTA_PREVIA_ITEM_TEMPLATE = `
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
        <div>
            <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 1.15rem;">{{indice}}. {{nombre}}</h4>
            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                <strong>Origen:</strong> {{origen}}
            </p>
            {{procesos}}
            <p style="margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;">
                <strong>Tallas:</strong> {{tallas}}
            </p>
        </div>
        <div style="text-align: right;">
            <div style="background: #fef3c7; color: #92400e; padding: 0.75rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 1.1rem;">
                 {{unidades}} unidades
            </div>
        </div>
    </div>
`;

// ========== NOTIFICACIÓN FLOTANTE ==========
const NOTIFICACION_TEMPLATE = {
    error: 'alert-danger',
    success: 'alert-success',
    info: 'alert-info'
};

// ========== TARJETA DE PROCESO (RENDERIZADO DIRECTO) ==========
const TARJETA_PROCESO_TEMPLATE = `
    <div style="
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    ">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            {{contenido}}
        </div>
    </div>
`;

// ========== CONTENEDOR IMAGEN PROCESO (PARA EDICIÓN) ==========
const IMAGEN_PROCESO_EDICION_TEMPLATE = `
    <img src="{{imgUrl}}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
    <button type="button" onclick="eliminarImagenProceso({{indice}}); event.stopPropagation();" 
        style="position: absolute; top: 4px; right: 4px; background: #dc2626; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
        ×
    </button>
`;

// ========== ESTILOS PARA COMPONENTES ==========
const ESTILOS_COMPONENTES = {
    contenedorTarjetas: 'display: grid; grid-template-columns: 1fr; gap: 1rem;',
    tarjeta: 'background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;',
    tarjetaContenido: 'display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;',
    etiqueta: 'font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;',
    valor: 'color: #374151; font-size: 0.875rem;',
    generoLabel: {
        dama: 'color: #be185d; margin-right: 0.5rem;',
        caballero: 'color: #1d4ed8; margin-right: 0.5rem;'
    },
    tallasBadge: {
        dama: 'background: #fce7f3; color: #be185d; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;',
        damaFuerte: 'background: #be185d; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;',
        caballero: 'background: #dbeafe; color: #1d4ed8; padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem; display: inline-flex; align-items: center; gap: 0.25rem;',
        caballeroFuerte: 'background: #1d4ed8; color: white; padding: 0.1rem 0.4rem; border-radius: 3px; font-weight: 700; font-size: 0.75rem;'
    }
};

// ========== CONTENEDOR VACÍO - PROCESOS ==========
const PROCESOS_VACIO_HTML = `
    <div style="text-align: center; padding: 1.5rem; color: #9ca3af;">
        <span class="material-symbols-rounded" style="font-size: 2rem; opacity: 0.3; display: block;">info</span>
        No hay procesos configurados
    </div>
`;

// ========== TARJETA PROCESO - HEADER ==========
const TARJETA_PROCESO_HEADER = `
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-size: 1.5rem;">⚙️</span>
            <strong style="color: #111827; font-size: 1rem;">Proceso Personalizado</strong>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" onclick="window.editarProcesoEdicion('{{tipo}}')" 
                style="background: #f3f4f6; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                title="Editar proceso">
                <i class="fas fa-edit" style="font-size: 1rem; color: #6b7280;"></i>
            </button>
            <button type="button" onclick="window.eliminarTarjetaProceso('{{tipo}}')" 
                style="background: #fee2e2; border: none; padding: 0.5rem; border-radius: 4px; cursor: pointer; display: flex; align-items: center;" 
                title="Eliminar proceso">
                <i class="fas fa-trash-alt" style="font-size: 1rem; color: #dc2626;"></i>
            </button>
        </div>
    </div>
`;

// ========== TARJETA PROCESO - CONTENEDOR ==========
const TARJETA_PROCESO_CONTENEDOR = `
    <div class="tarjeta-proceso" data-tipo="{{tipo}}" style="
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
    ">
        {{header}}
        <div style="display: grid; gap: 0.75rem;">
            {{contenido}}
        </div>
    </div>
`;

// ========== SECCIÓN IMAGEN PROCESO (RENDERIZADO) ==========
const PROCESO_SECCION_IMAGENES = `
    <div>
        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">IMÁGENES</div>
        <div style="position: relative; display: inline-block;">
            <img src="{{imgUrl}}" 
                style="width: 100px; height: 100px; object-fit: cover; border-radius: 6px; border: 2px solid #e5e7eb;" 
                alt="Imagen del proceso">
            {{badge}}
        </div>
    </div>
`;

// ========== BADGE IMÁGENES PROCESO ==========
const PROCESO_BADGE_IMAGENES = `
    <span style="position: absolute; bottom: 4px; right: 4px; background: rgba(0, 0, 0, 0.75); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">
        +{{cantidad}}
    </span>
`;

// ========== SECCIÓN UBICACIONES PROCESO ==========
const PROCESO_SECCION_UBICACIONES = `
    <div>
        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">UBICACIONES</div>
        <div style="color: #374151; font-size: 0.875rem;">{{ubicaciones}}</div>
    </div>
`;

// ========== SECCIÓN TALLAS PROCESO ==========
const PROCESO_SECCION_TALLAS = `
    <div>
        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">TALLAS ({{total}})</div>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.875rem;">
            {{tallas}}
        </div>
    </div>
`;

// ========== SECCIÓN OBSERVACIONES PROCESO ==========
const PROCESO_SECCION_OBSERVACIONES = `
    <div>
        <div style="font-size: 0.75rem; font-weight: 600; color: #6b7280; margin-bottom: 0.25rem;">OBSERVACIONES</div>
        <div style="color: #374151; font-size: 0.875rem; font-style: italic;">{{observaciones}}</div>
    </div>
`;

// ========== FOTOS PREVIEW - ESTADO VACÍO ==========
const FOTOS_PREVIEW_VACIO_HTML = `
    <div class="foto-preview-content">
        <div class="material-symbols-rounded">add_photo_alternate</div>
        <div class="foto-preview-text">Agregar</div>
    </div>
`;

// ========== IMAGEN PREVIEW EN MODAL ==========
const IMAGEN_PREVIEW_TEMPLATE = `<img src="{{url}}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;" alt="Preview">`;

// ========== BOTÓN GUARDAR CON ICONO ==========
const BTN_GUARDAR_CAMBIOS_HTML = `<span class="material-symbols-rounded">save</span>Guardar cambios`;

// ========== EMPTY STATE - SIN ITEMS ==========
const EMPTY_STATE_ITEMS_HTML = `
    <div class="empty-state" style="text-align: center; padding: 2rem; color: #9ca3af;">
        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
        <p>No hay ítems agregados. Selecciona un tipo de pedido para agregar nuevos ítems.</p>
    </div>
`;

// ========== INFORMACIÓN DEL PEDIDO - VISTA PREVIA ==========
const VISTA_PREVIA_INFO_ESTRUCTURA = `
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem;">
        <div>
            <p style="{{labelStyle}}">Cliente</p>
            <p style="{{valueStyle}}">{{cliente}}</p>
        </div>
        <div>
            <p style="{{labelStyle}}">Asesora</p>
            <p style="{{valueStyle}}">{{asesora}}</p>
        </div>
        <div>
            <p style="{{labelStyle}}">Forma de Pago</p>
            <p style="{{valueStyle}}">{{forma}}</p>
        </div>
    </div>
`;

// ========== ITEM INDIVIDUAL - VISTA PREVIA ==========
const VISTA_PREVIA_ITEM_ESTRUCTURA = `
    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
        <div>
            <h4 style="{{titleStyle}}">{{numero}}. {{nombre}}</h4>
            <p style="{{metadataStyle}}">
                <strong>Origen:</strong> {{origen}}
            </p>
            {{procesos}}
            <p style="{{metadataStyle}}">
                <strong>Tallas:</strong> {{tallas}}
            </p>
        </div>
        <div style="text-align: right;">
            <div style="{{unidadesStyle}}">
                 {{cantidad}} unidades
            </div>
        </div>
    </div>
`;

// ========== PROCESOS HTML CONDICIONAL ==========
const PROCESOS_ITEM_TEMPLATE = `
    <p style="{{metadataStyle}}">
        <strong>Procesos:</strong> {{procesos}}
    </p>
`;

// ========== ESTILOS CSS - NOTIFICACIONES ==========
const NOTIFICACION_STYLES = {
    contenedor: 'position: fixed; top: 1rem; right: 1rem; padding: 1rem; border-radius: 6px; z-index: 10000;',
    animacionEntrada: 'slideIn 0.3s ease-out',
    animacionSalida: 'slideOut 0.3s ease-out'
};

// ========== ESTILOS CSS - VISTA PREVIA MODAL ==========
const VISTA_PREVIA_MODAL_STYLES = {
    modal: 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;',
    contenedor: 'background: white; border-radius: 12px; width: 90%; max-width: 1000px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);',
    header: 'background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; padding: 2rem; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;',
    titulo: 'margin: 0; font-size: 1.5rem;',
    btnCerrar: 'background: rgba(255,255,255,0.2); color: white; border: none; border-radius: 6px; padding: 0.75rem 1.25rem; cursor: pointer; font-size: 1.5rem; font-weight: bold;',
    contenido: 'padding: 2rem;',
    infoPedido: 'background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border-left: 4px solid #0066cc;',
    infoPedidoLabel: 'margin: 0 0 0.25rem 0; color: #6b7280; font-size: 0.875rem; font-weight: 600; text-transform: uppercase;',
    infoPedidoValue: 'margin: 0; color: #1f2937; font-size: 1.1rem; font-weight: 700;',
    tituloItems: 'color: #1f2937; font-size: 1.25rem; margin: 0 0 1.5rem 0; padding-bottom: 0.75rem; border-bottom: 2px solid #0066cc;',
    itemsContainer: 'display: grid; grid-template-columns: 1fr; gap: 1rem;',
    itemDiv: 'background: white; border: 2px solid #e5e7eb; border-radius: 8px; padding: 1.5rem;',
    itemTitulo: 'margin: 0 0 0.5rem 0; color: #1e40af; font-size: 1.15rem;',
    itemMetadata: 'margin: 0.25rem 0; color: #6b7280; font-size: 0.95rem;',
    unidadesBox: 'background: #fef3c7; color: #92400e; padding: 0.75rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 1.1rem;',
    footer: 'padding: 2rem; display: flex; justify-content: space-between; gap: 1rem; border-top: 1px solid #e5e7eb;',
    btnAccion: 'color: white; border: none; border-radius: 6px; padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; font-size: 1rem;',
    btnImpreso: 'background: #6366f1;',
    btnContinuar: 'background: #10b981;',
    itemVacio: 'color: #6b7280; text-align: center; padding: 2rem;'
};
