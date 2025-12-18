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
                modalWrapper.style.top = '60%';
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
            ordenDiv.textContent = `N° ${data.numero_pedido}`;
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
        
        // ✅ LLENAR DESCRIPCIÓN DE PRENDAS - USAR LÓGICA DE order-detail-modal-manager.js
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        
        if (descripcionText && data.descripcion_prendas) {
            console.log('📝 [DESCRIPCION COMPLETA]:\n' + data.descripcion_prendas);
            
            // Dividir por "PRENDA " para obtener bloques individuales
            let bloquesPrendas = [];
            
            if (data.descripcion_prendas.includes('PRENDA ')) {
                const partes = data.descripcion_prendas.split('PRENDA ');
                bloquesPrendas = partes
                    .map((parte, idx) => {
                        if (idx === 0 && !parte.trim()) return null;
                        return (idx > 0 ? 'PRENDA ' : '') + parte.trim();
                    })
                    .filter(b => b && b.trim() !== '');
            } else {
                bloquesPrendas = data.descripcion_prendas
                    .split('\n\n')
                    .filter(b => b && b.trim() !== '');
            }
            
            console.log('📊 [MODAL] Total bloques de prendas:', bloquesPrendas.length);
            
            // Formatear bloques con estilos (EXACTO COMO EN ASESORES)
            const descripcionFormateada = bloquesPrendas
                .map((bloque, bloqueIdx) => {
                    const lineas = bloque.split('\n').map(l => l.trim()).filter(l => l !== '');
                    const lineasProcesadas = [];
                    
                    for (let i = 0; i < lineas.length; i++) {
                        let linea = lineas[i];
                        if (linea === '') continue;
                        
                        // NEGRILLA en títulos
                        linea = linea.replace(/^(PRENDA \d+:)/g, '<strong>$1</strong>');
                        linea = linea.replace(/(Color:|Tela:|Manga:|DESCRIPCION:)/g, '<strong>$1</strong>');
                        
                        // NEGRILLA en viñetas
                        linea = linea.replace(/^(•\s+(Reflectivo:|Bolsillos:|BOTÓN:|BROCHE:|[A-Z]+:))/g, '<strong>$1</strong>');
                        
                        // ROJO en tallas
                        if (/^Tallas?:/i.test(linea)) {
                            linea = linea.replace(/^(Tallas?:)\s+(.+)$/i, '$1 <span style="color: #d32f2f; font-weight: bold;">$2</span>');
                        }
                        
                        lineasProcesadas.push(linea);
                    }
                    
                    return lineasProcesadas.join('<br>');
                })
                .join('<br><br>');
            
            descripcionText.innerHTML = `<div style="line-height: 1.8; font-size: 0.75rem; color: #333; word-break: break-word; overflow-wrap: break-word; max-width: 100%; margin: 0; padding: 0;">
                ${descripcionFormateada}
            </div>`;
            
            // Ocultar flechas de navegación (mostrar todas las prendas)
            if (prevArrow) prevArrow.style.display = 'none';
            if (nextArrow) nextArrow.style.display = 'none';
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

