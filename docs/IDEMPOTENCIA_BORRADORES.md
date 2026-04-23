# 🔐 IDEMPOTENCIA EN CREACIÓN DE BORRADORES - GUÍA COMPLETA

## Resumen Ejecutivo

Este documento describe cómo se implementó idempotencia en el sistema de borradores para **prevenir duplicados por múltiples requests**.

## ⚙️ Cómo Funciona

### Creación (POST) - CON Idempotencia

```
Usuario hace clic "Guardar" 3 veces rápido
│
├─ Request 1: POST /api/asesores/pedidos/borrador + X-Idempotency-Key: abc-123
│  └─ Crear pedido #100
│
├─ Request 2: POST /api/asesores/pedidos/borrador + X-Idempotency-Key: abc-123
│  └─ (DETECTADO) Retornar resultado cacheado → pedido #100
│
└─ Request 3: POST /api/asesores/pedidos/borrador + X-Idempotency-Key: abc-123
   └─ (DETECTADO) Retornar resultado cacheado → pedido #100

Resultado: 1 solo pedido creado ✅
```

### Actualización (PUT) - SIN necesidad de Idempotencia

```
Usuario edita borrador múltiples veces
│
├─ Request 1: PUT /api/asesores/pedidos/100/borrador
│  └─ Actualizar pedido #100
│
├─ Request 2: PUT /api/asesores/pedidos/100/borrador
│  └─ Actualizar pedido #100 (mismo resultado)
│
└─ Request 3: PUT /api/asesores/pedidos/100/borrador
   └─ Actualizar pedido #100 (mismo resultado)

Resultado: 1 solo pedido, actualizado 3 veces ✅
```

---

## 🛠️ Componentes Implementados

### 1. **Middleware: IdempotencyKeyMiddleware**

**Ubicación:** `app/Http/Middleware/IdempotencyKeyMiddleware.php`

**Qué hace:**
- Intercepta todos los POST a `/api/asesores/pedidos/borrador`
- Busca el header `X-Idempotency-Key`
- Si ya procesó esa clave → retorna resultado cacheado
- Si no → deja procesar y cachea el resultado

**Cache:** 24 horas (configurable)

### 2. **FormRequests: Validación Explícita**

#### CrearBorradorRequest
- ❌ Rechaza si viene `pedido_id` en el payload
- ✅ Requiere `pedido` JSON válido
- ✅ Valida archivos de imágenes

#### ActualizarBorradorRequest
- ❌ Rechaza si viene `pedido_id` en el payload (ya está en URL)
- ✅ Requiere `pedido` JSON válido

### 3. **Frontend: IdempotencyService**

**Ubicación:** `public/js/modulos/crear-pedido/services/idempotency-service.js`

**Qué hace:**
- Genera UUID único para CREAR (se mantiene fijo durante todo el ciclo)
- NO genera clave para ACTUALIZAR (PUT es idempotente automáticamente)
- Proporciona método `obtenerIdempotencyKey()`

### 4. **BD: Constraints para Duplicados**

**Migración:** `database/migrations/2026_04_23_000000_add_borrador_constraints.php`

**Qué agrega:**
```sql
-- Un asesor NO puede tener múltiples borradores del MISMO cliente
UNIQUE KEY uk_borrador_por_asesor_cliente (asesor_id, cliente_id, estado)
WHERE estado = 'BORRADOR' AND deleted_at IS NULL
```

**Indices para velocidad:**
- `idx_pedidos_asesor_estado` - búsquedas rápidas por asesor
- `idx_pedidos_cliente_estado` - búsquedas rápidas por cliente

---

## 📋 Requisitos para Activar

### 1. Ejecutar Migración

```bash
php artisan migrate
```

### 2. Cargar JavaScript

En tu vista de creación de borradores, agrega ANTES de `draft-pedido-orchestrator.js`:

```html
<script src="{{ asset('js/modulos/crear-pedido/services/idempotency-service.js') }}"></script>
```

### 3. Inicializar en Frontend

En tu código donde defines `window.modoEdicion`:

```javascript
// Después de cargar datos
if (window.idempotencyService) {
    window.idempotencyService.inicializar(
        window.modoEdicion || false,
        window.pedidoEditarId || null
    );
}
```

---

## 🧪 Ejecutar Tests

```bash
# Todos los tests de idempotencia
php artisan test tests/Feature/Pedidos/CrearBorradorIdempotenciaTest.php

# Test específico: doble clic
php artisan test tests/Feature/Pedidos/CrearBorradorIdempotenciaTest.php::test_doble_click_con_idempotency_key_crea_solo_un_pedido

# Test específico: update no duplica
php artisan test tests/Feature/Pedidos/CrearBorradorIdempotenciaTest.php::test_actualizar_borrador_no_crea_duplicado
```

---

## 📊 Capas de Defensa

```
NIVEL 1: Frontend
├─ IdempotencyService genera clave única
└─ DraftPedidoSaveService envía X-Idempotency-Key

NIVEL 2: HTTP
├─ IdempotencyKeyMiddleware cachea resultados
└─ Cache: 24 horas

NIVEL 3: Validación
├─ CrearBorradorRequest: rechaza pedido_id
└─ ActualizarBorradorRequest: rechaza pedido_id en body

NIVEL 4: Base de Datos
├─ Unique constraint: (asesor_id, cliente_id, estado='BORRADOR')
└─ Indices para velocidad
```

---

## 🔍 Debugging

### Ver si la idempotencia está funcionando:

**En DevTools (Browser) - Console:**
```javascript
// Ver estado actual
console.log(window.idempotencyService.obtenerEstado());

// Debe mostrar algo como:
{
  modoEdicion: false,
  pedidoIdActual: null,
  idempotencyKey: "abc-123-def-456",
  tieneKey: true
}
```

**En el Network tab:**
- Busca requests a `/api/asesores/pedidos/borrador`
- Verifica que tengan el header `X-Idempotency-Key`
- La respuesta debe incluir `"idempotency_cached": true` en duplicados

**En Logs (Laravel):**
```
[IdempotencyKeyMiddleware] DUPLICADO DETECTADO Y RECHAZADO
[DraftPedidoSaveService] MODO CREACIÓN
```

---

## ⚠️ Notas Importantes

1. **No modificar idempotency key:** Una vez generada para CREAR, no debe cambiar
2. **PUT es idempotente:** No necesita clave, hacerlo 3 veces = mismo resultado
3. **Cache expira en 24h:** Después, una "nueva" request con la MISMA clave creará un nuevo pedido
4. **Por usuario:** Cada usuario tiene su propia cache de claves

---

## 🚀 Ejemplos de Request

### CREAR con Idempotencia

```bash
curl -X POST "http://localhost/api/asesores/pedidos/borrador" \
  -H "X-Idempotency-Key: abc-123-def-456" \
  -H "Content-Type: multipart/form-data" \
  -F "pedido={...json...}"
```

### ACTUALIZAR (sin idempotencia)

```bash
curl -X PUT "http://localhost/api/asesores/pedidos/100/borrador" \
  -H "Content-Type: multipart/form-data" \
  -F "pedido={...json...}"
```

---

## 📞 Preguntas Frecuentes

### ¿Qué pasa si la red es muy lenta?

El middleware cachea durante 24 horas. Incluso si el navegador se cierra y reabre, una nueva request con la MISMA clave retornará el pedido original.

### ¿Y si quiero crear otro borrador del mismo cliente?

Necesitas una **nueva clave de idempotencia** (UUID diferente). El `IdempotencyService` la genera automáticamente.

### ¿Qué pasa en los updates?

PUT es idempotente por naturaleza. No necesita clave. Hacer PUT 100 veces = mismo resultado.

### ¿Dónde se cachea?

Por defecto en Laravel Cache (puedes cambiar a Redis en `config/cache.php`).

---

## 🔄 Migración Desde Código Anterior

Si tienes código viejo que aún usa:

```javascript
// ❌ VIEJO - No debe venir id
formData.append('pedido_id', pedidoId);
```

**Cambia a:**

```javascript
// ✅ NUEVO - El id va en la URL para PUT
endpoint = `/api/asesores/pedidos/${pedidoId}/borrador`;
metodo = 'PUT'; // No POST
```

---

## 📈 Métricas de Éxito

Después de implementar, debes ver:

✅ Cero duplicados al hacer doble clic  
✅ Cero duplicados al reabrir borrador  
✅ Cache hits en logs (middleware detecta duplicados)  
✅ 1 solo pedido en BD por (usuario, cliente)  

---

**Última actualización:** 2026-04-23  
**Versión:** 1.0  
**Estado:** Production Ready ✅
