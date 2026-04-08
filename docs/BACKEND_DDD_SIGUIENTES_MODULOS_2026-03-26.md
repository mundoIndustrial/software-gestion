# Diagnóstico DDD del Backend después de Pedidos

Fecha: 2026-03-26

## Resumen ejecutivo

Después del cierre de `Pedidos`, los siguientes frentes con mayor retorno para continuar DDD son:

1. `Insumos`
2. `SupervisorPedidos`
3. `Asesores`

Orden recomendado:

1. `Insumos`
2. `SupervisorPedidos`
3. `Asesores`

La razón es simple:

- `Insumos` concentra hoy la mezcla más fuerte entre controller, request, Eloquent, `DB`, `Auth`, `Log` y renderizado
- `SupervisorPedidos` tiene buena intención de `UseCases`, pero esos use cases siguen ejecutando Eloquent directo
- `Asesores` es el módulo más grande y transversal; conviene atacarlo después de consolidar los dos anteriores

---

## 1. Insumos

### Estado actual

`Insumos` todavía está lejos de DDD consistente.

Piezas clave:

- `app/Infrastructure/Insumos/InsumosService.php`
- `app/Infrastructure/Insumos/Controllers/InsumosController.php`
- `app/Infrastructure/Insumos/Controllers/Api/InsumosApiController.php`

### Hallazgos

- `InsumosService` mezcla:
  - modelos Eloquent
  - `DB::table()`
  - `Auth`
  - `Log`
  - `Request`
  - validaciones
  - paginación
  - renderizado de vistas
  - cambios de estado
  - creación de procesos automáticos
  - broadcast/eventos
- el controller todavía delega demasiado a un mega-servicio en lugar de a casos de uso pequeños
- hay partes nuevas con intención DDD, por ejemplo:
  - `GuardarAnchoMetrajeUseCase`
  - `GuardarAnchoMetrajeDTO`
  - `AplicarFiltrosService`
  pero conviven con una masa legacy muy grande

### Diagnóstico

Arquitectura actual:

- `Infrastructure-heavy service`
- `Laravel clásico`
- `DDD parcial`

Nivel estimado DDD:

- 25-35%

### Qué sigue

Fase 1:

- partir `InsumosService` en use cases concretos:
  - listar recibos/materiales
  - guardar materiales
  - eliminar material
  - cambiar estado pedido
  - cambiar estado recibo
  - guardar ancho/metraje

Fase 2:

- sacar `Request`, `Auth`, `Log`, `DB` del centro de la lógica
- dejar controllers solo como adaptadores HTTP

Fase 3:

- definir repositorios/ports para:
  - recibos de insumos
  - materiales
  - cambios de estado
  - ancho/metraje

Fase 4:

- separar lectura de escritura con DTOs/read models donde aplique

### Prioridad

Muy alta.

---

## 2. SupervisorPedidos

### Estado actual

`SupervisorPedidos` tiene mejor forma que `Insumos`, pero todavía no está limpio.

Piezas clave:

- `app/Application/SupervisorPedidos/UseCases/*`
- `app/Infrastructure/Http/Controllers/SupervisorPedidos/*`

### Hallazgos

- hay muchos `UseCases` y `DTOs`, lo cual es buena base
- pero varios use cases siguen usando Eloquent directo, por ejemplo:
  - `ListOrdersUseCase` usa `App\Models\PedidoProduccion`
  - usa `SeleccionPedido`
  - usa `Log`
  - arma queries, filtros, ordenamiento y paginación directamente
- `SupervisorOrdersController` sigue teniendo fugas:
  - usa `PedidoProduccion::find(...)`
  - usa `Auth`
  - usa `app(...)` para resolver `ObtenerFacturaUseCase`
  - emite broadcasts directamente

### Diagnóstico

Arquitectura actual:

- `UseCase-first`
- `DDD parcial`
- `Laravel application services`

Nivel estimado DDD:

- 50-60%

### Qué sigue

Fase 1:

- mover consultas Eloquent pesadas de los use cases a repositorios/read repositories

Fase 2:

- consolidar un puerto de lectura para supervisor:
  - listado de órdenes
  - filtros
  - detalles
  - comparaciones
  - conteos/notificaciones

Fase 3:

- limpiar controllers:
  - evitar `PedidoProduccion::find(...)`
  - evitar `app(...)`
  - dejar broadcasting detrás de puertos o servicios de infraestructura

Fase 4:

- revisar si todos los `DTOs` actuales agregan valor o si parte de la capa está sobredimensionada

### Prioridad

Alta.

---

## 3. Asesores

### Estado actual

`Asesores` es el módulo más extenso y heterogéneo.

Piezas clave:

- `app/Infrastructure/Http/Controllers/Asesores/*`
- `app/Application/Services/Asesores/*`

### Hallazgos

- conviven use cases nuevos con servicios legacy grandes
- el controller histórico `AsesoresController` sigue siendo enorme y mezcla muchos subflujos
- varios servicios de `Application/Services/Asesores` siguen acoplados a framework y Eloquent
- ejemplos claros:
  - `ObtenerPedidoDetalleService` usa `PedidoProduccion`, `Auth`, `Log`, `DB`
  - `ObtenerPedidosService` usa `PedidoProduccion`, `Auth`, `Cache`, `app()`
  - `PerfilService` usa `Auth`, `Storage`, logs y manipulación de archivos
  - `ProcesarFotosTelasService` recibe `Request` y usa `Storage`

### Diagnóstico

Arquitectura actual:

- `mixto`
- `controller/service legacy`
- `DDD parcial en algunos flujos`

Nivel estimado DDD:

- 35-45%

### Qué sigue

Fase 1:

- dividir `Asesores` por subdominios funcionales antes de refactorizar:
  - pedidos
  - perfil
  - dashboard
  - recibos/factura
  - detalle/edición
  - fotos/archivos

Fase 2:

- reducir `AsesoresController` delegando a controllers más específicos o entrypoints ya existentes

Fase 3:

- mover lógica de lectura pesada de servicios como `ObtenerPedidoDetalleService` a read repositories / assemblers

Fase 4:

- sacar de `Application` lo que en realidad es infraestructura:
  - `Request`
  - `Storage`
  - queries `DB`
  - acceso a `Auth`

### Prioridad

Alta, pero después de `Insumos` y `SupervisorPedidos`.

---

## Semáforo

### Insumos

- Arquitectura actual: rojo
- Base reusable: amarilla
- Prioridad: muy alta

### SupervisorPedidos

- Arquitectura actual: amarilla
- Base reusable: verde
- Prioridad: alta

### Asesores

- Arquitectura actual: rojo/amarillo
- Base reusable: amarilla
- Prioridad: alta

---

## Plan recomendado del backend

### Etapa 1

Consolidar `Insumos`:

- reemplazar `InsumosService` por casos de uso pequeños
- aislar persistencia
- dejar controllers delgados

### Etapa 2

Consolidar `SupervisorPedidos`:

- mover queries desde use cases a read repositories
- limpiar controllers y side effects

### Etapa 3

Refactorizar `Asesores` por slices:

- empezar por `detalle de pedido`
- seguir con `listado`
- luego `factura/recibos`
- después `perfil` y `archivos`

---

## Conclusión

Después de `Pedidos`, el siguiente cuello de botella arquitectónico real del backend es `Insumos`.

Si el objetivo es llevar el backend completo hacia DDD de forma ordenada, el roadmap más sano es:

- `Pedidos` cerrado
- `Insumos`
- `SupervisorPedidos`
- `Asesores`
