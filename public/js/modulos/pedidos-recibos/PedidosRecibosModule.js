/**
 * PedidosRecibosModule.js
 * Módulo principal para gestión de recibos dinámicos en pedidos
 * 
 * Integra: ModalManager, CloseButtonManager, NavigationManager, 
 *          GalleryManager, ReceiptRenderer, y utilidades
 */

import { ReceiptBuilder } from './utils/ReceiptBuilder.js';
import { ReceiptRenderer } from './components/ReceiptRenderer.js';
import { NavigationManager } from './components/NavigationManager.js';
import { GalleryManager } from './components/GalleryManager.js';
import { CloseButtonManager } from './components/CloseButtonManager.js';
import { ModalManager } from './components/ModalManager.js';
import { Formatters } from './utils/Formatters.js';

export class PedidosRecibosModule {
    constructor() {
        this.modalManager = new ModalManager();
        this.validaciones();
    }

    /**
     * Valida que existan los elementos necesarios en el DOM
     */
    validaciones() {
        const elementosCriticos = [
            'order-detail-modal-wrapper',
            'modal-overlay'
        ];

        const faltantes = elementosCriticos.filter(id => !document.getElementById(id));
        if (faltantes.length > 0) {

        }

        // Elementos opcionales - solo advertencia
        const elementosOpcionales = [
            'receipt-title',
            'cliente-value',
            'asesora-value'
        ];
        const opcionalesFaltantes = elementosOpcionales.filter(id => !document.getElementById(id));
        if (opcionalesFaltantes.length > 0) {

        }
    }

    /**
     * FUNCIÓN PRINCIPAL: Abre un recibo específico en el modal
     * 
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoRecibo - Tipo de recibo (STRING)
     * @param {number} prendaIndex - Índice de la prenda (opcional)
     */
    async abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex = null) {
        // VALIDACIÓN: Bloquear COSTURA-BODEGA en supervisor-pedidos y registros
        const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
        const esRegistros = window.location.pathname.includes('/registros');
        if ((esSupervisorPedidos || esRegistros) && tipoRecibo === 'costura-bodega') {
            console.warn('🚫 [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA - BLOQUEADO');
            return;
        }
        
        // Validaciones
        if (typeof tipoRecibo !== 'string') {

            alert('Error: tipo de recibo debe ser texto');
            return;
        }

        if (typeof prendaId !== 'number') {

            alert('Error: ID de prenda debe ser número');
            return;
        }
        // Actualizar estado
        this.modalManager.setState({
            pedidoId,
            prendaId,
            tipoProceso: tipoRecibo,
            prendaIndex
        });

        try {
            // Resetear cualquier galería previa para evitar que quede pegada entre recibos
            GalleryManager.resetGaleria(this.modalManager);

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar el endpoint según el contexto
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                // Contexto de registros
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                // Contexto público (accesible para cualquier usuario autenticado)
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log(' [PedidosRecibosModule] Endpoint seleccionado:', endpoint);

            // Obtener datos del servidor
            const response = await fetch(endpoint);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            let datos = await response.json();            
            // Manejar respuesta envuelta en { success: true, data: {...} }
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }
            
            console.group('[PedidosRecibosModule.abrirRecibo] 📥 DATOS RECIBIDOS DEL ENDPOINT');
            console.log('Endpoint:', endpoint);
            console.log('Cliente:', datos.cliente);
            console.log('Asesor:', datos.asesor);
            console.log('Forma de pago:', datos.forma_de_pago);
            console.log('Número pedido:', datos.numero_pedido);
            console.log('Total prendas:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
            console.groupEnd();
                        
            this.modalManager.setState({ datosCompletos: datos });

            // Validar que existan prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            if (!prendaData) throw new Error(`Prenda ${prendaId} no encontrada`);

            // Debug: Verificar si los recibos están llegando desde el backend
            console.log(' [PedidosRecibosModule] Prenda encontrada:', {
                prendaId,
                prendaData: prendaData,
                recibos: prendaData.recibos,
                tieneRecibos: !!prendaData.recibos
            });



            // Construir lista de recibos
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            const reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Renderizar
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {

            alert('Error al cargar el recibo: ' + err.message);
            this.modalManager.cerrarModal();
        }
    }

    /**
     * Renderiza el recibo y configura componentes
     */
    _renderizarRecibo(prendaData, reciboIndice, tipoProceso, datosPedido, recibos) {
        // Crear botón X
        CloseButtonManager.crearBotonCierre(this.modalManager);

        // Renderizar contenido
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            reciboIndice,
            tipoProceso,
            datosPedido,
            recibos
        );

        // Configurar navegación
        NavigationManager.configurarFlechas(
            this.modalManager,
            prendaData,
            (prendaData, indice, tipo) => this._onProcesoCambiado(prendaData, indice, tipo, datosPedido, recibos)
        );
    }

    /**
     * Callback cuando cambia de proceso vía navegación
     */
    _onProcesoCambiado(prendaData, nuevoIndice, tipoRecibo, datosPedido, recibos) {
        // Renderizar nuevo recibo
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            nuevoIndice,
            tipoRecibo,
            datosPedido,
            recibos
        );

        // Actualizar flechas
        NavigationManager.actualizarVisibilidad(this.modalManager);

        // Cerrar galería si estaba abierta
        const galeria = document.getElementById('galeria-modal-costura');
        if (galeria && galeria.style.display !== 'none') {
            GalleryManager.cerrarGaleria();
        }
    }

    /**
     * Abre la galería de imágenes
     */
    async abrirGaleria() {
        const mostroGaleria = await GalleryManager.abrirGaleria(this.modalManager);
        if (mostroGaleria) {
            GalleryManager.actualizarBotonesEstilo(true);
        } else {
            // Usar galería original solo si existe y evitando recursión
            if (window.toggleGaleria && window.originalToggleGaleria) {
                return window.originalToggleGaleria.call(this);
            }
        }
    }

    /**
     * Cierra la galería
     */
    cerrarGaleria() {
        GalleryManager.cerrarGaleria();
    }

    /**
     * Cierra el recibo/modal
     */
    cerrarRecibo() {
        CloseButtonManager.forzarCierre(this.modalManager);
    }

    /**
     * Obtiene el estado actual del modal
     */
    getEstado() {
        return this.modalManager.getState();
    }
}

// Instancia global singleton
window.pedidosRecibosModule = new PedidosRecibosModule();

/**
 * FUNCIÓN GLOBAL compatibilidad con código antiguo
 * Mantiene la API antigua mientras usa el nuevo módulo
 */
window.openOrderDetailModalWithProcess = async function(pedidoId, prendaId, tipoRecibo, prendaIndex = null) {
    return window.pedidosRecibosModule.abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex);
};

/**
 * FUNCIÓN GLOBAL para cerrar recibos
 */
window.cerrarModalRecibos = function() {
    window.pedidosRecibosModule.cerrarRecibo();
};

// Compatibilidad: algunos templates aún llaman onclick="toggleFactura()"
// (sin cargar public/js/asesores/pedidos.js en ciertos contextos como visualizador-logo)
if (typeof window.toggleFactura !== 'function') {
    window.toggleFactura = function() {
        GalleryManager.cerrarGaleria();
    };
}

// Interceptar toggleGaleria original
const originalToggleGaleria = window.toggleGaleria;
window.originalToggleGaleria = originalToggleGaleria; // Guardar referencia para evitar recursión

window.toggleGaleria = async function() {
    // Evitar recursión infinita
    if (window.toggleGaleria._calling) {
        console.warn('[toggleGaleria]  Evitando recursión infinita');
        return;
    }
    
    window.toggleGaleria._calling = true;
    
    try {
        // Si hay estado de recibos dinámicos, usar la nueva galería
        const estado = window.pedidosRecibosModule.getEstado();
        if (estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId)) {
            return window.pedidosRecibosModule.abrirGaleria();
        }
        
        // Si no, usar la galería original
        if (originalToggleGaleria) {
            return originalToggleGaleria.call(this);
        }
    } finally {
        window.toggleGaleria._calling = false;
    }
};

