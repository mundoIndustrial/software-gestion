// --- PedidoRepository.js ---
/**
 * Domain Layer - Repository Interface
 * =====================================================
 * Define el contrato que deben cumplir todas las implementaciones
 * de acceso a datos para el módulo supervisor-pedidos.
 *
 * DDD Principle: El dominio no conoce detalles de infraestructura.
 */

class PedidoRepository {
    // ===== FILTROS =====

    /**
     * Obtiene opciones de filtro para una columna
     * @param {string} campo - Campo a filtrar (numero, cliente, estado, asesora, forma_pago)
     * @returns {Promise<{opciones: string[]}>}
     */
    async getFilterOptions(campo) {
        throw new Error('getFilterOptions() debe ser implementado por subclases');
    }

    // ===== SELECCIÓN =====

    /**
     * Marca un pedido como seleccionado
     * @param {number|string} pedidoId
     * @returns {Promise<{success: boolean}>}
     */
    async selectOrder(pedidoId) {
        throw new Error('selectOrder() debe ser implementado por subclases');
    }

    /**
     * Desmarca un pedido seleccionado
     * @param {number|string} pedidoId
     * @returns {Promise<{success: boolean}>}
     */
    async deselectOrder(pedidoId) {
        throw new Error('deselectOrder() debe ser implementado por subclases');
    }

    /**
     * Obtiene todas las selecciones guardadas
     * @returns {Promise<{success: boolean, selecciones: number[]}>}
     */
    async getSelections() {
        throw new Error('getSelections() debe ser implementado por subclases');
    }

    // ===== EDICIÓN DE PEDIDO =====

    /**
     * Obtiene datos del pedido para edición
     * @param {number|string} ordenId
     * @returns {Promise<{success: boolean, orden: Object, colores: Array, telas: Array}>}
     */
    async getOrderEditData(ordenId) {
        throw new Error('getOrderEditData() debe ser implementado por subclases');
    }

    /**
     * Actualiza un pedido
     * @param {number|string} ordenId
     * @param {FormData} formData
     * @returns {Promise<{success: boolean, message: string}>}
     */
    async updateOrder(ordenId, formData) {
        throw new Error('updateOrder() debe ser implementado por subclases');
    }

    /**
     * Elimina una imagen de un pedido
     * @param {string} tipo - Tipo de imagen (prenda, logo, tela)
     * @param {number|string} imageId
     * @returns {Promise<{success: boolean}>}
     */
    async deleteImage(tipo, imageId) {
        throw new Error('deleteImage() debe ser implementado por subclases');
    }

    /**
     * Calcula la fecha estimada de entrega
     * @param {number|string} ordenId
     * @param {number} diasEntrega
     * @returns {Promise<{success: boolean, fecha_estimada: string, fecha_estimada_iso: string}>}
     */
    async calculateEstimatedDate(ordenId, diasEntrega) {
        throw new Error('calculateEstimatedDate() debe ser implementado por subclases');
    }

    // ===== NAVEGACIÓN AJAX =====

    /**
     * Obtiene contenido HTML de una página via AJAX
     * @param {string} url
     * @returns {Promise<string>} HTML string
     */
    async fetchPageContent(url) {
        throw new Error('fetchPageContent() debe ser implementado por subclases');
    }
}

// Errores de dominio
class PedidoValidationError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PedidoValidationError';
    }
}

class PedidoBusinessError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PedidoBusinessError';
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PedidoRepository, PedidoValidationError, PedidoBusinessError };
} else {
    window.PedidoRepository = PedidoRepository;
    window.PedidoValidationError = PedidoValidationError;
    window.PedidoBusinessError = PedidoBusinessError;
}


// --- PedidoApiRepository.js ---
/**
 * Infrastructure Layer - PedidoApiRepository
 * =====================================================
 * Implementación concreta del PedidoRepository usando SharedHttpClient.
 * Centraliza TODAS las llamadas API del módulo supervisor-pedidos.
 *
 * Dependencias:
 *   - window.SharedHttpClient (shared/infrastructure/HttpClient.js)
 *   - window.PedidoRepository (core/domain/PedidoRepository.js)
 */

class PedidoApiRepository extends PedidoRepository {
    constructor(httpClient) {
        super();
        this.http = httpClient;
    }

    // ===== FILTROS =====

    async getFilterOptions(campo) {
        return await this.http.get(`/supervisor-pedidos/filtro-opciones/${encodeURIComponent(campo)}`);
    }

    // ===== SELECCIÓN =====

    async selectOrder(pedidoId) {
        return await this.http.post(`/supervisor-pedidos/seleccionar/${encodeURIComponent(pedidoId)}`, {});
    }

    async deselectOrder(pedidoId) {
        return await this.http.delete(`/supervisor-pedidos/seleccionar/${encodeURIComponent(pedidoId)}`);
    }

    async getSelections() {
        return await this.http.get('/supervisor-pedidos/selecciones');
    }

    // ===== EDICIÓN DE PEDIDO =====

    async getOrderEditData(ordenId) {
        return await this.http.get(`/ordenes/${encodeURIComponent(ordenId)}/editar-pedido`);
    }

    async updateOrder(ordenId, formData) {
        return await this.http.postFormData(`/supervisor-pedidos/${encodeURIComponent(ordenId)}/actualizar`, formData);
    }

    async deleteImage(tipo, imageId) {
        return await this.http.delete(`/supervisor-pedidos/imagen/${encodeURIComponent(tipo)}/${encodeURIComponent(imageId)}`);
    }

    async calculateEstimatedDate(ordenId, diasEntrega) {
        return await this.http.post(`/api/registros/${encodeURIComponent(ordenId)}/calcular-fecha-estimada`, {
            dia_de_entrega: parseInt(diasEntrega)
        });
    }

    // ===== NAVEGACIÓN AJAX =====

    async fetchPageContent(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        });

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        return await response.text();
    }
}

// Error específico de infraestructura
class PedidoRepositoryError extends Error {
    constructor(message, originalError = null) {
        super(message);
        this.name = 'PedidoRepositoryError';
        this.originalError = originalError;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PedidoApiRepository, PedidoRepositoryError };
} else {
    window.PedidoApiRepository = PedidoApiRepository;
    window.PedidoRepositoryError = PedidoRepositoryError;
}


// --- FilterService.js ---
/**
 * Application Layer - FilterService
 * =====================================================
 * Lógica de negocio para filtros de la tabla de pedidos.
 * Gestiona estado de filtros en URL y carga opciones del servidor.
 *
 * Responsabilidades:
 *   - Leer/escribir parámetros de filtro en la URL
 *   - Cargar opciones de filtro desde el repositorio
 *   - Navegar con AJAX (SPA-like) dentro de supervisor-pedidos
 */

class FilterService {
    constructor(repository) {
        this.repository = repository;
    }

    /**
     * Obtiene los valores de filtro activos desde la URL actual
     * @param {string} columna - Nombre de la columna
     * @returns {string[]}
     */
    getActiveFilterValues(columna) {
        const url = new URL(window.location.href);

        if (columna === 'numero' || columna === 'id-orden') {
            return this._splitParam(url, 'numero');
        }
        if (columna === 'fecha') {
            const desde = url.searchParams.get('fecha_desde') || '';
            const hasta = url.searchParams.get('fecha_hasta') || '';
            return [desde, hasta].filter(Boolean);
        }
        if (columna === 'forma_pago' || columna === 'forma-pago') {
            return this._splitParam(url, 'forma_pago');
        }

        return this._splitParam(url, columna);
    }

    /**
     * Carga opciones de filtro del servidor
     * @param {string} campo - Campo a filtrar
     * @returns {Promise<{opciones: string[]}>}
     */
    async loadFilterOptions(campo) {
        if (!campo) {
            throw new PedidoValidationError('Campo de filtro requerido');
        }
        return await this.repository.getFilterOptions(campo);
    }

    /**
     * Construye la URL con los filtros aplicados
     * @param {string} columna - Columna del filtro
     * @param {string[]} valores - Valores seleccionados
     * @param {{desde?: string, hasta?: string}} fechas - Rango de fechas (solo para filtro fecha)
     * @returns {string} URL con filtros aplicados
     */
    buildFilteredUrl(columna, valores, fechas = {}) {
        const url = new URL(window.location.href);

        if (columna === 'fecha') {
            url.searchParams.delete('fecha_desde');
            url.searchParams.delete('fecha_hasta');
            if (fechas.desde) url.searchParams.set('fecha_desde', fechas.desde);
            if (fechas.hasta) url.searchParams.set('fecha_hasta', fechas.hasta);
            return url.toString();
        }

        const paramName = this._resolveParamName(columna);
        url.searchParams.delete(paramName);

        if (valores.length > 0) {
            url.searchParams.set(paramName, valores.join(','));
        }

        return url.toString();
    }

    /**
     * Navega a una URL usando AJAX (reemplaza contenido del contenedor)
     * @param {string} urlString - URL destino
     * @param {{pushState?: boolean}} options
     * @returns {Promise<boolean>} true si la navegación fue exitosa
     */
    async navigateAjax(urlString, options = {}) {
        const { pushState = true } = options;
        const container = document.getElementById('supervisorPedidosIndexContent');

        if (!container) {
            window.location.href = urlString;
            return false;
        }

        try {
            container.style.opacity = '0.6';
            container.style.pointerEvents = 'none';

            const html = await this.repository.fetchPageContent(urlString);
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const next = doc.getElementById('supervisorPedidosIndexContent');

            if (!next) {
                window.location.href = urlString;
                return false;
            }

            container.innerHTML = next.innerHTML;

            if (pushState) {
                window.history.pushState({ url: urlString }, '', urlString);
            }

            return true;
        } catch (e) {
            console.error('[FilterService] Error en navegación AJAX:', e);
            window.location.href = urlString;
            return false;
        } finally {
            container.style.opacity = '';
            container.style.pointerEvents = '';
        }
    }

    // ===== PRIVADOS =====

    _splitParam(url, name) {
        const raw = url.searchParams.get(name) || '';
        return raw.split(',').map(v => v.trim()).filter(Boolean);
    }

    _resolveParamName(columna) {
        if (columna === 'id-orden' || columna === 'numero') return 'numero';
        if (columna === 'forma-pago' || columna === 'forma_pago') return 'forma_pago';
        return columna;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { FilterService };
} else {
    window.FilterService = FilterService;
}


// --- SelectionService.js ---
/**
 * Application Layer - SelectionService
 * =====================================================
 * Lógica de negocio para selección múltiple de pedidos.
 * Persiste el estado de selección en el servidor.
 *
 * Responsabilidades:
 *   - Seleccionar/deseleccionar pedidos
 *   - Cargar selecciones guardadas
 *   - Revertir UI si el servidor falla
 */

class SelectionService {
    constructor(repository) {
        this.repository = repository;
    }

    /**
     * Selecciona un pedido en el servidor
     * @param {number|string} pedidoId
     * @returns {Promise<boolean>} true si fue exitoso
     */
    async select(pedidoId) {
        if (!pedidoId) {
            throw new PedidoValidationError('pedidoId es requerido');
        }

        const data = await this.repository.selectOrder(pedidoId);
        return data.success === true;
    }

    /**
     * Deselecciona un pedido en el servidor
     * @param {number|string} pedidoId
     * @returns {Promise<boolean>} true si fue exitoso
     */
    async deselect(pedidoId) {
        if (!pedidoId) {
            throw new PedidoValidationError('pedidoId es requerido');
        }

        const data = await this.repository.deselectOrder(pedidoId);
        return data.success === true;
    }

    /**
     * Carga las selecciones guardadas del servidor
     * @returns {Promise<number[]>} Array de IDs seleccionados
     */
    async loadSavedSelections() {
        const data = await this.repository.getSelections();

        if (data && data.success && Array.isArray(data.selecciones)) {
            return data.selecciones;
        }

        return [];
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SelectionService };
} else {
    window.SelectionService = SelectionService;
}


// --- OrderEditService.js ---
/**
 * Application Layer - OrderEditService
 * =====================================================
 * Lógica de negocio para edición de pedidos.
 * Gestiona carga de datos, actualización, imágenes y fecha estimada.
 *
 * Responsabilidades:
 *   - Cargar datos del pedido para edición
 *   - Guardar cambios del formulario
 *   - Eliminar imágenes (inmediata o marcada para lote)
 *   - Calcular fecha estimada de entrega
 */

class OrderEditService {
    constructor(repository) {
        this.repository = repository;
        this._imagenesParaEliminar = [];
    }

    /**
     * Carga los datos del pedido para edición
     * @param {number|string} ordenId
     * @returns {Promise<{orden: Object, colores: Array, telas: Array}>}
     */
    async loadOrderData(ordenId) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }

        const data = await this.repository.getOrderEditData(ordenId);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al cargar datos del pedido');
        }

        return {
            orden: data.orden,
            colores: data.colores || [],
            telas: data.telas || [],
        };
    }

    /**
     * Guarda cambios del formulario de edición
     * @param {number|string} ordenId
     * @param {FormData} formData
     * @returns {Promise<{success: boolean, message: string}>}
     */
    async saveOrder(ordenId, formData) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }

        const data = await this.repository.updateOrder(ordenId, formData);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al actualizar pedido');
        }

        return data;
    }

    /**
     * Elimina una imagen inmediatamente
     * @param {string} tipo - Tipo de imagen (prenda, logo, tela) 
     * @param {number|string} imageId
     * @returns {Promise<{success: boolean}>}
     */
    async deleteImageNow(tipo, imageId) {
        if (!tipo || !imageId) {
            throw new PedidoValidationError('tipo e imageId son requeridos');
        }

        const data = await this.repository.deleteImage(tipo, imageId);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al eliminar imagen');
        }

        return data;
    }

    /**
     * Marca una imagen para eliminación diferida (dentro del modal)
     * @param {number|string} imageId
     */
    markImageForDeletion(imageId) {
        if (!this._imagenesParaEliminar.includes(imageId)) {
            this._imagenesParaEliminar.push(imageId);
        }
    }

    /**
     * Obtiene las imágenes marcadas para eliminación
     * @returns {number[]}
     */
    getMarkedImages() {
        return [...this._imagenesParaEliminar];
    }

    /**
     * Limpia la lista de imágenes pendientes de eliminación
     */
    clearMarkedImages() {
        this._imagenesParaEliminar = [];
    }

    /**
     * Calcula la fecha estimada de entrega
     * @param {number|string} ordenId
     * @param {number} diasEntrega
     * @returns {Promise<{fecha_estimada: string, fecha_estimada_iso: string}>}
     */
    async calculateEstimatedDate(ordenId, diasEntrega) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }
        if (!diasEntrega || diasEntrega <= 0) {
            throw new PedidoValidationError('Número de días de entrega debe ser mayor a 0');
        }

        const data = await this.repository.calculateEstimatedDate(ordenId, diasEntrega);

        if (!data.success || !data.fecha_estimada) {
            throw new PedidoBusinessError(data.message || 'Error al calcular la fecha estimada');
        }

        return {
            fecha_estimada: data.fecha_estimada,
            fecha_estimada_iso: data.fecha_estimada_iso,
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OrderEditService };
} else {
    window.OrderEditService = OrderEditService;
}


// --- bootstrap.js ---
/**
 * Core Bootstrap - Supervisor Pedidos DI Container
 * =====================================================
 * Inicializa la arquitectura DDD del módulo supervisor-pedidos.
 * Instancia las capas en orden y expone servicios globalmente.
 *
 * Dependencias (cargar ANTES de este archivo):
 *   1. shared/bootstrap.js             → window.shared
 *   2. core/domain/PedidoRepository.js  → PedidoRepository
 *   3. core/infrastructure/PedidoApiRepository.js → PedidoApiRepository
 *   4. core/application/FilterService.js      → FilterService
 *   5. core/application/SelectionService.js   → SelectionService
 *   6. core/application/OrderEditService.js   → OrderEditService
 *   7. core/bootstrap.js (este archivo)
 *
 * Después de cargar:
 *   window.supervisorPedidos.filterService     → FilterService
 *   window.supervisorPedidos.selectionService  → SelectionService
 *   window.supervisorPedidos.orderEditService  → OrderEditService
 *   window.supervisorPedidos.repository        → PedidoApiRepository
 *   window.supervisorPedidos.isReady           → true
 */

(function() {
    'use strict';

    // ===== VALIDACIÓN ESTRICTA =====
    if (!window.shared?.isReady) {
        throw new Error('[SP Bootstrap] window.shared no está disponible. Carga shared/bootstrap.js ANTES.');
    }

    if (typeof PedidoApiRepository === 'undefined') {
        throw new Error('[SP Bootstrap] PedidoApiRepository no disponible. Carga core/infrastructure/PedidoApiRepository.js ANTES.');
    }

    if (typeof FilterService === 'undefined') {
        throw new Error('[SP Bootstrap] FilterService no disponible. Carga core/application/FilterService.js ANTES.');
    }

    if (typeof SelectionService === 'undefined') {
        throw new Error('[SP Bootstrap] SelectionService no disponible. Carga core/application/SelectionService.js ANTES.');
    }

    if (typeof OrderEditService === 'undefined') {
        throw new Error('[SP Bootstrap] OrderEditService no disponible. Carga core/application/OrderEditService.js ANTES.');
    }

    if (window.supervisorPedidos?.isReady) {
        return;
    }

    // ===== INSTANCIACIÓN (bottom-up) =====

    // 1. Infrastructure - Repository (inyectar SharedHttpClient)
    const repository = new PedidoApiRepository(window.shared.http);

    // 2. Application - Services (inyectar Repository)
    const filterService = new FilterService(repository);
    const selectionService = new SelectionService(repository);
    const orderEditService = new OrderEditService(repository);

    // ===== EXPORTAR =====
    window.supervisorPedidos = Object.freeze({
        filterService,
        selectionService,
        orderEditService,
        repository,
        isReady: true,
        version: '2.0.0',
    });
})();
