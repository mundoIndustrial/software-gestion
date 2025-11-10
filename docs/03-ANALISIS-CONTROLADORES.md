# Análisis Detallado de Controladores

**Proyecto:** Mundo Industrial v4.0  
**Fecha:** 10 Noviembre 2025

---

## 📋 Índice

1. [Resumen de Controladores](#resumen-de-controladores)
2. [Análisis por Controlador](#análisis-por-controlador)
3. [Problemas Comunes](#problemas-comunes)
4. [Métricas de Complejidad](#métricas-de-complejidad)
5. [Plan de Refactorización](#plan-de-refactorización)

---

## 🎯 Resumen de Controladores

### Inventario de Controladores

| Controlador | Líneas | Métodos | Responsabilidades | Severidad |
|-------------|--------|---------|-------------------|-----------|
| **TablerosController** | 1691 | 30+ | 10+ | 🔴 Crítico |
| **EntregaController** | 551 | 12 | 4 | 🟡 Alto |
| **RegistroOrdenController** | 642 | 10 | 3 | 🟡 Alto |
| **BalanceoController** | 393 | 15 | 3 | 🟢 Medio |
| **VistasController** | ~400 | 8 | 2 | 🟢 Medio |
| **DashboardController** | ~300 | 8 | 2 | 🟢 Medio |
| **ConfiguracionController** | ~250 | 7 | 3 | 🟡 Alto |

---

## 🔴 TablerosController - CRÍTICO

**Archivo:** `app/Http/Controllers/TablerosController.php`  
**Líneas:** 1691  
**Métodos:** 30+

### Problemas Identificados

#### 1. God Object Anti-Pattern

```php
class TablerosController extends Controller
{
    // ❌ PROBLEMA: 10+ responsabilidades diferentes
    
    // Grupo 1: Vistas (3 métodos)
    public function index() { /* 181 líneas */ }
    public function fullscreen() { /* ... */ }
    public function corteFullscreen() { /* ... */ }
    
    // Grupo 2: CRUD Producción (4 métodos)
    public function store() { /* ... */ }
    public function update() { /* ... */ }
    public function destroy() { /* ... */ }
    public function duplicate() { /* ... */ }
    
    // Grupo 3: CRUD Corte (1 método)
    public function storeCorte() { /* ... */ }
    
    // Grupo 4: Gestión Operarios (3 métodos)
    public function searchOperarios() { /* ... */ }
    public function storeOperario() { /* ... */ }
    public function findOrCreateOperario() { /* ... */ }
    
    // Grupo 5: Gestión Máquinas (3 métodos)
    public function searchMaquinas() { /* ... */ }
    public function storeMaquina() { /* ... */ }
    public function findOrCreateMaquina() { /* ... */ }
    
    // Grupo 6: Gestión Telas (3 métodos)
    public function searchTelas() { /* ... */ }
    public function storeTela() { /* ... */ }
    public function findOrCreateTela() { /* ... */ }
    
    // Grupo 7: Cálculos (3 métodos)
    public function calcularSeguimientoModulos() { /* ... */ }
    public function calcularProduccionPorHoras() { /* ... */ }
    public function calcularProduccionPorOperarios() { /* ... */ }
    
    // Grupo 8: Filtros (2 métodos)
    public function aplicarFiltrosDinamicos() { /* ... */ }
    public function filtrarRegistrosPorFecha() { /* ... */ }
    
    // Grupo 9: Dashboards (3 métodos)
    public function getDashboardTablesData() { /* ... */ }
    public function getSeguimientoData() { /* ... */ }
    public function getDashboardCorteData() { /* ... */ }
    
    // Grupo 10: Utilidades (3 métodos)
    public function getUniqueValues() { /* ... */ }
    public function getTiempoCiclo() { /* ... */ }
    public function findHoraId() { /* ... */ }
}
```

### Métricas de Complejidad

- **Complejidad Ciclomática:** ~250 (Crítico, debería ser < 10)
- **Acoplamiento Eferente:** 14 clases (Alto)
- **Líneas por método:** Promedio 56 (Alto, debería ser < 20)
- **Nivel de anidación:** Hasta 5 niveles (Crítico)

### Impacto

- ❌ **Imposible hacer unit tests** aislados
- ❌ **Cambios riesgosos**: Modificar una parte afecta otras
- ❌ **Difícil de entender**: Requiere horas para comprender
- ❌ **Merge conflicts**: Múltiples desarrolladores tocando el mismo archivo

### Plan de Refactorización

```php
// ✅ SOLUCIÓN: Dividir en 10 controladores especializados

// 1. Controlador principal (solo vistas)
class TablerosController extends Controller
{
    public function __construct(
        private ProduccionService $produccionService,
        private DashboardService $dashboardService
    ) {}
    
    public function index(Request $request)
    {
        $data = $this->produccionService->getTablerosData($request->all());
        return view('tableros.index', $data);
    }
    
    public function fullscreen(Request $request)
    {
        $section = $request->get('section', 'produccion');
        $data = $this->produccionService->getFullscreenData($section, $request->all());
        return view('tableros.fullscreen', $data);
    }
}

// 2. CRUD Producción
class ProduccionController extends Controller
{
    public function __construct(private ProduccionService $service) {}
    
    public function store(StoreProduccionRequest $request)
    {
        $registro = $this->service->crear($request->validated());
        return response()->json($registro, 201);
    }
    
    public function update(int $id, UpdateProduccionRequest $request)
    {
        $registro = $this->service->actualizar($id, $request->validated());
        return response()->json($registro);
    }
    
    public function destroy(int $id)
    {
        $this->service->eliminar($id);
        return response()->json(null, 204);
    }
}

// 3. CRUD Corte
class CorteController extends Controller
{
    public function __construct(private CorteService $service) {}
    
    public function store(StoreCorteRequest $request)
    {
        $registro = $this->service->crear($request->validated());
        return response()->json($registro, 201);
    }
}

// 4. Gestión Operarios
class OperarioController extends Controller
{
    public function __construct(private OperarioService $service) {}
    
    public function index(Request $request)
    {
        return $this->service->buscar($request->get('q'));
    }
    
    public function store(StoreOperarioRequest $request)
    {
        return $this->service->crearOBuscar($request->validated());
    }
}

// 5. Gestión Máquinas
class MaquinaController extends Controller
{
    public function __construct(private MaquinaService $service) {}
    
    public function index(Request $request)
    {
        return $this->service->buscar($request->get('q'));
    }
    
    public function store(StoreMaquinaRequest $request)
    {
        return $this->service->crearOBuscar($request->validated());
    }
}

// 6. Gestión Telas
class TelaController extends Controller
{
    public function __construct(private TelaService $service) {}
    
    public function index(Request $request)
    {
        return $this->service->buscar($request->get('q'));
    }
    
    public function store(StoreTelaRequest $request)
    {
        return $this->service->crearOBuscar($request->validated());
    }
}

// 7. Dashboard Producción
class ProduccionDashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}
    
    public function seguimiento(Request $request)
    {
        return $this->service->getSeguimientoData($request->all());
    }
    
    public function tablas(Request $request)
    {
        return $this->service->getTablesData($request->all());
    }
}

// 8. Dashboard Corte
class CorteDashboardController extends Controller
{
    public function __construct(private CorteDashboardService $service) {}
    
    public function index(Request $request)
    {
        return $this->service->getDashboardData($request->all());
    }
}

// 9. Filtros y Búsquedas
class ProduccionFiltroController extends Controller
{
    public function __construct(private FiltroService $service) {}
    
    public function valoresUnicos(Request $request)
    {
        return $this->service->getUniqueValues(
            $request->get('tabla'),
            $request->get('columna')
        );
    }
}

// 10. Utilidades
class ProduccionUtilController extends Controller
{
    public function tiempoCiclo(Request $request)
    {
        return app(TiempoCicloService::class)->buscar($request->all());
    }
    
    public function horaId(Request $request)
    {
        return app(HoraService::class)->findOrCreate($request->get('hora'));
    }
}
```

---

## 🟡 EntregaController - ALTO

**Archivo:** `app/Http/Controllers/EntregaController.php`  
**Líneas:** 551  
**Métodos:** 12

### Problemas Identificados

#### 1. Lógica Condicional Repetida

```php
// ❌ PROBLEMA: Mismo patrón repetido en múltiples métodos
private function getModels($tipo)
{
    if ($tipo === 'pedido') {
        return [
            'costura' => EntregaPedidoCostura::class,
            'corte' => EntregaPedidoCorte::class,
            // ...
        ];
    } elseif ($tipo === 'bodega') {
        return [
            'costura' => EntregaBodegaCostura::class,
            'corte' => EntregaBodegaCorte::class,
            // ...
        ];
    }
}

// Se repite en: index(), costuraData(), corteData(), orderData(), etc.
```

#### 2. Violación de DRY

```php
// ❌ PROBLEMA: Código duplicado
public function costuraData(Request $request)
{
    $tipo = $request->route('tipo');
    $config = $this->getModels($tipo);
    $fecha = $request->get('fecha', Carbon::today()->toDateString());
    $data = $config['costura']::where('fecha_entrega', $fecha)->get();
    return response()->json($data);
}

public function corteData(Request $request)
{
    $tipo = $request->route('tipo');
    $config = $this->getModels($tipo);
    $fecha = $request->get('fecha', Carbon::today()->toDateString());
    $data = $config['corte']::where('fecha_entrega', $fecha)->get();
    return response()->json($data);
}
```

### Solución Propuesta

```php
// ✅ SOLUCIÓN: Strategy Pattern + Service Layer

interface EntregaRepositoryInterface
{
    public function findByFecha(Carbon $fecha): Collection;
    public function findByPedido(int $pedido): Collection;
    public function create(array $data): Entrega;
}

class EntregaPedidoRepository implements EntregaRepositoryInterface
{
    public function findByFecha(Carbon $fecha): Collection
    {
        return EntregaPedidoCostura::where('fecha_entrega', $fecha)->get();
    }
}

class EntregaBodegaRepository implements EntregaRepositoryInterface
{
    public function findByFecha(Carbon $fecha): Collection
    {
        return EntregaBodegaCostura::where('fecha_entrega', $fecha)->get();
    }
}

class EntregaController extends Controller
{
    public function __construct(
        private EntregaRepositoryFactory $repoFactory
    ) {}
    
    public function index(string $tipo, Request $request)
    {
        $repo = $this->repoFactory->create($tipo);
        $fecha = Carbon::parse($request->get('fecha', today()));
        
        $entregas = $repo->findByFecha($fecha);
        
        return view('entrega.index', compact('entregas', 'fecha', 'tipo'));
    }
    
    public function data(string $tipo, string $area, Request $request)
    {
        $repo = $this->repoFactory->create($tipo, $area);
        $fecha = Carbon::parse($request->get('fecha', today()));
        
        return response()->json($repo->findByFecha($fecha));
    }
}
```

---

## 🟡 RegistroOrdenController - ALTO

**Archivo:** `app/Http/Controllers/RegistroOrdenController.php`  
**Líneas:** 642  
**Métodos:** 10

### Problemas Identificados

#### 1. Lógica de Negocio en Controlador

```php
// ❌ PROBLEMA: Cálculo de festivos en controlador
public function index(Request $request)
{
    $currentYear = now()->year;
    $nextYear = now()->addYear()->year;
    $festivos = array_merge(
        FestivosColombiaService::obtenerFestivos($currentYear),
        FestivosColombiaService::obtenerFestivos($nextYear)
    );
    
    // Más lógica de negocio...
    foreach ($ordenes as $orden) {
        $orden->setFestivos($festivos);
    }
}
```

#### 2. Validación Manual

```php
// ❌ PROBLEMA: Validación hardcodeada
if ($request->has('get_unique_values') && $request->column) {
    $column = $request->column;
    $allowedColumns = [
        'pedido', 'estado', 'area', 'tiempo', 'total_de_dias_',
        // ... 40 columnas más
    ];
    
    if (in_array($column, $allowedColumns)) {
        // ...
    }
}
```

### Solución Propuesta

```php
// ✅ SOLUCIÓN: Service Layer + Form Requests

class OrdenService
{
    public function __construct(
        private OrdenRepository $ordenRepo,
        private FestivosService $festivosService
    ) {}
    
    public function getOrdenesConFestivos(array $filters): Collection
    {
        $ordenes = $this->ordenRepo->findWithFilters($filters);
        $festivos = $this->festivosService->getFestivosActualesYProximos();
        
        return $ordenes->each(fn($orden) => $orden->setFestivos($festivos));
    }
}

class GetUniqueValuesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'column' => [
                'required',
                Rule::in($this->allowedColumns())
            ]
        ];
    }
    
    private function allowedColumns(): array
    {
        return ['pedido', 'estado', 'area', /* ... */];
    }
}

class RegistroOrdenController extends Controller
{
    public function __construct(private OrdenService $service) {}
    
    public function index(Request $request)
    {
        $ordenes = $this->service->getOrdenesConFestivos($request->all());
        return view('registros.index', compact('ordenes'));
    }
    
    public function uniqueValues(GetUniqueValuesRequest $request)
    {
        $values = $this->service->getUniqueValues($request->validated('column'));
        return response()->json(['unique_values' => $values]);
    }
}
```

---

## 🟢 BalanceoController - MEDIO

**Archivo:** `app/Http/Controllers/BalanceoController.php`  
**Líneas:** 393  
**Métodos:** 15

### Aspectos Positivos

✅ **Bien estructurado**: Métodos con responsabilidades claras  
✅ **Usa Form Requests**: Validación separada  
✅ **Eager Loading**: Optimización de consultas  
✅ **Comentarios útiles**: Documentación clara

### Problemas Menores

#### 1. Manejo de Archivos en Controlador

```php
// ⚠️ MEJORABLE: Lógica de archivos en controlador
public function storePrenda(Request $request)
{
    if ($request->hasFile('imagen')) {
        $imagen = $request->file('imagen');
        $nombreImagen = time() . '_' . uniqid() . '.' . $imagen->getClientOriginalExtension();
        $imagen->move(public_path('images/prendas'), $nombreImagen);
        $validated['imagen'] = 'images/prendas/' . $nombreImagen;
    }
    
    $prenda = Prenda::create($validated);
}
```

### Mejora Propuesta

```php
// ✅ MEJOR: Service para manejo de archivos

class ImagenService
{
    public function guardarImagen(UploadedFile $file, string $carpeta): string
    {
        $nombre = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path("images/{$carpeta}"), $nombre);
        return "images/{$carpeta}/{$nombre}";
    }
    
    public function eliminarImagen(string $ruta): void
    {
        if (file_exists(public_path($ruta))) {
            unlink(public_path($ruta));
        }
    }
}

class BalanceoController extends Controller
{
    public function __construct(
        private PrendaService $prendaService,
        private ImagenService $imagenService
    ) {}
    
    public function storePrenda(StorePrendaRequest $request)
    {
        $data = $request->validated();
        
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->imagenService->guardarImagen(
                $request->file('imagen'),
                'prendas'
            );
        }
        
        $prenda = $this->prendaService->crear($data);
        
        return redirect()->route('balanceo.index')
            ->with('success', 'Prenda creada exitosamente');
    }
}
```

---

## 📊 Problemas Comunes en Todos los Controladores

### 1. Sin Inyección de Dependencias

```php
// ❌ MAL: Instanciación directa
$model = EntregaPedidoCostura::class;
$model::create($data);

event(new EntregaRegistrada($entrega));
```

### 2. Queries en Controladores

```php
// ❌ MAL: Eloquent directo en controlador
$ordenes = TablaOriginal::where('estado', 'Activo')
    ->whereDate('fecha_creacion', '>', now()->subDays(30))
    ->orderBy('fecha_creacion', 'desc')
    ->paginate(25);
```

### 3. Lógica de Presentación

```php
// ❌ MAL: Formateo de datos en controlador
$registroArray = $registro->toArray();
if ($registro->hora) {
    $registroArray['hora_display'] = $registro->hora->hora;
}
if ($registro->operario) {
    $registroArray['operario_display'] = $registro->operario->name;
}
```

### 4. Sin Manejo de Errores Consistente

```php
// ❌ MAL: Manejo inconsistente
try {
    // operación
} catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}

// En otro lugar:
if (!$orden) {
    return response()->json(['error' => 'No encontrado'], 404);
}
```

---

## ✅ Recomendaciones Generales

### 1. Implementar Arquitectura en Capas

```
HTTP Request
    ↓
Controller (solo HTTP)
    ↓
Service (lógica de negocio)
    ↓
Repository (acceso a datos)
    ↓
Model (entidad de dominio)
```

### 2. Usar Form Requests

```php
// Validación separada
class StoreOrdenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'pedido' => 'required|integer|unique:ordenes',
            'cliente_id' => 'required|exists:clientes,id',
            // ...
        ];
    }
}
```

### 3. Implementar API Resources

```php
// Transformación de datos
class OrdenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'pedido' => $this->pedido,
            'cliente' => new ClienteResource($this->cliente),
            'estado' => $this->estado->valor(),
            'dias_habiles' => $this->calcularDiasHabiles(),
        ];
    }
}
```

### 4. Manejo de Errores Centralizado

```php
// Exception Handler
class Handler extends ExceptionHandler
{
    protected $dontReport = [
        OrdenNoEncontradaException::class,
    ];
    
    public function render($request, Throwable $e)
    {
        if ($e instanceof OrdenNoEncontradaException) {
            return response()->json([
                'error' => 'Orden no encontrada',
                'code' => 'ORDEN_NOT_FOUND'
            ], 404);
        }
        
        return parent::render($request, $e);
    }
}
```

---

## 📈 Métricas de Mejora Esperadas

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Líneas por controlador | 1691 | <200 | 88% ↓ |
| Métodos por controlador | 30+ | <10 | 67% ↓ |
| Complejidad ciclomática | 250 | <10 | 96% ↓ |
| Cobertura de tests | 0% | 80%+ | ∞ ↑ |
| Tiempo de comprensión | 4h | 30min | 87% ↓ |

---

## 🎯 Prioridades de Refactorización

### CRÍTICO (Semana 1-2)
1. Dividir TablerosController en 10 controladores
2. Crear Service Layer para Producción
3. Implementar Repository Pattern

### ALTO (Semana 3-4)
4. Refactorizar EntregaController
5. Refactorizar RegistroOrdenController
6. Crear Form Requests

### MEDIO (Semana 5-6)
7. Mejorar BalanceoController
8. Implementar API Resources
9. Centralizar manejo de errores

**Siguiente:** `04-RECOMENDACIONES-MEJORAS.md`
