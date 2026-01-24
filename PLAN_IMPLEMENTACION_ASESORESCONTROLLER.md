#  PLAN DE IMPLEMENTACIÓN: REFACTOR ASESORESCONTROLLER

**Duración estimada**: 14-16 horas  
**Sprints necesarios**: 2 sprints (si son de 8 horas)  
**Complejidad**: Media-Alta  

---

## FASES EJECUTABLES

### ⏱️ FASE 1: ELIMINACIÓN DE DUPLICACIÓN (1-2 horas)

**Objetivo**: Eliminar ambigüedad del agregado duplicado

#### Paso 1.1: Verificar qué importa el agregado legacy

```bash
# Buscar imports de Agregado/
grep -r "Agregado\\\\PedidoProduccionAggregate" app/

# Esperado: 0 resultados (no debe estar en uso)
```

#### Paso 1.2: Eliminar la carpeta

```bash
# Backup (por si acaso)
cp -r app/Domain/PedidoProduccion/Agregado/ app/Domain/PedidoProduccion/Agregado.bak/

# Eliminar
rm -rf app/Domain/PedidoProduccion/Agregado/
```

#### Paso 1.3: Verificar tests

```bash
# Ejecutar tests de Domain
php artisan test tests/Unit/Domain/PedidoProduccion/

# Esperado:  Todos pasan
```

#### Paso 1.4: Commit

```bash
git add -A
git commit -m "[CLEANUP] Eliminar PedidoProduccionAggregate legacy (Agregado/)

- Removida carpeta app/Domain/PedidoProduccion/Agregado/
- Mantenida versión correcta: Aggregates/
- Eliminada ambigüedad de namespace
- Reducida deuda técnica: -1 clase duplicada

BREAKING: Ninguno (no estaba en uso)
"
```

---

### ⏱️ FASE 2: LIMPIAR SERVICIOS NO USADOS (1 hora)

**Objetivo**: Remover 7 servicios legacy sin usar del controlador

#### Paso 2.1: Abrir AsesoresController

```
Archivo: app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php
```

#### Paso 2.2: Remover imports (líneas ~8-25)

```diff
- use App\Application\Services\Asesores\EliminarPedidoService;
- use App\Application\Services\Asesores\ObtenerFotosService;
- use App\Application\Services\Asesores\ObtenerPedidosService;
- use App\Application\Services\Asesores\GuardarPedidoProduccionService;
- use App\Application\Services\Asesores\ConfirmarPedidoService;
- use App\Application\Services\Asesores\ActualizarPedidoService;
- use App\Application\Services\Asesores\ObtenerPedidoDetalleService;
```

#### Paso 2.3: Remover properties (líneas ~50-66)

```diff
- protected EliminarPedidoService $eliminarPedidoService;
- protected ObtenerFotosService $obtenerFotosService;
- protected ObtenerPedidosService $obtenerPedidosService;
- protected GuardarPedidoProduccionService $guardarPedidoProduccionService;
- protected ConfirmarPedidoService $confirmarPedidoService;
- protected ActualizarPedidoService $actualizarPedidoService;
- protected ObtenerPedidoDetalleService $obtenerPedidoDetalleService;
```

#### Paso 2.4: Remover inyecciones en constructor (líneas ~78-126)

```diff
  public function __construct(
      PedidoProduccionRepository $pedidoProduccionRepository,
      DashboardService $dashboardService,
      NotificacionesService $notificacionesService,
      PerfilService $perfilService,
-     EliminarPedidoService $eliminarPedidoService,
-     ObtenerFotosService $obtenerFotosService,
-     AnularPedidoService $anularPedidoService,
-     ObtenerPedidosService $obtenerPedidosService,
      ObtenerProximoPedidoService $obtenerProximoPedidoService,
      ObtenerDatosFacturaService $obtenerDatosFacturaService,
      ObtenerDatosRecibosService $obtenerDatosRecibosService,
      ...
  ) {
      // Remover asignaciones
-     $this->eliminarPedidoService = $eliminarPedidoService;
-     $this->obtenerFotosService = $obtenerFotosService;
      ...
  }
```

#### Paso 2.5: Ejecutar tests

```bash
php artisan test tests/Feature/Http/Controllers/AsesoresControllerTest.php

# Esperado:  Todos pasan
```

#### Paso 2.6: Commit

```bash
git add -A
git commit -m "[CLEANUP] Remover servicios legacy no usados de AsesoresController

Servicios eliminados:
- EliminarPedidoService (no se usaba)
- ObtenerFotosService (no se usaba)
- ObtenerPedidosService (no se usaba)
- GuardarPedidoProduccionService (no se usaba)
- ConfirmarPedidoService (no se usaba)
- ActualizarPedidoService (no se usaba)
- ObtenerPedidoDetalleService (no se usaba)

Resultado:
- Inyecciones reducidas: 23 → 16
- Constructor más limpio
- Mayor claridad de dependencias

BREAKING: Ninguno
"
```

---

### ⏱️ FASE 3: REFACTORIZAR MÉTODOS CRÍTICOS (2-3 horas)

**Objetivo**: Refactorizar anularPedido() y métodos de datos

#### Paso 3.1: Refactorizar anularPedido()

**Ubicación**: Línea ~635

**Antes**:
```php
public function anularPedido(Request $request, $id)
{
    $request->validate([
        'novedad' => 'required|string|min:10|max:500',
    ]);

    try {
        $pedido = $this->anularPedidoService->anular($id, $request->novedad);
        
        return response()->json([
            'success' => true,
            'message' => 'Pedido anulado correctamente',
            'pedido' => $pedido,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}
```

**Después**:
```php
public function anularPedido(Request $request, $id)
{
    try {
        $validated = $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ]);

        // Crear DTO para el Use Case
        $dto = AnularProduccionPedidoDTO::fromRequest(
            (string)$id,
            ['razon' => $validated['novedad']]
        );

        // Usar el nuevo Use Case DDD
        $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutar($dto);

        return response()->json([
            'success' => true,
            'message' => 'Pedido anulado correctamente',
            'pedido' => $pedidoAnulado,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}
```

**Cambios necesarios**:
1.  AnularProduccionPedidoUseCase ya está inyectado
2.  AnularProduccionPedidoDTO ya existe
3. ✓ Solo reemplazar servicio por Use Case

#### Paso 3.2: Refactorizar obtenerDatosFactura()

**Ubicación**: Línea ~680

**Antes**:
```php
public function obtenerDatosFactura($id)
{
    try {
        $datos = $this->obtenerDatosFacturaService->obtener($id);
        return response()->json($datos);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}
```

**Después**:
```php
public function obtenerDatosFactura($id)
{
    try {
        // Usar el repositorio directamente (sin servicio wrapper)
        $datos = $this->pedidoProduccionRepository->obtenerDatosFactura((int)$id);
        return response()->json($datos);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error obteniendo datos de la factura: ' . $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}
```

**Cambios necesarios**:
1.  Remover `$this->obtenerDatosFacturaService`
2. ✓ Usar `$this->pedidoProduccionRepository` (ya inyectado)

#### Paso 3.3: Refactorizar obtenerDatosRecibos()

**Ubicación**: Línea ~695

**Análogo a obtenerDatosFactura()**

```php
public function obtenerDatosRecibos($id)
{
    try {
        $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos((int)$id);
        return response()->json($datos);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
        ], $e->getCode() ?: 500);
    }
}
```

#### Paso 3.4: Remover servicios innecesarios

```diff
- use App\Application\Services\Asesores\AnularPedidoService;
- use App\Application\Services\Asesores\ObtenerDatosFacturaService;
- use App\Application\Services\Asesores\ObtenerDatosRecibosService;

- protected AnularPedidoService $anularPedidoService;
- protected ObtenerDatosFacturaService $obtenerDatosFacturaService;
- protected ObtenerDatosRecibosService $obtenerDatosRecibosService;

// En constructor:
- AnularPedidoService $anularPedidoService,
- ObtenerDatosFacturaService $obtenerDatosFacturaService,
- ObtenerDatosRecibosService $obtenerDatosRecibosService,

- $this->anularPedidoService = $anularPedidoService;
- $this->obtenerDatosFacturaService = $obtenerDatosFacturaService;
- $this->obtenerDatosRecibosService = $obtenerDatosRecibosService;
```

#### Paso 3.5: Tests

```bash
php artisan test tests/Feature/Http/Controllers/Asesores/AsesoresControllerTest.php

# Tests de: anularPedido, obtenerDatosFactura, obtenerDatosRecibos
```

#### Paso 3.6: Commit

```bash
git add -A
git commit -m "[REFACTOR] Refactorizar métodos críticos de AsesoresController

Cambios:
- anularPedido(): Usar AnularProduccionPedidoUseCase en lugar de servicio legacy
- obtenerDatosFactura(): Usar repositorio directamente
- obtenerDatosRecibos(): Usar repositorio directamente

Servicios removidos:
- AnularPedidoService (duplicado con Use Case)
- ObtenerDatosFacturaService (wrapper innecesario)
- ObtenerDatosRecibosService (wrapper innecesario)

Resultado:
- Métodos ahora consistentes con patrón DDD
- Inyecciones reducidas: 16 → 13
- Eliminada 1 capa de abstracción innecesaria

Tests:  Pasando
"
```

---

### ⏱️ FASE 4: REFACTORIZAR MÉTODOS ADICIONALES (2-3 horas)

**Objetivo**: agregarPrendaSimple() y getNextPedido()

#### Paso 4.1: Refactorizar agregarPrendaSimple()

**Ubicación**: Línea ~710

**Antes**:
```php
public function agregarPrendaSimple(Request $request, $pedidoId)
{
    try {
        $validated = $request->validate([
            'nombre_prenda' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $pedido = PedidoProduccion::find($pedidoId);
        if (!$pedido) {
            return response()->json([
                'error' => 'Pedido no encontrado'
            ], 404);
        }

        if ($pedido->asesor_id !== Auth::id()) {
            return response()->json([
                'error' => 'No tienes permiso para agregar prendas a este pedido'
            ], 403);
        }

        // Crear la prenda DIRECTAMENTE
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $validated['nombre_prenda'],
            'cantidad' => $validated['cantidad'],
            'descripcion' => $validated['descripcion'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'cantidad' => $prenda->cantidad,
            'descripcion' => $prenda->descripcion,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error agregando prenda simple', [
            'pedido_id' => $pedidoId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'error' => 'Error al agregar la prenda: ' . $e->getMessage()
        ], 500);
    }
}
```

**Después**:
```php
public function agregarPrendaSimple(Request $request, $pedidoId)
{
    try {
        $validated = $request->validate([
            'nombre_prenda' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        // Crear DTO para el Use Case
        $dto = new AgregarItemPedidoDTO(
            pedidoId: (string)$pedidoId,
            nombrePrenda: $validated['nombre_prenda'],
            cantidad: (int)$validated['cantidad'],
            descripcion: $validated['descripcion'] ?? null,
            usuarioId: Auth::id()
        );

        // Usar el Use Case existente
        $item = $this->agregarItemPedidoUseCase->ejecutar($dto);

        return response()->json([
            'success' => true,
            'id' => $item->id,
            'nombre_prenda' => $item->nombre_prenda,
            'cantidad' => $item->cantidad,
            'descripcion' => $item->descripcion,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error agregando prenda simple', [
            'pedido_id' => $pedidoId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'error' => 'Error al agregar la prenda: ' . $e->getMessage()
        ], 500);
    }
}
```

**Cambios necesarios**:
1.  Crear DTO (AgregarItemPedidoDTO debería existir)
2.  agregarItemPedidoUseCase ya está inyectado
3. ✓ Remover lógica directa de BD

#### Paso 4.2: Refactorizar getNextPedido()

**Ubicación**: Línea ~605

**Crear Use Case si no existe**:

```php
// app/Application/Pedidos/UseCases/ObtenerSiguientePedidoNumberUseCase.php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerSiguientePedidoNumberUseCase
{
    public function __construct(
        private PedidoProduccionRepository $repository
    ) {}

    public function ejecutar(): int
    {
        return $this->repository->obtenerSiguientePedidoNumber();
    }
}
```

**Agregar método al repositorio**:

```php
// app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php

public function obtenerSiguientePedidoNumber(): int
{
    $ultimoPedido = PedidoProduccion::max('numero_pedido');
    return $ultimoPedido ? $ultimoPedido + 1 : 1;
}
```

**Refactorizar método en controlador**:

```php
public function getNextPedido()
{
    try {
        $siguientePedido = $this->obtenerSiguientePedidoNumberUseCase->ejecutar();

        return response()->json([
            'siguiente_pedido' => $siguientePedido
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al obtener próximo número',
            'message' => $e->getMessage()
        ], 500);
    }
}
```

#### Paso 4.3: Remover ObtenerProximoPedidoService

```diff
- use App\Application\Services\Asesores\ObtenerProximoPedidoService;

- protected ObtenerProximoPedidoService $obtenerProximoPedidoService;

// En constructor:
- ObtenerProximoPedidoService $obtenerProximoPedidoService,
- $this->obtenerProximoPedidoService = $obtenerProximoPedidoService;
```

**Agregar nuevo Use Case a constructor**:

```php
protected ObtenerSiguientePedidoNumberUseCase $obtenerSiguientePedidoNumberUseCase;

public function __construct(
    // ... otros
    ObtenerSiguientePedidoNumberUseCase $obtenerSiguientePedidoNumberUseCase
) {
    // ...
    $this->obtenerSiguientePedidoNumberUseCase = $obtenerSiguientePedidoNumberUseCase;
}
```

#### Paso 4.4: Registrar Use Case en DomainServiceProvider

```php
// app/Providers/DomainServiceProvider.php

$this->app->singleton(ObtenerSiguientePedidoNumberUseCase::class, function ($app) {
    return new ObtenerSiguientePedidoNumberUseCase(
        $app->make(PedidoProduccionRepository::class)
    );
});
```

#### Paso 4.5: Tests

```bash
php artisan test tests/Feature/Http/Controllers/Asesores/AsesoresControllerTest.php

# Tests de: agregarPrendaSimple, getNextPedido
```

#### Paso 4.6: Commit

```bash
git add -A
git commit -m "[REFACTOR] Refactorizar agregarPrendaSimple y getNextPedido

Cambios:
- agregarPrendaSimple(): Usar AgregarItemPedidoUseCase
- getNextPedido(): Crear y usar ObtenerSiguientePedidoNumberUseCase

Nuevos componentes:
- ObtenerSiguientePedidoNumberUseCase (nuevo)
- PedidoProduccionRepository::obtenerSiguientePedidoNumber()

Servicios removidos:
- ObtenerProximoPedidoService (reemplazado por Use Case)

Resultado:
- Métodos ahora consistentes con patrón DDD
- Inyecciones reducidas: 13 → 12

Tests:  Pasando
"
```

---

### ⏱️ FASE 5: CREAR SERVICE PROVIDER (1 hora)

**Objetivo**: Crear AsesoresServiceProvider para inyecciones explícitas

#### Paso 5.1: Crear archivo

```bash
mkdir -p app/Infrastructure/Pedidos/Providers/
touch app/Infrastructure/Pedidos/Providers/AsesoresServiceProvider.php
```

#### Paso 5.2: Implementar Provider

```php
// app/Infrastructure/Pedidos/Providers/AsesoresServiceProvider.php

<?php

namespace App\Infrastructure\Pedidos\Providers;

use Illuminate\Support\ServiceProvider;
use App\Application\Services\Asesores\DashboardService;
use App\Application\Services\Asesores\NotificacionesService;
use App\Application\Services\Asesores\PerfilService;
use App\Application\Services\Asesores\ObtenerProximoPedidoService;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Application\Services\Asesores\ObtenerDatosRecibosService;
use App\Application\Services\Asesores\ProcesarFotosTelasService;
use App\Application\Services\Asesores\GuardarPedidoLogoService;

/**
 * Service Provider: Asesores Services
 * 
 * Registra servicios específicos de la funcionalidad de Asesores
 * que aún no han sido migrados a Use Cases (DDD)
 */
class AsesoresServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Servicios de Dashboard
        $this->app->singleton(DashboardService::class);
        
        // Servicios de Notificaciones
        $this->app->singleton(NotificacionesService::class);
        
        // Servicios de Perfil
        $this->app->singleton(PerfilService::class);
        
        // Servicios de Números (ya refactorizado a Use Case, solo mantener)
        $this->app->singleton(ObtenerProximoPedidoService::class);
        
        // Servicios de Datos (usa repositorio, pero mantener por compatibilidad)
        $this->app->singleton(ObtenerDatosFacturaService::class);
        $this->app->singleton(ObtenerDatosRecibosService::class);
        
        // Servicios de Procesamiento de Archivos
        $this->app->singleton(ProcesarFotosTelasService::class);
        $this->app->singleton(GuardarPedidoLogoService::class);
    }

    public function boot(): void
    {
        // Comportamiento al iniciar la aplicación
    }
}
```

#### Paso 5.3: Registrar en config/app.php

```php
// config/app.php - dentro del array 'providers'

'providers' => [
    // ... otros providers
    
    App\Infrastructure\Pedidos\Providers\AsesoresServiceProvider::class,
    
    // ... resto
],
```

#### Paso 5.4: Tests

```bash
php artisan test tests/Unit/Providers/AsesoresServiceProviderTest.php

# O generar test:
php artisan make:test Providers/AsesoresServiceProviderTest --unit
```

#### Paso 5.5: Commit

```bash
git add -A
git commit -m "[FEATURE] Crear AsesoresServiceProvider para inyección explícita

Nuevos componentes:
- AsesoresServiceProvider (registra servicios legacy)
- Configuración en config/app.php

Beneficios:
- Inyecciones explícitas y visibles
- Facilita testing
- Documenta arquitectura
- Centraliza registro de servicios

Servicios registrados:
- DashboardService
- NotificacionesService
- PerfilService
- ObtenerProximoPedidoService
- ObtenerDatosFacturaService (pendiente remover)
- ObtenerDatosRecibosService (pendiente remover)
- ProcesarFotosTelasService
- GuardarPedidoLogoService

Tests:  Pasando
"
```

---

### ⏱️ FASE 6: REFACTORIZAR DASHBOARD (2 horas)

**Objetivo**: Crear Use Cases para dashboard

#### Paso 6.1: Crear Use Cases

```php
// app/Application/Pedidos/UseCases/ObtenerDashboardEstadisticasUseCase.php

<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Application\Pedidos\DTOs\ObtenerDashboardDTO;

class ObtenerDashboardEstadisticasUseCase
{
    public function __construct(
        private PedidoProduccionRepository $repository
    ) {}

    public function ejecutar(ObtenerDashboardDTO $dto): array
    {
        $asesorId = $dto->asesorId;

        return [
            'pedidos_dia' => $this->repository->contarPorAsesorYFecha(
                $asesorId,
                now()->format('Y-m-d')
            ),
            'pedidos_mes' => $this->repository->contarPorAsesorYMes(
                $asesorId,
                now()->month,
                now()->year
            ),
            'pedidos_anio' => $this->repository->contarPorAsesorYAnio(
                $asesorId,
                now()->year
            ),
            'pedidos_pendientes' => $this->repository->contarPendientesPorAsesor($asesorId),
        ];
    }
}
```

```php
// app/Application/Pedidos/UseCases/ObtenerDashboardGraficasUseCase.php

<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Application\Pedidos\DTOs\ObtenerDashboardGraficasDTO;

class ObtenerDashboardGraficasUseCase
{
    public function __construct(
        private PedidoProduccionRepository $repository
    ) {}

    public function ejecutar(ObtenerDashboardGraficasDTO $dto): array
    {
        $asesorId = $dto->asesorId;
        $dias = $dto->dias ?? 30;

        return $this->repository->obtenerEstadisticasUltimoDias($asesorId, $dias);
    }
}
```

#### Paso 6.2: Crear DTOs

```php
// app/Application/Pedidos/DTOs/ObtenerDashboardDTO.php

<?php

namespace App\Application\Pedidos\DTOs;

class ObtenerDashboardDTO
{
    public function __construct(
        public int $asesorId
    ) {}

    public static function fromRequest(int $asesorId): self
    {
        return new self($asesorId);
    }
}
```

```php
// app/Application/Pedidos/DTOs/ObtenerDashboardGraficasDTO.php

<?php

namespace App\Application\Pedidos\DTOs;

class ObtenerDashboardGraficasDTO
{
    public function __construct(
        public int $asesorId,
        public ?int $dias = 30
    ) {}

    public static function fromRequest(int $asesorId, ?int $dias = 30): self
    {
        return new self($asesorId, $dias ?? 30);
    }
}
```

#### Paso 6.3: Agregar métodos al repositorio

```php
// app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php

public function contarPorAsesorYFecha(int $asesorId, string $fecha): int
{
    return PedidoProduccion::where('asesor_id', $asesorId)
        ->whereDate('created_at', $fecha)
        ->count();
}

public function contarPorAsesorYMes(int $asesorId, int $mes, int $anio): int
{
    return PedidoProduccion::where('asesor_id', $asesorId)
        ->whereMonth('created_at', $mes)
        ->whereYear('created_at', $anio)
        ->count();
}

public function contarPorAsesorYAnio(int $asesorId, int $anio): int
{
    return PedidoProduccion::where('asesor_id', $asesorId)
        ->whereYear('created_at', $anio)
        ->count();
}

public function contarPendientesPorAsesor(int $asesorId): int
{
    return PedidoProduccion::where('asesor_id', $asesorId)
        ->whereIn('estado', ['No iniciado', 'En Ejecución'])
        ->count();
}

public function obtenerEstadisticasUltimoDias(int $asesorId, int $dias = 30): array
{
    $estadisticas = DB::table('pedidos_produccion')
        ->where('asesor_id', $asesorId)
        ->where('created_at', '>=', now()->subDays($dias))
        ->select(DB::raw('DATE(created_at) as fecha'), DB::raw('COUNT(*) as total'))
        ->groupBy('fecha')
        ->orderBy('fecha')
        ->get();

    return $estadisticas->mapWithKeys(fn($stat) => [
        $stat->fecha => $stat->total
    ])->toArray();
}
```

#### Paso 6.4: Refactorizar métodos en controlador

```php
public function dashboard()
{
    try {
        $dto = ObtenerDashboardDTO::fromRequest(\Auth::id());
        $stats = $this->obtenerDashboardEstadisticasUseCase->ejecutar($dto);
        
        return view('asesores.dashboard', compact('stats'));
    } catch (\Exception $e) {
        \Log::error('Error al obtener dashboard: ' . $e->getMessage());
        return redirect()->back()->with('error', $e->getMessage());
    }
}

public function getDashboardData(Request $request)
{
    try {
        $dias = $request->get('tipo', 30);
        
        $dto = ObtenerDashboardGraficasDTO::fromRequest(\Auth::id(), $dias);
        $datos = $this->obtenerDashboardGraficasUseCase->ejecutar($dto);
        
        return response()->json($datos);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error al obtener datos: ' . $e->getMessage()
        ], 500);
    }
}
```

#### Paso 6.5: Registrar Use Cases

```php
// app/Providers/DomainServiceProvider.php

$this->app->singleton(ObtenerDashboardEstadisticasUseCase::class, function ($app) {
    return new ObtenerDashboardEstadisticasUseCase(
        $app->make(PedidoProduccionRepository::class)
    );
});

$this->app->singleton(ObtenerDashboardGraficasUseCase::class, function ($app) {
    return new ObtenerDashboardGraficasUseCase(
        $app->make(PedidoProduccionRepository::class)
    );
});
```

#### Paso 6.6: Actualizar constructor del controlador

```diff
+ use App\Application\Pedidos\UseCases\ObtenerDashboardEstadisticasUseCase;
+ use App\Application\Pedidos\UseCases\ObtenerDashboardGraficasUseCase;

+ protected ObtenerDashboardEstadisticasUseCase $obtenerDashboardEstadisticasUseCase;
+ protected ObtenerDashboardGraficasUseCase $obtenerDashboardGraficasUseCase;

  public function __construct(
      // ...
+     ObtenerDashboardEstadisticasUseCase $obtenerDashboardEstadisticasUseCase,
+     ObtenerDashboardGraficasUseCase $obtenerDashboardGraficasUseCase
  ) {
      // ...
+     $this->obtenerDashboardEstadisticasUseCase = $obtenerDashboardEstadisticasUseCase;
+     $this->obtenerDashboardGraficasUseCase = $obtenerDashboardGraficasUseCase;
  }
```

#### Paso 6.7: Remover DashboardService

```diff
- use App\Application\Services\Asesores\DashboardService;
- protected DashboardService $dashboardService;
// En constructor:
- DashboardService $dashboardService,
- $this->dashboardService = $dashboardService;
```

#### Paso 6.8: Tests

```bash
php artisan test tests/Feature/Http/Controllers/Asesores/AsesoresControllerTest.php

# Tests de: dashboard, getDashboardData
```

#### Paso 6.9: Commit

```bash
git add -A
git commit -m "[REFACTOR-FASE4] Refactorizar dashboard a Use Cases

Nuevos componentes:
- ObtenerDashboardEstadisticasUseCase
- ObtenerDashboardGraficasUseCase
- ObtenerDashboardDTO
- ObtenerDashboardGraficasDTO

Cambios en repositorio:
+ contarPorAsesorYFecha()
+ contarPorAsesorYMes()
+ contarPorAsesorYAnio()
+ contarPendientesPorAsesor()
+ obtenerEstadisticasUltimoDias()

Cambios en controlador:
- dashboard() ahora usa ObtenerDashboardEstadisticasUseCase
- getDashboardData() ahora usa ObtenerDashboardGraficasUseCase

Servicios removidos:
- DashboardService (reemplazado por Use Cases)

Resultado:
- Dashboard ahora sigue patrón DDD
- Métodos testables y reutilizables
- Inyecciones reducidas: 12 → 13 (2 Use Cases + beneficios)

Tests:  Pasando
"
```

---

### ⏱️ FASE 7: VALIDACIÓN Y TESTING (2-3 horas)

**Objetivo**: Verificar que todo funciona correctamente

#### Paso 7.1: Ejecutar tests completos

```bash
# Tests del controlador
php artisan test tests/Feature/Http/Controllers/Asesores/AsesoresControllerTest.php

# Tests de Use Cases
php artisan test tests/Unit/Application/Pedidos/UseCases/

# Tests del repositorio
php artisan test tests/Unit/Domain/PedidoProduccion/Repositories/

# Tests de Providers
php artisan test tests/Unit/Providers/
```

#### Paso 7.2: Verificar no hay imports muertos

```bash
# Buscar imports no usados (algunos linters de PHP lo hacen)
# O hacer una búsqueda manual:

grep -n "use App\\\\" app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php \
  | wc -l

# Resultado esperado: < 30 lineas (reducción de 40+)
```

#### Paso 7.3: Verificar que no hay servicios zombie

```bash
# Verificar que los servicios eliminados no se importan en otro lado
grep -r "AnularPedidoService" app/Infrastructure/Http/Controllers/ || echo " No encontrado"
grep -r "ObtenerDatosFacturaService" app/Infrastructure/Http/Controllers/ || echo " No encontrado"
```

#### Paso 7.4: Verificar logs

```bash
# Ejecutar la aplicación y revisar logs
php artisan serve

# Abrir http://localhost:8000/asesores/dashboard
# Verificar que NO hay errores en storage/logs/laravel.log
```

#### Paso 7.5: Commit final

```bash
git add -A
git commit -m "[TEST] Validar refactorización completa de AsesoresController

Validaciones realizadas:
 Tests del controlador pasan (100%)
 Tests de Use Cases pasan (100%)
 Tests del repositorio pasan (100%)
 No hay imports muertos
 No hay servicios zombie
 Logs limpios sin errores

Métricas finales:
- Inyecciones: 23 → 15 (35% reducción)
- Métodos refactorizados: 8 → 14 (75% total)
- Servicios legacy: 16 → 5 (69% reducción)
- Líneas constructor: 70+ → 45+ (36% reducción)

Deuda técnica reducida significativamente.

Tests:  Pasando (100%)
"
```

---

## 📊 RESUMEN DE CAMBIOS POR FASE

| Fase | Cambios | Tiempo | Servicios | Use Cases | Commits |
|------|---------|--------|-----------|-----------|---------|
| 1 | Eliminar agregado legacy | 1-2h | -1 | 0 | 1 |
| 2 | Remover servicios muertos | 1h | -7 | 0 | 1 |
| 3 | Refactorizar críticos | 2-3h | -3 | 0 | 1 |
| 4 | Refactorizar adicionales | 2-3h | -1 | +1 | 1 |
| 5 | Crear Service Provider | 1h | +0 | 0 | 1 |
| 6 | Refactorizar Dashboard | 2h | -1 | +2 | 1 |
| 7 | Validación y Testing | 2-3h | 0 | 0 | 1 |
| **TOTAL** | **33 cambios** | **14-16h** | **-13** | **+3** | **7** |

---

## MÉTRICAS ESPERADAS POST-REFACTOR

```
ANTES:
├── Total inyecciones: 23
│   ├── Use Cases: 7 (30%)
│   └── Servicios: 16 (70%)
├── Métodos refactorizados: 8/21 (38%)
├── Líneas constructor: 70+
└── Deuda técnica: ALTA

DESPUÉS:
├── Total inyecciones: 15
│   ├── Use Cases: 10 (67%)
│   └── Servicios: 5 (33%)
├── Métodos refactorizados: 14/21 (67%)
├── Líneas constructor: 45+
└── Deuda técnica: BAJA
```

---

**Plan creado**: 22 de Enero de 2026  
**Duración estimada**: 14-16 horas  
**Complejidad**: Media-Alta  
**ROI**: Muy Alto (50%+ reducción de deuda técnica)
