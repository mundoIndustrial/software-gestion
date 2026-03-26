# Diagnostico DDD de Pedidos

Fecha: 2026-03-26

## Resumen ejecutivo

El modulo `Pedidos` ya no esta en un estado hibrido grave. La arquitectura dominante paso a ser una organizacion por capas con una separacion mucho mas clara entre:

- `Application`
- `Domain`
- `Infrastructure`

La refactorizacion realizada saco de `Domain` la mayor parte de la logica que en realidad era:

- persistencia Eloquent
- wiring CQRS
- listeners
- servicios operativos
- validacion de payload HTTP o frontend
- repositorios concretos con `DB::table()`

Hoy `Pedidos` puede considerarse:

- `80-90%` alineado con DDD en el flujo principal
- `85-90%` limpio dentro de `app/Domain/Pedidos`
- `70-80%` consolidado en el backend relacionado, contando bordes legacy

## Estado por capa

### Domain

Semaforo: Verde con deuda menor

Estado actual:

- Se conservaron agregados, entities, events, enums, value objects, commands, queries y contratos.
- Ya no quedan referencias directas a `Application` o `Infrastructure` dentro de `app/Domain/Pedidos`.
- Se removieron de `Domain` listeners, handlers CQRS, DTOs de payload, servicios de despacho operativos, validadores de frontend y repositorios concretos legacy.

Deuda restante:

- `app/Domain/Pedidos/Repositories/PedidoProduccionRepository.php` sigue tipando modelos Eloquent:
  - `PedidoProduccion`
  - `PedidoEpp`
  - `PrendaPedido`
- Esto no es una fuga gruesa, pero si mantiene acoplamiento semantico del contrato del dominio con infraestructura Laravel.

### Application

Semaforo: Verde

Estado actual:

- Los `UseCases` principales de `Pedidos` quedaron mas cerca de orquestacion que de acceso directo a infraestructura.
- Los `CommandHandlers` y `QueryHandlers` de `Pedidos` ya viven en `Application`.
- Validadores que dependen de Laravel o BD fueron reubicados a `Application`:
  - `PedidoJSONValidator`
  - `PedidoValidator`
- Los servicios operativos de `Despacho` ahora viven en `Application`.

Deuda restante:

- Aun hay una mezcla historica de nombres y estilos entre:
  - `UseCases`
  - `Handlers`
  - `Services`
  - `Bridge`
- No es deuda critica, pero si una deuda de uniformidad.

### Infrastructure

Semaforo: Verde

Estado actual:

- La persistencia concreta de `Pedidos` esta mucho mas concentrada en `Infrastructure`.
- Se movieron ahi repositorios legacy concretos:
  - `LogoPedidoRepository`
  - `ColoresPorTallaRepository`
  - `CotizacionRepository`
- El repositorio Eloquent de `PedidoProduccion` absorbio elementos relacionales que no eran dominio:
  - `GestionaTallasRelacional`
- Los listeners de eventos de `Pedidos` tambien quedaron en `Infrastructure`.

Deuda restante:

- Existen repositorios y servicios en infraestructura con estilos distintos entre si.
- Falta un criterio uniforme para nombrar implementaciones Eloquent y repos legacy.

### CQRS

Semaforo: Verde

Estado actual:

- `CommandHandlers` y `QueryHandlers` de `Pedidos` fueron sacados de `Domain`.
- `CQRSServiceProvider` fue reordenado para separar mejor el wiring por bloques.
- Ya no hay una mezcla fuerte de command/query handlers de `Pedidos` viviendo como dominio.

Deuda restante:

- El provider sigue concentrando bastante wiring manual.
- A futuro conviene modularizar registros por bounded context o feature set.

### Despacho

Semaforo: Verde

Estado actual:

- `DespachoGeneradorService`
- `DespachoEstadoService`
- `DespachoValidadorService`

ya no estan en `Domain` cuando su comportamiento real es de aplicacion o infraestructura.

Se mantuvo en dominio lo que si tiene mejor sentido como contrato o logica asociada al agregado de despacho:

- entidad
- excepcion
- contrato de repositorio
- servicio de persistencia

Deuda restante:

- `DesparChoParcialesPersistenceService` sigue siendo debatible en cuanto a ubicacion exacta.
- Hoy es aceptable, pero podria moverse a `Application` si se decide que su rol es mas de coordinacion que de dominio.

### Tests

Semaforo: Amarillo

Estado actual:

- Hay pruebas utiles para piezas del flujo principal y para smoke tests CQRS.
- Se agregaron validaciones para asegurar que command bus y query bus de `Pedidos` resuelven desde `Application`.
- Hay cobertura puntual para:
  - `GuardarBorradorUseCase`
  - `ActualizarBorradorUseCase`
  - `PedidoLifecycleService`
  - resolucion CQRS

Deuda restante:

- Falta una bateria mas clara de pruebas de arquitectura y de flujo end-to-end para el camino principal.
- El smoke test CQRS es util, pero no reemplaza pruebas funcionales del flujo completo.

## Cambios relevantes consolidados en esta fase

### Flujo principal

- Se saco acceso Eloquent directo de `ActualizarBorradorUseCase` hacia repositorio.
- Se saco la busqueda directa de borrador desde `CrearPedidoCompleteUseCase`.
- `PedidoNormalizadorDTO` se movio fuera de `Domain`.

### Repositorios

- `PedidoProduccionRepository` paso a contrato de dominio.
- Su implementacion Eloquent quedo en infraestructura.
- Se movieron fuera de `Domain` los repositorios concretos legacy de:
  - logo
  - colores por talla
  - cotizacion

### CQRS

- `CommandHandlers` de `Pedidos` movidos a `Application`
- `QueryHandlers` de `Pedidos` movidos a `Application`
- `CQRSServiceProvider` reordenado

### Despacho

- Servicios operativos reubicados a `Application`
- Listener y wiring alineados con infraestructura

### Arquitectura paralela

- `app/Modules/Pedidos` fue desactivado y podado como provider activo
- Se movio el controller residual vivo hacia `Infrastructure`

### Eventos y listeners

- Los listeners de `Pedidos` salieron de `Domain`
- `EventServiceProvider` fue corregido para usar namespaces reales

### Validacion y soporte

- `PedidoJSONValidator` movido a `Application`
- `PedidoValidator` movido a `Application`
- `GestionaTallasRelacional` movido a infraestructura
- `CreacionPrendaStrategy` movida fuera de `Domain`

## Lo que sigue pendiente para dejar Pedidos mas cerca de un DDD completo

### Pendiente 1

Refinar el contrato `PedidoProduccionRepository`

Problema:

- El contrato de dominio todavia expone modelos Eloquent.

Objetivo:

- Reducir el acoplamiento del dominio con Laravel.

Opciones:

- Pragmatica: dejarlo como esta y documentar que es un contrato de dominio pragmatico
- Mas pura: introducir DTOs o entities de lectura/escritura para el contrato

Recomendacion:

- Hacer esto en una fase separada, porque tiene mayor impacto transversal.

### Pendiente 2

Unificar convenciones dentro de `Application`

Problema:

- Conviven `UseCases`, `Handlers`, `Services` y `Bridge` con estilos distintos.

Objetivo:

- Dejar una taxonomia estable para nuevos desarrollos.

Recomendacion:

- Definir regla:
  - `UseCase` para flujos de negocio invocados por controllers
  - `Handler` solo para CQRS
  - `Service` para colaborador reusable
  - `Bridge` como transicional y con plan de eliminacion

### Pendiente 3

Agregar pruebas de arquitectura y de flujo principal

Minimo recomendado:

1. Test de arquitectura que falle si `app/Domain/Pedidos` referencia `Application`, `Infrastructure`, facades o controllers.
2. Test funcional del flujo:
   - guardar borrador
   - actualizar borrador
   - crear pedido completo
3. Test de wiring de providers criticos:
   - `PedidosServiceProvider`
   - `CQRSServiceProvider`
   - `EventServiceProvider`

### Pendiente 4

Revisar bordes legacy alrededor de cotizacion y prenda editor

Problema:

- En estos bordes aun conviven lineas arquitectonicas diferentes.

Objetivo:

- Evitar que vuelvan a contaminar `Pedidos`.

Recomendacion:

- Considerarlos como borde legacy controlado
- No reabrir `Domain/Pedidos` por esa via

## Definicion practica de cierre de fase

Esta fase puede considerarse cerrada si se cumplen estos puntos:

1. No entra nuevo codigo de infraestructura o Laravel a `app/Domain/Pedidos`.
2. Se documenta que `PedidoProduccionRepository` queda como deuda fina o se refina en una fase posterior.
3. Se agregan al menos pruebas de arquitectura y del flujo principal.
4. El equipo adopta una regla clara para nuevos cambios en `Pedidos`:
   - dominio puro en `Domain`
   - orquestacion en `Application`
   - persistencia, HTTP, listeners y filesystem en `Infrastructure`

## Conclusión

`Pedidos` ya no esta dominado por arquitectura legacy dispersa. La capa dominante hoy si es una estructura DDD por capas, con una deuda restante mucho mas fina y controlable.

La principal conclusion tecnica es esta:

- El problema grande de mezcla de capas ya fue atacado
- Lo que queda ya no es un refactor de rescate
- Lo que sigue es consolidacion, normalizacion y blindaje para que no se recontamine

En otras palabras:

`Pedidos` todavia no es un DDD perfecto, pero ya esta en un punto donde si se puede gobernar como modulo DDD de forma sostenida.
