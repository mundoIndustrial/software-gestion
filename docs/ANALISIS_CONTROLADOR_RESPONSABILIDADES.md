# Análisis de Responsabilidades del Controlador

## 🔍 PROBLEMAS IDENTIFICADOS

El controlador `PedidosProduccionController.php` actualmente tiene **MÚLTIPLES RESPONSABILIDADES** que violan el principio de Single Responsibility:

---

##  RESPONSABILIDADES QUE NO SON HTTP

### 1. **ACCESO DIRECTO A BASE DE DATOS (Queries Eloquent)**

El controlador hace queries directas a modelos en múltiples lugares:

```php
// Línea 53 - crearForm()
$cotizaciones = Cotizacion::where('asesor_id', Auth::id())
    ->whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])
    ->with([...])
    ->get();

// Línea 204 - show()
$pedido = PedidoProduccion::findOrFail($id);

// Línea 431 - crearDesdeCotizacion_LEGACY()
$cantidadTotalPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
    ->sum('cantidad');

// Línea 455
$reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacion->id)->first();

// Línea 522
$prendasPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();

// Línea 692
$logoCotizacion = \App\Models\LogoCotizacion::find($logoCotizacionId);

// Línea 1113
$prendasCot = \App\Models\PrendaCot::where('cotizacion_id', $cotizacion->id)->get();

// Línea 1648
$prendas = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)->get();

// Línea 1672
$procesosExistentes = ProcesoPrenda::where('prenda_pedido_id', $prenda->id)->pluck('proceso')->toArray();
```

**PROBLEMA:** El controlador debería delegar TODAS las queries a repositorios.

---

### 2. **TRANSACCIONES DE BASE DE DATOS**

El controlador maneja transacciones DB directamente:

```php
// Línea 266, 785, 873, 1752, 1878, etc.
DB::beginTransaction();
// ... lógica de negocio ...
DB::commit();
// ... catch ...
DB::rollBack();
```

**PROBLEMA:** Las transacciones deberían estar en los servicios de dominio, no en el controlador.

---

### 3. **CREACIÓN Y ACTUALIZACIÓN DE MODELOS**

El controlador crea y actualiza modelos directamente:

```php
// Línea 434
$pedido->update(['cantidad_total' => $cantidadTotalPedido]);

// Línea 1145
$prendaPedido->update([
    'color_id' => $prendaCot->color_id,
    'tela_id' => $prendaCot->tela_id,
    ...
]);

// Línea 1222
$prendaPedido->update([
    'color_id' => $colorId,
    'tela_id' => $telaId,
    ...
]);

// Línea 1029
DB::table('logo_pedidos')->where('id', $pedidoId)->update($updateData);

// Línea 2271
$pedido->update(['cantidad_total' => $cantidadTotalPedido]);
```

**PROBLEMA:** La creación/actualización de modelos debería estar en servicios.

---

### 4. **LÓGICA DE NEGOCIO COMPLEJA**

El controlador contiene lógica de negocio que debería estar en servicios:

#### a) Cálculos de cantidades
```php
// Línea 431
$cantidadTotalPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
    ->sum('cantidad');
```

#### b) Procesamiento de datos
```php
// Líneas 1900-1952 - Procesamiento complejo de cantidades por género
$cantidadesPorGeneroTalla = null;
$cantidadesPorTalla = [];
// ... 50+ líneas de lógica de procesamiento ...
```

#### c) Herencia de variantes
```php
// Líneas 1100-1247 - heredarVariantesDePrenda()
// 147 líneas de lógica de negocio para heredar variantes
```

#### d) Conversión de especificaciones
```php
// Líneas 1510-1583 - convertirEspecificacionesAlFormatoNuevo()
// 73 líneas de transformación de datos
```

---

### 5. **VALIDACIONES DE NEGOCIO**

El controlador valida reglas de negocio:

```php
// Línea 1600-1604
if (!$cotizacion->tipoCotizacion) {
    \Log::info('⏭️ No hay tipo de cotización asociado');
    return;
}

// Línea 1606-1612
$tipoCotizacion = strtolower(trim($cotizacion->tipoCotizacion->nombre ?? ''));
if ($tipoCotizacion !== 'reflectivo') {
    \Log::info('⏭️ No es cotización reflectivo');
    return;
}
```

**PROBLEMA:** Las validaciones de negocio deberían estar en servicios de dominio.

---

### 6. **LOGGING EXTENSIVO**

El controlador tiene logging de lógica de negocio (no solo de HTTP):

```php
\Log::info('📦 [SIN COTIZACIÓN] Creando pedido', [...]);
\Log::info(' Pedido creado', [...]);
\Log::info(' Prendas encontradas', [...]);
\Log::info('🔍 [DESCRIPCION] Construyendo descripción', [...]);
```

**PROBLEMA:** El logging de lógica de negocio debería estar en los servicios.

---

### 7. **MÉTODOS PRIVADOS CON LÓGICA DE NEGOCIO**

El controlador tiene múltiples métodos privados que contienen lógica de negocio:

- `heredarVariantesDePrenda()` - 147 líneas
- `convertirEspecificacionesAlFormatoNuevo()` - 73 líneas
- `crearProcesosParaReflectivo_LEGACY()` - 110 líneas
- `obtenerDatosCotizacion_LEGACY()` - 200+ líneas

**PROBLEMA:** Estos métodos deberían ser servicios de dominio.

---

### 8. **MÉTODOS LEGACY SIN ELIMINAR**

El controlador tiene métodos marcados como LEGACY que aún no se han eliminado:

- `crearDesdeCotizacion_LEGACY()` - ~500 líneas
- `crearProcesosParaReflectivo_LEGACY()` - ~110 líneas
- `obtenerDatosCotizacion_LEGACY()` - ~200 líneas
- `crearPrendaSinCotizacion_LEGACY_BACKUP()` - ~400 líneas

**PROBLEMA:** Estos métodos legacy ocupan ~1200 líneas de código muerto.

---

## 📊 RESUMEN DE VIOLACIONES DDD

| Responsabilidad | Líneas Aprox | Debería estar en |
|----------------|--------------|------------------|
| Queries Eloquent directas | ~50 líneas | Repositorios |
| Transacciones DB | ~30 líneas | Servicios |
| Creación/Actualización modelos | ~100 líneas | Servicios |
| Lógica de negocio compleja | ~300 líneas | Servicios |
| Validaciones de negocio | ~50 líneas | Servicios |
| Métodos privados con lógica | ~530 líneas | Servicios |
| Métodos LEGACY | ~1200 líneas | ELIMINAR |
| **TOTAL** | **~2260 líneas** | **Fuera del controlador** |

---

##  LO QUE EL CONTROLADOR DEBERÍA HACER (SOLO HTTP)

Un controlador siguiendo DDD debería SOLO:

1. **Recibir Request HTTP**
2. **Validar formato de datos** (no reglas de negocio)
3. **Delegar a servicios de dominio**
4. **Retornar Response HTTP**

### Ejemplo de método CORRECTO:

```php
public function crearDesdeCotizacion($cotizacionId)
{
    try {
        // 1. Recibir request (ya lo tiene)
        // 2. Validar formato (opcional, puede ser FormRequest)
        // 3. Delegar a servicio
        $resultado = $this->creacionPedidoService->crearDesdeCotizacion($cotizacionId);
        
        // 4. Retornar response
        return response()->json($resultado);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al crear el pedido: ' . $e->getMessage()
        ], 500);
    }
}
```

---

## 🎯 ACCIONES REQUERIDAS

### 1. **Crear Repositorios Adicionales**
- `PedidoProduccionRepository` - Para queries de pedidos
- `PrendaPedidoRepository` - Para queries de prendas
- `ProcesosPrendaRepository` - Para queries de procesos

### 2. **Mover Transacciones a Servicios**
- Todas las transacciones DB deben estar en servicios
- El controlador NO debe manejar transacciones

### 3. **Extraer Métodos Privados a Servicios**
- `heredarVariantesDePrenda()` → Nuevo servicio `VariantesService`
- `convertirEspecificacionesAlFormatoNuevo()` → `CotizacionRepository`

### 4. **Eliminar Métodos LEGACY**
- Eliminar todos los métodos `*_LEGACY()` y `*_LEGACY_BACKUP()`
- Esto liberará ~1200 líneas de código

### 5. **Refactorizar Métodos Restantes**
- `crearForm()` - Delegar query al repositorio
- `show()` - Delegar query al repositorio
- `plantilla()` - Delegar query al repositorio
- `crearSinCotizacion()` - Delegar completamente al servicio
- `guardarLogoPedido()` - Delegar completamente al servicio

---

## 📈 RESULTADO ESPERADO

**Controlador Actual:** ~2555 líneas  
**Código a eliminar (LEGACY):** ~1200 líneas  
**Código a mover a servicios:** ~1060 líneas  
**Controlador Final Esperado:** ~300 líneas 

**Reducción Total:** 88% 🎯
