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
