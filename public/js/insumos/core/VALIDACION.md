# 🧪 VALIDACIÓN POST-INTEGRACIÓN

## Estado: Comenzando integración de arquitectura DDD

###  Cambios Realizados

**1. Layout (resources/views/layouts/insumos/app.blade.php)**
-  Agregados 5 scripts de core en orden correcto
-  Mantiene @stack('scripts') para compatibilidad

**2. Handlers (public/js/insumos/index-blade-handlers.js)**
-  Refactorizado abrirModalInsumos() → usa window.insumoService
-  Manejo de errores tipados (ValidationError, BusinessError, HttpError)
-  Cache automático y reintentos durante carga

**3. Form Handlers (public/js/insumos/form-handlers-insumos.js)**
-  Refactorizado guardarCambios() → usa window.insumoService
-  Validación centralizada en servicio
-  Manejo de errores mejorado

### ⏳ Pasos Siguientes (Para Validación Manual)

#### 1. Verificar en DevTools Console
```javascript
// Debería ver logs como:
// [CoreBootstrap] ✓ HttpClient inicializado
// [CoreBootstrap] ✓ SessionStorageInsumoRepository inicializado
// [CoreBootstrap] ✓ InsumoService inicializado
// [CoreBootstrap] ✓ Arquitectura DDD lista

// Verificar servicio está disponible
window.insumoService
// Response: InsumoService instance 

window.coreServices
// Response: {insumoService, insumoRepository, httpClient} 
```

#### 2. Pruebar Flujo de Carga de Insumos
```javascript
// En console, ejecutar manualmente:
await window.insumoService.obtenerInsumosDelPedido(123)

// Debería:
// 1️⃣  Mostrar logs [InsumoService] + [SessionStorageInsumoRepository]
// 2️⃣  Hacer HTTP GET (o returnar de cache si ya lo hizo)
// 3️⃣  Retornar objeto con {nombre_prenda, materiales[], totalMateriales, requiereCierre}
```

#### 3. Pruebar Cache
```javascript
// Primera llamada: HTTP request (Network tab)
await window.insumoService.obtenerInsumosDelPedido(123)

// Segunda llamada (< 30min): Solo cache (sin Network request) 
await window.insumoService.obtenerInsumosDelPedido(123)
// Time to complete: < 2ms (vs 100-150ms para HTTP)

// Verificar en sessionStorage:
sessionStorage
// Debería tener claves como: insumos_123_null
```

#### 4. Pruebar Error Handling
```javascript
// Test ValidationError
await window.insumoService.obtenerInsumosDelPedido('invalid-id')
// Error: ValidationError: "pedidoId debe ser un número válido"

// Test BusinessError
await window.insumoService.guardarCambiosInsumos(123, 456, [])
// Error: BusinessError: "Debe haber al menos un material"
```

#### 5. Pruebar Guardar Cambios
```javascript
// Simular clic en "Guardar" desde la UI
// O ejecutar manualmente:
const materiales = [
    { nombre_material: 'Algodón', recibido: true }
];
await window.insumoService.guardarCambiosInsumos(123, 456, materiales)

// Debería:
// 1️⃣  Validar parámetros
// 2️⃣  POST a servidor /insumos/api/materiales
// 3️⃣  Invalidar caché
// 4️⃣  Retornar true si éxito
```

#### 6. Pruebar Reintentos (Offline)
```javascript
// Abrir DevTools → Network → Offline
// Intentar abrirModalInsumos(123)

// Debería:
// 1️⃣  Primer intento: Network timeout
// 2️⃣  Automático retry 1: Network timeout
// 3️⃣  Automático retry 2: Network timeout (máximo alcanzado)
// 4️⃣  HttpError lanzado, mostrado al usuario

// En console ver: [HttpClient] Reintentando después de error...
```

### 🔍 Checklist de Validación

- [ ] DevTools Console abierto durante carga página
- [ ] Logs [CoreBootstrap] visibles (significa bootstrap.js ejecutado)
- [ ] window.insumoService disponible después DOMContentLoaded
- [ ] Clikear "Abrir Modal Insumos" ejecuta abrirModalInsumos refactorizado
- [ ] Modal muestra datos sin errores
- [ ] Console muestra [InsumoService] + [HttpClient] logs
- [ ] Segunda vez que abre modal = cache hit (< 2ms)
- [ ] Cambiar datos + guardar usa guardarCambios refactorizado
- [ ] Errores de validación se muestran como toast messages
- [ ] sessionStorage contiene claves insumos_*

### 📊 Performance Esperado

| Métrica | Sin Cache | Con Cache | Mejora |
|---------|-----------|-----------|--------|
| Tiempo de carga | 100-150ms | 1-2ms | **100x más rápido**  |
| Requests HTTP | 1 por usuario | 1 cada 30 min | **95% menos requests** 📉 |
| Network usage | Alto | Bajo | **Banda ahorrada** 💾 |

### 🐛 Debugging

**Si algo no funciona:**

1. **Bootstrap no se ejecuta**
   - Check: ¿Scripts cargan en orden correcto en layout?
   - Check: Error en console (syntax, reference error)?
   - Fix: Abrir ARQUITECTURA_COMPLETADA.md PASO 1

2. **window.insumoService es undefined**
   - Check: ¿Todos los core scripts cargaron?
   - Check: ¿DOMContentLoaded dispuesto después de scripts?
   - Fix: Verificar Network tab en DevTools, asegurarse scripts se cargan

3. **abrirModalInsumos fallando**
   - Check: ¿Error en console?
   - Check: ¿window.insumoService disponible?
   - Fix: Ver error específico, comparar con API_REFERENCE.md

4. **Cache no funciona**
   - Check: sessionStorage en DevTools Application tab
   - Check: ¿Primera llamada hace HTTP?
   - Fix: Abrir console, ejecutar manualmente para ver logs

### 📝 Notas Importantes

**⚠️  Critical Order**
El orden de carga es CRÍTICO:
1. HttpClient.js (sin dependencias)
2. InsumoRepository.js (interface)
3. SessionStorageInsumoRepository.js (usa HttpClient)
4. InsumoService.js (usa Repository)
5. bootstrap.js (usa Service)

Si está fuera de orden, obtendrá ReferenceError: X is not defined

**⚠️  Async/Await**
Las funciones refactorizadas son ahora async. Si se llaman desde HTML onclick:
```html
<!--  INCORRECTO
<button onclick="abrirModalInsumos(123)"></button>

<!--  CORRECTO (Promise se ignora silenciosamente si no se espera)
<button onclick="abrirModalInsumos(123)"></button>
```

**⚠️  Tests Locales**
Para testear sin servidor:
```javascript
// Mock repository
class MockRepository extends InsumoRepository {
  async obtenerInsumos() {
    return {
      nombre_prenda: 'Test',
      materiales: [{nombre_material: 'Test Material'}]
    };
  }
}

const service = new InsumoService(new MockRepository());
const result = await service.obtenerInsumosDelPedido(123);
```

### 📞 Contacto

Para preguntas o problemas:
1. Ver ARQUITECTURA_COMPLETADA.md para decisiones arquitectónicas
2. Ver API_REFERENCE.md para detalles de métodos
3. Ver INTEGRACION.md para pasos específicos
4. Ver console logs con prefijo [ComponentName]

---

**Estado**: 🚀 Listo para pruebas en navegador
**Próximo paso**: Validación manual en browser
**Feedback esperado**: Logs en console, comportamiento en UI
