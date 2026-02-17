/**
 * Servicio de Obtención de Datos de Factura
 * Maneja la comunicación con el backend para obtener datos de pedidos
 */

class InvoiceDataFetcher {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.verFacturaDelPedido = this.verFacturaDelPedido.bind(this);
        window.verRecibosDelPedido = this.verRecibosDelPedido.bind(this);
    }

    /**
     * Obtiene los datos de la factura para un pedido específico
     */
    async obtenerDatosFactura(pedidoId) {
        try {
            const response = await fetch(`/pedidos-public/${pedidoId}/factura-datos`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            const respuesta = await response.json();
            return respuesta.data || respuesta;
        } catch (error) {
            console.error('[InvoiceDataFetcher] Error obteniendo datos de factura:', error);
            throw error;
        }
    }

    /**
     * Obtiene los datos de recibos para un pedido específico
     */
    async obtenerDatosRecibos(pedidoId) {
        try {
            const response = await fetch(`/pedidos-public/${pedidoId}/recibos-datos`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('[InvoiceDataFetcher] Error obteniendo datos de recibos:', error);
            throw error;
        }
    }

    /**
     * Abre la vista previa de factura para un pedido guardado
     */
    async verFacturaDelPedido(numeroPedido, pedidoId) {
        console.log('[InvoiceDataFetcher] INICIO verFacturaDelPedido', { numeroPedido, pedidoId });
        
        try {
            // Mostrar spinner de carga
            if (window.loadingManager) {
                console.log('[InvoiceDataFetcher] Mostrando loading...');
                window.loadingManager.mostrarCargando('Cargando factura...');
            }
            
            // Obtener datos del pedido desde el servidor
            console.log('[InvoiceDataFetcher] Obteniendo datos del servidor para pedido:', pedidoId);
            const datos = await this.obtenerDatosFactura(pedidoId);
            
            console.log('[InvoiceDataFetcher] Datos obtenidos:', {
                prendas_existe: !!datos.prendas,
                prendas_count: datos.prendas?.length || 0,
                pedido_id: datos.id
            });
            
            // Usar el modal de visualización
            if (typeof window.invoiceModalManager?.crearModalFactura === 'function') {
                console.log('[InvoiceDataFetcher] Llamando a invoiceModalManager.crearModalFactura');
                window.invoiceModalManager.crearModalFactura(datos);
            } else if (typeof crearModalFacturaDesdeListaPedidos === 'function') {
                console.log('[InvoiceDataFetcher] Usando fallback crearModalFacturaDesdeListaPedidos');
                crearModalFacturaDesdeListaPedidos(datos);
            } else {
                console.warn('[InvoiceDataFetcher] Modal no disponible, usando fallback abrirModalEditarPedido');
                if (typeof abrirModalEditarPedido === 'function') {
                    abrirModalEditarPedido(pedidoId, datos, 'ver');
                }
            }
            
        } catch (error) {
            console.error('[InvoiceDataFetcher] Error en verFacturaDelPedido:', error);
            
            if (window.loadingManager) {
                window.loadingManager.ocultarCargando();
            }
            
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'No se pudo cargar la factura: ' + error.message);
            }
        }
    }

    /**
     * Abre la vista de recibos dinámicos para un pedido
     */
    async verRecibosDelPedido(numeroPedido, pedidoId, prendasIndex = null) {
        console.log('[InvoiceDataFetcher] Iniciando vista de recibos:', { numeroPedido, pedidoId });
        
        try {
            // Obtener datos de recibos del servidor
            const datos = await this.obtenerDatosRecibos(pedidoId);
            
            console.log('[InvoiceDataFetcher] Datos de recibos obtenidos');
            
            // Crear modal con los recibos
            if (typeof window.receiptsModalManager?.crearModalRecibos === 'function') {
                window.receiptsModalManager.crearModalRecibos(datos, prendasIndex);
            } else if (typeof crearModalRecibosDesdeListaPedidos === 'function') {
                crearModalRecibosDesdeListaPedidos(datos, prendasIndex);
            } else {
                console.error('[InvoiceDataFetcher] Gestor de recibos no disponible');
            }
            
        } catch (error) {
            console.error('[InvoiceDataFetcher] Error en vista de recibos:', error);
            
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'No se pudo cargar los recibos: ' + error.message);
            }
        }
    }

    /**
     * Valida la estructura de datos de factura
     */
    validarDatosFactura(datos) {
        const errores = [];
        
        if (!datos) {
            errores.push('Datos no proporcionados');
            return errores;
        }
        
        if (!datos.numero_pedido) {
            errores.push('Número de pedido faltante');
        }
        
        if (!datos.cliente) {
            errores.push('Cliente faltante');
        }
        
        if (!datos.prendas || !Array.isArray(datos.prendas)) {
            errores.push('Prendas no válidas');
        }
        
        return errores;
    }

    /**
     * Procesa y normaliza los datos de factura
     */
    procesarDatosFactura(datos) {
        // Asegurar que los datos tengan la estructura esperada
        const datosProcesados = {
            ...datos,
            numero_pedido: datos.numero_pedido || datos.numero_pedido_temporal,
            cliente: datos.cliente || 'Cliente no especificado',
            asesora: datos.asesora || datos.asesor || 'Sin asignar',
            forma_de_pago: datos.forma_de_pago || 'No especificada',
            prendas: datos.prendas || [],
            procesos: datos.procesos || [],
            epps: datos.epps || datos.epp || [],
            fecha_creacion: datos.fecha_creacion || new Date().toLocaleDateString('es-ES')
        };
        
        return datosProcesados;
    }
}

// Inicializar el servicio cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.invoiceDataFetcher = new InvoiceDataFetcher();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.invoiceDataFetcher = new InvoiceDataFetcher();
    });
} else {
    window.invoiceDataFetcher = new InvoiceDataFetcher();
}
