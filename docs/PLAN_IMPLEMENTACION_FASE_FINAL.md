# 🚀 PLAN DE IMPLEMENTACIÓN - FASE FINAL

## 📊 Estado Actual

### ✅ COMPLETADO
- Backend DDD (16 archivos) - Value Objects, Domain Services, Application Services ✅
- Frontend refactorizado - PrendaEditorOrchestrator ✅
- Migrations de referencias - gestion-items-pedido.js, item-orchestrator.js ✅

### ⚠️ FALTA (CRÍTICO)
- PrendaController.php (app/Http/Controllers/Api/) - Tiene código VIEJO
- Service Provider - Registrar inyecciones de dependencias
- Modelo Eloquent - Verificar que Prenda model tiene relaciones correctas
- Tests - Compilación PHP y test funcional

---

## 🎯 Fases de Implementación

### FASE 1: Preparación Backend (.php) - 30 min

#### 1.1 Actualizar PrendaController
**Ubicación:** `app/Http/Controllers/Api/PrendaController.php`
**Acción:** Reemplazar archivo VIEJO con el nuevo que tiene:
- `show(int $id)` - GET /api/prendas/{id}
- `store(Request)` - POST /api/prendas
- `update(int $id, Request)` - PUT /api/prendas/{id}
- `destroy(int $id)` - DELETE /api/prendas/{id}
- `index()` - GET /api/prendas (listar)

**Status:** 🔴 BLOQUEADO - Controller actual solo tiene `tiposPrenda()` y `reconocer()`

#### 1.2 Crear Service Provider
**Ubicación:** `app/Providers/PrendaServiceProvider.php`
**Contenido:**
```php
public function register(): void
{
    // Registrar inyecciones
    $this->app->bind(
        PrendaRepositoryInterface::class,
        EloquentPrendaRepository::class
    );
    
    $this->app->singleton(AplicarOrigenAutomaticoDomainService::class);
    $this->app->singleton(ValidarPrendaDomainService::class);
    $this->app->singleton(NormalizarDatosPrendaDomainService::class);
}
```

**Estado:** 📝 NO EXISTE - Necesario crear

#### 1.3 Actualizar config/app.php
**Acción:** Agregar `PrendaServiceProvider::class` a `providers[]`
**Estado:** 📝 VERIFICAR

#### 1.4 Actualizar Modelo Eloquent (Prenda.php)
**Acción:** Verificar relaciones:
```php
class Prenda extends Model {
    public function telas() { return $this->belongsToMany(Tela::class); }
    public function procesos() { return $this->belongsToMany(Proceso::class); }
    public function variaciones() { return $this->belongsToMany(Variacion::class); }
}
```

**Estado:** ✓ VERIFICAR si ya existen

---

### FASE 2: Integración Frontend - 20 min

#### 2.1 Verificar que PrendaAPI tiene endpoints correctos
**Archivo:** `public/js/servicios/prenda-api.js`
**Verificar:**
```javascript
async obtenerPrendaParaEdicion(prendaId) {
    return fetch(`/api/prendas/${prendaId}`).then(r => r.json());
}

async guardarPrenda(datos) {
    return fetch(`/api/prendas`, { 
        method: 'POST',
        body: JSON.stringify(datos)
    }).then(r => r.json());
}
```

**Status:** ✅ DEBERÍA ESTAR LISTO

#### 2.2 Scripts en HTML
**Verificar que están cargados en este orden:**
```html
<script src="/js/servicios/prenda-event-bus.js"></script>
<script src="/js/servicios/prenda-api.js"></script>
<script src="/js/servicios/prenda-dom-adapter.js"></script>
<script src="/js/servicios/prenda-editor-orchestrator.js"></script>
```

**Status:** 📝 VERIFICAR en Vista principal

---

### FASE 3: Testing - 30 min

#### 3.1 Compilación PHP ✓
```bash
php artisan tinker
# Dentro de tinker:
> new App\Domain\Prenda\ValueObjects\PrendaId(1);
> new App\Domain\Prenda\ValueObjects\Origen('bodega');
> // Etc...
```

#### 3.2 Test Funcional: Guardar Prenda
```bash
curl -X POST /api/prendas \
  -H "Content-Type: application/json" \
  -d '{
    "nombre_prenda": "Polo Reflectivo",
    "genero": 1,
    "tipo_cotizacion": "REFLECTIVO",
    "telas": [{"id": 1, "nombre": "Algodón", "codigo": "ALG-001"}],
    "procesos": [],
    "variaciones": [{"id": 1, "talla": "M", "color": "Azul"}]
  }'
```

**Expected Response:**
```json
{
  "exito": true,
  "datos": {
    "id": 1,
    "nombre_prenda": "Polo Reflectivo",
    "origen": "BODEGA",  // ← FUE CALCULADO EN BACKEND
    "tipo_cotizacion": "REFLECTIVO",
    "telas": [...],
    "variaciones": [...]
  },
  "errores": []
}
```

#### 3.3 Test Funcional: Cargar Prenda
```bash
curl -X GET /api/prendas/1
```

#### 3.4 Test UI
1. Abrir modal "Agregar Prenda Nueva"
2. Llenar formulario
3. Guardar
4. Verificar que respuesta del backend aparece en UI
5. Verificar que origen se aplicó correctamente

---

## 📋 CHECKLIST - Orden de Ejecución

### PASO 1: Reemplazar PrendaController
- [ ] Leer el PrendaController que creé hace rato (en las notas previas)
- [ ] Comparar con el actual
- [ ] Reemplazar archivo

### PASO 2: Crear Service Provider
- [ ] Crear `app/Providers/PrendaServiceProvider.php`
- [ ] Registrar en `config/app.php` en la lista de `providers`

### PASO 3: Verificar Modelo
- [ ] Abrir `app/Models/Prenda.php`
- [ ] Verificar relaciones con telas, procesos, variaciones (belongsToMany)
- [ ] Si faltan, agregar

### PASO 4: Compilación PHP
- [ ] `php artisan tinker`
- [ ] Crear instancias de Value Objects
- [ ] Verificar que no hay errores de sintaxis

### PASO 5: Test Funcional
- [ ] POST /api/prendas con datos
- [ ] Verificar que origen se aplicó
- [ ] GET /api/prendas/{id}
- [ ] Verificar respuesta normalizada

### PASO 6: Test UI
- [ ] Abrir modal
- [ ] Guardar prenda
- [ ] Verificar que función en UI

---

## 🚨 Errores Posibles

| Error | Causa | Solución |
|-------|-------|----------|
| `Class not found: App\Http\Controllers\Api\PrendaController` | routes/api.php usa import viejo | Actualizar import en routes/api.php |
| `Call to undefined method` en Orchestrator | PrendaAPI no tiene método | Agregar método en prenda-api.js |
| `Cannot instantiate Prenda` | Validación en Value Object | Verificar datos pasados |
| `Target not bindable` | Service Provider no registrado | Agregar a config/app.php |
| Origen no se aplica | Backend no llamando AplicarOrigenAutomaticoDomainService | Verificar GuardarPrendaApplicationService::ejecutar() |

---

## ✅ Definición de "Implementación Completa"

Se considera implementación completa cuando:

1. ✅ Código PHP compila sin errores
2. ✅ Rutas API responden correctamente
3. ✅ Backend aplica origen automático correctamente
4. ✅ Frontend puede guardar y cargar prendas
5. ✅ Errores del backend aparecen en UI
6. ✅ No hay lógica de negocio en frontend

---

## 🎬 ¿Comenzamos?

¿Por dónde empezamos? Las opciones:

1. **Opción A (Recomendada):** Todo en orden
   - Paso 1: Reemplazar PrendaController
   - Paso 2: Crear Service Provider
   - Paso 3: Verificar Modelo
   - Paso 4-6: Testing

2. **Opción B (Rápida - Solo lectura):**
   - Verificar que GET /api/prendas/1 funciona
   - Postergar POST/PUT para después

3. **Opción C (Específico):**
   - Dime qué paso quieres hacer primero

**Mi recomendación:** Opción A (30 min) para tener todo listo de una vez.

¿Vamos?
