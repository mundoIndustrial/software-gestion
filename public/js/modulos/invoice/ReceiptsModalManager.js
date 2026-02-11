/**
 * Gestor de Modal de Recibos
 * Maneja la creación y gestión del modal para visualizar recibos de producción
 */

class ReceiptsModalManager {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.crearModalRecibosDesdeListaPedidos = this.crearModalRecibos.bind(this);
        window.cerrarModalRecibos = this.cerrarModalRecibos.bind(this);
    }

    /**
     * Crea y muestra el modal con los recibos dinámicos
     */
    crearModalRecibos(datos, prendasIndex = null) {
        console.log('[ReceiptsModalManager] Creando modal de recibos');
        
        // Determinar dónde están los datos reales
        const datosReales = datos.data || datos;
        
        console.log('[ReceiptsModalManager] Datos procesados:', {
            cliente: datosReales.cliente,
            asesor: datosReales.asesor,
            numero_pedido: datosReales.numero_pedido,
            prendas_count: datosReales.prendas?.length || 0
        });
        
        // Usar el modal de supervisor existente
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        const overlay = document.getElementById('modal-overlay');
        
        if (!modalWrapper || !overlay) {
            console.error('[ReceiptsModalManager] No se encontró el modal de supervisor');
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'No se encontró el modal de recibos');
            }
            return;
        }
        
        // Mostrar el modal
        this.mostrarModal(modalWrapper, overlay);
        
        // Cargar ReceiptManager
        this.cargarReceiptManager(datosReales, prendasIndex);
    }

    /**
     * Muestra el modal con animación
     */
    mostrarModal(modalWrapper, overlay) {
        // Configurar overlay
        overlay.style.display = 'block';
        overlay.style.zIndex = '9997';
        overlay.style.position = 'fixed';
        overlay.style.opacity = '1';
        overlay.style.visibility = 'visible';
        
        // Configurar modal wrapper
        modalWrapper.style.display = 'block';
        modalWrapper.style.zIndex = '9998';
        modalWrapper.style.position = 'fixed';
        modalWrapper.style.top = '50%';
        modalWrapper.style.left = '50%';
        modalWrapper.style.transform = 'translate(-50%, -50%)';
        modalWrapper.style.pointerEvents = 'auto';
        modalWrapper.style.opacity = '1';
        modalWrapper.style.visibility = 'visible';
        
        console.log('[ReceiptsModalManager] Modal mostrado');
    }

    /**
     * Carga el ReceiptManager con los datos
     */
    cargarReceiptManager(datos, prendasIndex = null) {
        setTimeout(() => {
            // Verificar que el modal tenga los elementos necesarios
            const modalContainer = document.querySelector('.order-detail-modal-container');
            const receiptNumber = modalContainer?.querySelector('#receipt-number');
            const receiptTotal = modalContainer?.querySelector('#receipt-total');
            
            if (!receiptNumber || !receiptTotal) {
                console.error('[ReceiptsModalManager] No se encontraron elementos necesarios para ReceiptManager');
                if (window.notificationManager) {
                    window.notificationManager.mostrarError('Error', 'No se encontraron elementos del modal de recibos');
                }
                return;
            }
            
            // Cargar ReceiptManager
            if (typeof ReceiptManager === 'undefined') {
                this.cargarScriptReceiptManager(() => {
                    this.crearInstanciaReceiptManager(datos, prendasIndex);
                });
            } else {
                this.crearInstanciaReceiptManager(datos, prendasIndex);
            }
        }, 100);
    }

    /**
     * Carga el script de ReceiptManager dinámicamente
     */
    cargarScriptReceiptManager(callback) {
        const script = document.createElement('script');
        script.src = '/js/asesores/receipt-manager.js';
        script.onload = callback;
        script.onerror = () => {
            console.error('[ReceiptsModalManager] Error cargando ReceiptManager');
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'No se pudo cargar el gestor de recibos');
            }
        };
        document.head.appendChild(script);
    }

    /**
     * Crea una instancia de ReceiptManager
     */
    crearInstanciaReceiptManager(datos, prendasIndex) {
        try {
            console.debug('[ReceiptsModalManager] Creando ReceiptManager con datos:', datos);
            window.receiptManager = new ReceiptManager(datos, prendasIndex);
            
            // Inicializar botón X para insumos si existe
            if (typeof inicializarBotonCerrarInsumos === 'function') {
                setTimeout(() => {
                    inicializarBotonCerrarInsumos();
                }, 200);
            }
            
            console.log('[ReceiptsModalManager] ReceiptManager creado exitosamente');
        } catch (error) {
            console.error('[ReceiptsModalManager] Error creando ReceiptManager:', error);
            if (window.notificationManager) {
                window.notificationManager.mostrarError('Error', 'Error al crear el gestor de recibos');
            }
        }
    }

    /**
     * Cierra el modal de recibos
     */
    cerrarModalRecibos() {
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        const overlay = document.getElementById('modal-overlay');
        
        if (modalWrapper) {
            modalWrapper.style.display = 'none';
            modalWrapper.style.opacity = '0';
            modalWrapper.style.visibility = 'hidden';
        }
        
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
        }
        
        // Limpiar instancia de ReceiptManager
        if (window.receiptManager) {
            delete window.receiptManager;
        }
        
        console.log('[ReceiptsModalManager] Modal de recibos cerrado');
    }

    /**
     * Verifica si el modal de recibos está abierto
     */
    estaModalAbierto() {
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        const overlay = document.getElementById('modal-overlay');
        
        return modalWrapper && overlay && 
               modalWrapper.style.display === 'block' && 
               overlay.style.display === 'block';
    }

    /**
     * Actualiza los datos del modal de recibos
     */
    actualizarModalRecibos(datos, prendasIndex = null) {
        if (!this.estaModalAbierto()) {
            console.warn('[ReceiptsModalManager] Modal no está abierto para actualizar');
            return;
        }
        
        const datosReales = datos.data || datos;
        
        // Destruir instancia actual
        if (window.receiptManager) {
            delete window.receiptManager;
        }
        
        // Crear nueva instancia con datos actualizados
        this.crearInstanciaReceiptManager(datosReales, prendasIndex);
    }

    /**
     * Alterna la visibilidad del modal
     */
    alternarModal(datos, prendasIndex = null) {
        if (this.estaModalAbierto()) {
            this.cerrarModalRecibos();
        } else {
            this.crearModalRecibos(datos, prendasIndex);
        }
    }

    /**
     * Configura eventos adicionales para el modal
     */
    configurarEventosModal() {
        // Cerrar con ESC
        const manejadorEscape = (e) => {
            if (e.key === 'Escape' && this.estaModalAbierto()) {
                this.cerrarModalRecibos();
                document.removeEventListener('keydown', manejadorEscape);
            }
        };
        document.addEventListener('keydown', manejadorEscape);
        
        // Cerrar al hacer clic fuera del contenido
        const overlay = document.getElementById('modal-overlay');
        if (overlay) {
            overlay.onclick = (e) => {
                if (e.target === overlay && this.estaModalAbierto()) {
                    this.cerrarModalRecibos();
                }
            };
        }
    }

    /**
     * Prepara los datos para ReceiptManager
     */
    prepararDatosParaReceiptManager(datos) {
        const datosReales = datos.data || datos;
        
        // Asegurar estructura mínima requerida
        return {
            cliente: datosReales.cliente || 'Cliente no especificado',
            asesor: datosReales.asesor || datosReales.asesora || 'Sin asignar',
            asesora: datosReales.asesora || datosReales.asesor || 'Sin asignar',
            forma_de_pago: datosReales.forma_de_pago || 'No especificada',
            numero_pedido: datosReales.numero_pedido || 'N/A',
            fecha: datosReales.fecha || datosReales.fecha_creacion || new Date().toLocaleDateString(),
            prendas: datosReales.prendas || [],
            procesos: datosReales.procesos || [],
            epps: datosReales.epps || datosReales.epp || []
        };
    }

    /**
     * Valida que los datos sean válidos para el modal
     */
    validarDatosRecibos(datos) {
        const datosReales = datos.data || datos;
        
        if (!datosReales) {
            return { valido: false, errores: ['Datos no proporcionados'] };
        }
        
        const errores = [];
        
        if (!datosReales.numero_pedido) {
            errores.push('Número de pedido faltante');
        }
        
        if (!datosReales.cliente) {
            errores.push('Cliente faltante');
        }
        
        if (!datosReales.prendas || !Array.isArray(datosReales.prendas)) {
            errores.push('Prendas no válidas');
        }
        
        return {
            valido: errores.length === 0,
            errores: errores
        };
    }

    /**
     * Maneja errores de carga del modal
     */
    manejarError(error, datos) {
        console.error('[ReceiptsModalManager] Error:', error);
        
        const mensajeError = error.message || 'Error desconocido';
        
        if (window.notificationManager) {
            window.notificationManager.mostrarError(
                'Error en Recibos', 
                `No se pudo cargar los recibos: ${mensajeError}`
            );
        }
        
        // Intentar cerrar el modal si quedó abierto
        this.cerrarModalRecibos();
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.receiptsModalManager = new ReceiptsModalManager();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.receiptsModalManager = new ReceiptsModalManager();
    });
} else {
    window.receiptsModalManager = new ReceiptsModalManager();
}
