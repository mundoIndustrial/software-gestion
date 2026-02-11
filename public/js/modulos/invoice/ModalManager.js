/**
 * Gestor de Modales
 * Maneja la creación y gestión de modales para la vista previa
 */

class ModalManager {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.crearModalPreviewFactura = this.crearModalPreviewFactura.bind(this);
        window.registrarFontSizesFactura = this.registrarFontSizesFactura.bind(this);
    }

    /**
     * Crea un modal con la vista previa de la factura
     */
    crearModalPreviewFactura(datos) {
        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('invoice-preview-modal-wrapper');
        if (modalAnterior) {
            modalAnterior.remove();
        }
        
        // Crear el modal
        const modal = document.createElement('div');
        modal.id = 'invoice-preview-modal-wrapper';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        `;
        
        // Generar HTML de la factura
        const htmlFactura = window.generarHTMLFactura(datos);
        
        modal.innerHTML = `
            <style>
                #invoice-preview-modal-wrapper * {
                    font-family: Arial, sans-serif;
                }
                #invoice-preview-modal-wrapper table td,
                #invoice-preview-modal-wrapper table th,
                #invoice-preview-modal-wrapper table {
                    font-size: 11px !important;
                }
                #invoice-preview-modal-wrapper em {
                    font-size: 11px !important;
                }
            </style>
            <div style="background: white; border-radius: 6px; width: 100%; max-width: 1100px; height: 95vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                <!-- Header -->
                <div style="padding: 8px 12px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9;">
                    <h3 style="margin: 0; color: #333; font-size: 11px; font-weight: 700;">
                         Pedido #${datos.numero_pedido || datos.numero_pedido_temporal} | ${datos.cliente}
                    </h3>
                    <button onclick="document.getElementById('invoice-preview-modal-wrapper').remove();" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; padding: 0; line-height: 1;">
                        ×
                    </button>
                </div>
                
                <!-- Content -->
                <div id="preview-content" style="flex: 1; overflow: auto; padding: 8px 10px; background: #fafafa;">
                    ${htmlFactura}
                </div>
                
                <!-- Footer -->
                <div style="padding: 8px 12px; border-top: 1px solid #ddd; display: flex; gap: 6px; justify-content: flex-end; background: #f9f9f9;">
                    <button onclick="document.getElementById('preview-content').contentWindow?.print() || window.print();" 
                            style="padding: 6px 12px; background: #2c3e50; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 11px;">
                         Imprimir
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        try {
            // Registrar los font-sizes de la factura
            this.registrarFontSizesFactura();
        } catch (e) {
            // Ignorar errores de logging
        }
        
        // Cerrar al hacer click en el fondo
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Configurar atajos de teclado
        this.configurarAtajosTeclado(modal);
    }

    /**
     * Registra y loguea todos los font-sizes de los elementos de la factura
     * (Función deshabilitada - logging removido)
     */
    registrarFontSizesFactura() {
        // Función deshabilitada - logging removido
        console.log('[ModalManager] Función registrarFontSizesFactura deshabilitada');
    }

    /**
     * Configura atajos de teclado para el modal
     */
    configurarAtajosTeclado(modal) {
        const manejadorTeclado = (e) => {
            if (document.getElementById('invoice-preview-modal-wrapper')) {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', manejadorTeclado);
                    modal.remove();
                }
                // Se pueden agregar más atajos aquí si es necesario
            }
        };
        document.addEventListener('keydown', manejadorTeclado);
    }

    /**
     * Cierra el modal de vista previa
     */
    cerrarModalPreview() {
        const modal = document.getElementById('invoice-preview-modal-wrapper');
        if (modal) {
            modal.remove();
        }
    }

    /**
     * Verifica si hay un modal abierto
     */
    estaModalAbierto() {
        return !!document.getElementById('invoice-preview-modal-wrapper');
    }

    /**
     * Actualiza el contenido del modal con nuevos datos
     */
    actualizarModal(datos) {
        const contentDiv = document.getElementById('preview-content');
        if (contentDiv) {
            const htmlFactura = window.generarHTMLFactura(datos);
            contentDiv.innerHTML = htmlFactura;
            
            // Actualizar título
            const headerTitle = document.querySelector('#invoice-preview-modal-wrapper h3');
            if (headerTitle) {
                headerTitle.textContent = `Pedido #${datos.numero_pedido || datos.numero_pedido_temporal} | ${datos.cliente}`;
            }
        }
    }

    /**
     * Crea un modal genérico con contenido personalizado
     */
    crearModalGenerico(options = {}) {
        const {
            titulo = 'Modal',
            contenido = '',
            ancho = '800px',
            alto = '600px',
            botones = [],
            cerrarFondo = true,
            cerrarEscape = true
        } = options;

        // Remover modal anterior si existe
        const modalAnterior = document.getElementById('generic-modal-wrapper');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Crear el modal
        const modal = document.createElement('div');
        modal.id = 'generic-modal-wrapper';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px;
        `;

        // Generar HTML de botones
        const botonesHTML = botones.map(btn => `
            <button onclick="${btn.onclick || ''}" 
                    style="padding: 6px 12px; background: ${btn.color || '#2c3e50'}; color: white; border: none; border-radius: 3px; cursor: pointer; font-weight: 600; font-size: 11px; margin-left: 6px;">
                ${btn.texto}
            </button>
        `).join('');

        modal.innerHTML = `
            <div style="background: white; border-radius: 6px; width: 100%; max-width: ${ancho}; height: ${alto}; max-height: 95vh; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                <!-- Header -->
                <div style="padding: 12px 16px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9;">
                    <h3 style="margin: 0; color: #333; font-size: 14px; font-weight: 700;">${titulo}</h3>
                    <button onclick="document.getElementById('generic-modal-wrapper').remove();" 
                            style="background: none; border: none; font-size: 20px; cursor: pointer; color: #999; padding: 0; line-height: 1;">
                        ×
                    </button>
                </div>
                
                <!-- Content -->
                <div style="flex: 1; overflow: auto; padding: 16px; background: #fafafa;">
                    ${contenido}
                </div>
                
                <!-- Footer (si hay botones) -->
                ${botones.length > 0 ? `
                    <div style="padding: 12px 16px; border-top: 1px solid #ddd; display: flex; gap: 6px; justify-content: flex-end; background: #f9f9f9;">
                        ${botonesHTML}
                    </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(modal);

        // Eventos
        if (cerrarFondo) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        if (cerrarEscape) {
            const manejadorTeclado = (e) => {
                if (document.getElementById('generic-modal-wrapper')) {
                    if (e.key === 'Escape') {
                        document.removeEventListener('keydown', manejadorTeclado);
                        modal.remove();
                    }
                }
            };
            document.addEventListener('keydown', manejadorTeclado);
        }

        return modal;
    }
}

// Inicializar el gestor cuando se cargue el script
document.addEventListener('DOMContentLoaded', () => {
    window.modalManager = new ModalManager();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.modalManager = new ModalManager();
    });
} else {
    window.modalManager = new ModalManager();
}
