# Refactorización DDD: Gestión de Items en Pedidos

## 📐 Arquitectura Implementada

```
┌─────────────────────────────────────────────────────────────────┐
│                    PRESENTACIÓN (FRONTEND)                       │
├─────────────────────────────────────────────────────────────────┤
│ gestion-items-pedido-refactorizado.js                            │
│ - Recolecta datos del formulario                                │
│ - Llama APIs RESTful                                             │
│ - Renderiza UI                                                  │
│ - ✅ SIN lógica de negocio                                      │
└────────────┬────────────────────────────────────────────────────┘
             │ HTTP (JSON)
             ↓
┌─────────────────────────────────────────────────────────────────┐
│          INFRASTRUCTURE (HTTP Controllers)                       │
├─────────────────────────────────────────────────────────────────┤
│ app/Infrastructure/Http/Controllers/API/PedidoItemsController  │
│ POST   /api/pedidos/{pedidoId}/items         → agregarItem     │
│ DELETE /api/pedidos/{pedidoId}/items/{itemId} → eliminarItem   │
│ GET    /api/pedidos/{pedidoId}/items         → obtenerItems    │
│                                                                 │
│ Responsabilidad: Validar HTTP, invocar Use Cases               │
└────────────┬────────────────────────────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────────────────────────────┐
│           APPLICATION LAYER (Use Cases)                         │
├─────────────────────────────────────────────────────────────────┤
│ AgregarItemAlPedidoUseCase                                      │
│ - Valida entrada                                               │
│ - Ejecuta comando                                              │
│ - Retorna lista actualizada                                    │
│                                                                 │
│ EliminarItemDelPedidoUseCase                                   │
│ - Valida que item existe                                       │
│ - Ejecuta eliminación                                          │
│ - Retorna lista reordenada                                     │
└────────────┬────────────────────────────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────────────────────────────┐
│     DOMAIN LAYER (Lógica de Negocio Pura)                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│ COMMANDS:                                                      │
│ ├─ AgregarItemAlPedidoCommand                                  │
│ └─ EliminarItemDelPedidoCommand                                │
│                                                                 │
│ COMMAND HANDLERS (Orquestación):                               │
│ ├─ AgregarItemAlPedidoHandler                                  │
│ └─ EliminarItemDelPedidoHandler                                │
│                                                                 │
│ DOMAIN SERVICES (Lógica de Negocio):                           │
│ └─ GestorItemsPedidoDomainService                              │
│    ├─ agregarItemAlFinal()                                     │
│    ├─ eliminarItem()       ← Recalcula orden                  │
│    ├─ calcularProximaPosicion()                                │
│    ├─ validarOrden()                                           │
│    └─ obtenerItemsOrdenados()                                  │
│                                                                 │
│ VALUE OBJECTS (Tipos de Dominio):                              │
│ ├─ TipoItem                                                    │
│ │  ├─ PRENDA (constante)                                       │
│ │  └─ EPP (constante)                                          │
│ └─ OrdenItem                                                   │
│    ├─ valor(): int (1, 2, 3, ...)                              │
│    ├─ esPrimera(), esMenorQue(), esMayorQue()                 │
│    └─ incrementar(), decrementar()                             │
│                                                                 │
│ ENTITIES (Objetos con Identidad):                              │
│ └─ ItemPedido                                                  │
│    ├─ pedidoId()          → FK al Pedido                       │
│    ├─ referenciaId()      → ID de Prenda o EPP                 │
│    ├─ tipo(): TipoItem                                         │
│    ├─ orden(): OrdenItem                                       │
│    ├─ nombre()            → Para presentación                  │
│    └─ datosPresentacion() → JSON para frontend                 │
│                                                                 │
│ DOMAIN EVENTS (Notificaciones de cambios):                     │
│ ├─ ItemAgregadoAlPedido                                        │
│ └─ ItemEliminadoDelPedido                                      │
│                                                                 │
│ REPOSITORIES (Interfaces):                                     │
│ └─ ItemPedidoRepository                                        │
│    ├─ guardar(ItemPedido)                                      │
│    ├─ encontrarPorId(int)                                      │
│    ├─ obtenerPorPedido(int)                                    │
│    └─ eliminar(int)                                            │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
             │
             ↓
┌─────────────────────────────────────────────────────────────────┐
│         INFRASTRUCTURE LAYER (Persistencia)                     │
├─────────────────────────────────────────────────────────────────┤
│ EloquentItemPedidoRepository (implementación)                   │
│ - Traduce entre Eloquent Models ↔ Domain Entities              │
│ - Maneja la persistencia en BD                                 │
│                                                                 │
│ ItemPedido Model (Eloquent)                                    │
│ - Mapea a tabla: item_pedidos                                  │
│ - Relación: belongsTo(Pedido::class)                           │
│                                                                 │
│ Tabla: item_pedidos                                            │
│ ├─ id (PK)                                                     │
│ ├─ pedido_id (FK)                                              │
│ ├─ referencia_id (ID de Prenda/EPP)                            │
│ ├─ tipo ('prenda' | 'epp')                                     │
│ ├─ orden (1, 2, 3, ...)                                        │
│ ├─ nombre (para presentación)                                  │
│ ├─ descripcion (opcional)                                      │
│ ├─ datos_presentacion (JSON)                                   │
│ ├─ created_at, updated_at                                      │
│ └─ índices:                                                    │
│    - [pedido_id, orden]                                        │
│    - [tipo]                                                    │
│    - unique [pedido_id, referencia_id, tipo]                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 Responsabilidades por Capa

### Frontend (Presentación)
✅ **Recolectar** datos del formulario  
✅ **Llamar** APIs RESTful  
✅ **Renderizar** UI  
✅ **Notificar** usuario  
❌ NO gestionar orden  
❌ NO validar reglas de negocio  
❌ NO mantener estado sincronizado  

### Backend (Lógica de Negocio - DDD)
✅ **Validar** todas las reglas de negocio  
✅ **Gestionar** orden de items  
✅ **Recalcular** posiciones después de eliminar  
✅ **Persistir** datos  
✅ **Retornar** lista actualizada al frontend  

---

## 🔄 Flujo: Agregar Item a Pedido

```
1. USUARIO: Completa datos en el modal
   └─→ nombre_prenda, descripción, tallas seleccionadas

2. FRONTEND: Recolecta del formulario
   └─→ {tipo: 'prenda', referencia_id: 123, nombre: '...', ...}

3. FRONTEND: POST /api/pedidos/5/items
   └─→ Body: {tipo, referencia_id, nombre, descripcion, datos_presentacion}

4. INFRASTRUCTURE (Controller):
   └─→ Valida estructura HTTP
   └─→ Llama AgregarItemAlPedidoUseCase.ejecutar()

5. APPLICATION (UseCase):
   └─→ Valida entrada
   └─→ Crea: AgregarItemAlPedidoCommand
   └─→ Llama: AgregarItemAlPedidoHandler.ejecutar()

6. DOMAIN (CommandHandler):
   └─→ Obtiene items actuales del pedido
   └─→ Crea ItemPedido entity (con prenda o epp)
   └─→ Llama GestorItemsPedidoDomainService.agregarItemAlFinal()
   └─→ GestorItemsPedidoDomainService:
       - Calcula siguiente posición (orden)
       - Agrega item al collection
       - Valida invariantes
   └─→ Repository.guardar(item) → Persiste en BD
   └─→ Registra evento: ItemAgregadoAlPedido

7. APPLICATION (UseCase - retorno):
   └─→ Llama: Repository.obtenerPorPedidoOrdenados()
   └─→ Retorna: {success: true, item: {...}, items: [...]}

8. INFRASTRUCTURE (Controller - respuesta):
   └─→ return response()->json($resultado, 201)

9. FRONTEND: Recibe respuesta
   └─→ this.items = resultado.items (ya ordenados)
   └─→ Renderer.actualizar(this.items)
   └─→ Notificación: "Item agregado correctamente"
```

---

## 🗑️ Flujo: Eliminar Item

```
1. USUARIO: Hace clic en "Eliminar"

2. FRONTEND: Pide confirmación (SweetAlert)
   └─→ Usuario confirma

3. FRONTEND: DELETE /api/pedidos/5/items/12
   └─→ Parámetros: pedidoId=5, itemId=12

4. INFRASTRUCTURE (Controller):
   └─→ Llama: EliminarItemDelPedidoUseCase.ejecutar(itemId, pedidoId)

5. APPLICATION (UseCase):
   └─→ Valida que item existe
   └─→ Valida que pertenece al pedido
   └─→ Crea: EliminarItemDelPedidoCommand
   └─→ Llama: EliminarItemDelPedidoHandler.ejecutar()

6. DOMAIN (CommandHandler):
   └─→ Obtiene items del pedido
   └─→ Llama: GestorItemsPedidoDomainService.eliminarItem()
   └─→ GestorItemsPedidoDomainService:
       - Busca y elimina item
       - RECALCULA orden: {1, 2, 3, ...}
       - Retorna collection actualizado
   └─→ Para cada item: Repository.guardar(item) → Actualiza orden en BD
   └─→ Repository.eliminar(itemId) → Borra de BD
   └─→ Registra evento: ItemEliminadoDelPedido

7. APPLICATION (UseCase - retorno):
   └─→ Llama: Repository.obtenerPorPedidoOrdenados()
   └─→ Retorna: {success: true, items: [...], relacionados_eliminados: {...}}

8. INFRASTRUCTURE (Controller - respuesta):
   └─→ return response()->json($resultado, 200)

9. FRONTEND: Recibe respuesta
   └─→ this.items = resultado.items (ya reordenados)
   └─→ Renderer.actualizar(this.items)
   └─→ Notificación: "Item eliminado"
```

---

## 📝 Ejemplos de Uso

### Agregar Item (Frontend)

```javascript
const itemData = {
    tipo: 'prenda',
    referencia_id: 123,
    nombre: 'Camisa Azul',
    descripcion: 'Talla XXL',
    datos_presentacion: {
        tallas: { dama: ['S', 'M'], caballero: [] },
        // ... otros datos
    }
};

const resultado = await this.agregarItem(itemData);
// Frontend ahora tiene: this.items actualizado y ordenado
```

### Eliminar Item (Frontend)

```javascript
const itemId = this.items[0].id;
const pedidoId = 5;

const resultado = await this.apiService.eliminarItem(itemId, pedidoId);
// Respuesta: {success: true, items: [...], relacionados_eliminados: {...}}
// Frontend: this.items actualizado con orden recalculado (1, 2, 3)
```

### Backend retorna Items Ordenados

```json
{
  "success": true,
  "items": [
    {
      "id": 1,
      "tipo": "prenda",
      "nombre": "Camisa Azul",
      "descripcion": "...",
      "orden": 1,
      "referencia_id": 123,
      "datos_presentacion": { ... }
    },
    {
      "id": 2,
      "tipo": "epp",
      "nombre": "Guantes",
      "descripcion": null,
      "orden": 2,
      "referencia_id": 456,
      "datos_presentacion": { ... }
    }
  ]
}
```

---

## 🛣️ Rutas API

```
POST /api/pedidos/{pedidoId}/items
  - Agregar item (Prenda o EPP)
  - Body: {tipo, referencia_id, nombre, descripcion?, datos_presentacion?}
  - Response: {success, item, items, message}

DELETE /api/pedidos/{pedidoId}/items/{itemId}
  - Eliminar item y reorden
  - Response: {success, items, message, relacionados_eliminados}

GET /api/pedidos/{pedidoId}/items
  - Obtener items del pedido
  - Response: {success, items, total}
```

---

## 🧪 Testing

### Backend (PHP/Laravel)
```php
public function test_agregar_item_calcula_orden_correctamente()
{
    // Item 1: orden = 1
    // Item 2: orden = 2
    // Item 3: orden = 3
    // ✅ Orden secuencial garantizado
}

public function test_eliminar_item_recalcula_orden()
{
    // Items: [1(orden=1), 2(orden=2), 3(orden=3)]
    // Eliminar 2
    // Items: [1(orden=1), 3(orden=2)]
    // ✅ Orden continuada sin gaps
}

public function test_backend_valida_tallas()
{
    // Agregar prenda sin tallas
    // ✅ Retorna: {success: false, validation_errors: [...]}
}
```

### Frontend (JavaScript)
```javascript
it('should display items from backend in order', () => {
  const items = [{orden: 1, ...}, {orden: 2, ...}];
  gestionItemsUI.items = items;
  renderer.actualizar(items);
  
  const rendered = document.querySelectorAll('.item');
  expect(rendered.length).toBe(2);
  expect(rendered[0].dataset.orden).toBe('1');
  expect(rendered[1].dataset.orden).toBe('2');
});
```

---

## ✅ Beneficios de esta Arquitectura

1. **Separación de responsabilidades**: Frontend No tiene lógica de negocio
2. **Mantenibilidad**: Cambios en reglas va en un lugar (backend)
3. **Seguridad**: No se puede bypassear validaciones desde cliente
4. **Consistencia**: Backend es source of truth
5. **Escalabilidad**: Múltiples clientes (web, mobile) pueden usar mismo backend
6. **Testabilidad**: Cada capa es independiente y testeable
7. **Sincronización**: No hay desfases entre cliente y servidor
8. **Orden garantizado**: Recalc automático sin duplicados o gaps

---

## 📦 Archivos Creados/Modificados

### Domain Layer
- ✅ `app/Domain/Pedidos/ValueObjects/TipoItem.php`
- ✅ `app/Domain/Pedidos/ValueObjects/OrdenItem.php`
- ✅ `app/Domain/Pedidos/Entities/ItemPedido.php`
- ✅ `app/Domain/Pedidos/DomainServices/GestorItemsPedidoDomainService.php`
- ✅ `app/Domain/Pedidos/Commands/AgregarItemAlPedidoCommand.php`
- ✅ `app/Domain/Pedidos/Commands/EliminarItemDelPedidoCommand.php`
- ✅ `app/Domain/Pedidos/CommandHandlers/AgregarItemAlPedidoHandler.php`
- ✅ `app/Domain/Pedidos/CommandHandlers/EliminarItemDelPedidoHandler.php`
- ✅ `app/Domain/Pedidos/Events/ItemAgregadoAlPedido.php`
- ✅ `app/Domain/Pedidos/Events/ItemEliminadoDelPedido.php`
- ✅ `app/Domain/Pedidos/Repositories/ItemPedidoRepository.php` (Interface)

### Application Layer
- ✅ `app/Application/Pedidos/UseCases/AgregarItemAlPedidoUseCase.php`
- ✅ `app/Application/Pedidos/UseCases/EliminarItemDelPedidoUseCase.php`

### Infrastructure Layer
- ✅ `app/Infrastructure/Http/Controllers/API/PedidoItemsController.php`
- ✅ `app/Repositories/EloquentItemPedidoRepository.php`
- ✅ `app/Models/ItemPedido.php`
- ✅ `database/migrations/2026_02_07_000000_create_item_pedidos_table.php`

### Configuration
- ✅ `app/Providers/PedidosServiceProvider.php` (actualizado)
- ✅ `routes/api.php` (actualizado)

### Frontend
- ✅ `public/js/modulos/crear-pedido/procesos/gestion-items-pedido-refactorizado.js`

---

## 🚀 Próximos Pasos

1. Ejecutar migración: `php artisan migrate`
2. Registrar bindings en Service Provider (✅ Hecho)
3. Testear APIs con Postman/Insomnia
4. Reemplazar archivo viejo del frontend por el refactorizado
5. Actualizar ItemAPIService.js para usar nuevas rutas
6. Implementar obtenerItems() en el UseCase
7. Agregar logging y error handling más específico
8. Implementar eventos listeners para cascadas
