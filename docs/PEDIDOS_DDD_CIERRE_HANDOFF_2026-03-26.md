# Cierre DDD de Pedidos

Fecha: 2026-03-26

## Estado final

El módulo `Pedidos` queda cerrado como referencia DDD del backend:

- `Domain/Pedidos` sin dependencias de Laravel, facades ni `App\Models`
- `Application` como capa de orquestación y adaptación
- `Infrastructure` concentrando Eloquent, controllers, listeners, storage y persistencia
- CQRS de `Pedidos` movido fuera de `Domain`
- providers y wiring consistentes con la arquitectura por capas
- contratos y nombres principales estabilizados

## Cambios estructurales relevantes

### Limpieza de capas

- `CommandHandlers` y `QueryHandlers` de `Pedidos` movidos de `Domain` a `Application`
- DTOs, validators, listeners y servicios operativos que no eran dominio puro salieron de `Domain`
- `Despacho` fue limpiado para dejar en `Application` la lógica operativa y en `Infrastructure` la persistencia
- `app/Modules/Pedidos` fue desactivado y podado como arquitectura paralela

### Repositorios y naming

- `PedidoProduccionRepository` dejó de ser implementación concreta en `Domain`
- se consolidó `PedidoProduccionReadRepository` como contrato oficial
- se eliminó el alias deprecated `PedidoProduccionRepository`
- la clase de infraestructura con nombre ambiguo fue renombrada a `PedidoProduccionTrackingRepository`

### Contrato de lectura refinado

Se eliminaron exposiciones directas de modelos Eloquent desde el contrato de dominio:

- `obtenerPrendaDelPedido` -> `PedidoPrendaRef`
- `obtenerEppConImagenes` -> `PedidoEppRef`
- `obtenerPorIdYAsesor` -> `PedidoBorradorRef`
- `findByNumeroPedido` -> `PedidoNumeroRef`
- `obtenerPorId` removido del contrato

Además:

- la paginación de `PedidoProduccionReadRepository` ya no expone tipos de Laravel
- el dominio usa `PaginatedPedidosResult` y `PedidoProduccionListItem`
- la adaptación a `LengthAwarePaginator` quedó en `Application`

### Dominio puro

Se eliminaron dependencias residuales a `Illuminate\Support\Collection` dentro de `Domain/Pedidos`:

- `PedidoAggregate`
- `DesparChoParcialesRepository`
- `DesparChoParcialesPersistenceService`

## Blindaje

Se agregó un test de arquitectura:

- `tests/Feature/Architecture/PedidosDomainArchitectureTest.php`

Ese test falla si `Domain/Pedidos` vuelve a introducir:

- `App\Models`
- `Illuminate`
- facades
- `DB::`, `Log::`, `Storage::`, `Auth::`
- `app(...)` o `resolve(...)`

## Verificación ejecutada

Pruebas usadas para validar los cortes principales:

- `tests/Feature/Architecture/PedidosDomainArchitectureTest.php`
- `tests/Feature/CQRS/PedidosCommandBusResolutionTest.php`
- `tests/Feature/ProcesosRenderTest.php`
- tests focalizados de borradores y repositorio de pedidos

Resultado del cierre:

- `Domain/Pedidos` limpio a nivel arquitectónico
- naming estabilizado
- contrato de lectura sin fugas gruesas a Laravel
- flujo principal de `Pedidos` alineado con DDD por capas

## Regla de mantenimiento

Para cambios nuevos en `Pedidos`:

1. No introducir `App\Models`, facades ni `Illuminate` dentro de `Domain/Pedidos`
2. Los controllers solo adaptan HTTP y delegan
3. Los use cases orquestan, no consultan Eloquent directamente
4. Toda persistencia concreta debe vivir en `Infrastructure`
5. Si aparece una necesidad de lectura operativa, modelarla con `ReadModels` o resultados propios del dominio

## Conclusión

`Pedidos` queda cerrado como módulo guía para futuras migraciones DDD del backend.
No se detectan deudas gruesas activas dentro de `Domain/Pedidos`.
