/**
 * Application Service: OrderApiService
 * 
 * Capa de aplicacion que coordina entre Domain e Infrastructure.
 * 
 * Responsabilidades:
 * - Centralizar TODAS las llamadas a APIs
 * - Transformar respuestas del backend en objetos de dominio
 * - Manejo centralizado y consistente de errores
 * - Validaciones de negocio antes de enviar al backend
 * - Preparado para testing de APIs (facil mockear)
 * 
 * Patron: Use Cases / Application Services (DDD)
 * Cada metodo representa un "use case" o caso de uso de la aplicacion.
 * 
 * Beneficios:
 * - Un solo lugar para todas las APIs
 * - Facil cambiar endpoints sin tocar el resto del codigo
 * - Manejo centralizado de CSRF, headers, errores
 * - Testeable: puede mockear sin cambiar tracking-modal-handler
 */

class OrderApiService {
  /**
   * API base URL (puede configurarse desde el backend)
   * @type {String}
   */
  static BASE_URL = '/api';

  /**
   * Timeout por defecto para requests (ms)
   * @type {Number}
   */
  static REQUEST_TIMEOUT = 30000; // 30 segundos

  /**
   * Cargar datos basicos de una orden
   * Use Case: "Ver detalles de la orden"
   * 
   * @param {Number} orderId - ID de la orden
   * @returns {Promise<Object>} Datos de la orden
   * @throws {Error} Si hay error en la API
   */
  static async loadOrderData(orderId) {
    if (!orderId) {
      throw new Error('OrderApiService: orderId es requerido');
    }

    console.log('[OrderApiService.loadOrderData] Cargando orden:', orderId);

    try {
      const response = await this.#fetchWithTimeout(
        `/registros/${orderId}/recibos-datos`,
        { method: 'GET' }
      );

      if (!response.ok) {
        throw new Error(
          `Error al cargar datos del pedido: ${response.status} ${response.statusText}`
        );
      }

      const result = await response.json();
      console.log('[OrderApiService.loadOrderData] âœ“ Respuesta:', result);

      // Extraer datos desde la estructura del endpoint
      const orderData = result.data || result;

      // Validar estructura minima
      this.#validateOrderData(orderData);

      return orderData;
    } catch (error) {
      console.error('[OrderApiService.loadOrderData] âœ— Error:', error);
      throw this.#formatError('cargar datos del pedido', error);
    }
  }

  /**
   * Cargar prendas con seguimiento de una orden
   * Use Case: "Ver prendas y su progreso"
   * 
   * @param {Number} orderId - ID de la orden
   * @returns {Promise<Array>} Array de prendas
   * @throws {Error} Si hay error en la API
   */
  static async loadPrendasWithTracking(orderId) {
    if (!orderId) {
      throw new Error('OrderApiService: orderId es requerido');
    }

    console.log('[OrderApiService.loadPrendasWithTracking] Cargando prendas:', orderId);

    try {
      const response = await this.#fetchWithTimeout(
        `/registros/${orderId}/seguimiento-prenda`,
        { method: 'GET' }
      );

      if (!response.ok) {
        throw new Error(
          `Error al cargar seguimiento de prendas: ${response.status}`
        );
      }

      const data = await response.json();
      console.log('[OrderApiService.loadPrendasWithTracking] âœ“ Prendas cargadas:', data.prendas?.length);

      return {
        prendas: data.prendas || [],
        areasConfig: data.areas_config || {},
        pedido: data.pedido || {}
      };
    } catch (error) {
      console.error('[OrderApiService.loadPrendasWithTracking] âœ— Error:', error);
      throw this.#formatError('cargar prendas con seguimiento', error);
    }
  }

  /**
   * Cargar lista de encargados para una area especifica
   * Use Case: "Asignar responsable a un proceso"
   * 
   * @param {String} area - Nombre del area (ej: "costura", "corte")
   * @returns {Promise<Array>} Array de encargados
   * @throws {Error} Si hay error o no hay encargados disponibles
   */
  static async loadEncargados(area) {
    if (!area) {
      throw new Error('OrderApiService: area es requerida');
    }

    console.log('[OrderApiService.loadEncargados] Cargando encargados para:', area);

    try {
      const response = await this.#fetchWithTimeout(
        `${this.BASE_URL}/areas/${encodeURIComponent(area)}/encargados`,
        { method: 'GET' }
      );

      if (!response.ok) {
        throw new Error(
          `Error al cargar encargados: ${response.status}`
        );
      }

      const data = await response.json();

      if (!data.success) {
        throw new Error(data.message || `No hay encargados para: ${area}`);
      }

      if (!data.encargados || !Array.isArray(data.encargados)) {
        throw new Error(`Respuesta invalida del servidor: encargados no es un array`);
      }

      if (data.encargados.length === 0) {
        throw new Error(`No hay encargados disponibles para: ${area}`);
      }

      console.log('[OrderApiService.loadEncargados] âœ“ Encargados cargados:', data.encargados.length);
      return data.encargados;
    } catch (error) {
      console.error('[OrderApiService.loadEncargados] âœ— Error:', error);
      throw this.#formatError(`cargar encargados para ${area}`, error);
    }
  }

  /**
   * Cargar datos de consecutivo-costura para una prenda
   * Use Case: "Refrescar datos de area/encargado en tabla de recibos"
   * 
   * @param {Number} orderId - ID de la orden
   * @param {Number} prendaId - ID de la prenda
   * @returns {Promise<Object>} Datos del consecutivo
   * @throws {Error} Si hay error
   */
  static async loadConsecutivoCostura(orderId, prendaId) {
    if (!orderId) {
      throw new Error('OrderApiService: orderId es requerido');
    }
    if (!prendaId) {
      throw new Error('OrderApiService: prendaId es requerido');
    }

    console.log('[OrderApiService.loadConsecutivoCostura] Cargando:', { orderId, prendaId });

    try {
      const response = await this.#fetchWithTimeout(
        `/registros/${orderId}/consecutivo-costura?prenda_id=${encodeURIComponent(prendaId)}`,
        { method: 'GET' }
      );

      if (!response.ok) {
        throw new Error(`Error al cargar consecutivo-costura: ${response.status}`);
      }

      const data = await response.json();
      console.log('[OrderApiService.loadConsecutivoCostura] âœ“ Datos cargados');
      return data;
    } catch (error) {
      console.error('[OrderApiService.loadConsecutivoCostura] âœ— Error:', error);
      throw this.#formatError('cargar consecutivo-costura', error);
    }
  }

  /**
   * Calcular fecha estimada de entrega
   * Use Case: "Establecer fecha de entrega basada en dias de trabajo"
   * 
   * El backend calcula los dias habiles considerando:
   * - Festivos segun la localidad
   * - Zona horaria del servidor
   * - Reglas de negocio de la empresa
   * 
   * @param {Number} orderId - ID de la orden
   * @param {Number} estimatedDays - Dias estimados para entregar
   * @returns {Promise<Object>} { fecha_estimada, dias_calculados, ... }
   * @throws {Error} Si hay error
   */
  static async calculateDeliveryDate(orderId, estimatedDays, prendaId = null) {
    if (!orderId) {
      throw new Error('OrderApiService: orderId es requerido');
    }

    if (!Number.isFinite(estimatedDays) || estimatedDays < 0) {
      throw new Error('OrderApiService: estimatedDays debe ser un numero positivo');
    }

    console.log('[OrderApiService.calculateDeliveryDate] Calculando fecha:', {
      orderId,
      estimatedDays,
      prendaId
    });

    try {
      const response = await this.#fetchWithTimeout(
        `/registros/${orderId}/dia-entrega`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.#getCsrfToken()
          },
          body: JSON.stringify({
            dia_de_entrega: estimatedDays,
            prenda_id: prendaId,
            calcular_fecha_estimada: true
          })
        }
      );

      if (!response.ok) {
        throw new Error(
          `Error al calcular fecha de entrega: ${response.status}`
        );
      }

      const result = await response.json();
      console.log('[OrderApiService.calculateDeliveryDate] âœ“ Fecha calculada:', result);

      if (!result?.success) {
        throw new Error(result?.message || 'No se pudo guardar el día de entrega');
      }

      return result?.data || result;
    } catch (error) {
      console.error('[OrderApiService.calculateDeliveryDate] âœ— Error:', error);
      throw this.#formatError('calcular fecha de entrega', error);
    }
  }

  /**
   * Guardar nuevo proceso para una prenda
   * Use Case: "Registrar nuevo proceso de manufactura"
   * 
   * @param {Number} prendasId - ID de la prenda
   * @param {Object} procesoData - Datos del proceso
   * @returns {Promise<Object>} Respuesta del servidor
   * @throws {Error} Si hay error
   */
  static async saveProceso(prendasId, procesoData) {
    if (!prendasId) {
      throw new Error('OrderApiService: prendasId es requerido');
    }

    if (!procesoData) {
      throw new Error('OrderApiService: procesoData es requerido');
    }

    console.log('[OrderApiService.saveProceso] Guardando proceso:', { prendasId, procesoData });

    try {
      const response = await this.#fetchWithTimeout(
        `/seguimiento-proceso/guardar`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.#getCsrfToken()
          },
          body: JSON.stringify(procesoData)
        }
      );

      if (!response.ok) {
        throw new Error(`Error al guardar proceso: ${response.status}`);
      }

      const result = await response.json();
      console.log('[OrderApiService.saveProceso] âœ“ Proceso guardado');

      return result;
    } catch (error) {
      console.error('[OrderApiService.saveProceso] âœ— Error:', error);
      throw this.#formatError('guardar proceso', error);
    }
  }

  /**
   * Eliminar un proceso
   * Use Case: "Deshacer registracion de un proceso"
   * 
   * @param {Number} procesId - ID del proceso
   * @returns {Promise<Object>} Respuesta del servidor
   * @throws {Error} Si hay error
   */
  static async deleteProceso(procesId) {
    if (!procesId) {
      throw new Error('OrderApiService: procesId es requerido');
    }

    console.log('[OrderApiService.deleteProceso] Eliminando proceso:', procesId);

    try {
      const response = await this.#fetchWithTimeout(
        `/seguimiento-proceso/${procesId}`,
        {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': this.#getCsrfToken()
          }
        }
      );

      if (!response.ok) {
        throw new Error(`Error al eliminar proceso: ${response.status}`);
      }

      const result = await response.json();
      console.log('[OrderApiService.deleteProceso] âœ“ Proceso eliminado');

      return result;
    } catch (error) {
      console.error('[OrderApiService.deleteProceso] âœ— Error:', error);
      throw this.#formatError('eliminar proceso', error);
    }
  }

  /**
   * Metodo estatico para eliminar proceso
   * Alias de deleteProceso para compatibilidad con DI
   * 
   * @param {Number} procesoId - ID del proceso
   * @returns {Promise<Object>} Respuesta del servidor
   */
  static async delete(procesoId) {
    return this.deleteProceso(procesoId);
  }

  /**
   * Actualizar un proceso existente
   * Use Case: "Modificar estado/encargado/area de un proceso"
   * 
   * @param {Number} procesoId - ID del proceso
   * @param {Object} procesoData - { area, estado, fecha_inicio, encargado, observaciones }
   * @returns {Promise<Object>} Respuesta del servidor
   * @throws {Error} Si hay error
   */
  static async updateProceso(procesoId, procesoData) {
    if (!procesoId) {
      throw new Error('OrderApiService: procesoId es requerido');
    }

    if (!procesoData) {
      throw new Error('OrderApiService: procesoData es requerido');
    }

    console.log('[OrderApiService.updateProceso] Actualizando proceso:', { procesoId, procesoData });

    try {
      const response = await this.#fetchWithTimeout(
        `/seguimiento-proceso/${procesoId}`,
        {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.#getCsrfToken()
          },
          body: JSON.stringify(procesoData)
        }
      );

      if (!response.ok) {
        throw new Error(`Error al actualizar proceso: ${response.status}`);
      }

      const result = await response.json();
      console.log('[OrderApiService.updateProceso] âœ“ Proceso actualizado');

      return result;
    } catch (error) {
      console.error('[OrderApiService.updateProceso] âœ— Error:', error);
      throw this.#formatError('actualizar proceso', error);
    }
  }

  /**
   * ============================================================
   * METODOS PRIVADOS (Helpers internos)
   * ============================================================
   */

  /**
   * Fetch con timeout automatico
   * @private
   */
  static async #fetchWithTimeout(url, options = {}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), this.REQUEST_TIMEOUT);

    try {
      return await fetch(url, {
        ...options,
        signal: controller.signal
      });
    } finally {
      clearTimeout(timeoutId);
    }
  }

  /**
   * Obtener CSRF token del meta tag
   * @private
   */
  static #getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')
      ?.getAttribute('content') || '';
  }

  /**
   * Validar estructura minima de datos de orden
   * @private
   * @throws {Error} Si datos invalidos
   */
  static #validateOrderData(orderData) {
    if (!orderData || typeof orderData !== 'object') {
      throw new Error('Respuesta invalida: orderData no es un objeto');
    }

    if (!orderData.id) {
      throw new Error('Respuesta invalida: orderData sin ID');
    }

    if (!orderData.numero_pedido) {
      throw new Error('Respuesta invalida: orderData sin numero de pedido');
    }
  }

  /**
   * Formatear error de forma consistente
   * @private
   */
  static #formatError(context, error) {
    let message = `Error al ${context}`;

    if (error instanceof TypeError) {
      // Network error or fetch error
      message += ': Error de red. Verifica tu conexion.';
    } else if (error.name === 'AbortError') {
      // Timeout
      message += ': La solicitud tardo demasiado. Intenta nuevamente.';
    } else if (error.message) {
      message += `: ${error.message}`;
    }

    return new Error(message);
  }
}

export default OrderApiService;
