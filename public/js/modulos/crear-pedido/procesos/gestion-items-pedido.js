/**
 * Gestión de Ítems - Capa de Presentación (Refactorizado - SOLID)
 * 
 * Esta clase orquesta los servicios especializados que manejan:
 * - ItemFormCollector: Recolecta datos de formularios
 * - PrendaEditor: Gestiona edición de prendas
 * - ItemRenderer: Renderiza UI
 * - NotificationService: Maneja notificaciones
 * - ItemAPIService: Comunica con backend
 */

class GestionItemsUI {
    constructor(options = {}) {
        this.prendaEditIndex = null;
        this.items = [];
        this.prendas = [];      // Array separado para prendas
        this.epps = [];         // Array separado para EPPs
        this.ordenItems = [];   // Orden de inserción: [{tipo: 'prenda', index: 0}, {tipo: 'epp', index: 0}, ...]
        
        // Inicializar servicios con validación de disponibilidad
        try {
            this.notificationService = options.notificationService || (typeof NotificationService !== 'undefined' ? new NotificationService() : null);
            this.apiService = options.apiService || (typeof ItemAPIService !== 'undefined' ? new ItemAPIService() : null);
            this.formCollector = options.formCollector || (typeof ItemFormCollector !== 'undefined' ? new ItemFormCollector() : null);
            this.renderer = options.renderer || (typeof ItemRenderer !== 'undefined' && this.apiService ? new ItemRenderer({ apiService: this.apiService }) : null);
            this.prendaEditor = options.prendaEditor || (typeof PrendaEditor !== 'undefined' && this.notificationService ? new PrendaEditor({ notificationService: this.notificationService }) : null);
            
            // Solo inicializar si los servicios esenciales están disponibles
            if (this.formCollector && this.notificationService) {
                this.inicializar();
            } else {
                console.warn('[GestionItemsUI] Servicios esenciales no disponibles. Inicialización diferida.');
            }
        } catch (error) {
            console.error('[GestionItemsUI] Error en inicialización de servicios:', error);
        }
    }

    /**
     * Obtener todos los items en orden de inserción
     */
    obtenerItemsOrdenados() {
        const itemsOrdenados = [];
        this.ordenItems.forEach(({ tipo, index }) => {
            if (tipo === 'prenda' && this.prendas[index]) {
                itemsOrdenados.push(this.prendas[index]);
            } else if (tipo === 'epp' && this.epps[index]) {
                itemsOrdenados.push(this.epps[index]);
            }
        });
        return itemsOrdenados;
    }

    /**
     * Agregar prenda y registrar en orden
     */
    agregarPrendaAlOrden(prenda) {
        const index = this.prendas.length;
        this.prendas.push(prenda);
        this.ordenItems.push({ tipo: 'prenda', index });
        console.log('[GestionItemsUI] Prenda agregada. Orden actual:', this.ordenItems);
    }

    /**
     * Agregar EPP y registrar en orden
     */
    agregarEPPAlOrden(epp) {
        const index = this.epps.length;
        this.epps.push(epp);
        this.ordenItems.push({ tipo: 'epp', index });
        console.log('[GestionItemsUI] EPP agregado. Orden actual:', this.ordenItems);
        return index;
    }

    /**
     * Método público para agregar EPP desde modal externo
     */
    async agregarEPPDesdeModal(eppData) {
        try {
            console.log('[GestionItemsUI.agregarEPPDesdeModal] Agregando EPP:', eppData);
            
            // Agregar al orden
            this.agregarEPPAlOrden(eppData);
            
            // Notificar éxito
            this.notificationService?.exito('EPP agregado correctamente');
            
            // Actualizar visualización en orden
            if (this.renderer) {
                const itemsOrdenados = this.obtenerItemsOrdenados();
                await this.renderer.actualizar(itemsOrdenados);
            }
            
            return true;
        } catch (error) {
            console.error('[GestionItemsUI.agregarEPPDesdeModal] ERROR:', error);
            this.notificationService?.error('Error al agregar EPP: ' + error.message);
            return false;
        }
    }

    inicializar() {
        this.attachEventListeners();
        
        if (document.getElementById('btn-agregar-item-cotizacion') || 
            document.getElementById('btn-agregar-item-tipo')) {
            this.cargarItems();
        }
    }

    attachEventListeners() {
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.abrirModalSeleccionPrendas());

        document.getElementById('btn-agregar-item-tipo')?.addEventListener('click',
            () => this.abrirModalAgregarPrendaNueva());

        document.getElementById('btn-vista-previa')?.addEventListener('click',
            () => this.mostrarVistaPreviaFactura());

        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.manejarSubmitFormulario(e));
    }

    async cargarItems() {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                console.warn('[GestionItemsUI.cargarItems] Servicios no disponibles');
                return;
            }
            const resultado = await this.apiService.obtenerItems();
            this.items = resultado.items;
            await this.renderer.actualizar(this.items);
        } catch (error) {
            console.error('Error al cargar ítems:', error);
            if (this.notificationService) {
                this.notificationService.error('Error al cargar ítems');
            }
        }
    }

    async agregarItem(itemData) {
        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                console.warn('[GestionItemsUI.agregarItem] Servicios no disponibles');
                return false;
            }
            const resultado = await this.apiService.agregarItem(itemData);
            if (resultado.success) {
                this.items = resultado.items;
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem agregado correctamente');
                return true;
            }
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
            return false;
        }
    }

    async eliminarItem(index) {
        if (!confirm('¿Eliminar este ítem?')) return;

        try {
            if (!this.apiService || !this.renderer || !this.notificationService) {
                console.warn('[GestionItemsUI.eliminarItem] Servicios no disponibles');
                return;
            }
            const resultado = await this.apiService.eliminarItem(index);
            if (resultado.success) {
                this.items = resultado.items;
                await this.renderer.actualizar(this.items);
                this.notificationService.exito('Ítem eliminado');
            }
        } catch (error) {
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    abrirModalSeleccionPrendas() {
        if (window.abrirModalSeleccionPrendas) {
            window.abrirModalSeleccionPrendas();
        }
    }

    abrirModalAgregarPrendaNueva() {
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        this.prendaEditor.abrirModal(esEdicion, this.prendaEditIndex);
    }

    /**
     * Cerrar modal de agregar/editar prenda
     */
    cerrarModalAgregarPrendaNueva() {
        try {
            // Intentar cerrar con window.cerrarModalPrendaNueva
            if (typeof window.cerrarModalPrendaNueva === 'function') {
                window.cerrarModalPrendaNueva();
            } else {
                // Fallback: cerrar directamente el modal
                const modal = document.getElementById('modal-agregar-prenda-nueva');
                if (modal) {
                    modal.style.display = 'none';
                }
            }
            
            // Resetear editor
            if (this.prendaEditor) {
                this.prendaEditor.resetearEdicion();
            }
        } catch (error) {
            console.error('[GestionItemsUI.cerrarModalAgregarPrendaNueva] ERROR:', error);
        }
    }

    /**
     * Cargar datos de prenda en el modal para editar
     */
    cargarItemEnModal(prenda, prendaIndex) {
        this.prendaEditIndex = prendaIndex;
        this.prendaEditor.cargarPrendaEnModal(prenda, prendaIndex);
    }

    /**
     * Agregar prenda nueva - Recolectar datos del formulario modal y guardar
     */
    async agregarPrendaNueva() {
        try {
            console.log('[GestionItemsUI.agregarPrendaNueva] Iniciando...');

            // Recolectar datos del formulario modal
            const prendaData = this._construirPrendaDesdeFormulario();
            
            if (!prendaData) {
                console.warn('[GestionItemsUI.agregarPrendaNueva] No se obtuvieron datos válidos del formulario');
                this.notificationService?.error('Por favor completa los datos de la prenda');
                return;
            }

            console.log('[GestionItemsUI.agregarPrendaNueva] Datos recolectados:', prendaData);

            // Si es edición (prendaEditIndex !== null), actualizar; si no, agregar nueva
            if (this.prendaEditIndex !== null && this.prendaEditIndex !== undefined) {
                console.log('[GestionItemsUI.agregarPrendaNueva] Modo EDICIÓN - índice:', this.prendaEditIndex);
                
                // Actualizar prenda existente en el array de prendas
                if (this.prendas[this.prendaEditIndex]) {
                    this.prendas[this.prendaEditIndex] = prendaData;
                    console.log('[GestionItemsUI.agregarPrendaNueva] Prenda actualizada en índice:', this.prendaEditIndex);
                    this.notificationService?.exito('Prenda actualizada correctamente');
                }
            } else {
                console.log('[GestionItemsUI.agregarPrendaNueva] Modo AGREGAR NUEVA');
                // Agregar prenda al orden
                this.agregarPrendaAlOrden(prendaData);
                console.log('[GestionItemsUI.agregarPrendaNueva] Prenda agregada. Total prendas:', this.prendas.length);
                this.notificationService?.exito('Prenda agregada correctamente');
            }

            // Cerrar el modal
            this.cerrarModalAgregarPrendaNueva();
            
            // Actualizar la visualización de items en orden
            if (this.renderer) {
                const itemsOrdenados = this.obtenerItemsOrdenados();
                await this.renderer.actualizar(itemsOrdenados);
            }

            // Resetear índice de edición
            this.prendaEditIndex = null;

        } catch (error) {
            console.error('[GestionItemsUI.agregarPrendaNueva] ERROR:', error);
            this.notificationService?.error('Error al agregar prenda: ' + error.message);
        }
    }

    /**
     * Construir objeto de prenda desde el formulario modal
     * Recolecta: nombre, descripción, origen, imágenes, telas, tallas, variaciones, procesos
     * @private
     */
    _construirPrendaDesdeFormulario() {
        try {
            // Obtener datos básicos
            const nombre = document.getElementById('nueva-prenda-nombre')?.value?.trim();
            const descripcion = document.getElementById('nueva-prenda-descripcion')?.value?.trim();
            const origen = document.getElementById('nueva-prenda-origen-select')?.value || 'bodega';

            // Validar campos requeridos
            if (!nombre) {
                this.notificationService?.error('El nombre de la prenda es requerido');
                return null;
            }

            // Construir objeto de prenda
            const prendaData = {
                tipo: 'prenda_nueva',
                nombre_producto: nombre,
                descripcion: descripcion || '',
                origen: origen,
                // Imágenes de prenda desde storage
                imagenes: window.imagenesPrendaStorage?.images || [],
                telasAgregadas: [],
                procesos: window.procesosSeleccionados || {},
                // Formato único: cantidad_talla (JSON plano)
                cantidad_talla: window.cantidadesTallas || {},
                variantes: {}
            };

            // Recolectar telas desde window.telasAgregadas (gestionadas por gestion-telas.js)
            if (window.telasAgregadas && Array.isArray(window.telasAgregadas) && window.telasAgregadas.length > 0) {
                prendaData.telasAgregadas = window.telasAgregadas.map(tela => ({
                    tela: tela.tela || '',
                    color: tela.color || '',
                    referencia: tela.referencia || '',
                    // Imágenes de tela
                    imagenes: tela.imagenes || []
                }));
            }
            // Si estamos en modo edición y no hay telas en window.telasAgregadas, 
            // obtener telas de la prenda anterior
            else if (this.prendaEditIndex !== null && this.prendaEditIndex !== undefined) {
                const prendaAnterior = this.prendas[this.prendaEditIndex];
                if (prendaAnterior && prendaAnterior.telasAgregadas && prendaAnterior.telasAgregadas.length > 0) {
                    prendaData.telasAgregadas = prendaAnterior.telasAgregadas.map(tela => ({
                        tela: tela.tela || '',
                        color: tela.color || '',
                        referencia: tela.referencia || '',
                        imagenes: tela.imagenes || []
                    }));
                    console.log('[GestionItemsUI._recolectarDatosFormularioModal] Telas recuperadas de prenda anterior:', prendaData.telasAgregadas);
                }
            }

            // Recolectar variaciones/variantes desde el formulario
            const variantes = {};
            
            // Manga
            const checkManga = document.getElementById('aplica-manga');
            if (checkManga && checkManga.checked) {
                const mangaInput = document.getElementById('manga-input');
                const mangaObs = document.getElementById('manga-obs');
                variantes.tipo_manga = mangaInput?.value || 'No aplica';
                variantes.obs_manga = mangaObs?.value || '';
            } else {
                variantes.tipo_manga = 'No aplica';
                variantes.obs_manga = '';
            }
            
            // Bolsillos
            const checkBolsillos = document.getElementById('aplica-bolsillos');
            if (checkBolsillos && checkBolsillos.checked) {
                const bolsillosObs = document.getElementById('bolsillos-obs');
                variantes.tiene_bolsillos = true;
                variantes.obs_bolsillos = bolsillosObs?.value || '';
            } else {
                variantes.tiene_bolsillos = false;
                variantes.obs_bolsillos = '';
            }
            
            // Broche
            const checkBroche = document.getElementById('aplica-broche');
            if (checkBroche && checkBroche.checked) {
                const broqueInput = document.getElementById('broche-input');
                const broqueObs = document.getElementById('broche-obs');
                variantes.tipo_broche = broqueInput?.value || 'No aplica';
                variantes.obs_broche = broqueObs?.value || '';
            } else {
                variantes.tipo_broche = 'No aplica';
                variantes.obs_broche = '';
            }
            
            // Reflectivo
            const checkReflectivo = document.getElementById('aplica-reflectivo');
            if (checkReflectivo && checkReflectivo.checked) {
                const reflectivoObs = document.getElementById('reflectivo-obs');
                variantes.tiene_reflectivo = true;
                variantes.obs_reflectivo = reflectivoObs?.value || '';
            } else {
                variantes.tiene_reflectivo = false;
                variantes.obs_reflectivo = '';
            }
            
            prendaData.variantes = variantes;

            console.log('[GestionItemsUI._recolectarDatosFormularioModal] Datos preparados:', prendaData);
            return prendaData;

        } catch (error) {
            console.error('[GestionItemsUI._recolectarDatosFormularioModal] ERROR:', error);
            return null;
        }
    }

    /**
     * Actualizar prenda existente
     */
    async actualizarPrendaExistente() {
        await this.prendaEditor.actualizarPrendaExistente();
        this.prendaEditIndex = null;
    }

    async manejarSubmitFormulario(e) {
        e.preventDefault();

        try {
            if (!this.formCollector || !this.apiService || !this.notificationService) {
                console.warn('[GestionItemsUI.manejarSubmitFormulario] Servicios no disponibles');
                return;
            }
            
            const clienteInput = document.getElementById('cliente_editable');
            if (!clienteInput?.value || clienteInput.value.trim() === '') {
                this.notificationService.error('El cliente es requerido');
                clienteInput?.focus();
                return;
            }

            const pedidoData = this.formCollector.recolectarDatosPedido();
            
            console.log('[manejarSubmitFormulario] Datos recolectados:', pedidoData);

            if (!pedidoData.items || pedidoData.items.length === 0) {
                this.notificationService.error('Debe agregar al menos un item al pedido');
                return;
            }

            const validacion = await this.apiService.validarPedido(pedidoData);
            if (!validacion.valid) {
                alert('Errores en el pedido:\n' + validacion.errores.join('\n'));
                return;
            }

            const resultado = await this.apiService.crearPedido(pedidoData);

            if (resultado.success) {
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                setTimeout(() => this.mostrarModalExito(), 500);
            }
        } catch (error) {
            console.error('[manejarSubmitFormulario] ERROR:', error);
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    recolectarDatosPedido() {
        return this.formCollector.recolectarDatosPedido();
    }

    mostrarVistaPreviaFactura() {
        if (!this.renderer) {
            console.warn('[GestionItemsUI.mostrarVistaPreviaFactura] Servicio renderer no disponible');
            return;
        }
        this.renderer.mostrarVistaPreviaFactura();
    }

    mostrarModalExito() {
        let modalElement = document.getElementById('modalExitoPedido');
        if (!modalElement) {
            document.body.insertAdjacentHTML('beforeend', MODAL_EXITO_PEDIDO_HTML);
            modalElement = document.getElementById('modalExitoPedido');
        }

        const btnVolverAPedidos = document.getElementById('btnVolverAPedidos');
        if (btnVolverAPedidos) {
            btnVolverAPedidos.onclick = () => window.location.href = '/asesores/pedidos';
        }

        modalElement.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    renderizarProcesosDirectos() {
        this.renderer.renderizarProcesosDirectos();
    }
}

/**
 * Wrapper para editarProceso que funciona en contexto de edición
 */
window.editarProcesoEdicion = function(tipo) {
    console.log(` Editando proceso en contexto de edición: ${tipo}`);
    
    // Asegurar que el modal de proceso esté encima
    const modalProceso = document.getElementById('modal-proceso-generico');
    if (modalProceso) {
        modalProceso.style.zIndex = '10000';
        console.log('    Z-index del modal de proceso establecido a 10000');
    }
    
    // Usar la función global si existe
    if (window.editarProceso && window.editarProceso !== window.editarProcesoEdicion) {
        window.editarProceso(tipo);
        return;
    }
    
    // Si no existe, intentar abrir el modal genérico
    if (window.abrirModalProcesoGenerico) {
        window.abrirModalProcesoGenerico(tipo);
        
        // Asegurar nuevamente que el z-index esté alto después de abrir
        if (modalProceso) {
            setTimeout(() => {
                modalProceso.style.zIndex = '10000';
            }, 100);
        }
        
        // Cargar datos en el modal
        const proceso = window.procesosSeleccionados?.[tipo];
        if (proceso?.datos) {
            cargarDatosProcesoEnModalEdicion(tipo, proceso.datos);
        }
    } else {
        console.error(' No se pudo abrir el modal de proceso');
    }
};

/**
 * Cargar datos de un proceso en el modal para editar (fallback)
 */
function cargarDatosProcesoEnModalEdicion(tipo, datos) {
    console.log(` Cargando datos en modal para editar:`, datos);
    
    // Limpiar imágenes anteriores
    if (window.imagenesProcesoActual) {
        window.imagenesProcesoActual = [null, null, null];
    }
    
    // Cargar imágenes
    const imagenes = datos.imagenes || (datos.imagen ? [datos.imagen] : []);
    imagenes.forEach((img, idx) => {
        if (img && idx < 3) {
            const indice = idx + 1;
            const preview = document.getElementById(`proceso-foto-preview-${indice}`);
            
            if (preview) {
                const imgUrl = img instanceof File ? URL.createObjectURL(img) : img;
                const htmlImg = IMAGEN_PROCESO_EDICION_TEMPLATE
                    .replace('{{imgUrl}}', imgUrl)
                    .replace('{{indice}}', indice);
                preview.innerHTML = htmlImg;
            }
            
            if (window.imagenesProcesoActual) {
                window.imagenesProcesoActual[idx] = img;
            }
        }
    });
    
    // Cargar ubicaciones
    if (datos.ubicaciones && window.ubicacionesProcesoSeleccionadas) {
        window.ubicacionesProcesoSeleccionadas.length = 0;
        window.ubicacionesProcesoSeleccionadas.push(...datos.ubicaciones);
        if (window.renderizarListaUbicaciones) {
            window.renderizarListaUbicaciones();
        }
    }
    
    // Cargar observaciones
    const obsInput = document.getElementById('proceso-observaciones');
    if (obsInput && datos.observaciones) {
        obsInput.value = datos.observaciones;
    }
    
    // Cargar tallas
    if (datos.tallas && window.tallasSeleccionadasProceso) {
        window.tallasSeleccionadasProceso.dama = datos.tallas.dama || [];
        window.tallasSeleccionadasProceso.caballero = datos.tallas.caballero || [];
        if (window.actualizarResumenTallasProceso) {
            window.actualizarResumenTallasProceso();
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.gestionItemsUI = new GestionItemsUI();
});
 