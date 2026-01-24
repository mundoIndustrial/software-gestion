# BÚSQUEDA DE ARCHIVOS PARA MIGRACIÓN - FASE 2

**Fecha:** 2024
**Objetivo:** Localizar todos los archivos que necesitan ser actualizados en Fase 2

---

## 🔍 Paso 1: Buscar Llamadas a Rutas Antiguas

### Comandos de Búsqueda:

```bash
# 1. Buscar "/asesores/pedidos" en todos los archivos
grep -r "/asesores/pedidos" . --include="*.js" --include="*.php" --include="*.blade.php" --exclude-dir=vendor --exclude-dir=node_modules

# 2. Buscar "asesores/pedidos" (sin slash inicial)
grep -r "asesores/pedidos" . --include="*.js" --include="*.php" --include="*.blade.php" --exclude-dir=vendor --exclude-dir=node_modules

# 3. Buscar referencias a CrearPedidoService
grep -r "CrearPedidoService" . --include="*.php" --exclude-dir=vendor

# 4. Buscar referencias a AnularPedidoService
grep -r "AnularPedidoService" . --include="*.php" --exclude-dir=vendor

# 5. Buscar referencias a ObtenerFotosService
grep -r "ObtenerFotosService" . --include="*.php" --exclude-dir=vendor
```

---

## 📂 Archivos a Revisar

### 1. Templates (Blade) - VISTAS HTML

**Ubicación:** `resources/views/`

Buscar en:
- [ ] `resources/views/asesores/**/*.blade.php` - Vistas de asesores
- [ ] `resources/views/pedidos/**/*.blade.php` - Vistas de pedidos
- [ ] Cualquier template con `<form action="/asesores/pedidos"`

**Qué buscar:**
```blade
<!-- ANTES - ❌ DEPRECADO -->
<form action="/asesores/pedidos" method="POST">
<form action="/asesores/pedidos/confirm" method="POST">
<form action="/asesores/pedidos/{{ $id }}/anular" method="POST">
fetch('/asesores/pedidos/...', { method: 'POST' })

<!-- DESPUÉS -  NUEVO -->
<form action="/api/pedidos" method="POST">
fetch('/api/pedidos', { method: 'POST' })
```

---

### 2. JavaScript (AJAX/Fetch) - LÓGICA FRONTAL

**Ubicación:** `resources/js/`, `public/js/`

Buscar en:
- [ ] `resources/js/**/*.js` - Código JavaScript moderno
- [ ] `public/js/**/*.js` - Código JavaScript legacy
- [ ] Cualquier archivo con `fetch('/asesores/pedidos`
- [ ] Cualquier archivo con `$.ajax('/asesores/pedidos`

**Qué buscar:**
```javascript
// ANTES - ❌ DEPRECADO
fetch('/asesores/pedidos', ...)
$.ajax('/asesores/pedidos', ...)
axios.post('/asesores/pedidos', ...)
window.location = '/asesores/pedidos'

// DESPUÉS -  NUEVO
fetch('/api/pedidos', ...)
$.ajax('/api/pedidos', ...)
axios.post('/api/pedidos', ...)
fetch(`/api/pedidos/${id}`, ...)
```

---

### 3. Controllers (PHP) - LÓGICA DE SERVIDOR

**Ubicación:** `app/Http/Controllers/`, `app/Infrastructure/Http/Controllers/`

Buscar en:
- [ ] `app/Http/Controllers/AsesoresController.php` - Controller de asesores
- [ ] `app/Http/Controllers/SupervisorPedidosController.php` - Controller de supervisor
- [ ] `app/Infrastructure/Http/Controllers/CotizacionController.php` - Controller de cotizaciones
- [ ] Cualquier controller que inyecte CrearPedidoService

**Qué buscar:**
```php
// ANTES - ❌ DEPRECADO
use App\Services\CrearPedidoService;
use App\Services\AnularPedidoService;
use App\Services\ObtenerFotosService;

public function __construct(CrearPedidoService $service) {
    $this->crearPedidoService = $service;
}

$this->crearPedidoService->crear($datos);
$this->anularPedidoService->anular($id, $razon);

// DESPUÉS -  NUEVO
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;

public function __construct(CrearPedidoUseCase $useCase) {
    $this->crearPedidoUseCase = $useCase;
}

$this->crearPedidoUseCase->ejecutar($datos);
$this->cancelarPedidoUseCase->ejecutar($id, $razon);
```

---

### 4. Routes (web.php) - CONFIGURACIÓN DE RUTAS

**Ubicación:** `routes/web.php`, `routes/api.php`

Buscar en:
- [x] `routes/web.php` - YA REVISADO Y ACTUALIZADO

**Estado:**  YA CONSOLIDADAS

---

### 5. Services (Legacy) - LÓGICA DE NEGOCIO

**Ubicación:** `app/Services/`

Archivos a eliminar en Fase 4:
- [ ] `app/Services/CrearPedidoService.php` - DEPRECADO (usar CrearPedidoUseCase)
- [ ] `app/Services/AnularPedidoService.php` - DEPRECADO (usar CancelarPedidoUseCase)
- [ ] `app/Services/ObtenerFotosService.php` - DEPRECADO (usar nueva estructura)
- [ ] Cualquier otro service relacionado a pedidos legacy

**Acción:** Documentar qué usa cada uno antes de eliminar

---

### 6. Models (Eloquent) - MODELOS DE BASE DE DATOS

**Ubicación:** `app/Models/`

Revisar si usan tabla legacy:
- [ ] `app/Models/PedidoProduccion.php` - ⚠️ TABLA LEGACY
- [ ] `app/Models/Pedido.php` -  TABLA NUEVA (DDD)

**Qué buscar:**
```php
// ANTES - ❌ LEGACY
class PedidoProduccion extends Model {
    protected $table = 'pedidos_produccion';
}

// DESPUÉS -  DDD
class Pedido extends Model {
    protected $table = 'pedidos';
}
```

---

### 7. Repositories - ACCESO A DATOS

**Ubicación:** `app/Infrastructure/`

Revisar si usan legacy:
- [ ] `app/Infrastructure/Pedidos/Persistence/` - Repositories
- [ ] Cualquier repository que use PedidoProduccion model

---

## 🔎 Comandos de Búsqueda Ejecutables

```bash
# PowerShell en Windows
# Copiar y pegar estos comandos en terminal PowerShell

# 1. Buscar archivos JavaScript con /asesores/pedidos
Get-ChildItem -Path . -Recurse -Include "*.js", "*.blade.php" -Exclude "vendor", "node_modules" | 
  Select-String "asesores/pedidos" | 
  Format-Table Path, LineNumber, Line

# 2. Buscar archivos PHP con services legacy
Get-ChildItem -Path . -Recurse -Include "*.php" -Exclude "vendor" | 
  Select-String "CrearPedidoService|AnularPedidoService" | 
  Format-Table Path, LineNumber, Line

# 3. Listar todos los archivos en resources/views
Get-ChildItem -Path ".\resources\views" -Recurse -Include "*.blade.php"

# 4. Listar todos los archivos en resources/js
Get-ChildItem -Path ".\resources\js" -Recurse -Include "*.js"
```

---

##  Template de Checklist por Archivo

Crear este checklist para CADA archivo encontrado:

```
ARCHIVO: resources/views/asesores/pedidos/index.blade.php
ESTADO: ⏳ PENDIENTE REVISAR

Líneas con /asesores/pedidos:
- Línea 45: <form action="/asesores/pedidos" method="POST">
- Línea 102: fetch('/asesores/pedidos/...', {...})

Cambios Requeridos:
- [ ] Actualizar form action="/api/pedidos"
- [ ] Actualizar fetch a /api/pedidos
- [ ] Validar estructura de respuesta
- [ ] Agregar manejo de errores 410

Testing:
- [ ] Funciona crear pedido
- [ ] Funciona confirmar
- [ ] Funciona listar
- [ ] Respuestas JSON estructuradas correctamente

Validación:
- [ ] No hay referencias a código legacy
- [ ] Compila sin errores
- [ ] Tests pasan

Status: ⏳ PENDIENTE / 🔄 EN PROGRESO /  COMPLETADO
```

---

## Plan de Acción para Fase 2

### Paso 1: Ejecutar búsquedas (15 min)
```bash
# Buscar en JavaScript
grep -r "asesores/pedidos" resources/js --include="*.js"
grep -r "asesores/pedidos" resources/views --include="*.blade.php"

# Buscar en Controllers
grep -r "CrearPedidoService" app/ --include="*.php" --exclude-dir=vendor
grep -r "AnularPedidoService" app/ --include="*.php" --exclude-dir=vendor

# Buscar en Routes
grep -r "AsesoresAPIController" routes/ --include="*.php"
```

### Paso 2: Crear lista de archivos (30 min)
- Documentar cada archivo encontrado
- Indicar cantidad de líneas a cambiar
- Priorizar por críticidad

### Paso 3: Migrar por archivo (2-4 horas)
- Abrir archivo
- Localizar referencias a `/asesores/pedidos`
- Actualizar según GUIA_MIGRACION_FRONTEND.md
- Testing local
- Validar no hay errores

### Paso 4: Testing integrado (1-2 horas)
- Ejecutar suite completa
- Testing manual de flujos
- Validar respuestas JSON
- Verificar mensajes de error

### Paso 5: Commit (15 min)
- Commit con mensaje claro
- Push a rama de desarrollo
- Crear PR si aplica

---

## 📊 Matriz de Archivos

Use esta tabla para rastrear progreso:

| Archivo | Ubicación | Líneas | Prioridad | Status | Fecha |
|---------|-----------|--------|-----------|--------|-------|
| pedidos/index.blade.php | views | 5 | 🔴 ALTA | ⏳ | - |
| pedidos/create.blade.php | views | 3 | 🔴 ALTA | ⏳ | - |
| pedidos.js | js | 8 | 🔴 ALTA | ⏳ | - |
| AsesoresController.php | Controllers | 2 | 🟡 MEDIA | ⏳ | - |
| ... | ... | ... | ... | ... | ... |

---

## ⚠️ Notas Importantes

1. **NO BORRAR** archivos aún - solo actualizar
2. **TESTEAR** cada cambio antes de siguiente
3. **MANTENER** backward compatibility si es posible
4. **DOCUMENTAR** cambios en commit message
5. **REVISAR** errores 410 Gone si aparecen

---

**Próximo paso:** Ejecutar búsquedas y crear lista detallada de archivos a migrar

**Tiempo estimado para Fase 2:** 4-6 horas (dependiendo de cantidad de archivos encontrados)
