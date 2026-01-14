# Refactorización DDD del Controlador PedidosProduccionController

**Fecha:** 14 de Enero de 2026  
**Objetivo:** Extraer la lógica de negocio del controlador a servicios de dominio según arquitectura DDD

## Cambios Realizados

### 1. Servicios Nuevos Creados

#### ListaPedidosService
- **Responsabilidad:** Manejar todas las queries de lectura de pedidos
- **Métodos:**
  - `obtenerPedidosProduccion(array $filtros)` - Obtener pedidos con filtros
  - `obtenerLogoPedidos(array $filtros)` - Obtener pedidos LOGO
  - `obtenerDetallePedido(int $pedidoId)` - Obtener detalle completo
  - `obtenerPlantillaPedido(int $pedidoId)` - Obtener plantilla ERP

**Antes:** Métodos `indexLegacy()`, `indexLogoPedidos()`, `show()`, `plantilla()`  
**Ahora:** Centralizado en un servicio reutilizable

---

#### VariantesService
- **Responsabilidad:** Herencia de variantes de cotización a pedido
- **Métodos:**
  - `heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)` - Mapea variantes
  - `obtenerOCrearColor(string)` - Crea/obtiene colores
  - `obtenerOCrearTela(string)` - Crea/obtiene telas

**Antes:** Método `heredarVariantesDePrenda()` (155 líneas) en controlador  
**Ahora:** Servicio dedicado con lógica limpia

---

#### FormularioPedidoService
- **Responsabilidad:** Preparar datos para formularios
- **Métodos:**
  - `obtenerDatosFormularioCrearDesdeCotizacion()` - Datos para vista
  - `obtenerDatosRouter(string $tipo)` - Datos para router

**Antes:** Queries directas en `crearForm()` y `crearFormEditable()`  
**Ahora:** Servicio centralizado

---

#### UtilitariosService
- **Responsabilidad:** Funciones helper de conversión
- **Métodos:**
  - `convertirEspecificacionesAlFormatoNuevo($especificaciones)` - Convierte formato
  - `procesarGeneros($generoInput)` - Procesa géneros

**Antes:** Métodos `convertirEspecificacionesAlFormatoNuevo()` (100+ líneas) y `procesarGeneros()` + `procesarMultiplesGeneros()`  
**Ahora:** Servicio reutilizable

---

### 2. Cambios en el Controlador

#### Inyección de Servicios
```php
public function __construct(
    // ... servicios existentes
    private ListaPedidosService $listaPedidosService,
    private VariantesService $variantesService,
    private FormularioPedidoService $formularioPedidoService,
    private UtilitariosService $utilitariosService,
) {}
```

#### Métodos Simplificados

**`crearForm()`**
```php
// Antes: 18 líneas con queries directas
// Ahora:
public function crearForm()
{
    $cotizaciones = $this->formularioPedidoService->obtenerDatosFormularioCrearDesdeCotizacion();
    return view('asesores.pedidos.crear-desde-cotizacion', compact('cotizaciones'));
}
```

**`crearFormEditable($tipo)`**
```php
// Antes: 30 líneas con queries y transformación
// Ahora:
public function crearFormEditable($tipo = 'cotizacion')
{
    if (!in_array($tipo, ['cotizacion', 'nuevo'])) {
        $tipo = 'cotizacion';
    }
    $data = $this->formularioPedidoService->obtenerDatosRouter($tipo);
    return view('asesores.pedidos.crear-pedido', $data);
}
```

**`index(Request $request)`**
```php
// Antes: 15 + 65 líneas (indexLegacy + indexLogoPedidos)
// Ahora:
public function index(Request $request)
{
    $filtros = ['estado' => $request->estado, 'fecha_desde' => $request->fecha_desde, 'fecha_hasta' => $request->fecha_hasta];
    
    if ($request->has('tipo') && $request->tipo === 'logo') {
        $pedidos = $this->listaPedidosService->obtenerLogoPedidos($filtros);
    } else {
        $pedidos = $this->listaPedidosService->obtenerPedidosProduccion($filtros);
    }
    return view('asesores.pedidos.index', compact('pedidos'));
}
```

**`show($id)` y `plantilla($id)`**
```php
// Antes: Queries y validaciones inline
// Ahora:
public function show($id)
{
    $pedido = $this->listaPedidosService->obtenerDetallePedido($id);
    // ... resto del código
}
```

#### Métodos Privados Eliminados
- ❌ `indexLegacy()` → ListaPedidosService
- ❌ `indexLogoPedidos()` → ListaPedidosService  
- ❌ `heredarVariantesDePrenda()` → VariantesService (solo delegación)
- ❌ `convertirEspecificacionesAlFormatoNuevo()` → UtilitariosService
- ❌ `procesarGeneros()` y `procesarMultiplesGeneros()` → UtilitariosService

### 3. Líneas de Código

| Métrica | Antes | Después | Cambio |
|---------|-------|---------|--------|
| **Líneas Controlador** | 1800+ | ~1200 | -33% |
| **Métodos Privados** | 6 | 2 | -67% |
| **Lógica de Negocio en Controlador** | ~600 líneas | ~100 líneas | -83% |
| **Servicios de Dominio** | 7 | 11 | +4 nuevos |

### 4. Principios SOLID Aplicados

✅ **SRP (Single Responsibility)**
- ListaPedidosService: Queries de lectura
- VariantesService: Herencia de variantes
- FormularioPedidoService: Datos de formularios
- UtilitariosService: Conversiones y helpers
- Controlador: Coordinación HTTP

✅ **DIP (Dependency Inversion)**
- Controlador inyecta servicios de dominio
- No accede directamente a modelos (excepto donde es necesario)

✅ **OCP (Open/Closed)**
- Fácil extender con nuevos tipos de filtros
- Nuevas conversiones sin modificar controlador

### 5. Beneficios

1. **Testabilidad:** Servicios pueden ser unitarios sin contexto HTTP
2. **Reutilización:** Servicios usables desde API, CLI, jobs, etc.
3. **Mantenibilidad:** Controlador limpio y enfocado
4. **Escalabilidad:** Fácil agregar nueva lógica sin quebrar existente
5. **Legibilidad:** Código más legible y autodocumentado

### 6. Uso de los Nuevos Servicios

#### En otros contextos (API, CLI, etc)

```php
// Desde API Controller
$pedidos = app(ListaPedidosService::class)->obtenerPedidosProduccion(['estado' => 'activo']);

// Desde Job
$generos = app(UtilitariosService::class)->procesarGeneros($input);

// Desde Listener
app(VariantesService::class)->heredarVariantesDePrenda($cot, $prenda, 0);
```

---

## Archivos Modificados

- `app/Http/Controllers/Asesores/PedidosProduccionController.php` - Refactorizado
- `app/Domain/PedidoProduccion/Services/ListaPedidosService.php` - ✨ NUEVO
- `app/Domain/PedidoProduccion/Services/VariantesService.php` - ✨ NUEVO
- `app/Domain/PedidoProduccion/Services/FormularioPedidoService.php` - ✨ NUEVO
- `app/Domain/PedidoProduccion/Services/UtilitariosService.php` - ✨ NUEVO

---

## Próximos Pasos

- [ ] Validar tests (unitarios de servicios)
- [ ] Migrar métodos de creación privados a servicios
- [ ] Eliminar métodos legacy completamente
- [ ] Aplicar mismo patrón a otros controladores
