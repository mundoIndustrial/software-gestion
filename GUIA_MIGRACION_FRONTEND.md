# GUÍA MIGRACIÓN FRONTEND - SISTEMA PEDIDOS DDD

**Fecha:** 2024
**Estado:** Activa
**Prioridad:** ALTA

---

## 📌 Resumen

El frontend debe ser actualizado para llamar a los nuevos endpoints DDD en `/api/pedidos`. Esta guía contiene ejemplos de ANTES y DESPUÉS para cada operación.

---

## 🔄 Migración por Operación

### 1. CREAR PEDIDO

#### ANTES (Legacy - ❌ DEPRECADO):
```javascript
// POST /asesores/pedidos
async function crearPedido(datos) {
    const response = await fetch('/asesores/pedidos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(datos)
    });
    
    const result = await response.json();
    if (result.success) {
        return result.borrador_id || result.logo_pedido_id;
    }
}
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// POST /api/pedidos
async function crearPedido(datos) {
    const response = await fetch('/api/pedidos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`  // Si usa tokens API
        },
        body: JSON.stringify({
            cliente: datos.cliente,
            descripcion: datos.descripcion || '',
            prendas: datos.prendas || [],
            // ... más campos según Use Case
        })
    });
    
    if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
    }
    
    const result = await response.json();
    return result.data.id; // Retorna ID del nuevo pedido
}
```

**Cambios Clave:**
- Endpoint: `/asesores/pedidos` → `/api/pedidos`
- Response: `borrador_id` → `data.id` (estructura DTO)
- Requiere: Bearer token (si está habilitado)

---

### 2. CONFIRMAR PEDIDO

#### ANTES (Legacy - ❌ DEPRECADO):
```javascript
// POST /asesores/pedidos/confirm
async function confirmarPedido(borradorId, numeroPedido) {
    const response = await fetch('/asesores/pedidos/confirm', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            borrador_id: borradorId,
            numero_pedido: numeroPedido
        })
    });
    
    const result = await response.json();
    return result.success;
}
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// PATCH /api/pedidos/{id}/confirmar
async function confirmarPedido(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}/confirmar`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({})
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error al confirmar pedido');
    }
    
    const result = await response.json();
    return result.data; // Retorna el pedido actualizado
}
```

**Cambios Clave:**
- Endpoint: `POST /asesores/pedidos/confirm` → `PATCH /api/pedidos/{id}/confirmar`
- El numero_pedido se genera automáticamente en el UseCase
- Response: Retorna la versión actualizada del pedido (DTO completo)

---

### 3. CANCELAR / ANULAR PEDIDO

#### ANTES (Legacy - ❌ DEPRECADO):
```javascript
// POST /asesores/pedidos/{id}/anular
async function anularPedido(pedidoId, razonAnulacion) {
    const response = await fetch(`/asesores/pedidos/${pedidoId}/anular`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            novedad: razonAnulacion
        })
    });
    
    const result = await response.json();
    return result.success;
}
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// DELETE /api/pedidos/{id}/cancelar
async function cancelarPedido(pedidoId, razonCancelacion) {
    const response = await fetch(`/api/pedidos/${pedidoId}/cancelar`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({
            razon: razonCancelacion
        })
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error al cancelar pedido');
    }
    
    const result = await response.json();
    return result.data; // Retorna el pedido cancelado
}
```

**Cambios Clave:**
- Endpoint: `POST /asesores/pedidos/{id}/anular` → `DELETE /api/pedidos/{id}/cancelar`
- Field: `novedad` → `razon`
- HTTP Method: POST → DELETE (REST semántico)

---

### 4. OBTENER DETALLE DE PEDIDO

#### ANTES (Legacy - ❌ DEPRECADO):
```javascript
// GET /asesores/pedidos/{id}/recibos-datos
async function obtenerDetallePedido(pedidoId) {
    const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`
        }
    });
    
    const data = await response.json();
    return data; // Estructura legacy
}
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// GET /api/pedidos/{id}
async function obtenerDetallePedido(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`
        }
    });
    
    if (!response.ok) {
        throw new Error('Pedido no encontrado');
    }
    
    const result = await response.json();
    return result.data; // DTO estructurado
}
```

**Cambios Clave:**
- Endpoint: `GET /asesores/pedidos/{id}/recibos-datos` → `GET /api/pedidos/{id}`
- Response: `{ ...data }` → `{ data: { ... } }` (wrapped in DTO)

---

### 5. LISTAR PEDIDOS POR CLIENTE

#### ANTES (Legacy - ❌ NO EXISTÍA):
```javascript
// NO HABÍA ENDPOINT PARA LISTAR PEDIDOS DEL CLIENTE
// Tenía que obtener todos y filtrar en frontend
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// GET /api/pedidos/cliente/{clienteId}
async function listarPedidosCliente(clienteId) {
    const response = await fetch(`/api/pedidos/cliente/${clienteId}`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`
        }
    });
    
    if (!response.ok) {
        throw new Error('Error obteniendo pedidos del cliente');
    }
    
    const result = await response.json();
    return result.data; // Array de pedidos
}
```

**Cambios Clave:**
- Nuevo endpoint que NO EXISTÍA antes
- Filtra por cliente en el servidor (mejor performance)
- Retorna array de DTOs

---

### 6. ACTUALIZAR DESCRIPCIÓN DE PEDIDO

#### ANTES (Legacy - ❌ NO EXISTÍA):
```javascript
// NO HABÍA ENDPOINT SEPARADO
// Tenía que hacer PUT a /asesores/pedidos/{id} desde vistas
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// PATCH /api/pedidos/{id}/actualizar-descripcion
async function actualizarDescripcion(pedidoId, nuevaDescripcion) {
    const response = await fetch(`/api/pedidos/${pedidoId}/actualizar-descripcion`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({
            descripcion: nuevaDescripcion
        })
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error actualizando descripción');
    }
    
    const result = await response.json();
    return result.data; // Pedido actualizado
}
```

**Cambios Clave:**
- Nuevo endpoint específico
- Valida que el pedido no esté en estado final
- Retorna DTO completo actualizado

---

### 7. INICIAR PRODUCCIÓN

#### ANTES (Legacy - ❌ NO EXISTÍA):
```javascript
// NO HABÍA MÉTODO ESPECÍFICO
// Se usaba en workflows pero sin separación clara
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// POST /api/pedidos/{id}/iniciar-produccion
async function iniciarProduccion(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}/iniciar-produccion`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({})
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error iniciando producción');
    }
    
    const result = await response.json();
    return result.data; // Pedido en estado EN_PRODUCCION
}
```

**Cambios Clave:**
- Nuevo endpoint con negocio clara
- Transiciona estado CONFIRMADO → EN_PRODUCCION
- Valida que pedido esté listo

---

### 8. COMPLETAR PEDIDO

#### ANTES (Legacy - ❌ NO EXISTÍA):
```javascript
// NO HABÍA MÉTODO ESPECÍFICO
```

#### DESPUÉS (DDD - ✅ NUEVO):
```javascript
// POST /api/pedidos/{id}/completar
async function completarPedido(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}/completar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            'Authorization': `Bearer ${getAuthToken()}`
        },
        body: JSON.stringify({})
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error completando pedido');
    }
    
    const result = await response.json();
    return result.data; // Pedido en estado COMPLETADO
}
```

---

## 📋 Checklist de Migración

### Frontend JavaScript/AJAX:
- [ ] Actualizar llamadas POST /asesores/pedidos → POST /api/pedidos
- [ ] Actualizar llamadas POST /asesores/pedidos/confirm → PATCH /api/pedidos/{id}/confirmar
- [ ] Actualizar llamadas POST /asesores/pedidos/{id}/anular → DELETE /api/pedidos/{id}/cancelar
- [ ] Actualizar llamadas GET /asesores/pedidos/{id}/recibos-datos → GET /api/pedidos/{id}
- [ ] Agregar llamadas a nuevos endpoints (listar, actualizar-descripcion, iniciar-produccion, completar)
- [ ] Validar structure de respuestas (wrapped in `data` property)
- [ ] Agregar manejo de errores 410 Gone con mensajes claros

### Blade Templates:
- [ ] Si usan `action="/asesores/pedidos"` → cambiar a `action="/api/pedidos"`
- [ ] Actualizar JavaScript en templates
- [ ] Validar rutas de formularios

### API Clients (Postman, Insomnia, etc.):
- [ ] Crear collection con nuevos endpoints
- [ ] Documentar parámetros requeridos
- [ ] Incluir ejemplos de respuestas exitosas y errores

### Testing:
- [ ] Tests unitarios para cada función migrada
- [ ] Tests de integración con API
- [ ] Tests E2E de flujos completos
- [ ] Validar backward compatibility

---

## ⚠️ Manejo de Errores

### Respuestas de Error Estándar (DDD):

```javascript
// Error 400 - Validación
{
    "success": false,
    "message": "Validación fallida",
    "errors": {
        "cliente": ["El cliente es obligatorio"],
        "prendas": ["Debe incluir al menos una prenda"]
    }
}

// Error 404 - Recurso no encontrado
{
    "success": false,
    "message": "Pedido no encontrado"
}

// Error 410 - Legacy deprecado
{
    "success": false,
    "message": "Esta ruta está deprecada. Usa POST /api/pedidos en su lugar.",
    "nueva_ruta": "POST /api/pedidos"
}

// Error 422 - Estado inválido
{
    "success": false,
    "message": "No puedes cancelar un pedido ya completado"
}

// Error 500 - Servidor
{
    "success": false,
    "message": "Error interno del servidor"
}
```

### Manejo en JavaScript:

```javascript
async function llamarAPI(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            const error = await response.json();
            
            if (response.status === 410) {
                // Ruta deprecada - mostrar instrucción clara
                console.error(`Ruta deprecada: ${error.nueva_ruta}`);
                throw new Error(`Por favor usa: ${error.nueva_ruta}`);
            }
            
            if (response.status === 422) {
                // Error de negocio (estado inválido)
                throw new Error(error.message);
            }
            
            if (response.status === 400 && error.errors) {
                // Errores de validación
                const messages = Object.values(error.errors).flat().join(', ');
                throw new Error(messages);
            }
            
            throw new Error(error.message || 'Error desconocido');
        }
        
        return await response.json();
        
    } catch (error) {
        console.error('Error en API:', error);
        throw error;
    }
}
```

---

## 📍 Endpoints de Referencia Rápida

```
CREAR PEDIDO
POST /api/pedidos
→ 201 Created | 400 Validación | 500 Error

CONFIRMAR PEDIDO
PATCH /api/pedidos/{id}/confirmar
→ 200 OK | 404 No encontrado | 422 Estado inválido

CANCELAR PEDIDO
DELETE /api/pedidos/{id}/cancelar
→ 200 OK | 404 No encontrado | 422 Estado inválido

OBTENER PEDIDO
GET /api/pedidos/{id}
→ 200 OK | 404 No encontrado

LISTAR POR CLIENTE
GET /api/pedidos/cliente/{clienteId}
→ 200 OK | 404 Cliente no encontrado

ACTUALIZAR DESCRIPCIÓN
PATCH /api/pedidos/{id}/actualizar-descripcion
→ 200 OK | 404 No encontrado | 422 Estado inválido

INICIAR PRODUCCIÓN
POST /api/pedidos/{id}/iniciar-produccion
→ 200 OK | 404 No encontrado | 422 Estado inválido

COMPLETAR PEDIDO
POST /api/pedidos/{id}/completar
→ 200 OK | 404 No encontrado | 422 Estado inválido
```

---

## 🔗 Referencias

- Documento de API completo: `GUIA_API_PEDIDOS_DDD.md`
- Decision de endpoints: `GUIA_CUAL_ENDPOINT_USAR.md`
- Fase de consolidación: `FASE_CONSOLIDACION_PEDIDOS.md`

---

**Última actualización:** 2024
**Responsable:** Team Refactor DDD
