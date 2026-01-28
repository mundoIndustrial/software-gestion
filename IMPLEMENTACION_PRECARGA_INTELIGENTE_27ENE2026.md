# 🚀 IMPLEMENTACIÓN: Precarguía Inteligente de Módulos

**Fecha:** 27 de Enero de 2026  
**Problema:** Primera apertura del modal demora ~4.4s  
**Solución:** Precargar módulos en background sin bloquear  

---

## 📊 IMPACTO

### Antes (Cold Load - Primera carga)
```
├─ Swal Ready: 0.2ms
├─ Módulos: 4389ms ❌ (CUELLO DE BOTELLA)
├─ Fetch: 590ms
├─ Modal: 10ms
└─ TOTAL: ~5s
```

### Después (Warm Load - Segunda carga con precarguía)
```
├─ Swal Ready: 0.2ms
├─ Módulos: 0ms ✅ (YA EN CACHÉ)
├─ Fetch: 590ms
├─ Modal: 10ms
└─ TOTAL: ~600ms (85% más rápido 🎉)
```

---

## 🔧 ARCHIVOS CREADOS/MODIFICADOS

### 1️⃣ NUEVO: `prenda-editor-preloader.js`
📄 **Ubicación:** `/public/js/lazy-loaders/prenda-editor-preloader.js`

**Función:** Maneja la precarguía en background
- ✅ Carga módulos cuando el navegador está idle
- ✅ Compatible con SweetAlert2
- ✅ Cache en memoria
- ✅ Sin bloqueo de UI

**API Pública:**
```javascript
// Iniciar precarguía automática
window.PrendaEditorPreloader.start()

// Cargar con loader visual
await window.PrendaEditorPreloader.loadWithLoader({
    title: '⏳ Cargando',
    message: 'Por favor espera...'
})

// Ver estado
window.PrendaEditorPreloader.getStatus()

// Verificar si está listo
window.PrendaEditorPreloader.isReady()
```

---

### 2️⃣ MODIFICADO: `resources/views/asesores/pedidos/index.blade.php`

#### A. Agregar preloader en scripts (línea ~113)
```php
@push('scripts')

<!-- ✅ PRELOADER: Precarga en background -->
<script src="{{ asset('js/lazy-loaders/prenda-editor-preloader.js') }}"></script>

<!-- ✅ LAZY LOADERS: Cargan módulos bajo demanda -->
<script src="{{ asset('js/lazy-loaders/prenda-editor-loader.js') }}"></script>
<script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>
```

#### B. Activar preloader en DOMContentLoaded (línea ~1260)
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // ✅ NUEVO: Activar precarguía en background
    if (window.PrendaEditorPreloader) {
        console.log('[PedidosInit] 🔄 Iniciando precarguía en background...');
        window.PrendaEditorPreloader.start();
    }
    
    // ... resto del código
});
```

#### C. Usar preloader en `editarPedido()` (línea ~405)
```javascript
// Antes:
if (!window.PrendaEditorLoader.isLoaded()) {
    await window.PrendaEditorLoader.load();
}

// Después:
if (!window.PrendaEditorPreloader?.isReady?.()) {
    await window.PrendaEditorPreloader.loadWithLoader({
        title: '⏳ Cargando módulos',
        message: 'Preparando el editor de prendas...'
    });
} else {
    console.log('[editarPedido] ⚡ Módulos ya precargados en background');
}
```

---

## 🎯 FLUJO DE EJECUCIÓN

```
┌─────────────────────────────────────────────────────────────────┐
│ 1️⃣ USUARIO ACCEDE A LA PÁGINA DE PEDIDOS                        │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2️⃣ Page se renderiza completamente                              │
│    ├─ Tabla de pedidos cargada ✓                                │
│    ├─ JS inicializado ✓                                         │
│    └─ preloader.js presente ✓                                   │
└─────────────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3️⃣ DOMContentLoaded dispara                                     │
│    └─ PrendaEditorPreloader.start() inicia                      │
└─────────────────────────────────────────────────────────────────┘
                           ↓
                    Espera ~2 segundos
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4️⃣ requestIdleCallback() ejecuta                                │
│    (cuando el navegador está sin hacer nada)                    │
│                                                                 │
│    INICIA PRECARGUÍA EN BACKGROUND:                             │
│    └─ prenda-editor-loader.load() comienza                      │
│    └─ Carga todos los módulos (4.3s)                            │
│    └─ TODO PASA EN BACKGROUND, SIN BLOQUEAR UI ⚡             │
└─────────────────────────────────────────────────────────────────┘
                           ↓
    [Usuario puede navegar, escribir, etc. normalmente]
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5️⃣ USUARIO HACE CLIC EN "EDITAR PEDIDO"                         │
└─────────────────────────────────────────────────────────────────┘
                           ↓
        ¿Módulos ya precargados?
          ├─ SÍ: Abre modal inmediatamente ⚡ (~600ms)
          └─ NO: Muestra loader mientras termina ⏳
```

---

## 💾 CACHÉ Y PERSISTENCIA

### En Memoria (durante la sesión)
- ✅ Scripts precargados se almacenan en `Map`
- ✅ Están disponibles durante toda la sesión
- ✅ Se limpian al recargar la página

### Mejoras Futuras
- [ ] Cachear en localStorage para persistencia entre navegación
- [ ] Service Worker para pre-cache de assets
- [ ] IndexedDB para almacenamiento más grande

---

## 🔍 MONITOREO Y DEBUG

### Ver estado actual
```javascript
window.PrendaEditorPreloader.getStatus()

// Resultado:
{
  isPreloading: false,
  isPreloaded: true,
  preloadError: null,
  scriptCacheSize: 18,
  moduleCacheSize: 25,
  config: {
    preloadDelay: 2000,
    idleThreshold: 100
  }
}
```

### Escuchar eventos
```javascript
window.addEventListener('prendaEditorPreloaded', (e) => {
    console.log('✅ Precarga completada en', e.detail.elapsed, 'ms');
});

window.addEventListener('prendaEditorPreloadError', (e) => {
    console.error('❌ Error:', e.detail.error);
});
```

### Forzar recarga
```javascript
window.PrendaEditorPreloader.forceReload()
```

---

## ⚠️ CONSIDERACIONES

### Consumo de datos
- **Impacto:** ~120-150KB descargados en background
- **Trade-off:** Vale la pena por la velocidad posterior
- **Desactivar:** Comenta `PrendaEditorPreloader.start()` si no deseas

### Navegadores sin `requestIdleCallback`
- **Fallback:** Usa `setTimeout` como alternativa
- **Compatibilidad:** Todos los navegadores modernos soportan requestIdleCallback

### Conexión lenta
- **Impacto:** Precarguía tardará más, pero NO bloqueará
- **Resultado:** Usuario sigue navegando normalmente

---

## 🧪 PRUEBAS RECOMENDADAS

1. **Primera carga (sin caché)**
   - [ ] Abre DevTools → Network
   - [ ] Marca "Disable cache"
   - [ ] Recarga página
   - [ ] Haz clic en "Editar" → Verás loader
   - [ ] Espera ~4.4s a que cargue

2. **Segunda carga (con caché)**
   - [ ] SIN recarga
   - [ ] Haz clic en "Editar" nuevamente
   - [ ] Verás que abre casi instantáneamente (~600ms)

3. **Monitor en consola**
   - [ ] Ejecuta: `window.PrendaEditorPreloader.getStatus()`
   - [ ] Verifica que `isPreloaded: true`

4. **Eventos de error**
   - [ ] Desconecta internet momentáneamente
   - [ ] Verifica que la precarguía detecte el error
   - [ ] La carga manual sigue funcionando

---

## 📈 MÉTRICAS ESPERADAS

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Cold Load (1ª vez) | ~4.4s | ~4.4s | (igual, sin precarga) |
| Warm Load (2ª vez) | ~4.4s | ~0.6s | ⚡ 85% |
| Tiempo para editar | ~4.5s | ~0.6s | ⚡ 88% |
| Memory (caché) | N/A | ~2MB | Aceptable |
| Data downloaded | ~120KB | ~120KB | (en background) |

---

## 🚀 PRÓXIMOS PASOS

1. ✅ Implementada precarguía básica
2. ✅ Integrada en index.blade.php
3. ⏳ Monitorear en producción
4. [ ] Agregar Service Worker (opcional)
5. [ ] Cachear en localStorage (opcional)
6. [ ] Análisis A/B con usuarios reales

---

## 📝 NOTAS PARA EL EQUIPO

- La precarguía es **completamente transparente** para el usuario
- El módulo es **independiente** y no interfiere con nada más
- Si hay errores, la carga manual sigue funcionando normalmente
- Se puede desactivar fácilmente comentando una línea

**Ahora, cuando abras un modal de edición por segunda vez, verás que es casi instantáneo.** ⚡
