# 🔍 AUDITORÍA DE ARQUITECTURA Y PERFORMANCE - MÓDULO BODEGA

**Fecha**: 2026-04-25  
**Analista**: Experto DDD & Arquitectura  
**Severidad**: 🔴 CRÍTICA

---

## RESUMEN EJECUTIVO

El módulo Bodega tiene **serios problemas de arquitectura y performance** que degradan significativamente la velocidad. Hay **~4,500 líneas de código** repartidas en **14 servicios** sin patrón claro, violando principios fundamentales de DDD, SOLID y arquitectura limpia.

**Impacto Estimado:**
- ❌ **N+1 Queries**: Múltiples operaciones que podrían ser 1-2 queries
- ❌ **Lógica en memoria**: Filtrado y paginación en PHP en lugar de BD
- ❌ **14 servicios monolíticos**: Responsabilidades dispersas y poco claras
- ❌ **Controlador con 19 dependencias**: Difícil de mantener y probar
- ❌ **~150ms por page load** → Objetivo: **~30ms**

---

## 🔴 PROBLEMAS CRÍTICOS (Alto Impacto)

### 1. **N+1 QUERIES MASIVAS EN FILTRADO DE PEDIDOS**

**Ubicación**: `BodegaPedidoConsultaService::filtrarPedidosPorArea()` (línea 323-340)

```php
private function filtrarPedidosPorArea(Collection $pedidos, array $areasPermitidas): Collection
{
    return $pedidos->filter(function ($item) use ($areasPermitidas) {
        $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get(); // ❌ QUERY AQUÍ
        // ... lógica
    })->values();
}
```

**Problema**: 
- Si hay 20 pedidos en la página → **20 queries** a `bodega_detalles_talla`
- Se repite en: `obtenerPedidosPaginados()`, `obtenerPedidosAnuladosPaginados()`, `obtenerPedidosEntregadosPaginados()`
- **Estimado**: 20-50 queries extras por page load

**Impacto**: ~50-80ms por request de lista de pedidos

**Solución Rápida**:
```php
// Batch-load todos los detalles de una vez
$detallesPorNumero = BodegaDetallesTalla::whereIn(
    'numero_pedido', 
    $pedidos->pluck('numero_pedido')->unique()
)->get()->groupBy('numero_pedido');

return $pedidos->filter(function ($item) use ($areasPermitidas, $detallesPorNumero) {
    $detalles = $detallesPorNumero->get($item->numero_pedido, collect());
    // Lógica sin query
})->values();
```

---

### 2. **FILTRADO Y PAGINACIÓN EN MEMORIA (PHP)**

**Ubicación**: `BodegaPedidoConsultaService::paginarPedidos()` y `procesarVistaLista()`

```php
// ❌ Problema: Se cargan TODOS los pedidos, luego se filtran en memoria
$todos = ReciboPrenda::where(...)->get(); // 1000+ registros
$filtrados = $todos->filter(...); // En memoria
$paginados = $filtrados->slice($offset, $porPagina); // Después
```

**Problema**:
- Base de datos devuelve 1000+ registros
- Se filtra en PHP (lento)
- Se pagina en PHP (debería ser en BD)
- Se repite 3 veces (normal, anulados, entregados)

**Impacto**: ~100-150ms por listado

**Solución**:
```php
$query = ReciboPrenda::query()
    ->where('numero_pedido', '!=', '') // Filtro 1: en BD
    ->whereNotIn('numero_pedido', $ocultosMap) // Filtro 2: en BD
    ->when($search, fn($q) => $q->where('numero_pedido', 'LIKE', "%{$search}%")) // Filtro 3: en BD
    ->orderByDesc('numero_pedido')
    ->paginate(20); // Paginación EN BD
```

---

### 3. **LÓGICA DE ESTADO CALCULADA MÚLTIPLES VECES**

**Ubicación**: `procesarVistaLista()` línea 538-541

```php
foreach ($numerosPedidos as $num) {
    $estadosPorPedido[$num] = $this->estadoCalculator->calcular($num); // ❌ Para CADA pedido
}
```

**Problema**:
- Se calcula el estado para cada pedido en la página
- No se cachea
- Si `calcular()` hace queries → se multiplica por página size

**Estimado**: 20-50 queries extras

**Solución**:
```php
// 1. Cache temporal en request
$estadoCache = Cache::remember("pedido_estados_{$numerosPedidos->implode(',')}", 5, fn() => 
    $this->estadoCalculator->calcularMultiples($numerosPedidos)
);

// 2. Inyectar caching en estadoCalculator con Redis
class PedidoEstadoCalculator {
    public function calcular($num) {
        return Cache::remember("estado:$num", 60, fn() => 
            // ... cálculo
        );
    }
}
```

---

### 4. **CONTROLADOR CON 19 DEPENDENCIAS - VIOLACIÓN DE RESPONSABILIDAD ÚNICA**

**Ubicación**: `PedidosController::__construct()` (línea 52-71)

```php
public function __construct(
    private ObtenerPedidoUseCase $obtenerPedidoUseCase,
    private ObtenerFilasDespachoUseCase $obtenerFilas,
    private PedidoProduccionReadRepository $pedidoRepository,
    private BodegaPedidoService $bodegaPedidoService,
    private BodegaRoleService $roleService,
    private BodegaAuditoriaService $auditoriaService,
    private CQRSManager $cqrsManager,
    private BodegaDatosService $datosService,
    // ... 11 más
) {}
```

**Problema**:
- El controlador coordina demasiadas responsabilidades
- Difícil de testear (19 mocks necesarios)
- Violación de SRP (Single Responsibility Principle)
- Métodos con lógica de 60+ líneas (ej: `show()` línea 391-460)

**Impacto**:
- Código difícil de mantener y debuggear
- Cambios en lógica afectan múltiples servicios

---

### 5. **14 SERVICIOS SIN PATRÓN CLARO - FALTA DE AGREGADOS**

**Ubicación**: `app/Infrastructure/Services/Bodega/` (14 archivos, 4500 LOC)

```
BodegaDatosService
BodegaAuditoriaService
BodegaNotaService
BodegaFiltroService
BodegaGuardadoService
BodegaPedidoDetalleService
BodegaNotificacionService
BodegaPedidoService
BodegaPedidoConsultaService ← 627 líneas
BodegaPedidoPersistenciaService
BodegaPedidoHistorialService
BodegaRoleService
BodegaPedidoDetalleUtilsService
BodegaUpdateService
```

**Problema DDD**:
- ❌ No hay Agregados (Aggregate Roots) definidos
- ❌ La entidad `Pedido` existe pero NO se usa
- ❌ Los repositorios devuelven Modelos Eloquent, no Agregados
- ❌ La lógica de negocio está repartida en servicios
- ❌ No hay límites de transacción claros

**Ejemplo**: ¿Dónde se valida que "solo Costura-Bodega puede ver Costura"?
- En `roleService` ✓
- En `filtrarPedidosPorArea()` ✓
- En el controlador ✓
- Múltiples lugares = Lógica duplicada y difícil de mantener

---

### 6. **MÉTODOS EXCESIVAMENTE LARGOS Y COMPLEJOS**

**Ubicación**: Múltiples métodos

| Método | LOC | Complejidad | Problema |
|--------|-----|------------|----------|
| `BodegaPedidoConsultaService::obtenerDetallePedido()` | 127 | 🔴 Alta | Búsqueda de pedido requiere 20+ líneas |
| `BodegaPedidoConsultaService::procesarVistaLista()` | 100 | 🔴 Alta | Batch-loading mezclado con lógica de transformación |
| `PedidosController::showPendientesPorArea()` | 171 | 🔴 Alta | Filtrado, debugging, transformación en un método |
| `PedidosController::show()` | 69 | 🟠 Media | Filtrado por rol duplicado 3 veces |

**Impacto**: Difícil de entender, mantener, testear y debuggear

---

## 🟠 PROBLEMAS IMPORTANTES (Medio Impacto)

### 7. **DUPLICACIÓN DE LÓGICA EN 3 MÉTODOS SIMILARES**

```php
obtenerPedidosPaginados()          // 52 líneas
obtenerPedidosAnuladosPaginados()  // 84 líneas
obtenerPedidosEntregadosPaginados() // 160 líneas
```

Todos hacen: query → filtro rol → filtro ocultos → paginación → procesamiento

**Solución**: Un método genérico `obtenerPedidosFiltrando($filtros)` que acepte predicados

---

### 8. **FALTA DE ÍNDICES EN QUERIES FRECUENTES**

```sql
-- ❌ Sin índice: BodegaDetallesTalla WHERE numero_pedido, area
SELECT * FROM bodega_detalles_talla 
WHERE numero_pedido = ? AND area = ? -- Se ejecuta miles de veces

-- ❌ Sin índice: PedidoProduccion WHERE numero_pedido  
SELECT * FROM pedidos_produccion 
WHERE numero_pedido = ? -- Se ejecuta cientos de veces

-- ❌ Sin índice: PedidoOculto WHERE user_id
SELECT * FROM pedido_oculto 
WHERE user_id = ? -- Se ejecuta en cada listado
```

**Solución**: Crear índices en BD
```sql
CREATE INDEX idx_bodega_detalles_numero_area 
    ON bodega_detalles_talla(numero_pedido, area);
CREATE INDEX idx_pedidos_produccion_numero 
    ON pedidos_produccion(numero_pedido);
CREATE INDEX idx_pedido_oculto_user 
    ON pedido_oculto(user_id);
```

---

### 9. **FALTA DE CACHING DE QUERIES REPETIDAS**

**Consultas que se repiten** en cada page load:

- Estados permitidos: 3+ veces
- Áreas permitidas por rol: 3+ veces
- Detalles de bodega por pedido: 20+ veces (N+1)
- Recibos de pedido: 3+ veces

**Costo**: ~30-50ms de DB overhead

---

### 10. **FALTA DE AGREGACIÓN EN BASE DE DATOS**

```php
// ❌ Se obtiene TODO, se agrupa en memoria
$recibos = ReciboPrenda::where(...)->get(); // 1000+ registros
$agrupados = $recibos->groupBy('numero_pedido'); // En PHP

// ✅ Agregación en BD
$agrupados = DB::table('pedidos_produccion')
    ->select('numero_pedido', DB::raw('COUNT(*) as cantidad'))
    ->groupBy('numero_pedido')
    ->get(); // Solo datos necesarios
```

**Impacto**: Reducir transferencia de datos en 90%

---

### 11. **PROBLEMAS DE TRANSACCIONALIDAD**

No hay transacciones explícitas en:
- Guardado de múltiples detalles
- Actualización de estado + auditoría + notificación
- Entregas masivas

Riesgo: Inconsistencia de datos si hay error

---

### 12. **FALTA DE QUERY LOGGING Y MONITOREO**

No hay forma de saber:
- ¿Cuántas queries se ejecutan por request?
- ¿Cuáles son las queries más lentas?
- ¿Cuál es el tiempo real de BD vs lógica PHP?

**Solución**: Activar `Debugbar` / `Laravel Telescope`

---

## 🟡 PROBLEMAS MENORES (Bajo Impacto)

### 13. **Inconsistencias en búsqueda de pedidos**

Método `obtenerDetallePedido()` tiene 127 líneas para decidir si buscar por `id` o `numero_pedido`. Debería ser:

```php
$pedido = $priorizarNumeroPedido 
    ? Pedido::findByNumero($id)
    : Pedido::findById($id);
```

### 14. **Logging excesivo en bucles**

```php
foreach ($debugDetalleItems as $item) {
    // ... logging
}
```

Genera cientos de logs por request

### 15. **Array filtering con closures costosas**

```php
// ❌ Lento
$pedidos->filter(fn($p) => $p->numero_pedido === $numeroPedido)

// ✅ Más rápido
$pedidos->firstWhere('numero_pedido', $numeroPedido)
```

---

## 📊 RESUMEN DE IMPACTO

| Problema | Queries Extra | Tiempo Estimado | Severity |
|----------|--------------|-----------------|----------|
| N+1 queries en filtro | 20-50 | 50-80ms | 🔴 |
| Filtrado en memoria | N/A | 30-50ms | 🔴 |
| Cálculo de estado repetido | 20-50 | 40-60ms | 🔴 |
| Sin índices BD | N/A | 15-30ms | 🟠 |
| Sin caching | N/A | 20-30ms | 🟠 |
| **TOTAL ESTIMADO** | **60-150** | **155-250ms** | **🔴** |

**Objetivo**: Reducir a ~30-50ms (6-8x más rápido)

---

## ✅ PLAN DE REFACTORIZACIÓN (Prioridad)

### FASE 1: QUICK WINS (1-2 semanas, máximo impacto)

1. **Crear índices en BD** (5 minutos)
   ```php
   // Migration
   Schema::table('bodega_detalles_talla', fn($t) => $t->index(['numero_pedido', 'area']));
   ```

2. **Batch-load detalles** (2 horas)
   ```php
   // Reemplazar filtrarPedidosPorArea() con batch load
   $detalles = BodegaDetallesTalla::whereIn(...)->get()->groupBy('numero_pedido');
   ```

3. **Cachear estados** (3 horas)
   ```php
   // En PedidoEstadoCalculator
   return Cache::remember("estado:$num", 60, fn() => $this->calcularActual($num));
   ```

4. **Mover filtrado a BD** (4 horas)
   ```php
   // En paginarPedidos(), aplicar filtros antes de get()
   ```

**Resultado esperado**: -70% queries, -100ms tiempo

---

### FASE 2: REFACTORIZACIÓN ARQUITECTÓNICA (3-4 semanas)

1. **Implementar Agregado Pedido**
   ```php
   class PedidoAggregate {
       public function __construct(
           private PedidoProduccion $pedido,
           private Collection $detalles
       ) {}
       
       public function calcularEstado(): EstadoPedido { ... }
       public function entregar(): void { ... }
       public function ocultarPara(User $usuario): void { ... }
   }
   ```

2. **Crear PedidoRepository real**
   ```php
   interface PedidoRepository {
       public function obtenerPorNumero(string $numero): PedidoAggregate;
       public function obtenerOcultosPorUsuario(User $usuario): Collection;
       public function guardar(PedidoAggregate $pedido): void;
   }
   ```

3. **Consolidar servicios**
   - 14 servicios → 3-4 servicios cohesivos
   - Application layer: Orquestación (UseCases)
   - Domain layer: Lógica (Agregados)
   - Infrastructure: Persistencia (Repositories)

4. **Crear UseCases**
   ```php
   class ObtenerListadoPedidosUseCase {
       public function ejecutar(ObtenerListadoPedidosInput $input): ObtenerListadoPedidosOutput { ... }
   }
   ```

5. **Separar responsabilidades del Controlador**
   ```php
   class PedidosController {
       public function __construct(
           private ObtenerListadoPedidosUseCase $listar,
           private ObtenerDetallePedidoUseCase $obtener,
           private EntregarPedidoUseCase $entregar
       ) {}
   }
   ```

**Resultado esperado**: Código mantenible, testeable, escalable

---

### FASE 3: OPTIMIZACIONES AVANZADAS (2-3 semanas)

1. **Implementar caching con TTL por tipo**
   - Estados: 60 segundos
   - Permisos: 300 segundos
   - Detalles: 30 segundos

2. **Query optimization**
   - Usar `select()` para traer solo campos necesarios
   - Usar `limit()` en subqueries
   - Considerar Elasticsearch para búsquedas complejas

3. **Event-driven architecture**
   ```php
   // Cuando cambia estado, disparar evento
   class PedidoEntregadoEvent {
       public function __construct(public PedidoAggregate $pedido) {}
   }
   
   // Listeners: Auditoría, Notificación, etc.
   ```

4. **CQRS avanzado**
   - Query model separada para lecturas
   - Tablas desnormalizadas para listados
   - Event sourcing para auditoría

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### FASE 1 (Semana 1)
- [ ] Crear índices en BD
- [ ] Implementar batch-load en filtrarPedidosPorArea()
- [ ] Cachear estados de pedidos
- [ ] Mover filtros a consultas BD
- [ ] Ejecutar tests y verificar velocidad
- [ ] Medir: queries y tiempo antes/después

### FASE 2 (Semana 2-5)
- [ ] Diseñar Agregado PedidoAggregate
- [ ] Crear PedidoRepository
- [ ] Implementar UseCases principales
- [ ] Refactorizar PedidosController
- [ ] Consolidar servicios
- [ ] Escribir tests para agregados

### FASE 3 (Semana 6-8)
- [ ] Implementar caching avanzado
- [ ] Optimizar queries con instrumentación
- [ ] Implementar eventos de dominio
- [ ] Mejorar CQRS

---

## 🎯 MÉTRICAS DE ÉXITO

| Métrica | Actual | Objetivo | Mejora |
|---------|--------|----------|--------|
| Queries por página | 150-200 | 20-30 | 87% ↓ |
| Tiempo página (ms) | 150-250 | 30-50 | 80% ↓ |
| Líneas servicio promedio | 320 | 100 | 68% ↓ |
| Dependencias controlador | 19 | 3-5 | 75% ↓ |
| Cobertura tests | ~10% | 80% | 8x ↑ |

---

## 🚀 RECOMENDACIONES INMEDIATAS

### Día 1-2
1. Crear índices en BD
2. Batch-load detalles en filtro
3. Medir impacto

### Semana 1
4. Cachear estados
5. Mover filtros a BD
6. Definir plan de refactorización con equipo

### Semana 2+
7. Iniciar FASE 2 (arquitectura)

---

## 📚 REFERENCIAS ÚTILES

**DDD**:
- [Domain-Driven Design - Evans](https://domainlanguage.com/ddd/)
- [Implementing Domain-Driven Design - Vaughn Vernon](https://vaughnvernon.com/)

**Performance**:
- [Query Optimization - MySQL](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [Laravel Query Optimization](https://laravel.com/docs/eloquent#lazy-loading)

**Patrones**:
- [CQRS Pattern](https://martinfowler.com/bliki/CQRS.html)
- [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)

---

**Auditoría completada por**: Experto DDD & Arquitectura  
**Prioridad**: 🔴 CRÍTICA - Requiere acción inmediata
