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
     * Abre un recibo parcial (anexo) reutilizando la misma pipeline de renderizado.
     * Sigue el mismo flujo que abrirRecibo() pero inyecta las tallas del parcial
     * en el objeto recibo ANTES de renderizar.
     *
     * @param {number} pedidoId    - ID del pedido de producción
     * @param {number} prendaId    - ID de la prenda
     * @param {string} tipoRecibo  - Tipo del proceso base (ej: "BORDADO")
     * @param {number} parcialId   - ID del recibo parcial (pedidos_parciales)
     * @param {string} nombreAnexo - Nombre para mostrar (ej: "BORDADO ANEXO 2")
     */
    async abrirReciboParcial(pedidoId, prendaId, tipoRecibo, parcialId, nombreAnexo) {
        // Validaciones
        if (typeof tipoRecibo !== 'string') {
            alert('Error: tipo de recibo debe ser texto');
            return;
        }
        if (typeof prendaId !== 'number') {
            alert('Error: ID de prenda debe ser número');
            return;
        }
        if (!parcialId) {
            alert('Error: ID de recibo parcial requerido');
            return;
        }

        try {
            // Resetear estado
            GalleryManager.resetGaleria(this.modalManager);
            this.modalManager.limpiarEstado();
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex: null
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar endpoint de datos del pedido (misma lógica que abrirRecibo)
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] Cargando datos en paralelo:', {
                endpoint,
                parcialEndpoint: `/api/recibos-parciales/${parcialId}`
            });

            // Fetch en paralelo: datos del pedido + datos del parcial
            const [pedidoResponse, parcialResponse] = await Promise.all([
                fetch(endpoint),
                fetch(`/api/recibos-parciales/${parcialId}`)
            ]);

            if (!pedidoResponse.ok) throw new Error(`HTTP ${pedidoResponse.status} al cargar pedido`);
            if (!parcialResponse.ok) throw new Error(`HTTP ${parcialResponse.status} al cargar parcial`);

            let datos = await pedidoResponse.json();
            const parcialResult = await parcialResponse.json();

            if (!parcialResult.success) {
                throw new Error(parcialResult.message || 'Error al cargar recibo parcial');
            }

            // Desempaquetar datos del pedido
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            this.modalManager.setState({ datosCompletos: datos });

            // Validar prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            if (!prendaData) {
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }

            // Construir recibos normalmente
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            const reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            // === INYECCIÓN DE TALLAS DEL PARCIAL (antes de renderizar) ===
            const recibo = recibos[reciboIndice];
            const tallasFormato = parcialResult.data.tallas_formato;

            console.log('[PedidosRecibosModule.abrirReciboParcial] Inyectando tallas del parcial:', {
                tallasOriginales: recibo.tallas,
                tallasDelParcial: tallasFormato,
                nombreAnexo
            });

            // Sobrescribir tallas del recibo con las del parcial
            recibo.tallas = tallasFormato;

            // Eliminar talla_colores para evitar que el Formatter use colores
            // (los parciales no tienen asignación de colores por talla)
            delete recibo.talla_colores;

            // Marcar como parcial para que el renderer sepa limpiar consecutivo
            recibo._esParcial = true;
            recibo._nombreAnexo = nombreAnexo || tipoRecibo;

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Temporalmente limpiar talla_colores de prendaData para que el renderer
            // NO re-inyecte colores en el recibo (los parciales usan solo tallas planas)
            const tallaColoresOriginal = prendaData.talla_colores;
            prendaData.talla_colores = null;

            // Renderizar con la pipeline normal (tallas ya están inyectadas)
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Restaurar talla_colores para no mutar el estado permanentemente
            prendaData.talla_colores = tallaColoresOriginal;

            // Post-renderizado: ajustar título y consecutivo para el anexo
            const titleEl = document.querySelector('.receipt-title');
            if (titleEl) {
                titleEl.textContent = 'RECIBO DE ' + tipoRecibo.toUpperCase();
            }

            const pedidoNumberEl = document.querySelector('#order-pedido') || document.querySelector('.pedido-number');
            if (pedidoNumberEl) {
                pedidoNumberEl.textContent = '#-';
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] ✓ Renderizado completo con tallas del parcial');

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {
            console.error('[PedidosRecibosModule.abrirReciboParcial] Error:', err);
            alert('Error al cargar el recibo parcial: ' + err.message);
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
 * FUNCIÓN GLOBAL para abrir recibo parcial (anexo)
 * Usa la pipeline de renderizado completa, inyectando tallas del parcial
 */
window.openOrderDetailModalWithParcial = async function(parcialId, prendaId, tipoString) {
    const pedidoId = window.selectorRecibosState?.pedidoId;
    const nombreAnexo = window.selectorRecibosState?.nombreProcesoAnexo || tipoString;

    if (!pedidoId) {
        console.error('[openOrderDetailModalWithParcial] pedidoId no disponible en selectorRecibosState');
        alert('Error: No se pudo determinar el pedido');
        return;
    }

    return window.pedidosRecibosModule.abrirReciboParcial(
        pedidoId, prendaId, tipoString, parcialId, nombreAnexo
    );
};

/**
 * FUNCIÓN GLOBAL para cerrar recibos
 */
window.cerrarModalRecibos = function() {
    window.pedidosRecibosModule.cerrarRecibo();
};

/**
 * FUNCIÓN GLOBAL para abrir imagen en grande desde la galería
 * Disponible en todas las vistas que usen PedidosRecibosModule
 */
if (typeof window.abrirModalImagenProcesoGrande !== 'function') {
    window.abrirModalImagenProcesoGrande = function(indice, fotos) {
        GalleryManager.abrirModalImagenProcesoGrande(indice, fotos);
    };
}

// Compatibilidad: algunos templates aún llaman onclick="toggleFactura()"
// Siempre sobreescribir para que funcione en todas las vistas (supervisor-pedidos, recibos-costura, insumos, etc.)
// Guarda la versión anterior como fallback para cuando NO hay estado activo de recibo
const _originalToggleFactura = window.toggleFactura;

window.toggleFactura = function() {
    console.log('[toggleFactura-PRM] Toggle entre recibo y galería');
    
    // Verificar si PedidosRecibosModule tiene estado activo
    const estado = window.pedidosRecibosModule ? window.pedidosRecibosModule.getEstado() : null;
    const tieneEstadoActivo = estado && estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId);
    
    if (!tieneEstadoActivo) {
        // Sin estado activo → usar la implementación original si existe
        console.log('[toggleFactura-PRM] Sin estado activo, delegando a implementación original');
        if (_originalToggleFactura) return _originalToggleFactura.call(this);
        return;
    }
    
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

