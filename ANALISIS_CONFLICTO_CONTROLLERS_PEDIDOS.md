# 🔍 ANÁLISIS: CONFLICTO DE MÉTODOS EN CONTROLLERS DE PEDIDOS

## 📍 RESUMEN EJECUTIVO

**La buena noticia:** NO hay conflicto técnico de rutas. Las APIs están separadas.

**El verdadero riesgo:** Hay TWO sistemas de pedidos funcionando en paralelo SIN sincronización:
1. **Sistema LEGACY** - `/asesores/pedidos` (borradores complejos)
2. **Sistema DDD NUEVO** - `/api/pedidos` (pedidos formales)

**Esto significa:** Un cliente puede crear pedidos en AMBAS rutas y quedar con datos inconsistentes en BD.

### 1. AsesoresController (app/Http/Controllers/AsesoresController.php)
**Métodos de pedidos disponibles:**
- `index()` - GET /asesores/pedidos
- `create()` - GET /asesores/pedidos/create
- `store()` - POST /asesores/pedidos
- `show()` - GET /asesores/pedidos/{pedido}
- `edit()` - GET /asesores/pedidos/{pedido}/edit
- `update()` - PUT /asesores/pedidos/{pedido}
- `destroy()` - DELETE /asesores/pedidos/{pedido}
- `confirm()` - POST /asesores/pedidos/confirm
- `anularPedido()` - POST /asesores/pedidos/{id}/anular
- `getNextPedido()` - GET /asesores/pedidos/next-pedido

### 2. AsesoresAPIController (app/Infrastructure/Http/Controllers/Asesores/AsesoresAPIController.php)
**Métodos expuestos:**
- `store()` - POST /asesores/pedidos
- `confirm()` - POST /asesores/pedidos/confirm
- `anularPedido()` - POST /asesores/pedidos/{id}/anular
- `obtenerDatosRecibos()` - GET /asesores/pedidos/{id}/recibos-datos
- `obtenerFotosPrendaPedido()` - GET /asesores/prendas-pedido/{prendaPedidoId}/fotos

### 3. PedidoController (app/Http/Controllers/API/PedidoController.php) - NUEVO DDD
**Métodos expuestos:**
- `store()` - POST /api/pedidos
- `show()` - GET /api/pedidos/{id}
- `confirmar()` - PATCH /api/pedidos/{id}/confirmar
- `cancelar()` - DELETE /api/pedidos/{id}/cancelar
- `listarPorCliente()` - GET /api/pedidos/cliente/{clienteId}

---

## ⚠️ CONFLICTOS DETECTADOS

## 📋 ENTENDIENDO LOS DOMINIOS

### AsesoresAPIController::store() - /asesores/pedidos POST
**Propósito:** Crear BORRADORES de pedidos con productos (prendas) y logos
**Datos que maneja:**
- Cliente
- Forma de pago
- Área
- Productos (prendas) con:
  - nombre_producto, descripcion, talla, genero, cantidad
  - telas (color, referencia)
  - ref_hilo, precio_unitario
- Logos con:
  - descripcion, observaciones_tecnicas
  - tecnicas, ubicaciones
  - imagenes (archivos)
  - observaciones_generales

**Retorna:**
- Borrador ID si es exitoso
- O Logo Pedido ID si es logo

**Servicios usados:**
- `CrearPedidoService` (servicio antiguo, NO DDD)

---

### PedidoController::store() - /api/pedidos POST (DDD NUEVO)
**Propósito:** Crear PEDIDOS formales estructurados en DDD
**Datos que maneja:**
- cliente_id
- descripcion
- observaciones
- prendas (arreglo simple):
  - prenda_id
  - descripcion
  - cantidad
  - tallas (JSON simple)

**Retorna:**
- Pedido ID con número generado

**Servicios usados:**
- `CrearPedidoUseCase` (DDD Use Case)
- `PedidoAggregate` (Domain Aggregate)
- `PedidoRepository` (Domain Repository)

---

## 🎯 CONCLUSIÓN: NO SON COMPETENCIA

**Estos NO son conflictos, son DOMINIOS DIFERENTES:**

| Aspecto | AsesoresAPI | PedidoController |
|---------|-------------|-----------------|
| **URL** | POST /asesores/pedidos | POST /api/pedidos |
| **Propósito** | Crear borradores complejos | Crear pedidos DDD |
| **Usuarios** | Asesores internos | API pública / sistemas externos |
| **Datos** | Productos, logos, telas | Prendas simples |
| **Estado** | Borrador → Confirmado | Pendiente → Confirmado → Producción |
| **Arquitectura** | Legacy (servicio antiguo) | DDD (agregado, UC, repositorio) |
| **Tablas** | pedidos_produccion, logo_pedido | pedidos (nueva tabla DDD) |

---

## 🛑 IMPACTO EN PRODUCCIÓN

**Estado ACTUAL:**

1. ✅ `/asesores/pedidos` maneja BORRADORES complejos (legacy, funciona)
2. ✅ `/api/pedidos` maneja PEDIDOS DDD simples (nuevo, funciona)
3. ✅ **NO hay conflicto porque son dominios diferentes**
4. ⚠️ **PERO: Hay potencial para CONFUSIÓN si clientes usan ambas**

**Riesgo Real:**
- Clientes pueden crear pedidos en AMBAS rutas
- Los datos no se sincronizan
- Un pedido en `/asesores/pedidos` no se ve en `/api/pedidos`
- Esto crea INCONSISTENCIA en la BD

**Ejemplo del problema:**
```
Cliente A hace:
  POST /asesores/pedidos    → Crea en tabla pedidos_produccion
  POST /api/pedidos         → Crea en tabla pedidos

Dos sistemas paralelos SIN sincronización ❌
```

---

## 📋 MÉTODOS NO EXPUESTOS EN API

El nuevo DDD Controller (`PedidoController`) NO está siendo usado correctamente porque:

1. ❌ Las rutas de asesores usan `AsesoresController` (métodos antiguos)
2. ❌ `AsesoresAPIController` tiene métodos DDD pero están ocultos por conflicto de rutas
3. ✅ El nuevo `PedidoController` está en `/api/pedidos` (diferente)

---

## 🎯 SOLUCIÓN RECOMENDADA

### Opción A: Mantener compatibilidad (RECOMENDADO PARA PRODUCCIÓN)
1. Mantener `AsesoresController` como está (vistas web)
2. **Eliminar o comentar las rutas duplicadas en `AsesoresAPIController`**
3. Redirigir a `/api/pedidos` (nuevo DDD) en la documentación

### Opción B: Migración completa a DDD
1. Reemplazar `AsesoresController::store()` con `PedidoController::store()`
2. Actualizar todas las rutas a usar `PedidoController`
3. Eliminar `AsesoresAPIController`
4. Requiere migración de clientes (cambio de rutas)

### Opción C: Segregación clara
1. Mantener `/asesores/pedidos` para vistas (AsesoresController)
2. Usar `/api/pedidos` para API JSON (PedidoController - DDD)
3. Documentar claramente cuál usar
4. **AGREGAR MIDDLEWARE para diferenciar**

---

## 📊 TABLA COMPARATIVA

| Aspecto | `/asesores/pedidos` | `/api/pedidos` |
|---------|-------------------|----------------|
| **Controller** | AsesoresAPIController | PedidoController (DDD) |
| **Propósito** | Borradores internos | Pedidos formales |
| **Usuarios** | Asesores (rol:asesor) | API Pública |
| **Tabla BD** | pedidos_produccion | pedidos (nueva) |
| **Estructura** | Compleja (productos, logos) | Simple (prendas) |
| **Arquitectura** | Legacy (servicios) | DDD (UC, Aggregate, Repo) |
| **Estado de pedido** | Borrador → Confirmado | Pendiente → Confirmado → Producción |
| **Tests** | No hay | 16 tests ✅ |
| **Documentado** | No | Sí (GUIA_API_PEDIDOS_DDD.md) |

---

## 🚨 RECOMENDACIÓN FINAL

### El problema REAL es la DUPLICIDAD, no el conflicto

**No hay que eliminar nada, pero SÍ hay que:**

1. ✅ **Documentar claramente** cuál usar según el caso:
   - Asesores creando borradores → `/asesores/pedidos` (legacy)
   - Sistemas externos / API → `/api/pedidos` (DDD)

2. ✅ **Proteger el nuevo DDD** con tests de integración E2E

3. ✅ **Considerar una migración futura** cuando se migre `pedidos_produccion` a la tabla `pedidos` (DDD)

4. ⚠️ **Advertir a clientes** que NO mezclen ambas rutas en la misma operación

### FASE SIGUIENTE (Migración Gradual)

```
Ahora (Enero 2026):
  /asesores/pedidos    ← Legacy (funciona, no tocar)
  /api/pedidos         ← Nuevo DDD (recién refactorizado)

Fase 2 (Cuando esté listo):
  Migrar /asesores/pedidos → Usar PedidoController (DDD)
  Consolidar en una sola tabla `pedidos`
  Mantener `/api/pedidos` como estándar
```
