/**
 * Módulo: CrearPedidoApp
 * Responsabilidad: Orquestar la aplicación - Patrón Facade
 * Principio SRP: solo responsable de coordinar componentes
 * Principio DIP: inyecta todas sus dependencias
 */
import { CotizacionRepository } from './CotizacionRepository.js';
import { CotizacionSearchUIController } from './CotizacionSearchUIController.js';
import { PrendasUIController } from './PrendasUIController.js';
import { FormularioPedidoController } from './FormularioPedidoController.js';
import { FormInfoUpdater } from './FormInfoUpdater.js';
import { CotizacionDataLoader } from './CotizacionDataLoader.js';

/**
 * Aplicación principal para creación de pedidos desde cotización
 * Utiliza patrón Facade para simplificar la orquestación
 */
export class CrearPedidoApp {
    constructor(initialData = {}) {
        this.cotizacionesData = initialData.cotizaciones || [];
        this.asesorActual = initialData.asesorActual || '';
        this.csrfToken = initialData.csrfToken || '';

        this.initializeComponents();
        this.attachHandlers();
    }

    /**
     * Inicializa componentes
     */
    initializeComponents() {
        // Repository de cotizaciones
        this.cotizacionRepository = new CotizacionRepository(
            this.obtenerCotizacionesDelAsesor()
        );

        // UI Controller para búsqueda
        this.cotizacionSearchUI = new CotizacionSearchUIController(
            this.cotizacionRepository,
            {
                searchInput: document.getElementById('cotizacion_search'),
                hiddenInput: document.getElementById('cotizacion_id'),
                dropdown: document.getElementById('cotizacion_dropdown'),
                selectedDiv: document.getElementById('cotizacion_selected'),
                selectedText: document.getElementById('cotizacion_selected_text'),
            }
        );

        // UI Controller para prendas
        this.prendasUI = new PrendasUIController({
            container: document.getElementById('prendas-container'),
        });

        // Updater de información del formulario
        this.formInfoUpdater = new FormInfoUpdater({
            numeroCotizacion: document.getElementById('numero_cotizacion'),
            cliente: document.getElementById('cliente'),
            asesora: document.getElementById('asesora'),
            formaPago: document.getElementById('forma_de_pago'),
            numeroPedido: document.getElementById('numero_pedido'),
        });

        // Controller del formulario
        this.formularioPedido = new FormularioPedidoController(
            document.getElementById('formCrearPedido'),
            {
                cotizacionSearch: this.cotizacionSearchUI,
                prendasUI: this.prendasUI,
                formInfoUpdater: this.formInfoUpdater,
                csrfToken: this.csrfToken,
            }
        );
    }

    /**
     * Obtiene cotizaciones del asesor actual
     */
    obtenerCotizacionesDelAsesor() {
        return this.cotizacionesData.filter(cot => cot.asesora === this.asesorActual);
    }

    /**
     * Adjunta handlers globales
     */
    attachHandlers() {
        // Click en item de dropdown
        document.addEventListener('click', (e) => {
            const item = e.target.closest('.cotizacion-item');
            if (item) {
                const id = parseInt(item.getAttribute('data-id'));
                const cotizacion = this.cotizacionRepository.obtenerPorId(id);
                
                if (cotizacion) {
                    this.cotizacionSearchUI.seleccionar(cotizacion, (cot) => {
                        this.cargarCotizacion(cot.id);
                    });
                }
            }
        });

        // Eliminar item de dropdown al hacerlo
        document.addEventListener('click', (e) => {
            if (e.target.closest('.cotizacion-item')) {
                e.preventDefault();
            }
        });
    }

    /**
     * Carga datos de cotización seleccionada
     */
    async cargarCotizacion(cotizacionId) {
        try {
            const data = await CotizacionDataLoader.cargar(cotizacionId);
            const cotizacion = this.cotizacionRepository.obtenerPorId(cotizacionId);

            // Actualizar información del formulario
            this.formularioPedido.actualizarInfo(cotizacion, data);

            // Cargar prendas
            this.prendasUI.cargar(data.prendas || []);

        } catch (error) {
            console.error('Error al cargar cotización:', error);
            this.prendasUI.container.innerHTML = 
                `<p class="text-red-500">Error al cargar las prendas: ${error.message}</p>`;
        }
    }

    /**
     * Inicializa la aplicación completamente
     */
    async inicializar() {
        try {
            // Cargar próximo número de pedido
            const data = await CotizacionDataLoader.cargarProximoNumero();
            this.formInfoUpdater.establecerNumeroPedido(data.siguiente_pedido);
        } catch (error) {
            console.warn('No se pudo cargar próximo número:', error);
        }
    }
}
