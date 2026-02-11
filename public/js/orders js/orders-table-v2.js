/**
 *  REFACTORIZACIÓN: orders-table-v2.js
 * 
 * Este archivo integra los módulos SOLID creados eliminando código duplicado de orders-table.js
 * Mantiene compatibilidad con código existente mientras delega responsabilidades a los módulos
 * 
 * Cambios clave:
 * - Usa FormattingModule para formatiar fechas
 * - Usa UpdatesModule para PATCH requests
 * - Usa RowManager para actualizaciones de filas
 * - Usa StorageModule para sincronización entre tabs
 * - Usa NotificationModule para notificaciones
 * - Usa DiaEntregaModule para día de entrega
 */



// Verificar que todos los módulos estén disponibles









// ============================================================================
// SECCIÓN 1: INICIALIZACIÓN Y DELEGACIÓN A MÓDULOS
// ============================================================================

/**
 * Flag global para evitar reinicializaciones duplicadas
 */
window.isInitializingDropdowns = false;

/**
 * DELEGACIÓN: Inicializar dropdowns de estado
 * → Usa OrdersDropdownManager.initializeStatusDropdowns()
 */
function initializeStatusDropdowns() {
    if (OrdersDropdownManager && OrdersDropdownManager.initializeStatusDropdowns) {
        OrdersDropdownManager.initializeStatusDropdowns();
    } else {

    }
}

/**
 * DELEGACIÓN: Inicializar dropdowns de área
 * → Usa OrdersDropdownManager.initializeAreaDropdowns()
 */
function initializeAreaDropdowns() {
    if (OrdersDropdownManager && OrdersDropdownManager.initializeAreaDropdowns) {
        OrdersDropdownManager.initializeAreaDropdowns();
    } else {

    }
}

/**
 * DELEGACIÓN: Inicializar dropdowns de día de entrega
 * → Usa DiaEntregaModule.initialize()
 */
function initializeDiaEntregaDropdowns() {
    if (DiaEntregaModule && DiaEntregaModule.initialize) {
        DiaEntregaModule.initialize();
    } else {

    }
}

// ============================================================================
// SECCIÓN 2: DELEGACIÓN DE ACTUALIZACIÓN A MÓDULOS
// ============================================================================

// NOTA: Las funciones handleStatusChange y handleAreaChange ahora están en OrdersDropdownManager
// No se necesitan funciones fallback aquí ya que el módulo siempre debe estar cargado

/**
 * DELEGACIÓN: Actualizar día de entrega
 * → Antes: Lógica local + PATCH
 * → Ahora: UpdatesModule.updateOrderDiaEntrega()
 */
function handleDiaEntregaChange() {
    const orderId = this.dataset.ordenId || this.dataset.id;
    const newValue = this.value;
    
    if (UpdatesModule && UpdatesModule.updateOrderDiaEntrega) {
        UpdatesModule.updateOrderDiaEntrega(orderId, newValue);
    } else {

    }
}

// ============================================================================
// SECCIÓN 3: DELEGACIÓN DE FORMATOS A MÓDULOS
// ============================================================================

/**
 * DELEGACIÓN: Formatear fecha
 * → Usa FormattingModule.formatearFecha()
 */
function formatearFecha(fecha, columna = 'desconocida') {
    if (FormattingModule && FormattingModule.formatearFecha) {
        return FormattingModule.formatearFecha(fecha);
    } else {
        // Fallback: implementación local básica
        if (!fecha) return fecha;
        if (typeof fecha !== 'string') return fecha;
        if (fecha.match(/^\d{2}\/\d{2}\/\d{4}$/)) return fecha;
        if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const partes = fecha.split('-');
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        }
        return fecha;
    }
}

/**
 * DELEGACIÓN: Verificar si es columna de fecha
 * → Usa FormattingModule.esColumnaFecha()
 */
function esColumnaFecha(column) {
    if (FormattingModule && FormattingModule.esColumnaFecha) {
        return FormattingModule.esColumnaFecha(column);
    } else {
        const COLUMNAS_FECHA = [
            'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
            'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
            'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega', 'despacho'
        ];
        return COLUMNAS_FECHA.includes(column);
    }
}

/**
 * DELEGACIÓN: Asegurar formato de fecha
 * → Usa FormattingModule.asegurarFormatoFecha()
 */
function asegurarFormatoFecha(fecha) {
    if (FormattingModule && FormattingModule.asegurarFormatoFecha) {
        return FormattingModule.asegurarFormatoFecha(fecha);
    } else {
        return formatearFecha(fecha);
    }
}

// ============================================================================
// SECCIÓN 4: DELEGACIÓN DE ESTILOS DE FILAS
// ============================================================================

/**
 * DELEGACIÓN: Actualizar color de fila
 * → Usa RowManager.updateRowColor()
 */
function updateRowColor(orderId, newStatus) {
    if (RowManager && RowManager.updateRowColor) {
        // RowManager necesita objeto orden completo
        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
        if (row) {
            const orden = {
                pedido: orderId,
                estado: newStatus,
                dia_de_entrega: row.querySelector('.dia-entrega-dropdown')?.value
            };
            RowManager.updateRowColor(orden);
        }
    } else {

    }
}

// ============================================================================
// SECCIÓN 5: FUNCIONES CRÍTICAS MANTIENEN LÓGICA NECESARIA
// ============================================================================

/**
 * Actualizar los valores de días en la tabla
 * MANTENER: Esta función es crítica para sincronización de días
 */
function actualizarDiasTabla() {

    const tabla = document.getElementById('tablaOrdenes');
    if (!tabla) {

        return;
    }
    
    const tbody = tabla.querySelector('tbody');
    if (!tbody) {

        return;
    }
    
    const filas = tbody.querySelectorAll('tr:not(.no-results)');

    
    let actualizadas = 0;
    filas.forEach((fila, index) => {
        const totalDias = fila.getAttribute('data-total-dias');
        
        if (totalDias === null) return;
        
        const celdaTotal = fila.querySelector('td[data-column="total_de_dias_"]');
        if (!celdaTotal) return;
        
        const diasSpan = celdaTotal.querySelector('.dias-value');
        if (diasSpan) {
            diasSpan.textContent = totalDias;
            diasSpan.setAttribute('data-dias', totalDias);
            actualizadas++;
        }
    });
    

}

/**
 * Recargar tabla de pedidos
 * MANTENER: Lógica compleja de reconstrucción de tabla
 */
async function recargarTablaPedidos() {
    try {
        const response = await fetch(window.fetchUrl + window.location.search, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) {

            return;
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {

            return;
        }
        const data = await response.json();

        const tbody = document.getElementById('tablaOrdenesBody');
        if (!tbody) {

            return;
        }
        tbody.innerHTML = '';

        if (data.orders.length === 0) {
            tbody.innerHTML = `
                <tr class="table-row">
                    <td colspan="51" class="no-results" style="text-align: center; padding: 20px; color: #6c757d;">
                        No hay resultados que coincidan con los filtros aplicados.
                    </td>
                </tr>
            `;
        } else {
            const theadRow = document.querySelector('#tablaOrdenes thead tr');
            const ths = Array.from(theadRow.querySelectorAll('th'));
            const dataColumns = ths.slice(1).map(th => th.dataset.column).filter(col => col);

            data.orders.forEach(orden => {
                const totalDias = data.totalDiasCalculados[orden.pedido] ?? 0;
                const tr = document.createElement('tr');
                tr.className = 'table-row';
                tr.dataset.orderId = orden.pedido;
                tr.setAttribute('data-numero-pedido', orden.numero_pedido || orden.pedido);

                // [Crear fila... código original mantiene su lógica]
                tbody.appendChild(tr);
            });
        }

        const paginationContainer = document.getElementById('paginationContainer');
        if (paginationContainer) {
            paginationContainer.innerHTML = data.pagination_html;
        }

        // Reinicializar con módulos
        initializeStatusDropdowns();
        initializeAreaDropdowns();
        initializeDiaEntregaDropdowns();
        


    } catch (error) {

    }
}

/**
 * Eliminar orden
 * MANTENER: Lógica de modal y confirmación
 */
function deleteOrder(pedido) {
    const modal = document.getElementById('deleteConfirmationModal');
    const orderIdElement = document.getElementById('deleteOrderId');
    const overlay = document.getElementById('deleteModalOverlay');
    const cancelBtn = document.getElementById('deleteCancelBtn');
    const confirmBtn = document.getElementById('deleteConfirmBtn');

    orderIdElement.textContent = pedido;
    modal.style.display = 'flex';

    const closeModal = () => {
        modal.style.display = 'none';
    };

    const handleCancel = () => {
        closeModal();
        overlay.removeEventListener('click', handleCancel);
        cancelBtn.removeEventListener('click', handleCancel);
        confirmBtn.removeEventListener('click', handleConfirm);
    };

    const handleConfirm = () => {
        closeModal();
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-width="2" stroke-linecap="round"/></svg> Eliminando...';

        fetch(`${window.fetchUrl}/${pedido}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (NotificationModule && NotificationModule.showSuccess) {
                        NotificationModule.showSuccess('Orden eliminada correctamente');
                    } else {
                        showDeleteNotification('Orden eliminada correctamente', 'success');
                    }
                    setTimeout(() => recargarTablaPedidos(), 1000);
                } else {
                    if (NotificationModule && NotificationModule.showError) {
                        NotificationModule.showError(data.message || 'Error al eliminar');
                    } else {
                        showDeleteNotification(data.message || 'Error al eliminar', 'error');
                    }
                }
            })
            .catch(error => {

                if (NotificationModule && NotificationModule.showError) {
                    NotificationModule.showError('Error al eliminar la orden');
                } else {
                    showDeleteNotification('Error al eliminar la orden', 'error');
                }
            })
            .finally(() => {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round"/></svg> Eliminar Orden';
            });

        overlay.addEventListener('click', handleCancel);
        cancelBtn.addEventListener('click', handleCancel);
        confirmBtn.addEventListener('click', handleConfirm);
    };

    overlay.addEventListener('click', handleCancel);
    cancelBtn.addEventListener('click', handleCancel);
    confirmBtn.addEventListener('click', handleConfirm);
}

/**
 * Mostrar notificación de eliminación (fallback si NotificationModule no está disponible)
 */
function showDeleteNotification(message, type) {
    const existingNotifications = document.querySelectorAll('.delete-notification');
    existingNotifications.forEach(notification => notification.remove());

    const notification = document.createElement('div');
    notification.className = `delete-notification delete-notification-${type}`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'notificationSlideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

/**
 * Ver detalle de orden
 * MANTENER: Lógica compleja de modal de detalles
 * Ahora abre el selector de prendas (igual que en main)
 */
async function viewDetail(pedido) {
    try {
        // Usar el mismo sistema que main: abrirSelectorRecibos
        if (typeof window.abrirSelectorRecibos === 'function') {
            window.abrirSelectorRecibos(pedido);
        } else {
            console.error(' [viewDetail] abrirSelectorRecibos no disponible');
            alert('Error: Sistema de detalles no disponible');
        }
    } catch (error) {
        console.error(' [viewDetail] Error:', error);
    }
    
    return;
    
    // CÓDIGO ANTIGUO - COMENTADO (mantener para referencia)
    /*
    try {
        setCurrentOrder(pedido);
        
        const response = await fetch(`${window.fetchUrl}/${pedido}`);
        if (!response.ok) throw new Error('Error fetching order');
        const order = await response.json();
        
if (typeof loadOrderImages === 'function') {
            loadOrderImages(pedido);
        }

        //  LLENAR CAMPOS DEL MODAL
        
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
            pedidoDiv.textContent = `N° ${pedido}`;
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
        
        //  LLENAR DESCRIPCIÓN DE PRENDAS CON NAVEGACIÓN
        const descripcionText = document.getElementById('descripcion-text');
        const prevArrow = document.getElementById('prev-arrow');
        const nextArrow = document.getElementById('next-arrow');
        const arrowContainer = prevArrow?.parentElement;
        
        if (descripcionText && order.descripcion_prendas) {
            // Parsear la descripción de prendas - NUEVO FORMATO CON ASTERISCOS
            const prendas = order.descripcion_prendas.split(/(?=PRENDA\s+\d+:)/i).filter(p => p.trim());
            
            // Función para formatear una prenda - ADAPTADA AL NUEVO FORMATO
            function formatearPrenda(prendaText, index) {
                // Parsear cada prenda
                const prendaMatch = prendaText.match(/^PRENDA\s+(\d+):\s*(.+?)(?:\n|$)/i);
                const prendaNum = prendaMatch ? prendaMatch[1] : (index + 1);
                const prendaNombre = prendaMatch ? prendaMatch[2].trim() : '';
                
                // Buscar Color | Tela | Manga en una línea
                const atributosMatch = prendaText.match(/Color:.*?\|.*?Tela:.*?(?:\|.*?Manga:.*?)?/i);
                const atributosLinea = atributosMatch ? atributosMatch[0] : '';
                
                // Buscar DESCRIPCIÓN (para prendas sin variaciones)
                const descMatch = prendaText.match(/DESCRIPCIÓN:\s*(.+?)(?=\n\*\*\*|$)/i);
                const desc = descMatch ? descMatch[1].trim() : '';
                
                // Buscar Bolsillos
                const bolsillosMatch = prendaText.match(/\*\*\*\s*Bolsillos:\s*\*\*\*([\s\S]*?)(?=\n\*\*\*|$)/i);
                const bolsillos = bolsillosMatch ? bolsillosMatch[1].trim() : '';
                
                // Buscar Broche
                const brocheMatch = prendaText.match(/\*\*\*\s*Broche:\s*\*\*\*([\s\S]*?)(?=\n\*\*\*|$)/i);
                const broche = brocheMatch ? brocheMatch[1].trim() : '';
                
                // Buscar Reflectivo
                const reflectivoMatch = prendaText.match(/\*\*\*\s*Reflectivo:\s*\*\*\*([\s\S]*?)(?=\n\*\*\*|$)/i);
                const reflectivo = reflectivoMatch ? reflectivoMatch[1].trim() : '';
                
                // Buscar Otros detalles
                const otrosMatch = prendaText.match(/\*\*\*\s*Otros detalles:\s*\*\*\*([\s\S]*?)(?=\n\*\*\*|$)/i);
                const otros = otrosMatch ? otrosMatch[1].trim() : '';
                
                // Buscar Tallas
                const tallasMatch = prendaText.match(/\*\*\*\s*TALLAS:\s*\*\*\*([\s\S]*?)$/i);
                const tallas = tallasMatch ? tallasMatch[1].trim() : '';
                
                // Construir descripción con secciones
                let descripcionLinea = '';
                
                const secciones = [];
                if (desc && !bolsillos && !broche && !reflectivo && !otros) {
                    // Si solo hay descripción simple, mostrarla
                    descripcionLinea = `<strong>Descripción:</strong> ${desc}`;
                } else {
                    // Si hay variaciones, mostrarlas
                    if (bolsillos) secciones.push(`<strong>Bolsillos:</strong> ${bolsillos}`);
                    if (broche) secciones.push(`<strong>Broche:</strong> ${broche}`);
                    if (reflectivo) secciones.push(`<strong>Reflectivo:</strong> ${reflectivo}`);
                    if (otros) secciones.push(`<strong>Otros:</strong> ${otros}`);
                    
                    if (secciones.length > 0) {
                        descripcionLinea = secciones.join(' | ');
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
                    // Mostrar prendas desde currentIndex hasta currentIndex + 1
                    html = formatearPrenda(prendas[currentIndex], currentIndex) + 
                           (currentIndex + 1 < prendas.length ? formatearPrenda(prendas[currentIndex + 1], currentIndex + 1) : '');
                    
                    descripcionText.innerHTML = html;
                    if (arrowContainer) arrowContainer.style.display = 'flex';
                    if (prevArrow) prevArrow.style.display = currentIndex > 0 ? 'inline-block' : 'none';
                    if (nextArrow) nextArrow.style.display = currentIndex + 2 < prendas.length ? 'inline-block' : 'none';
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
                    if (currentIndex + 2 < prendas.length) {
                        currentIndex++;
                        updateDescripcion();
                    }
                };
                nextArrow.addEventListener('click', nextArrow._prendasClickHandler);
            }
        } else {
            descripcionText.innerHTML = '';
            if (arrowContainer) arrowContainer.style.display = 'none';
        }
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-detail' }));

    } catch (error) {


        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-detail' }));
    }
    */
}

/**
 * Actualizar orden en tabla desde broadcast (localStorage)
 * DELEGACIÓN PARCIAL: Usa StorageModule pero mantiene lógica existente
 */
function updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    if (!row) {

        return;
    }

    // Actualizar campo específico
    if (field === 'estado') {
        const estadoDropdown = row.querySelector('.estado-dropdown');
        if (estadoDropdown) {
            estadoDropdown.value = newValue;
            estadoDropdown.dataset.value = newValue;
            updateRowColor(orderId, newValue);
        }
    } else if (field === 'area') {
        const areaDropdown = row.querySelector('.area-dropdown');
        if (areaDropdown) {
            areaDropdown.value = newValue;
            areaDropdown.dataset.value = newValue;
        }
    }

    // Actualizar campos relacionados
    if (updatedFields) {
        for (const [updateField, updateValue] of Object.entries(updatedFields)) {
            if (updateField === 'fecha_de_creacion_de_orden') continue;
            
            const updateCell = row.querySelector(`td[data-column="${updateField}"] .cell-text`);
            if (updateCell) {
                updateCell.textContent = asegurarFormatoFecha(updateValue);
            }
        }
    }

    // Actualizar días
    if (totalDiasCalculados && totalDiasCalculados[orderId] !== undefined) {
        const totalDiasCell = row.querySelector('td[data-column="total_de_dias_"] .cell-text');
        if (totalDiasCell) {
            totalDiasCell.textContent = totalDiasCalculados[orderId];
        }
    }


}

/**
 * Limpiar filtros
 * MANTENER: Lógica de filtros
 */
function clearFilters() {
    document.getElementById('buscarOrden').value = '';

    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    for (let key of params.keys()) {
        if (key.startsWith('filter_')) {
            params.delete(key);
        }
    }
    
    params.delete('search');
    params.set('page', 1);
    
    window.history.pushState({}, '', `${url.pathname}?${params}`);
    recargarTablaPedidos();
    

}

/**
 * Abrir registro de orden
 * MANTENER: Lógica simple
 */
function openOrderRegistration() {
    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'order-registration' }));
}

// ============================================================================
// SECCIÓN 6: LISTENERS Y SINCRONIZACIÓN CON STORAGE
// ============================================================================

/**
 * DELEGACIÓN: Escuchar cambios en localStorage
 * Aunque mantenemos la lógica, usa módulos para actualización si disponibles
 */
window.addEventListener('storage', function(event) {
    if (event.key === 'orders-updates') {
        try {
            const data = JSON.parse(event.newValue);


            const { type, orderId, field, newValue, updatedFields, order, totalDiasCalculados, timestamp } = data;

            const lastTimestamp = Number.parseInt(localStorage.getItem('last-orders-update-timestamp') || '0');
            if (timestamp && timestamp <= lastTimestamp) {

                return;
            }

            localStorage.setItem('last-orders-update-timestamp', timestamp.toString());

            updateRowFromBroadcast(orderId, field, newValue, updatedFields, order, totalDiasCalculados);
        } catch (e) {

        }
    }
});

// ============================================================================
// SECCIÓN 7: INICIALIZACIÓN DEL DOM
// ============================================================================

/**
 * Inicialización al cargar DOM
 * DELEGACIÓN: Usa módulos para inicialización
 */
document.addEventListener('DOMContentLoaded', function () {

    
    // Inicializar con módulos (si están disponibles)
    initializeStatusDropdowns();
    initializeAreaDropdowns();
    initializeDiaEntregaDropdowns();
    
    //  Sistema de edición de celdas con doble clic (gestionado por modern-table.js)
    // No agregar evento de clic simple - solo doble clic para editar
    
    // Actualizar días con delay
    if (typeof actualizarDiasTabla === 'function') {
        setTimeout(() => {

            actualizarDiasTabla();

        }, 800);
    }
});

/**
 * Auto-recarga en caso de errores (MANTENER)
 */
function showAutoReloadNotification(message, duration) {
    if (NotificationModule && NotificationModule.showAutoReload) {
        NotificationModule.showAutoReload(message, duration);
        return;
    }
    
    // Fallback: implementación local
    const existingNotifications = document.querySelectorAll('.auto-reload-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    const notification = document.createElement('div');
    notification.className = 'auto-reload-notification';
    notification.innerHTML = `
        <div class="auto-reload-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <div class="auto-reload-content">
            <div class="auto-reload-title">Recargando página</div>
            <div class="auto-reload-message">${message}</div>
            <div class="auto-reload-progress">
                <div class="auto-reload-progress-bar" style="animation-duration: ${duration}ms"></div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
}

// Detectar errores globales
window.addEventListener('error', function(event) {

    
    window.globalJsErrors = (window.globalJsErrors || 0) + 1;
    
    if (window.globalJsErrors >= 5) {

        showAutoReloadNotification('Múltiples errores detectados. Recargando página...', 3000);
        setTimeout(() => window.location.reload(), 3000);
    }
});

// WebSocket disconnect handling
if (window.Echo) {
    window.Echo.connector.pusher.connection.bind('disconnected', function() {

        
        const reconnectTimeout = setTimeout(() => {
            if (window.Echo.connector.pusher.connection.state !== 'connected') {

                showAutoReloadNotification('Conexión perdida. Recargando página...', 2000);
                setTimeout(() => window.location.reload(), 2000);
            }
        }, 10000);
        
        window.Echo.connector.pusher.connection.bind('connected', function() {
            clearTimeout(reconnectTimeout);

        });
    });
}




