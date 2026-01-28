# 🔍 AUDITORÍA DE RENDIMIENTO: Ruta `/asesores/pedidos` (~17 segundos)

**Fecha:** 27 Enero 2026  
**Componentes Analizados:**
- Controlador: `AsesoresController::index()`
- Servicio: `ObtenerPedidosService::obtener()`
- Vista: `asesores/pedidos/index.blade.php`
- Modelos: `PedidoProduccion`, `PrendaPedido`, `ProcesoPrenda`

---

## 📊 RESUMEN EJECUTIVO

La ruta está cargando **~17 segundos** mientras los assets cargan en milisegundos. Esto indica que el problema está **100% en la lógica de backend (SQL + procesamiento)**.

### 🚨 Problemas Identificados:

| Prioridad | Problema | Impacto | Línea/Ubicación |
|-----------|----------|--------|-----------------|
| 🔴 CRÍTICO | **N+1 Query**: Cargar `procesos` dentro de loop de prendas | ~50-60% de retraso | [ObtenerPedidosService.php:65-68](#bloque-1-n1-en-procesos) |
| 🔴 CRÍTICO | **Sin paginación en query principal** | Cargando TODOS los pedidos | [ObtenerPedidosService.php:61-62](#bloque-2-ausencia-de-paginacion) |
| 🟠 SERIO | **Relaciones sin limit/select** | Cargando columnas innecesarias | [ObtenerPedidosService.php:63-68](#bloque-3-select-completo) |
| 🟠 SERIO | **Múltiples queries en vista** | `abrirModalDescripcion()` hace fetch individual | [index.blade.php:310-350](#bloque-4-fetch-individual) |
| 🟡 MODERADO | **Logs en producción** | 4 logs por request (`\Log::info`) | [ObtenerPedidosService.php:19-24](#bloque-5-logs-produccion) |
| 🟡 MODERADO | **Estado distinct sin índice** | Tabla completa en cada request | [ObtenerPedidosService.php:133](#bloque-6-distinct-sin-indice) |

---

## 🎯 ANÁLISIS DETALLADO

### ❌ Bloque 1: N+1 EN PROCESOS
**Ubicación:** [ObtenerPedidosService.php](ObtenerPedidosService.php#L63-L68)

```php
// ❌ PROBLEMA: Esto genera N+1 queries
$query = PedidoProduccion::where('asesor_id', $userId)
    ->with([
        'prendas' => function ($q) {
            $q->with(['procesos' => function ($q2) {
                $q2->orderBy('created_at', 'desc');  // ❌ SIN LIMIT!
            }]);
        },
        'asesora'
    ]);
```

**¿Qué pasa?**
1. Query 1: Obtiene todos los pedidos
2. Query 2-N: Por CADA prenda, carga TODOS sus procesos sin limit
3. Si tienes 20 pedidos × 5 prendas = **100 queries adicionales**
4. Cada query trae procesos SIN ordenar ni limitar

**Impacto Estimado:** 10-12 segundos de los 17

---

### ❌ Bloque 2: AUSENCIA DE PAGINACIÓN
**Ubicación:** [ObtenerPedidosService.php](ObtenerPedidosService.php#L84)

```php
// ✅ CORRECTO al final:
return $query->orderBy('created_at', 'desc')->paginate($perPage);

// ❌ PERO: El parámetro por defecto es 20, y SIN paginación en la query anterior
```

**Problema:** Si bien la paginación está al final, la query completa SIN limit corre primero.

---

### ❌ Bloque 3: SELECT COMPLETO DE COLUMNAS
**Ubicación:** [ObtenerPedidosService.php](ObtenerPedidosService.php#L61-L68)

```php
// ❌ Cargando TODAS las columnas
$query = PedidoProduccion::where('asesor_id', $userId)
    ->with([...]);

// ❌ En la relación 'prendas', cargando todo:
'prendas' => function ($q) {
    $q->with(['procesos' => ...]);
}
```

**¿Qué debería ser?**
- De `pedidos_produccion`: Solo columnas necesarias (id, numero_pedido, cliente, estado, fecha_creacion, etc.)
- De `prendas_pedido`: Solo columnas necesarias (id, nombre_prenda, cantidad, etc.)
- De `procesos_prenda`: MÁXIMO 3 procesos, y solo (id, tipo_proceso, fecha)

---

### ❌ Bloque 4: FETCH INDIVIDUAL EN MODAL
**Ubicación:** [index.blade.php](index.blade.php#L315-L330)

```javascript
async function editarPedido(pedidoId) {
    // ❌ NUEVO FETCH por cada clic
    const response = await fetch(`/api/pedidos/${pedidoId}`, {
        method: 'GET',
        headers: { ... }
    });
    const respuesta = await response.json();
    
    // Esto genera OTRA query completa al backend
}
```

**Problema:** La vista YA tiene los datos del pedido, pero hace otro fetch. Desperdicio de conexión + tiempo.

---

### ❌ Bloque 5: LOGS EN PRODUCCIÓN
**Ubicación:** [ObtenerPedidosService.php](ObtenerPedidosService.php#L19-24)

```php
\Log::info(' [OBTENER PEDIDOS] Iniciando búsqueda', [
    'tipo' => $tipo,
    'filtros' => $filtros,
    'por_pagina' => $perPage
]);

// ❌ 4 logs por request
```

**Impacto:** Cada log escribe a disco (storage/logs/laravel.log) = I/O lento.

---

### ❌ Bloque 6: DISTINCT SIN ÍNDICE
**Ubicación:** [ObtenerPedidosService.php](ObtenerPedidosService.php#L130-L135)

```php
public function obtenerEstados(): array
{
    $estados = PedidoProduccion::select('estado')
        ->whereNotNull('estado')
        ->distinct()  // ❌ Sin índice en 'estado', escanea tabla completa
        ->pluck('estado')
        ->toArray();
}
```

---

## ✅ SOLUCIONES CONCRETAS

### 🔧 SOLUCIÓN 1: Eliminar N+1 con Limit en Procesos

**Archivo:** `app/Application/Services/Asesores/ObtenerPedidosService.php`

```php
// ✅ OPTIMIZADO
private function obtenerPedidosProduccion(int $userId, ?string $tipo, array $filtros = [], int $perPage = 20): LengthAwarePaginator
{
    $query = PedidoProduccion::where('asesor_id', $userId)
        ->select([
            'id', 'numero_pedido', 'cliente', 'estado', 
            'forma_de_pago', 'created_at', 'asesor_id'
        ])  // ✅ Solo columnas necesarias
        ->with([
            'prendas' => function ($q) {
                $q->select(['id', 'pedido_produccion_id', 'nombre_prenda', 'cantidad'])
                  ->with(['procesos' => function ($q2) {
                      $q2->select(['id', 'prenda_pedido_id', 'tipo_proceso', 'created_at'])
                          ->limit(3)  // ✅ CRÍTICO: Limit 3 procesos por prenda
                          ->orderBy('created_at', 'desc');
                  }]);
            },
            'asesora' => function ($q) {
                $q->select(['id', 'name', 'email']);
            }
        ])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);  // ✅ Paginación AQUÍ

    // Remover logs de producción
    // \Log::info(...);

    return $query;
}
```

**Mejora Estimada:** -8 a -10 segundos (60% del retraso)

---

### 🔧 SOLUCIÓN 2: Cache de Estados

**Archivo:** `app/Application/Services/Asesores/ObtenerPedidosService.php`

```php
/**
 * Obtener estados únicos disponibles - CON CACHE
 */
public function obtenerEstados(): array
{
    // ✅ Cache por 1 hora
    return \Cache::remember('pedidos_estados_list', 3600, function () {
        return PedidoProduccion::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado')
            ->toArray();
    });
}
```

**Mejora:** -0.5 a -1 segundo en requests posteriores

---

### 🔧 SOLUCIÓN 3: Remover Logs de Producción

**Archivo:** `app/Application/Services/Asesores/ObtenerPedidosService.php`

Reemplazar todas las líneas:
```php
\Log::info(...)
\Log::warning(...)
```

Con condicional:
```php
if (app()->isLocal()) {
    \Log::info('[OBTENER PEDIDOS] ...', [...]);
}
```

**Mejora:** -0.3 a -0.5 segundos (escritura a disco)

---

### 🔧 SOLUCIÓN 4: Datos en JavaScript en Lugar de Fetch

**Archivo:** `resources/views/asesores/pedidos/index.blade.php`

Cambiar función `editarPedido()`:

```javascript
// ❌ ANTES:
async function editarPedido(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}`);
    const respuesta = await response.json();
    // ...
}

// ✅ DESPUÉS:
async function editarPedido(pedidoId) {
    // Los datos ya están en la tabla, extraer de data attributes
    const fila = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
    
    if (!fila) {
        // Solo si no encontramos, hacer fetch
        const response = await fetch(`/api/pedidos/${pedidoId}`);
        const respuesta = await response.json();
        const datos = respuesta.data || respuesta.datos;
        abrirModalEditarPedido(pedidoId, datos, 'editar');
        return;
    }
    
    // Extraer datos de data attributes
    const datos = {
        id: fila.dataset.pedidoId,
        numero_pedido: fila.dataset.numeroPedido,
        cliente: fila.dataset.cliente,
        estado: fila.dataset.estado,
        // ... más campos
    };
    
    abrirModalEditarPedido(pedidoId, datos, 'editar');
}
```

**Mejora:** -2 a -3 segundos (por clic de editar, no afecta carga inicial)

---

## 📈 IMPACTO ESTIMADO

| Solución | Tiempo Ahorrado | Costo |
|----------|-----------------|-------|
| 1. Eliminar N+1 | **-8 a -10s** | Bajo (30 min) |
| 2. Cache de estados | **-0.5s** | Muy bajo (10 min) |
| 3. Remover logs | **-0.3s** | Muy bajo (5 min) |
| 4. Datos en JS | **-0.5s** (ediciones) | Bajo (20 min) |
| **TOTAL** | **-9 a -11 segundos** | ~1 hora |
| **TIEMPO FINAL** | **6-8 segundos → 2-3 segundos** | ✅ |

---

## 🏗️ CAMBIOS DE ARQUITECTURA SUGERIDOS

### 1. **Repository Pattern para Queries Complejas**

Crear [app/Repositories/PedidoRepository.php](app/Repositories/PedidoRepository.php):

```php
<?php

namespace App\Repositories;

use App\Models\PedidoProduccion;
use Illuminate\Pagination\LengthAwarePaginator;

class PedidoRepository
{
    /**
     * Obtener pedidos del asesor - OPTIMIZADO
     */
    public function obtenerPorAsesor(
        int $userId,
        ?string $tipo = null,
        array $filtros = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = PedidoProduccion::where('asesor_id', $userId)
            ->select([
                'id', 'numero_pedido', 'cliente', 'estado', 
                'forma_de_pago', 'novedades', 'created_at', 'asesor_id'
            ])
            ->with([
                'prendas' => function ($q) {
                    $q->select(['id', 'pedido_produccion_id', 'nombre_prenda', 'cantidad', 'descripcion'])
                      ->with(['procesos' => function ($q2) {
                          $q2->select(['id', 'prenda_pedido_id', 'tipo_proceso', 'created_at'])
                              ->limit(3)
                              ->orderBy('created_at', 'desc');
                      }]);
                },
                'asesora' => function ($q) {
                    $q->select(['id', 'name']);
                }
            ]);

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $this->aplicarFiltroEstado($query, $filtros['estado']);
        }

        if (!empty($filtros['search'])) {
            $this->aplicarFiltroSearch($query, $filtros['search']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    private function aplicarFiltroEstado($query, string $estado): void
    {
        if ($estado === 'No iniciado') {
            $query->where('estado', 'No iniciado')
                  ->whereNull('aprobado_por_supervisor_en');
        } elseif ($estado === 'En Ejecución') {
            $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
        } else {
            $query->where('estado', $estado);
        }
    }

    private function aplicarFiltroSearch($query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('numero_pedido', 'LIKE', "%{$search}%")
              ->orWhere('cliente', 'LIKE', "%{$search}%");
        });
    }
}
```

**Ventaja:** Reutilizable, testeable, mantenible.

---

### 2. **Crear Modelo de Vista (View Model)**

Crear [app/ViewModels/PedidosListViewModel.php](app/ViewModels/PedidosListViewModel.php):

```php
<?php

namespace App\ViewModels;

use Illuminate\Pagination\LengthAwarePaginator;

class PedidosListViewModel
{
    public function __construct(
        public LengthAwarePaginator $pedidos,
        public array $estados,
        public string $filtroActual = ''
    ) {}

    /**
     * Normalizar datos para evitar múltiples queries en la vista
     */
    public function toArray(): array
    {
        return [
            'pedidos' => $this->pedidos->map(fn($p) => [
                'id' => $p->id,
                'numero' => $p->numero_pedido,
                'cliente' => $p->cliente,
                'estado' => $p->estado,
                'prendas_count' => $p->prendas->count(),
                'prendas_json' => json_encode($p->prendas),
                'asesor' => $p->asesora?->name,
            ])->toArray(),
            'estados' => $this->estados,
            'paginacion' => [
                'total' => $this->pedidos->total(),
                'per_page' => $this->pedidos->perPage(),
                'current_page' => $this->pedidos->currentPage(),
            ]
        ];
    }
}
```

**Ventaja:** Datos pre-procesados, menos lógica en la vista.

---

### 3. **API Endpoint para Editar Modal**

Crear ruta que retorna JSON con índice:

```php
// routes/api.php
Route::get('/pedidos/{id}/for-modal', [PedidoApiController::class, 'showForModal'])
    ->middleware(['auth', 'api']);

// app/Http/Controllers/Api/PedidoApiController.php
public function showForModal($id)
{
    $pedido = PedidoProduccion::where('asesor_id', Auth::id())
        ->select(['id', 'numero_pedido', 'cliente', 'estado', 'forma_de_pago', ...])
        ->with([
            'prendas' => fn($q) => $q->select([...])->with([...])
        ])
        ->findOrFail($id);

    return response()->json(['success' => true, 'data' => $pedido]);
}
```

**Ventaja:** Caché del navegador, reutilizable, escalable.

---

## 🗂️ RESUMEN DE ARCHIVOS A MODIFICAR

```
1. app/Application/Services/Asesores/ObtenerPedidosService.php
   - Agregar select() en queries
   - Agregar limit() en procesos
   - Remover logs de producción
   - Agregar cache en obtenerEstados()

2. resources/views/asesores/pedidos/index.blade.php
   - Agregar data attributes a filas de tabla
   - Modificar función editarPedido()

3. [OPCIONAL] Crear app/Repositories/PedidoRepository.php
4. [OPCIONAL] Crear app/ViewModels/PedidosListViewModel.php
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

- [ ] Agregar `select()` en `ObtenerPedidosService`
- [ ] Agregar `limit(3)` en procesos
- [ ] Remover `\Log::info()` de producción
- [ ] Agregar `\Cache::remember()` en `obtenerEstados()`
- [ ] Modificar `editarPedido()` en JavaScript
- [ ] Agregar data attributes a filas de tabla
- [ ] Agregar índice en columna `estado` (DB):
  ```sql
  ALTER TABLE pedidos_produccion ADD INDEX idx_estado (estado);
  ```
- [ ] Probar con `php artisan tinker` para verificar queries

---

## 🧪 CÓMO VERIFICAR LAS MEJORAS

### 1. Usar Laravel Debugbar

```php
// En .env
APP_DEBUG=true
DEBUGBAR_ENABLED=true
```

Ver el panel: número de queries, tiempo total.

### 2. Usar Query Logging

```php
// En controller o service
\DB::listen(function($query) {
    \Log::info('SQL: ' . $query->sql, $query->bindings);
});
```

### 3. Benchmark con Script Python

```python
import requests
import time

start = time.time()
response = requests.get('http://mundoindustrial.local/asesores/pedidos')
end = time.time()

print(f"Tiempo total: {end - start:.2f}s")
print(f"Status: {response.status_code}")
```

---

## 🎓 CONCLUSIÓN

**El problema está 100% en la capa de datos (SQL).**

- ✅ **Causa Principal:** N+1 en procesos sin limit
- ✅ **Causa Secundaria:** Logs en producción
- ✅ **Solución Rápida:** Modificar `ObtenerPedidosService` (30 minutos)
- ✅ **Solución Completa:** Implementar Repository Pattern + ViewModel (2-3 horas)

**Tiempo esperado después de optimizaciones: 2-4 segundos (80% de mejora)**

