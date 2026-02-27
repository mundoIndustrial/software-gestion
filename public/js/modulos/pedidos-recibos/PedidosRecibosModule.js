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

        try {
            // Resetear cualquier galería previa para evitar que quede pegada entre recibos
            GalleryManager.resetGaleria(this.modalManager);
            
            // Limpiar estado del modal para evitar caché entre recibos
            this.modalManager.limpiarEstado();

            // Actualizar estado con los nuevos datos
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar el endpoint según el contexto
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                // Contexto de registros
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                // Contexto de insumos - usar endpoint de registros para evitar filtro de de_bodega
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                // Contexto público (accesible para cualquier usuario autenticado)
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log(' [PedidosRecibosModule] Endpoint seleccionado:', endpoint);
            console.log(' [PedidosRecibosModule] Parámetros recibidos:', {
                pedidoId,
                prendaId,
                tipoRecibo,
                prendaIndex
            });

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
            console.log('IDs de prendas disponibles:', datos.prendas ? datos.prendas.map(p => p.id) : 'NONE');
            console.log('Buscando prenda_id:', prendaId);
            console.groupEnd();
                        
            this.modalManager.setState({ datosCompletos: datos });

            // Validar que existan prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            console.log(`[PedidosRecibosModule] 🔍 Buscando prenda con ID ${prendaId} entre ${datos.prendas.length} prendas`);
            console.log(`[PedidosRecibosModule] 📋 IDs disponibles:`, datos.prendas.map(p => ({ id: p.id, nombre: p.nombre || p.nombre_prenda })));
            
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            
            if (!prendaData) {
                console.error(`[PedidosRecibosModule] ❌ Prenda ${prendaId} no encontrada`);
                console.error(`[PedidosRecibosModule] 🔍 Búsqueda realizada con:`, {
                    buscarPor: 'id',
                    valorBuscado: prendaId,
                    tipoDeComparacion: '== (flexible)',
                    prendasDisponibles: datos.prendas.map(p => ({
                        id: p.id,
                        tipoId: typeof p.id,
                        nombre: p.nombre || p.nombre_prenda
                    }))
                });
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }
            
            console.log(`[PedidosRecibosModule] ✅ Prenda encontrada:`, {
                id: prendaData.id,
                nombre: prendaData.nombre || prendaData.nombre_prenda,
                tieneRecibos: !!prendaData.recibos,
                totalRecibos: prendaData.recibos ? prendaData.recibos.length : 0
            });

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
// Actúa como TOGGLE: si está en galería → vuelve al recibo, si está en recibo → abre galería
if (typeof window.toggleFactura !== 'function') {
    window.toggleFactura = function() {
        console.log('[toggleFactura-PRM] Toggle entre recibo y galería');
        const galeria = document.getElementById('galeria-modal-costura');
        const estaEnGaleria = galeria && (galeria.style.display === 'flex' || galeria.style.display === 'block');
        
        const btnFactura = document.getElementById('btn-factura');
        const btnGaleria = document.getElementById('btn-galeria');
        
        if (estaEnGaleria) {
            // Estamos en galería → cerrar galería y mostrar recibo
            console.log('[toggleFactura-PRM] Cerrando galería, mostrando recibo');
            GalleryManager.cerrarGaleria();
            // Mostrar btn-factura (con icono de galería para indicar que se puede ir a galería)
            if (btnFactura) {
                btnFactura.style.display = 'block';
                btnFactura.style.visibility = 'visible';
                btnFactura.style.zIndex = '10';
                const icon = btnFactura.querySelector('i');
                if (icon) { icon.className = 'fas fa-images'; btnFactura.title = 'Ver galería'; }
            }
            // Ocultar btn-galeria
            if (btnGaleria) {
                btnGaleria.style.display = 'none';
                btnGaleria.style.visibility = 'hidden';
                btnGaleria.style.zIndex = '-1';
            }
        } else {
            // Estamos en recibo → abrir galería
            console.log('[toggleFactura-PRM] Abriendo galería');
            if (window.toggleGaleria) {
                window.toggleGaleria();
            }
            // Ocultar btn-factura
            if (btnFactura) {
                btnFactura.style.display = 'none';
                btnFactura.style.visibility = 'hidden';
                btnFactura.style.zIndex = '-1';
            }
            // Mostrar btn-galeria (con icono de recibo para indicar que se puede volver)
            if (btnGaleria) {
                btnGaleria.style.display = 'block';
                btnGaleria.style.visibility = 'visible';
                btnGaleria.style.zIndex = '10';
                const icon = btnGaleria.querySelector('i');
                if (icon) { icon.className = 'fas fa-receipt'; btnGaleria.title = 'Ver recibos'; }
            }
        }
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

