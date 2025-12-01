/**
 * Script para manejar el modal de detalle de órdenes de bodega
 * Funcionalidad: Mostrar detalles completos de una orden con fecha formateada y navegación de prendas
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('📦 Bodega Detail Modal Script Loaded');
    initializeBodegaDetailModal();
});

/**
 * Inicializar modal de detalle de bodega
 */
function initializeBodegaDetailModal() {
    // Event delegation para botones de ver detalles
    document.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('[data-action="view-bodega"]');
        if (viewBtn) {
            const pedido = viewBtn.dataset.id;
            openBodegaDetailModal(pedido);
        }
    });
}

/**
 * Abrir modal de detalle de bodega
 */
function openBodegaDetailModal(pedido) {
    console.log(`👁️ Abriendo detalle de bodega para pedido ${pedido}`);
    
    // Obtener datos de la orden
    fetch(`/bodega/${pedido}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayBodegaDetailModal(data);
    })
    .catch(error => {
        console.error('Error al obtener datos:', error);
        showNotification('Error al cargar los detalles', 'error');
    });
}

/**
 * Mostrar modal de detalle de bodega con datos
 */
function displayBodegaDetailModal(orden) {
    // Esperar a que los elementos del modal estén disponibles
    let attempts = 0;
    const checkElements = setInterval(() => {
        const dayBox = document.querySelector('.day-box');
        const monthBox = document.querySelector('.month-box');
        const yearBox = document.querySelector('.year-box');
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        
        if (dayBox && monthBox && yearBox && descripcionText && prevArrow && nextArrow) {
            clearInterval(checkElements);
            
            // Llenar fecha
            const fecha = new Date(orden.fecha_de_creacion_de_orden);
            if (!isNaN(fecha.getTime())) {
                const dia = String(fecha.getDate()).padStart(2, '0');
                const mes = String(fecha.getMonth() + 1).padStart(2, '0');
                const año = fecha.getFullYear();
                
                dayBox.textContent = dia;
                monthBox.textContent = mes;
                yearBox.textContent = año;
            }

            // Llenar cliente
            const clienteValue = document.getElementById('cliente-value');
            if (clienteValue) clienteValue.textContent = orden.cliente || '-';

            // Llenar descripción y agregar navegación si es necesario
            const descripcionHTML = formatearDescripcion(orden.descripcion);
            descripcionText.innerHTML = descripcionHTML;
            
            // Configurar navegación de prendas
            if (window.bodegaPrendas && window.bodegaPrendas.length > 2) {
                const arrowContainer = prevArrow?.parentElement;
                
                // Remover listeners anteriores
                const oldPrevHandler = prevArrow._bodegaClickHandler;
                const oldNextHandler = nextArrow._bodegaClickHandler;
                if (oldPrevHandler) prevArrow.removeEventListener('click', oldPrevHandler);
                if (oldNextHandler) nextArrow.removeEventListener('click', oldNextHandler);
                
                // Crear función para actualizar descripción
                const updateDescripcion = () => {
                    let html = '';
                    if (window.bodegaPrendaIndex === 0) {
                        // Primera pantalla: Prenda 1 + Prenda 2
                        html = formatearPrenda(window.bodegaPrendas[0], 0) + 
                               formatearPrenda(window.bodegaPrendas[1], 1);
                    } else {
                        // Pantallas siguientes: mostrar solo Prenda siguiente
                        html = formatearPrenda(window.bodegaPrendas[window.bodegaPrendaIndex + 1], window.bodegaPrendaIndex + 1);
                    }
                    descripcionText.innerHTML = html;
                    
                    // Mostrar/ocultar flechas
                    if (arrowContainer) arrowContainer.style.display = 'flex';
                    prevArrow.style.display = window.bodegaPrendaIndex > 0 ? 'inline-block' : 'none';
                    nextArrow.style.display = window.bodegaPrendaIndex < window.bodegaPrendas.length - 2 ? 'inline-block' : 'none';
                };
                
                // Agregar nuevos listeners
                prevArrow._bodegaClickHandler = () => {
                    if (window.bodegaPrendaIndex > 0) {
                        window.bodegaPrendaIndex--;
                        updateDescripcion();
                    }
                };
                
                nextArrow._bodegaClickHandler = () => {
                    if (window.bodegaPrendaIndex < window.bodegaPrendas.length - 2) {
                        window.bodegaPrendaIndex++;
                        updateDescripcion();
                    }
                };
                
                prevArrow.addEventListener('click', prevArrow._bodegaClickHandler);
                nextArrow.addEventListener('click', nextArrow._bodegaClickHandler);
                
                // Actualizar estado inicial de flechas
                prevArrow.style.display = 'none';
                nextArrow.style.display = window.bodegaPrendas.length > 2 ? 'inline-block' : 'none';
            } else {
                // Sin navegación si hay 2 o menos prendas
                if (prevArrow && nextArrow) {
                    prevArrow.style.display = 'none';
                    nextArrow.style.display = 'none';
                }
            }

            // Llenar pedido número
            const pedidoNumber = document.querySelector('.pedido-number');
            if (pedidoNumber) {
                pedidoNumber.textContent = `#${orden.pedido}`;
            }

            // Llenar encargado de orden
            const encargadoValue = document.getElementById('encargado-value');
            if (encargadoValue) encargadoValue.textContent = orden.encargado_orden || '-';

            // Llenar prendas entregadas
            const prendasValue = document.getElementById('prendas-entregadas-value');
            if (prendasValue) {
                prendasValue.textContent = `${orden.total_entregado || orden.cantidad || 0}/${orden.cantidad || 0}`;
            }

            console.log('✅ Modal de detalle completado y mostrando...');
            showBodegaDetailModal();
        } else if (attempts++ > 20) {
            clearInterval(checkElements);
            console.error('❌ Timeout esperando elementos del modal');
        }
    }, 100);
}

/**
 * Formatear descripción para mostrar en el modal
 */
function formatearDescripcion(descripcion) {
    if (!descripcion) return '-';
    
    // Dividir por doble salto de línea para separar prendas
    const prendas = descripcion.split(/\n\s*\n/).filter(p => p.trim());
    
    // Si hay más de 2 prendas, guardarlas globalmente para navegación
    if (prendas.length > 2) {
        window.bodegaPrendas = prendas;
        window.bodegaPrendaIndex = 0;
        // Mostrar las primeras 2 prendas
        return prendas.slice(0, 2).map((p, i) => formatearPrenda(p, i)).join('');
    } else {
        window.bodegaPrendas = null;
        // Mostrar todas las prendas
        return prendas.map((p, i) => formatearPrenda(p, i)).join('');
    }
}

/**
 * Formatear una prenda individual
 */
function formatearPrenda(prendaText, index) {
    // Parsear cada prenda
    const prendaMatch = prendaText.match(/^Prenda\s+(\d+):\s*(.+?)(?:\n|$)/i) || 
                        prendaText.match(/^(\d+)\.\s+(.+?)(?:\n|$)/);
    const prendaNum = prendaMatch ? prendaMatch[1] : (index + 1);
    const prendaNombre = prendaMatch ? prendaMatch[2].trim() : '';
    
    // Buscar Color
    const colorMatch = prendaText.match(/Color:\s*(.+?)(?:\n|$)/i);
    const color = colorMatch ? colorMatch[1].trim() : '';
    
    // Buscar Tela
    const telaMatch = prendaText.match(/Tela:\s*(.+?)(?:\n|$)/i);
    const tela = telaMatch ? telaMatch[1].trim() : '';
    
    // Buscar Manga
    const mangaMatch = prendaText.match(/Manga:\s*(.+?)(?:\n|$)/i);
    const manga = mangaMatch ? mangaMatch[1].trim() : '';
    
    // Buscar Especificaciones
    const especificacionesMatch = prendaText.match(/Especificaciones:\s*(.+?)(?=Descripción:|Tallas:|$)/is);
    const especificaciones = especificacionesMatch ? especificacionesMatch[1].trim() : '';
    
    // Buscar Descripción
    const descMatch = prendaText.match(/Descripción:\s*(.+?)(?=\n\s*Tallas:|$)/is);
    let desc = descMatch ? descMatch[1].trim() : '';
    desc = desc.replace(/^Prenda\s+\d+:.*?\n/i, '').trim();
    desc = desc.replace(/^Descripción:\s*/i, '').trim();
    
    // Buscar Tallas
    const tallasMatch = prendaText.match(/Tallas:\s*(.+?)$/is);
    const tallas = tallasMatch ? tallasMatch[1].trim() : '';
    
    // Construir línea de atributos (Color | Tela | Manga)
    const atributos = [];
    if (color) atributos.push(`<span class="prenda-description-label">Color:</span> ${color}`);
    if (tela) atributos.push(`<span class="prenda-description-label">Tela:</span> ${tela}`);
    if (manga) atributos.push(`<span class="prenda-description-label">Manga:</span> ${manga}`);
    const atributosLinea = atributos.join(' | ');
    
    // Construir línea de descripción (con Bolsillos y Reflectivo en negrilla)
    let descripcionLinea = '';
    if (desc) {
        descripcionLinea = `<span class="prenda-description-label">Descripción:</span> ${desc}`;
    }
    if (especificaciones) {
        let especificacionesFormato = especificaciones
            .replace(/Bolsillos:/g, '<strong>Bolsillos:</strong>')
            .replace(/Reflectivo:/g, '<strong>Reflectivo:</strong>');
        
        if (descripcionLinea) {
            descripcionLinea += ` | ${especificacionesFormato}`;
        } else {
            descripcionLinea = `<span class="prenda-description-label">Descripción:</span> ${especificacionesFormato}`;
        }
    }
    
    return `
        <div class="prenda-line">
            <span class="prenda-name"><strong>Prenda ${prendaNum}: ${prendaNombre}</strong></span>
            ${atributosLinea ? `<div>${atributosLinea}</div>` : ''}
            ${atributosLinea && descripcionLinea ? `<div style="height: 4px;"></div>` : ''}
            ${descripcionLinea ? `<div>${descripcionLinea}</div>` : ''}
            ${descripcionLinea && tallas ? `<div style="height: 4px;"></div>` : ''}
            ${tallas ? `<div><span class="prenda-tallas-label">Tallas:</span> <span class="prenda-tallas-value">${tallas}</span></div>` : ''}
        </div>
    `;
}

/**
 * Mostrar modal de detalle
 */
function showBodegaDetailModal() {
    // Buscar el modal Livewire usando Alpine.js
    const modalElement = document.querySelector('[x-data*="bodega-order-detail"]') ||
                         document.querySelector('[x-show*="bodega-order-detail"]') ||
                         document.querySelector('.order-detail-modal-container');
    
    if (modalElement) {
        // Disparar evento Alpine.js para abrir el modal
        if (window.Alpine && modalElement.__x) {
            modalElement.__x.$data.show = true;
            console.log('✅ Modal mostrado via Alpine.js');
        } else {
            // Fallback: mostrar directamente
            modalElement.style.display = 'flex';
            modalElement.classList.add('show');
            console.log('✅ Modal mostrado (fallback)');
        }
    } else {
        console.warn('⚠️ No se encontró elemento modal');
    }
}

/**
 * Cerrar modal de detalle
 */
function closeBodegaDetailModal() {
    const modalElement = document.querySelector('[x-data*="bodega-order-detail"]') ||
                         document.querySelector('[x-show*="bodega-order-detail"]') ||
                         document.querySelector('.order-detail-modal-container');
    
    if (modalElement) {
        // Disparar evento Alpine.js para cerrar el modal
        if (window.Alpine && modalElement.__x) {
            modalElement.__x.$data.show = false;
            console.log('✅ Modal cerrado via Alpine.js');
        } else {
            // Fallback: cerrar directamente
            modalElement.style.display = 'none';
            modalElement.classList.remove('show');
            console.log('✅ Modal cerrado (fallback)');
        }
    }
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff';
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10001;
        animation: slideIn 0.3s ease;
    `;
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
