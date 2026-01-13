/**
 * Módulo: Gestión de Ítems del Pedido
 * Maneja la lista de ítems, renderizado y actualización
 */

// Array global de ítems del pedido
window.itemsPedido = [];

/**
 * Actualizar vista de ítems
 */
window.actualizarVistaItems = function() {
    const listaItems = document.getElementById('lista-items-pedido');
    const mensajeSinItems = document.getElementById('mensaje-sin-items');

    console.log('🔄 Actualizando vista de ítems. Total:', window.itemsPedido.length);
    console.log('📦 Ítems actuales:', window.itemsPedido);

    if (window.itemsPedido.length === 0) {
        console.log('  ℹ️ No hay ítems, mostrando mensaje');
        mensajeSinItems.style.display = 'block';
        listaItems.style.display = 'none';
    } else {
        console.log('  ✅ Hay ítems, renderizando lista');
        mensajeSinItems.style.display = 'none';
        listaItems.style.display = 'flex';
        renderizarItems();
    }
};

/**
 * Renderizar lista de ítems - Nuevo diseño con prendas y procesos unificados
 */
function renderizarItems() {
    const listaItems = document.getElementById('lista-items-pedido');

    console.log('🎨 Renderizando ítems. Total:', window.itemsPedido.length);
    listaItems.innerHTML = '';

    // Agrupar ítems por prenda (sin procesos) y procesos
    const prendas = window.itemsPedido.filter(item => !item.es_proceso);
    const procesos = window.itemsPedido.filter(item => item.es_proceso);

    let numeroItem = 1;

    // Renderizar prendas con sus procesos en un solo contenedor
    prendas.forEach((prenda, prendaIndex) => {
        const procesosDeEstaPrenda = procesos.filter(p => 
            p.prenda?.nombre === prenda.prenda?.nombre && 
            p.origen === prenda.origen
        );

        const contenedorDiv = crearContenedorPrendaConProcesos(prenda, procesosDeEstaPrenda, numeroItem, prendaIndex);
        listaItems.appendChild(contenedorDiv);
        numeroItem += 1 + procesosDeEstaPrenda.length;
    });

    console.log('🎨 Renderizado completado. Elementos en listaItems:', listaItems.children.length);
}

/**
 * Crear contenedor unificado de prenda con sus procesos
 */
function crearContenedorPrendaConProcesos(prenda, procesosDeEstaPrenda, numero, prendaIndex) {
    const contenedor = document.createElement('div');
    contenedor.style.cssText = 'background: white; border: 2px solid #e5e7eb; border-radius: 8px; margin-bottom: 1.5rem; overflow: hidden;';

    const origen = prenda.origen === 'bodega' ? 'BODEGA' : 'CONFECCIÓN';
    const origenColor = prenda.origen === 'bodega' ? '#fef3c7' : '#dcfce7';
    const origenTextColor = prenda.origen === 'bodega' ? '#92400e' : '#166534';

    // Construir variaciones
    let variacionesHTML = '';
    if (prenda.variaciones) {
        const vars = prenda.variaciones;
        variacionesHTML = `
            <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem;">
                <strong>Variaciones:</strong><br>
                ${vars.tela ? `• Tela: ${vars.tela}` : ''}
                ${vars.color ? `${vars.tela ? ' | ' : ''}Color: ${vars.color}` : ''}
                ${vars.referencia ? `${vars.tela || vars.color ? ' | ' : ''}Ref: ${vars.referencia}` : ''}
                ${vars.manga ? `<br>• Manga: ${vars.manga}` : ''}
                ${vars.broche ? `${vars.manga ? ' | ' : ''}Broche: ${vars.broche}` : ''}
                ${vars.bolsillos ? `${vars.manga || vars.broche ? ' | ' : ''}Bolsillos: ${vars.bolsillos}` : ''}
            </div>
        `;
    }

    // Construir tallas y cantidades
    let tallasHTML = '';
    if (prenda.tallas && Array.isArray(prenda.tallas)) {
        tallasHTML = prenda.tallas.map(t => `${t.talla}: ${t.cantidad}`).join(' | ');
    }

    // Header de la prenda
    const headerHTML = `
        <div style="padding: 1.25rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: start;">
            <div style="flex: 1;">
                <div style="font-weight: 700; color: #1e40af; font-size: 1.1rem; margin-bottom: 0.5rem;">
                    ${numero}. ${prenda.prenda?.nombre || 'Prenda'}
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                    ${prenda.tipo === 'cotizacion' ? `
                        <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                            📋 ${prenda.numero}
                        </span>
                    ` : ''}
                    <span style="padding: 0.25rem 0.75rem; background: ${origenColor}; color: ${origenTextColor}; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                        ${origen}
                    </span>
                    <span style="padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                        📦 ${prenda.prenda?.cantidad || 0} unidades
                    </span>
                </div>
                ${prenda.tipo === 'cotizacion' ? `
                    <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">
                        <strong>Cliente:</strong> ${prenda.cliente}
                    </div>
                ` : ''}
                ${variacionesHTML}
                <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.75rem;">
                    <strong>Tallas y Cantidades:</strong> ${tallasHTML || 'No especificadas'}
                </div>
            </div>
            <button type="button" onclick="window.eliminarItem(${prendaIndex})" style="padding: 0.5rem 0.75rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; margin-left: 1rem; height: fit-content;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                ✕
            </button>
        </div>
    `;

    contenedor.innerHTML = headerHTML;

    // Agregar procesos dentro del contenedor
    if (procesosDeEstaPrenda.length > 0) {
        const procesosContainer = document.createElement('div');
        procesosContainer.style.cssText = 'background: #fafbfc;';

        procesosDeEstaPrenda.forEach((proceso, procesoIndex) => {
            const procesoDiv = document.createElement('div');
            procesoDiv.style.cssText = 'padding: 1rem; border-top: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;';
            procesoDiv.onmouseover = () => procesoDiv.style.background = '#f3f4f6';
            procesoDiv.onmouseout = () => procesoDiv.style.background = 'transparent';

            const idCollapse = `proceso-${numero}-${procesoIndex}`;
            const procesosNombre = proceso.procesos?.join(', ') || 'Proceso';
            let tallasProcesoHTML = '';
            if (proceso.tallas && Array.isArray(proceso.tallas)) {
                tallasProcesoHTML = proceso.tallas.map(t => `${t.talla}: ${t.cantidad}`).join(' | ');
            }

            procesoDiv.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center;" onclick="const el = document.getElementById('${idCollapse}'); el.style.display = el.style.display === 'none' ? 'block' : 'none'; this.querySelector('span').textContent = el.style.display === 'none' ? '▼' : '▲';">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #9f1239; font-size: 0.95rem;">
                            ${procesosNombre}
                        </div>
                    </div>
                    <span style="font-size: 1rem; color: #6b7280; margin-left: 1rem;">▼</span>
                </div>
                <div id="${idCollapse}" style="display: none; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                    <strong>Tallas y Cantidades:</strong> ${tallasProcesoHTML || 'No especificadas'}
                </div>
            `;

            procesosContainer.appendChild(procesoDiv);
        });

        contenedor.appendChild(procesosContainer);
    }

    return contenedor;
}

/**
 * Determinar categoría del ítem
 */
function determinarCategoria(item) {
    if (!item.procesos || item.procesos.length === 0) {
        return item.origen === 'bodega' ? 'COSTURA-BODEGA' : 'COSTURA-CONFECCIÓN';
    }

    if (item.procesos.length > 1) {
        return 'COMBINADO';
    }

    const proceso = item.procesos[0].toLowerCase();
    if (proceso.includes('bordado')) return 'BORDADO';
    if (proceso.includes('estampado') || proceso.includes('dtf') || proceso.includes('sublimado')) return 'ESTAMPADO';
    if (proceso.includes('reflectivo')) return 'REFLECTIVO';

    return 'OTRO';
}

/**
 * Obtener colores según categoría
 */
function obtenerColorCategoria(categoria) {
    const colores = {
        'COSTURA-BODEGA': { bg: '#fef3c7', text: '#92400e' },
        'COSTURA-CONFECCIÓN': { bg: '#dcfce7', text: '#166534' },
        'BORDADO': { bg: '#dbeafe', text: '#1e40af' },
        'ESTAMPADO': { bg: '#fce7f3', text: '#9f1239' },
        'REFLECTIVO': { bg: '#fef9c3', text: '#854d0e' },
        'COMBINADO': { bg: '#e9d5ff', text: '#6b21a8' },
        'OTRO': { bg: '#f3f4f6', text: '#374151' }
    };

    return colores[categoria] || colores['OTRO'];
}

/**
 * Eliminar ítem
 */
window.eliminarItem = function(index) {
    window.itemsPedido.splice(index, 1);
    window.actualizarVistaItems();
};

/**
 * Función global para obtener ítems del pedido
 */
window.obtenerItemsPedido = function() {
    return window.itemsPedido;
};

/**
 * Función global para verificar si hay ítems
 */
window.tieneItems = function() {
    return window.itemsPedido.length > 0;
};

console.log('✅ Módulo gestion-items-pedido.js cargado correctamente');
