/**
 * init-recibos-costura.js - Inicializador del módulo de recibos de costura
 *
 * Este archivo es el único que debe incluirse en el Blade:
 * <script src="/js/recibos-costura/init-recibos-costura.js"></script>
 *
 * Coordina:
 * 1. Carga de dependencias
 * 2. Inicialización de State Manager
 * 3. Initialización de API Client
 * 4. Inicialización del Table Controller
 * 5. Setup de filtros
 *
 * @file Punto de entrada para el módulo
 */

class RecibosCostruaModule {
    constructor() {
        this.state = null;
        this.api = null;
        this.tableController = null;
        this.initialized = false;
    }

    /**
     * Valida que todas las dependencias estén cargadas
     */
    _validarDependencias() {
        const dependencias = [
            'EstadoRecibo',
            'AreaRecibo',
            'DiasTranscurridos',
            'EncargadoProceso',
            'RecibosState',
            'ReciboCosturaAPI',
            'RecibosTableController'
        ];

        const faltantes = dependencias.filter(dep => typeof window[dep] === 'undefined');

        if (faltantes.length > 0) {
            throw new Error(
                `Dependencias faltantes: ${faltantes.join(', ')}. ` +
                `Asegúrate de incluir todos los scripts en el orden correcto.`
            );
        }
    }

    /**
     * Inicializa el módulo
     */
    async init() {
        try {
            console.log(' Inicializando módulo de recibos de costura...');

            // 1. Validar dependencias
            this._validarDependencias();

            // 2. Obtener instancias singleton
            this.state = RecibosState.getInstance();
            this.api = new ReciboCosturaAPI();
            this.tableController = new RecibosTableController();

            // 3. Inicializar el controller de tabla
            await this.tableController.init({
                tbody: document.querySelector('table tbody'),
                paginationContainer: document.querySelector('.pagination-container'),
                filterContainer: document.querySelector('.filter-container'),
                loadingSpinner: document.querySelector('.loading-spinner'),
                errorAlert: document.querySelector('.alert-danger'),
                successAlert: document.querySelector('.alert-success')
            });

            // 4. Setup de filtros dinámicos
            this._setupFiltrosDinamicos();

            // 5. Exponer módulo globalmente para acceso externo
            window.recibosCostruaModule = this;

            this.initialized = true;
            console.log(' Módulo de recibos de costura inicializado exitosamente');

            // Disparar evento de inicialización
            const evento = new CustomEvent('recibosCostruaModuleReady', {
                detail: { module: this }
            });
            document.dispatchEvent(evento);

        } catch (error) {
            console.error(' Error al inicializar módulo:', error);
            this._mostrarErrorFatal(error.message);
            throw error;
        }
    }

    /**
     * Setup de filtros dinámicos
     */
    _setupFiltrosDinamicos() {
        // Escuchar evento de mostrar modal de filtro
        document.addEventListener('mostrarModalFiltro', (e) => {
            const { tipoFiltro, opciones, filtroActual } = e.detail;
            this._mostrarModalFiltro(tipoFiltro, opciones, filtroActual);
        });
    }

    /**
     * Muestra un modal para seleccionar filtro
     */
    _mostrarModalFiltro(tipoFiltro, opciones, filtroActual) {
        // Crear modal dinámicamente
        const modalId = `filtroModal-${tipoFiltro}`;
        let modal = document.getElementById(modalId);

        if (!modal) {
            modal = document.createElement('div');
            modal.id = modalId;
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Filtrar por ${this._capitalizarTexto(tipoFiltro)}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="filter-options"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary btn-aplicar-filtro">Aplicar Filtro</button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Poblar opciones
        const optionsContainer = modal.querySelector('.filter-options');
        optionsContainer.innerHTML = '';

        opciones.forEach(opcion => {
            const div = document.createElement('div');
            div.className = 'form-check';
            div.innerHTML = `
                <input class="form-check-input filtro-opcion" type="radio" 
                       name="${tipoFiltro}" value="${opcion}" id="${tipoFiltro}-${opcion}"
                       ${opcion === filtroActual ? 'checked' : ''}>
                <label class="form-check-label" for="${tipoFiltro}-${opcion}">
                    ${opcion}
                </label>
            `;
            optionsContainer.appendChild(div);
        });

        // Listener para botón de aplicar
        const btnAplicar = modal.querySelector('.btn-aplicar-filtro');
        btnAplicar.onclick = () => {
            const seleccionado = modal.querySelector(`input[name="${tipoFiltro}"]:checked`);
            if (seleccionado) {
                const filtros = {};
                filtros[tipoFiltro] = seleccionado.value;
                this.tableController.aplicarFiltros(filtros);

                // Cerrar modal
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            }
        };

        // Mostrar modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    /**
     * Muestra un error fatal en la UI
     */
    _mostrarErrorFatal(mensaje) {
        const alertaError = document.querySelector('.alert-danger');
        if (alertaError) {
            alertaError.innerHTML = `
                <strong>Error Fatal:</strong> ${mensaje}
            `;
            alertaError.classList.remove('d-none');
        } else {
            alert(`Error Fatal: ${mensaje}`);
        }
    }

    /**
     * Capitaliza un texto
     */
    _capitalizarTexto(texto) {
        return texto.charAt(0).toUpperCase() + texto.slice(1).replace(/_/g, ' ');
    }
}

/**
 * Inicializar cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', async () => {
    try {
        const modulo = new RecibosCostruaModule();
        await modulo.init();
    } catch (error) {
        console.error('Error en DOMContentLoaded:', error);
    }
});

/**
 * Exportar para testing
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RecibosCostruaModule;
}
