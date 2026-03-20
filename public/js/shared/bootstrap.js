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
 *   4. shared/WebSocketClient.js
 *   5. shared/infrastructure/EchoReverbWebSocketClient.js
 *   6. shared/CacheRepository.js
 *   7. shared/infrastructure/SessionStorageCacheRepository.js
 *
 * Después de cargar:
 *   window.shared.http          → SharedHttpClient (instancia)
 *   window.shared.notify        → SharedNotification
 *   window.shared.modal         → SharedModal
 *   window.shared.cache         → SessionStorageCacheRepository (instancia)
 *   window.shared.websocket     → EchoReverbWebSocketClient (lazy, inicializa cuando se accede)
 *   window.shared.isReady       → true
 *
 * Uso en cualquier módulo:
 *   const { http, notify, modal, cache, websocket } = window.shared;
 *   const data = await http.get('/api/pedidos');
 *   notify.success('Pedido cargado');
 *   modal.open('miModal');
 *   const cached = cache.get('myKey');
 *   websocket.subscribe('channel', 'event', handler);
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

    // Instanciar CacheRepository
    const cache = new SessionStorageCacheRepository({
        storage: 'session',
        keyPrefix: 'shared_',
        garbageCollectionInterval: 300000,
    });

    // Closure for lazy WebSocketClient initialization
    let _websocketInstance = null;

    // Crear objeto compartido base
    const sharedObj = {
        http:     http,
        notify:   SharedNotification,
        modal:    SharedModal,
        cache:    cache,
        isReady:  true,
        version:  '1.1.0',
    };

    // Agregar getter lazy para WebSocketClient
    // Se inicializa bajo demanda cuando se accede por primera vez
    Object.defineProperty(sharedObj, 'websocket', {
        get() {
            // Verificar que Echo esté disponible
            if (!window.EchoInstance) {
                throw new Error(
                    '[Shared] WebSocketClient no disponible aún. ' +
                    'Espera a que resources/js/bootstrap.js se cargue (inicializa Echo/Reverb). ' +
                    'Usa: window.waitForEcho(() => { const ws = window.shared.websocket; ... })'
                );
            }

            // Crear una sola vez, reutilizar después
            if (!_websocketInstance) {
                _websocketInstance = new EchoReverbWebSocketClient(window.EchoInstance);
            }

            return _websocketInstance;
        },
        configurable: false,
        enumerable: true,
    });

    // Registrar en namespace global (congelado para evitar mutaciones)
    window.shared = Object.freeze(sharedObj);

    console.log('[Shared Bootstrap] Infraestructura compartida inicializada v1.1.0');
})();
