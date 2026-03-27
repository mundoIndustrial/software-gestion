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

---

## Continuacion de Refactor (2026-03-27)

En la siguiente iteracion se aplico una segunda fase de refactor sobre `AsesoresController`:

### Cambios adicionales

- Se agrego `obtenerPedidoPorId(int $pedidoId): ?array` al contrato:
  - `app/Domain/Pedidos/Repositories/PedidoProduccionReadRepository.php`
- Se implemento en infraestructura:
  - `app/Infrastructure/Pedidos/Persistence/Eloquent/EloquentPedidoProduccionRepository.php`
- Nuevo use case:
  - `app/Application/Asesores/UseCases/ConfirmarCorreccionPedidoUseCase.php`
- Refactor en controller:
  - `pendientesDetalle()` ya no usa `PedidoProduccion::findOrFail()`
  - `edit()` ya no usa `\App\Models\PedidoProduccion::find()`
  - `confirmarCorreccion()` delega a `ConfirmarCorreccionPedidoUseCase` y evita acceso directo al modelo

### Pruebas agregadas

- `tests/Unit/Application/Asesores/UseCases/ConfirmarCorreccionPedidoUseCaseTest.php`

Ejecucion de suite:

- `php artisan test tests/Unit/Application/Asesores/UseCases`
- Resultado: `8 passed`

### Estado actualizado de acoplamiento directo en AsesoresController

Referencias directas restantes:

- `\App\Models\Cotizacion::findOrFail($id)` en `editCotizacion` (2 puntos)

Estado despues de esta continuacion:

- Antes de continuar: 5 referencias directas en controller.
- Despues: 2 referencias directas en controller.

## Continuacion de Refactor (2026-03-27 - Fase 3)

Se completo el retiro de accesos directos a modelos en `AsesoresController`.

Cambios:

- Se extendio `CotizacionDetalleRepositoryInterface` con `obtenerResumenCotizacion(...)`.
- Se implemento en `app/Infrastructure/Repositories/Cotizacion/CotizacionDetalleRepository.php`.
- Nuevo use case:
  - `app/Application/Asesores/UseCases/ObtenerCotizacionEditableAsesorUseCase.php`
- `editCotizacion()` ahora delega owner-check y carga base de cotizacion a UseCase/repository.

Estado final del controller en esta fase:

- Referencias directas a `\App\Models\PedidoProduccion` / `\App\Models\Cotizacion`: 0

Prueba agregada:

- `tests/Unit/Application/Asesores/UseCases/ObtenerCotizacionEditableAsesorUseCaseTest.php`

Suite actual de Asesores UseCases:

- `php artisan test tests/Unit/Application/Asesores/UseCases`
- Resultado: `10 passed`

## Continuacion de Refactor (2026-03-27 - Fase 4)

Se extendio el alcance a `PedidoCommandController` para sostener el mismo criterio DDD en la capa HTTP de comandos:

- Archivo:
  - `app/Infrastructure/Http/Controllers/PedidoCommandController.php`
- Metodos intervenidos:
  - `actualizarDescripcion()`
  - `actualizarEstado()`

Cambio aplicado:

- Se removio acceso directo a `\App\Models\PedidoProduccion`.
- Se delego lectura y mutacion a `PedidoProduccionReadRepository`:
  - `obtenerPedidoPorId(...)`
  - `actualizarDatosBasicos(...)`

Resultado:

- Controller de comandos sin `find()/findOrFail()` de Eloquent para esos flujos.
- Contrato HTTP y respuestas se mantuvieron.

## Continuacion de Refactor (2026-03-27 - Fase 5)

Se avanzo en `CotizacionesViewController` del modulo Asesores con foco en desacoplar consultas de listado/contador:

- Archivo:
  - `app/Infrastructure/Http/Controllers/Asesores/CotizacionesViewController.php`

Cambios aplicados:

- `index()`:
  - Antes: cargaba cotizaciones con `\App\Models\Cotizacion::where(...)->with(...)->get()` en el controller.
  - Ahora: usa `ListarCotizacionesHandler` (`ListarCotizacionesQuery`) y mapea DTOs para la vista.
- `cotizacionesPendientesAprobadorCount()`:
  - Antes: `\App\Models\Cotizacion::where('estado', 'APROBADA_CONTADOR')->count()` directo.
  - Ahora: delega a `ContarCotizacionesPorEstadoUseCase`.
- `resources/views/components/cotizaciones/table.blade.php`:
  - Antes: hacia `\App\Models\LogoCotizacion::where(...)->exists()` dentro de la vista.
  - Ahora: usa `tiene_logo` precalculado desde controller/DTO.

Nuevos artefactos DDD:

- `app/Application/Asesores/UseCases/ContarCotizacionesPorEstadoUseCase.php`
- `tests/Unit/Application/Asesores/UseCases/ContarCotizacionesPorEstadoUseCaseTest.php`

Ajustes de contrato/repositorio para soportar el caso:

- `app/Domain/Cotizacion/Repositories/CotizacionRepositoryInterface.php`
  - Nuevo metodo: `countByEstado(string $estado): int`
- `app/Infrastructure/Persistence/Eloquent/Repositories/EloquentCotizacionRepository.php`
  - Implementacion de `countByEstado(...)`
  - Enriquecimiento de listados con relaciones `cliente` y `prendas`
- `app/Application/Cotizacion/DTOs/CotizacionDTO.php`
  - Se adiciona `createdAt` para preservar fecha de creacion al renderizar vistas.

Estado de acoplamiento directo restante en este controller:

- Permanece en `getDatosForModal()` una carga directa con `\App\Models\Cotizacion::with(...)->findOrFail(...)`.

Estimacion de avance DDD en Asesores tras esta fase:

- Rango anterior: 88-92%
- Rango actual: 91-94%

## Continuacion de Refactor (2026-03-27 - Fase 6)

Se elimino el ultimo acceso directo a `\App\Models\Cotizacion` que quedaba en `CotizacionesViewController`:

- `getDatosForModal()` ahora delega en:
  - `app/Application/Asesores/UseCases/ObtenerDatosCotizacionModalUseCase.php`
  - `app/Domain/Cotizacion/Repositories/CotizacionDetalleRepositoryInterface.php` (nuevo metodo `obtenerCotizacionParaModal`)
  - `app/Infrastructure/Repositories/Cotizacion/CotizacionDetalleRepository.php` (implementacion)

Prueba unitaria agregada:

- `tests/Unit/Application/Asesores/UseCases/ObtenerDatosCotizacionModalUseCaseTest.php`

Validacion:

- `php artisan test tests/Unit/Application/Asesores/UseCases`
- Resultado: `13 passed`

Estado:

- `AsesoresController` + `CotizacionesViewController` quedaron sin referencias directas a modelos para estos flujos refactorizados.

## Continuacion de Refactor (2026-03-27 - Fase 7: SOLID Controller Split)

Se aplico limpieza de malas practicas SOLID/DDD en `AsesoresController`:

- Se extrajo endpoint realtime a:
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresRealtimePedidosController.php`
- Se extrajeron endpoints de documentos (factura/recibos) a:
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresPedidoDocumentosController.php`
- Se movio la ruta de inventario para evitar `app(...)` service locator:
  - de `AsesoresController@inventarioTelas` a `AsesoresInventarioTelasController@index`

Rutas actualizadas:

- `routes/asesores.php`
  - `/realtime/pedidos` -> `AsesoresRealtimePedidosController@listar`
  - `/inventario-telas` -> `AsesoresInventarioTelasController@index`
- `routes/web.php`
  - `/pedidos-public/{id}/factura-datos` -> `AsesoresPedidoDocumentosController@obtenerDatosFactura`

Mejoras de seguridad y contrato HTTP:

- Se eliminaron payloads `debug` en respuesta realtime.
- Se estandarizaron errores para no exponer mensajes internos de excepcion en endpoints extraidos.
- Se ajustaron errores `abort(500, ...)` sensibles en `AsesoresController`.

## Continuacion de Refactor (2026-03-27 - Fase 8: Thin Controllers)

Se profundizo el desacople para acercar el modulo al criterio "controller delgado":

- Nuevos controllers especializados:
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresDashboardController.php`
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresPerfilController.php`
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresNotificacionesController.php`
- `routes/asesores.php` actualizado para que dashboard/perfil/notificaciones ya no pasen por `AsesoresController`.
- `AsesoresController` se limpio de dependencias de dashboard/perfil/notificaciones y de metodos movidos.
- Se saneo manejo de errores en multiples endpoints para no exponer mensajes internos al cliente.
- Se restituyo `anularPedido(...)` como endpoint delgado en contexto de asesor (delegando en use cases).

## Continuacion de Refactor (2026-03-27 - Fase 9: Split Pedidos en Controllers Dedicados)

Se completo el split principal de `AsesoresController` para pedidos en tres controllers especializados:

- `app/Infrastructure/Http/Controllers/Asesores/AsesoresPedidosViewController.php`
- `app/Infrastructure/Http/Controllers/Asesores/AsesoresPedidosQueryController.php`
- `app/Infrastructure/Http/Controllers/Asesores/AsesoresPedidosCommandController.php`

Rutas migradas:

- `routes/asesores.php`: endpoints de pedidos (views, queries y commands) ahora apuntan a los controllers de arriba.
- `routes/api-realtime.php`: `/realtime/pedidos` ahora usa `AsesoresPedidosQueryController@apiListar`.

Resultado:

- `AsesoresController` deja de ser punto de entrada operativo para el modulo de asesores en rutas principales.
- Se reduce fuertemente el acoplamiento y el tamaño del agregado HTTP.

## Continuacion de Refactor (2026-03-27 - Fase 10: Retiro de Legacy Controller)

Se retiro el controller legado:

- Eliminado:
  - `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php`

Verificacion:

- No quedan referencias activas a `AsesoresController` en rutas de asesores/realtime.
- Validacion sintactica OK de controllers/rutas nuevas.
