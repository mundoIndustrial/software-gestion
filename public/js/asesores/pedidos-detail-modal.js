/**
 * Order Detail Modal Management for Pedidos
 * Handles opening, closing, and overlay management for the order detail modal
 */

/**
 * Abre el modal de detalle de la orden y carga los datos
 * @param {number} numeroPedido - Número del pedido
 */
async function verFactura(numeroPedido) {
    console.log('Abriendo modal de detalle para pedido:', numeroPedido);
    
    try {
        // ✅ HACER FETCH a la API para obtener datos del pedido
        const response = await fetch(`/registros/${numeroPedido}`);
        if (!response.ok) throw new Error('Error fetching order');
        const order = await response.json();
        
        console.log('✅ Datos del pedido obtenidos:', order);
        
        // ✅ LLENAR CAMPOS DEL MODAL
        
        // Fecha
        if (order.fecha_de_creacion_de_orden) {
            const fechaCreacion = new Date(order.fecha_de_creacion_de_orden);
            const day = fechaCreacion.getDate().toString().padStart(2, '0');
            const month = fechaCreacion.toLocaleDateString('es-ES', { month: 'short' }).toUpperCase();
            const year = fechaCreacion.getFullYear().toString().slice(-2);
            
            const orderDate = document.getElementById('order-date');
            if (orderDate) {
                const dayBox = orderDate.querySelector('.day-box');
                const monthBox = orderDate.querySelector('.month-box');
                const yearBox = orderDate.querySelector('.year-box');
                if (dayBox) dayBox.textContent = day;
                if (monthBox) monthBox.textContent = month;
                if (yearBox) yearBox.textContent = year;
            }
        }
        
        // Número de pedido
        const pedidoDiv = document.getElementById('order-pedido');
        if (pedidoDiv) {
            pedidoDiv.textContent = `N° ${numeroPedido}`;
        }
        
        // Asesora
        const asesoraValue = document.getElementById('asesora-value');
        if (asesoraValue) {
            asesoraValue.textContent = order.asesora || order.asesor || '---';
        }
        
        // Forma de pago
        const formaPagoValue = document.getElementById('forma-pago-value');
        if (formaPagoValue) {
            formaPagoValue.textContent = order.forma_de_pago || '---';
        }
        
        // Cliente
        const clienteValue = document.getElementById('cliente-value');
        if (clienteValue) {
            clienteValue.textContent = order.cliente_nombre || order.cliente || '---';
        }
        
        // Encargado de orden
        const encargadoValue = document.getElementById('encargado-value');
        if (encargadoValue) {
            encargadoValue.textContent = order.encargado_orden || '';
        }
        
        // Prendas entregadas
        const prendasEntregadasValue = document.getElementById('prendas-entregadas-value');
        if (prendasEntregadasValue) {
            const totalEntregado = order.total_entregado || 0;
            const totalCantidad = order.total_cantidad || 0;
            prendasEntregadasValue.textContent = `${totalEntregado} de ${totalCantidad}`;
        }
        
        // ✅ LLENAR DESCRIPCIÓN DE PRENDAS CON NAVEGACIÓN
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = prevArrow?.parentElement;
        
        if (descripcionText && order.descripcion_prendas) {
            // Parsear la descripción de prendas
            const prendas = order.descripcion_prendas.split(/\n\s*\n/).filter(p => p.trim());
            
            // Función para formatear una prenda
            function formatearPrenda(prendaText, index) {
                // Parsear cada prenda
                const prendaMatch = prendaText.match(/^Prenda\s+(\d+):\s*(.+?)(?:\n|$)/);
                const prendaNum = prendaMatch ? prendaMatch[1] : (index + 1);
                const prendaNombre = prendaMatch ? prendaMatch[2].trim() : '';
                
                // Buscar Color
                const colorMatch = prendaText.match(/Color:\s*(.+?)(?:\n|$)/);
                const color = colorMatch ? colorMatch[1].trim() : '';
                
                // Buscar Tela
                const telaMatch = prendaText.match(/Tela:\s*(.+?)(?:\n|$)/);
                const tela = telaMatch ? telaMatch[1].trim() : '';
                
                // Buscar Manga
                const mangaMatch = prendaText.match(/Manga:\s*(.+?)(?:\n|$)/);
                const manga = mangaMatch ? mangaMatch[1].trim() : '';
                
                // Buscar "Especificaciones:" (contiene Bolsillos, Reflectivo, etc.)
                const especificacionesMatch = prendaText.match(/Especificaciones:\s*(.+?)(?=Descripción:|Tallas:|$)/s);
                const especificaciones = especificacionesMatch ? especificacionesMatch[1].trim() : '';
                
                // Buscar Descripción
                const descMatch = prendaText.match(/Descripción:\s*(.+?)(?=\n\s*Tallas:|$)/s);
                let desc = descMatch ? descMatch[1].trim() : '';
                desc = desc.replace(/^Prenda\s+\d+:.*?\n/, '').trim();
                desc = desc.replace(/^Descripción:\s*/, '').trim();
                
                // Buscar Tallas
                const tallasMatch = prendaText.match(/Tallas:\s*(.+?)$/s);
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
                    // Hacer negrilla los títulos "Bolsillos:" y "Reflectivo:"
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
            
            // Función para actualizar la descripción
            let currentIndex = 0;
            function updateDescripcion() {
                if (prendas.length <= 2) {
                    // Si hay 2 o menos prendas, mostrar todas
                    const html = prendas.map((p, i) => formatearPrenda(p, i)).join('');
                    descripcionText.innerHTML = html;
                    if (arrowContainer) arrowContainer.style.display = 'none';
                } else {
                    // Si hay más de 2 prendas, mostrar 2 a la vez con navegación
                    let html = '';
                    if (currentIndex === 0) {
                        // Primera pantalla: Prenda 1 + Prenda 2
                        html = formatearPrenda(prendas[0], 0) + formatearPrenda(prendas[1], 1);
                    } else {
                        // Siguientes pantallas: mostrar Prenda N+1 (solo la siguiente)
                        html = formatearPrenda(prendas[currentIndex + 1], currentIndex + 1);
                    }
                    descripcionText.innerHTML = html;
                    if (arrowContainer) arrowContainer.style.display = 'flex';
                    if (prevArrow) prevArrow.style.display = currentIndex > 0 ? 'inline-block' : 'none';
                    if (nextArrow) nextArrow.style.display = currentIndex < prendas.length - 2 ? 'inline-block' : 'none';
                }
            }
            
            // Actualizar descripción inicial
            updateDescripcion();
            
            // Remover listeners anteriores para evitar acumulación
            if (prevArrow && prevArrow._prendasClickHandler) {
                prevArrow.removeEventListener('click', prevArrow._prendasClickHandler);
            }
            if (nextArrow && nextArrow._prendasClickHandler) {
                nextArrow.removeEventListener('click', nextArrow._prendasClickHandler);
            }
            
            // Crear nuevos handlers para navegación
            if (prevArrow) {
                prevArrow._prendasClickHandler = () => {
                    if (currentIndex > 0) {
                        currentIndex--;
                        updateDescripcion();
                    }
                };
                prevArrow.addEventListener('click', prevArrow._prendasClickHandler);
            }
            
            if (nextArrow) {
                nextArrow._prendasClickHandler = () => {
                    if (currentIndex < prendas.length - 2) {
                        currentIndex++;
                        updateDescripcion();
                    }
                };
                nextArrow.addEventListener('click', nextArrow._prendasClickHandler);
            }
        }
        
        // ✅ MOSTRAR MODAL
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.style.display = 'block';
        }
        
        // Disparar evento para abrir el modal usando Alpine.js
        const event = new CustomEvent('open-modal', { detail: 'order-detail' });
        window.dispatchEvent(event);
        
    } catch (error) {
        console.error('❌ Error al cargar datos del pedido:', error);
        alert('Error al cargar los datos del pedido. Intenta nuevamente.');
    }
}

/**
 * Abre el modal de seguimiento del pedido
 * @param {number} numeroPedido - Número del pedido
 */
function verSeguimiento(numeroPedido) {
    console.log('Abriendo modal de seguimiento para pedido:', numeroPedido);
    
    // Llamar a la función openOrderTracking que ya existe en orderTracking.js
    if (typeof openOrderTracking === 'function') {
        openOrderTracking(numeroPedido);
    } else {
        console.error('❌ Función openOrderTracking no disponible');
        alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
    }
}

/**
 * Cierra el modal de detalle y el overlay
 */
function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    
    // Cerrar el modal
    const closeEvent = new CustomEvent('close-modal', { detail: 'order-detail' });
    window.dispatchEvent(closeEvent);
}

// Escuchar evento de cierre del modal desde Alpine.js
window.addEventListener('close-modal', function(event) {
    if (event.detail === 'order-detail') {
        closeModalOverlay();
    }
});

// Cerrar modal al presionar Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const overlay = document.getElementById('modal-overlay');
        if (overlay && overlay.style.display === 'block') {
            closeModalOverlay();
        }
    }
});

// Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('click', function(event) {
    const overlay = document.getElementById('modal-overlay');
    const modalContainer = document.querySelector('div[style*="max-width: 672px"]');
    
    // Si se hace clic en el overlay y no en el modal
    if (overlay && overlay.style.display === 'block' && event.target === overlay) {
        closeModalOverlay();
    }
});
