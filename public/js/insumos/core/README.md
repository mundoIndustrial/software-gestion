# 🏗️ Arquitectura DDD Híbrida - Módulo Insumos

## Visión General

Este directorio contiene la implementación de **Domain-Driven Design (DDD)** siguiendo un enfoque **Híbrido** - balanceado entre arquitectura limpia y pragmatismo.

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTACIÓN LAYER                        │ 
│  (index.blade.php, index-blade-handlers.js, modales HTML)   │
│         Consumidor: window.insumoService                     │
└────────────────────┬────────────────────────────────────────┘
                     │ Inyecta
                     ↓
┌─────────────────────────────────────────────────────────────┐
│                  APPLICATION LAYER                           │
│               InsumoService.js (Business Logic)              │
│  - Validaciones  - Reglas de negocio  - Orquestación       │
└────────────────────┬────────────────────────────────────────┘
                     │ Usa
                     ↓
┌─────────────────────────────────────────────────────────────┐
│                    DOMAIN LAYER                              │
│             InsumoRepository.js (Abstract Interface)         │
│        Contrato: métodos que toda implementación debe tener  │
└────────────────────┬────────────────────────────────────────┘
                     │ Implementado por
                     ↓
┌─────────────────────────────────────────────────────────────┐
│                INFRASTRUCTURE LAYER                          │
│                                                              │
│  ┌──────────────────────┐      ┌──────────────────────────┐ │
│  │   HttpClient.js      │      │ SessionStorageInsumo     │ │
│  │    (HTTP Abstract)   │────→ │   Repository.js          │ │
│  │                      │      │  (Concrete Storage)      │ │
│  │ • Retry logic        │      │                          │ │
│  │ • Timeout handling   │      │ • Cache-first strategy   │ │
│  │ • Error typing       │      │ • 30min TTL              │ │
│  │                      │      │ • Session-based          │ │
│  └──────────────────────┘      └──────────────────────────┘ │
└────────────────────┬────────────────────────────────────────┘
                     │ Accesa
                     ↓
        Backend API: /insumos/api/materiales
```

---

## Estructura de Archivos

```
core/
├── bootstrap.js                          ← ⚡ PUNTO DE ENTRADA
│   └─ Inicializa todas las capas
│   └─ Inyecta dependencias
│   └─ Expone window.insumoService
│
├── domain/
│   └─ InsumoRepository.js
│      └─ Abstract interface
│      └─ Define contrato
│      └─ Sin implementación
│
├── infrastructure/
│   ├── HttpClient.js
│   │   └─ Abstracción HTTP
│   │   └─ Retry logic (3 intentos)
│   │   └─ Timeout 10s (configurable)
│   │   └─ Detección de errores
│   │
│   └── SessionStorageInsumoRepository.js
│       └─ Implementa InsumoRepository
│       └─ Usa HttpClient
│       └─ Cache-first strategy
│       └─ 30 minutos TTL
│       └─ Garbage collection auto
│
├── application/
│   └─ InsumoService.js
│      └─ Lógica de negocio
│      └─ Validaciones
│      └─ Orquestación
│      └─ Errores tipados
│
└── docs/
    ├── INTEGRACION.md      ← Cómo integrar en blade
    ├── API_REFERENCE.md    ← API de InsumoService
    └── ARQUITECTURA.md     ← Este archivo
```

---

## Conceptos Clave

### 1. **Repository Pattern**
El patron de repository abstrae el acceso a datos:

```javascript
// Domain layer: Define el contrato
class InsumoRepository {
  async obtenerInsumos(pedidoId, prendaId) {
    throw new Error('Must be implemented');
  }
}

// Infrastructure layer: Implementa concretamente
class SessionStorageInsumoRepository extends InsumoRepository {
  async obtenerInsumos(pedidoId, prendaId) {
    // Lógica real: cache + HTTP
  }
}

// Application layer: Usa sin acoplamiento
class InsumoService {
  constructor(repository) {
    this.repository = repository; // Inyectado
  }
  
  async obtenerInsumosDelPedido(pedidoId, prendaId) {
    return await this.repository.obtenerInsumos(pedidoId, prendaId);
  }
}
```

### 2. **Dependency Injection**
Las dependencias se inyectan, no se crean internamente:

```javascript
// ❌ MAL - Acoplado
class InsumoService {
  async obtenerInsumos() {
    const repo = new SessionStorageInsumoRepository();
    return await repo.obtenerInsumos(...);
  }
}

// ✅ BIEN - Inyectado
class InsumoService {
  constructor(repository) {
    this.repository = repository;
  }
  
  async obtenerInsumos() {
    return await this.repository.obtenerInsumos(...);
  }
}
```

### 3. **Layered Architecture**
Cada capa tiene responsabilidad única y clara:

| Capa | Responsabilidad | Sin acoplamiento con |
|------|-----------------|---------------------|
| **Presentation** | Interfaz HTML, manejo de DOM | HTTP, Storage, Lógica de negocio |
| **Application** | Lógica de negocio, validación | HTTP, Storage, UI |
| **Domain** | Interfaz de contratación | Detalles de implementación |
| **Infrastructure** | HTTP, Storage, APIs externas | Lógica de negocio |

---

## Flujo de Datos

### Obtener Insumos (Cache-first)

```
Usuario hace clic
      ↓
abrirModalInsumos(pedido, prenda)
      ↓
window.insumoService.obtenerInsumosDelPedido(pedido, prenda)
      ↓
InsumoService.obtenerInsumosDelPedido()
  • Valida parámetros
  • Enriquece datos
      ↓
SessionStorageInsumoRepository.obtenerInsumos()
  
  1️⃣  Busca en sessionStorage (cache)
      [HIT] ✅ Retorna data cached (< 1ms)
      
  2️⃣  Si no está o expiró, hace HTTP GET
      ↓
      HttpClient.get('/insumos/api/materiales/{pedido}')
        • Timeout: 10 segundos (AbortController)
        • Error: Reintentar hasta 3 veces si retryable
        • Status 5xx: Retryable
        • Timeout/Network: Retryable
        • Status 4xx: NO retryable
      
  3️⃣  Guarda respuesta en sessionStorage
      [CACHED] Próxima llamada en 30 min será < 1ms
      
      ↓
Retorna data a handler
      ↓
llenarTablaInsumos(data.materiales)
      ↓
Modal visible con datos
```

### Guardar Insumos

```
Usuario hace clic "Guardar"
      ↓
guardarCambiosInsumos()
      ↓
window.insumoService.guardarCambiosInsumos(pedido, prenda, materiales)
      ↓
InsumoService.guardarCambiosInsumos()
  • Valida pedidoId, prendaId numéricos
  • Valida array no vacío
  • Valida cada material:
    - Tiene nombre_material
    - Fechas lógicamente válidas
  • Si ERROR: Lanza ValidationError o BusinessError
      ↓
SessionStorageInsumoRepository.guardarInsumos()
  
  1️⃣  POST a servidor
      ↓
      HttpClient.post('/insumos/api/materiales', {materiales})
        • Cuerpo: JSON
        • Timeout: 10 segundos
        • Reintenta si error retryable
      
  2️⃣  Si éxito: Invalida caché
      sessionStorage.removeItem('insumos_' + pedido)
      
  3️⃣  Retorna respuesta del servidor
      
      ↓
Mensaje "Guardado" al usuario
      ↓
Opcionalmente: recargarTabla()
```

---

## Error Handling

### Validación (Application Layer)

```javascript
// ❌ User passes invalid data
window.insumoService.obtenerInsumosDelPedido("abc"); // Not a number

// InsumoService throws:
// ValidationError: "pedidoId debe ser un número válido"

// Handler:
try {
  const datos = await window.insumoService.obtenerInsumosDelPedido(id);
} catch (error) {
  if (error instanceof ValidationError) {
    showToast('Check your input: ' + error.message);
  }
}
```

### Business Rules (Application Layer)

```javascript
// ❌ Business rule violation
window.insumoService.guardarCambiosInsumos(123, 456, []); // Empty array

// InsumoService throws:
// BusinessError: "Debe haber al menos un material"

// Handler:
try {
  await window.insumoService.guardarCambiosInsumos(pedido, prenda, materiales);
} catch (error) {
  if (error instanceof BusinessError) {
    showToast('Cannot proceed: ' + error.message);
  }
}
```

### Network Errors (Infrastructure Layer)

```javascript
// ❌ Network timeout
// HttpClient auto-retries 3 times
// If still fails:

try {
  const datos = await window.insumoService.obtenerInsumosDelPedido(123);
} catch (error) {
  if (error instanceof HttpError) {
    console.error(`HTTP ${error.status}: ${error.statusText}`);
    showToast('Network error - retried 3 times');
  }
}
```

### Storage Errors (Infrastructure Layer)

```javascript
// ❌ sessionStorage quota exceeded
// SessionStorageRepository auto-cleanup (clearExpired)
// If still fails:

try {
  await window.insumoService.guardarCambiosInsumos(123, 456, materiales);
} catch (error) {
  if (error instanceof RepositoryError) {
    console.error('Cache issue:', error.originalError);
    showToast('Storage issue - retried clearing old data');
  }
}
```

---

## Testing

### Mockear HttpClient

```javascript
class TestHttpClient extends HttpClient {
  async get(path) {
    if (path.includes('materiales/123')) {
      return {
        nombre_prenda: 'Prenda Test',
        materiales: [
          { nombre_material: 'Test Material' }
        ]
      };
    }
    throw new Error('Unknown path');
  }
}

const repo = new SessionStorageInsumoRepository(new TestHttpClient());
const service = new InsumoService(repo);

// Test sin servidor
const resultado = await service.obtenerInsumosDelPedido(123);
console.assert(resultado.totalMateriales === 1);
```

### Mockear Repository

```javascript
class TestRepository extends InsumoRepository {
  async obtenerInsumos(pedidoId, prendaId) {
    return {
      nombre_prenda: 'Test',
      materiales: []
    };
  }
  
  async guardarInsumos(pedidoId, prendaId, datos) {
    return true;
  }
  
  async existeEnCache(pedidoId, prendaId) {
    return false;
  }
  
  async limpiar(pedidoId) {}
}

const service = new InsumoService(new TestRepository());
// Prueba InsumoService sin Repository real
```

---

## Performance

### Cache Benefits

```
Sin caché:
  User 1 abre modal → HTTP GET (100ms)
  User 2 abre modal → HTTP GET (100ms)
  User 3 abre modal → HTTP GET (100ms)
  ────────────────────────────────
  Total: 300ms

Con caché (30 min):
  User 1 abre modal → HTTP GET (100ms) → Cache
  User 2 abre modal → Cache (1ms) ✅ 100x faster
  User 3 abre modal → Cache (1ms) ✅ 100x faster
  ────────────────────────────────
  Total: 102ms
```

### Network Resilience

```
Scenario: User on slow 3G connection
  First attempt: Timeout (10s)
  Retry 1: Timeout (10s)
  Retry 2: Success (8s)
  
  Without retry: FAIL after 10s
  With retry: SUCCESS after 28s (best effort)
```

---

## Migration Checklist

- [ ] Add core imports to blade layout (PASO 1)
- [ ] Refactor abrirModalInsumos (PASO 2)
- [ ] Refactor guardarCambiosInsumos (PASO 3)
- [ ] Handle errors with try/catch
- [ ] Test cache in DevTools
- [ ] Test offline/slow network
- [ ] Monitor console logs ([InsumoService], [HttpClient], etc)
- [ ] Verify window.insumoService available
- [ ] Remove old fetch() calls from handlers

---

## Configuration

```javascript
// En bootstrap.js (línea ~90)
new CoreBootstrap({
  httpTimeout: 10000,           // Milliseconds
  cacheExpiry: 30 * 60 * 1000,  // Milliseconds
  retryAttempts: 3              // Number of retries
}).boot();
```

---

## Logging

Todos los módulos loguean con prefijos para fácil debuggeo:

```javascript
// En DevTools Console, puedes filtrar:
// [InsumoService]           → Llamadas al servicio
// [SessionStorageInsumo...] → Operaciones de caché
// [HttpClient]              → Peticiones HTTP
// [CoreBootstrap]           → Inicialización
```

**Ejemplo de logs esperados:**

```
[CoreBootstrap] Iniciando arquitectura DDD...
[CoreBootstrap] ✓ HttpClient inicializado
[CoreBootstrap] ✓ SessionStorageInsumoRepository inicializado
[CoreBootstrap] ✓ InsumoService inicializado
[CoreBootstrap] ✓ Arquitectura DDD lista

(User opens modal)
[InsumoService] Obteniendo insumos del pedido 123
[SessionStorageInsumoRepository] Buscando en caché: insumos_123_null
[HttpClient] GET /insumos/api/materiales/123
[HttpClient] Respuesta 200 OK
[SessionStorageInsumoRepository] Data cached for 30 minutes
```

---

## Decisiones de Arquitectura

### ✅ Por qué Hybrid DDD

**Ventajas:**
- Separación de capas clara sin over-engineering
- Fácil de testear y mockear
- Flexible: cambiar storage (IndexedDB en lugar de sessionStorage)
- Escalable: agregar más services sin tocar existentes

**Trade-offs:**
- No es "pure" DDD (no value objects, agregados simples)
- Servicios pueden crecer (solución: partir en sub-servicios)
- Más archivos que código monolítico (pero más mantenible)

### ✅ Por qué sessionStorage para caché

**Alternativas consideradas:**
- LocalStorage: Persiste entre sesiones (no queremos en Insumos)
- IndexedDB: Complejo, overkill para datos simples
- Memory (variable global): Se pierde en recarga
- **sessionStorage**: Perfecto balance (sesión, simple API, largo plazo)

### ✅ Por qué 3 reintentos

```
1st try:   Network error → Retry
2nd try:   Network error → Retry  
3rd try:   Network error → Retry
4th try:   Fails permanently (too many retries)

Ideal para: Conexión inestable (3G, WiFi débil)
Evita: Spam de requests en servidor caído
```

### ✅ Por qué 10 segundos de timeout

```
Rápido (< 5s):     User perceive it as instant
Normal (5-10s):    Noticeable pero aceptable
Lento (> 10s):     Frustra al usuario
```

---

## Recursos Adicionales

- **INTEGRACION.md** - Pasos para integrar en blade
- **API_REFERENCE.md** - Referencia rápida de métodos
- **InsumoService.js** - Comentarios de implementación
- **HttpClient.js** - Detalle de retry/timeout logic
- **SessionStorageInsumoRepository.js** - Cache management

---

## FAQ

**P: ¿Qué pasa si guardo múltiples veces rápido?**
R: InsumoService valida cada vez. HttpClient tolera múltiples requests. Cache se invalida en la primera llamada exitosa.

**P: ¿Cómo sé si estoy usando caché o HTTP?**
R: Abre DevTools Console, busca logs con [SessionStorageInsumo...] y [HttpClient]. Cache = sin logs de HTTP.

**P: ¿Puedo cambiar el tiempo de caché?**
R: Sí, en bootstrap.js `cacheExpiry: 60 * 60 * 1000` (60 minutos).

**P: ¿Qué si sessionStorage da quota exceeded?**
R: SessionStorageRepository auto-limpia items expirados. Si sigue fallando, lanza RepositoryError.

**P: ¿Cómo testeo offline?**
R: DevTools → Network → Offline, luego intenta una acción. Debería ver reintentos en console.

---

## Contacto / Preguntas

Ver INTEGRACION.md o API_REFERENCE.md para ejemplos específicos.
