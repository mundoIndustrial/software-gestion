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
 * Renderizar lista de ítems
 */
function renderizarItems() {
    const listaItems = document.getElementById('lista-items-pedido');

    console.log('🎨 Renderizando ítems. Total:', window.itemsPedido.length);
    console.log('🎨 Elemento listaItems:', listaItems);

    listaItems.innerHTML = '';

    window.itemsPedido.forEach((item, index) => {
        console.log(`  🔸 Renderizando ítem ${index + 1}:`, item.prenda?.nombre);

        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'padding: 1.25rem; background: white; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;';

        // Determinar categoría del ítem
        const categoria = determinarCategoria(item);
        const colorCategoria = obtenerColorCategoria(categoria);

        const infoDiv = document.createElement('div');
        infoDiv.style.cssText = 'flex: 1;';

        // Calcular total de prendas
        const totalPrendas = item.prenda?.cantidad || 0;

        // Construir texto de procesos
        const procesosTexto = item.procesos && item.procesos.length > 0 ?
            item.procesos.join(', ') : 'Sin procesos';

        // Determinar si es ítem de proceso
        const esProceso = item.es_proceso === true;
        const tipoItem = esProceso ? '🔧 PROCESO' : (item.origen === 'bodega' ? '🏪 BASE' : '✂️ BASE');

        infoDiv.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: ${esProceso ? '#9f1239' : '#1e40af'}; font-size: 1.1rem; margin-bottom: 0.5rem;">
                        ${index + 1}. ${item.prenda?.nombre || item.nombre || 'Ítem ' + (index + 1)}
                        ${esProceso ? '<span style="font-size: 0.875rem; color: #9f1239; font-weight: 600;"> (PROCESO)</span>' : ''}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                        ${item.tipo === 'cotizacion' ? `
                            <span style="padding: 0.25rem 0.75rem; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                                📋 ${item.numero}
                            </span>
                        ` : ''}
                        <span style="padding: 0.25rem 0.75rem; background: ${esProceso ? '#fce7f3' : (item.origen === 'bodega' ? '#fef3c7' : '#dcfce7')}; color: ${esProceso ? '#9f1239' : (item.origen === 'bodega' ? '#92400e' : '#166534')}; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                            ${tipoItem}
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                            📦 ${totalPrendas} unidades
                        </span>
                        <span style="padding: 0.25rem 0.75rem; background: ${colorCategoria.bg}; color: ${colorCategoria.text}; border-radius: 12px; font-size: 0.875rem; font-weight: 500;">
                            🏷️ ${categoria}
                        </span>
                    </div>
                    <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.5rem;">
                        <strong>${esProceso ? 'Procesos aplicados:' : 'Procesos:'}</strong> ${procesosTexto}
                    </div>
                    ${item.tipo === 'cotizacion' ? `
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                            <strong>Cliente:</strong> ${item.cliente}
                        </div>
                    ` : ''}
                </div>
                <button type="button" onclick="window.eliminarItem(${index})" style="padding: 0.5rem 0.75rem; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: background 0.2s; margin-left: 1rem;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    ✕
                </button>
            </div>
        `;

        itemDiv.appendChild(infoDiv);
        listaItems.appendChild(itemDiv);
        console.log(`  ✅ Ítem ${index + 1} agregado al DOM`);
    });

    console.log('🎨 Renderizado completado. Elementos en listaItems:', listaItems.children.length);
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
