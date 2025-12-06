# 🚀 GUÍA RÁPIDA - COMENZAR REFACTORIZACIÓN FASE 1

**Objetivo:** Extraer servicios de los God Controllers para hacerlos testeable  
**Tiempo Estimado:** 12 horas  
**Dificultad:** Media  

---

## ✅ CHECKLIST PRE-INICIO

- [ ] Branch feature/refactor-urgent creada
- [ ] Este documento leído
- [ ] ANALISIS-REFACTORIZACION-URGENTE.md leído
- [ ] Database de desarrollo actualizada
- [ ] Tests en verde (si existen)

```bash
# Crear branch
git checkout -b feature/refactor-urgent

# Verificar tests (si existen)
php artisan test
```

---

## 📋 TAREA 1: RegistroOrdenQueryService (2 horas)

### Paso 1.1: Analizar Controller Actual

```bash
# Abrir y revisar líneas 30-150 del controller
code app/Http/Controllers/RegistroOrdenController.php
```

**Qué encontrarás:**
- Línea 30-50: `$dateColumns` array y setup
- Línea 52-100: Manejo de `get_unique_values` request
- Línea 130-150: Construcción de `$query` base
- Línea 160+: Aplicación de filtros

### Paso 1.2: Crear Service Nuevo

```bash
touch app/Services/RegistroOrdenQueryService.php
```

**Contenido inicial:**

```php
<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * RegistroOrdenQueryService
 * 
 * Encapsula toda la lógica de construcción de queries para RegistroOrden
 * Responsabilidades:
 * - Construir query base con selects y with()
 * - Aplicar filtros dinámicos
 * - Obtener valores únicos de columnas
 */
class RegistroOrdenQueryService
{
    protected $dateColumns = [
        'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario', 
        'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo', 
        'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
    ];

    /**
     * Construir query base para el listado de órdenes
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function buildBaseQuery()
    {
        return PedidoProduccion::query()
            ->select([
                'id', 'numero_pedido', 'estado', 'area', 'cliente', 'cliente_id',
                'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega',
                'fecha_ultimo_proceso',
                'dia_de_entrega', 'asesor_id', 'forma_de_pago',
                'novedades', 'cotizacion_id', 'numero_cotizacion', 'aprobado_por_supervisor_en'
            ])
            ->with([
                'asesora:id,name',
                'prendas' => function($q) {
                    $q->select('id', 'numero_pedido', 'nombre_prenda', 'cantidad', 'descripcion', 
                              'descripcion_variaciones', 'cantidad_talla', 'color_id', 'tela_id', 
                              'tipo_manga_id', 'tiene_bolsillos', 'tiene_reflectivo')
                      ->with('color:id,nombre', 'tela:id,nombre,referencia', 'tipoManga:id,nombre');
                }
            ])
            ->where(function($q) {
                $q->whereNotNull('aprobado_por_supervisor_en')
                  ->orWhereNull('cotizacion_id');
            });
    }

    /**
     * Aplicar filtro de búsqueda
     * 
     * @param $query
     * @param string $searchTerm
     * @return mixed
     */
    public function applySearchFilter($query, string $searchTerm)
    {
        return $query->where(function($q) use ($searchTerm) {
            $q->where('numero_pedido', 'LIKE', '%' . $searchTerm . '%')
              ->orWhere('cliente', 'LIKE', '%' . $searchTerm . '%');
        });
    }

    /**
     * Aplicar filtros dinámicos por columna
     * 
     * @param $query
     * @param array $filters
     * @return mixed
     */
    public function applyColumnFilters($query, array $filters)
    {
        $allowedColumns = [
            'id', 'estado', 'area', 'total_de_dias_', 'dia_de_entrega', 
            'fecha_estimada_de_entrega', 'numero_pedido', 'cliente',
            'descripcion_prendas', 'cantidad', 'novedades', 'forma_de_pago', 
            'asesora', 'encargado_orden',
            'fecha_de_creacion_de_orden', 'fecha_ultimo_proceso'
        ];

        foreach ($filters as $column => $value) {
            if (empty($value) || !in_array($column, $allowedColumns)) {
                continue;
            }

            // Manejar diferentes tipos de columnas
            if ($column === 'asesora') {
                $query->whereHas('asesora', function($q) use ($value) {
                    $q->where('name', $value);
                });
            } elseif ($column === 'descripcion_prendas') {
                $query->whereHas('prendas', function($q) use ($value) {
                    $q->where('descripcion', $value);
                });
            } elseif (in_array($column, $this->dateColumns)) {
                $query->where($column, '=', $this->parseDateFilter($value));
            } else {
                $query->where($column, $value);
            }
        }

        return $query;
    }

    /**
     * Obtener valores únicos de una columna para filtros
     * 
     * @param string $column
     * @return array
     */
    public function getUniqueValues(string $column): array
    {
        $allowedColumns = [
            'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
            'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
            'fecha_estimada_de_entrega', 'fecha_ultimo_proceso', 'descripcion_prendas',
            'asesora', 'encargado_orden'
        ];

        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Column {$column} not allowed");
        }

        $values = [];

        if ($column === 'asesora') {
            $values = PedidoProduccion::join('users', 'pedidos_produccion.asesor_id', '=', 'users.id')
                ->whereNotNull('users.name')
                ->distinct()
                ->pluck('users.name')
                ->filter(function($value) { return $value !== null && $value !== ''; })
                ->values()
                ->toArray();
        } elseif ($column === 'descripcion_prendas') {
            $values = DB::table('prendas_pedido')
                ->whereNotNull('descripcion')
                ->where('descripcion', '!=', '')
                ->distinct()
                ->pluck('descripcion')
                ->filter(function($value) { return $value !== null && $value !== ''; })
                ->values()
                ->toArray();
        } else {
            $values = PedidoProduccion::whereNotNull($column)
                ->distinct()
                ->pluck($column)
                ->filter(function($value) { return $value !== null && $value !== ''; })
                ->values()
                ->toArray();
        }

        // Formatear si es fecha
        if (in_array($column, $this->dateColumns)) {
            $values = array_map(function($value) {
                try {
                    return Carbon::parse($value)->format('d/m/Y');
                } catch (\Exception $e) {
                    return $value;
                }
            }, $values);
            $values = array_values(array_unique($values));
        }

        sort($values);
        return $values;
    }

    /**
     * Parsear valor de fecha desde formato d/m/Y a BD
     * 
     * @param $value
     * @return string
     */
    protected function parseDateFilter($value): string
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            return $value;
        }
    }
}
```

### Paso 1.3: Actualizar Controller

En `app/Http/Controllers/RegistroOrdenController.php`, línea 1, agregar inyección:

```php
<?php

namespace App\Http\Controllers;

// ... otros imports ...
use App\Services\RegistroOrdenQueryService;  // ← NUEVO

class RegistroOrdenController extends Controller
{
    protected $queryService;

    public function __construct(RegistroOrdenQueryService $queryService)  // ← NUEVO
    {
        $this->queryService = $queryService;
    }

    public function index(Request $request)
    {
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->has('column')) {
            try {
                $uniqueValues = $this->queryService->getUniqueValues($request->input('column'));
                return response()->json(['unique_values' => $uniqueValues]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        }

        // Construir query base
        $query = $this->queryService->buildBaseQuery();

        // Aplicar filtro de búsqueda
        if ($request->has('search') && !empty($request->search)) {
            $query = $this->queryService->applySearchFilter($query, $request->search);
        }

        // Aplicar filtros dinámicos
        $filters = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                $column = str_replace('filter_', '', $key);
                $filters[$column] = $value;
            }
        }
        if (!empty($filters)) {
            $query = $this->queryService->applyColumnFilters($query, $filters);
        }

        // Retornar paginado
        return view('asesores.registro_orden.index', [
            'ordenes' => $query->paginate(50)
        ]);
    }

    // ✅ RESULTADO: El método index() pasó de 300+ líneas a ~50 líneas
    // ✅ La lógica ahora es testeable
    // ✅ El service es reutilizable desde otros controllers
}
```

### Paso 1.4: Test Unitario

Crear `tests/Unit/Services/RegistroOrdenQueryServiceTest.php`:

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RegistroOrdenQueryService;
use App\Models\PedidoProduccion;

class RegistroOrdenQueryServiceTest extends TestCase
{
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RegistroOrdenQueryService();
    }

    public function test_build_base_query_returns_query_builder()
    {
        $query = $this->service->buildBaseQuery();
        $this->assertNotNull($query);
        $this->assertTrue(is_object($query));
    }

    public function test_apply_search_filter_finds_by_numero_pedido()
    {
        // Crear pedido de prueba
        $pedido = PedidoProduccion::factory()->create([
            'numero_pedido' => 'TEST-12345'
        ]);

        $query = $this->service->buildBaseQuery();
        $query = $this->service->applySearchFilter($query, 'TEST-12345');

        $results = $query->get();
        $this->assertCount(1, $results);
        $this->assertEquals('TEST-12345', $results[0]->numero_pedido);
    }

    public function test_get_unique_values_returns_array()
    {
        $values = $this->service->getUniqueValues('estado');
        $this->assertIsArray($values);
    }

    public function test_get_unique_values_rejects_invalid_column()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getUniqueValues('invalid_column_name');
    }
}
```

**Ejecutar test:**

```bash
php artisan test tests/Unit/Services/RegistroOrdenQueryServiceTest.php
```

### Paso 1.5: Commit

```bash
git add app/Services/RegistroOrdenQueryService.php
git add app/Http/Controllers/RegistroOrdenController.php
git add tests/Unit/Services/RegistroOrdenQueryServiceTest.php
git commit -m "refactor(SRP): Extract RegistroOrdenQueryService

- Extraer lógica de queries del controller
- Controller ahora 300+ → 50 líneas
- Service testeable y reutilizable
- Métodos: buildBaseQuery, applySearchFilter, applyColumnFilters, getUniqueValues
- Tests unitarios incluidos"
```

---

## 📋 TAREA 2: RegistroBodegaQueryService (2 horas)

**Es prácticamente idéntica a Tarea 1, pero para `RegistroBodegaController`**

Pasos:
1. Copiar structure de `RegistroOrdenQueryService`
2. Adaptar para tabla `tabla_original_bodega`
3. Crear `app/Services/RegistroBodegaQueryService.php`
4. Refactor `app/Http/Controllers/RegistroBodegaController.php`
5. Tests

---

## 📋 TAREA 3: RegistroOrdenFilterService (2 horas)

**Separar lógica de filtros complejos**

```php
// app/Services/RegistroOrdenFilterService.php (NUEVO)
class RegistroOrdenFilterService {
    public function validateColumn($column) { ... }
    public function parseFilterValue($column, $value) { ... }
    public function buildDynamicFilters($request) { ... }
}
```

---

## 📋 TAREA 4: Consolidar Migraciones (3 horas)

**Objetivo:** Reducir 152 migraciones a schema base coherente

### Análisis

```bash
# Ver todas las migraciones
ls database/migrations/ | wc -l
# Output: 152

# Encontrar duplicadas
ls database/migrations/ | sort | uniq -d
```

### Solución

1. Crear `database/migrations_archived/` (guardar antiguas)
2. Crear migración consolidada: `2025_01_01_000_create_tables_consolidated.php`
3. Ejecutar en dev
4. Validar schema
5. Guardar antiguas como referencia

**Resultado:** Deploy de 152 → 1 migración base + mejoras posteriores

---

## 📋 TAREA 5: Tests para Nuevos Services (3 horas)

Ya iniciado en Tarea 1, expandir con:

```php
tests/Unit/Services/
├── RegistroOrdenQueryServiceTest.php ✅ Hecho
├── RegistroOrdenFilterServiceTest.php ← NUEVO
├── RegistroBodegaQueryServiceTest.php ← NUEVO
└── MigrationsTest.php ← NUEVO
```

---

## ✅ CHECKLIST DE COMPLETITUD

### Fase 1 - Registro Orden Query Service
- [ ] Service creado: `app/Services/RegistroOrdenQueryService.php`
- [ ] Controller refactorizado: línea index() ahora usa service
- [ ] Tests escritos y en verde: `tests/Unit/Services/RegistroOrdenQueryServiceTest.php`
- [ ] Código sin errores: `php artisan tinker` + pruebas manuales
- [ ] Commit hecho con mensaje descriptivo

### Fase 1 - Registro Bodega Query Service
- [ ] Service creado: `app/Services/RegistroBodegaQueryService.php`
- [ ] Controller refactorizado
- [ ] Tests escritos y en verde
- [ ] Commit hecho

### Fase 1 - Consolidar Migraciones
- [ ] Migraciones analizadas: pattern de duplicaciones identificado
- [ ] Carpeta `migrations_archived/` creada
- [ ] Migración consolidada creada
- [ ] Ejecutada en dev sin errores
- [ ] Commit hecho

### Fase 1 - Filter Service
- [ ] Service creado si aplica
- [ ] Tests creados si aplica
- [ ] Commit hecho

### Fase 1 - Testing
- [ ] Todos los tests en verde: `php artisan test`
- [ ] Coverage >= 40% para nuevos services: `php artisan test --coverage`
- [ ] Manual QA realizado

---

## 🎯 DESPUÉS DE COMPLETAR FASE 1

1. **Code Review:**
   ```bash
   git log --oneline feature/refactor-urgent..master
   # Revisar cada commit
   ```

2. **Merge a develop:**
   ```bash
   git checkout develop
   git pull origin develop
   git merge --no-ff feature/refactor-urgent
   git push origin develop
   ```

3. **Verificar en staging:**
   - Deploy cambios
   - Probar filtros en Registro Orden
   - Probar búsqueda
   - Verificar sin errores en logs

4. **Documentar aprendizajes:**
   - ¿Qué fue difícil?
   - ¿Qué fue fácil?
   - ¿Qué hacer diferente en Fase 2?

---

## 📞 SI ALGO FALLA

### Error: "Class not found"
```bash
php artisan config:cache
php artisan config:clear
```

### Error en test
```bash
php artisan test --filter=RegistroOrdenQueryServiceTest -vvv
```

### Error en migration
```bash
php artisan migrate:status  # Ver cuál falló
php artisan migrate:rollback --step=1
php artisan migrate  # Ejecutar de nuevo
```

---

## ⏱️ TIMELINE SUGERIDO

- **Día 1:** Tarea 1 (RegistroOrdenQueryService) → 2-3 horas
- **Día 2:** Tarea 2 (RegistroBodegaQueryService) → 2 horas
- **Día 3:** Tarea 3 (FilterService) → 2 horas
- **Día 4:** Tarea 4 (Migraciones) → 3 horas
- **Día 5:** Tarea 5 (Testing completo) → 3 horas

**Total:** ~12 horas en 5 días ✅

---

## 🚀 ¿LISTO?

Ejecuta esto para confirmar que estás listo:

```bash
# 1. Crear branch
git checkout -b feature/refactor-urgent

# 2. Verificar tests actuales
php artisan test

# 3. Crear carpeta de services si no existe
mkdir -p app/Services

# 4. Comenzar con Tarea 1
code app/Services/RegistroOrdenQueryService.php
```

**¡COMENZAMOS!**

---

*Guía creada: Enero 2025*  
*Framework: Laravel v10*  
*Tiempo total: 12 horas (Fase 1)*
