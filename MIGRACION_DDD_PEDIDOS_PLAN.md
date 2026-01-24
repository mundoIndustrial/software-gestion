# 📄 PLAN DE MIGRACIÓN DDD – MÓDULO PEDIDOS

**Estado:** En progreso  
**Fecha inicio:** 22/01/2026  
**Enfoque:** Por fases, sin romper producción

---

## 🧩 FASES DEL PROYECTO

###  Fase 0 – Preparación (SIN IMPACTO EN PRODUCCIÓN)

**Status:**  COMPLETADA  
**Objetivo:** Crear estructura de carpetas y clases base sin usarlas aún

**Tareas:**
- [x] Crear carpetas Domain/Pedidos/, Application/Pedidos/, Infrastructure/Pedidos/
- [x] Crear clases base vacías (listadas abajo)
- [x] Crear tests básicos (3 mínimo)
- [x] Verificar que todo compila  **3/3 tests PASANDO**

**Estructura a crear:**
```
app/Domain/Pedidos/
├── Agregado/
│   └── PedidoAggregate.php
├── Entities/
│   └── PrendaPedido.php
├── ValueObjects/
│   ├── NumeroPedido.php
│   └── Estado.php
├── Repositories/
│   └── PedidoRepository.php
├── Services/
│   └── CalculadorPedidoService.php
├── Events/
│   ├── PedidoCreado.php
│   ├── PedidoActualizado.php
│   └── PedidoEliminado.php
└── Exceptions/
    ├── PedidoNoEncontrado.php
    └── EstadoPedidoInvalido.php

app/Application/Pedidos/
├── UseCases/
│   ├── CrearPedidoUseCase.php
│   ├── ConfirmarPedidoUseCase.php
│   └── ObtenerPedidoUseCase.php
├── DTOs/
│   ├── CrearPedidoDTO.php
│   ├── ActualizarPedidoDTO.php
│   └── PedidoResponseDTO.php
└── Listeners/
    └── PedidoCreadoListener.php

app/Infrastructure/Pedidos/
├── Persistence/
│   └── Eloquent/
│       └── PedidoRepositoryImpl.php
└── Providers/
    └── PedidoServiceProvider.php
```

---

### 🟡 Fase 1 – Dominio (SIN IMPACTO EN PRODUCCIÓN)

**Status:** 🔵 Pendiente  
**Objetivo:** Construir el dominio correctamente con lógica de negocio pura

**Tareas:**
- [ ] Implementar Value Objects (NumeroPedido, Estado)
- [ ] Implementar Entities (PrendaPedido)
- [ ] Implementar Aggregate Root (PedidoAggregate)
- [ ] Escribir tests para el dominio (sin usar BD)

**Dependencias:** Fase 0 completada

---

### 🟠 Fase 2 – Persistencia DDD (SIN IMPACTO EN PRODUCCIÓN)

**Status:** 🔵 Pendiente  
**Objetivo:** Crear repositorio sin reemplazar código viejo

**Tareas:**
- [ ] Crear PedidoRepository (interface)
- [ ] Crear PedidoRepositoryImpl (Eloquent)
- [ ] Crear Mapper (Hydrator)
- [ ] Tests de persistencia

**Nota:** El código antiguo sigue funcionando. Este existe pero NO se usa en producción aún.

**Dependencias:** Fase 1 completada

---

### 🔵 Fase 3 – MIGRAR ENDPOINT: Crear Pedido

**Status:** 🔵 Pendiente  
**Objetivo:** Primer endpoint en producción usando DDD

**Tareas:**
- [ ] Crear DTOs (CrearPedidoDTO, PedidoResponseDTO)
- [ ] Crear Use Case (CrearPedidoUseCase)
- [ ] Refactorizar PedidoController::store()
- [ ] Tests de integración
- [ ] Desplegar a producción

**Endpoints afectados:**
- `POST /api/pedidos` ← MIGRADO

**Endpoints sin cambios:**
- GET /api/pedidos
- GET /api/pedidos/{id}
- PATCH /api/pedidos/{id}/confirmar (todavía viejo)

**Dependencias:** Fase 2 completada

---

### 🟣 Fase 4 – MIGRAR ENDPOINT: Confirmar Pedido

**Status:** 🔵 Pendiente  
**Objetivo:** Segundo endpoint migrado

**Tareas:**
- [ ] Crear Use Case (ConfirmarPedidoUseCase)
- [ ] Refactorizar PedidoController::confirmar()
- [ ] Tests de integración
- [ ] Desplegar a producción

**Endpoints afectados:**
- `PATCH /api/pedidos/{id}/confirmar` ← MIGRADO

**Dependencias:** Fase 3 completada

---

### 🟤 Fase 5 – MIGRAR CONSULTAS (Query Side)

**Status:** 🔵 Pendiente  
**Objetivo:** Separar lectura de escritura (CQRS básico)

**Tareas:**
- [ ] Crear QueryHandlers o servicios de consulta
- [ ] Implementar ObtenerPedidoQueryHandler
- [ ] Implementar ListarPedidosQueryHandler
- [ ] Tests para queries

**Endpoints afectados:**
- `GET /api/pedidos` ← QueryHandler
- `GET /api/pedidos/{id}` ← QueryHandler

**Nota:** Las queries pueden usar Eloquent directo (solo lectura)

**Dependencias:** Fase 4 completada

---

### ⚫ Fase 6 – LIMPIEZA FINAL

**Status:** 🔵 Pendiente  
**Objetivo:** Eliminar código antiguo y dejar solo DDD

**Tareas:**
- [ ] Eliminar lógica antigua de Controllers
- [ ] Eliminar modelos viejos si no se usan
- [ ] Limpiar rutas duplicadas
- [ ] Ejecutar test suite completo
- [ ] Verificar no hay regresiones

**Dependencias:** Fase 5 completada

---

## 🧠 PRINCIPIOS A CUMPLIR

 El dominio NO debe depender de Laravel  
 Los casos de uso deben orquestar el flujo  
 El agregado debe contener reglas del negocio  
 Los repositorios deben ser interfaces  
 La persistencia debe estar en Infrastructure  
 Eventos de dominio para desacoplar acciones  
 Separar lectura y escritura (CQRS)

---

## 📌 REGLAS DE MIGRACIÓN

1. **No se cambia todo de golpe** - Fase a fase
2. Se migran endpoints uno por uno
3. Cada fase debe estar testeada antes de avanzar
4. Si algo falla, se revierte sin afectar producción
5. Documentación actualizada en cada fase

---

## 📊 INDICADORES DE ÉXITO

| Fase | Indicador | Status |
|------|-----------|--------|
| 0 | Estructura compilada sin errores |  |
| 1 | Tests de dominio pasen | 🟢 3/3 PASANDO |
| 2 | Persistencia funcione en tests | 🔵 |
| 3 | POST /api/pedidos migrado  | 🔵 |
| 4 | PATCH /api/pedidos/{id}/confirmar migrado  | 🔵 |
| 5 | GET endpoints usen QueryHandlers | 🔵 |
| 6 | Código antiguo eliminado | 🔵 |
| FINAL | Cero regresiones en producción | 🔵 |

---

## 📝 TIMELINE ESTIMADO

- **Fase 0:** 1-2 horas
- **Fase 1:** 3-4 horas
- **Fase 2:** 2-3 horas
- **Fase 3:** 2-3 horas (incluye testing)
- **Fase 4:** 1-2 horas
- **Fase 5:** 2-3 horas
- **Fase 6:** 1-2 horas

**TOTAL:** 14-20 horas (2-3 días de trabajo real)

---

## 🔄 FLUJO DE TRABAJO

```
Fase 0: Setup
    ↓
Fase 1: Dominio puro
    ↓
Fase 2: Persistencia (sin usar)
    ↓
Fase 3: Crear Pedido en DDD
    ↓
Fase 4: Confirmar Pedido en DDD
    ↓
Fase 5: Consultas con QueryHandlers
    ↓
Fase 6: Limpiar y eliminar código viejo
    ↓
 MIGRACIÓN COMPLETADA
```

---

**Próximo paso:** Comenzar Fase 0 - Crear estructura de carpetas y clases base
