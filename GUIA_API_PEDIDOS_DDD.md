# GUÍA RÁPIDA - API DE PEDIDOS (DDD)

## 📍 Base URL
```
/api/pedidos
```

---

## 📝 ENDPOINTS DISPONIBLES

### 1️⃣ CREAR PEDIDO
```
POST /api/pedidos
```

**Request:**
```json
{
  "cliente_id": 1,
  "descripcion": "Pedido de camisetas personalizadas",
  "observaciones": "Entregar en bodega de Bogotá",
  "prendas": [
    {
      "prenda_id": 5,
      "descripcion": "Camiseta ejecutiva",
      "cantidad": 100,
      "tallas": {
        "DAMA": {
          "S": 25,
          "M": 35,
          "L": 40
        }
      }
    }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Pedido creado exitosamente",
  "data": {
    "id": 42,
    "numero": "PED-20260122120530-4567",
    "cliente_id": 1,
    "estado": "PENDIENTE",
    "descripcion": "Pedido de camisetas personalizadas",
    "total_prendas": 1,
    "total_articulos": 100,
    "mensaje": "Pedido creado exitosamente"
  }
}
```

---

### 2️⃣ OBTENER PEDIDO
```
GET /api/pedidos/{id}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "numero": "PED-20260122120530-4567",
    "cliente_id": 1,
    "estado": "PENDIENTE",
    "descripcion": "Pedido de camisetas personalizadas",
    "observaciones": "Entregar en bodega de Bogotá",
    "total_prendas": 1,
    "total_articulos": 100,
    "mensaje": "Pedido obtenido exitosamente"
  }
}
```

---

### 3️⃣ CONFIRMAR PEDIDO
```
PATCH /api/pedidos/{id}/confirmar
```

**Response (200):**
```json
{
  "success": true,
  "message": "Pedido confirmado exitosamente",
  "data": {
    "id": 42,
    "numero": "PED-20260122120530-4567",
    "cliente_id": 1,
    "estado": "CONFIRMADO",
    "descripcion": "Pedido de camisetas personalizadas",
    "total_prendas": 1,
    "total_articulos": 100,
    "mensaje": "Pedido confirmado exitosamente"
  }
}
```

**Estados permitidos para confirmar:**
- De: `PENDIENTE` → A: `CONFIRMADO` ✅
- De: `CONFIRMADO` → Error ❌
- De: `EN_PRODUCCION` → Error ❌
- De: `COMPLETADO` → Error ❌
- De: `CANCELADO` → Error ❌

---

### 4️⃣ CANCELAR PEDIDO
```
DELETE /api/pedidos/{id}/cancelar
```

**Response (200):**
```json
{
  "success": true,
  "message": "Pedido cancelado exitosamente",
  "data": {
    "id": 42,
    "numero": "PED-20260122120530-4567",
    "cliente_id": 1,
    "estado": "CANCELADO",
    "descripcion": "Pedido de camisetas personalizadas",
    "total_prendas": 1,
    "total_articulos": 100,
    "mensaje": "Pedido cancelado exitosamente"
  }
}
```

**Estados permitidos para cancelar:**
- De: `PENDIENTE` → A: `CANCELADO` ✅
- De: `CONFIRMADO` → A: `CANCELADO` ✅
- De: `EN_PRODUCCION` → Error ❌
- De: `COMPLETADO` → Error ❌
- De: `CANCELADO` → Error ❌

---

### 5️⃣ LISTAR PEDIDOS DEL CLIENTE
```
GET /api/pedidos/cliente/{clienteId}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "numero": "PED-20260122120530-4567",
      "cliente_id": 1,
      "estado": "CONFIRMADO",
      "descripcion": "Pedido de camisetas personalizadas",
      "total_prendas": 1,
      "total_articulos": 100,
      "mensaje": "Pedidos obtenidos exitosamente"
    },
    {
      "id": 43,
      "numero": "PED-20260122143022-1234",
      "cliente_id": 1,
      "estado": "PENDIENTE",
      "descripcion": "Pedido de pantalones",
      "total_prendas": 2,
      "total_articulos": 50,
      "mensaje": "Pedidos obtenidos exitosamente"
    }
  ]
}
```

---

## 🔄 TRANSICIONES DE ESTADO

```
PENDIENTE
  ├─ → CONFIRMADO (confirmar) ✅
  └─ → CANCELADO (cancelar) ✅

CONFIRMADO
  ├─ → EN_PRODUCCION (iniciar-produccion) [TBD]
  └─ → CANCELADO (cancelar) ✅

EN_PRODUCCION
  ├─ → COMPLETADO (completar) [TBD]
  └─ ❌ No se puede cancelar

COMPLETADO
  └─ ❌ Estado final, sin transiciones

CANCELADO
  └─ ❌ Estado final, sin transiciones
```

---

## 🛠️ MANEJO DE ERRORES

### Error de Validación (422)
```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "cliente_id": ["El campo cliente_id es requerido"],
    "prendas": ["El campo prendas debe tener al menos 1 elemento"]
  }
}
```

### Pedido No Encontrado (404)
```json
{
  "success": false,
  "message": "Pedido 999 no encontrado"
}
```

### Error de Negocio (422)
```json
{
  "success": false,
  "message": "No se puede confirmar un pedido en estado final"
}
```

### Error Interno (500)
```json
{
  "success": false,
  "message": "Error al crear pedido: [detalle del error]"
}
```

---

## 📊 FLUJO TÍPICO DE UN PEDIDO

```
1. Cliente hace un pedido
   POST /api/pedidos
   ↓
   Estado: PENDIENTE

2. Asesor revisa y confirma
   PATCH /api/pedidos/{id}/confirmar
   ↓
   Estado: CONFIRMADO

3. Producción inicia fabricación
   PATCH /api/pedidos/{id}/iniciar-produccion [TBD]
   ↓
   Estado: EN_PRODUCCION

4. Producción termina
   PATCH /api/pedidos/{id}/completar [TBD]
   ↓
   Estado: COMPLETADO

O en cualquier momento:
   DELETE /api/pedidos/{id}/cancelar
   ↓
   Estado: CANCELADO
```

---

## 🧪 TESTING CON CURL

```bash
# Crear pedido
curl -X POST http://localhost:8000/api/pedidos \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 1,
    "descripcion": "Test pedido",
    "prendas": [
      {
        "prenda_id": 1,
        "descripcion": "Camiseta",
        "cantidad": 10,
        "tallas": {"DAMA": {"S": 5, "M": 5}}
      }
    ]
  }'

# Obtener pedido
curl -X GET http://localhost:8000/api/pedidos/42

# Confirmar pedido
curl -X PATCH http://localhost:8000/api/pedidos/42/confirmar

# Cancelar pedido
curl -X DELETE http://localhost:8000/api/pedidos/42/cancelar

# Listar pedidos del cliente
curl -X GET http://localhost:8000/api/pedidos/cliente/1
```

---

## 📚 USE CASES DISPONIBLES

| Use Case | Responsabilidad |
|----------|-----------------|
| `CrearPedidoUseCase` | Crear nuevo pedido validado |
| `ObtenerPedidoUseCase` | Obtener pedido por ID (Query) |
| `ListarPedidosPorClienteUseCase` | Listar pedidos del cliente (Query) |
| `ConfirmarPedidoUseCase` | Cambiar estado a CONFIRMADO |
| `CancelarPedidoUseCase` | Cambiar estado a CANCELADO |
| `ActualizarDescripcionPedidoUseCase` | Actualizar descripción [No expuesto en API aún] |
| `IniciarProduccionPedidoUseCase` | Cambiar estado a EN_PRODUCCION [No expuesto en API aún] |
| `CompletarPedidoUseCase` | Cambiar estado a COMPLETADO [No expuesto en API aún] |

---

## 📌 NOTAS IMPORTANTES

1. **Número de pedido**: Auto-generado con formato `PED-YYYYMMDDHHmmss-XXXX`
2. **Tallas**: Deben sumar exactamente la cantidad especificada
3. **Estados inmutables**: Una vez COMPLETADO o CANCELADO, no se puede cambiar
4. **Transacciones**: Todas las operaciones de escritura están en transacciones
5. **Validación**: El dominio valida toda la lógica de negocio

---

## 🔐 Autenticación

Actualmente sin implementar. Agregar middleware si es necesario:
```php
Route::middleware(['auth:api'])->group(function () {
    // Rutas protegidas
});
```
