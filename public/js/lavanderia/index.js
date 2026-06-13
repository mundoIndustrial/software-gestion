/**
 * LAVANDERÍA MODULE - Index
 * Orquesta todos los módulos de lavandería
 */

import { showToast, debounce } from './utilities.js';
import { SearchHandler } from './search-handler.js';
import { TallasHandler } from './tallas-handler.js';
import { MovementsHandler } from './movements-handler.js';
import { SignatureHandler } from './signature-handler.js';
import { RegistrationHandler } from './registration-handler.js';
import { MultiReceiptHandler } from './multi-receipt-handler.js';
import { ManualPrendaHandler } from './manual-prenda-handler.js';
import { SalidaLoader } from './salida-loader.js';

class LavanderiaManager {
    constructor() {
        this.apiSearchUrl = window.apiSearchUrl || '';
        if (!this.apiSearchUrl) {
            console.error('[LavanderiaManager] apiSearchUrl no está definido');
            return;
        }
        
        this.searchHandler = new SearchHandler(this.apiSearchUrl);
        this.tallasHandler = new TallasHandler();
        this.multiReceiptHandler = new MultiReceiptHandler();
        this.manualPrendaHandler = new ManualPrendaHandler();
        this.movementsHandler = new MovementsHandler(this.apiSearchUrl);
        this.signatureHandler = new SignatureHandler(this.apiSearchUrl);
        this.registrationHandler = new RegistrationHandler(this.apiSearchUrl, this.tallasHandler, this.multiReceiptHandler, this.manualPrendaHandler);
        this.salidaLoader = new SalidaLoader(this.apiSearchUrl);
        
        this.init();
    }

    init() {
        try {
            this.setupEventListeners();
            this.setupCustomEvents();
            this.movementsHandler.loadMovements();
            
            // Ocultar pantalla de carga
            const loadingScreen = document.getElementById('loadingScreen');
            if (loadingScreen) {
                loadingScreen.style.display = 'none';
            }
        } catch (error) {
            console.error('[LavanderiaManager] Error en init:', error);
        }
    }

    /**
     * Configura los event listeners del DOM
     */
    setupEventListeners() {
        // Sincronizar radios circulares con select oculto
        const radioButtons = document.querySelectorAll('input[name="tipoMovimiento"]');
        const selectTipoMovimiento = document.getElementById('selectTipoMovimiento');

        if (radioButtons.length > 0) {
            radioButtons.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    if (selectTipoMovimiento && e.target.checked) {
                        selectTipoMovimiento.value = e.target.value;
                        console.log('[LavanderiaManager] Tipo de movimiento actualizado a:', e.target.value);
                        
                        // Mostrar/ocultar selector de movimiento de salida según tipo
                        const selectorContainer = document.getElementById('selectorMovimientoSalidaContainer');
                        const searchRecibosContainer = document.getElementById('searchRecibosContainer');
                        
                        if (e.target.value === 'ENTRADA') {
                            // Mostrar AMBAS opciones: selector de salida Y búsqueda de recibos
                            if (selectorContainer) selectorContainer.style.display = 'block';
                            if (searchRecibosContainer) searchRecibosContainer.style.display = 'block';
                            
                            // Cargar movimientos de salida
                            this.salidaLoader.loadMovimientosSalida();
                        } else {
                            // Mostrar solo búsqueda de recibos para SALIDA
                            if (selectorContainer) selectorContainer.style.display = 'none';
                            if (searchRecibosContainer) searchRecibosContainer.style.display = 'block';
                            
                            // Limpiar selección de salida
                            this.salidaLoader.clear();
                        }
                        
                        // Clear form and selected recibos when tipoMovimiento changes
                        this.registrationHandler.clearForm();
                        // Clear search input
                        const searchInput = document.getElementById('searchRecibo');
                        if (searchInput) {
                            searchInput.value = '';
                            const results = document.querySelector('.autocomplete-results');
                            if (results) results.classList.remove('active');
                        }
                    }
                });
            });
        }

        // Sincronizar select con radios (para compatibilidad)
        if (selectTipoMovimiento) {
            selectTipoMovimiento.addEventListener('change', (e) => {
                const radio = document.querySelector(`input[name="tipoMovimiento"][value="${e.target.value}"]`);
                if (radio) {
                    radio.checked = true;
                }
            });
        }

        // Búsqueda de movimientos
        const searchMovimientosInput = document.getElementById('searchMovimientosInput');
        const searchMovimientosClear = document.getElementById('searchMovimientosClear');
        
        if (searchMovimientosInput) {
            searchMovimientosInput.addEventListener('input', debounce((e) => {
                const query = e.target.value.trim();
                
                // Mostrar/ocultar botón de limpiar
                if (searchMovimientosClear) {
                    searchMovimientosClear.style.display = query.length > 0 ? 'flex' : 'none';
                }
                
                this.movementsHandler.searchMovements(query);
            }, 300));
        } else {
            console.warn('[LavanderiaManager] searchMovimientosInput no encontrado');
        }

        // Botón limpiar búsqueda
        if (searchMovimientosClear) {
            searchMovimientosClear.addEventListener('click', (e) => {
                e.preventDefault();
                searchMovimientosInput.value = '';
                searchMovimientosClear.style.display = 'none';
                this.movementsHandler.searchMovements('');
                searchMovimientosInput.focus();
            });
        }

        // Botón abrir modal
        const btnAbrirModal = document.getElementById('btnAbrirModalSalida');
        if (btnAbrirModal) {
            btnAbrirModal.addEventListener('click', () => this.registrationHandler.openModalSalida());
        } else {
            console.warn('[LavanderiaManager] btnAbrirModalSalida no encontrado');
        }

        // Búsqueda de recibos
        const searchInput = document.getElementById('searchRecibo');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchHandler.handleSearch(e));
            searchInput.addEventListener('blur', () => {
                setTimeout(() => {
                    const results = document.querySelector('.autocomplete-results');
                    if (results) results.classList.remove('active');
                }, 200);
            });
        } else {
            console.warn('[LavanderiaManager] searchRecibo no encontrado');
        }

        // Botón registrar
        const btnRegistrar = document.getElementById('btnRegistrarSalida');
        if (btnRegistrar) {
            btnRegistrar.addEventListener('click', () => this.registrationHandler.registrarSalida());
        } else {
            console.warn('[LavanderiaManager] btnRegistrarSalida no encontrado');
        }

        // Botón agregar prenda manual
        const btnAgregarPrendaManual = document.getElementById('btnAgregarPrendaManual');
        if (btnAgregarPrendaManual) {
            btnAgregarPrendaManual.addEventListener('click', () => {
                const form = document.getElementById('formAgregarPrendaManual');
                if (form) {
                    const shouldShow = form.style.display === 'none';
                    form.style.display = shouldShow ? 'block' : 'none';
                    if (shouldShow) {
                        this.registrationHandler.inicializarFormPrendaManual();
                    }
                }
            });
        }

        // Botón cerrar formulario prenda manual
        const btnCerrarFormPrenda = document.getElementById('btnCerrarFormPrenda');
        if (btnCerrarFormPrenda) {
            btnCerrarFormPrenda.addEventListener('click', () => {
                const form = document.getElementById('formAgregarPrendaManual');
                if (form) {
                    form.style.display = 'none';
                }
                const inputDescripcion = document.getElementById('inputDescripcionPrenda');
                if (inputDescripcion && !this.registrationHandler.hasManualPrendaResumen()) {
                    inputDescripcion.value = '';
                }
                this.registrationHandler.renderManualPrendaResumen();
            });
        }
        const btnAgregarTallasManual = document.getElementById('btnAgregarTallasManual');
        if (btnAgregarTallasManual) {
            btnAgregarTallasManual.addEventListener('click', () => {
                this.registrationHandler.agregarPrendaManual();
            });
        }

        const btnCerrarModalSelectorTallasManual = document.getElementById('btnCerrarModalSelectorTallasManual');
        if (btnCerrarModalSelectorTallasManual) {
            btnCerrarModalSelectorTallasManual.addEventListener('click', () => {
                this.registrationHandler.closeManualPrendaWizard({ clearDraft: true, restoreForm: true });
            });
        }

        // Firma - Limpiar
        const btnLimpiar = document.getElementById('btnLimpiarFirma');
        if (btnLimpiar) {
            btnLimpiar.addEventListener('click', () => {
                if (this.signatureHandler.signatureCapture) {
                    this.signatureHandler.signatureCapture.clear();
                }
            });
        }

        // Firma - Cancelar
        const btnCancelarFirma = document.getElementById('btnCancelarFirma');
        if (btnCancelarFirma) {
            btnCancelarFirma.addEventListener('click', () => {
                document.getElementById('modalFirmaSalida').classList.remove('active');
            });
        }

        // Firma - Guardar
        const btnGuardarFirma = document.getElementById('btnGuardarFirma');
        if (btnGuardarFirma) {
            btnGuardarFirma.addEventListener('click', () => this.signatureHandler.guardarFirma());
        }

        // Firma - Rotar izquierda
        const btnRotarIzquierda = document.getElementById('btnRotarIzquierda');
        if (btnRotarIzquierda) {
            btnRotarIzquierda.addEventListener('click', () => this.signatureHandler.rotarFirmaIzquierda());
        }

        // Firma - Rotar derecha
        const btnRotarDerecha = document.getElementById('btnRotarDerecha');
        if (btnRotarDerecha) {
            btnRotarDerecha.addEventListener('click', () => this.signatureHandler.rotarFirmaDerecha());
        }

        // Tabs
        const tabButtons = document.querySelectorAll('.tab-button');
        if (tabButtons.length > 0) {
            tabButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const tabType = e.currentTarget.dataset.tab;
                    this.movementsHandler.filterMovementsByTab(tabType);
                });
            });
        } else {
            console.warn('[LavanderiaManager] tab-button no encontrados');
        }

        // Paginación
        const btnPrevPage = document.getElementById('btnPrevPage');
        const btnNextPage = document.getElementById('btnNextPage');

        if (btnPrevPage) {
            btnPrevPage.addEventListener('click', () => {
                if (this.movementsHandler.currentPage > 1) {
                    this.movementsHandler.currentPage--;
                    this.movementsHandler.renderPaginatedMovements();
                }
            });
        }

        if (btnNextPage) {
            btnNextPage.addEventListener('click', () => {
                const totalPages = this.movementsHandler.getTotalPages();
                if (this.movementsHandler.currentPage < totalPages) {
                    this.movementsHandler.currentPage++;
                    this.movementsHandler.renderPaginatedMovements();
                }
            });
        }

        // Modales - Cerrar
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (!modal) return;

                if (modal.id === 'modalSelectorTallasManual') {
                    this.registrationHandler.closeManualPrendaWizard({ clearDraft: true, restoreForm: true });
                    return;
                }

                modal.classList.remove('active');
            });
        });

        // Modales - Cerrar al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    if (modal.id === 'modalSelectorTallasManual') {
                        this.registrationHandler.closeManualPrendaWizard({ clearDraft: true, restoreForm: true });
                        return;
                    }
                    modal.classList.remove('active');
                }
            });
        });
    }

    /**
     * Configura los eventos personalizados
     */
    setupCustomEvents() {
        // Evento: Recibo seleccionado
        window.addEventListener('reciboSelected', (e) => {
            const recibo = e.detail;
            this.registrationHandler.handleReciboSelected(recibo);
        });

        // Evento: Prendas de salida cargadas
        window.addEventListener('prendasSalidaLoaded', (e) => {
            const { movimientoId, prendas } = e.detail;
            this.registrationHandler.handlePrendasSalidaLoaded(prendas);
        });

        // Evento: Abrir modal de firma
        window.addEventListener('openFirmaModal', (e) => {
            this.signatureHandler.openModalFirmaSalida(e.detail.movementId);
        });

        // Evento: Abrir modal de ver firma
        window.addEventListener('openVerFirmaModal', (e) => {
            this.signatureHandler.openModalVerFirma(e.detail.firmaUrl);
        });

        // Evento: Recargar movimientos
        window.addEventListener('reloadMovements', () => {
            this.movementsHandler.loadMovements();
        });

        // Evento: Mostrar toast
        window.addEventListener('showToast', (e) => {
            const { title, message, type } = e.detail;
            showToast(title, message, type);
        });
    }
}

// Inicializar inmediatamente
function initLavanderia() {
    console.log('[Lavandería] Inicializando módulo...');
    window.lavanderiaManager = new LavanderiaManager();
    console.log('[Lavandería] Módulo inicializado');
}

// Intentar inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLavanderia);
} else {
    // El DOM ya está listo
    initLavanderia();
}

export { LavanderiaManager };



