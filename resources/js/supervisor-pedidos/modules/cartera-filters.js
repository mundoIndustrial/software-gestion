/**
 * Cartera Filters Module - Gestor de filtros para cartera de pedidos
 *
 * Responsabilidades:
 * - Abrir/cerrar modales de filtro
 * - Buscar sugerencias (cliente, número, fecha)
 * - Aplicar filtros
 * - Mostrar notificaciones
 *
 * Migrado de: public/js/cartera-pedidos/cartera-filtros-compartidos.js
 */

export class CarteraFilters {
    constructor() {
        this.filtroFechaActual = '';
        this.filtroClienteActual = '';
        this.filtroNumeroActual = '';

        // Autocomplete state
        this.sugerenciaSeleccionada = -1;
        this.sugerenciasCliente = [];
        this.sugerenciasNumero = [];
        this.sugerenciasFecha = [];
        this.busquedaTimeout = null;

        this.init();
    }

    init() {
        // Exponer funciones globales para compatibilidad con código legacy
        window.mostrarNotificacion = (msg, tipo = 'info') => this.mostrarNotificacion(msg, tipo);
        window.abrirModalFiltro = (tipo, event) => this.abrirModalFiltro(tipo, event);
        window.limpiarTodosLosFiltros = () => this.limpiarTodosLosFiltros();
        window.verificarFiltrosActivos = () => this.verificarFiltrosActivos();
        window.buscarSugerenciasFecha = () => this.buscarSugerenciasFecha();
        window.verFactura = (id, num) => this.verFactura(id, num);

        // Event listeners para inputs de filtro
        this.attachEventListeners();

        console.log('[CarteraFilters] ✅ Initialized');
    }

    /**
     * Mostrar notificación toast
     */
    mostrarNotificacion(mensaje, tipo = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${tipo}`;
        toast.textContent = mensaje;

        const toastContainer = document.getElementById('toastContainer');
        if (toastContainer) {
            toastContainer.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOutRight 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    }

    /**
     * Abrir modal de filtro
     */
    abrirModalFiltro(tipo, event) {
        if (event) {
            event.stopPropagation();
        }

        const tipo_capitalized = tipo.charAt(0).toUpperCase() + tipo.slice(1);
        const modal = document.getElementById(`modalFiltro${tipo_capitalized}`);

        if (modal) {
            modal.classList.add('active');
            modal.style.display = 'block';

            const input = document.getElementById(`filtro${tipo_capitalized}Input`);
            if (input) {
                input.value = '';
                input.focus();
            }

            // Cerrar al hacer clic fuera
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    this.cerrarModalFiltro(tipo);
                }
            });
        }
    }

    /**
     * Cerrar modal de filtro
     */
    cerrarModalFiltro(tipo) {
        const tipo_capitalized = tipo.charAt(0).toUpperCase() + tipo.slice(1);
        const modal = document.getElementById(`modalFiltro${tipo_capitalized}`);

        if (modal) {
            modal.classList.remove('active');
            modal.style.display = 'none';

            // Limpiar sugerencias
            const sugerenciasDiv = document.getElementById(`sugerencias${tipo_capitalized}`);
            if (sugerenciasDiv) {
                sugerenciasDiv.innerHTML = '';
            }
        }
    }

    /**
     * Limpiar todos los filtros
     */
    limpiarTodosLosFiltros() {
        this.filtroFechaActual = '';
        this.filtroClienteActual = '';
        this.filtroNumeroActual = '';

        const inputFecha = document.getElementById('filtroFechaInput');
        const inputCliente = document.getElementById('filtroClienteInput');
        const inputNumero = document.getElementById('filtroNumeroInput');
        const inputEstado = document.getElementById('filtroEstadoInput');

        if (inputFecha) inputFecha.value = '';
        if (inputCliente) inputCliente.value = '';
        if (inputNumero) inputNumero.value = '';
        if (inputEstado) inputEstado.value = '';

        // Remover badges de filtros activos
        document.querySelectorAll('.filter-badge').forEach(badge => {
            badge.textContent = '';
        });

        // Disparar evento de filtros limpiados
        document.dispatchEvent(new CustomEvent('filters:cleared'));

        this.mostrarNotificacion('Filtros limpiados', 'info');
    }

    /**
     * Verificar si hay filtros activos
     */
    verificarFiltrosActivos() {
        const hayFiltros = this.filtroFechaActual || this.filtroClienteActual || this.filtroNumeroActual;

        const btnLimpiar = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiar) {
            btnLimpiar.style.display = hayFiltros ? 'block' : 'none';
        }
    }

    /**
     * Buscar sugerencias de fecha
     */
    buscarSugerenciasFecha() {
        const input = document.getElementById('filtroFechaInput');
        if (!input) return;

        const valor = input.value.trim().toLowerCase();

        if (valor.length < 2) {
            this.sugerenciasFecha = [];
            return;
        }

        // Simular búsqueda - en futuro conectar a API
        const fechasDisponibles = this.obtenerFechasDisponibles();
        this.sugerenciasFecha = fechasDisponibles.filter(fecha =>
            fecha.toLowerCase().includes(valor)
        );

        this.renderizarSugerenciasFecha();
    }

    /**
     * Renderizar sugerencias de fecha
     */
    renderizarSugerenciasFecha() {
        const sugerenciasDiv = document.getElementById('sugerenciasFecha');
        if (!sugerenciasDiv) return;

        sugerenciasDiv.innerHTML = '';

        this.sugerenciasFecha.forEach((fecha, index) => {
            const li = document.createElement('li');
            li.textContent = fecha;
            li.onclick = () => this.seleccionarFecha(fecha);
            li.onmouseover = () => this.hoverSugerencia(index);
            li.onmouseout = () => this.unhoverSugerencia();
            sugerenciasDiv.appendChild(li);
        });
    }

    /**
     * Seleccionar fecha
     */
    seleccionarFecha(fecha) {
        this.filtroFechaActual = fecha;
        const input = document.getElementById('filtroFechaInput');
        if (input) input.value = fecha;

        this.cerrarModalFiltro('fecha');
        this.aplicarFiltros();
    }

    /**
     * Obtener fechas disponibles (stub - conectar a API en futuro)
     */
    obtenerFechasDisponibles() {
        // En futuro: conectar a API
        return [
            '2026-04-20',
            '2026-04-21',
            '2026-04-22',
            '2026-04-23',
        ];
    }

    /**
     * Aplicar todos los filtros activos
     */
    aplicarFiltros() {
        // Disparar evento para que app.js maneje la búsqueda
        document.dispatchEvent(new CustomEvent('filters:applied', {
            detail: {
                fecha: this.filtroFechaActual,
                cliente: this.filtroClienteActual,
                numero: this.filtroNumeroActual,
            },
        }));

        // Mostrar badges de filtros activos
        this.verificarFiltrosActivos();
    }

    /**
     * Hover en sugerencia
     */
    hoverSugerencia(index) {
        this.sugerenciaSeleccionada = index;
    }

    /**
     * Unhover en sugerencia
     */
    unhoverSugerencia() {
        this.sugerenciaSeleccionada = -1;
    }

    /**
     * Ver factura
     */
    verFactura(pedidoId, numeroPedido) {
        console.log('[CarteraFilters] Ver factura:', { pedidoId, numeroPedido });
        // Implementar vista de factura en futuro
    }

    /**
     * Adjuntar event listeners
     */
    attachEventListeners() {
        // Listener para input de fecha
        const inputFecha = document.getElementById('filtroFechaInput');
        if (inputFecha) {
            inputFecha.addEventListener('input', (e) => {
                clearTimeout(this.busquedaTimeout);
                this.busquedaTimeout = setTimeout(() => {
                    this.buscarSugerenciasFecha();
                }, 300);
            });

            inputFecha.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const valor = inputFecha.value.trim();
                    if (valor) {
                        this.seleccionarFecha(valor);
                    }
                }
            });
        }

        // Listeners para botones de filtro (delegados)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-filter-column')) {
                const columnType = e.target.closest('[data-column]')?.dataset.column;
                if (columnType) {
                    this.abrirModalFiltro(columnType, e);
                }
            }
        });
    }
}

/**
 * Inicializar filters cuando el DOM esté listo
 */
export async function initializeFilters() {
    return new Promise((resolve) => {
        const checkDOM = () => {
            // Esperar a que exista al menos uno de los elementos esperados
            if (
                document.getElementById('filtroFechaInput') ||
                document.getElementById('filterBtnDate') ||
                document.querySelector('[data-column]')
            ) {
                const filters = new CarteraFilters();
                window.carteraFilters = filters;
                console.log('[initializeFilters] ✅ Filters ready');
                resolve(filters);
            } else {
                setTimeout(checkDOM, 100);
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', checkDOM);
        } else {
            checkDOM();
        }
    });
}
