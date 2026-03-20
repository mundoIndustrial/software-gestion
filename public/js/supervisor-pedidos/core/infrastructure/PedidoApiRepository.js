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
