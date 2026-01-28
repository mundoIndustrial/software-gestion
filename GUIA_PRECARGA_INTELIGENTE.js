/**
 * 🚀 SOLUCIÓN DE PRECARGUÍA - GUÍA DE IMPLEMENTACIÓN
 * 
 * PROBLEMA: Primera apertura del modal de edición demora ~4.4s
 * SOLUCIÓN: Precargar módulos en background cuando está idle
 * 
 * ============================================================================
 * FLUJO DE FUNCIONAMIENTO
 * ============================================================================
 * 
 * 1️⃣ PÁGINA CARGA
 *    ↓
 *    └─ prenda-editor-preloader.js se inyecta y se inicializa
 *    └─ Espera 2 segundos para que la página esté lista
 * 
 * 2️⃣ BACKGROUND IDLE (después de 2s)
 *    ↓
 *    └─ El navegador está "idle" (usuario no hace nada)
 *    └─ requestIdleCallback() ejecuta la precarguía
 *    └─ Los módulos se cargan EN BACKGROUND (~4.4s pero sin bloquear)
 * 
 * 3️⃣ USUARIO HACE CLIC EN "EDITAR"
 *    ↓
 *    ├─ SI módulos ya precargados: Abre inmediatamente ⚡
 *    └─ SI aún cargando: Muestra loader mientras termina ⏳
 * 
 * ============================================================================
 * ARCHIVOS INVOLUCRADOS
 * ============================================================================
 * 
 * 📄 prenda-editor-preloader.js (NUEVO)
 *    └─ Maneja la precarguía en background
 *    └─ Compatible con SweetAlert2
 *    └─ Cache en memoria
 * 
 * 📄 index.blade.php (MODIFICADO)
 *    └─ Agregado: <script src="prenda-editor-preloader.js"></script>
 *    └─ Modificado: editarPedido() usa PrendaEditorPreloader.loadWithLoader()
 * 
 * 📄 prenda-editor-loader.js (SIN CAMBIOS)
 *    └─ Sigue cargando módulos bajo demanda
 *    └─ Ahora se beneficia de la precarguía
 * 
 * ============================================================================
 * CAMBIOS EN index.blade.php
 * ============================================================================
 * 
 * ✅ AGREGADO al @push('scripts'):
 *    <script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>
 *    (ANTES del prenda-editor-loader.js)
 * 
 * ✅ MODIFICADO en DOMContentLoaded:
 *    if (window.PrendaEditorPreloader) {
 *        window.PrendaEditorPreloader.start();
 *    }
 * 
 * ✅ MODIFICADO en editarPedido():
 *    await window.PrendaEditorPreloader.loadWithLoader({...})
 *    (en lugar de PrendaEditorLoader.load())
 * 
 * ============================================================================
 * BENCHMARKS ESPERADOS
 * ============================================================================
 * 
 * ESCENARIO 1: Primera carga (sin precarga)
 *    ├─ Tiempo total: ~4.4s (igual que antes)
 *    └─ Razón: Sin precarga anterior disponible
 * 
 * ESCENARIO 2: Segunda carga (con precarga en background)
 *    ├─ Tiempo total: ~600ms ✅ (85% más rápido!)
 *    ├─ Desglose:
 *    │  └─ Fetch datos: ~590ms
 *    │  └─ Render modal: ~10ms
 *    │  └─ Módulos: ~0ms (YA CARGADOS)
 *    └─ El usuario ve el modal abrirse casi instantáneamente
 * 
 * ============================================================================
 * VARIABLES DE CONTROL Y DEBUG
 * ============================================================================
 * 
 * En consola:
 *    window.PrendaEditorPreloader.getStatus()
 *    → Retorna estado actual: si está precargado, precargando, tiempo, etc.
 * 
 * Eventos personalizados (puedes escuchar):
 *    window.addEventListener('prendaEditorPreloaded', (e) => {
 *        console.log('¡Precarga completada en', e.detail.elapsed, 'ms');
 *    });
 * 
 *    window.addEventListener('prendaEditorPreloadError', (e) => {
 *        console.error('Error en precarga:', e.detail.error);
 *    });
 * 
 * ============================================================================
 * MANEJO DE ERRORES Y EDGE CASES
 * ============================================================================
 * 
 * ✅ Conexión lenta: requestIdleCallback toma más tiempo, pero NO bloquea
 * ✅ Usuario hace clic antes de terminar: loadWithLoader() espera con spinner
 * ✅ Error en precarga: Falls back a carga normal
 * ✅ Navegador sin requestIdleCallback: Usa setTimeout como fallback
 * ✅ Múltiples clics: edicionEnProgreso previene race conditions
 * 
 * ============================================================================
 * CÓMO VERIFICAR QUE FUNCIONA
 * ============================================================================
 * 
 * 1. Abre DevTools → Console
 * 2. Carga la página → verás "[PrendaEditorPreloader] 🔄 Precarguía iniciada..."
 * 3. Ejecuta en consola:
 *    window.PrendaEditorPreloader.getStatus()
 *    → Verás: { isPreloading: true, isPreloaded: false, ... }
 * 
 * 4. Espera 5-6 segundos → verás "✅ Precarguía completada"
 * 5. Haz clic en "Editar" → Modal abre casi al instante
 * 6. Ejecuta nuevamente:
 *    window.PrendaEditorPreloader.getStatus()
 *    → Verás: { isPreloading: false, isPreloaded: true, ... }
 * 
 * ============================================================================
 * OPTIMIZACIONES FUTURAS
 * ============================================================================
 * 
 * □ Cachear scripts en localStorage (persistencia entre navegación)
 * □ Service Worker para pre-cache de assets
 * □ Predicción: precargar solo si usuario abre lista de pedidos
 * □ Segmentación: diferentes módulos para diferentes usuarios
 * □ Preload hints: <link rel="preload"> en HTML
 * 
 * ============================================================================
 * PREGUNTAS FRECUENTES
 * ============================================================================
 * 
 * P: ¿Por qué tarda 2 segundos antes de empezar la precarga?
 * R: Para dejar que la página render completamente. Si se hace inmediato,
 *    puede ralentizar el pintado inicial de la página.
 * 
 * P: ¿Consume datos si el usuario nunca abre edición?
 * R: Sí, ~120-150KB. Es el trade-off por la velocidad después.
 *    Si necesitas evitarlo, desactiva PrendaEditorPreloader.start()
 * 
 * P: ¿Funciona offline?
 * R: No, pero tampoco necesita. Sin conexión, la carga de módulos fallaría de todas formas.
 * 
 * P: ¿Interfiere con otras funciones?
 * R: No. Es completamente aislado y no afecta al DOM hasta que se necesita.
 * 
 * ============================================================================
 */
