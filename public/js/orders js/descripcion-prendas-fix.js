/**
 * FIX: Descripción de prendas vacía en modal
 * Se ejecuta ANTES de orders-table-v2.js para interceptar la extracción de contenido
 */

console.log('🔧 descripcion-prendas-fix.js cargado');

// Función para obtener el contenido real de descripcion_prendas
function obtenerContenidoDescripcionPrendas(cell, column) {
    if (column !== 'descripcion_prendas') {
        return null;
    }
    
    console.log('🔍 Buscando contenido de descripcion_prendas...');
    
    // Buscar el div con data-full-content
    const descripcionDiv = cell.querySelector('.descripcion-preview');
    if (descripcionDiv && descripcionDiv.dataset.fullContent) {
        try {
            const decodedContent = atob(descripcionDiv.dataset.fullContent);
            console.log(' Contenido decodificado:', decodedContent.substring(0, 100));
            return decodedContent;
        } catch (e) {
            console.error(' Error decodificando base64:', e);
        }
    }
    
    // Si no hay data-full-content, retornar null para que use el método por defecto
    return null;
}

// Interceptar la función que extrae el contenido de la celda
// Esto se hace reemplazando el método de extracción antes de que se ejecute
document.addEventListener('DOMContentLoaded', function() {
    // Esperar a que el DOM esté completamente listo
    setTimeout(function() {
        // Buscar todas las celdas de descripcion_prendas y guardar su contenido
        const table = document.querySelector('.modern-table');
        if (table) {
            const cells = table.querySelectorAll('td');
            cells.forEach(function(td) {
                // Buscar si esta celda es descripcion_prendas
                const cellText = td.querySelector('.cell-text');
                if (cellText) {
                    const descripcionDiv = cellText.querySelector('.descripcion-preview');
                    if (descripcionDiv && descripcionDiv.dataset.fullContent) {
                        // Guardar el contenido decodificado en un atributo data del cell-text
                        try {
                            const decodedContent = atob(descripcionDiv.dataset.fullContent);
                            cellText.dataset.fullDescripcion = decodedContent;
                            console.log('💾 Contenido guardado en cell-text para:', cellText.dataset.pedido);
                        } catch (e) {
                            console.error(' Error:', e);
                        }
                    }
                }
            });
        }
    }, 100);
});

// Interceptar el evento de doble clic ANTES de que se procese
document.addEventListener('dblclick', function(e) {
    const cellContent = e.target.closest('.cell-content');
    if (!cellContent) return;
    
    const cell = cellContent.closest('td');
    if (!cell) return;
    
    const cellText = cell.querySelector('.cell-text');
    if (!cellText) return;
    
    // Verificar si es descripcion_prendas
    const column = cell.getAttribute('data-column') || 'unknown';
    if (column === 'descripcion_prendas' && cellText.dataset.fullDescripcion) {
        console.log('🎯 Interceptando dblclick en descripcion_prendas');
        
        // Guardar el contenido en un lugar donde orders-table-v2.js pueda encontrarlo
        window.lastDescripcionPrendasContent = cellText.dataset.fullDescripcion;
        
        // Obtener el número de pedido de la fila
        const row = cell.closest('.table-row');
        if (row) {
            const numeroPedido = row.getAttribute('data-orden-id');
            window.lastNumeroPedido = numeroPedido;
            console.log('📦 Contenido guardado en window.lastDescripcionPrendasContent para pedido:', numeroPedido);
            
            // Obtener datos de prendas si es una cotización
            obtenerDatosPrendasParaModal(numeroPedido);
        }
    }
}, true); // Usar captura para que se ejecute ANTES que otros listeners

// Función para obtener datos de prendas del servidor
function obtenerDatosPrendasParaModal(numeroPedido) {
    fetch(`/orders/${numeroPedido}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Error fetching order data');
        return response.json();
    })
    .then(data => {
        console.log('📊 Datos de orden obtenidos:', data);
        // Guardar datos para que showOrderDescriptionModal los use
        window.lastOrderData = data;
    })
    .catch(error => {
        console.error(' Error obteniendo datos de orden:', error);
    });
}

// Función para inyectar el contenido en el modal
function inyectarContenidoEnModal() {
    if (!window.lastDescripcionPrendasContent) return;
    
    const input = document.getElementById('cell-input');
    if (!input) return;
    
    const column = document.querySelector('[data-column]')?.getAttribute('data-column');
    if (column !== 'descripcion_prendas') return;
    
    // Esperar a que el modal esté visible
    setTimeout(function() {
        if (input.value.trim() === '' && window.lastDescripcionPrendasContent) {
            // NO poner el contenido en el input, solo mostrar formateado
            console.log(' Contenido de descripcion_prendas detectado');
            
            // Ocultar el input para descripcion_prendas
            input.style.display = 'none';
            
            // Mostrar el contenido formateado
            renderizarContenidoFormateado(window.lastDescripcionPrendasContent);
        }
    }, 50);
}

// Función para renderizar el contenido formateado
function renderizarContenidoFormateado(content) {
    const modal = document.getElementById('cell-modal');
    if (!modal) return;
    
    // Remover display anterior si existe
    const oldDisplay = modal.querySelector('.descripcion-formatted-display');
    if (oldDisplay) oldDisplay.remove();
    
    // Crear nuevo display
    const display = document.createElement('div');
    display.className = 'descripcion-formatted-display';
    display.style.cssText = `
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 12px;
        margin-top: 12px;
        font-size: 13.4px;
        line-height: 1.5;
        background: #f9f9f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    `;
    
    // Renderizar contenido con HTML
    display.innerHTML = content
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll(/\n\n/g, '</div><div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e0e0e0;">')
        .replaceAll(/^/, '<div>')
        .replaceAll(/$/, '</div>')
        .replaceAll(/PRENDA (\d+):/gi, '<strong style="font-size: 13px; color: #000;">PRENDA $1:</strong>')
        .replaceAll(/Prenda (\d+):/g, '<strong style="font-size: 13px; color: #000;">Prenda $1:</strong>')
        .replaceAll(/Color:/gi, '<strong style="color: #333;">Color:</strong>')
        .replaceAll(/Tela:/gi, '<strong style="color: #333;">Tela:</strong>')
        .replaceAll(/Manga:/gi, '<strong style="color: #333;">Manga:</strong>')
        .replaceAll(/Descripción:/gi, '<strong style="color: #333;">Descripción:</strong>')
        .replaceAll(/DESCRIPCION:/gi, '<strong style="color: #333;">DESCRIPCION:</strong>')
        .replaceAll(/Bolsillos:/gi, '<strong style="color: #d32f2f;">Bolsillos:</strong>')
        .replaceAll(/Reflectivo:/gi, '<strong style="color: #d32f2f;">Reflectivo:</strong>')
        .replaceAll(/Tallas:/gi, '<strong style="color: #d32f2f; font-size: 13px;">Tallas:</strong>');
    
    const input = document.getElementById('cell-input');
    if (input && input.parentElement) {
        input.parentElement.appendChild(display);
        console.log(' Contenido formateado renderizado');
    }
}

// Observar cambios en el modal para inyectar contenido
const modalObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            const modal = mutation.target;
            if (modal.id === 'cell-modal' && modal.classList.contains('active')) {
                console.log('🎭 Modal abierto, inyectando contenido...');
                inyectarContenidoEnModal();
            }
        }
    });
});

// Iniciar observación cuando el modal esté disponible
setTimeout(function() {
    const modal = document.getElementById('cell-modal');
    if (modal) {
        modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });
        console.log('👀 Observador de modal iniciado');
    }
}, 500);

console.log(' descripcion-prendas-fix.js inicializado');
