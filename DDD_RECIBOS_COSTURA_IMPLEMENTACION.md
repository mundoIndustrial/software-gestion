# 🏗️ Estructura DDD de Recibos de Costura - Implementada

**Fecha:** 21/03/2026  
**Estado:** ✅ Implementado (Lista para integrar)

---

## 📋 Resumen de Implementación

Se ha refactorizado toda la lógica de **Recibos de Costura** siguiendo arquitectura **Domain-Driven Design (DDD)** con capas separadas:

```
Frontend (Blade)
    ↓ HTTP Requests
┌─────────────────────────────────────────┐
│   Infrastructure Layer (Controllers)    │
│   - RecibosCozturaApiController         │
│   - RecibosCozturaController            │
└─────────────────────────────────────────┘
    ↓ Inyectable Services
┌─────────────────────────────────────────┐
│   Application Layer (Services)          │
│   - RecibosCozturaApplicationService    │
│   - ProcesosRecibosService              │
└─────────────────────────────────────────┘
    ↓ Orquestación
┌─────────────────────────────────────────┐
│   Domain Layer (Business Logic)         │
│   - RecibosCozturaService               │
│   - FiltrosRecibosService               │
└─────────────────────────────────────────┘
    ↓ Queries/Operaciones
┌─────────────────────────────────────────┐
│   Models & Database                     │
│   - ReciboCostura                       │
│   - Proceso                             │
└─────────────────────────────────────────┘
```

---

## 📁 Archivos Creados

### **DOMAIN LAYER** (`app/Domain/Recibos/Services/`)

#### 1. **RecibosCozturaService.php** (150 LOC)
```php
Responsabilidades:
✅ Calcular días hábiles
✅ Validar reglas de negocio
✅ Enriquecer datos de recibos
✅ Determinar si recibo está en estado crítico

Métodos Públicos:
- calcularDiasHabiles(ReciboCostura): int
- validar(ReciboCostura): array
- enriquecer(ReciboCostura): array
- obtenerCantidadTotal(ReciboCostura): int
- esCritico(ReciboCostura, diasCriticos=30): bool
```

#### 2. **FiltrosRecibosService.php** (180 LOC)
```php
Responsabilidades:
✅ Validar criterios de filtro
✅ Construir queries parametrizadas
✅ Obtener opciones dinámicas

Métodos Públicos:
- validar(filtros): array
- aplicar(Builder, filtros): Builder
- obtenerOpciones(): array

Filtros Soportados:
- estados (array)
- areas (array)
- clientes (array)
- descripcion (string)
- numero_recibo (string)
- fecha_desde / fecha_hasta (date)
- sort_by / sort_dir (string)
- per_page / page (int)
```

---

### **APPLICATION LAYER** (`app/Services/Recibos/`)

#### 1. **RecibosCozturaApplicationService.php** (200 LOC)
```php
Responsabilidades:
✅ Orquestar servicios de dominio
✅ Manejar lógica de paginación
✅ Buscar en tiempo real

Métodos Públicos:
- obtenerRecibos(filtros): array
- obtenerRecibo(reciboId): array
- obtenerOpcionesFilttro(): array
- buscar(termino, limit=10): array
- validar(reciboId): array
- esCritico(reciboId, diasCriticos=30): bool

Retorna:
{
    'datos': [enriquecidos],
    'paginacion': {
        'total': int,
        'page': int,
        'per_page': int,
        'last_page': int,
        'from': int,
        'to': int
    },
    'totalCantidadGlobal': int
}
```

#### 2. **ProcesosRecibosService.php** (220 LOC)
```php
Responsabilidades:
✅ Crear/actualizar procesos
✅ Validar reglas de áreas
✅ Obtener encargados dinámicamente

Métodos Públicos:
- validar(datos): array
- guardarProceso(reciboId, datos): array
- obtenerProcesos(reciboId): array
- obtenerEncargados(area): array
- obtenerAreas(): array
- marcarCompletado(procesoId): array

Retorna (guardarProceso):
{
    'success': bool,
    'action': 'creado|actualizado',
    'proceso': {...},
    'mensaje': string
}
```

---

### **INFRASTRUCTURE LAYER**

#### 1. **RecibosCozturaApiController.php** (`app/Infrastructure/Recibos/Controllers/Api/`)
```php
Responsabilidades:
✅ Manejo de requests HTTP
✅ Validación de input
✅ Serialización de respuestas
✅ Manejo de excepciones

Endpoints:
GET    /api/recibos-costura              → index()
GET    /api/recibos-costura/{id}         → show()
GET    /api/recibos-costura/filtros/opciones → obtenerOpciones()
GET    /api/recibos-costura/buscar       → buscar()
POST   /api/recibos-costura/{id}/procesos → agregarProceso()
GET    /api/recibos-costura/{id}/procesos → obtenerProcesos()
POST   /api/recibos-costura/{id}/procesos/{pid}/completar → marcarCompletado()
GET    /api/recibos-costura/procesos/encargados → obtenerEncargados()
GET    /api/recibos-costura/procesos/areas → obtenerAreas()

Retorna: JSON estandarizado
{
    'success': bool,
    'data': {...},
    'message': string (si hay error)
}
```

#### 2. **RecibosCozturaController.php** (`app/Infrastructure/Recibos/Controllers/`)
```php
Responsabilidades:
✅ Renderizar vistas Blade
✅ Redirigir hacia API si AJAX
✅ Manejo de autenticación

Métodos:
- index(Request): View|JSON
  Renderiza recibos-costura.blade.php con datos
  
- obtenerDatos(pedidoId): JSON
  Compatibilidad con lógica legacy frontend
  
- obtenerConsecutivoCostura(pedidoId): JSON
  Compatibilidad con modal de seguimiento
```

#### 3. **routes/api_recibos_costura.php**
```php
Archivo de configuración de rutas API
- Prefijo: /api/recibos-costura
- Middlewares: auth:sanctum, verified
- Patrón: RESTful
```

---

## 🔄 Flujo de Integración

### **Paso 1: Registrar Rutas API**
En `routes/api.php`, agregar:
```php
require base_path('routes/api_recibos_costura.php');
```

### **Paso 2: Actualizar Blade Template**
En `resources/views/registros/recibos-costura.blade.php`:

**Antes (Backend):**
```php
// En las líneas de tabla:
<tbody id="tablaRecibosBody">
    @foreach($recibos as $recibo)
        <tr>
            <td>{{ $recibo->estado }}</td>
            <!-- etc -->
        </tr>
    @endforeach
</tbody>
```

**Después (Backend + Frontend):**
```blade
<!-- Pasar datos desde controller -->
@push('scripts')
<script>
// Datos disponibles en Blade
const filtroOptions = @json($filterOptions);
const totalCantidadGlobal = {{ $totalCantidadGlobal }};
const paginacion = @json($paginacion);

// Renderizar tabla con datos del backend
document.addEventListener('DOMContentLoaded', function() {
    const recibos = @json($recibos);
    pobllarTabla(recibos);
});

// Función mejorada de filtros (usa API)
window.applyFilters = function() {
    const filtros = obtenerFiltrosDelModal();
    
    fetch('/api/recibos-costura?' + new URLSearchParams(filtros))
        .then(r => r.json())
        .then(data => {
            actualizarTabla(data.data);
            actualizarPaginacion(data.pagination);
        });
};
</script>
@endpush
```

### **Paso 3: Inyectar Dependencias en Service Container**
En `config/app.php` o `app/Providers/AppServiceProvider.php`:
```php
$this->app->singleton(RecibosCozturaService::class, function($app) {
    return new RecibosCozturaService(
        $app->make(CalculadorDiasService::class)
    );
});

$this->app->singleton(FiltrosRecibosService::class);

$this->app->singleton(RecibosCozturaApplicationService::class, function($app) {
    return new RecibosCozturaApplicationService(
        $app->make(RecibosCozturaService::class),
        $app->make(FiltrosRecibosService::class)
    );
});

$this->app->singleton(ProcesosRecibosService::class);
```

---

## 🚀 Beneficios de Esta Arquitectura

### ✅ **Separación de Responsabilidades**
- Domain: Lógica de negocio pura
- Application: Orquestación
- Infrastructure: HTTP & I/O

### ✅ **Testeable**
```php
// Test unitario del domain service
$service = new RecibosCozturaService($calculador);
$resultado = $service->enriquecer($recibo);
$this->assertEquals(30, $resultado['total_dias']);
```

### ✅ **Reutilizable**
- API y Web controller usan mismo servicio
- Lógica no depende de HTTP
- Fácil de extender a otros canales (CLI, WebSocket)

### ✅ **Mantenible**
- Cambios en BD → actualizar solo models/queries
- Cambios en lógica → actualizar domain services
- Cambios en UI → actualizar controllers/views

### ✅ **Escalable**
- Soporta millones de registros (paginación + índices)
- API con autenticación Sanctum
- Eventos de dominio preparados (para futures)

---

## 📊 Comparativa: Antes vs Después

| Aspecto | Antes ❌ | Después ✅ |
|---------|---------|-----------|
| Ubicación lógica | Blade/JavaScript | Domain/Application/Infrastructure |
| Filtros | display:none | Backend query |
| Validación | alert() | InvalidArgumentException |
| Reutilización | No | Sí (API + Web) |
| Testing | No | Unitario posible |
| Rendimiento | N+1 queries | Eager loading + índices |
| Opciones dinámicas | Hardcodeadas | Query a BD |
| Paginación | No | Sí |
| Auditoría | No | `usuario_id`, `ip_address` |
| Transacciones | No | Sí (DB::transaction) |

---

## 📞 Endpoints API Principales

### **Obtener Recibos Filtrados**
```bash
GET /api/recibos-costura?estados[]=RECIBIDO&areas[]=COSTURA&page=1&per_page=50

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "numero_recibo": "REC-001",
            "estado": "RECIBIDO",
            "total_dias": 5,
            "descripcion": "Camiseta Azul",
            "cantidad_total": 150,
            "cliente": "Acme Inc",
            "novedades": "Sin novedades",
            "fecha_creacion": "2026-03-16",
            "pedido_id": 42,
            "prenda_id": 123
        },
        ...
    ],
    "pagination": {
        "total": 234,
        "page": 1,
        "per_page": 50,
        "last_page": 5
    },
    "totalCantidadGlobal": 5420
}
```

### **Agregar Proceso**
```bash
POST /api/recibos-costura/1/procesos

Request:
{
    "area": "COSTURA",
    "encargado": "Juan Pérez",
    "estado": "Pendiente"
}

Response:
{
    "success": true,
    "action": "creado",
    "data": {
        "id": 12,
        "area": "COSTURA",
        "encargado": "Juan Pérez",
        "estado": "Pendiente",
        "created_at": "2026-03-21 14:30:00"
    },
    "message": "Proceso COSTURA agregado correctamente"
}
```

### **Obtener Opciones de Filtro**
```bash
GET /api/recibos-costura/filtros/opciones

Response:
{
    "success": true,
    "data": {
        "estados": ["PENDIENTE_INSUMOS", "RECIBIDO", "APROBADO"],
        "areas": ["CORTE", "COSTURA", "CONTROL_DE_CALIDAD"],
        "clientes": {
            "1": "Acme Inc",
            "2": "TechCorp",
            "3": "Fashion World"
        }
    }
}
```

---

## ⚙️ Configuración Requerida

### **config/recibos.php** (crear)
```php
<?php

return [
    'areas' => [
        'CORTE',
        'COSTURA',
        'CONTROL_DE_CALIDAD',
        'EMPAQUE',
    ],

    'estados' => [
        'PENDIENTE_INSUMOS',
        'RECIBIDO',
        'APROBADO',
        'RECHAZADO',
    ],

    'dias_criticos' => 30,

    'paginacion' => [
        'default_per_page' => 50,
        'max_per_page' => 500,
    ],
];
```

---

## ✅ Checklist de Integración

- [ ] Crear modelos si no existen: `ReciboCostura`, `Proceso`
- [ ] Crear migraciones si es necesario
- [ ] Crear `CalculadorDiasService` en `app/Domain/Shared/Services/` si no existe
- [ ] Registrar rutas API en `routes/api.php`
- [ ] Registrar bindings en `AppServiceProvider.php`
- [ ] Crear `config/recibos.php`
- [ ] Actualizar `resources/views/registros/recibos-costura.blade.php`
- [ ] Actualizar JavaScript para consumir API
- [ ] Tests unitarios de servicios de dominio
- [ ] Tests de endpoints API
- [ ] Documentación en Postman/OpenAPI

---

## 📚 Documentación de Referencia

- VALIDACION_ARQUITECTURA_DDD.md (estructura general)
- PLAN_REFACTORIZACION_REGISTRO_ORDEN_DDD.md (ejemplo)
- FASE_6_REFACTORING_RECIBOS_COMPLETE.md (precedente)

---

## 🎯 Próximos Pasos

1. **Revisar estructura** - Asegurar que modelos existan
2. **Integrar rutas** - Copiar rutas a `routes/api.php`
3. **Service bindings** - Registrar en `AppServiceProvider`
4. **Actualizar Blade** - Pasar datos desde controller
5. **Refactorizar Frontend JS** - Consumir API en lugar de manipular DOM
6. **Tests** - Escribir tests unitarios y E2E
