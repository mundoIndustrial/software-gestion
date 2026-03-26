# Fases para refinar PedidoProduccionRepository hacia DDD

Fecha: 2026-03-26

## Objetivo

Reducir el acoplamiento del contrato `App\Domain\Pedidos\Repositories\PedidoProduccionRepository`
con modelos Eloquent sin romper los flujos actuales de `Pedidos`.

Hoy el contrato expone:

- `PedidoProduccion`
- `PedidoEpp`
- `PrendaPedido`

Eso mantiene una deuda fina de infraestructura dentro de un contrato de dominio.

## Principio de trabajo

No cambiar todo el contrato de una vez.

La migracion debe hacerse por capas:

1. aislar consumidores
2. introducir lecturas mas estables
3. migrar casos de uso prioritarios
4. dejar el contrato legacy como transicional
5. cerrar el contrato final

## Fase 1

### Nombre

Inventario y segmentacion del contrato actual

### Objetivo

Separar conceptualmente los metodos del repositorio por tipo de responsabilidad.

### Grupos actuales

#### Grupo A

Metodos relativamente seguros porque ya devuelven escalares, arrays o paginacion:

- `obtenerPedidosAsesor`
- `perteneceAlAsesor`
- `actualizarCantidadTotal`
- `obtenerDatosFactura`
- `obtenerDatosRecibos`

#### Grupo B

Metodos que exponen Eloquent de forma directa y son la deuda principal:

- `findByNumeroPedido`
- `cargarPrendas`
- `obtenerPorId`
- `obtenerUltimoPedido`
- `obtenerPorIdYAsesor`
- `actualizarDatosBasicos`
- `obtenerEppConImagenes`
- `obtenerPrendaDelPedido`

### Resultado esperado

- Tener claro que no todo el contrato requiere el mismo tratamiento.
- Empezar por el Grupo B en cortes pequeños.

## Fase 2

### Nombre

Introducir modelos de lectura y actualizacion estables

### Objetivo

Crear contratos de datos mas estables para no exponer directamente modelos Eloquent.

### Recomendacion

Crear DTOs o read models en `Application` o `Domain` segun su uso:

- `PedidoProduccionView`
- `PedidoPrendaView`
- `PedidoEppView`
- `PedidoBasicoUpdateData`

### Alcance inicial recomendado

No migrar todos los metodos al mismo tiempo.

Empezar por:

- `obtenerPrendaDelPedido`
- `obtenerEppConImagenes`
- `obtenerPorIdYAsesor`

Porque son mas acotados y tienen menos impacto que `obtenerPorId`.

## Fase 3

### Nombre

Crear un puerto nuevo sin romper el actual

### Objetivo

Introducir un contrato nuevo, mas limpio, para el flujo principal.

### Recomendacion

Crear un segundo contrato, por ejemplo:

- `PedidoProduccionReadRepository`
- o `PedidoProduccionGateway`

Ese contrato nuevo debe devolver:

- arrays tipados
- DTOs
- read models

No modelos Eloquent.

### Regla

El contrato viejo no se elimina todavia.

Se usa como compatibilidad transicional mientras migramos consumidores.

## Fase 4

### Nombre

Migrar consumidores prioritarios

### Objetivo

Mover primero los casos de uso y servicios del flujo oficial de `Pedidos`.

### Prioridad recomendada

1. `ActualizarBorradorUseCase`
2. `CrearPedidoCompleteUseCase`
3. servicios de asesores ligados a factura y recibos
4. servicios de bodega y supervisor
5. controllers legacy

### Razon

Primero se limpia el camino principal.
Los bordes legacy pueden quedarse un tiempo con el contrato viejo.

## Fase 5

### Nombre

Degradar el contrato actual a legacy interno

### Objetivo

Cuando los consumidores principales ya no dependan de modelos Eloquent del contrato,
renombrar o encapsular el contrato viejo como compatibilidad legacy.

### Opciones

- dejarlo en infraestructura como `LegacyPedidoProduccionRepository`
- o mantenerlo temporalmente solo para adapters legacy

## Fase 6

### Nombre

Cierre final del contrato de dominio

### Objetivo

Dejar en `Domain` solo el contrato nuevo, limpio y estable.

### Resultado esperado

- sin `App\Models` en el contrato del dominio
- sin metodos que reciban o retornen Eloquent directamente
- consumidores principales trabajando contra DTOs o read models

## Recomendacion practica

La mejor siguiente ejecucion no es intentar cerrar todo en un solo turno.

El siguiente corte tecnico recomendado es:

1. crear el nuevo puerto de lectura limpio
2. migrar un metodo pequeno
3. adaptar uno o dos consumidores del flujo principal
4. dejar test de compatibilidad

## Primer corte recomendado

Si seguimos ahora, el mejor primer corte es:

- introducir un modelo de lectura para `obtenerPrendaDelPedido`
- adaptar `ActualizarBorradorUseCase`
- mantener el contrato viejo funcionando en paralelo

Ese corte da valor real sin abrir una explosion de cambios.
