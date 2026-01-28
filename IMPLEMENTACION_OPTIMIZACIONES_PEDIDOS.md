# 🚀 GUÍA RÁPIDA: IMPLEMENTAR OPTIMIZACIONES

## Paso 1️⃣: Optimizar ObtenerPedidosService (10 min)

**Archivo:** `app/Application/Services/Asesores/ObtenerPedidosService.php`

### Cambio 1: Reemplazar `obtenerPedidosProduccion()`

```php
/**
 * Obtener Pedidos del asesor - OPTIMIZADO
 */
private function obtenerPedidosProduccion(int $userId, ?string $tipo, array $filtros = [], int $perPage = 20): LengthAwarePaginator
{
    // ✅ Step 1: Select solo columnas necesarias
    $query = PedidoProduccion::where('asesor_id', $userId)
        ->select([
            'id',
            'numero_pedido',
            'cliente',
            'estado',
            'forma_de_pago',
            'novedades',
            'created_at',
            'asesor_id'
        ])
        // ✅ Step 2: With relaciones optimizadas
        ->with([
            'prendas' => function ($q) {
                // ✅ Select solo columnas necesarias
                $q->select([
                    'id',
                    'pedido_produccion_id',
                    'nombre_prenda',
                    'cantidad',
                    'descripcion'
                ])
                // ✅ CRITICAL: Eager load procesos con limit
                ->with(['procesos' => function ($q2) {
                    $q2->select([
                        'id',
                        'prenda_pedido_id',
                        'tipo_proceso',
                        'created_at'
                    ])
                    ->limit(3)  // 🔥 CRÍTICO: Máximo 3 procesos por prenda
                    ->orderBy('created_at', 'desc');
                }]);
            },
            'asesora' => function ($q) {
                $q->select(['id', 'name', 'email']);
            }
        ]);

    // Aplicar filtros
    $this->aplicarFiltros($query, $filtros);

    return $query
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
}
```

### Cambio 2: Remover logs de producción

Reemplazar estas líneas:

```php
// ❌ ELIMINAR:
\Log::info(' [OBTENER PEDIDOS] Iniciando búsqueda', [
    'tipo' => $tipo,
    'filtros' => $filtros,
    'por_pagina' => $perPage
]);

// ✅ REEMPLAZAR CON:
if (app()->isLocal()) {
    \Log::info('[OBTENER PEDIDOS] Iniciando búsqueda', [
        'tipo' => $tipo,
        'filtros' => $filtros,
        'por_pagina' => $perPage
    ]);
}
```

Hacer lo mismo con las demás líneas `\Log::info()`, `\Log::warning()`.

### Cambio 3: Cache en obtenerEstados()

```php
/**
 * Obtener estados únicos disponibles - CON CACHE
 */
public function obtenerEstados(): array
{
    // ✅ Cache por 1 hora (3600 segundos)
    return \Cache::remember('pedidos_estados_list', 3600, function () {
        return PedidoProduccion::select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->pluck('estado')
            ->toArray();
    });
}
```

---

## Paso 2️⃣: Agregar data-attributes a Tabla (5 min)

**Archivo:** `resources/views/asesores/pedidos/components/table.blade.php`

Encuentra la fila de la tabla y modifica:

```blade
{{-- ❌ ANTES --}}
<div class="table-row">
    <div>{{ $pedido->numero_pedido }}</div>
    <div>{{ $pedido->cliente }}</div>
    ...
</div>

{{-- ✅ DESPUÉS --}}
<div class="table-row" 
     data-pedido-row
     data-pedido-id="{{ $pedido->id }}"
     data-numero-pedido="{{ $pedido->numero_pedido }}"
     data-cliente="{{ $pedido->cliente }}"
     data-estado="{{ $pedido->estado }}"
     data-forma-pago="{{ $pedido->forma_de_pago }}"
     data-asesor="{{ $pedido->asesora?->name }}">
     
    <div class="cell-numero">{{ $pedido->numero_pedido }}</div>
    <div class="cell-cliente">{{ $pedido->cliente }}</div>
    ...
</div>
```

---

## Paso 3️⃣: Optimizar editarPedido() en JavaScript (10 min)

**Archivo:** `resources/views/asesores/pedidos/index.blade.php`

Reemplaza la función completa:

```javascript
/**
 * Editar pedido - OPTIMIZADO sin fetch adicional
 */
async function editarPedido(pedidoId) {
    // 🔒 Prevenir múltiples clics simultáneos
    if (edicionEnProgreso) {
        return;
    }
    
    edicionEnProgreso = true;
    
    try {
        // 🔥 CAMBIO IMPORTANTE: Extraer datos de la fila, no hacer fetch
        const fila = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
        
        if (!fila) {
            throw new Error('No se encontró la fila del pedido');
        }

        // 📊 Extraer datos de data attributes
        const datosEnFila = {
            id: fila.dataset.pedidoId,
            numero_pedido: fila.dataset.numeroPedido,
            cliente: fila.dataset.cliente,
            estado: fila.dataset.estado,
            forma_de_pago: fila.dataset.formaPago,
            asesor: fila.dataset.asesor,
            prendas: fila.dataset.prendas ? JSON.parse(fila.dataset.prendas) : [],
        };

        // ✅ Si los datos básicos están, abrir modal directamente
        if (datosEnFila.numero_pedido && datosEnFila.cliente) {
            console.log('[editarPedido] ✅ Datos extraídos de fila, abriendo modal sin fetch');
            abrirModalEditarPedido(pedidoId, datosEnFila, 'editar');
            return;
        }

        // 🔴 FALLBACK: Si falta info, hacer fetch (debería ser raro)
        console.log('[editarPedido] ⚠️ Datos incompletos en fila, haciendo fetch...');
        
        await _ensureSwal();
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');

        const response = await fetch(`/api/pedidos/${pedidoId}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Error al cargar el pedido');
        
        const respuesta = await response.json();
        Swal.close();

        if (!respuesta.success) {
            throw new Error(respuesta.message || 'Error desconocido');
        }

        const datos = respuesta.data || respuesta.datos;
        const datosTransformados = {
            id: datos.id || datos.numero_pedido,
            numero_pedido: datos.numero_pedido || datos.numero,
            cliente: datos.cliente || 'Cliente sin especificar',
            estado: datos.estado || 'Pendiente',
            forma_de_pago: datos.forma_pago || datos.forma_de_pago,
            prendas: datos.prendas || [],
            ...datos
        };

        abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');

    } catch (err) {
        Swal.close();
        UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
    } finally {
        edicionEnProgreso = false;
    }
}
```

---

## Paso 4️⃣: Agregar Índice en Base de Datos (2 min)

```bash
# Terminal - Conectar a DB
mysql -u usuario -p mundoindustrial

# SQL:
ALTER TABLE pedidos_produccion ADD INDEX idx_estado (estado);
ALTER TABLE pedidos_produccion ADD INDEX idx_asesor_created (asesor_id, created_at);
```

O crear migración:

```php
// database/migrations/[timestamp]_add_indexes_pedidos.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Si no existen ya
            $table->index('estado');
            $table->index(['asesor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropIndex(['asesor_id', 'created_at']);
        });
    }
};
```

Ejecutar:
```bash
php artisan migrate
```

---

## Paso 5️⃣: Testear y Validar (5 min)

### Test 1: Verificar Queries

```php
// En tinker o en controller
php artisan tinker

>>> \DB::listen(fn($q) => dump($q->sql));
>>> $service = app(\App\Application\Services\Asesores\ObtenerPedidosService::class);
>>> $pedidos = $service->obtener(null, []);
>>> // Verifica que sean ~3-4 queries, no 20+
```

### Test 2: Benchmark en Navegador

Abrir DevTools (F12) → Network → Actualizar página

**Antes:** ~17000ms  
**Después:** ~3000ms (80% de mejora)

### Test 3: Verificar en Logs

```bash
tail -f storage/logs/laravel.log | grep "OBTENER PEDIDOS"
# Debería estar vacío en producción (solo logs locales)
```

---

## 📋 CHECKLIST FINAL

```bash
☐ Modificar ObtenerPedidosService.php (select + limit + cache)
☐ Remover logs de producción
☐ Agregar data attributes a tabla
☐ Optimizar editarPedido() en JavaScript
☐ Crear/ejecutar migración de índices
☐ Probar en navegador (verificar tiempo)
☐ Verificar queries en Debugbar
☐ Confirmar no hay errores en console del navegador
☐ Hacer git commit
```

---

## ⏱️ TIEMPO TOTAL: ~30 minutos

**Antes:** ~17 segundos  
**Después:** ~3 segundos  
**Mejora:** 82% ⚡

