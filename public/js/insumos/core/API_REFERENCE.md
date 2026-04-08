# InsumoService - API Reference

## Global Access
```javascript
// Automáticamente inyectado por bootstrap.js después de DOMContentLoaded
window.insumoService
window.coreServices  // {insumoService, insumoRepository, httpClient}
```

## Method: obtenerInsumosDelPedido()

**Signature**
```javascript
obtenerInsumosDelPedido(pedidoId, prendaId = null) → Promise<InsumosData>
```

**Parameters**
- `pedidoId` (number, required): Order/pedido ID
- `prendaId` (number|null, optional): Prenda ID to filter

**Returns**
```javascript
{
  nombre_prenda: string,
  materiales: Array<{
    nombre_material: string,
    recibido: boolean,
    fecha_pedido: string,
    fecha_llegada: string,
    ...
  }>,
  // Business logic enrichment:
  totalMateriales: number,
  materialesRecibidos: number,
  requiereCierre: boolean
}
```

**Throws**
- `ValidationError`: if pedidoId is invalid (not a number)
- `BusinessError`: if server response malformed
- `HttpError`: if network/server error (status, statusText, response)
- `RepositoryError`: if sessionStorage corrupted/full

**Examples**
```javascript
// Load all insumos for order 123
try {
  const insumos = await window.insumoService.obtenerInsumosDelPedido(123);
  console.log(`Found ${insumos.totalMateriales} materials`);
  if (insumos.requiereCierre) showBtnCerrar();
} catch (error) {
  console.error('Failed to load:', error);
}

// Load insumos filtered by prenda
const insumosForPrenda = await window.insumoService.obtenerInsumosDelPedido(123, 456);
```

---

## Method: guardarCambiosInsumos()

**Signature**
```javascript
guardarCambiosInsumos(pedidoId, prendaId, materiales) → Promise<boolean>
```

**Parameters**
- `pedidoId` (number): Order ID
- `prendaId` (number): Prenda ID  
- `materiales` (Array): Material objects to save
  - Each must have `nombre_material` (string)
  - Each may have `fecha_pedido`, `fecha_llegada` (validated)

**Validation Rules**
- pedidoId, prendaId must be valid numbers
- materiales must be non-empty array
- Each material must have `nombre_material`
- fecha_llegada cannot be before fecha_pedido

**Returns**
- `true` if saved successfully to cache + server
- Throws error if validation fails

**Throws**
- `ValidationError`: missing pedidoId/prendaId, empty array, missing nombre_material
- `BusinessError`: date invalid, business rule violated
- `HttpError`: network/server error
- `RepositoryError`: cache write failed

**Side Effects**
-  POSTs to `/insumos/api/materiales`
-  Auto-invalidates cache for this pedido
-  No manual cache management needed

**Examples**
```javascript
const materiales = [
  { nombre_material: 'Algodón', recibido: true },
  { nombre_material: 'Botones', recibido: false, fecha_llegada: '2024-01-15' }
];

try {
  const saved = await window.insumoService.guardarCambiosInsumos(123, 456, materiales);
  if (saved) {
    showToast('Changes saved!', 'success');
  }
} catch (error) {
  if (error.name === 'ValidationError') {
    showToast('Invalid data: ' + error.message, 'error');
  }
}
```

---

## Method: tieneDataEnCache()

**Signature**
```javascript
tieneDataEnCache(pedidoId, prendaId = null) → Promise<boolean>
```

**Parameters**
- `pedidoId` (number): Order ID
- `prendaId` (number|null, optional): Prenda ID

**Returns**
- `true` if valid cached data exists (not expired)
- `false` if cache missing or expired

**Use Cases**
- Show cached badge/indicator
- Decide whether to reload
- Skip network request if data recent

**Examples**
```javascript
// Check if we can show data immediately
const hasCache = await window.insumoService.tieneDataEnCache(123);
if (hasCache) {
  showCachedBadge();
} else {
  showLoadingSpinner();
}

// Load with smart caching
if (!await window.insumoService.tieneDataEnCache(123)) {
  await window.insumoService.obtenerInsumosDelPedido(123);
}
```

---

## Method: limpiarCache()

**Signature**
```javascript
limpiarCache(pedidoId = null) → Promise<void>
```

**Parameters**
- `pedidoId` (number|null, optional)
  - If provided: clear cache for that specific pedido only
  - If null: clear ALL insumos cache

**Returns**
- Promise that resolves when cleared

**Use Cases**
- After saving changes (already done automatically)
- Manual refresh button click
- On data inconsistency
- Testing/debugging

**Examples**
```javascript
// Clear specific pedido cache
await window.insumoService.limpiarCache(123);

// Clear all insumos cache  
await window.insumoService.limpiarCache();

// With user feedback
try {
  await window.insumoService.limpiarCache();
  showToast('Cache cleared', 'info');
} catch (error) {
  console.error('Clear failed:', error);
}
```

---

## Error Classes

### ValidationError
```javascript
try {
  await service.obtenerInsumosDelPedido('invalid');
} catch (error) {
  if (error instanceof ValidationError) {
    console.log(error.message); // "pedidoId debe ser un número válido"
    // Show to user: input validation failed
  }
}
```

### BusinessError
```javascript
try {
  await service.guardarCambiosInsumos(123, 456, []); // empty array
} catch (error) {
  if (error instanceof BusinessError) {
    console.log(error.message); // "Debe haber al menos un material"
    // Show to user: business rule violated
  }
}
```

### HttpError (from HttpClient)
```javascript
try {
  await service.obtenerInsumosDelPedido(123);
} catch (error) {
  if (error instanceof HttpError) {
    console.log(`HTTP ${error.status}: ${error.statusText}`);
    console.log(error.response); // parsed response from server
    // Network/server error, will retry automatically up to 3 times
  }
}
```

### RepositoryError (from SessionStorageRepository)
```javascript
try {
  await service.guardarCambiosInsumos(123, 456, [...]);
} catch (error) {
  if (error instanceof RepositoryError) {
    console.log(error.originalError); // root cause
    // Storage failed (quota exceeded, corrupted data, etc)
  }
}
```

---

## Complete Usage Example

```javascript
// Initialize (automatic, but can be called manually)
const services = new CoreBootstrap().boot();
const insumoService = services.insumoService;

// Open modal with insumos
async function abrirModalInsumos(pedido, prendaId) {
  try {
    // Show loading state
    document.getElementById('insumosModal').classList.add('loading');
    
    // Fetch insumos (with cache-first strategy)
    const insumos = await insumoService.obtenerInsumosDelPedido(pedido, prendaId);
    
    // Update UI
    document.getElementById('modalPedido').textContent = pedido;
    document.getElementById('modalPrendaNombre').textContent = insumos.nombre_prenda;
    llenarTablaInsumos(insumos.materiales);
    
    // Show appropriate buttons based on business logic
    if (insumos.requiereCierre) {
      document.getElementById('btnCerrar').style.display = 'block';
    }
    
    // Show modal
    document.getElementById('insumosModal').style.display = 'flex';
    
  } catch (error) {
    if (error instanceof ValidationError) {
      showToast('Invalid order number', 'error');
    } else if (error instanceof BusinessError) {
      showToast('Cannot load: ' + error.message, 'error');
    } else if (error instanceof HttpError) {
      showToast('Server error, retried 3 times', 'error');
      console.error('HTTP Error:', error.status);
    } else {
      showToast('Unknown error', 'error');
      console.error('Error:', error);
    }
  } finally {
    document.getElementById('insumosModal').classList.remove('loading');
  }
}

// Save changes
async function guardarCambiosInsumos() {
  try {
    const pedido = document.getElementById('modalPedido').textContent;
    const prendaId = document.getElementById('modalPrendaId').value;
    const materiales = obtenerDatosFilasTabla();
    
    // Validate before sending
    const guardado = await insumoService.guardarCambiosInsumos(pedido, prendaId, materiales);
    
    if (guardado) {
      showToast('Changes saved successfully!', 'success');
      cerrarModalInsumos();
      recargarTablaRecibos();
    }
    
  } catch (error) {
    showToast('Error: ' + error.message, 'error');
    console.error('Save failed:', error);
  }
}

// Refresh with cache clear
async function refrescarInsumos() {
  const pedido = document.getElementById('modalPedido').textContent;
  await insumoService.limpiarCache(pedido);
  await abrirModalInsumos(pedido);
}
```

---

## Configuration Options

**Default CoreBootstrap Config**
```javascript
{
  httpTimeout: 10000,           // 10 seconds
  cacheExpiry: 1800000,         // 30 minutes
  retryAttempts: 3              // 3 retries for network errors
}
```

**Custom Config (before DOMContentLoaded)**
```javascript
window.coreBootstrapConfig = {
  httpTimeout: 15000,
  cacheExpiry: 60 * 60 * 1000,  // 1 hour
  retryAttempts: 5
};

// Then let bootstrap.js load and use this config
```

**Accessing Current Config**
```javascript
const config = window.coreBootstrap.getConfig();
console.log(config.cacheExpiry); // 1800000
```

---

## Performance Monitoring

**Check Cache Hit Rate**
```javascript
// In DevTools console
const storage = sessionStorage;
const keys = Object.keys(storage).filter(k => k.startsWith('insumos_'));
console.log(`Cached items: ${keys.length}`);
keys.forEach(k => console.log(k, storage.getItem(k)));
```

**Check HTTP Retries**
```javascript
// HttpClient logs retries to console with [HttpClient] prefix
// Open DevTools Console to see retry attempts
```

**Monitor Service Calls**
```javascript
// Services log with prefixes:
// [InsumoService] - service calls
// [SessionStorageInsumoRepository] - cache operations  
// [HttpClient] - HTTP requests
```

---

## Testing

**Mock HTTP Client**
```javascript
class MockHttpClient extends HttpClient {
  async get(path) {
    return {
      materiales: [
        { nombre_material: 'Testing Material' }
      ]
    };
  }
}

// Use in tests
const mockRepo = new SessionStorageInsumoRepository(new MockHttpClient());
const service = new InsumoService(mockRepo);
```

**Disable Cache for Testing**
```javascript
// Clear sessionStorage before test
sessionStorage.clear();

// Or disable via config
const bootstrap = new CoreBootstrap({ cacheExpiry: 0 });
```
