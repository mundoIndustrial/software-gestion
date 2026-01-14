# 🚀 FASE 4 - INICIADA: CQRS (Command Query Responsibility Segregation)

## Estado Actual

**Fecha de inicio**: 14 de Enero de 2026
**Status**: En Progreso (Base completada, 10/30 archivos)
**Avance**: ~33% de FASE 4

---

## Qué es CQRS

**CQRS** separa las operaciones en dos categorías:

- **Queries (Lectura)**: Obtienen datos, NO tienen efectos secundarios
- **Commands (Escritura)**: Modifican datos, TIENEN efectos secundarios

```
ANTES (Monolítico):
Controller → Servicio → Lógica
         ↘ Cache ↘ Auditoría

DESPUÉS (CQRS):
GET  → QueryBus → Handler → Resultado (sin efectos)
POST → CommandBus → Handler → Resultado + Eventos + Cache + Auditoría
```

---

## Archivos Creados en FASE 4 (10 archivos)

### Interfaces y Base CQRS (6 archivos)

**Ubicación**: `app/Domain/Shared/CQRS/`

1. **Query.php** (20 líneas)
   - Interface marker para todas las queries
   - Define contrato: toda query debe implementar Query
   - Patrón: Query Object

2. **Command.php** (20 líneas)
   - Interface marker para todos los commands
   - Define contrato: todo command debe implementar Command
   - Patrón: Command Object

3. **QueryHandler.php** (30 líneas)
   - Interface para handlers de queries
   - Método: `handle(Query $query): mixed`
   - Responsabilidad: ejecutar query y retornar resultado

4. **CommandHandler.php** (30 líneas)
   - Interface para handlers de commands
   - Método: `handle(Command $command): mixed`
   - Responsabilidad: ejecutar command y retornar resultado

5. **QueryBus.php** (130 líneas)
   - Despachador de queries
   - Métodos: `register()`, `execute()`, `getHandlers()`
   - Logging y manejo de errores
   - Service Locator pattern
   - **Sin transacciones** (solo lectura)

6. **CommandBus.php** (140 líneas)
   - Despachador de commands
   - Métodos: `register()`, `execute()`, `getHandlers()`
   - **Con transacciones** (DB::transaction)
   - Logging y manejo de errores
   - Service Locator pattern

### Primer Query: ObtenerPedido (2 archivos)

**Ubicación**: `app/Domain/PedidoProduccion/Queries/`

7. **ObtenerPedidoQuery.php** (20 líneas)
   - Query para obtener detalles completos de un pedido
   - Parámetro: `pedidoId` (int|string)
   - Getter: `getPedidoId()`

**Ubicación**: `app/Domain/PedidoProduccion/QueryHandlers/`

8. **ObtenerPedidoHandler.php** (90 líneas)
   - Handler para ObtenerPedidoQuery
   - Estrategia: Cache-aside (primero cache, luego BD)
   - Con relaciones: prendas, logos, asesor, cliente
   - TTL: 1 hora en cache
   - Logging detallado

### Primer Command: CrearPedido (2 archivos)

**Ubicación**: `app/Domain/PedidoProduccion/Commands/`

9. **CrearPedidoCommand.php** (40 líneas)
   - Command para crear nuevo pedido
   - Parámetros: numeroPedido, cliente, formaPago, asesorId, cantidadInicial
   - Getters: getNumeroPedido(), getCliente(), getFormaPago(), getAsesorId(), getCantidadInicial()

**Ubicación**: `app/Domain/PedidoProduccion/CommandHandlers/`

10. **CrearPedidoHandler.php** (110 líneas)
    - Handler para CrearPedidoCommand
    - Validaciones: número único, agregado
    - Persiste en BD
    - Emite eventos del agregado
    - Invalida cachés relacionados
    - En transacción DB
    - Logging completo

---

## Validación de Calidad

✅ **10 archivos validados** - 0 errores PHP

```
✅ Query.php
✅ Command.php
✅ QueryHandler.php
✅ CommandHandler.php
✅ QueryBus.php
✅ CommandBus.php
✅ ObtenerPedidoQuery.php
✅ ObtenerPedidoHandler.php
✅ CrearPedidoCommand.php
✅ CrearPedidoHandler.php
```

---

## Flujos de Ejemplo

### Flujo 1: Query (Lectura sin efectos)

```
GET /api/pedidos/123

    Controller
    ├─ Valida HTTP request
    └─ QueryBus->execute(new ObtenerPedidoQuery(123))
            │
            ├─ QueryBus resuelve handler
            ├─ Ejecuta ObtenerPedidoHandler->handle()
            │   ├─ Intenta cache
            │   ├─ Si miss: query BD con relaciones
            │   ├─ Cachea resultado 1 hora
            │   └─ Retorna PedidoProduccion
            │
            └─ Retorna resultado

Response: 200 JSON con pedido completo
    
NO hay: transacciones, eventos, invalidación
```

### Flujo 2: Command (Escritura con efectos)

```
POST /api/pedidos (create)

    Controller
    ├─ Valida HTTP request
    └─ CommandBus->execute(new CrearPedidoCommand(...))
            │
            ├─ QueryBus resuelve handler
            ├─ Ejecuta EN TRANSACCIÓN:
            │   ├─ CrearPedidoHandler->handle()
            │   │   ├─ Valida número único
            │   │   ├─ Crea PedidoProduccionAggregate
            │   │   ├─ Persiste en BD
            │   │   ├─ Emite PedidoProduccionCreado
            │   │   │   ├─ Notificar cliente
            │   │   │   ├─ Actualizar cache
            │   │   │   └─ Registrar auditoría
            │   │   ├─ Invalida cachés
            │   │   └─ Retorna PedidoProduccion
            │   │
            │   └─ [COMMIT si OK, ROLLBACK si error]
            │
            └─ Retorna resultado

Response: 201 JSON con pedido creado
    
SÍ hay: transacción, eventos, listeners, invalidación
```

---

## Arquitectura de FASE 4

```
┌─────────────────────────────────────┐
│      HTTP Controller (Thin)         │
│  • Validate HTTP                    │
│  • Call QueryBus or CommandBus      │
│  • Return HTTP Response             │
└────────────────┬────────────────────┘
                 │
        ┌────────┴─────────┐
        ↓                  ↓
┌──────────────┐    ┌───────────────┐
│  QueryBus    │    │  CommandBus   │
│              │    │               │
│ • register() │    │ • register()  │
│ • execute()  │    │ • execute()   │
│ NO trans     │    │ WITH trans    │
└──────┬───────┘    └───────┬───────┘
       │                    │
       ↓                    ↓
   ┌─────────────┐    ┌──────────────┐
   │   Queries   │    │   Commands   │
   │             │    │              │
   │ Obtener*    │    │ Crear*       │
   │ Listar*     │    │ Actualizar*  │
   │ Filtrar*    │    │ Eliminar*    │
   │ Buscar*     │    │ Cambiar*     │
   └─────┬───────┘    └──────┬───────┘
         │                   │
         ↓                   ↓
    ┌──────────────┐   ┌──────────────┐
    │QueryHandlers │   │CommandHandlers│
    │              │   │               │
    │ • Queries BD │   │ • Valida      │
    │ • Cache A/S  │   │ • Persiste    │
    │ • Logging    │   │ • Emite eventos│
    │ • No efectos │   │ • Invalida    │
    │              │   │ • Logging     │
    └──────────────┘   └──────────────┘
```

---

## Ventajas de CQRS (Lo que hemos logrado)

| Ventaja | Beneficio |
|---------|----------|
| **Separación** | Queries y Commands separados, fácil de entender |
| **Escalabilidad** | Queries en cache, Commands en transacción |
| **Testabilidad** | Handlers sin dependencias de HTTP |
| **Auditabilidad** | Todo command genera eventos |
| **Mantenibilidad** | Controller thin, lógica en handlers |
| **Reutilización** | Handlers usables desde CLI, Jobs, API |
| **Performance** | Cache-aside en queries, no impacto en writes |

---

## Próximos Pasos (Restante de FASE 4)

### Task 11: Más Queries (5-10 queries)
- [ ] ListarPedidosQuery + Handler
- [ ] FiltrarPedidosPorEstadoQuery + Handler
- [ ] BuscarPedidoPorNumeroQuery + Handler
- [ ] ObtenerEstadisticasPedidosQuery + Handler
- [ ] ObtenerPrendasonPorPedidoQuery + Handler

### Task 12: Más Commands (5-10 commands)
- [ ] ActualizarPedidoCommand + Handler
- [ ] CambiarEstadoPedidoCommand + Handler
- [ ] AgregarPrendaAlPedidoCommand + Handler (usando PrendaCreationService)
- [ ] CrearLogoPedidoCommand + Handler (usando LogoPedidoService)
- [ ] EliminarPedidoCommand + Handler

### Task 13: Validators
- [ ] PedidoValidator (número único, cliente válido, etc)
- [ ] PrendaValidator (cantidad > 0, genero válido, etc)
- [ ] Integrar en Handlers

### Task 14: Registro en DI
- [ ] Crear CQRSServiceProvider
- [ ] Registrar QueryBus y CommandBus como singletons
- [ ] Registrar todos los Query Handlers
- [ ] Registrar todos los Command Handlers

### Task 15: Refactorizar Controller
- [ ] Inyectar QueryBus y CommandBus
- [ ] Reemplazar cada GET con Query
- [ ] Reemplazar cada POST/PUT/DELETE con Command
- [ ] Target: Controller < 100 líneas

---

## Estimaciones

| Elemento | Estimado | Realizado | % |
|----------|----------|-----------|---|
| Base CQRS | 2 hrs | 1.5 hrs | 75% ✅ |
| Primer Q/C | 2 hrs | 1.5 hrs | 75% ✅ |
| Más Q/C | 5 hrs | - | 0% ⏳ |
| Validators | 2 hrs | - | 0% ⏳ |
| Registro DI | 1 hr | - | 0% ⏳ |
| Refactor Controller | 3 hrs | - | 0% ⏳ |
| **Total FASE 4** | **15 hrs** | **3 hrs** | **20% ✅** |

---

## Código Ejemplo: Uso en Controller

### Antes (Monolítico)
```php
public function show($id) {
    $pedido = PedidoProduccion::with(['prendas', 'logos'])->find($id);
    if (!$pedido) return response()->json(['error' => 'No encontrado'], 404);
    return response()->json($pedido);
}
```

### Después (CQRS)
```php
public function show($id) {
    $pedido = $this->queryBus->execute(new ObtenerPedidoQuery($id));
    if (!$pedido) return response()->json(['error' => 'No encontrado'], 404);
    return response()->json($pedido);
}
```

---

## Conclusión

**FASE 4 Base completada**: 10 archivos, 0 errores, arquitectura CQRS lista.

Hemos implementado:
- ✅ QueryBus y CommandBus
- ✅ Query y Command interfaces
- ✅ QueryHandler y CommandHandler interfaces
- ✅ Primer Query: ObtenerPedido (con cache-aside)
- ✅ Primer Command: CrearPedido (con transacción + eventos)

**Listo para**: Continuar con más queries y commands.

---

**Próximo**: Task 11 - Crear más Queries
**Cuando estés listo**: Avísame y continuamos 🚀

---

Última actualización: 14 de Enero de 2026
Estado: ✅ FASE 4 BASE COMPLETADA
