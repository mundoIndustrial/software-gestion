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

            // Recolectar datos del formulario modal usando el componente extraído
            window.prendaFormCollector.setNotificationService(this.notificationService);
            const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
                this.prendaEditIndex,
                this.prendas
            );
            
            if (!prendaData) {
                console.warn('[GestionItemsUI.agregarPrendaNueva] No se obtuvieron datos válidos del formulario');
                this.notificationService?.error('Por favor completa los datos de la prenda');
                return;
            }

            console.log('[GestionItemsUI.agregarPrendaNueva] Datos recolectados:', prendaData);

            // Verificar si estamos en un pedido existente
            const enPedidoExistente = window.datosEdicionPedido && (window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido);
            // IMPORTANTE: Usar SIEMPRE el .id de la BD, nunca numero_pedido
            // El numero_pedido es solo para mostrar en UI, no para rutas de API
            const pedidoId = enPedidoExistente ? window.datosEdicionPedido.id : null;
            
            if (!pedidoId && enPedidoExistente) {
                console.warn('[GestionItemsUI.agregarPrendaNueva] ADVERTENCIA: No hay pedido.id disponible, usando numero_pedido como fallback');
                // Solo si realmente no hay id, usar numero_pedido (pero esto NO debería pasar en producción)
            }

            // Si es edición (prendaEditIndex !== null), actualizar; si no, agregar nueva
            if (this.prendaEditIndex !== null && this.prendaEditIndex !== undefined) {
                console.log('[GestionItemsUI.agregarPrendaNueva] Modo EDICIÓN - índice:', this.prendaEditIndex);
                
                // SI ESTAMOS EDITANDO EN UN PEDIDO EXISTENTE, MOSTRAR MODAL DE NOVEDADES
                if (enPedidoExistente) {
                    console.log('[GestionItemsUI.agregarPrendaNueva] Mostrando modal de novedades para registrar cambios...');
                    
                    // Obtener la prenda original desde window.prendaEnEdicion (guardada por prenda-editor-modal.js)
                    const prendaOriginal = window.prendaEnEdicion?.prendaOriginal;
                    console.log('[GestionItemsUI.agregarPrendaNueva] Prenda original desde window.prendaEnEdicion:', prendaOriginal);
                    console.log('[GestionItemsUI.agregarPrendaNueva] ID de prenda original:', prendaOriginal?.id || prendaOriginal?.prenda_pedido_id);
                    
                    // Agregar el ID de la prenda original a prendaData
                    prendaData.prenda_pedido_id = prendaOriginal?.prenda_pedido_id || prendaOriginal?.id;
                    console.log('[GestionItemsUI.agregarPrendaNueva] prendaData.prenda_pedido_id asignado:', prendaData.prenda_pedido_id);
                    
                    await window.modalNovedadEditacion.mostrarModalYActualizar(pedidoId, prendaData, this.prendaEditIndex);
                    // El modal de novedades maneja todo: actualización, cierre, etc.
                    // NO continuamos aquí
                    return;
                } else {
                    // Solo en memoria - sin novedades
                    console.log('[GestionItemsUI.agregarPrendaNueva] Actualizando prenda en memoria...');
                    if (this.prendas[this.prendaEditIndex]) {
                        this.prendas[this.prendaEditIndex] = prendaData;
                        console.log('[GestionItemsUI.agregarPrendaNueva] Prenda actualizada en índice:', this.prendaEditIndex);
                        this.notificationService?.exito('Prenda actualizada correctamente');
                    }
                }
            } else {
                console.log('[GestionItemsUI.agregarPrendaNueva] Modo AGREGAR NUEVA');
                
                // GUARDAR EN LA BASE DE DATOS SI ESTAMOS EN EDICIÓN DE PEDIDO EXISTENTE
                if (enPedidoExistente) {
                    console.log('[GestionItemsUI.agregarPrendaNueva] Guardando prenda en pedido:', pedidoId);
                    
                    // PASO 1: MOSTRAR MODAL DE NOVEDAD USANDO COMPONENTE EXTRAÍDO
                    await window.modalNovedadPrenda.mostrarModalYGuardar(pedidoId, prendaData);
                    // El modal de novedades maneja todo: guardado, cierre, etc.
                    // NO continuamos aquí
                    return;
                    
                } else {
                    console.log('[GestionItemsUI.agregarPrendaNueva] No hay pedido activo, agregando solo en memoria');
                    this.notificationService?.exito('Prenda agregada correctamente');
                    
                    // Agregar prenda al orden
                    this.agregarPrendaAlOrden(prendaData);
                    console.log('[GestionItemsUI.agregarPrendaNueva] Prenda agregada. Total prendas:', this.prendas.length);
                }
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
            
            // IMPORTANTE: Actualizar window.datosEdicionPedido.prendas (sin reabrirse automáticamente)
            if (window.datosEdicionPedido) {
                console.log('[GestionItemsUI.agregarPrendaNueva] Actualizando prendas globales...');
                window.datosEdicionPedido.prendas = this.prendas;
                console.log('[GestionItemsUI.agregarPrendaNueva] Prendas actualizadas. Total:', this.prendas.length);
                // El modal de éxito se mostrará y el usuario decidirá si ver la lista
            }

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

            // Mostrar indicador de carga
            this.mostrarCargando('Validando pedido...');

            const validacion = await this.apiService.validarPedido(pedidoData);
            if (!validacion.valid) {
                this.ocultarCargando();
                alert('Errores en el pedido:\n' + validacion.errores.join('\n'));
                return;
            }

            this.mostrarCargando('Creando pedido...');
            const resultado = await this.apiService.crearPedido(pedidoData);

            if (resultado.success) {
                this.datosPedidoCreado = {
                    pedido_id: resultado.pedido_id,
                    numero_pedido: resultado.numero_pedido
                };
                
                // Ocultar loader y mostrar modal de éxito existente
                this.ocultarCargando();
                setTimeout(() => this.mostrarModalExito(), 300);
            }
        } catch (error) {
            console.error('[manejarSubmitFormulario] ERROR:', error);
            this.ocultarCargando();
            if (this.notificationService) {
                this.notificationService.error('Error: ' + error.message);
            }
        }
    }

    mostrarCargando(mensaje = 'Cargando...') {
        // Remover loader anterior si existe
        this.ocultarCargando();
        
        const loader = document.createElement('div');
        loader.id = 'pedido-loader';
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        `;
        
        const contenido = document.createElement('div');
        contenido.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        `;
        
        const spinner = document.createElement('div');
        spinner.style.cssText = `
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        `;
        
        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #333;
            font-size: 16px;
            font-weight: 500;
        `;
        
        // Agregar animación CSS
        if (!document.getElementById('pedido-loader-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-loader-style';
            style.textContent = `
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        contenido.appendChild(spinner);
        contenido.appendChild(texto);
        loader.appendChild(contenido);
        document.body.appendChild(loader);
    }

    ocultarCargando() {
        const loader = document.getElementById('pedido-loader');
        if (loader) {
            loader.remove();
        }
    }

    mostrarExito(mensaje) {
        const exito = document.createElement('div');
        exito.id = 'pedido-exito';
        exito.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        const texto = document.createElement('p');
        texto.textContent = mensaje;
        texto.style.cssText = `
            margin: 0;
            color: #27ae60;
            font-size: 18px;
            font-weight: 600;
        `;
        
        // Agregar animación CSS si no existe
        if (!document.getElementById('pedido-exito-style')) {
            const style = document.createElement('style');
            style.id = 'pedido-exito-style';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translate(-50%, -60%);
                    }
                    to {
                        opacity: 1;
                        transform: translate(-50%, -50%);
                    }
                }
            `;
            document.head.appendChild(style);
        }
        
        exito.appendChild(texto);
        document.body.appendChild(exito);
        
        // Remover después de 2 segundos
        setTimeout(() => {
            exito.remove();
        }, 2000);
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
 