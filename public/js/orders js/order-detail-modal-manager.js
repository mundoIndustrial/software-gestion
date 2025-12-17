/**
 * Order Detail Modal Manager para Registro de Órdenes
 * Maneja la apertura y cierre del modal de detalles de orden
 * SINCRONIZADO CON: pedidos-detail-modal.js (asesores)
 */

console.log('📄 [MODAL] Cargando order-detail-modal-manager.js');

/**
 * Abre el modal de detalle de la orden
 * Compatible con la estructura de asesores
 */
window.openOrderDetailModal = function(orderId) {
    console.log('%c🔵 [MODAL] Abriendo modal para orden: ' + orderId, 'color: blue; font-weight: bold; font-size: 14px;');
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');
    console.log('🔍 [MODAL] Overlay encontrado:', !!overlay);
    
    if (overlay) {
        // Mover al body si es necesario
        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
        
        // Mostrar overlay
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
            modalWrapper.style.display = 'block';
            modalWrapper.style.zIndex = '9998';
            modalWrapper.style.position = 'fixed';
            modalWrapper.style.top = '60%';
            modalWrapper.style.left = '50%';
            modalWrapper.style.transform = 'translate(-50%, -50%)';
            modalWrapper.style.pointerEvents = 'auto';
            console.log('✅ [MODAL] Wrapper mostrado');
        } else {
            console.error('❌ [MODAL] Wrapper no encontrado');
        }
    }
};

/**
 * Cierra el modal de detalle de la orden
 */
window.closeOrderDetailModal = function() {
    console.log('%c🔵 [MODAL] Cerrando modal', 'color: blue; font-weight: bold; font-size: 14px;');
    
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    
    if (overlay) {
        overlay.style.display = 'none';
        console.log('✅ [MODAL] Overlay ocultado');
    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
        console.log('✅ [MODAL] Wrapper ocultado');
    }
};

/**
 * Cierra el modal al hacer click en el overlay
 */
window.closeModalOverlay = function() {
    console.log('🔵 [MODAL] Click en overlay, cerrando...');
    window.closeOrderDetailModal();
};

/**
 * Estado global para navegación de prendas
 */
window.prendasState = {
    todasLasPrendas: [],
    currentPage: 0,
    prendasPorPagina: 2,
    esCotizacion: false
};

/**
 * Renderizar datos de la orden en el modal
 */
function renderOrderDetail(orden) {
    console.log('🎨 [MODAL] Renderizando detalles de orden:', orden.numero_pedido);
    
    // Guardar estado de prendas
    window.prendasState.todasLasPrendas = orden.prendas || [];
    window.prendasState.currentPage = 0;
    window.prendasState.esCotizacion = orden.es_cotizacion || false;
    
    // Llenar fecha
    const dayBox = document.querySelector('.day-box');
    const monthBox = document.querySelector('.month-box');
    const yearBox = document.querySelector('.year-box');
    
    if (dayBox && monthBox && yearBox) {
        const fecha = new Date(orden.fecha_de_creacion_de_orden);
        if (!isNaN(fecha.getTime())) {
            const dia = String(fecha.getDate()).padStart(2, '0');
            const mes = String(fecha.getMonth() + 1).padStart(2, '0');
            const año = fecha.getFullYear();
            
            dayBox.textContent = dia;
            monthBox.textContent = mes;
            yearBox.textContent = año;
        }
    }
    
    // Llenar cliente
    const clienteValue = document.getElementById('cliente-value');
    if (clienteValue) clienteValue.textContent = orden.cliente || '-';
    
    // Llenar asesora
    const asesoraValue = document.getElementById('asesora-value');
    if (asesoraValue) asesoraValue.textContent = orden.asesora || '-';
    
    // Llenar forma de pago
    const formaPagoValue = document.getElementById('forma-pago-value');
    if (formaPagoValue) formaPagoValue.textContent = orden.forma_de_pago || '-';
    
    // Renderizar prendas con paginación
    renderPrendasPage();
    
    // Llenar pedido número
    const pedidoNumber = document.querySelector('.pedido-number');
    if (pedidoNumber) {
        pedidoNumber.textContent = `#${orden.numero_pedido}`;
    }
    
    // Llenar encargado de orden
    const encargadoValue = document.getElementById('encargado-value');
    if (encargadoValue) encargadoValue.textContent = orden.encargado_orden || '-';
    
    // Llenar prendas entregadas
    const prendasValue = document.getElementById('prendas-entregadas-value');
    if (prendasValue) {
        prendasValue.textContent = `${orden.total_entregado || 0}/${orden.cantidad_total || orden.cantidad || 0}`;
    }
    
    // Actualizar visibilidad de flechas de navegación
    updateNavigationArrows();
    
    console.log('✅ [MODAL] Detalles renderizados');
}

/**
 * Renderizar página actual de prendas
 */
function renderPrendasPage() {
    const { todasLasPrendas, currentPage, prendasPorPagina, esCotizacion } = window.prendasState;
    
    if (!todasLasPrendas || todasLasPrendas.length === 0) {
        const descripcionText = document.getElementById('descripcion-text');
        if (descripcionText) {
            descripcionText.innerHTML = '-';
        }
        return;
    }
    
    // Calcular índices de inicio y fin
    const startIndex = currentPage * prendasPorPagina;
    const endIndex = startIndex + prendasPorPagina;
    const prendasActuales = todasLasPrendas.slice(startIndex, endIndex);
    
    let descripcionHTML = '';
    
    // SIEMPRE generar descripción dinámica (sin importar si es cotización o no)
    {
        // Generar descripción dinámica para pedidos
        prendasActuales.forEach((prenda, index) => {
            console.log('🔍 [PRENDA] Datos completos de prenda:', JSON.stringify(prenda, null, 2));
            console.log('🔍 [PRENDA] Keys disponibles:', Object.keys(prenda));
            console.log('🔍 [PRENDA] Color:', prenda.color);
            console.log('🔍 [PRENDA] Tela:', prenda.tela);
            console.log('🔍 [PRENDA] Tipo manga:', prenda.tipo_manga);
            console.log('🔍 [PRENDA] Cantidad talla:', prenda.cantidad_talla);
            
            let html = '';
            
            // 1. Nombre de la prenda
            html += `<strong>PRENDA ${prenda.numero}: ${prenda.nombre.toUpperCase()}</strong><br>`;
            
            // 2. Línea de atributos: Color | Tela | Manga (con observación de manga si existe)
            const atributos = [];
            if (prenda.color) {
                atributos.push(`<strong>Color:</strong> ${prenda.color.toUpperCase()}`);
            }
            if (prenda.tela) {
                let telaTexto = prenda.tela.toUpperCase();
                if (prenda.tela_referencia) {
                    telaTexto += ` REF:${prenda.tela_referencia.toUpperCase()}`;
                }
                atributos.push(`<strong>Tela:</strong> ${telaTexto}`);
            }
            if (prenda.tipo_manga) {
                let mangaTexto = prenda.tipo_manga.toUpperCase();
                // Agregar observación de manga si existe en descripcion_variaciones
                if (prenda.descripcion_variaciones) {
                    const mangaMatch = prenda.descripcion_variaciones.match(/Manga:\s*(.+?)(?:\s*\||$)/i);
                    if (mangaMatch) {
                        const observacionManga = mangaMatch[1].trim().toUpperCase();
                        // Solo agregar si es diferente al tipo de manga
                        if (observacionManga !== mangaTexto) {
                            mangaTexto += ` (${observacionManga})`;
                        }
                    }
                }
                atributos.push(`<strong>Manga:</strong> ${mangaTexto}`);
            }
            
            if (atributos.length > 0) {
                html += atributos.join(' | ') + '<br>';
            }
            
            // 3. DESCRIPCION con viñetas (sin manga, solo reflectivo, bolsillos y broche si existe)
            if (prenda.descripcion_variaciones) {
                const descripcionVar = prenda.descripcion_variaciones;
                const partes = [];
                
                // Reflectivo
                const reflectivoMatch = descripcionVar.match(/Reflectivo:\s*(.+?)(?:\s*\||$)/i);
                if (reflectivoMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Reflectivo:</strong> ${reflectivoMatch[1].trim().toUpperCase()}`);
                }
                
                // Bolsillos
                const bolsillosMatch = descripcionVar.match(/Bolsillos:\s*(.+?)(?:\s*\||$)/i);
                if (bolsillosMatch) {
                    partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">Bolsillos:</strong> ${bolsillosMatch[1].trim().toUpperCase()}`);
                }
                
                // Broche/Botón - SOLO si existe tipo_broche en los datos (label dinámico según el tipo)
                if (prenda.tipo_broche) {
                    const brocheMatch = descripcionVar.match(/Broche:\s*(.+?)(?:\s*\||$)/i);
                    if (brocheMatch) {
                        // Usar el tipo_broche como label (ej: "Botón", "Broche", etc.)
                        const tipoLabel = prenda.tipo_broche.toUpperCase();
                        const observacion = brocheMatch[1].trim().toUpperCase();
                        partes.push(`<strong style="margin-left: 1.5em;">•</strong> <strong style="color: #000;">${tipoLabel}:</strong> ${observacion}`);
                    }
                }
                
                // NO incluir manga aquí - ya está en la línea principal
                
                if (partes.length > 0) {
                    html += '<strong>DESCRIPCION:</strong><br>';
                    html += partes.join('<br>') + '<br>';
                }
            } else if (prenda.descripcion && prenda.descripcion !== '-') {
                // Fallback: usar descripción guardada si no hay descripcion_variaciones
                html += `<strong>DESCRIPCION:</strong> ${prenda.descripcion.toUpperCase()}<br>`;
            }
            
            // 4. Tallas
            if (prenda.cantidad_talla && prenda.cantidad_talla !== '-') {
                try {
                    const tallas = typeof prenda.cantidad_talla === 'string' 
                        ? JSON.parse(prenda.cantidad_talla) 
                        : prenda.cantidad_talla;
                    
                    const tallasFormateadas = [];
                    for (const [talla, cantidad] of Object.entries(tallas)) {
                        if (cantidad > 0) {
                            tallasFormateadas.push(`${talla}: ${cantidad}`);
                        }
                    }
                    
                    if (tallasFormateadas.length > 0) {
                        html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${tallasFormateadas.join(', ')}</span>`;
                    }
                } catch (e) {
                    html += `<strong>Tallas:</strong> <span style="color: #d32f2f; font-weight: bold;">${prenda.cantidad_talla}</span>`;
                }
            }
            
            descripcionHTML += `<div class="prenda-item" style="margin-bottom: 16px; line-height: 1.4; font-size: 0.75rem; color: #333;">
                ${html}
            </div>`;
            
            // Agregar separador solo entre prendas mostradas
            if (index < prendasActuales.length - 1) {
                descripcionHTML += `<hr style="border: none; border-top: 2px solid #ccc; margin: 16px 0;">`;
            }
        });
    }
    
    const descripcionText = document.getElementById('descripcion-text');
    if (descripcionText) {
        descripcionText.innerHTML = descripcionHTML;
    }
}

/**
 * Actualizar visibilidad de flechas de navegación
 */
function updateNavigationArrows() {
    const { todasLasPrendas, currentPage, prendasPorPagina } = window.prendasState;
    const totalPages = Math.ceil(todasLasPrendas.length / prendasPorPagina);
    
    const prevArrow = document.getElementById('prev-arrow');
    const nextArrow = document.getElementById('next-arrow');
    
    if (prevArrow) {
        prevArrow.style.display = currentPage > 0 ? 'block' : 'none';
    }
    
    if (nextArrow) {
        nextArrow.style.display = currentPage < totalPages - 1 ? 'block' : 'none';
    }
}

/**
 * Navegar a la página anterior
 */
window.prevPrendas = function() {
    if (window.prendasState.currentPage > 0) {
        window.prendasState.currentPage--;
        renderPrendasPage();
        updateNavigationArrows();
    }
};

/**
 * Navegar a la página siguiente
 */
window.nextPrendas = function() {
    const { todasLasPrendas, currentPage, prendasPorPagina } = window.prendasState;
    const totalPages = Math.ceil(todasLasPrendas.length / prendasPorPagina);
    
    if (currentPage < totalPages - 1) {
        window.prendasState.currentPage++;
        renderPrendasPage();
        updateNavigationArrows();
    }
};

/**
 * Escuchar el evento de apertura del modal
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('%c✅ [MODAL] DOM cargado, registrando listeners', 'color: green; font-weight: bold; font-size: 14px;');
    
    // Listener para cargar datos de la orden
    window.addEventListener('load-order-detail', function(event) {
        console.log('%c📦 [MODAL] Evento load-order-detail recibido', 'color: orange; font-weight: bold; font-size: 14px;');
        const orden = event.detail;
        renderOrderDetail(orden);

        // Cargar imágenes de la orden si el módulo está disponible
        if (typeof loadOrderImages === 'function') {
            try {
                loadOrderImages(orden.numero_pedido);
            } catch (err) {
                console.warn('⚠️ Error cargando imágenes de la orden:', err);
            }
        }

        window.openOrderDetailModal();
    });
    
    // Listener para abrir el modal
    window.addEventListener('open-modal', function(event) {
        console.log('%c🔔 [MODAL] Evento open-modal recibido', 'color: purple; font-weight: bold; font-size: 14px;');
        console.log('   - detail:', event.detail);
        
        if (event.detail === 'order-detail') {
            console.log('%c✅ [MODAL] Detail es "order-detail", abriendo...', 'color: green; font-weight: bold;');
            window.openOrderDetailModal();
        }
    });
    
    // Listener para cerrar el modal
    window.addEventListener('close-modal', function(event) {
        if (event.detail === 'order-detail') {
            console.log('🔵 [MODAL] Evento close-modal recibido');
            window.closeOrderDetailModal();
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const overlay = document.getElementById('modal-overlay');
            if (overlay && overlay.style.display !== 'none') {
                console.log('🔵 [MODAL] ESC presionado, cerrando modal');
                window.closeOrderDetailModal();
            }
        }
    });
    
    // Listeners para botones de navegación de prendas
    const prevArrow = document.getElementById('prev-arrow');
    const nextArrow = document.getElementById('next-arrow');
    
    if (prevArrow) {
        prevArrow.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('⬅️ [MODAL] Flecha anterior presionada');
            window.prevPrendas();
        });
    }
    
    if (nextArrow) {
        nextArrow.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('➡️ [MODAL] Flecha siguiente presionada');
            window.nextPrendas();
        });
    }
    
    console.log('✅ [MODAL] Listeners registrados');
});
