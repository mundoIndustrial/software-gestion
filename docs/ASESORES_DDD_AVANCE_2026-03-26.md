# Avance DDD Modulo Asesores

Fecha: 2026-03-26
Autor: Codex (sesion de refactor con el equipo)

## Resumen Ejecutivo

En esta iteracion se avanzo en la migracion de Asesores hacia DDD, enfocando primero los puntos con mayor acoplamiento en controlador y rutas.

Estado actual estimado de Asesores:

- Antes de esta iteracion: 68-72%
- Despues de esta iteracion: 78-82%

Resultado principal:

- Se redujo acoplamiento directo Controller -> Eloquent en operaciones clave de borradores y resolucion de pedidos.
- Se elimino logica de negocio en `routes/asesores.php` (closure realtime) y se movio a controller + use case.

## Cambios Implementados

### 1) Nuevos UseCases en Application (Asesores)

Se crearon los siguientes casos de uso:

- `app/Application/Asesores/UseCases/ListarBorradoresAsesorUseCase.php`
- `app/Application/Asesores/UseCases/EliminarBorradorAsesorUseCase.php`
- `app/Application/Asesores/UseCases/ResolverPedidoIdAsesorUseCase.php`

Objetivo:

- Centralizar reglas de aplicacion para borradores y resolucion de identificadores (`id` / `numero_pedido`) con validacion de ownership por asesor.

### 2) Refactor de AsesoresController

Archivo:

- `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php`

Se aplicaron estos cambios:

- Inyeccion de nuevos UseCases de Asesores.
- `obtenerNotasPedido($id)`:
  - Antes: hacia `PedidoProduccion::findOrFail(...)` directo.
  - Ahora: resuelve pedido por caso de uso (`ResolverPedidoIdAsesorUseCase`) y consulta por repositorio de lectura.
- `anularPedido($id)`:
  - Antes: resolvia pedido directo con query Eloquent en controller.
  - Ahora: resuelve `id` por caso de uso con validacion de ownership del asesor.
- `borradores()`:
  - Antes: query Eloquent directa en controller.
  - Ahora: delega a `ListarBorradoresAsesorUseCase`.
- `destroyBorrador()`:
  - Antes: query Eloquent y delete en controller.
  - Ahora: delega a `EliminarBorradorAsesorUseCase`.
- Nuevo metodo `listarRealtimePedidos()` para endpoint realtime de pedidos.

### 3) Ajuste en repositorio de lectura de pedidos

Archivo:

- `app/Infrastructure/Pedidos/Persistence/Eloquent/EloquentPedidoProduccionRepository.php`

Cambio:

- Se agrego filtro `sin_numero` en `obtenerPedidosAsesor()` para soportar el caso de borradores sin numero de pedido.

### 4) Refactor de rutas (eliminacion de closure con logica de negocio)

Archivo:

- `routes/asesores.php`

Cambio:

- Endpoint:
  - Antes: `Route::get('/realtime/pedidos', function () { ... })`
  - Ahora: `Route::get('/realtime/pedidos', [AsesoresController::class, 'listarRealtimePedidos'])`

Beneficio:

- Se evita logica de autorizacion + query de negocio en archivo de rutas.

## Pruebas Agregadas

Se crearon pruebas unitarias para los nuevos UseCases:

- `tests/Unit/Application/Asesores/UseCases/ListarBorradoresAsesorUseCaseTest.php`
- `tests/Unit/Application/Asesores/UseCases/EliminarBorradorAsesorUseCaseTest.php`
- `tests/Unit/Application/Asesores/UseCases/ResolverPedidoIdAsesorUseCaseTest.php`

Ejecucion:

- Comando: `php artisan test tests/Unit/Application/Asesores/UseCases`
- Resultado: `6 passed`

## Evidencia de Acoplamiento Reducido

Puntos que dejaron de tener query Eloquent directa en controller:

- Listado de borradores.
- Eliminacion de borradores.
- Resolucion de pedido para anulacion.
- Endpoint realtime movido de route closure a controller.

## Deuda Pendiente para "Asesores 100% DDD"

En `AsesoresController` todavia existen accesos directos a modelo (pendiente de migracion):

- `PedidoProduccion::findOrFail($id)` en `pendientesDetalle`.
- `\App\Models\Cotizacion::findOrFail($id)` en `editCotizacion` (2 puntos).
- `\App\Models\PedidoProduccion::find($id)` en `edit`.
- `PedidoProduccion::find((int)$id)` en `confirmarCorreccion`.

Total pendiente detectado en este corte: 5 referencias directas.

## Plan Recomendado (Siguiente Fase)

Fase siguiente sugerida (corta, segura):

1. Crear UseCases para:
   - `PendientesDetalleAsesorUseCase`
   - `ConfirmarCorreccionPedidoUseCase`
   - `ObtenerCotizacionEditableAsesorUseCase`
2. Mover acceso a cotizacion y pedido editable a repositorios de lectura/servicios de aplicacion.
3. Cubrir esos flujos con tests de aplicacion y feature.
4. Revalidar endpoint por endpoint.

Con esa fase, Asesores deberia quedar entre 90-95% DDD (restando solo detalles de uniformidad de naming y providers).

## Nota de Contexto del Workspace

Durante esta sesion existian cambios previos/no relacionados en otros archivos del modulo Insumos y frontend.
No se tocaron ni se revirtieron esos cambios para respetar el estado del trabajo en curso.
