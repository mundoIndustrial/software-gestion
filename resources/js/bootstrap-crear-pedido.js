/**
 * bootstrap-crear-pedido.js
 * 
 * Inicializa la aplicación de Crear Pedido desde Cotización
 * Se ejecuta en DOMContentLoaded
 * 
 * Uso: Importar en Blade y ejecutar al cargar la página
 */

import { CrearPedidoApp } from './modules/CrearPedidoApp.js';

/**
 * Inicializa la aplicación cuando el documento está listo
 * 
 * @param {Object} initialData - Datos iniciales de la aplicación
 *   - cotizaciones: Array de cotizaciones como DTOs
 *   - asesorActual: Nombre del asesor logueado
 *   - csrfToken: Token CSRF para requests
 */
export function initCrearPedidoApp(initialData) {
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            const app = new CrearPedidoApp(initialData);
            await app.inicializar();
            console.log('✅ Aplicación Crear Pedido iniciada correctamente');
        } catch (error) {
            console.error('❌ Error al inicializar aplicación:', error);
        }
    });
}

/**
 * Exporta función para inicializar la aplicación
 */
export default { initCrearPedidoApp };
