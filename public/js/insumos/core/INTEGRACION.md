/**
 * INTEGRACIÓN - ARQUITECTURA HYBRID DDD PARA INSUMOS
 * 
 * Estado:  CORE LAYER COMPLETADO (Domain + Infrastructure + Application)
 * 
 * Archivos Creados:
 * - public/js/insumos/core/domain/InsumoRepository.js          [Abstract interface]
 * - public/js/insumos/core/infrastructure/HttpClient.js        [HTTP abstraction con retry/timeout]
 * - public/js/insumos/core/infrastructure/SessionStorageInsumoRepository.js  [Implementación con caché]
 * - public/js/insumos/core/application/InsumoService.js        [Lógica de negocio]
 * - public/js/insumos/core/bootstrap.js                         [Dependency Injection container]
 * 
 * ========================================================================
 * PASO 1: ACTUALIZAR IMPORTS EN BLADE
 * ========================================================================
 * 
 * Agregar a: resources/views/layouts/insumos/app.blade.php
 * 
 * Antes del cierre de </body>, reemplazar:
 * 
 *    <!-- Scripts -->
 *    <script src="{{ asset('js/insumos/layout.js') }}"></script>
 *    @stack('scripts')
 * 
 * POR:
 * 
 *    <!-- Scripts -->
 *    <script src="{{ asset('js/insumos/layout.js') }}"></script>
 * 
 *    <!-- CORE ARCHITECTURE LAYER - orden crítico para DDD -->
 *    <!-- 1. Infrastructure: HTTP Client (sin dependencias) -->
 *    <script src="{{ asset('js/insumos/core/infrastructure/HttpClient.js') }}"></script>
 *    
 *    <!-- 2. Domain: Abstract Repository (interface contract) -->
 *    <script src="{{ asset('js/insumos/core/domain/InsumoRepository.js') }}"></script>
 *    
 *    <!-- 3. Infrastructure: Concrete Repository Implementation -->
 *    <script src="{{ asset('js/insumos/core/infrastructure/SessionStorageInsumoRepository.js') }}"></script>
 *    
 *    <!-- 4. Application: Service Layer (business logic) -->
 *    <script src="{{ asset('js/insumos/core/application/InsumoService.js') }}"></script>
 *    
 *    <!-- 5. Bootstrap: Dependency Injection & Initialization -->
 *    <script src="{{ asset('js/insumos/core/bootstrap.js') }}"></script>
 * 
 *    @stack('scripts')
 * 
 * ⚠️  IMPORTANTE:
 *    - El orden de carga es CRÍTICO - respeta exactamente el orden arriba
 *    - HttpClient debe cargar primero (usado por SessionStorageInsumoRepository)
 *    - InsumoRepository debe cargar antes de SessionStorageInsumoRepository
 *    - Bootstrap debe ser último (usa todas las capas anteriores)
 * 
 * ========================================================================
 * PASO 2: REFACTORIZAR index-blade-handlers.js
 * ========================================================================
 * 
 * PATRÓN ACTUAL ( NO RECOMENDADO):
 * 
 *    window.abrirModalInsumos = function(pedido, prendaId) {
 *        fetch(`/insumos/api/materiales/${pedido}`)
 *            .then(r => r.json())
 *            .then(data => llenarTablaInsumos(data.materiales));
 *    };
 * 
 * PATRÓN NUEVO ( CON DDD):
 * 
 *    window.abrirModalInsumos = async function(pedido, prendaId) {
 *        try {
 *            // Usar servicio inyectado en window.insumoService (por bootstrap)
 *            const insumos = await window.insumoService.obtenerInsumosDelPedido(pedido, prendaId);
 *            
 *            // Usar datos enriquecidos con lógica de negocio
 *            document.getElementById('modalPedido').textContent = pedido;
 *            document.getElementById('modalPrendaNombre').textContent = insumos.nombre_prenda || 'General';
 *            llenarTablaInsumos(insumos.materiales || []);
 *            
 *            // Modal visible
 *            const modal = document.getElementById('insumosModal');
 *            modal.style.display = 'flex';
 *        } catch (error) {
 *            console.error('[InsumoService Error]', error);
 *            if (error.name === 'ValidationError') {
 *                showToast('Datos inválidos: ' + error.message, 'error');
 *            } else if (error.name === 'BusinessError') {
 *                showToast('Error de lógica: ' + error.message, 'error');
 *            } else {
 *                showToast('Error al cargar insumos', 'error');
 *            }
 *        }
 *    };
 * 
 * ========================================================================
 * PASO 3: EJEMPLO COMPLETO - GUARDAR CAMBIOS
 * ========================================================================
 * 
 * PATRÓN ACTUAL ():
 * 
 *    function guardarCambiosInsumos() {
 *        const materiales = obtenerDatosTabla();
 *        fetch(`/insumos/api/materiales`, {
 *            method: 'POST',
 *            body: JSON.stringify({ materiales })
 *        })
 *        .then(r => r.json())
 *        .then(data => {
 *            showToast('Guardado', 'success');
 *            cache_manager.clear();  //  Acoplado a module global
 *        });
 *    }
 * 
 * PATRÓN NUEVO ():
 * 
 *    async function guardarCambiosInsumos() {
 *        try {
 *            const pedido = document.getElementById('modalPedido').textContent;
 *            const prendaId = document.getElementById('modalPrendaId').value;
 *            const materiales = obtenerDatosTabla();
 *            
 *            // Service maneja la validación y lógica de negocio
 *            const resultado = await window.insumoService.guardarCambiosInsumos(
 *                pedido,
 *                prendaId,
 *                materiales
 *            );
 *            
 *            showToast('Cambios guardados correctamente', 'success');
 *            cerrarModalInsumos();
 *            recargarTabla(); // Actualizar tabla principal
 *            
 *        } catch (error) {
 *            console.error('[InsumoService SaveError]', error);
 *            showToast('Error: ' + error.message, 'error');
 *        }
 *    }
 * 
 * VENTAJAS:
 *    ✓ Sin acoplamiento a globals (cache_manager, etc)
 *    ✓ Errores validables (ValidationError, BusinessError)
 *    ✓ Lógica de negocio centralizada en InsumoService
 *    ✓ Fácil de mockear/testear
 * 
 * ========================================================================
 * PASO 4: API DE InsumoService - MÉTODOS DISPONIBLES
 * ========================================================================
 * 
 * Accesible via: window.insumoService (inyectado por bootstrap.js)
 * 
 * 1️⃣  obtenerInsumosDelPedido(pedidoId, prendaId = null)
 *    Parámetros:
 *      - pedidoId: número del pedido (requerido)
 *      - prendaId: ID de prenda (opcional)
 *    Retorna: Promise<{nombre_prenda, materiales[], totalMateriales, materialesRecibidos, requiereCierre}>
 *    Lanza: ValidationError, BusinessError
 *    
 *    Ejemplo:
 *      const insumos = await window.insumoService.obtenerInsumosDelPedido(123);
 * 
 * 2️⃣  guardarCambiosInsumos(pedidoId, prendaId, materiales)
 *    Parámetros:
 *      - pedidoId: número del pedido
 *      - prendaId: ID de prenda
 *      - materiales: Array<{nombre_material, cantidad, ...}>
 *    Retorna: Promise<boolean>
 *    Lanza: ValidationError, BusinessError
 *    
 *    Ejemplo:
 *      const success = await window.insumoService.guardarCambiosInsumos(123, 456, [...]);
 * 
 * 3️⃣  tieneDataEnCache(pedidoId, prendaId = null)
 *    Parámetros:
 *      - pedidoId: número del pedido
 *      - prendaId: ID de prenda (opcional)
 *    Retorna: Promise<boolean>
 *    Uso: Saber si hay datos en caché (sin gastar request)
 *    
 *    Ejemplo:
 *      const enCache = await window.insumoService.tieneDataEnCache(123);
 * 
 * 4️⃣  limpiarCache(pedidoId = null)
 *    Parámetros:
 *      - pedidoId: número del pedido (opcional)
 *    Retorna: Promise<void>
 *    Nota: Si no pasa pedidoId, limpia TODO el caché
 *    
 *    Ejemplo:
 *      await window.insumoService.limpiarCache(123);  // Limpia solo pedido 123
 *      await window.insumoService.limpiarCache();     // Limpia todo
 * 
 * ========================================================================
 * PASO 5: MANEJO DE ERRORES
 * ========================================================================
 * 
 * Los servicios lanzan errores tipados (ó Error):
 * 
 * - ValidationError: Parámetros de entrada inválidos
 *   Uso: Mostrar error al usuario (datos incompletos)
 * 
 * - BusinessError: Lógica de negocio violada
 *   Uso: Mostrar error al usuario (al menos 1 material requerido)
 * 
 * - RepositoryError: Error en acceso a datos (thrown by SessionStorageInsumoRepository)
 *   Causa: sessionStorage saturado, error HTTP, etc.
 *   Uso: Log a consola, mostrar error genérico al usuario
 * 
 * - HttpError: Error en petición HTTP (thrown by HttpClient)
 *   Properties: status, statusText, response
 *   Uso: Detectar 404, 500, timeout, etc.
 * 
 * PATRÓN RECOMENDADO:
 * 
 *    try {
 *        const datos = await window.insumoService.obtenerInsumosDelPedido(id);
 *    } catch (error) {
 *        if (error.name === 'ValidationError') {
 *            // El usuario pasó datos inválidos
 *            showToast('Error de validación: ' + error.message, 'error');
 *        } else if (error.name === 'BusinessError') {
 *            // Violó regla de negocio
 *            showToast('Error de operación: ' + error.message, 'error');
 *        } else if (error.name === 'HttpError') {
 *            // Error de comunicación con servidor
 *            console.error('HTTP Error:', error.status, error.response);
 *            showToast('Error al conectar con el servidor', 'error');
 *        } else {
 *            // Error no esperado
 *            console.error('Unexpected error:', error);
 *            showToast('Error desconocido', 'error');
 *        }
 *    }
 * 
 * ========================================================================
 * PASO 6: TESTING (OPCIONAL)
 * ========================================================================
 * 
 * Con esta arquitectura, puedes mockear fácilmente:
 * 
 *    // Mock HttpClient
 *    class MockHttpClient extends HttpClient {
 *        async get(path) {
 *            return { materiales: [{...}] };
 *        }
 *    }
 *    
 *    // Crear repository con mock
 *    const mockRepo = new SessionStorageInsumoRepository(new MockHttpClient());
 *    
 *    // Crear service con mock repository
 *    const service = new InsumoService(mockRepo);
 *    
 *    // Testear sin servidor
 *    const resultado = await service.obtenerInsumosDelPedido(123);
 * 
 * ========================================================================
 * PASO 7: CONFIGURACIÓN (OPCIONAL)
 * ========================================================================
 * 
 * bootstrap.js permite configuración:
 * 
 *    // En HTML o app.js del layout
 *    new CoreBootstrap({
 *        httpTimeout: 15000,           // Timeout HTTP en ms (default 10000)
 *        cacheExpiry: 60 * 60 * 1000,  // TTL del caché en ms (default 30 min)
 *        retryAttempts: 5              // Reintentos en errores (default 3)
 *    }).boot();
 * 
 * ========================================================================
 * CHECKLIST DE MIGRACIÓN
 * ========================================================================
 * 
 * [ ] 1. Agregar imports de core/* al layout (PASO 1)
 * [ ] 2. Refactorizar abrirModalInsumos en index-blade-handlers.js (PASO 2)
 * [ ] 3. Refactorizar guardarCambiosInsumos (PASO 3)
 * [ ] 4. Refactorizar otras funciones que usan fetch directo
 * [ ] 5. Remover imports de cache-manager.js si no se usa
 * [ ] 6. Testear cada función refactorizada en navegador
 * [ ] 7. Verificar que window.insumoService está disponible (console)
 * [ ] 8. Validar que caché funciona (Network tab, sessionStorage)
 * [ ] 9. Testear manejo de errores (desconectar red, servidor offline)
 * [ ] 10. Actualizar documentación interna
 */
