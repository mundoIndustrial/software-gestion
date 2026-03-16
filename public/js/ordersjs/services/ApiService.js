/**
 * API Service - Capa de abstracción para todas las llamadas HTTP
 * Centraliza los endpoints y la lógica de comunicación con el backend
 * 
 * ARQUITECTURA: Service Layer Pattern
 * - Responsabilidad única: comunicación con API
 * - SRP (Single Responsibility Principle) ✓
 * - DRY (Don't Repeat Yourself) ✓
 * - Sin lógica de negocio, solo HTTP
 * 
 * USO:
 * await ApiService.seguimiento.getPrendas(orderId);
 * await ApiService.proceso.guardar(data);
 * await ApiService.proceso.eliminar(procesoId);
 */

const ApiService = {
  /**
   * Obtener token CSRF del meta tag
   * @returns {string} Token CSRF
   */
  getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  },

  /**
   * Realizar petición fetch genérica con headers estándar
   * @param {string} url - URL del endpoint
   * @param {Object} options - Opciones de fetch
   * @returns {Promise<Object>} Respuesta JSON
   */
  async request(url, options = {}) {
    try {
      const defaultOptions = {
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.getCsrfToken(),
          ...options.headers
        },
        ...options
      };

      console.log(`[ApiService] ${options.method || 'GET'} ${url}`, defaultOptions);

      const response = await fetch(url, defaultOptions);

      if (!response.ok) {
        const errorText = await response.text();
        throw new Error(`HTTP ${response.status}: ${errorText}`);
      }

      return await response.json();
    } catch (error) {
      console.error(`[ApiService] Error en ${url}:`, error);
      throw error;
    }
  },

  /**
   * Endpoints para órdenes/pedidos
   */
  ordenes: {
    /**
     * Obtener datos básicos del pedido
     * @param {number} orderId - ID de la orden
     * @returns {Promise<Object>} Datos del pedido
     */
    getDatos: async (orderId) => {
      return ApiService.request(`/registros/${orderId}/recibos-datos`);
    },

    /**
     * Obtener consecutivo de costura
     * @param {number} orderId - ID de la orden
     * @param {number} prendaId - ID de la prenda (opcional)
     * @returns {Promise<Object>} Datos de costura
     */
    getConsecutivoCostura: async (orderId, prendaId = null) => {
      let url = `/registros/${orderId}/consecutivo-costura`;
      if (prendaId) {
        url += `?prenda_id=${encodeURIComponent(prendaId)}`;
      }
      return ApiService.request(url);
    }
  },

  /**
   * Endpoints para prendas con seguimiento
   */
  prendas: {
    /**
     * Obtener prendas con seguimiento de una orden
     * @param {number} orderId - ID de la orden
     * @returns {Promise<Object>} Datos con array de prendas
     */
    getSeguimiento: async (orderId) => {
      return ApiService.request(`/registros/${orderId}/seguimiento-prenda`);
    }
  },

  /**
   * Endpoints para procesos de producción
   */
  proceso: {
    /**
     * Guardar nuevo proceso o actualizar existente
     * @param {Object} data - Datos del proceso
     * @param {number} data.pedido_produccion_id - ID del pedido
     * @param {number} data.prenda_id - ID de la prenda
     * @param {string} data.area - Área/proceso
     * @param {string} data.encargado - Nombre del encargado
     * @param {string} data.estado - Estado (Pendiente/En proceso/Completado)
     * @param {string} data.fecha_inicio - Fecha de inicio (opcional)
     * @param {string} data.observaciones - Observaciones (opcional)
     * @returns {Promise<Object>} Proceso guardado
     */
    guardar: async (data) => {
      return ApiService.request('/seguimiento-proceso/guardar', {
        method: 'POST',
        body: JSON.stringify(data)
      });
    },

    /**
     * Actualizar proceso existente
     * @param {number} procesoId - ID del proceso
     * @param {Object} data - Datos a actualizar
     * @param {string} data.area - Área/proceso
     * @param {string} data.estado - Estado
     * @param {string} data.fecha_inicio - Fecha de inicio
     * @param {string} data.encargado - Encargado
     * @param {string} data.observaciones - Observaciones
     * @returns {Promise<Object>} Proceso actualizado
     */
    actualizar: async (procesoId, data) => {
      return ApiService.request(`/seguimiento-proceso/${procesoId}`, {
        method: 'PUT',
        body: JSON.stringify(data)
      });
    },

    /**
     * Eliminar proceso
     * @param {number} procesoId - ID del proceso
     * @returns {Promise<Object>} Respuesta de eliminación
     */
    eliminar: async (procesoId) => {
      return ApiService.request(`/seguimiento-proceso/${procesoId}`, {
        method: 'DELETE'
      });
    }
  }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
  window.ApiService = ApiService;
}
