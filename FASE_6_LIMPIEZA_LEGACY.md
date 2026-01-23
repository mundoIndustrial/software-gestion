# 🧹 FASE 6: LIMPIEZA DE CÓDIGO LEGACY

**Objetivo:** Limpiar código legacy del módulo de Pedidos después de la migración a DDD/CQRS.

**Status:** ✅ Refactor DDD completado → Ahora limpiar código viejo

---

## 📊 ANÁLISIS ACTUAL DEL PROYECTO

### ✅ YA MIGRADO A DDD/CQRS

1. **PedidoController.php** (API - DDD)
   - Ubicación: `app/Http/Controllers/API/PedidoController.php`
   - Estado: ✅ Usando Use Cases (DDD)
   - Métodos: `store()`, `confirmar()`, `obtener()`, `listar()`
   - Use Cases: CrearPedidoUseCase, ConfirmarPedidoUseCase, etc.

2. **PedidosProduccionController.php** (CQRS)
   - Ubicación: `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
   - Estado: ✅ Usando CQRS (QueryBus, CommandBus)
   - Patrón: Commands/Queries (QueryHandlers, CommandHandlers)

3. **Módulo de Pedidos**
   - Ubicación: `app/Modules/Pedidos/`
   - Estado: ✅ Estructura DDD completa
   - Contiene: Domain/, Application/, Infrastructure/

---

## 🗑️ CONTROLADORES LEGACY ACTUALMENTE EN USO

**⚠️ IMPORTANTE:** Estos controladores ESTÁN SIENDO USADOS en rutas activas.

### Controladores en Uso Actualmente

```
ACTIVO ✓ app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php
   - Rutas: api-pedidos-editable.php, routes/web.php
   - Métodos en Uso:
     • agregarItem (POST /items/agregar)
     • eliminarItem (POST /items/eliminar)
     • obtenerItems (GET /items)
     • validarPedido (POST /validar)
     • crearPedido (POST /crear)
     • subirImagenesPrenda (POST /subir-imagenes)
   - Status: ACTIVO - Se usa para crear/editar pedidos desde frontend
   - Migración Requerida: SÍ - Necesita migrar a DDD

ACTIVO ✓ app/Infrastructure/Http/Controllers/Asesores/GuardarPedidoJSONController.php
   - Rutas: routes/web.php
   - Métodos en Uso:
     • guardar (POST /guardar-desde-json)
     • validar (POST /validar-json)
   - Status: ACTIVO
   - Migración Requerida: SÍ

⚠️ app/Http/Controllers/Asesores/PedidoLogoAreaController.php
   - Status: Verificar si se usa
   - Migración Requerida: Depende

⚠️ app/Http/Controllers/SupervisorPedidosController.php
   - Status: Usado en supervisor panel
   - Migración Requerida: Revisar primero
```

---

## 📋 PLAN DE LIMPIEZA POR FASES

### ⏸️ PAUSA IMPORTANTE

**No se pueden eliminar estos controladores hasta migrar su funcionalidad a DDD.**

**Opción 1: Migración Completa (Recomendado)**
```
Crear nuevos Use Cases para:
  ✓ AgregarItemPedidoUseCase
  ✓ EliminarItemPedidoUseCase
  ✓ ValidarPedidoUseCase
  ✓ SubirImagenesPrendaUseCase
  
Crear nuevos endpoints en API:
  ✓ POST /api/pedidos/items
  ✓ DELETE /api/pedidos/items/{id}
  ✓ POST /api/pedidos/validar
  ✓ POST /api/pedidos/{id}/imagenes
  
Actualizar Frontend:
  ✓ Cambiar llamadas a nuevos endpoints DDD
```

**Opción 2: Refactorizar Controllers Legacy (Temporal)**
```
Mantener controladores pero:
  ✓ Hacer que usen Use Cases internamente
  ✓ Eliminar lógica de negocio
  ✓ Usar DTOs/validación común
  ✓ Reducir a mínimo (solo HTTP)
```

**Opción 3: Limpieza Mínima (Ahora)**
```
Eliminar solo lo que definitivamente NO se usa:
  ✓ Buscar servicios duplicados
  ✓ Limpiar imports innecesarios
  ✓ Eliminar clases comentadas
  ✓ Actualizar documentación
```

---

## 🔍 RECOMENDACIÓN ACTUAL

Basado en el análisis: **No tenemos controladores completamente abandonados.**

**Lo que SÍ podemos hacer ahora (Seguro):**

1. ✅ Limpiar imports y usar statements innecesarios
2. ✅ Documentar qué métodos usan cada controlador
3. ✅ Crear tests para endpoints activos
4. ✅ Refactorizar controllers para que usen Use Cases
5. ✅ Consolidar lógica duplicada

**Lo que NECESITA migración:**

1. CrearPedidoEditableController → AgregarItemPedidoUseCase + refactor
2. GuardarPedidoJSONController → CrearPedidoUseCase (migrado a DDD)
3. Endpoints editable → Mirar si se pueden unificar en API DDD

---

## 📌 SIGUIENTE PASO

**Opción A: Continuar con Limpieza Mínima**
```
1. Refactorizar CrearPedidoEditableController para usar Use Cases
2. Refactorizar GuardarPedidoJSONController para usar PedidoController API
3. Actualizar rutas web.php para apuntar a nuevos controladores
4. Limpiar código muerto
5. Commit: "Refactor: Limpiar controladores legacy"
```

**Opción B: Migración Completa (Más Trabajo)**
```
1. Crear nuevos Use Cases para cada método
2. Crear nuevos endpoints en API REST
3. Actualizar Frontend para usar nuevos endpoints
4. Eliminar controladores legacy completamente
5. Tests de integración
6. Commit: "Refactor: Migrar CrearPedidoEditableController a DDD"
```

**¿Cuál prefieres?**

---

## 🗂️ ARCHIVOS INVOLUCRADOS

**Controllers Legacy:**
- `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`
- `app/Infrastructure/Http/Controllers/Asesores/GuardarPedidoJSONController.php`
- `app/Http/Controllers/Asesores/PedidoLogoAreaController.php`
- `app/Http/Controllers/SupervisorPedidosController.php`

**Rutas:**
- `routes/api-pedidos-editable.php` (Está activa)
- `routes/web.php` (Líneas 895-920)
- `routes/asesores.php` (Líneas 46-76)

**Use Cases (Ya Creados):**
- `app/Application/Pedidos/UseCases/CrearPedidoUseCase.php`
- `app/Application/Pedidos/UseCases/ConfirmarPedidoUseCase.php`
- `app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php`

---

## 📊 ESTADO DE MIGRACIÓN

```
[█████████░] 90% Refactor DDD Completado
- Domain Layer: ✅ Completo
- Application Layer: ✅ Completo (Use Cases)
- Infrastructure: ✅ Completo (Repositories, Events)
- Controllers: ⚠️ Parcialmente Refactorizado
  ├── API Controllers: ✅ Usando Use Cases
  ├── Legacy Editable: ⚠️ Aún no migrado
  └── Legacy JSON: ⚠️ Aún no migrado
```

---

**¿Qué opción prefieres? (A, B, o esperar más cambios?)**
