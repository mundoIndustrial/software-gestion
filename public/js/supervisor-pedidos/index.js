/**
 * =====================================================
 * SUPERVISOR PEDIDOS INDEX - FUNCIONALIDAD PRINCIPAL
 * =====================================================
 */

// ===== VARIABLES GLOBALES =====
let filtroActual = null;

// ===== TOGGLE MENU ACCIONES =====
function toggleAcciones(event, ordenId) {
    event.stopPropagation();
    const menu = document.getElementById(`menu-${ordenId}`);

    document.querySelectorAll('.action-menu:not([style*="display: none"])').forEach(m => {
        if (m.id !== `menu-${ordenId}`) {
            m.style.display = 'none';
        }
    });

    if (menu.style.display === 'none' || menu.style.display === '') {
        menu.style.display = 'block';
    } else {
        menu.style.display = 'none';
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.action-menu') && !e.target.closest('.action-view-btn')) {
        document.querySelectorAll('.action-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// ===== FILTROS HELPERS =====
function getValoresFiltroDesdeURL(columna) {
    const url = new URL(window.location.href);
    if (columna === 'numero' || columna === 'id-orden') {
        const raw = url.searchParams.get('numero') || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }
    if (columna === 'cliente') {
        const raw = url.searchParams.get('cliente') || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }
    if (columna === 'estado') {
        const raw = url.searchParams.get('estado') || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }
    if (columna === 'asesora') {
        const raw = url.searchParams.get('asesora') || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }
    if (columna === 'forma_pago' || columna === 'forma-pago') {
        const raw = url.searchParams.get('forma_pago') || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }
    if (columna === 'fecha') {
        const desde = url.searchParams.get('fecha_desde') || '';
        const hasta = url.searchParams.get('fecha_hasta') || '';
        return [desde, hasta].filter(Boolean);
    }
    return [];
}

function asegurarBadgeEnBoton(btn) {
    if (!btn) return null;
    let badge = btn.querySelector('.filter-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'filter-badge';
        badge.textContent = '0';
        btn.appendChild(badge);
    }
    return badge;
}

function actualizarIndicadoresFiltros() {
    const botones = document.querySelectorAll('#supervisorPedidosIndexContent .btn-filter-column');
    if (!botones || botones.length === 0) return;

    botones.forEach(btn => {
        const title = btn.getAttribute('title') || '';
        let columna = '';
        switch(title) {
            case 'Filtrar Número':  columna = 'numero';    break;
            case 'Filtrar Cliente': columna = 'cliente';   break;
            case 'Filtrar Estado':  columna = 'estado';    break;
            case 'Filtrar Asesora': columna = 'asesora';   break;
            case 'Filtrar Forma Pago': columna = 'forma_pago'; break;
            default: columna = '';
        }
        const valores = columna ? getValoresFiltroDesdeURL(columna) : [];
        const cantidad = valores.length;
        const badge = asegurarBadgeEnBoton(btn);
        if (cantidad > 0) {
            btn.classList.add('has-filter');
            if (badge) badge.textContent = String(cantidad);
        } else {
            btn.classList.remove('has-filter');
            if (badge) badge.textContent = '0';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    actualizarIndicadoresFiltros();
});

// ===== MENU VER ORDEN =====
function toggleVerMenu(event, ordenId) {
    event.stopPropagation();
    const menu = document.getElementById(`ver-menu-${ordenId}`);

    document.querySelectorAll('.ver-submenu[style*="display: block"]').forEach(m => {
        if (m.id !== `ver-menu-${ordenId}`) {
            m.style.display = 'none';
        }
    });

    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

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

    switch(columna) {
        case 'id-orden':
            titulo = 'Filtrar por ID Orden';
            campoNombre = 'numero';
            break;
        case 'numero':
            titulo = 'Filtrar por Número';
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
            {
                const url = new URL(window.location.href);
                const desdeEl = document.getElementById('filtroDesde');
                const hastaEl = document.getElementById('filtroHasta');
                if (desdeEl) desdeEl.value = url.searchParams.get('fecha_desde') || '';
                if (hastaEl) hastaEl.value = url.searchParams.get('fecha_hasta') || '';
            }
            modal.style.display = 'flex';
            return;
        case 'estado': {
            titulo = 'Filtrar por Estado';
            campoNombre = 'estado';
            const estadosDisplay = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'Pendiente Supervisor', 'Pendiente Insumos', 'Pendiente Cartera', 'Rechazado Cartera', 'Devuelto a Asesora'];
            const estadosDB    = ['Pendiente', 'No iniciado', 'En Ejecución', 'Entregado', 'Anulada', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS', 'pendiente_cartera', 'RECHAZADO_CARTERA', 'DEVUELTO_A_ASESORA'];
            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorEstado" class="form-control" placeholder="Buscar estado..." style="margin-bottom: 1rem;">
                    <div id="listaEstados">
                        ${estadosDisplay.map((display, i) => `
                            <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px;">
                                <input type="checkbox" name="estado" value="${estadosDB[i]}" class="filtro-checkbox">
                                <span>${display}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            const seleccionados = new Set(getValoresFiltroDesdeURL('estado'));
            document.querySelectorAll('#listaEstados .filtro-checkbox').forEach(cb => {
                cb.checked = seleccionados.has(cb.value);
            });
            setTimeout(() => {
                document.getElementById('buscadorEstado')?.addEventListener('input', function(e) {
                    const valor = e.target.value.toLowerCase();
                    document.querySelectorAll('#listaEstados label').forEach(label => {
                        label.style.display = label.textContent.toLowerCase().includes(valor) ? 'flex' : 'none';
                    });
                });
            }, 0);
            modal.style.display = 'flex';
            return;
        }
        case 'asesora':
            titulo = 'Filtrar por Asesora';
            campoNombre = 'asesora';
            break;
        case 'forma-pago':
        case 'forma_pago':
            titulo = 'Filtrar por Forma de Pago';
            campoNombre = 'forma_pago';
            break;
    }

    if (campoNombre && columna !== 'fecha' && columna !== 'estado') {
        cargarOpcionesFiltro(campoNombre, titulo, modal, filtroContenido);
    }
}

function cargarOpcionesFiltro(campo, titulo, modal, filtroContenido) {
    const endpoint = `/supervisor-pedidos/filtro-opciones/${campo}`;

    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            const modalTitulo = document.getElementById('modalFiltroTitulo');
            modalTitulo.textContent = titulo;

            filtroContenido.innerHTML = `
                <div class="form-group">
                    <input type="text" id="buscadorFiltro" class="form-control" placeholder="Buscar..." style="margin-bottom: 1rem;">
                    <div id="listaOpciones" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
            `;

            const buscador = document.getElementById('buscadorFiltro');
            const lista    = document.getElementById('listaOpciones');
            const columnaMap = (campo === 'forma_pago') ? 'forma_pago' : campo;
            const seleccionados = new Set(getValoresFiltroDesdeURL(columnaMap));

            function normalizarTexto(v) {
                return String(v || '').toLowerCase();
            }

            function renderOpciones(query) {
                const q = normalizarTexto(query).trim();
                const opciones = Array.isArray(data.opciones) ? data.opciones : [];
                let list = q === ''
                    ? opciones.slice(0, campo === 'cliente' ? 5 : opciones.length)
                    : opciones.filter(op => normalizarTexto(op).includes(q));

                if (!lista) return;

                lista.innerHTML = list.map(opcion => {
                    const safeValue = (opcion === null || opcion === undefined) ? '' : String(opcion);
                    const checked = seleccionados.has(safeValue) ? 'checked' : '';
                    return `
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="${campo}" value="${safeValue}" class="filtro-checkbox" ${checked}>
                            <span>${safeValue || '(Sin especificar)'}</span>
                        </label>
                    `;
                }).join('');
            }

            if (lista) {
                lista.addEventListener('change', function(e) {
                    const cb = e.target;
                    if (!cb?.classList?.contains('filtro-checkbox')) return;
                    const val = String(cb.value ?? '');
                    cb.checked ? seleccionados.add(val) : seleccionados.delete(val);
                });
            }

            renderOpciones('');

            if (buscador) {
                buscador.addEventListener('input', e => renderOpciones(e.target.value));
            }

            modal.style.display = 'flex';
        })
        .catch(() => {
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

    const url = new URL(window.location.href);
    const valoresSeleccionados = Array.from(
        document.querySelectorAll('.filtro-checkbox:checked')
    ).map(cb => cb.value);

    const setParam = (key) => {
        url.searchParams.delete(key);
        if (valoresSeleccionados.length > 0) url.searchParams.set(key, valoresSeleccionados.join(','));
    };

    if (filtroActual === 'id-orden' || filtroActual === 'numero') {
        setParam('numero');
    } else if (filtroActual === 'cliente') {
        setParam('cliente');
    } else if (filtroActual === 'fecha') {
        url.searchParams.delete('fecha_desde');
        url.searchParams.delete('fecha_hasta');
        const desde = document.getElementById('filtroDesde')?.value;
        const hasta = document.getElementById('filtroHasta')?.value;
        if (desde) url.searchParams.set('fecha_desde', desde);
        if (hasta) url.searchParams.set('fecha_hasta', hasta);
    } else if (filtroActual === 'estado') {
        setParam('estado');
    } else if (filtroActual === 'asesora') {
        setParam('asesora');
    } else if (filtroActual === 'forma-pago' || filtroActual === 'forma_pago') {
        setParam('forma_pago');
    }

    cerrarModalFiltro();
    navegarSupervisorPedidos(url.toString());
}

async function navegarSupervisorPedidos(urlString, options = {}) {
    const { pushState = true } = options;
    const container = document.getElementById('supervisorPedidosIndexContent');
    if (!container) {
        window.location.href = urlString;
        return;
    }
    try {
        container.style.opacity = '0.6';
        container.style.pointerEvents = 'none';

        const res = await fetch(urlString, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        });

        const html = await res.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const next = doc.getElementById('supervisorPedidosIndexContent');

        if (!res.ok || !next) {
            window.location.href = urlString;
            return;
        }

        container.innerHTML = next.innerHTML;

        if (pushState) {
            window.history.pushState({ url: urlString }, '', urlString);
        }

        actualizarIndicadoresFiltros();
        window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));

        setTimeout(() => {
            if (typeof window.cargarSeleccionesGuardadas === 'function') {
                window.cargarSeleccionesGuardadas();
            }
        }, 300);
    } catch (e) {
        window.location.href = urlString;
    } finally {
        container.style.opacity = '';
        container.style.pointerEvents = '';
    }
}

window.addEventListener('popstate', function() {
    navegarSupervisorPedidos(window.location.href, { pushState: false });
    window.dispatchEvent(new Event('supervisorPedidos:filtersUpdated'));
});

document.addEventListener('click', function(e) {
    const a = e.target.closest('#supervisorPedidosIndexContent a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#')) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    if (!href.startsWith(window.location.origin) && !href.startsWith('/')) return;
    const urlAbs = href.startsWith('http') ? href : (window.location.origin + href);
    let path = '';
    try { path = new URL(urlAbs).pathname || ''; } catch (e) { return; }
    if (!path.startsWith('/supervisor-pedidos')) return;
    e.preventDefault();
    navegarSupervisorPedidos(urlAbs);
});

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

// Contador de caracteres del textarea de anulación
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

document.getElementById('modalExitoRevision')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoRevision();
});

document.getElementById('modalOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalOcultar();
});

document.getElementById('modalExitoOcultar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExitoOcultar();
});

// Delegado: novedades y filtros de columna
document.addEventListener('click', function(e) {
    const btnNovedades = e.target.closest('.btn-novedades');
    if (btnNovedades) {
        e.preventDefault();
        const ordenId = btnNovedades.dataset.ordenId;
        const novedadesJson = btnNovedades.getAttribute('data-novedades');
        try {
            const novedades = JSON.parse(novedadesJson);
            abrirNovedades(ordenId, novedades);
        } catch (err) {
            console.error('[Novedades] Error al parsear JSON:', err);
        }
        return;
    }

    const btnFiltro = e.target.closest('.btn-filter-column');
    if (btnFiltro) {
        e.preventDefault();
        e.stopPropagation();
        const title = btnFiltro.getAttribute('title');
        let columna = '';
        switch(title) {
            case 'Filtrar Número':     columna = 'numero';    break;
            case 'Filtrar Cliente':    columna = 'cliente';   break;
            case 'Filtrar Estado':     columna = 'estado';    break;
            case 'Filtrar Asesora':    columna = 'asesora';   break;
            case 'Filtrar Forma Pago': columna = 'forma_pago'; break;
            default:                   columna = 'cliente';
        }
        abrirModalFiltro(columna);
    }
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

function verOrdenDetalles(ordenId) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';
    openOrderDetailModal(ordenId);
}

function abrirSeguimiento(ordenId) {
    const menu = document.getElementById(`ver-menu-${ordenId}`);
    if (menu) menu.style.display = 'none';

    if (typeof openOrderTrackingModal === 'function') {
        try {
            openOrderTrackingModal(ordenId);
        } catch (error) {
            console.error('[abrirSeguimiento] Error:', error);
            alert('Error en openOrderTrackingModal: ' + error.message);
        }
    } else {
        console.error('[abrirSeguimiento] openOrderTrackingModal no está disponible');
        alert('Error: El modal de seguimiento no está disponible. Intenta nuevamente.');
    }
}

async function editarPedido(pedidoId) {
    if (window.edicionEnProgreso) return;
    window.edicionEnProgreso = true;

    try {
        await _ensureSwal();

        Swal.fire({
            html: `
                <div style="text-align: center; padding: 2rem;">
                    <div style="width: 60px; height: 60px; border: 4px solid #e5e7eb; border-top-color: #1e40af; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1.5rem;"></div>
                    <p style="color: #6b7280; font-size: 14px; font-weight: 500; margin: 0;">Cargando datos del pedido...</p>
                </div>
                <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
            `,
            width: '300px',
            padding: '0',
            background: 'white',
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => { document.body.style.overflow = 'hidden'; }
        });

        if (!window.PrendaEditorPreloader?.isReady?.()) {
            await window.PrendaEditorPreloader.loadWithLoader({ title: 'Cargando datos', message: 'Por favor espera...' });
        }

        const response = await fetch(`/api/pedidos/${pedidoId}`, {
            method: 'GET',
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' }
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

        const respuesta = await response.json();
        if (!respuesta.success) throw new Error(respuesta.message || 'Error desconocido');

        const datos = respuesta.data || respuesta.datos;
        const datosTransformados = {
            id: datos.id || datos.numero_pedido,
            numero_pedido: datos.numero_pedido || datos.numero,
            numero: datos.numero || datos.numero_pedido,
            cliente: datos.cliente || 'Cliente sin especificar',
            asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
            estado: datos.estado || 'Pendiente',
            forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
            prendas: datos.prendas || [],
            epps: datos.epps_transformados || datos.epps || [],
            procesos: datos.procesos || [],
            ...datos
        };

        await abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');

    } catch (err) {
        Swal.close();
        console.error('[editarPedido] Error:', err);
        alert('Error: No se pudo cargar el pedido: ' + err.message);
    } finally {
        window.edicionEnProgreso = false;
    }
}
