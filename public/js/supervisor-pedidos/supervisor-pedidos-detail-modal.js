/**
 * Order Detail Modal Management for Supervisor Pedidos
 * Handles opening, closing, and overlay management for the order detail modal
 * Follows the same pattern as asesores/pedidos-detail-modal.js
 */

console.log('📄 [MODAL] Cargando supervisor-pedidos-detail-modal.js');

/**
 * Abre el modal de detalle de la orden y carga los datos
 * @param {number} ordenId - ID de la orden
 */
window.openOrderDetailModal = async function openOrderDetailModal(ordenId) {
    console.log('🔵 [MODAL] Abriendo modal de detalle para orden:', ordenId);
    
    try {
        // ✅ HACER FETCH a la API para obtener datos del pedido
        console.log('🔵 [MODAL] Haciendo fetch a /supervisor-pedidos/' + ordenId + '/datos');
        const response = await fetch(`/supervisor-pedidos/${ordenId}/datos`);
        if (!response.ok) throw new Error('Error fetching order');
        const data = await response.json();
        
        console.log('✅ [MODAL] Datos del pedido obtenidos:', data);
        
        // Mostrar el overlay
        let overlay = document.getElementById('modal-overlay');
        console.log('🔵 [MODAL] Buscando overlay:', { encontrado: !!overlay, id: 'modal-overlay' });
        
        if (overlay) {
            // Mover el overlay al body si no está ya ahí
            if (overlay.parentElement !== document.body) {
                console.log('🔵 [MODAL] Moviendo overlay al body...');
                document.body.appendChild(overlay);
            }
            
            console.log('🔵 [MODAL] Overlay encontrado, mostrando...');
            overlay.style.display = 'block';
            overlay.style.zIndex = '9997';
            overlay.style.position = 'fixed';
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            console.log('✅ [MODAL] Overlay mostrado');
            
            // Mostrar el wrapper del modal
            const modalWrapper = document.getElementById('order-detail-modal-wrapper');
            if (modalWrapper) {
                console.log('🔵 [MODAL] Moviendo wrapper al body...');
                if (modalWrapper.parentElement !== document.body) {
                    document.body.appendChild(modalWrapper);
                }
                
                modalWrapper.style.display = 'block';
                modalWrapper.style.zIndex = '9998';
                modalWrapper.style.position = 'fixed';
                modalWrapper.style.top = '50%';
                modalWrapper.style.left = '50%';
                modalWrapper.style.transform = 'translate(-50%, -50%)';
                modalWrapper.style.pointerEvents = 'auto';
                modalWrapper.style.width = '90%';
                modalWrapper.style.maxWidth = '672px';
                console.log('✅ [MODAL] Modal wrapper mostrado');
            } else {
                console.error('❌ [MODAL] Modal wrapper NO encontrado en el DOM');
            }
        } else {
            console.error('❌ [MODAL] Overlay NO encontrado en el DOM');
        }
        
        // ✅ LLENAR CAMPOS DEL MODAL
        console.log('🔵 [MODAL] Llenando campos del modal...');
        
        // Fecha
        if (data.created_at) {
            const fechaCreacion = new Date(data.created_at);
            const day = String(fechaCreacion.getDate()).padStart(2, '0');
            const month = String(fechaCreacion.getMonth() + 1).padStart(2, '0');
            const year = fechaCreacion.getFullYear();
            
            const orderDate = document.getElementById('order-date');
            if (orderDate) {
                const dayBox = orderDate.querySelector('.day-box');
                const monthBox = orderDate.querySelector('.month-box');
                const yearBox = orderDate.querySelector('.year-box');
                if (dayBox) dayBox.textContent = day;
                if (monthBox) monthBox.textContent = month;
                if (yearBox) yearBox.textContent = year;
                console.log('✅ [MODAL] Fecha llenada:', day + '/' + month + '/' + year);
            }
        }
        
        // Número de orden
        const ordenDiv = document.getElementById('order-pedido');
        if (ordenDiv) {
            ordenDiv.textContent = `N° ${data.id}`;
        }
        
        // Información del pedido
        const clienteField = document.getElementById('cliente-value');
        if (clienteField) clienteField.textContent = data.cliente_nombre || data.cliente || 'N/A';
        
        const asesoraField = document.getElementById('asesora-value');
        if (asesoraField) asesoraField.textContent = data.asesora_nombre || data.asesora?.name || 'N/A';
        
        const formaPagoField = document.getElementById('forma-pago-value');
        if (formaPagoField) formaPagoField.textContent = data.forma_de_pago || 'N/A';
        
        const encargadoField = document.getElementById('encargado-value');
        if (encargadoField) encargadoField.textContent = data.asesora_nombre || data.asesora?.name || 'N/A';
        
        // Prendas entregadas
        const prendasEntregadasValue = document.getElementById('prendas-entregadas-value');
        if (prendasEntregadasValue) {
            const totalEntregado = data.total_entregado || 0;
            const totalCantidad = data.total_cantidad || data.cantidad_total || 0;
            prendasEntregadasValue.textContent = `${totalEntregado} de ${totalCantidad}`;
        }
        
        // ✅ LLENAR DESCRIPCIÓN DE PRENDAS CON NAVEGACIÓN (EXACTO COMO EN ASESORES)
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = prevArrow?.parentElement;
        
        if (descripcionText && data.descripcion_prendas) {
            // Parsear la descripción de prendas - EXACTAMENTE COMO EN ASESORES
            const prendas = data.descripcion_prendas.split(/\n\s*\n/).filter(p => p.trim());
            
            // Función para formatear una prenda - COPIA EXACTA DE ASESORES
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
            
            // Función para actualizar la descripción - COPIA EXACTA DE ASESORES
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
            
            // Remover listeners anteriores para evitar acumulación (COMO EN ASESORES)
            if (prevArrow && prevArrow._prendasClickHandler) {
                prevArrow.removeEventListener('click', prevArrow._prendasClickHandler);
            }
            if (nextArrow && nextArrow._prendasClickHandler) {
                nextArrow.removeEventListener('click', nextArrow._prendasClickHandler);
            }
            
            // Crear nuevos handlers para navegación (COMO EN ASESORES)
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
        
        console.log('✅ [MODAL] Modal abierto completamente');
        
    } catch (error) {
        console.error('❌ [MODAL] Error al cargar el modal:', error);
        alert('Error al cargar los detalles de la orden');
    }
};

/**
 * Cierra el modal y el overlay
 */
window.closeModalOverlay = function closeModalOverlay() {
    console.log('🔵 [MODAL] Cerrando modal...');
    
    const overlay = document.getElementById('modal-overlay');
    const wrapper = document.getElementById('order-detail-modal-wrapper');
    
    if (overlay) {
        overlay.style.display = 'none';
        console.log('✅ [MODAL] Overlay cerrado');
    }
    
    if (wrapper) {
        wrapper.style.display = 'none';
        console.log('✅ [MODAL] Modal wrapper cerrado');
    }
};

console.log('✅ [MODAL] supervisor-pedidos-detail-modal.js cargado correctamente');

