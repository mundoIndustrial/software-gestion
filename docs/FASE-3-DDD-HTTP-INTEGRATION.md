# DDD Implementation - Fase 3: HTTP Layer Integration

## 📊 Arquitectura DDD Completa

```
┌─────────────────────────────────────────────────────────────────┐
│ HTTP REQUEST                                                    │
│ POST /api/v1/ordenes                                            │
└──────────────────────────┬──────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ HTTP LAYER                                                      │
│ RegistroOrdenDDDController                                      │
│ - Recibe Request                                                │
│ - Valida con Form Requests                                      │
│ - Delega a Application Services                                 │
│ - Retorna JSON Response                                         │
└──────────────────────────┬──────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ APPLICATION LAYER                                               │
│ CrearOrdenService                                               │
│ - Orquesta la creación                                          │
│ - Valida reglas de negocio                                      │
│ - Instancia Domain Model                                        │
└──────────────────────────┬──────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ DOMAIN LAYER                                                    │
│ Orden Aggregate (Entidad Raíz)                                  │
│ - Máquina de estados                                            │
│ - Validación de invariantes                                     │
│ - Emite Domain Events                                           │
│ ├─ Prendas (Child Entities)                                     │
│ ├─ Value Objects (NumeroOrden, EstadoOrden, etc.)               │
│ └─ Comportamiento de negocio                                    │
└──────────────────────────┬──────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ PERSISTENCE LAYER                                               │
│ EloquentOrdenRepository                                         │
│ - Implementa OrdenRepositoryInterface                           │
│ - Traduce Domain Model → Eloquent Models                        │
│ - Maneja transacciones                                          │
└──────────────────────────┬──────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────────┐
│ DATABASE LAYER                                                  │
│ - tabla_original                                                │
│ - prendas_pedido                                                │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Ejemplos de Uso de la API

### 1. Crear Orden

```bash
POST /api/v1/ordenes
Content-Type: application/json

{
  "numero": 12345,
  "cliente": "Cliente ABC",
  "forma_pago": "Crédito 30 días",
  "area": "Producción",
  "prendas": [
    {
      "nombre_prenda": "POLO HOMBRE",
      "cantidad_total": 100,
      "cantidad_talla": {
        "XS": 10,
        "S": 25,
        "M": 35,
        "L": 20,
        "XL": 10
      },
      "descripcion": "Polo básico blanco",
      "color_id": 1,
      "tela_id": 2
    },
    {
      "nombre_prenda": "PANTALÓN HOMBRE",
      "cantidad_total": 50,
      "cantidad_talla": {
        "28": 15,
        "30": 20,
        "32": 15
      },
      "descripcion": "Pantalón de drill"
    }
  ]
}
```

**Response 201:**
```json
{
  "success": true,
  "message": "Orden 12345 creada exitosamente",
  "data": {
    "numero_pedido": 12345
  }
}
```

### 2. Obtener Orden

```bash
GET /api/v1/ordenes/12345
```

**Response 200:**
```json
{
  "success": true,
  "data": {
    "numero_pedido": 12345,
    "cliente": "Cliente ABC",
    "estado": "Borrador",
    "forma_pago": "Crédito 30 días",
    "area": "Producción",
    "fecha_creacion": "2025-12-06T10:30:00Z",
    "fecha_ultima_modificacion": "2025-12-06T10:30:00Z",
    "total_cantidad": 150,
    "total_entregado": 0,
    "total_pendiente": 150,
    "porcentaje_completado": 0,
    "prendas": [
      {
        "nombre": "POLO HOMBRE",
        "cantidad_total": 100,
        "cantidad_entregada": 0,
        "cantidad_pendiente": 100,
        "porcentaje_entrega": 0,
        "descripcion": "Polo básico blanco",
        "tallas": {
          "XS": 10,
          "S": 25,
          "M": 35,
          "L": 20,
          "XL": 10
        }
      }
    ]
  }
}
```

### 3. Aprobar Orden

```bash
PATCH /api/v1/ordenes/12345/aprobar
```

**Response 200:**
```json
{
  "success": true,
  "message": "Orden 12345 aprobada"
}
```

### 4. Iniciar Producción

```bash
PATCH /api/v1/ordenes/12345/iniciar-produccion
```

**Response 200:**
```json
{
  "success": true,
  "message": "Orden 12345 en producción"
}
```

### 5. Completar Orden

```bash
PATCH /api/v1/ordenes/12345/completar
```

**Response 200:**
```json
{
  "success": true,
  "message": "Orden 12345 completada"
}
```

**Nota:** Retorna 422 si no todas las prendas están entregadas.

### 6. Cancelar Orden

```bash
DELETE /api/v1/ordenes/12345
```

**Response 200:**
```json
{
  "success": true,
  "message": "Orden 12345 cancelada"
}
```

### 7. Listar Órdenes

```bash
GET /api/v1/ordenes
```

**Response 200:**
```json
{
  "success": true,
  "data": [
    { /* orden 1 */ },
    { /* orden 2 */ }
  ],
  "count": 2
}
```

### 8. Listar por Cliente

```bash
GET /api/v1/ordenes/cliente/Cliente%20ABC
```

### 9. Listar por Estado

```bash
GET /api/v1/ordenes/estado/EnProduccion
```

---

## ✅ Características Implementadas

### HTTP Layer (`RegistroOrdenDDDController`)

✅ `index()` - Listar todas las órdenes
✅ `show(int $numero)` - Obtener orden específica
✅ `porCliente(string $cliente)` - Filtrar por cliente
✅ `porEstado(string $estado)` - Filtrar por estado
✅ `store(CrearOrdenRequest)` - Crear orden
✅ `aprobar(int $numero)` - Transición: Borrador → Aprobada
✅ `iniciarProduccion(int $numero)` - Transición: Aprobada → EnProduccion
✅ `completar(int $numero)` - Transición: EnProduccion → Completada
✅ `destroy(int $numero)` - Cancelar orden

### Form Validation

✅ `CrearOrdenRequest` - Valida datos de creación
✅ `ActualizarOrdenRequest` - Valida datos de actualización

### Resources

✅ `OrdenResource` - Serializa Orden a JSON
✅ `PrendaResource` - Serializa Prenda a JSON

### API Routes

✅ `GET /api/v1/ordenes`
✅ `GET /api/v1/ordenes/{numero}`
✅ `GET /api/v1/ordenes/cliente/{cliente}`
✅ `GET /api/v1/ordenes/estado/{estado}`
✅ `POST /api/v1/ordenes`
✅ `PATCH /api/v1/ordenes/{numero}/aprobar`
✅ `PATCH /api/v1/ordenes/{numero}/iniciar-produccion`
✅ `PATCH /api/v1/ordenes/{numero}/completar`
✅ `DELETE /api/v1/ordenes/{numero}`

---

## 🔄 State Machine (Máquina de Estados)

```
┌─────────────────────────────────────────────────────────────────┐
│                    ORDEN STATE MACHINE                          │
└─────────────────────────────────────────────────────────────────┘

                      ┌───────────────────┐
                      │     BORRADOR      │  ← Estado inicial
                      └────────┬──────────┘
                               │
                        (invocar: aprobar())
                               │
                               ↓
                      ┌───────────────────┐
        ┌────────────→│    APROBADA       │
        │             └────────┬──────────┘
        │                      │
        │             (iniciarProduccion())
        │                      │
        │                      ↓
        │             ┌───────────────────┐
        │             │   ENPRODUCCION    │
        │             └────────┬──────────┘
        │                      │
        │            (completar() si 100% entregado)
        │                      │
        │                      ↓
        │             ┌───────────────────┐
        │             │   COMPLETADA      │
        │             └───────────────────┘
        │
        │  (cancelar() desde cualquier estado excepto Completada/Cancelada)
        │
        └───────────→ ┌───────────────────┐
                      │    CANCELADA      │
                      └───────────────────┘
```

---

## 🎯 Beneficios de esta Arquitectura

### 1. **Separación de Responsabilidades**
- Controller: Solo HTTP, sin lógica
- Application Services: Orquestación
- Domain Model: Toda la lógica de negocio
- Repository: Abstracción de persistencia

### 2. **Testeable**
- Domain Model: Testeable en forma aislada (sin BD)
- Application Services: Testeable con mocks
- Repository: Testeable con BD de prueba

### 3. **Mantenible**
- Cambios en BD: Solo afectan Repository
- Cambios en negocio: Solo afectan Domain Model
- Cambios en API: Solo afectan Controller

### 4. **Escalable**
- Fácil agregar nuevos casos de uso (nuevos Application Services)
- Fácil agregar nuevos Bounded Contexts
- Fácil cambiar implementación de Repository

### 5. **Type-Safe**
- Value Objects garantizan validez
- Máquina de estados previene estados inválidos
- Domain Exceptions lanzan errores de negocio

---

## 📚 Archivo de Configuración

El DomainServiceProvider está registrado en `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\EventServiceProvider::class,
    App\Providers\BladeDirectivesServiceProvider::class,
    App\Providers\DomainServiceProvider::class,  // ← DDD
];
```

Esto permite que las dependencias se inyecten automáticamente en los controllers.

---

## 🚀 Próximas Fases

**FASE 4:** Event Listeners para Domain Events
- Cuando `OrdenCreada` → Enviar email
- Cuando `OrdenActualizada` → Actualizar estadísticas
- Cuando `PrendaAgregada` → Verificar stock

**FASE 5:** Tests Unitarios
- Tests para Domain Model
- Tests para Application Services
- Tests para Repository

**FASE 6:** Async Processing
- Queue jobs para procesar eventos
- Event Sourcing opcional
