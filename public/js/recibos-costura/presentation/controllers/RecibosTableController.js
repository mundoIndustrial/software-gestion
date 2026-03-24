/**
 * RecibosTableController - Orquestador de la tabla de recibos de costura
 *
 * Flujo:
 * 1. Llama a ReciboCosturaAPI para obtener datos
 * 2. Actualiza RecibosState con los datos
 * 3. Se suscribe a cambios de estado para renderizar
 * 4. Renderiza tabla dinámicamente usando Value Objects
 *
 * @class RecibosTableController
 */
class RecibosTableController {
    constructor() {
        this.api = new ReciboCosturaAPI();
        this.state = RecibosState.getInstance();
        this.initialized = false;
        
        // Elementos del DOM
        this.elements = {
            tbody: null,
            paginationContainer: null,
            filterContainer: null,
            loadingSpinner: null,
            errorAlert: null,
            successAlert: null
        };

        // Unsubscribers
        this.unsubscribers = [];
    }

    /**
     * Inicializa el controller con referencias a elementos del DOM
     */
    async init(options = {}) {
        try {
            this.elements.tbody = options.tbody || document.querySelector('tbody');
            this.elements.paginationContainer = options.paginationContainer || document.querySelector('.pagination-container');
            this.elements.filterContainer = options.filterContainer || document.querySelector('.filter-container');
            this.elements.loadingSpinner = options.loadingSpinner || document.querySelector('.loading-spinner');
            this.elements.errorAlert = options.errorAlert || document.querySelector('.alert-danger');
            this.elements.successAlert = options.successAlert || document.querySelector('.alert-success');

            if (!this.elements.tbody) {
                throw new Error('No se encontró elemento tbody en el DOM');
            }

            // Cargar opciones de filtro
            this.state.setLoading(true);
            const opcionesFiltro = await this.api.getFilterOptions();
            this.state.setOpcionesFiltro(opcionesFiltro);

            // Cargar recibos iniciales
            await this.cargarRecibos();

            // Suscribirse a cambios de estado
            this._subscribirACambiosDeEstado();

            // Inicializar listeners de UI
            this._inicializarListeners();

            this.initialized = true;
            console.log('✅ RecibosTableController inicializado');
        } catch (error) {
            this.state.setError(`Error al inicializar: ${error.message}`);
            console.error('Error en init:', error);
            throw error;
        }
    }

    /**
     * Carga los recibos desde la API
     */
    async cargarRecibos(pagina = 1) {
        try {
            this.state.setLoading(true);
            this.state.setError(null);

            const filtros = this.state.getFiltrosParaAPI();
            const respuesta = await this.api.getRecibos({
                ...filtros,
                page: pagina
            });

            this.state.setRecibos(respuesta.data || []);
            this.state.setPaginacion(respuesta.pagination || {});
            this.state.setLoading(false);
        } catch (error) {
            this.state.setError(`Error al cargar recibos: ${error.message}`);
            this.state.setLoading(false);
            console.error('Error en cargarRecibos:', error);
        }
    }

    /**
     * Carga las opciones disponibles para los filtros
     */
    async cargarOpcionesFiltro() {
        try {
            const opciones = await this.api.getFilterOptions();
            this.state.setOpcionesFiltro(opciones);
        } catch (error) {
            console.error('Error al cargar opciones de filtro:', error);
        }
    }

    /**
     * Aplica nuevos filtros y recarga desde página 1
     */
    async aplicarFiltros(filtrosNuevos) {
        try {
            this.state.setFiltrosActivos(filtrosNuevos);
            await this.cargarRecibos(1); // Siempre empezar en página 1
        } catch (error) {
            console.error('Error al aplicar filtros:', error);
        }
    }

    /**
     * Limpia todos los filtros activos
     */
    async limpiarFiltros() {
        try {
            this.state.limpiarFiltros();
            await this.cargarRecibos(1);
            this.state.setSuccess('Filtros eliminados');
        } catch (error) {
            console.error('Error al limpiar filtros:', error);
        }
    }

    /**
     * Navega a una página específica
     */
    async irAPageina(numeroPagina) {
        try {
            await this.cargarRecibos(numeroPagina);
        } catch (error) {
            console.error('Error al ir a página:', error);
        }
    }

    /**
     * Se suscribe a cambios en el estado
     */
    _subscribirACambiosDeEstado() {
        // Cuando cambien los recibos, renderizar tabla
        const unsub1 = this.state.subscribe('recibos', (recibos) => {
            this.renderizarTabla(recibos);
        });

        // Cuando cambie la paginación, renderizar paginación
        const unsub2 = this.state.subscribe('paginacion', (paginacion) => {
            this.renderizarPaginacion(paginacion);
        });

        // Cuando hay error, mostrar
        const unsub3 = this.state.subscribe('error', (error) => {
            if (this.elements.errorAlert) {
                if (error) {
                    this.elements.errorAlert.textContent = error;
                    this.elements.errorAlert.classList.remove('d-none');
                } else {
                    this.elements.errorAlert.classList.add('d-none');
                }
            }
        });

        // Cuando hay éxito, mostrar
        const unsub4 = this.state.subscribe('successMessage', (mensaje) => {
            if (this.elements.successAlert) {
                if (mensaje) {
                    this.elements.successAlert.textContent = mensaje;
                    this.elements.successAlert.classList.remove('d-none');
                } else {
                    this.elements.successAlert.classList.add('d-none');
                }
            }
        });

        // Cuando hay carga, mostrar spinner
        const unsub5 = this.state.subscribe('loading', (loading) => {
            if (this.elements.loadingSpinner) {
                loading 
                    ? this.elements.loadingSpinner.classList.remove('d-none')
                    : this.elements.loadingSpinner.classList.add('d-none');
            }
        });

        this.unsubscribers = [unsub1, unsub2, unsub3, unsub4, unsub5];
    }

    /**
     * Initializa listeners para botones y enlaces
     */
    _inicializarListeners() {
        if (this.elements.filterContainer) {
            this.elements.filterContainer.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-filter-type]');
                if (btn) {
                    this._mostrarModalFiltro(btn.dataset.filterType);
                }
            });

            // Listener para botón de limpiar filtros
            const btnLimpiar = this.elements.filterContainer.querySelector('[data-action="limpiar-filtros"]');
            if (btnLimpiar) {
                btnLimpiar.addEventListener('click', () => this.limpiarFiltros());
            }
        }

        // Listeners para las filas de la tabla
        if (this.elements.tbody) {
            this.elements.tbody.addEventListener('click', (e) => {
                const btnAccion = e.target.closest('[data-action]');
                if (btnAccion) {
                    const fila = btnAccion.closest('tr');
                    const reciboId = fila?.dataset.reciboId;
                    const numeroPedido = fila?.dataset.numeroPedido;
                    const numeroRecibo = fila?.dataset.numeroRecibo;
                    const accion = btnAccion.dataset.action;

                    this._handleAccionRecibo(accion, reciboId, numeroPedido, numeroRecibo);
                }
            });
        }
    }

    /**
     * Maneja las acciones de los recibos
     */
    _handleAccionRecibo(accion, reciboId, numeroPedido, numeroRecibo) {
        switch (accion) {
            case 'ver-detalles':
                this.state.abrirModalAgregarProceso(null, reciboId);
                break;

            case 'ver-seguimiento':
                this.state.abrirModalSeguimiento(numeroPedido);
                break;

            case 'editar-novedades':
                this.state.abrirModalNovedades(numeroPedido, numeroRecibo);
                break;

            default:
                console.warn(`Acción desconocida: ${accion}`);
        }
    }

    /**
     * Muestra modal de filtro
     */
    _mostrarModalFiltro(tipoFiltro) {
        const opciones = this.state.get(`opcionesFiltro.${tipoFiltro}s`) || [];
        const filtroActual = this.state.get(`filtrosActivos.${tipoFiltro}`) || '';

        // Disparar evento para que el módulo initializer cree el modal
        const evento = new CustomEvent('mostrarModalFiltro', {
            detail: { tipoFiltro, opciones, filtroActual }
        });
        document.dispatchEvent(evento);
    }

    /**
     * Renderiza la tabla de recibos
     */
    renderizarTabla(recibos) {
        if (!this.elements.tbody) {
            return;
        }

        // Limpiar tabla
        this.elements.tbody.innerHTML = '';

        if (!recibos || recibos.length === 0) {
            const fila = document.createElement('tr');
            fila.innerHTML = `
                <td colspan="12" class="text-center py-3 text-muted">
                    No se encontraron recibos
                </td>
            `;
            this.elements.tbody.appendChild(fila);
            return;
        }

        // Renderizar cada fila
        for (const recibo of recibos) {
            const fila = this._renderFila(recibo);
            this.elements.tbody.appendChild(fila);
        }

        // Agregar listeners a las filas
        this._agregarListenersAFilas();
    }

    /**
     * Renderiza una fila individual usando Value Objects
     */
    _renderFila(recibo) {
        const fila = document.createElement('tr');
        fila.dataset.reciboId = recibo.id;
        fila.dataset.numeroPedido = recibo.numero_pedido;
        fila.dataset.numeroRecibo = recibo.numero_recibo;

        // Crear Value Objects para usar sus métodos
        const estado = new EstadoRecibo(recibo.estado || 'No iniciado');
        const area = new AreaRecibo(recibo.area || 'Costura');
        const dias = DiasTranscurridos.fromFechas(recibo.fecha_creacion, new Date());
        const encargado = EncargadoProceso.tryFrom(recibo.encargado_proceso);

        const filaHtml = `
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-action="ver-detalles" title="Ver detalles">
                        <i class="fa fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-outline-info btn-sm" data-action="ver-seguimiento" title="Ver seguimiento">
                        <i class="fa fa-track"></i>
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm" data-action="editar-novedades" title="Novedades">
                        <i class="fa fa-exclamation-triangle"></i>
                    </button>
                </div>
            </td>

            <td>
                <span class="badge" style="background-color: ${estado.getColorHex()}">
                    <i class="fa ${estado.getIcon()}"></i>
                    ${estado.toString()}
                </span>
            </td>

            <td>
                <span class="badge" style="background-color: ${area.getColorHex()}">
                    <i class="fa ${area.getIcon()}"></i>
                    ${area.toString()}
                </span>
            </td>

            <td>
                <span class="badge bg-${dias.getColorBadge()}">
                    <i class="fa ${dias.getIcon()}"></i>
                    ${dias.toString()}
                </span>
            </td>

            <td>
                <strong>${recibo.numero_recibo || 'N/A'}</strong>
            </td>

            <td>${recibo.cliente || 'N/A'}</td>

            <td>${recibo.descripcion || 'N/A'}</td>

            <td class="text-center">${recibo.cantidad || 0}</td>

            <td>
                <span class="badge bg-${recibo.novedades > 0 ? 'danger' : 'light'}">
                    ${recibo.novedades || 0}
                </span>
            </td>

            <td>${this._formatearFecha(recibo.fecha_creacion)}</td>

            <td>${recibo.dia_entrega || 'N/A'}</td>

            <td>
                ${encargado ? `
                    <img src="${encargado.getAvatarUrl()}" 
                         alt="${encargado.getNombre()}"
                         class="img-fluid rounded-circle"
                         style="width: 30px; height: 30px;"
                         title="${encargado.getNombre()}">
                ` : '<span class="text-muted">N/A</span>'}
            </td>
        `;

        fila.innerHTML = filaHtml;
        return fila;
    }

    /**
     * Renderiza los controles de paginación
     */
    renderizarPaginacion(paginacion) {
        if (!this.elements.paginationContainer) {
            return;
        }

        this.elements.paginationContainer.innerHTML = '';

        if (paginacion.last_page <= 1) {
            return;
        }

        const nav = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination';

        // Botón anterior
        if (paginacion.current_page > 1) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `
                <a class="page-link" href="#" data-page="${paginacion.current_page - 1}">
                    &laquo; Anterior
                </a>
            `;
            ul.appendChild(li);
        }

        // Números de página
        for (let i = 1; i <= Math.min(paginacion.last_page, 5); i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === paginacion.current_page ? 'active' : ''}`;
            li.innerHTML = `
                <a class="page-link" href="#" data-page="${i}">
                    ${i}
                </a>
            `;
            ul.appendChild(li);
        }

        // Si hay más de 5 páginas, mostrar puntos suspensivos
        if (paginacion.last_page > 5) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">...</span>';
            ul.appendChild(li);
        }

        // Botón siguiente
        if (paginacion.current_page < paginacion.last_page) {
            const li = document.createElement('li');
            li.className = 'page-item';
            li.innerHTML = `
                <a class="page-link" href="#" data-page="${paginacion.current_page + 1}">
                    Siguiente &raquo;
                </a>
            `;
            ul.appendChild(li);
        }

        // Agregar listeners a links de paginación
        ul.querySelectorAll('[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const pagina = parseInt(link.dataset.page);
                this.irAPageina(pagina);
            });
        });

        nav.appendChild(ul);
        this.elements.paginationContainer.appendChild(nav);
    }

    /**
     * Agregar listeners a las filas de la tabla
     */
    _agregarListenersAFilas() {
        const filas = this.elements.tbody?.querySelectorAll('tr[data-recibo-id]') || [];

        filas.forEach(fila => {
            fila.addEventListener('mouseover', () => {
                fila.style.backgroundColor = '#f5f5f5';
            });

            fila.addEventListener('mouseout', () => {
                fila.style.backgroundColor = '';
            });
        });
    }

    /**
     * Formatea una fecha a formato legible
     */
    _formatearFecha(fecha) {
        if (!fecha) {
            return 'N/A';
        }

        const date = new Date(fecha);
        return date.toLocaleDateString('es-MX', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    /**
     * Destruye el controller y limpia recursos
     */
    destroy() {
        // Desuscribirse de todos los cambios
        for (const unsub of this.unsubscribers) {
            unsub();
        }

        this.initialized = false;
    }
}

/**
 * Exportar clase
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RecibosTableController;
}
