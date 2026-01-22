¡Listo! Te dejo un documento **claro y profesional** para que Copilot (o cualquier persona) entienda exactamente qué vamos a hacer en esta migración por fases.

Puedes copiarlo tal cual en un archivo `MIGRACION_DDD_PEDIDOS.md` o en tu documentación interna.

---

# 📄 DOCUMENTO: MIGRACIÓN POR FASES A DDD – MÓDULO DE PEDIDOS

## 📌 Objetivo

Migrar el módulo de **Pedidos** a una arquitectura **DDD (Domain-Driven Design)** bien aplicada, sin romper el sistema actual.

La migración se hará por fases, migrando un endpoint a la vez, asegurando que el sistema se mantenga estable en producción.

---

## 🧩 FASES DEL PROYECTO

### 🟢 Fase 0 – Preparación (sin impacto en producción)

**Objetivo:** crear la estructura del proyecto para DDD sin usarla aún.

**Tareas:**

1. Crear carpetas:

```
app/Domain/Pedidos/
app/Application/Pedidos/
app/Infrastructure/Pedidos/
```

2. Crear clases base vacías:

* `PedidoAggregate`
* `ValueObjects`
* `Entities`
* `Repositories`
* `UseCases`
* `DTOs`
* `Events`
* `Listeners`

3. Crear tests básicos (mínimo 3):

* Crear pedido válido
* Confirmar pedido
* No permitir confirmar pedido finalizado

---

### 🟡 Fase 1 – Dominio (sin impacto en producción)

**Objetivo:** construir el dominio correctamente.

**Tareas:**

1. Crear Value Objects:

* `NumeroPedido`
* `Estado`

2. Crear Entities:

* `PrendaPedido`

3. Crear Aggregate Root:

* `PedidoAggregate`

4. Validar que el dominio funcione con tests (sin usar en producción aún).

---

### 🟠 Fase 2 – Persistencia DDD (sin impacto en producción)

**Objetivo:** crear repositorio y mapper sin usarlo todavía.

**Tareas:**

1. Crear `PedidoRepository` (interface).
2. Crear `PedidoRepositoryImpl` (implementación con Eloquent).
3. Crear un `Mapper` (Hydrator) para convertir:

   * Eloquent Model → PedidoAggregate
   * PedidoAggregate → Eloquent Model

**Nota:** No se reemplaza el código viejo todavía.
El nuevo repositorio existe pero no se usa aún.

---

### 🔵 Fase 3 – Migrar endpoint: Crear Pedido

**Objetivo:** migrar el endpoint de creación de pedidos a DDD.

**Tareas:**

1. Crear DTOs:

* `CrearPedidoDTO`
* `PedidoResponseDTO`

2. Crear Use Case:

* `CrearPedidoUseCase`

3. Modificar `PedidoController::store()` para que use el Use Case.

**Nota:** Los demás endpoints siguen funcionando con el código antiguo.

---

### 🟣 Fase 4 – Migrar endpoint: Confirmar Pedido

**Objetivo:** migrar el endpoint de confirmar pedido a DDD.

**Tareas:**

1. Crear Use Case:

* `ConfirmarPedidoUseCase`

2. Modificar `PedidoController::confirmar()` para usar el Use Case.

---

### 🟤 Fase 5 – Migrar consultas (Query Side)

**Objetivo:** separar lectura de escritura (CQRS básico).

**Tareas:**

1. Crear QueryHandlers o servicios de consulta:

* `ObtenerPedidoQueryHandler`
* `ListarPedidosQueryHandler`

2. Estos servicios pueden usar Eloquent directo, porque son solo lectura.

---

### ⚫ Fase 6 – Limpieza final

**Objetivo:** eliminar código antiguo y dejar solo el módulo DDD.

**Tareas:**

1. Eliminar lógica antigua del controlador.
2. Eliminar modelos viejos si ya no se usan.
3. Limpiar rutas y eliminar código duplicado.
4. Asegurar que todos los tests pasen.

---

## 🧠 Principios a cumplir

* **El dominio NO debe depender de Laravel**
* **Los casos de uso deben orquestar el flujo**
* **El agregado debe contener reglas del negocio**
* **Los repositorios deben ser interfaces**
* **La persistencia debe estar en Infrastructure**
* **Eventos de dominio para desacoplar acciones secundarias**
* **Separar lectura y escritura (CQRS básico)**

---

## 📌 Reglas de migración

1. **No se cambia todo de golpe.**
2. Se migran endpoints uno por uno.
3. Cada fase debe estar testeada y estable antes de avanzar.
4. Si algo falla, se revierte el cambio sin afectar producción.

---

## 📌 Indicadores de éxito

* El endpoint de crear pedido funciona en DDD.
* El endpoint de confirmar pedido funciona en DDD.
* El sistema no presenta errores nuevos.
* La lógica de negocio queda en el dominio.
* La persistencia queda en Infrastructure.
* Los controladores quedan limpios.

