# 📚 GUÍA DE INTEGRACIÓN - OPERARIOS CON PEDIDOS_PRODUCCION

## 🎯 Objetivo
Integrar completamente el sistema de operarios (cortador/costurero) con la tabla `pedidos_produccion` y sus relaciones.

---

## 📊 ESTRUCTURA DE DATOS

### Tablas Relacionadas
```
pedidos_produccion
├── numero_pedido (PK)
├── cliente
├── estado
├── forma_de_pago
├── asesora (asesor_id → User)
├── fecha_de_creacion_de_orden
├── fecha_estimada_de_entrega
└── novedades

prendas_pedido
├── id (PK)
├── numero_pedido (FK → pedidos_produccion)
├── nombre_prenda
├── cantidad
└── descripcion

procesos_prenda
├── id (PK)
├── numero_pedido (FK → pedidos_produccion)
├── proceso (Corte, Costura, Bordado, etc.)
├── estado_proceso (Pendiente, En Progreso, Completado)
├── fecha_inicio
├── fecha_fin
└── encargado
```

---

## 🔍 LÓGICA DE FILTRADO

### Para Cortador
```php
// Obtener procesos pendientes de CORTE
$procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
    ->where('proceso', 'Corte')
    ->where('estado_proceso', '!=', 'Completado')
    ->get();

// Si hay procesos de corte pendientes → mostrar pedido
if ($procesos->isNotEmpty()) {
    // Mostrar pedido en dashboard
}
```

### Para Costurero
```php
// Obtener procesos pendientes de COSTURA
$procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
    ->where('proceso', 'Costura')
    ->where('estado_proceso', '!=', 'Completado')
    ->get();

// Si hay procesos de costura pendientes → mostrar pedido
if ($procesos->isNotEmpty()) {
    // Mostrar pedido en dashboard
}
```

---

## 🔄 FLUJO DE DATOS

### 1. Obtener Pedidos del Operario
```
OperarioController::dashboard()
    ↓
ObtenerPedidosOperarioService::obtenerPedidosDelOperario($usuario)
    ↓
Obtener tipo de operario (cortador/costurero)
    ↓
Obtener área (Corte/Costura)
    ↓
ObtenerPedidosOperarioService::obtenerPedidosPorArea($area)
    ↓
PedidoProduccion::with('prendas')->get()
    ↓
Filtrar por procesos pendientes del área
    ↓
Formatear datos para DTO
    ↓
Retornar ObtenerPedidosOperarioDTO
    ↓
Vista renderiza dashboard
```

### 2. Formateo de Datos
```php
[
    'numero_pedido' => $pedido->numero_pedido,
    'cliente' => $pedido->cliente,
    'descripcion' => $prendas->pluck('nombre_prenda')->join(', '),
    'cantidad' => $prendas->sum('cantidad'),
    'estado' => $pedido->estado,
    'area' => $this->obtenerAreaActual($pedido->numero_pedido),
    'fecha_creacion' => $pedido->fecha_de_creacion_de_orden->format('d/m/Y'),
    'dia_entrega' => $pedido->dia_de_entrega,
    'fecha_estimada' => $pedido->fecha_estimada_de_entrega->format('d/m/Y'),
    'asesora' => $pedido->asesora->name,
    'forma_pago' => $pedido->forma_de_pago,
    'novedades' => $pedido->novedades,
]
```

---

## 📝 CAMPOS IMPORTANTES

### De pedidos_produccion
- `numero_pedido` - Identificador único del pedido
- `cliente` - Nombre del cliente
- `estado` - Estado actual (No iniciado, En Ejecución, Completada)
- `forma_de_pago` - Forma de pago
- `asesor_id` - ID de la asesora
- `fecha_de_creacion_de_orden` - Fecha de creación
- `fecha_estimada_de_entrega` - Fecha estimada
- `dia_de_entrega` - Días de entrega
- `novedades` - Novedades del pedido

### De prendas_pedido
- `numero_pedido` - Referencia al pedido
- `nombre_prenda` - Nombre de la prenda
- `cantidad` - Cantidad de prendas
- `descripcion` - Descripción de la prenda

### De procesos_prenda
- `numero_pedido` - Referencia al pedido
- `proceso` - Nombre del proceso (Corte, Costura, etc.)
- `estado_proceso` - Estado (Pendiente, En Progreso, Completado)
- `fecha_inicio` - Fecha de inicio
- `fecha_fin` - Fecha de fin
- `encargado` - Persona encargada

---

## 🛠️ IMPLEMENTACIÓN ACTUAL

### Service: ObtenerPedidosOperarioService
```php
// Obtiene pedidos de pedidos_produccion
// Filtra por procesos pendientes del área
// Formatea datos para respuesta
// Obtiene prendas y procesos asociados
```

**Métodos principales:**
- `obtenerPedidosDelOperario($usuario)` - Obtiene pedidos del operario
- `obtenerPedidosPorArea($area)` - Filtra por área
- `pedidoPertenecealArea($pedido, $area)` - Verifica si pertenece al área
- `formatearPedidos($pedidos)` - Formatea para respuesta
- `obtenerAreaActual($numeroPedido)` - Obtiene área actual

### Controller: OperarioController
```php
// dashboard() - Muestra dashboard con stats y pedidos
// misPedidos() - Muestra tabla de pedidos
// verPedido() - Muestra detalle de pedido
// obtenerPedidosJson() - API endpoint
// buscarPedido() - Búsqueda
```

---

## ✅ VALIDACIONES

### Cortador
- ✅ Solo ve pedidos con procesos "Corte" pendientes
- ✅ No ve pedidos completados
- ✅ Ve prendas del pedido
- ✅ Ve información de la asesora

### Costurero
- ✅ Solo ve pedidos con procesos "Costura" pendientes
- ✅ No ve pedidos completados
- ✅ Ve prendas del pedido
- ✅ Ve información de la asesora

---

## 🔗 RELACIONES ELOQUENT

### PedidoProduccion
```php
public function prendas(): HasMany
{
    return $this->hasMany(PrendaPedido::class, 'numero_pedido', 'numero_pedido');
}
```

### PrendaPedido
```php
public function pedido(): BelongsTo
{
    return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
}
```

### ProcesoPrenda
```php
public function pedido(): BelongsTo
{
    return $this->belongsTo(PedidoProduccion::class, 'numero_pedido', 'numero_pedido');
}
```

---

## 📱 VISTAS

### dashboard.blade.php
- Stats cards con información resumida
- Listado de órdenes en cards
- Filtrado por área automático
- Información de cliente, fecha, descripción
- Botón para ver detalle

### mis-pedidos.blade.php
- Tabla con columnas principales
- Filtro por estado
- Ordenamiento por: Reciente, Antiguo, Cliente
- Búsqueda en tiempo real

### ver-pedido.blade.php
- Información general del pedido
- Descripción de prendas
- Información de cantidad
- Información adicional
- Botones de acción

---

## 🚀 PRÓXIMOS PASOS

### Fase 2: Cambio de Estado
```php
// Implementar cambio de estado de procesos
Route::patch('/operario/pedido/{numeroPedido}/proceso/{procesoId}/estado', 
    [OperarioController::class, 'cambiarEstadoProceso']);
```

### Fase 3: Notificaciones
```php
// Agregar notificaciones en tiempo real
// Cuando se asigna un pedido al operario
// Cuando cambia el estado de un proceso
```

### Fase 4: Reportes
```php
// Crear reportes de productividad
// Tiempo en cada proceso
// Cantidad de prendas procesadas
// Eficiencia por operario
```

---

## 📌 NOTAS IMPORTANTES

1. **Filtrado Automático**: Los operarios solo ven pedidos de su área
2. **Procesos Pendientes**: Solo se muestran procesos no completados
3. **Información Completa**: Se obtienen prendas y procesos asociados
4. **Formato de Fechas**: Se usa formato d/m/Y para todas las fechas
5. **Relaciones**: Se usan relaciones via `numero_pedido`, no via `id`

---

## 🔧 TROUBLESHOOTING

### Problema: No aparecen pedidos
**Solución**: Verificar que existan procesos pendientes en `procesos_prenda`

### Problema: Área incorrecta
**Solución**: Verificar que el campo `proceso` en `procesos_prenda` sea exacto (Corte, Costura, etc.)

### Problema: Prendas no se muestran
**Solución**: Verificar que `numero_pedido` en `prendas_pedido` coincida con `numero_pedido` en `pedidos_produccion`

---

**Versión**: 1.0
**Última actualización**: 12 de Diciembre de 2025
