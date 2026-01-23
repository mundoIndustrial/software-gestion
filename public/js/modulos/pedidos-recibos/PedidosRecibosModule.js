/**
 * PedidosRecibosModule.js
 * Módulo principal para gestión de recibos dinámicos en pedidos
 * 
 * Integra: ModalManager, CloseButtonManager, NavigationManager, 
 *          GalleryManager, ReceiptRenderer, y utilidades
 */

import { ModalManager } from './components/ModalManager.js';
import { CloseButtonManager } from './components/CloseButtonManager.js';
import { NavigationManager } from './components/NavigationManager.js';
import { GalleryManager } from './components/GalleryManager.js';
import { ReceiptRenderer } from './components/ReceiptRenderer.js';
import { ReceiptBuilder } from './utils/ReceiptBuilder.js';
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
            // Mostrar modal
            this.modalManager.abrirModal();

            // Obtener datos del servidor
            const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const datos = await response.json();
            this.modalManager.setState({ datosCompletos: datos });

            // Encontrar la prenda
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            if (!prendaData) throw new Error(`Prenda ${prendaId} no encontrada`);



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
            // Usar galería original si existe
            if (window.toggleGaleria) {
                window.toggleGaleria();
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

// Interceptar toggleGaleria original
const originalToggleGaleria = window.toggleGaleria;
window.toggleGaleria = async function() {
    // Si hay estado de recibos dinámicos, usar la nueva galería
    const estado = window.pedidosRecibosModule.getEstado();
    if (estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId)) {
        return window.pedidosRecibosModule.abrirGaleria();
    }
    
    // Si no, usar la galería original
    if (originalToggleGaleria) {
        return originalToggleGaleria.call(this);
    }
};

