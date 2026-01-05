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

/**
 * Variables globales para la galería de costura
 */
let allImagesCostura = [];
let currentImageIndexCostura = 0;
let currentPedidoNumberCostura = null;

/**
 * Alterna entre la vista de factura y la galería de fotos
 * Implementadas abajo con window. para evitar duplicación
 */

// ===== VARIABLES GLOBALES PARA GALERÍA (YA EXISTEN) =====
// allImagesCostura, currentImageIndexCostura, currentPedidoNumberCostura
// Están declaradas arriba en la línea 218-219

/**
 * Cambia entre vista de factura y galería
 */
window.toggleFactura = function toggleFactura() {
    console.log('🎬 [TOGGLE FACTURA] Iniciando cambio a factura...');
    
    // Buscar dentro del modal de costura
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (!modalWrapper) {
        console.error('❌ [TOGGLE FACTURA] No se encontró el wrapper del modal de costura');
        return;
    }
    
    // Mostrar factura y ocultar galería
    const container = modalWrapper.querySelector('.order-detail-modal-container');
    if (container) {
        container.style.padding = '1.5cm';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
        container.style.height = 'auto';
        container.style.width = '100%';
    }
    
    // Restaurar el tamaño original del wrapper
    modalWrapper.style.maxWidth = '672px';
    modalWrapper.style.width = '90%';
    modalWrapper.style.height = 'auto';
    console.log('✅ [TOGGLE FACTURA] Wrapper restaurado a tamaño original');
    
    const card = modalWrapper.querySelector('.order-detail-card');
    if (card) card.style.display = 'block';
    
    const galeria = document.getElementById('galeria-modal-costura');
    if (galeria) galeria.style.display = 'none';
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-factura').style.border = 'none';
    document.getElementById('btn-factura').style.color = 'white';
    document.getElementById('btn-galeria').style.background = 'white';
    document.getElementById('btn-galeria').style.border = '2px solid #ddd';
    document.getElementById('btn-galeria').style.color = '#333';
};

/**
 * Muestra la galería de costura
 */
window.toggleGaleria = function toggleGaleria() {
    console.log('🎬 [TOGGLE GALERIA] Iniciando cambio a galería...');
    
    // Buscar dentro del modal de costura
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (!modalWrapper) {
        console.error('❌ [TOGGLE GALERIA] No se encontró el wrapper del modal de costura');
        return;
    }
    
    // Ocultar factura y mostrar galería
    const card = modalWrapper.querySelector('.order-detail-card');
    console.log('📋 [TOGGLE GALERIA] Card encontrada:', !!card);
    if (card) {
        card.style.display = 'none';
        console.log('✅ [TOGGLE GALERIA] Card ocultada');
    }
    
    // Configurar el contenedor para la galería
    const container = modalWrapper.querySelector('.order-detail-modal-container');
    console.log('📦 [TOGGLE GALERIA] Container encontrado:', !!container);
    
    if (container) {
        // Remover padding para que el header quede pegado arriba
        container.style.padding = '0';
        container.style.alignItems = 'stretch';
        container.style.justifyContent = 'flex-start';
        container.style.height = 'auto';
        container.style.width = '100%';
    }
    
    // Crear galería si no existe
    let galeria = document.getElementById('galeria-modal-costura');
    console.log('🖼️ [TOGGLE GALERIA] Galería existente:', !!galeria);
    
    if (!galeria) {
        console.log('🔨 [TOGGLE GALERIA] Creando nueva galería...');
        galeria = document.createElement('div');
        galeria.id = 'galeria-modal-costura';
        galeria.style.cssText = 'width: 100%; margin: 0; padding: 0; display: flex; flex-direction: column; min-height: 400px; max-height: 600px; overflow-y: auto;';
        if (container) {
            container.appendChild(galeria);
            console.log('✅ [TOGGLE GALERIA] Galería creada y agregada al DOM');
        } else {
            console.error('❌ [TOGGLE GALERIA] No se pudo agregar galería, container no encontrado');
            return;
        }
    }
    
    galeria.style.display = 'flex';
    console.log('🖼️ [TOGGLE GALERIA] Galería display establecido a flex');
    
    // Obtener número de pedido directamente del DOM
    const pedidoElement = document.getElementById('order-pedido');
    console.log('🖼️ [TOGGLE GALERIA] Elemento pedido:', pedidoElement);
    
    if (!pedidoElement) {
        console.error('❌ [TOGGLE GALERIA] No se encontró elemento order-pedido');
        galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    
    const pedidoText = pedidoElement.textContent;
    const pedidoMatch = pedidoText.match(/\d+/);
    const pedido = pedidoMatch ? pedidoMatch[0] : null;
    
    console.log('🖼️ [TOGGLE GALERIA] Texto del pedido:', pedidoText);
    console.log('🖼️ [TOGGLE GALERIA] Número de pedido extraído:', pedido);
    
    if (!pedido) {
        console.error('❌ [TOGGLE GALERIA] No se pudo extraer número de pedido');
        galeria.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    
    // Cargar imágenes de costura
    loadGaleria(galeria, pedido);
    
    // Cambiar estilos de botones
    document.getElementById('btn-factura').style.background = 'white';
    document.getElementById('btn-factura').style.border = '2px solid #ddd';
    document.getElementById('btn-factura').style.color = '#333';
    document.getElementById('btn-galeria').style.background = 'linear-gradient(135deg, #1e40af, #0ea5e9)';
    document.getElementById('btn-galeria').style.border = 'none';
    document.getElementById('btn-galeria').style.color = 'white';
    
    console.log('✅ [TOGGLE GALERIA] Completado');
};

/**
 * Carga las imágenes de costura en la galería
 * @param {HTMLElement} container - Contenedor donde mostrar la galería
 * @param {string} pedido - Número de pedido
 */
window.loadGaleria = function loadGaleria(container, pedido) {
    // Validar que tenemos el número de pedido
    if (!pedido) {
        console.error('❌ [GALERIA] No se proporcionó número de pedido');
        container.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error: Número de pedido no disponible</p>';
        return;
    }
    
    console.log('🖼️ [GALERIA] Cargando galería para pedido:', pedido);
    
    // ✅ Remover el # del número de pedido si existe
    const pedidoLimpio = pedido.replace('#', '');
    
    // Cargar imágenes de costura (prenda y tela)
    const url = `/registros/${pedidoLimpio}/images`;
    console.log('🖼️ [GALERIA] Haciendo fetch a:', url);
    
    fetch(url)
        .then(response => {
            console.log('🖼️ [GALERIA] Respuesta recibida:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('🖼️ [GALERIA] Datos recibidos:', data);
            
            // Construir array de todas las imágenes para el visor
            allImagesCostura = [];
            let html = '<div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 12px; margin: 0; border-radius: 0; width: 100%; box-sizing: border-box; position: sticky; top: 0; z-index: 100;">';
            html += '<h2 style="text-align: center; margin: 0; font-size: 1.6rem; font-weight: 700; color: white; letter-spacing: 1px;">GALERÍA DE COSTURA</h2>';
            html += '</div>';
            html += '<div style="padding: 20px; flex: 1; overflow-y: auto;">';
            
            console.log('📦 [GALERIA] Iniciando construcción de galería...');
            
            let totalFotos = 0;
            
            // Mostrar fotos de prendas y telas (agrupadas por prenda)
            // Estructura: data.prendas = [{numero, nombre, imagenes: [{url, type, orden}]}]
            if (data.prendas && data.prendas.length > 0) {
                data.prendas.forEach((prenda, prendaIdx) => {
                    if (prenda.imagenes && prenda.imagenes.length > 0) {
                        // Separar imágenes de prenda y tela
                        const imagenesPrend = prenda.imagenes.filter(img => img.type === 'prenda');
                        const imagenesTela = prenda.imagenes.filter(img => img.type === 'tela');
                        
                        // Mostrar fotos de prenda
                        if (imagenesPrend.length > 0) {
                            const fotosAMostrar = imagenesPrend.slice(0, 4);
                            const fotosOcultas = Math.max(0, imagenesPrend.length - 4);
                            totalFotos += fotosAMostrar.length;
                            
                            console.log(`📸 [GALERIA] Prenda ${prendaIdx + 1} (${prenda.nombre}):`, {
                                fotos_a_mostrar: fotosAMostrar.length,
                                fotos_ocultas: fotosOcultas
                            });
                            
                            html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                                <div style="border-left: 4px solid #2563eb; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #2563eb; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                        PRENDA ${prendaIdx + 1}
                                    </h3>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                            
                            fotosAMostrar.forEach(image => {
                                const imageIndex = allImagesCostura.length;
                                allImagesCostura.push(image.url);
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                    onmouseover="this.style.borderColor='#2563eb'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.2)';"
                                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="mostrarImagenGrande(${imageIndex})">
                                    <img src="${image.url}" alt="Foto prenda" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>`;
                            });
                            
                            html += '</div></div>';
                        }
                        
                        // Mostrar fotos de tela
                        if (imagenesTela.length > 0) {
                            const fotosAMostrar = imagenesTela.slice(0, 4);
                            const fotosOcultas = Math.max(0, imagenesTela.length - 4);
                            totalFotos += fotosAMostrar.length;
                            
                            console.log(`📸 [GALERIA] Tela de Prenda ${prendaIdx + 1}:`, {
                                fotos_a_mostrar: fotosAMostrar.length,
                                fotos_ocultas: fotosOcultas
                            });
                            
                            html += `<div style="margin-bottom: 1.5rem; display: flex; gap: 12px; align-items: flex-start; padding: 0 20px;">
                                <div style="border-left: 4px solid #1d4ed8; padding-left: 12px; display: flex; flex-direction: column; justify-content: flex-start; min-width: 120px;">
                                    <h3 style="font-size: 0.65rem; font-weight: 700; color: #1d4ed8; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                                        TELA ${prendaIdx + 1}
                                    </h3>
                                </div>
                                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px; flex: 1;">`;
                            
                            fotosAMostrar.forEach(image => {
                                const imageIndex = allImagesCostura.length;
                                allImagesCostura.push(image.url);
                                
                                html += `<div style="aspect-ratio: 1; border-radius: 4px; overflow: hidden; background: #f5f5f5; cursor: pointer; border: 2px solid #e5e5e5; transition: all 0.2s ease; box-shadow: 0 2px 4px rgba(0,0,0,0.08);" 
                                    onmouseover="this.style.borderColor='#2563eb'; this.style.transform='scale(1.08)'; this.style.boxShadow='0 4px 12px rgba(37,99,235,0.2)';"
                                    onmouseout="this.style.borderColor='#e5e5e5'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.08)';"
                                    onclick="mostrarImagenGrande(${imageIndex})">
                                    <img src="${image.url}" alt="Foto tela" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>`;
                            });
                            
                            html += '</div></div>';
                        }
                    }
                });
            }
            
            html += '</div>';
            
            if (totalFotos === 0) {
                html = '<p style="text-align: center; color: #999; padding: 2rem;">No hay fotos de costura disponibles para este pedido</p>';
            }
            
            container.innerHTML = html;
            console.log('✅ [GALERIA] Galería cargada con', totalFotos, 'fotos');
        })
        .catch(error => {
            console.error('❌ [GALERIA] Error cargando galería:', error);
            container.innerHTML = '<p style="text-align: center; color: #999; padding: 2rem;">Error al cargar las fotos. Intenta nuevamente.</p>';
        });
};

/**
 * Muestra una imagen en grande en modal
 */
window.mostrarImagenGrande = function mostrarImagenGrande(index) {
    console.log('🖼️ [IMAGEN GRANDE] Abriendo imagen', index);
    currentImageIndexCostura = index;
    
    if (!allImagesCostura || allImagesCostura.length === 0) {
        console.error('❌ [IMAGEN GRANDE] No hay imágenes disponibles');
        return;
    }
    
    // Crear modal si no existe
    let modalImagen = document.getElementById('modal-imagen-grande-costura');
    if (!modalImagen) {
        modalImagen = document.createElement('div');
        modalImagen.id = 'modal-imagen-grande-costura';
        modalImagen.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 10001; padding: 20px;';
        document.body.appendChild(modalImagen);
    }
    
    const img = allImagesCostura[index];
    modalImagen.innerHTML = `
        <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
            <img src="${img}" alt="Foto grande" style="max-width: 90vw; max-height: 90vh; object-fit: contain;">
            
            <button onclick="cerrarImagenGrande()" style="position: absolute; top: 20px; right: 20px; background: white; border: none; color: black; font-size: 28px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                ✕
            </button>
            
            <button onclick="cambiarImagen(-1)" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; color: black; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                ‹
            </button>
            
            <button onclick="cambiarImagen(1)" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: white; border: none; color: black; font-size: 24px; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                ›
            </button>
            
            <div style="position: absolute; bottom: 20px; background: rgba(0,0,0,0.7); color: white; padding: 8px 16px; border-radius: 4px; font-size: 14px;">
                ${index + 1} / ${allImagesCostura.length}
            </div>
        </div>
    `;
    
    modalImagen.style.display = 'flex';
};

/**
 * Cierra el modal de imagen grande
 */
window.cerrarImagenGrande = function cerrarImagenGrande() {
    const modalImagen = document.getElementById('modal-imagen-grande-costura');
    if (modalImagen) {
        modalImagen.style.display = 'none';
    }
};

/**
 * Cambia entre imágenes
 */
window.cambiarImagen = function cambiarImagen(direccion) {
    currentImageIndexCostura += direccion;
    
    if (currentImageIndexCostura < 0) {
        currentImageIndexCostura = allImagesCostura.length - 1;
    } else if (currentImageIndexCostura >= allImagesCostura.length) {
        currentImageIndexCostura = 0;
    }
    
    mostrarImagenGrande(currentImageIndexCostura);
};

console.log('✅ [MODAL] supervisor-pedidos-detail-modal.js cargado correctamente');

