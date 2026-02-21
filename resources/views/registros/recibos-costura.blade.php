@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Table Component -->
            <x-recibos.recibos-costura-table :recibos="$recibos" />
        </div>
    </div>
</div>

<!-- Modal para ver detalles del recibo -->
<div id="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); z-index: 9997; display: none; pointer-events: auto;" onclick="closeModalOverlay()"></div>

<div id="order-detail-modal-wrapper" style="width: 90%; max-width: 672px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9998; pointer-events: auto; display: none;">
    <x-orders-components.order-detail-modal />
</div>

<!-- Modal de Seguimiento del Pedido -->
<x-orders-components.order-tracking-modal />

<!-- Modal de Novedades -->
<x-modals.novedades-edit-modal />

@endsection

@push('styles')
<!-- Styles Component -->
<x-recibos.recibos-costura-styles />
@endpush

@push('scripts')
<!-- Scripts Component -->
<x-recibos.recibos-costura-scripts />

<!-- Script con funciones globales adicionales (solo las que no están en el componente) -->
<script>
// Cargar nombres de prendas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Cargando nombres de prendas en recibos-costura');
    
    // Obtener todas las filas de recibos
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
            // Buscar el enlace del pedido para obtener el pedido_produccion_id
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;
            
            if (enlacePedido) {
                const href = enlacePedido.getAttribute('href');
                const match = href.match(/\/registros\/(\d+)/);
                if (match) {
                    pedidoProduccionId = match[1];
                }
            }
            
            if (pedidoProduccionId) {
                // Obtener el nombre de la primera prenda del pedido
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(response => response.json())
                    .then(datos => {
                        if (datos.data && typeof datos.data === 'object') {
                            datos = datos.data;
                        }
                        
                        if (datos.prendas && Array.isArray(datos.prendas) && datos.prendas.length > 0) {
                            const primeraPrenda = datos.prendas[0];
                            const nombrePrenda = primeraPrenda.nombre || primeraPrenda.nombre_prenda || 'Sin nombre';
                            
                            // Actualizar el texto de la descripción
                            descripcionElemento.textContent = nombrePrenda;
                            console.log(`[CargarNombres] ✅ Prenda actualizada para recibo ${reciboId}: ${nombrePrenda}`);
                        } else {
                            descripcionElemento.textContent = 'Sin prendas';
                        }
                    })
                    .catch(error => {
                        console.error(`[CargarNombres] Error cargando prenda para recibo ${reciboId}:`, error);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
            }
        }
    });
});

function verDetallesRecibo(reciboId) {
    // Buscar la fila del recibo para obtener el pedido_produccion_id
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    if (!fila) {
        alert('No se encontró el recibo');
        return;
    }
    
    console.log(`[verDetallesRecibo] 📌 Fila encontrada para recibo ${reciboId}`);
    
    // Intentar obtener el enlace del pedido para extraer el pedido_produccion_id
    const enlacePedido = fila.querySelector('a[href*="/registros/"]');
    let pedidoId = null;
    
    if (enlacePedido) {
        // Extraer el ID del pedido desde el href
        const href = enlacePedido.getAttribute('href');
        const pedidoIdMatch = href.match(/\/registros\/(\d+)/);
        if (pedidoIdMatch) {
            pedidoId = parseInt(pedidoIdMatch[1]);
            console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde enlace: ${pedidoId}`);
        }
    }
    
    // Si no se encontró el pedidoId, intentar obtenerlo del data-pedido-id
    if (!pedidoId) {
        const pedidoIdAttr = fila.getAttribute('data-pedido-id');
        if (pedidoIdAttr) {
            pedidoId = parseInt(pedidoIdAttr);
            console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde data-pedido-id: ${pedidoId}`);
        }
    }
    
    // Si todavía no hay pedidoId, intentar obtenerlo del dropdown de día de entrega
    if (!pedidoId) {
        const dropdownDiaEntrega = fila.querySelector('.dia-entrega-dropdown');
        if (dropdownDiaEntrega) {
            const dropdownIdAttr = dropdownDiaEntrega.getAttribute('data-orden-id');
            if (dropdownIdAttr) {
                pedidoId = parseInt(dropdownIdAttr);
                console.log(`[verDetallesRecibo] 📋 Pedido ID encontrado desde dropdown día entrega: ${pedidoId}`);
            }
        }
    }
    
    // Si todavía no hay pedidoId, mostrar error detallado
    if (!pedidoId) {
        console.error(`[verDetallesRecibo] ❌ No se pudo encontrar el ID del pedido para el recibo: ${reciboId}`);
        console.log(`[verDetallesRecibo] 🔍 Contenido de la fila:`, fila.innerHTML);
        alert('No se encontró información del pedido asociada a este recibo. El recibo puede no estar correctamente vinculado a un pedido.');
        return;
    }
    
    console.log(`[verDetallesRecibo] ✅ Pedido ID confirmado: ${pedidoId}`);
    
    // Para recibos de costura, necesitamos encontrar la primera prenda del pedido
    // Hacemos una llamada al endpoint para obtener las prendas del pedido
    fetch(`/registros/${pedidoId}/recibos-datos`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(datos => {
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }
            
            if (!datos.prendas || !Array.isArray(datos.prendas) || datos.prendas.length === 0) {
                console.warn('[verDetallesRecibo] No se encontraron prendas para el pedido:', pedidoId);
                alert('No se encontraron prendas para este pedido. No se puede generar el recibo.');
                return;
            }
            
            // Obtener la primera prenda (asumimos que los recibos de costura son para la primera prenda)
            const primeraPrenda = datos.prendas[0];
            const prendaId = primeraPrenda.id;
            
            console.log(`[verDetallesRecibo] ✅ Prenda encontrada: ${prendaId}`);
            
            // Abrir el recibo de costura usando el módulo
            if (window.pedidosRecibosModule) {
                window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, 'costura');
            } else {
                console.error('[verDetallesRecibo] Módulo de recibos no disponible');
                alert('Módulo de recibos no disponible. Por favor recargue la página.');
            }
        })
        .catch(error => {
            console.error('[verDetallesRecibo] Error al obtener datos del pedido:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
}

// Función para abrir el modal de seguimiento
function abrirModalSeguimiento(pedidoId) {
    // Cerrar cualquier menú abierto
    document.querySelectorAll('.action-menu').forEach(m => {
        m.classList.remove('show', 'active');
    });
    
    console.log('Abriendo seguimiento para el pedido:', pedidoId);
    
    // Inicializar datos del pedido para el tracking modal
    if (typeof openOrderTracking === 'function') {
        console.log('[abrirModalSeguimiento] Llamando a openOrderTracking para inicializar datos');
        openOrderTracking(pedidoId, false).then(() => {
            console.log('[abrirModalSeguimiento] Datos inicializados, abriendo modal directamente');
            
            // NO mostrar el selector de prendas, abrir directamente el seguimiento
            // con la primera prenda disponible
            console.log('[abrirModalSeguimiento] Estructura de currentOrderData:', window.currentOrderData);
            console.log('[abrirModalSeguimiento] currentOrderData.prendas:', window.currentOrderData?.prendas);
            console.log('[abrirModalSeguimiento] currentOrderData.data?.prendas:', window.currentOrderData?.data?.prendas);
            console.log('[abrirModalSeguimiento] window.prendasData:', window.prendasData);
            
            // Intentar encontrar prendas en diferentes estructuras posibles
            let prendas = null;
            if (window.currentOrderData && window.currentOrderData.prendas) {
                prendas = window.currentOrderData.prendas;
            } else if (window.currentOrderData && window.currentOrderData.data && window.currentOrderData.data.prendas) {
                prendas = window.currentOrderData.data.prendas;
            } else if (window.prendasData && window.prendasData.length > 0) {
                prendas = window.prendasData;
            }
            
            console.log('[abrirModalSeguimiento] Prendas encontradas:', prendas);
            
            if (prendas && prendas.length > 0) {
                const primeraPrenda = prendas[0];
                console.log('[abrirModalSeguimiento] Abriendo seguimiento directamente con la primera prenda:', primeraPrenda);
                
                // Inicializar currentPrendaData
                window.currentPrendaData = primeraPrenda;
                
                // Abrir directamente el modal de seguimiento
                abrirModalSeguimientoDirecto(pedidoId);
            } else {
                console.warn('[abrirModalSeguimiento] No hay prendas disponibles en ninguna estructura');
                console.warn('[abrirModalSeguimiento] Estructura completa de currentOrderData:', JSON.stringify(window.currentOrderData, null, 2));
                console.warn('[abrirModalSeguimiento] Estructura completa de window.prendasData:', JSON.stringify(window.prendasData, null, 2));
                
                // Como fallback, abrir el selector de prendas para que el usuario pueda seleccionar manualmente
                console.log('[abrirModalSeguimiento] Abriendo selector de prendas como fallback');
                if (typeof showPrendasSelector === 'function') {
                    showPrendasSelector();
                } else {
                    alert('No hay prendas disponibles para este pedido');
                }
            }
        }).catch(error => {
            console.error('[abrirModalSeguimiento] Error al inicializar datos:', error);
            alert('Error al cargar los datos del pedido: ' + error.message);
        });
    } else {
        console.warn('[abrirModalSeguimiento] openOrderTracking no disponible');
        alert('Sistema de seguimiento no disponible');
    }
}

// Función para abrir el modal de seguimiento directamente sin selector
function abrirModalSeguimientoDirecto(pedidoId) {
    
    // Diagnóstico: verificar si los elementos existen
    // Abrir el overlay del modal de seguimiento
    const trackingOverlay = document.getElementById('trackingModalOverlay');
    if (trackingOverlay) {
        trackingOverlay.style.display = 'block';
        console.log('Overlay de seguimiento abierto');
    } else {
        console.warn('Modal de seguimiento no encontrado');
        alert('Modal de seguimiento no disponible');
        return;
    }
    
    // Abrir el contenido del modal
    const trackingModal = document.getElementById('orderTrackingModal');
    if (trackingModal) {
        trackingModal.style.display = 'flex';
        trackingModal.classList.add('show');
        console.log('Contenido del modal de seguimiento abierto');
        
        // Obtener el consecutivo de costura y fecha de creación para este pedido
        fetch(`/registros/${pedidoId}/consecutivo-costura`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.consecutivo) {
                    // Actualizar el campo N° Recibo con el consecutivo de costura
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) {
                        reciboElement.textContent = data.consecutivo;
                        console.log('Consecutivo de costura asignado:', data.consecutivo);
                    }
                    
                    // Actualizar el subtítulo del header con el número del recibo
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) {
                        headerSubtitleElement.textContent = `COSTURA #${data.consecutivo}`;
                        console.log('Subtítulo del header actualizado:', `COSTURA #${data.consecutivo}`);
                    }
                } else {
                    console.warn('No se encontró consecutivo de costura para el pedido:', pedidoId);
                    const reciboElement = document.getElementById('trackingOrderRecibo');
                    if (reciboElement) {
                        reciboElement.textContent = '-';
                    }
                    
                    const headerSubtitleElement = document.getElementById('trackingPrendaReciboHeader');
                    if (headerSubtitleElement) {
                        headerSubtitleElement.textContent = 'COSTURA #?';
                    }
                }
                
                // Actualizar la fecha de inicio si está disponible
                if (data.fecha_creacion) {
                    const fechaElement = document.getElementById('trackingOrderDate');
                    if (fechaElement) {
                        // Formatear la fecha a dd/mm/yyyy
                        const fecha = new Date(data.fecha_creacion);
                        const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric'
                        });
                        fechaElement.textContent = fechaFormateada;
                        console.log('Fecha de inicio asignada:', fechaFormateada);
                    }
                }
                
                // Ahora mostrar directamente el seguimiento de la primera prenda
                console.log('[abrirModalSeguimientoDirecto] Llamando a showPrendaTracking con la primera prenda');
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                } else {
                    console.error('[abrirModalSeguimientoDirecto] showPrendaTracking o currentPrendaData no disponibles');
                }
            })
            .catch(error => {
                console.error('Error al obtener consecutivo de costura:', error);
                const reciboElement = document.getElementById('trackingOrderRecibo');
                if (reciboElement) {
                    reciboElement.textContent = 'COSTURA #?';
                }
                
                // Intentar mostrar el seguimiento de todos modos
                if (typeof showPrendaTracking === 'function' && window.currentPrendaData) {
                    showPrendaTracking(window.currentPrendaData);
                }
            });
        
        console.log('Modal abierto usando estilos CSS del componente');
    } else {
        console.warn('Contenido del modal de seguimiento no encontrado');
    }
}

// Función para menú de acciones (similar a la de registros)
document.addEventListener('click', function(e) {
    // Manejo del botón de acción principal - SOLO si no fue manejado por el listener individual
    if (e.target.closest('.action-view-btn')) {
        // No hacer nada aquí, dejar que el listener individual lo maneje
        return;
    }
    
    // Cerrar menús al hacer clic fuera (excluyendo clicks en botones y menús)
    if (!e.target.closest('.action-view-btn') && !e.target.closest('.action-menu')) {
        document.querySelectorAll('.action-menu').forEach(m => {
            m.classList.remove('show', 'active');
            m.style.cssText = '';
        });
    }
    
    // Cerrar menú al hacer clic en una opción
    if (e.target.closest('.action-menu-item')) {
        // Cerrar todos los menús después de un pequeño delay para permitir la acción
        setTimeout(() => {
            document.querySelectorAll('.action-menu').forEach(m => {
                m.classList.remove('show', 'active');
                m.style.cssText = '';
            });
        }, 100);
    }
});

// Función para cerrar el modal overlay
function closeModalOverlay() {
    const modal = document.getElementById('modal-overlay');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Ocultar el botón Volver específicamente en recibos-costura
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Inicializando menús de acción en recibos-costura');
    
    // Verificar si estamos en la vista de recibos-costura
    if (window.location.pathname.includes('/recibos-costura')) {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver) {
            botonVolver.style.display = 'none';
            console.log('Botón Volver ocultado en recibos-costura');
        }
        
        // Verificar que los botones de acción existen
        const botonesAccion = document.querySelectorAll('.action-view-btn');
        console.log(`[Menu] Se encontraron ${botonesAccion.length} botones de acción`);
        
        const menus = document.querySelectorAll('.action-menu');
        console.log(`[Menu] Se encontraron ${menus.length} menús de acción`);
        
        // Agregar listeners individuales para debugging
        botonesAccion.forEach((btn, index) => {
            const ordenId = btn.dataset.ordenId;
            console.log(`[Menu] Botón ${index}: orden-id=${ordenId}`);
            
            btn.addEventListener('click', function(e) {
                console.log(`[Menu] Click en botón para orden ${ordenId}`);
                e.preventDefault();
                e.stopPropagation();
                
                // Buscar el menú específico
                const menu = document.querySelector(`.action-menu[data-orden-id="${ordenId}"]`);
                console.log(`[Menu] Menú encontrado:`, menu);
                
                if (menu) {
                    // Cerrar otros menús
                    document.querySelectorAll('.action-menu').forEach(m => {
                        if (m !== menu) {
                            m.classList.remove('show', 'active');
                        }
                    });
                    
                    // Toggle este menú
                    const wasVisible = menu.classList.contains('show');
                    console.log(`[Menu] Estado anterior del menú: ${wasVisible}`);
                    
                    if (!wasVisible) {
                        // Abrir menú
                        menu.classList.add('show');
                        menu.classList.add('active');
                        
                        // Asegurar que el contenedor padre permita overflow
                        const td = btn.closest('td');
                        if (td) {
                            td.style.overflow = 'visible';
                        }
                        
                        // Aplicar estilos inline forzosamente (estilo contador - horizontal)
                        menu.style.cssText = `
                            position: absolute !important;
                            top: 50% !important;
                            left: 85px !important;
                            transform: translateY(-50%) !important;
                            background: white !important;
                            border: 1px solid #e0e6ed !important;
                            border-radius: 8px !important;
                            box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.08) !important;
                            z-index: 9999 !important;
                            min-width: 180px !important;
                            opacity: 1 !important;
                            visibility: visible !important;
                            pointer-events: auto !important;
                            display: block !important;
                        `;
                        
                        // Forzar estilos en los items del menú también
                        const menuItems = menu.querySelectorAll('.action-menu-item');
                        menuItems.forEach(item => {
                            item.style.cssText = `
                                display: flex !important;
                                align-items: center !important;
                                gap: 10px !important;
                                padding: 12px 16px !important;
                                color: #2c3e50 !important;
                                text-decoration: none !important;
                                font-size: 14px !important;
                                font-weight: 500 !important;
                                border-bottom: 1px solid #e5e7eb !important;
                                transition: all 0.2s ease !important;
                                cursor: pointer !important;
                                background: white !important;
                            `;
                        });
                        
                        // Ajustar posición si está fuera de la pantalla (estilo contador)
                        setTimeout(() => {
                            const rect = menu.getBoundingClientRect();
                            const windowWidth = window.innerWidth;
                            
                            console.log(`[Menu] Posición del menú (estilo contador):`, {
                                top: rect.top,
                                left: rect.left,
                                right: rect.right,
                                bottom: rect.bottom,
                                width: rect.width,
                                height: rect.height
                            });
                            
                            if (rect.right > windowWidth) {
                                menu.style.left = 'auto';
                                menu.style.right = '85px';
                                console.log('[Menu] Ajustado posición a la izquierda (estilo contador)');
                            }
                        }, 10);
                        
                        console.log(`[Menu] Menú abierto para orden ${ordenId}`);
                        console.log(`[Menu] Estilos aplicados:`, menu.style.cssText);
                    } else {
                        // Cerrar menú
                        menu.classList.remove('show');
                        menu.classList.remove('active');
                        menu.style.cssText = '';
                        console.log(`[Menu] Menú cerrado para orden ${ordenId}`);
                    }
                    
                    console.log(`[Menu] Menú toggle para orden ${ordenId}: ${!wasVisible}`);
                } else {
                    console.warn(`[Menu] No se encontró menú para orden ${ordenId}`);
                }
            });
        });
    }
});
</script>
@endpush
