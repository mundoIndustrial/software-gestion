# 🔍 ANÁLISIS: Duplicación de Dominios (Pedidos vs PedidoProduccion)

## 📊 ESTRUCTURA ACTUAL (PROBLEMA)

### Carpeta 1: `/app/Domain/Pedidos` (BÁSICA)
```
Pedidos/
├── Agregado/
│   └── PedidoAggregate.php
├── Entities/
│   └── PrendaPedido.php
├── Events/
│   └── PedidoActualizado.php
│   └── PedidoCreado.php
│   └── PedidoEliminado.php
├── Exceptions/
│   └── EstadoPedidoInvalido.php
│   └── PedidoNoEncontrado.php
├── Repositories/
│   └── PedidoRepository.php
├── Services/
│   └── PrendaFotoService.php
└── ValueObjects/
    └── Estado.php
    └── NumeroPedido.php
```

**Características:**
- ❌ Sin CQRS (sin Commands, Queries, Handlers)
- ❌ Estructura simplificada
- ✅ Tiene Domain Services (PrendaFotoService)

---

### Carpeta 2: `/app/Domain/PedidoProduccion` (COMPLETA)
```
PedidoProduccion/
├── Aggregates/ (3 archivos)
│   ├── LogoPedidoAggregate.php
│   ├── PedidoProduccionAggregate.php
│   └── PrendaPedidoAggregate.php
├── CommandHandlers/ (5 handlers)
│   ├── ActualizarPedidoHandler.php
│   ├── AgregarPrendaAlPedidoHandler.php
│   ├── CambiarEstadoPedidoHandler.php
│   ├── CrearPedidoHandler.php
│   └── EliminarPedidoHandler.php
├── Commands/ (5 commands)
│   ├── ActualizarPedidoCommand.php
│   ├── AgregarPrendaAlPedidoCommand.php
│   ├── CambiarEstadoPedidoCommand.php
│   ├── CrearPedidoCommand.php
│   └── EliminarPedidoCommand.php
├── DTOs/
├── Entities/
│   └── PrendaEntity.php
├── Events/ (4 events)
│   ├── LogoPedidoCreado.php
│   ├── PedidoProduccionCompletado.php
│   ├── PedidoProduccionCreado.php
│   └── PrendaPedidoAgregada.php
├── Facades/
├── Listeners/ (4 listeners)
│   ├── ActualizarCachePedidos.php
│   ├── ActualizarEstadisticasPrendas.php
│   ├── NotificarClientePedidoCreado.php
│   └── RegistrarAuditoriaPedido.php
├── Queries/ (5 queries)
│   ├── BuscarPedidoPorNumeroQuery.php
│   ├── FiltrarPedidosPorEstadoQuery.php
│   ├── ListarPedidosQuery.php
│   ├── ObtenerPedidoQuery.php
│   └── ObtenerPrendasPorPedidoQuery.php
├── QueryHandlers/ (5 handlers) ⚠️ AQUÍ ESTÁN LOS HANDLERS QUE RECIÉN ARREGLAMOS
│   ├── BuscarPedidoPorNumeroHandler.php
│   ├── FiltrarPedidosPorEstadoHandler.php
│   ├── ListarPedidosHandler.php
│   ├── ObtenerPedidoHandler.php
│   └── ObtenerPrendasPorPedidoHandler.php
├── Repositories/ (3 repositories)
│   ├── CotizacionRepository.php
│   ├── LogoPedidoRepository.php
│   └── PedidoProduccionRepository.php
├── Services/ (MUCHÍSIMOS - ~30+ servicios)
│   ├── CaracteristicasPrendaService.php
│   ├── ClienteService.php
│   ├── ColorTelaService.php
│   ├── CreacionPedidoService.php
│   ├── ... (muchos más)
│   └── PrendaVarianteService.php
├── Strategies/
├── Traits/
├── Validators/
└── ValueObjects/
```

**Características:**
- ✅ CQRS completo (Commands, CommandHandlers, Queries, QueryHandlers)
- ✅ Event Sourcing (Events, Listeners)
- ✅ Patrones avanzados (Aggregates, Strategies, Validators)
- ✅ Repositorio separado

---

## 🚨 PROBLEMAS IDENTIFICADOS

### 1. **Duplicación de Concepto: "Pedido"**
```
Pedidos/Agregado/PedidoAggregate.php          ← PedidoAggregate
PedidoProduccion/Aggregates/PedidoProduccionAggregate.php ← PedidoProduccionAggregate
```

**¿Cuál es el "verdadero" Pedido?**
- ¿Son dos agregates diferentes?
- ¿Son el mismo concepto con nombres distintos?
- ¿Debería haber una fusión?

### 2. **Duplicación de "Prenda"**
```
Pedidos/Entities/PrendaPedido.php            ← Entity simplificada
PedidoProduccion/Entities/PrendaEntity.php   ← Entity diferente
PedidoProduccion/Aggregates/PrendaPedidoAggregate.php ← Aggregate
```

**Problemas:**
- Dos entidades de Prenda con lógica potencialmente duplicada
- Confusión sobre cuál usar dónde

### 3. **Estructura Inconsistente**
```
Pedidos/           (Simplificada, pocas responsabilidades)
PedidoProduccion/  (Completa, CQRS, Event Sourcing)
```

### 4. **Los Controllers usan PedidoProduccion**
Los controllers seguramente importan de `PedidoProduccion/*` porque es donde está la lógica completa.

**Resultado:** `Pedidos/` está siendo ignorado/subutilizado.

---

## 🎯 SOLUCIÓN RECOMENDADA

### Opción A: Migrar TODO a Pedidos (RECOMENDADO)

**Idea:** Consolidar TODO en `/app/Domain/Pedidos` con la estructura completa de `PedidoProduccion`

```
Pedidos/
├── Aggregates/
│   ├── PedidoAggregate.php              (Fusionar PedidoAggregate + PedidoProduccionAggregate)
│   ├── LogoPedidoAggregate.php          (De PedidoProduccion)
│   └── PrendaPedidoAggregate.php        (De PedidoProduccion)
├── CommandHandlers/
│   ├── ActualizarPedidoHandler.php
│   ├── AgregarPrendaAlPedidoHandler.php
│   ├── CambiarEstadoPedidoHandler.php
│   ├── CrearPedidoHandler.php
│   └── EliminarPedidoHandler.php
├── Commands/
│   ├── ActualizarPedidoCommand.php
│   ├── ... (etc)
├── Entities/
│   ├── PrendaPedido.php                 (Entity)
│   └── PrendaEntity.php                 (Consolidar o eliminar duplicado)
├── Events/
│   ├── PedidoActualizado.php
│   ├── PedidoCreado.php
│   ├── LogoPedidoCreado.php
│   └── ... (etc)
├── Exceptions/
│   ├── EstadoPedidoInvalido.php
│   └── PedidoNoEncontrado.php
├── QueryHandlers/                       ⚠️ AQUÍ VAN NUESTROS FIXES
│   ├── ObtenerPedidoHandler.php         (✅ Ya migrado y arreglado)
│   ├── ObtenerPrendasPorPedidoHandler.php (✅ Ya migrado y arreglado)
│   ├── BuscarPedidoPorNumeroHandler.php (✅ Ya migrado y arreglado)
│   └── ... (etc)
├── Queries/
│   ├── ObtenerPedidoQuery.php
│   └── ... (etc)
├── Repositories/
│   ├── PedidoRepository.php
│   ├── LogoPedidoRepository.php
│   └── CotizacionRepository.php
├── Services/
│   ├── PrendaFotoService.php            (✅ Ya está en Pedidos)
│   ├── CreacionPedidoService.php
│   ├── PrendaVarianteService.php
│   └── ... (todos de PedidoProduccion)
├── ValueObjects/
│   ├── Estado.php
│   ├── NumeroPedido.php
│   └── ... (etc)
├── Listeners/
│   ├── ActualizarCachePedidos.php
│   └── ... (etc)
├── Validators/
├── Traits/
└── Strategies/
```

**Pasos:**
1. Mover todos los archivos de `PedidoProduccion/*` a `Pedidos/*`
2. Actualizar namespaces en los archivos movidos
3. Eliminar la carpeta `PedidoProduccion/`
4. Buscar y reemplazar imports de `PedidoProduccion` a `Pedidos`
5. Actualizar `config/` si hay configuración hardcoded

---

### Opción B: Mantener Separados (NO RECOMENDADO)

Si realmente necesitas dos dominios separados:
- Documentar claramente la diferencia
- NO duplicar lógica entre ellos
- Definir límites claros (dónde es "Pedidos" vs dónde es "PedidoProduccion")

---

## 📋 ANÁLISIS DE IMPACTO

### Archivos que NO necesitan cambios (modelos en `/app/Models/`):
- `PedidoProduccion.php` (modelo Eloquent)
- `PrendaPedido.php` (modelo Eloquent)
- etc.

### Archivos que REQUIEREN cambios (imports):
- Todos los `Controller` que usen `use App\Domain\PedidoProduccion\...`
- Todos los `Service` que usen `use App\Domain\PedidoProduccion\...`
- Todos los listeners
- Tests

### Estimación de cambios:
- ~50+ archivos con imports
- ~100+ líneas de código para actualizar namespaces

---

## 🚀 MI RECOMENDACIÓN

**Opción A: Consolidar TODO en Pedidos** porque:

1. ✅ **Coherencia:** Un dominio = una carpeta
2. ✅ **Claridad:** Nadie confunde qué usar dónde
3. ✅ **Mantenibilidad:** Una sola fuente de verdad
4. ✅ **Performance:** No cargar configuración de dos dominios
5. ✅ **Escalabilidad:** Fácil agregar nuevas features sin duplicar

---

## ⚠️ RIESGO: NOMBRES CONFLICTIVOS

⚠️ **Problema:** Ambas carpetas tienen conceptos similares pero con nombres diferentes:

| Concepto | Pedidos/ | PedidoProduccion/ |
|----------|---------|-------------------|
| Aggregate del Pedido | PedidoAggregate | PedidoProduccionAggregate |
| Entity de Prenda | PrendaPedido | PrendaEntity |
| Estado | Estado (VO) | ??? |

**Decisión:** Al migrar, usar nombres CONSISTENTES:
- ✅ `PedidoAggregate.php` (no PedidoProduccionAggregate)
- ✅ `PrendaEntity.php` (no PrendaPedido para Entity)
- ✅ `Estado.php` (VO)

---

## 📝 PRÓXIMOS PASOS

1. ✅ Analizar si hay lógica diferente en ambos dominios
2. ⏳ Decidir si es realmente una sola cosa o dos cosas
3. ⏳ Hacer la migración
4. ⏳ Actualizar todos los imports
5. ⏳ Ejecutar tests para validar
