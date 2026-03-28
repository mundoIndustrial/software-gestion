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
        const response = await this.http.get(`/api/supervisor-pedidos/filtro-opciones/${encodeURIComponent(campo)}`);
        return response?.data ?? response;
    }

    // ===== SELECCIÓN =====

    async selectOrder(pedidoId) {
        const response = await this.http.post(`/api/supervisor-pedidos/seleccionar/${encodeURIComponent(pedidoId)}`, {});
        return response?.data ?? response;
    }

    async deselectOrder(pedidoId) {
        const response = await this.http.delete(`/api/supervisor-pedidos/seleccionar/${encodeURIComponent(pedidoId)}`);
        return response?.data ?? response;
    }

    async getSelections() {
        const response = await this.http.get('/api/supervisor-pedidos/selecciones');
        const payload = response?.data ?? response;

        // Compatibilidad con SelectionService (espera "selecciones")
        if (payload && Array.isArray(payload.selections) && !Array.isArray(payload.selecciones)) {
            payload.selecciones = payload.selections;
        }

        return payload;
    }

    // ===== EDICIÓN DE PEDIDO =====

    async getOrderEditData(ordenId) {
        return await this.http.get(`/ordenes/${encodeURIComponent(ordenId)}/editar-pedido`);
    }

    async updateOrder(ordenId, formData) {
        return await this.http.postFormData(`/api/supervisor-pedidos/ordenes/${encodeURIComponent(ordenId)}/actualizar`, formData);
    }

    async deleteImage(tipo, imageId) {
        return await this.http.delete(`/api/supervisor-pedidos/imagenes/${encodeURIComponent(tipo)}/${encodeURIComponent(imageId)}`);
    }

    async calculateEstimatedDate(ordenId, diasEntrega) {
        return await this.http.post(`/api/registros/${encodeURIComponent(ordenId)}/calcular-fecha-estimada`, {
            dia_de_entrega: parseInt(diasEntrega)
        });
    }

    // ===== NAVEGACIÓN AJAX =====

    async fetchPageContent(url) {
        const sourceUrl = new URL(url, window.location.origin);
        const apiPath = `/api/supervisor-pedidos/ordenes-fragment${sourceUrl.search || ''}`;
        const response = await this.http.get(apiPath);

        if (!response?.success || !response?.data?.html) {
            throw new Error('Respuesta inválida al cargar fragmento de órdenes');
        }

        return response.data.html;
    }

    async fetchOrdersData(url) {
        const sourceUrl = new URL(url, window.location.origin);
        const apiPath = `/api/supervisor-pedidos/ordenes${sourceUrl.search || ''}`;
        const response = await this.http.get(apiPath);

        if (!response?.success || !response?.data) {
            throw new Error('Respuesta inválida al cargar órdenes');
        }

        return response.data;
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
