# 🔍 ANÁLISIS PROFUNDO: CÓDIGO LEGACY DE PEDIDOS NO MIGRADO A DDD

**Fecha:** 22/01/2026  
**Estado:** ANÁLISIS COMPLETO DE DEUDA TÉCNICA  
**Alcance:** Módulo de Pedidos

---

## 📊 INVENTARIO COMPLETO DE CONTROLADORES LEGACY

### 1. **AsesoresController.php** ⚠️ MUY GRANDE
**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php`  
**Líneas:** ~640 líneas  
**Responsabilidad:** Todo lo de pedidos (MIXED)

**Métodos de Pedidos:**
- `index()` - Listar pedidos (delega a ObtenerPedidosService)
- `create()` - Mostrar formulario crear pedido
- `store()` - Guardar nuevo pedido (LÓGICA DE NEGOCIO PURA)
- `confirm()` - Confirmar pedido
- `show()` - Mostrar pedido
- `edit()` - Editar pedido
- `update()` - Actualizar pedido
- `destroy()` - Anular pedido
- `getNextPedido()` - Obtener siguiente pedido
- `anularPedido()` - Anular un pedido

**Problema:** 
- ❌ Contiene lógica de negocio mezclada con HTTP
- ❌ Inyecta servicios directamente, no Use Cases
- ❌ Métodos muy largos (store ~80+ líneas)
- ❌ NO ESTÁ EN DDD

---

### 2. **AsesoresAPIController.php** ⚠️ GRANDE
**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php`  
**Líneas:** ~600+ líneas  
**Responsabilidad:** API de pedidos (LEGACY API)

**Métodos de Pedidos:**
- `store()` - Crear pedido desde API
- `confirm()` - Confirmar pedido
- `anularPedido()` - Anular pedido
- `obtenerDatosFactura()` - Obtener datos para factura
- `obtenerDatosEdicion()` - Obtener datos para edición
- `obtenerFotosPrendaPedido()` - Obtener fotos de prenda
- Posiblemente más...

**Problema:**
- ❌ API antigua sin DDD
- ❌ Duplica lógica de AsesoresController
- ❌ Inyecta servicios legacy, no Use Cases
- ❌ NO ESTÁ EN DDD

---

### 3. **PedidoEstadoController.php** ⚠️ MEDIUM
**Ubicación:** `app/Http/Controllers/PedidoEstadoController.php`  
**Líneas:** ~150 líneas  
**Responsabilidad:** Gestión de estado de pedidos

**Métodos:**
- `aprobarSupervisor()` - Aprobar como supervisor
- `historial()` - Obtener historial
- `seguimiento()` - Obtener seguimiento

**Problema:**
- ⚠️ Maneja estados de pedidos
- ⚠️ NO tiene Use Cases
- ⚠️ Lógica de estado mezclada con HTTP
- ❌ NO ESTÁ EN DDD

---

### 4. **PedidosProduccionController.php** ⚠️ GRANDE (CQRS)
**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`  
**Líneas:** ~1,069 líneas  
**Responsabilidad:** Producción de pedidos (CQRS)

**Estado:** ⚠️ Usa CQRS pero NO está en DDD puro
- Usa QueryBus/CommandBus
- Pero no tiene Domain Layer correcta
- Lógica de negocio en Services, no en Agregados

---

### 5. **SupervisorPedidosController.php** ⚠️ LEGACY
**Ubicación:** `app/Http/Controllers/SupervisorPedidosController.php`  
**Problema:**
- Panel de supervisor
- Probablemente con lógica mezclada
- NO ESTÁ EN DDD

---

### 6. **RegistroBodegaController.php** ⚠️ HUGE
**Ubicación:** `app/Http/Controllers/RegistroBodegaController.php`  
**Líneas:** ~1,200+  
**Responsabilidad:** Gestión bodega de pedidos

**Métodos que tocan Pedidos:**
- `show()`, `getPrendas()`, `getNextPedido()`
- `validatePedido()`, `update()`, `getEntregas()`
- `updatePedido()`, `editFullOrder()`, etc.

**Problema:**
- ❌ ENORME controlador (1,200+ líneas)
- ❌ Lógica de negocio directa en controller
- ❌ NO ESTÁ EN DDD
- ❌ Toca pedidos pero no es especializado

---

### 7. **OrdenController.php** ⚠️ MEDIUM
**Ubicación:** `app/Http/Controllers/OrdenController.php`  
**Métodos Pedidos:**
- `obtenerProcesosPorPedido()`
- `obtenerHistorial()`
- `editarPedido()`

**Problema:**
- ⚠️ Controlador de "Orden" pero maneja pedidos
- ❌ NO ESTÁ EN DDD
- ⚠️ Duplicación de lógica

---

### 8. **CrearPedidoEditableController.php**  (PARCIALMENTE MIGRADO)
**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`  
**Estado:**  Refactorizado a Use Cases (hace poco)
- Ya usa `AgregarItemPedidoUseCase`
- Ya usa `EliminarItemPedidoUseCase`
- Ya usa `ObtenerItemsPedidoUseCase`

---

### 9. **GuardarPedidoJSONController.php**  (PARCIALMENTE MIGRADO)
**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/GuardarPedidoJSONController.php`  
**Estado:**  Refactorizado a Use Cases (hace poco)
- Ya usa `GuardarPedidoDesdeJSONUseCase`
- Ya usa `ValidarPedidoDesdeJSONUseCase`

---

### 10. **PedidoController.php**  (MIGRADO)
**Ubicación:** `app/Http/Controllers/API/PedidoController.php`  
**Estado:**  YA EN DDD
- Usa `CrearPedidoUseCase`
- Usa `ConfirmarPedidoUseCase`
- Usa `ObtenerPedidoUseCase`

---

## 📈 RESUMEN DE MIGRACIÓN

| Controlador | Líneas | DDD | Estado |
|------------|--------|-----|--------|
| **AsesoresController** | ~640 | ❌ | NO MIGRADO |
| **AsesoresAPIController** | ~600 | ❌ | NO MIGRADO |
| **PedidoEstadoController** | ~150 | ❌ | NO MIGRADO |
| **PedidosProduccionController** | ~1,069 | ⚠️ | CQRS PARCIAL |
| **SupervisorPedidosController** | ? | ❌ | NO MIGRADO |
| **RegistroBodegaController** | ~1,200 | ❌ | NO MIGRADO |
| **OrdenController** | ? | ❌ | NO MIGRADO |
| CrearPedidoEditableController | ~450 |  | MIGRADO |
| GuardarPedidoJSONController | ~130 |  | MIGRADO |
| PedidoController (API) | ~250 |  | MIGRADO |

---

## 🚨 DEUDA TÉCNICA REAL

### Total de código legacy SIN migrar a DDD:
- **Mínimo: 4,500+ líneas de código legacy**
- **En: 7 controladores principales**
- **Afectando:** Creación, edición, aprobación, seguimiento de pedidos

---

## 🔴 PROBLEMAS PRINCIPALES

### 1. **Duplicación Masiva**
- AsesoresController.store() y AsesoresAPIController.store() - Mismo código
- Múltiples lugares hacen crear/editar pedidos
- Lógica de negocio esparcida

### 2. **Servicios Legacy Gigantes**
- `ObtenerPedidosService`
- `PedidoPrendaService`
- `PedidoCreationService`
- `GuardarPedidoDesdeJSONService`
- Todos inyectados directamente en controllers (NO es DDD)

### 3. **Controladores Monolíticos**
- AsesoresController: 640 líneas (debería ser 50-100)
- RegistroBodegaController: 1,200+ líneas
- PedidosProduccionController: 1,069 líneas

### 4. **Falta Domain Layer Real**
- Los Services NO son parte del Domain
- No hay Agregados reales para Pedidos
- No hay Value Objects específicos
- No hay Repositories para recuperar datos

### 5. **Lógica de Negocio en Controllers**
```php
// ❌ ESTO NO ES DDD
public function store(Request $request) {
    $validated = $request->validate(...);
    $pedido = new PedidoProduccion();
    $pedido->fill($validated);
    $pedido->save();
    // ... más lógica aquí
}

//  ESTO SÍ ES DDD
public function store(Request $request) {
    $dto = CrearPedidoDTO::fromRequest($request);
    $response = $this->crearPedidoUseCase->ejecutar($dto);
    return response()->json($response);
}
```

---

##  PLAN DE MIGRACIÓN COMPLETO (REALISTA)

### FASE 1: Migrar AsesoresController (Principal)
**Tiempo estimado:** 4-6 horas  
**Métodos a migrar:**
- ✓ index() → ListarPedidosPorAsesorUseCase
- ✓ store() → CrearPedidoUseCase (ya existe, mejorar)
- ✓ confirm() → ConfirmarPedidoUseCase (ya existe, mejorar)
- ✓ show() → ObtenerPedidoUseCase (ya existe)
- ✓ edit() → ObtenerPedidoParaEdicionUseCase (CREAR)
- ✓ update() → ActualizarPedidoUseCase (mejora)
- ✓ destroy() → AnularPedidoUseCase
- ✓ getNextPedido() → ObtenerSiguientePedidoUseCase

---

### FASE 2: Migrar AsesoresAPIController
**Tiempo estimado:** 3-4 horas  
**Objetivo:** Consolidar con API PedidoController
- Reutilizar Use Cases de FASE 1
- Eliminar duplicación

---

### FASE 3: Migrar PedidoEstadoController
**Tiempo estimado:** 2-3 horas  
**Métodos:**
- ✓ aprobarSupervisor() → AprobarPedidoUseCase (CREAR)
- ✓ historial() → ObtenerHistorialPedidoUseCase (CREAR)
- ✓ seguimiento() → ObtenerSeguimientoPedidoUseCase (CREAR)

---

### FASE 4: Migrar PedidosProduccionController
**Tiempo estimado:** 6-8 horas  
**Objetivo:** Convertir CQRS parcial a DDD puro
- Crear Domain Layer para Producción
- Crear Use Cases para comandos
- Usar Domain agregados

---

### FASE 5: Limpiar RegistroBodegaController
**Tiempo estimado:** 4-5 horas  
**Objetivo:** Extraer lógica de Pedidos
- Crear `RegistroBodegaPedidoUseCase`
- Crear `ValidarPedidoBodegaUseCase`
- Fragmentar controlador grande

---

## RECOMENDACIÓN

**Opción A: Migración Completa (RECOMENDADO)**
- Total: 19-26 horas de trabajo
- Resultado: 100% DDD
- Beneficio: Código limpio, testeable, mantenible

**Opción B: Migración Crítica (RÁPIDA)**
- Migrar solo: AsesoresController, AsesoresAPIController
- Total: 7-10 horas
- Resultado: 70% migrado
- Beneficio: Mejora significativa rápido

**Opción C: No hacer nada (PROBLEMA)**
- Mantener legacy
- Seguir con deuda técnica
- Cada cambio será más costoso

---

##  PRÓXIMO PASO

¿Cuál opción prefieres?
- **A) Migración completa (todas las fases)**
- **B) Migración crítica (fases 1-2)**
- **C) Empezar con fase 1 (AsesoresController)**
- **D) Otro enfoque**

Tengo un plan detallado para cada una.
