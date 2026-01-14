# Análisis SOLID y DDD - PedidosProduccionController

## 📊 Resumen Ejecutivo

**VIOLACIONES DETECTADAS:** 7 críticas + 8 moderadas  
**ESTADO GENERAL:** ⚠️ **PARCIALMENTE COMPROMETIDO**

El controlador ha mejorado significativamente desde la refactorización inicial, pero aún contiene **lógica de negocio mixta** y **responsabilidades múltiples** que violan SOLID y DDD.

---

## 🔴 VIOLACIONES CRÍTICAS (Requieren corrección inmediata)

### 1. **SRP VIOLATION: Business Logic en el Controlador** ⚠️ CRÍTICO
**Ubicación:** Métodos: `guardarLogoPedido()` (200+ líneas), `crearPrendaSinCotizacion()` (400+ líneas), `crearReflectivoSinCotizacion()` (300+ líneas)

**Problema:**
```php
// ❌ LÍNEA 250-350: Lógica de negocio directa en el controlador
public function guardarLogoPedido(Request $request): JsonResponse
{
    // ... Validaciones HTTP (correcto)
    $pedidoId = $request->input('pedido_id');
    $logoCotizacionId = $request->input('logo_cotizacion_id');
    
    // ❌ LÍNEA 285-310: Lógica de búsqueda/creación de logo_pedido
    $logoPedidoExistente = null;
    if (is_numeric($pedidoId)) {
        $logoPedidoExistente = DB::table('logo_pedidos')->find($pedidoId);
        if (!$logoPedidoExistente) {
            $logoPedidoExistente = DB::table('logo_pedidos')
                ->where('pedido_id', $pedidoId)
                ->first();
        }
    }
    
    // ❌ LÍNEA 315-360: Creación de registro si no existe
    if (!$logoPedidoExistente) {
        $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();
        $nuevoPedidoLogoId = DB::table('logo_pedidos')->insertGetId([
            'pedido_id' => $pedidoId,
            'logo_cotizacion_id' => $logoCotizacionId,
            'numero_pedido' => $numeroLogoPedido,
            // ... 15 campos más
        ]);
        // ... 50 líneas de lógica
    } else {
        // ❌ LÍNEA 365-390: Actualización con lógica compleja
        $updateData = [ /* ... */ ];
        // ... más 30 líneas
    }
    
    // ❌ LÍNEA 395-420: Procesamiento de fotos
    $fotos = $request->input('fotos', []);
    if (!empty($fotos)) {
        foreach ($fotos as $index => $fotoId) {
            DB::table('logo_pedido_fotos')->insertOrIgnore([ /* ... */ ]);
        }
    }
}
```

**Responsabilidades Mezcladas:**
- ✅ Validar request HTTP (correcto)
- ❌ Lógica: Buscar logo_pedido existente
- ❌ Lógica: Crear o actualizar logo_pedido
- ❌ Lógica: Procesamiento de fotos
- ❌ Lógica: Generación de números

**Debería ser:**
```
Controller (HTTP Adapter)
    ↓ (valida request)
    ↓ (inyecta parámetros)
    ↓
LogoPedidoService (Domain)
    ├─ obtenerOCrearLogoPedido()
    ├─ guardarDatosLogoPedido()
    └─ procesarFotosLogoPedido()
```

**Impacto:**
- ❌ Controlador = 1,662 líneas (debería ser < 300)
- ❌ Difícil de testear
- ❌ Lógica de negocio no reutilizable
- ❌ Violaría DDD (lógica dispersa, no en Agregados)

---

### 2. **DDD VIOLATION: Agregados Incompletos** ⚠️ CRÍTICO

**Problema:** Las entidades de dominio no tienen métodos de negocio, solo atributos. Toda la lógica está en servicios sueltos o controladores.

```php
// ❌ MODELO: LogoPedido.php (posible contenido)
class LogoPedido extends Model {
    protected $table = 'logo_pedidos';
    public $timestamps = true;
    // Solo properties, SIN MÉTODOS de lógica de negocio
}

// ❌ EN CONTROLADOR (línea 240): Se trata como simple registro
DB::table('logo_pedidos')->find($pedidoId);

// ✅ DEBERÍA SER (DDD Agregado):
class LogoPedido extends AggregateRoot {
    // Métodos de negocio
    public static function crear(LogoCotizacion $logoCot): self {
        $logo = new self();
        $logo->logo_cotizacion_id = $logoCot->id;
        $logo->numero_pedido = NumeracionService::generarNumeroLogoPedido();
        $logo->estado = EstadoLogoPedido::PENDIENTE;
        
        $logo->recordThat(new LogoPedidoCreado($logo));
        return $logo;
    }
    
    public function guardarDatos(array $datos): void {
        $this->descripcion = $datos['descripcion'];
        $this->cantidad = $datos['cantidad'];
        // ... más campos
        
        $this->recordThat(new DatosLogoPedidoGuardados($this));
    }
    
    public function agregarFoto(LogoPedidoFoto $foto): void {
        $this->fotos()->attach($foto);
    }
}
```

**Problema Actual:**
- ❌ LogoPedido es un modelo Eloquent vacío, sin lógica
- ❌ Los servicios manipulan datos directamente con DB::table()
- ❌ No hay Events de Dominio registrados
- ❌ No hay Agregados definidos explícitamente
- ❌ No hay Value Objects para validar datos complejos

---

### 3. **DIP VIOLATION: Dependencia Directa de Detalles** ⚠️ CRÍTICO

**Ubicación:** Línea 240-460 en `guardarLogoPedido()`

```php
// ❌ MAL: Dependencia directa de detalles de implementación
DB::table('logo_pedidos')->find($pedidoId);
DB::table('logo_pedidos')->where('pedido_id', $pedidoId)->first();
DB::table('logo_pedidos')->insertGetId([...]);
DB::table('logo_pedido_fotos')->insertOrIgnore([...]);
```

**Debería ser:**
```php
// ✅ BIEN: Dependencia de abstracción (Repository/Service)
interface LogoPedidoRepository {
    public function obtenerPorId(int $id): ?LogoPedido;
    public function obtenerPorPedidoId(int $pedidoId): ?LogoPedido;
    public function guardar(LogoPedido $logo): void;
}

// En el controlador:
$logoPedido = $this->logoPedidoRepository->obtenerPorId($pedidoId);
$logoPedido = $logoPedido ?? $this->logoPedidoRepository->obtenerPorPedidoId($pedidoId);
if (!$logoPedido) {
    $logoPedido = LogoPedido::crear($logoCotizacion);
}
$this->logoPedidoRepository->guardar($logoPedido);
```

**Impacto:**
- ❌ Acoplamiento fuerte a Eloquent
- ❌ Difícil cambiar de BD en el futuro
- ❌ No se puede testear sin BD real
- ❌ Viola inversión de dependencias

---

### 4. **OCP VIOLATION: Métodos Gigantes con Múltiples Caminos** ⚠️ CRÍTICO

**Ubicación:** `crearPrendaSinCotizacion()` (400+ líneas, línea 1000-1400)

```php
// ❌ PROBLEMA: Un solo método maneja múltiples tipos de creación
public function crearPrendaSinCotizacion(Request $request): JsonResponse
{
    // 50 líneas: Validaciones
    // 80 líneas: Crear pedido
    // 200 líneas: Procesar prendas (con 3 formas diferentes de estructura de cantidad)
    // 100 líneas: Extraer variantes (color, tela, manga, broche)
    // 100 líneas: Crear prenda del pedido
    // 50 líneas: Guardar fotos de prenda
    // 50 líneas: Guardar fotos de telas
    // 30 líneas: Response
}

// ❌ Línea 1050-1150: Tres caminos diferentes para procesar cantidades
if (!empty($prenda['cantidad_talla'])) {
    // Camino 1: Nueva estructura
} else if (!empty($prenda['cantidades_por_genero'])) {
    // Camino 2: Estructura alternativa
} else {
    // Camino 3: Antigua estructura
}
```

**Impacto:**
- ❌ 400 líneas en UN método
- ❌ Múltiples razones para cambiar
- ❌ Imposible de testear unitariamente
- ❌ Alto riesgo de bugs

**Solución DDD:**
```php
// ✅ Crear estrategias para cada tipo
interface CreacionPrendaStrategy {
    public function procesar(array $prendaData): PrendaPedido;
}

class CreacionPrendaDesdeCotizacionStrategy implements CreacionPrendaStrategy { }
class CreacionPrendaSinCotizacionStrategy implements CreacionPrendaStrategy { }
class CreacionPrendaReflectivoStrategy implements CreacionPrendaStrategy { }

// En el servicio:
class CreacionPrendaService {
    public function crearDesdeRequest(array $datos, CreacionPrendaStrategy $strategy): PrendaPedido {
        return $strategy->procesar($datos);
    }
}
```

---

### 5. **MIXED CONCERNS: Lógica de Datos + Transformación + HTTP** ⚠️ CRÍTICO

**Ubicación:** `obtenerDatosCotizacion()` (línea 520-830, 310 líneas)

```php
// ❌ PROBLEMA: Un solo método hace 5 cosas
public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
{
    // 1️⃣ Cargar datos (línea 525-540)
    $cotizacion = Cotizacion::with([...])->findOrFail($cotizacionId);
    
    // 2️⃣ Validar permisos (línea 545-550)
    if ($cotizacion->asesor_id !== Auth::id()) {
        return response()->json([...], 403);
    }
    
    // 3️⃣ Transformar especificaciones (línea 555-570)
    $especificacionesConvertidas = $this->utilitariosService
        ->convertirEspecificacionesAlFormatoNuevo($cotizacion->especificaciones ?? []);
    
    // 4️⃣ Construir estructura JSON con 300 líneas de mapping (línea 575-750)
    'prendas' => $cotizacion->prendas->map(function($prenda) {
        // 50 líneas de transformación por prenda
    })->toArray(),
    
    // 5️⃣ Construir logo, prendas técnicas, reflectivo (línea 755-850)
    'logo' => $cotizacion->logoCotizacion ? [ /* ... */ ] : null,
    'prendas_tecnicas' => $cotizacion->logoCotizacion ? 
        $cotizacion->logoCotizacion->prendas->map(function($prenda) {
            // 30 líneas de transformación
        })->toArray() : [],
    
    // Retornar JSON transformado (línea 855-860)
    return response()->json([...]);
}
```

**Debería ser:**
```
HTTP Layer:
    ├─ Validar request
    ├─ Validar permisos
    └─ Response

Aplication/Query Layer:
    └─ ObtenerDatosCotizacionQuery

DTO/Transformer Layer:
    └─ CotizacionResponseTransformer
```

---

### 6. **LSP VIOLATION: Herencia de Controller Inadecuada** ⚠️ CRÍTICO

```php
// ❌ PROBLEMA: Hereda de Controller genérico
class PedidosProduccionController extends Controller
{
    // Hereda middleware(), validate(), authorize(), etc.
    // Pero el controlador MEZCLA http handling con business logic
    // Los métodos no siguen el patrón coherente
}

// ✅ DEBERÍA SER:
class PedidosProduccionController extends ApiResourceController {
    // Define contrato: index, show, store, update, destroy
    // O múltiples controladores especializados
}

// O mejor aún (DDD + CQRS):
class CrearPedidoDesdeCtaCommand { }
class ListarPedidosQuery { }
class ObtenerDetallePedidoQuery { }

// Handlers especializados:
class CrearPedidoDesdeCtaCommandHandler { }
class ListarPedidosQueryHandler { }
```

---

### 7. **HIDDEN LOGIC: Métodos Privados Vacíos** ⚠️ CRÍTICO

```php
// ❌ LÍNEA 590: Método privado que solo delega
private function heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)
{
    $this->variantesService->heredarVariantesDePrenda($cotizacion, $prendaPedido, $index);
}

// ❌ LÍNEA 850-875: Método legacy no eliminado
private function crearProcesosParaReflectivo_LEGACY(PedidoProduccion $pedido, Cotizacion $cotizacion): void
{
    // 300+ líneas de código antiguo sin comentario de que está deprecated
    // Causa confusión y potenciales bugs
}

// ❌ LÍNEA 295: Método privado sin sentido
private function crearLogoPedidoDesdeAnullCotizacion(Cotizacion $cotizacion)
{
    // Nunca es llamado desde el controlador
    // Lógica vieja, probablemente obsoleta
}
```

---

## 🟡 VIOLACIONES MODERADAS (Mejorar)

### 8. **Falta de Validación en Capa de Dominio**

```php
// ❌ Validación SOLO en controller
if (!$cliente) {
    return response()->json(['success' => false, 'message' => 'El cliente es requerido'], 422);
}

// ✅ DEBERÍA SER: Validación en agregado
class PedidoProduccion extends AggregateRoot {
    public function __construct(string $cliente, ...) {
        if (empty($cliente)) {
            throw new ClienteRequerido();
        }
        $this->cliente = $cliente;
    }
}
```

---

### 9. **Falta de Eventos de Dominio**

```php
// ❌ NO HAY EVENTOS
$pedido = PedidoProduccion::create([...]);
// ¿Quién se entera de que se creó?
// ¿Quién envía notificaciones?
// ¿Quién actualiza cachés?
// ¿Quién genera auditoría?

// ✅ DEBERÍA SER: Events de Dominio
$pedido = PedidoProduccion::crear(...);
event(new PedidoProduccionCreado($pedido)); // Listeners se suscriben

// En listeners:
class NotificarClienteDelPedidoCreado {
    public function handle(PedidoProduccionCreado $event) { }
}
```

---

### 10. **Falta de Value Objects**

```php
// ❌ STRINGS directos para datos complejos
'cliente' => $cliente,  // string
'forma_de_pago' => $formaPago,  // string
'estado' => EstadoPedido::PENDIENTE_SUPERVISOR->value,  // enum pero sin validación

// ✅ DEBERÍA SER: Value Objects
$cliente = new NombreCliente($clienteString); // Valida
$formaPago = new FormaPago($formaPagoString); // Valida
$estado = EstadoPedido::PENDIENTE_SUPERVISOR; // Enum con métodos

$pedido = new PedidoProduccion(
    clienteId: $cliente->id(),
    nombreCliente: $cliente->nombre(),
    formaPago: $formaPago->valor(),
    estado: $estado
);
```

---

### 11. **Inyección de Dependencias Excesiva**

```php
// ⚠️ 13 dependencias inyectadas
public function __construct(
    private PedidoProduccionService $pedidoService,
    private CreacionPedidoService $creacionPedidoService,
    private LogoPedidoService $logoPedidoService,
    private ProcesosPedidoService $procesosService,
    private NumeracionService $numeracionService,
    private DescripcionService $descripcionService,
    private ImagenService $imagenService,
    private CotizacionRepository $cotizacionRepository,
    private ListaPedidosService $listaPedidosService,
    private VariantesService $variantesService,
    private FormularioPedidoService $formularioPedidoService,
    private UtilitariosService $utilitariosService,
) {}

// ✅ MEJOR: Inyectar una Facade o CommandBus
public function __construct(
    private PedidosProduccionFacade $fachada,
) {}
```

---

### 12. **Falta de Separación: Queries vs Commands**

```php
// ❌ Mezclado en mismo controlador
public function index() { } // QUERY: leer
public function show($id) { } // QUERY: leer
public function crearForm() { } // QUERY: leer
public function crearDesdeCotizacion() { } // COMMAND: escribir
public function guardarLogoPedido() { } // COMMAND: escribir
public function crearSinCotizacion() { } // COMMAND: escribir

// ✅ DEBERÍA SER: Separación CQRS
class ListarPedidosQuery { }
class ObtenerPedidoQuery { }
class CrearPedidoDesdeCtaCommand { }
class GuardarLogoPedidoCommand { }
class CrearPedidoSinCtaCommand { }
```

---

### 13. **Transacciones Manuales en Servicios**

```php
// ⚠️ Cada método maneja su propia transacción
public function guardarLogoPedido() {
    DB::beginTransaction();
    try {
        // lógica
        DB::commit();
    } catch (...) {
        DB::rollBack();
    }
}

// ✅ DEBERÍA SER: Usar middleware transaccional
#[Transactional]
public function guardarLogoPedido() {
    // Laravel auto-wraps en transacción
}
```

---

### 14. **Logging Excesivo en Lógica de Negocio**

```php
// ⚠️ 50+ \Log::info() en la lógica de negocio
\Log::info('🎨 [LOGO desde Cotización] Creando logo_pedido...');
\Log::info('🎨 [LOGO desde Cotización] logo_cotizacion encontrado...');
\Log::info('✅ [LOGO desde Cotización] logo_pedido creado...');

// ✅ MEJOR: Logging separado en infraestructura
// La lógica de negocio NO debe saber sobre Log
// Los listeners manejan el logging
```

---

### 15. **Métodos sin Returntype** ⚠️

```php
// ❌ Sin return type
private function heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)

// ✅ CON return type
private function heredarVariantesDePrenda(
    Cotizacion $cotizacion, 
    PrendaPedido $prendaPedido, 
    int $index
): void
```

---

## 📋 TABLA COMPARATIVA: Antes vs Después de Corrección

| Métrica | Actual | Objetivo |
|---------|--------|----------|
| Líneas totales | 1,662 | < 300 |
| Métodos públicos | 14 | 6-8 |
| Máx líneas por método | 400+ (crearPrendaSinCotizacion) | < 50 |
| Inyecciones | 13 | 1-2 (Facade/CommandBus) |
| Responsabilidades | 6+ (HTTP, validación, BD, transformación, logging) | 1 (HTTP Adapter) |
| Métodos privados vacíos | 3 | 0 |
| Uso de agregados | 0% | 100% |
| Uso de Value Objects | 0% | 80%+ |
| Uso de Events de Dominio | 0% | Eventos en c/operación crítica |

---

## 🛠️ Plan de Corrección (Prioridad)

### FASE 1: CRÍTICA (Próxima sprint)
1. **Extraer `LogoPedidoService`** desde `guardarLogoPedido()`
2. **Crear `CreacionPrendaStrategy`** y eliminar lógica de `crearPrendaSinCotizacion()`
3. **Crear `LogoPedidoRepository`** para abstraer queries
4. **Implementar Agregados** LogoPedido, PrendaPedido con métodos de negocio

### FASE 2: IMPORTANTE (Sprint siguiente)
5. **Separar Queries de Commands** (CQRS básico)
6. **Crear Value Objects** para NombreCliente, FormaPago, etc.
7. **Implementar Events de Dominio** para operaciones críticas
8. **Eliminar métodos legacy** (_LEGACY, privados sin uso)

### FASE 3: OPTIMIZACIÓN (Sprint 3)
9. **Reducir inyecciones** a 1-2 usando Facade
10. **Mover transformación JSON** a DTOs/Transformers
11. **Implementar Validadores de Dominio** (no solo en controller)
12. **Refactor respuestas HTTP** a response builders

---

## 🎯 Recomendación Inmediata

El controlador está en **"tierra de nadie"**:
- ❌ Demasiada lógica para ser solo HTTP adapter
- ❌ Muy acoplado a detalles de BD
- ✅ Pero tiene servicios inyectados (buen inicio)

**Acción ahora:** 
1. Crear `LogoPedidoService` que encapsule `guardarLogoPedido()`
2. Crear `PrendaCreationStrategy` que simplifique `crearPrendaSinCotizacion()`
3. Eliminar métodos legacy y privados sin uso
4. Reducir a máx 30-40 líneas por método público

---

## 📍 Conclusión

**SOLID Status:** 2/5 ✅✅ (SRP, DIP violados)
**DDD Status:** 2/5 ✅✅ (Agregados incompletos, sin Events)
**Mantenibilidad:** 3/10 ⚠️

El controlador necesita una **refactorización profunda**, no ajustes superficiales. Los servicios inyectados son un buen paso, pero la lógica de negocio aún está demasiado distribuida.
