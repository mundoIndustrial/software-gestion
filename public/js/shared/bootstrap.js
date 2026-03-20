/**
 * =====================================================
 * SHARED BOOTSTRAP - DEPENDENCY INJECTION CONTAINER
 * =====================================================
 * Inicializa y conecta todos los servicios compartidos.
 * Se carga UNA VEZ en el layout y queda disponible globalmente.
 *
 * Dependencias (cargar ANTES de este archivo):
 *   1. shared/infrastructure/HttpClient.js
 *   2. shared/infrastructure/NotificationService.js
 *   3. shared/infrastructure/ModalManager.js
 *
 * Después de cargar:
 *   window.shared.http          → SharedHttpClient (instancia)
 *   window.shared.notify        → SharedNotification
 *   window.shared.modal         → SharedModal
 *   window.shared.isReady       → true
 *
 * Uso en cualquier módulo:
 *   const { http, notify, modal } = window.shared;
 *   const data = await http.get('/api/pedidos');
 *   notify.success('Pedido cargado');
 *   modal.open('miModal');
 */

(function() {
    'use strict';

    if (window.shared?.isReady) {
        console.warn('[Shared Bootstrap] Ya inicializado, saltando...');
        return;
    }

    // Instanciar HttpClient
    const http = new SharedHttpClient({
        timeout: 15000,
        retries: 2,
    });

    // Registrar en namespace global
    window.shared = Object.freeze({
        http:     http,
        notify:   SharedNotification,
        modal:    SharedModal,
        isReady:  true,
        version:  '1.0.0',
    });

    console.log('[Shared Bootstrap] Infraestructura compartida inicializada v1.0.0');
})();
