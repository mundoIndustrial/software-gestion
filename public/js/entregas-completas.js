// Entregas Completas - JavaScript

document.addEventListener('DOMContentLoaded', function() {
    inicializarEntregasCompletas();
});

function inicializarEntregasCompletas() {
    // Inicializar tooltips
    inicializarTooltips();
    
    // Configurar eventos
    configurarEventos();
    
    // Actualizacion en tiempo real (sin polling)
    setupRealtimeEntregas();
    setupVisibilityRefresh();
}

function inicializarTooltips() {
    // Inicializar tooltips de Bootstrap si están disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

function configurarEventos() {
    // Evento de submit del formulario de filtros
    const filtroForm = document.getElementById('filtroForm');
    if (filtroForm) {
        filtroForm.addEventListener('submit', function(e) {
            mostrarLoading();
        });
    }
    
    // Eventos de cambio en filtros para búsqueda automática
    const filtrosAuto = ['estado_entrega', 'estado_pedido', 'per_page'];
    filtrosAuto.forEach(function(filtroId) {
        const elemento = document.getElementById(filtroId);
        if (elemento) {
            elemento.addEventListener('change', function() {
                // Pequeño delay para evitar múltiples submits
                clearTimeout(window.filtroTimeout);
                window.filtroTimeout = setTimeout(function() {
                    filtroForm.submit();
                }, 300);
            });
        }
    });
}

function setupRealtimeEntregas() {
    const onEntregaChanged = () => recargarDatos(false);

    try {
        const ws = window.shared?.websocket;
        if (ws && typeof ws.subscribe === 'function') {
            ws.subscribe('entregas.pedido', 'EntregaRegistrada', onEntregaChanged);
            ws.subscribe('entregas.pedido', 'EntregaEliminada', onEntregaChanged);
            ws.subscribe('entregas.bodega', 'EntregaRegistrada', onEntregaChanged);
            ws.subscribe('entregas.bodega', 'EntregaEliminada', onEntregaChanged);
            return;
        }
    } catch (error) {
        console.warn('[entregas-completas] No se pudo usar window.shared.websocket:', error);
    }

    if (window.EchoInstance && typeof window.EchoInstance.channel === 'function') {
        window.EchoInstance.channel('entregas.pedido')
            .listen('EntregaRegistrada', onEntregaChanged)
            .listen('EntregaEliminada', onEntregaChanged);
        window.EchoInstance.channel('entregas.bodega')
            .listen('EntregaRegistrada', onEntregaChanged)
            .listen('EntregaEliminada', onEntregaChanged);
        return;
    }

    console.warn('[entregas-completas] Realtime no disponible; solo refresco por foco/visibilidad');
}

function setupVisibilityRefresh() {
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            recargarDatos(false);
        }
    });

    window.addEventListener('focus', function() {
        recargarDatos(false);
    });
}

function recargarDatos(mostrarLoadingState = true) {
    if (mostrarLoadingState) {
        mostrarLoading();
    }
    
    const currentUrl = new URL(window.location);
    
    fetch(currentUrl.pathname + currentUrl.search, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al recargar los datos');
        }
        return response.text();
    })
    .then(html => {
        // Actualizar la tabla de entregas
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const nuevaTabla = doc.querySelector('#tablaEntregas tbody');
        const tablaActual = document.querySelector('#tablaEntregas tbody');
        
        if (nuevaTabla && tablaActual) {
            tablaActual.innerHTML = nuevaTabla.innerHTML;
            
            // Actualizar paginación
            const nuevaPaginacion = doc.querySelector('.pagination');
            const paginacionActual = document.querySelector('.pagination');
            if (nuevaPaginacion && paginacionActual) {
                paginacionActual.innerHTML = nuevaPaginacion.innerHTML;
            }
            
            // Actualizar contador de registros
            const nuevoContador = doc.querySelector('.text-muted');
            const contadorActual = document.querySelector('.text-muted');
            if (nuevoContador && contadorActual && nuevoContador.textContent.includes('Mostrando')) {
                contadorActual.textContent = nuevoContador.textContent;
            }
            
            // Mostrar notificación de éxito
            mostrarNotificacion('Datos actualizados correctamente', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al actualizar los datos', 'error');
    })
    .finally(() => {
        if (mostrarLoadingState) {
            ocultarLoading();
        }
    });
}

function limpiarFiltros() {
    const filtroForm = document.getElementById('filtroForm');
    if (filtroForm) {
        // Limpiar todos los campos del formulario
        filtroForm.reset();
        
        // Enviar el formulario limpio
        filtroForm.submit();
    }
}

function verDetallesPedido(pedidoId) {
    // Mostrar loading
    mostrarLoading();
    
    // Obtener detalles del pedido via API
    fetch(`/api/entregas-completas?pedido_id=${pedidoId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error al obtener los detalles del pedido');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data.length > 0) {
            const pedido = data.data[0];
            
            // Crear contenido del modal
            const contenido = crearContenidoModalDetalles(pedido);
            
            // Actualizar modal
            const modalContenido = document.getElementById('detallesContenido');
            if (modalContenido) {
                modalContenido.innerHTML = contenido;
            }
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('detallesModal'));
            modal.show();
        } else {
            mostrarNotificacion('No se encontraron detalles para este pedido', 'warning');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarNotificacion('Error al cargar los detalles del pedido', 'error');
    })
    .finally(() => {
        ocultarLoading();
    });
}

function crearContenidoModalDetalles(pedido) {
    const fechaEntregaSupervisor = pedido.fecha_entrega_supervisor ? 
        new Date(pedido.fecha_entrega_supervisor).toLocaleString('es-ES') : 'Pendiente';
    const fechaEntregaDespacho = pedido.fecha_entrega_despacho ? 
        new Date(pedido.fecha_entrega_despacho).toLocaleString('es-ES') : 'Pendiente';
    
    return `
        <div class="row">
            <div class="col-md-6">
                <h6>Información del Pedido</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Número:</strong></td>
                        <td>#${pedido.numero_pedido}</td>
                    </tr>
                    <tr>
                        <td><strong>Cliente:</strong></td>
                        <td>${pedido.cliente}</td>
                    </tr>
                    <tr>
                        <td><strong>Estado:</strong></td>
                        <td><span class="badge estado-pedido ${getEstadoBadgeClass(pedido.estado_pedido)}">${pedido.estado_pedido}</span></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Estado de Entregas</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Supervisor:</strong></td>
                        <td>${fechaEntregaSupervisor}</td>
                    </tr>
                    <tr>
                        <td><strong>Despacho:</strong></td>
                        <td>${fechaEntregaDespacho}</td>
                    </tr>
                    <tr>
                        <td><strong>Estado General:</strong></td>
                        <td><span class="badge estado-entrega ${getEstadoEntregaBadgeClass(pedido.estado_entrega_general)}">${pedido.estado_entrega_general}</span></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Timeline</h6>
                <div class="timeline">
                    <div class="timeline-item ${pedido.fecha_entrega_supervisor ? 'completed' : 'pending'}">
                        <div class="timeline-marker">
                            <span class="material-symbols-rounded">${pedido.fecha_entrega_supervisor ? 'check_circle' : 'radio_button_unchecked'}</span>
                        </div>
                        <div class="timeline-content">
                            <h6>Entrega Supervisor → Despacho</h6>
                            <p class="text-muted mb-0">${fechaEntregaSupervisor}</p>
                            ${pedido.nombre_supervisor_entrega ? `<p class="mb-0"><strong>Responsable:</strong> ${pedido.nombre_supervisor_entrega}</p>` : ''}
                        </div>
                    </div>
                    <div class="timeline-item ${pedido.fecha_entrega_despacho ? 'completed' : 'pending'}">
                        <div class="timeline-marker">
                            <span class="material-symbols-rounded">${pedido.fecha_entrega_despacho ? 'check_circle' : 'radio_button_unchecked'}</span>
                        </div>
                        <div class="timeline-content">
                            <h6>Entrega Despacho → Asesor</h6>
                            <p class="text-muted mb-0">${fechaEntregaDespacho}</p>
                            ${pedido.nombre_despacho_entrega ? `<p class="mb-0"><strong>Responsable:</strong> ${pedido.nombre_despacho_entrega}</p>` : ''}
                            ${pedido.horas_entre_entregas ? `<p class="mb-0"><small class="text-info">Tiempo: ${Number(pedido.horas_entre_entregas).toFixed(1)} horas</small></p>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function getEstadoBadgeClass(estado) {
    const classes = {
        'Pendiente': 'bg-secondary',
        'No iniciado': 'bg-secondary',
        'En Ejecución': 'bg-primary',
        'Entregado': 'bg-success',
        'Anulada': 'bg-danger',
        'PENDIENTE_SUPERVISOR': 'bg-warning',
        'pendiente_cartera': 'bg-info',
        'RECHAZADO_CARTERA': 'bg-danger',
        'PENDIENTE_INSUMOS': 'bg-purple',
        'DEVUELTO_A_ASESORA': 'bg-orange',
    };
    return classes[estado] || 'bg-secondary';
}

function getEstadoEntregaBadgeClass(estado) {
    const classes = {
        'Completado': 'bg-success',
        'Pendiente Despacho': 'bg-warning',
        'Pendiente Supervisor': 'bg-info',
        'Pendiente Ambos': 'bg-secondary',
    };
    return classes[estado] || 'bg-secondary';
}

function exportarExcel() {
    mostrarLoading();
    
    // Obtener URL actual con filtros
    const currentUrl = new URL(window.location);
    const params = new URLSearchParams(currentUrl.search);
    
    // Agregar parámetro de exportación
    params.set('export', 'excel');
    
    // Crear URL de exportación
    const exportUrl = `${currentUrl.pathname}?${params.toString()}`;
    
    // Crear un formulario temporal para la descarga
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = exportUrl;
    
    // Agregar CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken.getAttribute('content');
        form.appendChild(csrfInput);
    }
    
    // Agregar método POST simulado
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'GET';
    form.appendChild(methodInput);
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    // Ocultar loading después de un delay
    setTimeout(() => {
        ocultarLoading();
        mostrarNotificación('Exportación iniciada', 'success');
    }, 1000);
}

function mostrarLoading() {
    const tabla = document.querySelector('#tablaEntregas');
    if (tabla) {
        tabla.classList.add('loading');
    }
}

function ocultarLoading() {
    const tabla = document.querySelector('#tablaEntregas');
    if (tabla) {
        tabla.classList.remove('loading');
    }
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show position-fixed`;
    notificacion.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notificacion.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.parentNode.removeChild(notificacion);
        }
    }, 5000);
}

// Función para manejar errores de red
window.addEventListener('online', function() {
    mostrarNotificación('Conexión restaurada', 'success');
    recargarDatos();
});

window.addEventListener('offline', function() {
    mostrarNotificación('Conexión perdida', 'warning');
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + R para recargar
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        recargarDatos();
    }
    
    // Ctrl/Cmd + E para exportar
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportarExcel();
    }
    
    // Escape para limpiar filtros
    if (e.key === 'Escape') {
        limpiarFiltros();
    }
});

