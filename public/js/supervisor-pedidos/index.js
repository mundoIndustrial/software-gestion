/**
 * =====================================================
 * SUPERVISOR PEDIDOS INDEX - FUNCIONALIDAD PRINCIPAL
 * =====================================================
 */

// ===== VARIABLES GLOBALES =====
let filtroActual = null;

// ===== MENU VER ORDEN =====
function toggleVerMenu(event, ordenId) {
    event.stopPropagation();
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    
    // Cerrar otros menús abiertos
    document.querySelectorAll('.ver-submenu[style*="display: block"]').forEach(m => {
        if (m.id !== `ver-menu-${ordenId}`) {
            m.style.display = 'none';
        }
    });
    
    // Toggle del menú actual
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

// Cerrar menús al hacer clic afuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ver-menu-container')) {
        document.querySelectorAll('.ver-submenu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// ===== FILTROS DE COLUMNAS =====

function abrirModalFiltro(columna) {
    filtroActual = columna;
    const modalTitulo = document.getElementById('modalFiltroTitulo');
    const filtroContenido = document.getElementById('filtroContenido');
    const modal = document.getElementById('modalFiltro');

    let titulo = '';
    let campoNombre = '';

    // Configurar según la columna
    switch(columna) {
        case 'id-orden':
            titulo = 'Filtrar por ID Orden';
            campoNombre = 'numero';
            break;
        case 'cliente':
            titulo = 'Filtrar por Cliente';
            campoNombre = 'cliente';
            break;
        case 'fecha':
            modalTitulo.textContent = 'Filtrar por Fecha';
            filtroContenido.innerHTML = `
                <label for="filtroDesde">Desde:</label>
                <input type="date" id="filtroDesde" name="fecha_desde" class="form-control">
                <label for="filtroHasta" style="margin-top: 1rem;">Hasta:</label>
                <input type="date" id="filtroHasta" name="fecha_hasta" class="form-control">
            `;
            modal.style.display = 'flex';
            return;
        case 'estado':
            titulo = 'Filtrar por Estado';
            campoNombre = 'estado';
            // Estados predefinidos
            const estados = ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada'];
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                    <div id="listaEstados">
                        ${estados.map(estado => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" name="estado" value="${estado}" class="filtro-checkbox">
                                <span>${estado}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Agregar funcionalidad de búsqueda
            setTimeout(() => {
                document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                    const valor = e.target.value.toLowerCase();
                    document.querySelectorAll('#listaEstados label').forEach(label => {
                        const texto = label.textContent.toLowerCase();
                        label.style.display = texto.includes(valor) ? 'flex' : 'none';
                    });
                });
            }, 0);
            
            modal.style.display = 'flex';
            return;
        case 'asesora':
            titulo = 'Filtrar por Asesora';
            campoNombre = 'asesora';
            break;
        case 'forma-pago':
            titulo = 'Filtrar por Forma de Pago';
            campoNombre = 'forma_pago';
            break;
    }

    // Para columnas que necesitan cargar datos de la BD
    if (campoNombre && columna !== 'fecha' && columna !== 'estado') {
        cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
    }
}

function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
    // Mapear campos a columnas de la BD
    const endpoint = `/supervisor-pedidos/filtro-opciones/${campo}`;
    
    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            modalTitulo = document.getElementById('modalFiltroTitulo');
            modalTitulo.textContent = titulo;
            
            // Crear HTML con buscador y checkboxes
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                    <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;">
                        ${data.opciones.map(opcion => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                                <input type="checkbox" name="${campo}" value="${opcion}" class="filtro-checkbox">
                                <span>${opcion || '(Sin especificar)'}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Agregar funcionalidad de búsqueda
            setTimeout(() => {
                document.getElementById('buscadorFiltro')?.addEventListener('input', function(e) {
                    const valor = e.target.value.toLowerCase();
                    document.querySelectorAll('#listaOpciones label').forEach(label => {
                        const texto = label.textContent.toLowerCase();
                        label.style.display = texto.includes(valor) ? 'flex' : 'none';
                    });
                });
            }, 0);
            
            modal.style.display = 'flex';
        })
        .catch(error => {

            filtroContenido.innerHTML = `<p style="color: red;">Error cargando opciones de filtro</p>`;
            modal.style.display = 'flex';
        });
}

function cerrarModalFiltro() {
    document.getElementById('modalFiltro').style.display = 'none';
    filtroActual = null;
}

function aplicarFiltroColumna(event) {
    event.preventDefault();
    
    // Construir URL con parámetros actuales
    const url = new URL(window.location);
    
    // Obtener todos los checkboxes seleccionados
    const checkboxes = document.querySelectorAll('.filtro-checkbox:checked');
    const valoresSeleccionados = Array.from(checkboxes).map(cb => cb.value);
    
    // Limpiar parámetros anteriores según el filtro actual
    if (filtroActual === 'id-orden') {
        url.searchParams.delete('numero');
        if (valoresSeleccionados.length > 0) url.searchParams.set('numero', valoresSeleccionados.join(','));
    } else if (filtroActual === 'cliente') {
        url.searchParams.delete('cliente');
        if (valoresSeleccionados.length > 0) url.searchParams.set('cliente', valoresSeleccionados.join(','));
    } else if (filtroActual === 'fecha') {
        url.searchParams.delete('fecha_desde');
        url.searchParams.delete('fecha_hasta');
        const desde = document.getElementById('filtroDesde')?.value;
        const hasta = document.getElementById('filtroHasta')?.value;
        if (desde) url.searchParams.set('fecha_desde', desde);
        if (hasta) url.searchParams.set('fecha_hasta', hasta);
    } else if (filtroActual === 'estado') {
        url.searchParams.delete('estado');
        if (valoresSeleccionados.length > 0) url.searchParams.set('estado', valoresSeleccionados.join(','));
    } else if (filtroActual === 'asesora') {
        url.searchParams.delete('asesora');
        if (valoresSeleccionados.length > 0) url.searchParams.set('asesora', valoresSeleccionados.join(','));
    } else if (filtroActual === 'forma-pago') {
        url.searchParams.delete('forma_pago');
        if (valoresSeleccionados.length > 0) url.searchParams.set('forma_pago', valoresSeleccionados.join(','));
    }
    
    window.location.href = url.toString();
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalFiltro')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalFiltro();
});

// ===== MODALES DE ÓRDENES =====
function verOrdenComparar(ordenId) {
    document.getElementById(`ver-menu-${ordenId}`).style.display = 'none';
    abrirModalComparar(ordenId);
}

function cerrarModalVerOrden() {
    document.getElementById('modalVerOrden').style.display = 'none';
}

function abrirModalAnulacion(ordenId, numeroOrden) {
    document.getElementById('ordenNumero').textContent = '#' + numeroOrden;
    document.getElementById('formAnulacion').dataset.ordenId = ordenId;
    document.getElementById('motivoAnulacion').value = '';
    document.getElementById('contadorActual').textContent = '0';
    document.getElementById('modalAnulacion').style.display = 'flex';
}

function cerrarModalAnulacion() {
    document.getElementById('modalAnulacion').style.display = 'none';
}

function confirmarAnulacion(event) {
    event.preventDefault();
    
    const ordenId = document.getElementById('formAnulacion').dataset.ordenId;
    const motivo = document.getElementById('motivoAnulacion').value;

    fetch(`/supervisor-pedidos/${ordenId}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            motivo_anulacion: motivo,
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Orden anulada correctamente');
            // Recargar notificaciones si la función existe
            if (typeof cargarNotificacionesPendientes === 'function') {
                cargarNotificacionesPendientes();
            }
            // Cerrar modal y recargar después de 1 segundo
            cerrarModalAnulacion();
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {

        alert('Error al anular la orden');
    });
}

// Contador de caracteres
document.getElementById('motivoAnulacion')?.addEventListener('input', function() {
    document.getElementById('contadorActual').textContent = this.value.length;
    const btnConfirmar = document.getElementById('btnConfirmarAnulacion');
    if (btnConfirmar) {
        btnConfirmar.disabled = this.value.length < 10 || this.value.length > 500;
    }
});

// Cerrar modales al hacer clic fuera
document.getElementById('modalVerOrden')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalVerOrden();
});

document.getElementById('modalAnulacion')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAnulacion();
});

// Función para aprobar orden
function aprobarOrden(ordenId, numeroOrden) {
    Swal.fire({
        title: '¿Aprobar Pedido?',
        html: `<p>¿Deseas aprobar el pedido <strong>#${numeroOrden}</strong>?</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-check"></i> Sí, aprobar',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            // Mostrar modal de cargando
            Swal.fire({
                title: 'Procesando...',
                html: '<p>Por favor espera mientras se aprueba el pedido</p><div style="margin-top: 20px;"><div class="spinner-border" role="status"><span class="sr-only">Cargando...</span></div></div>',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(`/supervisor-pedidos/${ordenId}/aprobar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({}),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Aprobado!',
                        html: `<p>${data.message || 'Pedido aprobado correctamente'}</p><p style="margin-top: 10px; font-weight: 600; color: #10b981;">Estado: ${data.estado}</p>`,
                        icon: 'success',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        // Recargar notificaciones si la función existe
                        if (typeof cargarNotificacionesPendientes === 'function') {
                            cargarNotificacionesPendientes();
                        }
                        // Recargar la página después de 1 segundo
                        setTimeout(() => location.reload(), 1000);
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo aprobar el pedido',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error al aprobar la orden:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Error al procesar la solicitud',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
    });
}

// Función para ver detalles de orden (orden-detail-modal)
// Cierra el menú y abre el modal de detalles
function verOrdenDetalles(ordenId) {
    // Cerrar el menú ver
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) {
        menu.style.display = 'none';
    }
    
    // Abrir el modal de detalles usando la función externa
    openOrderDetailModal(ordenId);
}
