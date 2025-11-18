# 📋 Sistema de Órdenes con Borradores - Documentación Completa

## 🎯 Objetivo General

Implementar un sistema de órdenes que permite:
- ✅ **Guardar como borrador** sin asignar número de pedido
- ✅ **Editar múltiples veces** antes de confirmar
- ✅ **Confirmar y crear** la orden definitiva (asignando número consecutivo)
- ✅ **Soporte para concurrencia** con múltiples usuarios simultáneamente

## 🏗️ Arquitectura del Sistema

### Flujo Completo

```
1. CREAR BORRADOR
   ├─ Usuario ingresa datos básicos
   ├─ Agrega productos (opcional)
   └─ Hace clic en "Guardar Borrador"
      └─ Se crea orden con:
         ├─ es_borrador = true
         ├─ estado_pedido = 'borrador'
         ├─ pedido = NULL (sin número oficial)
         └─ ID = BORRADOR-{id}

2. EDITAR BORRADOR
   ├─ Usuario puede editar datos
   ├─ Agregar/eliminar productos
   └─ Guardar múltiples veces
      └─ Solo se actualiza la orden existente

3. CONFIRMAR ORDEN
   ├─ Usuario hace clic en "Confirmar y Crear Orden"
   ├─ Se valida:
   │  ├─ Tenga al menos 1 producto
   │  └─ Tenga cliente asignado
   └─ Se asigna número consecutivo
      ├─ pedido = siguiente número
      ├─ es_borrador = false
      ├─ estado_pedido = 'confirmado'
      ├─ fecha_confirmacion = now()
      └─ ID = PEDIDO-{numero}
```

## 🗄️ Base de Datos

### Tabla: ordenes_asesores

```sql
- id (PK)
- numero_orden (String, Unique) -- ID temporal: TEMP-{uniqid}
- pedido (Integer, Nullable) -- NULL si es borrador, número si confirmado
- asesor_id (FK) -- Relación con usuarios
- cliente (String)
- telefono (String, Nullable)
- email (String, Nullable)
- descripcion (Text, Nullable)
- monto_total (Decimal)
- cantidad_prendas (Integer)
- estado (Enum) -- pendiente, en_proceso, completada, cancelada
- estado_pedido (Enum) -- borrador, confirmado, en_proceso, completado, cancelado
- es_borrador (Boolean) -- TRUE si es borrador
- fecha_confirmacion (Timestamp, Nullable)
- prioridad (Enum) -- baja, media, alta, urgente
- fecha_entrega (Date, Nullable)
- created_at, updated_at (Timestamps)
```

### Relaciones

```
ordenes_asesores
├── productos_pedido (hasMany) -- Productos de cada orden
└── usuarios (belongsTo) -- Asesor que creó la orden
```

## 💻 Implementación - Modelos

### OrdenAsesor.php

**Métodos principales:**

1. **esBorrador()** - Verifica si es borrador
2. **getIdentificadorAttribute** - Retorna "BORRADOR-id" o "PEDIDO-numero"
3. **confirmar()** - Asigna número y confirma (usa transacción)
4. **cancelar()** - Elimina orden y productos

**Scopes útiles:**

```php
OrdenAsesor::borradores() // Solo borradores
OrdenAsesor::confirmados() // Solo confirmadas
OrdenAsesor::delAsesor($id) // Del asesor específico
OrdenAsesor::delDia() // Del día actual
OrdenAsesor::delMes() // Del mes actual
```

## 🎮 Implementación - Controlador

### OrdenController.php

**Métodos:**

1. **index()** - Lista órdenes (borradores + confirmadas)
2. **create()** - Muestra formulario
3. **guardarBorrador()** - Crea/actualiza borrador (AJAX)
4. **edit()** - Edita borrador existente
5. **update()** - Actualiza borrador
6. **confirmar()** - Confirma orden y asigna número
7. **show()** - Muestra orden confirmada
8. **destroy()** - Elimina borrador
9. **stats()** - Retorna estadísticas del asesor

## 🎨 Frontend - Vistas

### 1. create.blade.php - Formulario de Creación

**Características:**
- Formulario dinámico con agregar/eliminar productos
- Dos botones de acción:
  - "Guardar Borrador" → Endpoint: `/asesores/ordenes/guardar-borrador` (AJAX)
  - "Confirmar y Crear Orden" → Endpoint: `/asesores/ordenes/confirmar-orden` (AJAX)

**Productos dinámicos:**
```javascript
function agregarProducto() {
    // Crea nuevo campo de producto
}

function eliminarProducto(button) {
    // Elimina campo de producto
}
```

### 2. edit.blade.php - Editar Borrador

- Permite editar todos los campos del borrador
- Agregar/eliminar productos
- Botones:
  - "Guardar Cambios"
  - "Confirmar Orden"
  - "Eliminar Borrador"

### 3. index.blade.php - Lista de Órdenes

**Secciones:**
- Estadísticas (Borradores, Confirmadas Hoy, Mes, Monto Total)
- Filtros (Estado: Todos/Borradores/Confirmados, Búsqueda por cliente)
- Grid de órdenes

**Tarjetas de orden:**
```
┌─ Status Badge (BORRADOR/CONFIRMADA)
├─ Identificador (BORRADOR-123 o PEDIDO-456)
├─ Cliente
├─ Prendas, Monto, Fecha Entrega
├─ Productos (primeros 2)
└─ Acciones
   ├─ Si es borrador: Continuar, Confirmar, Eliminar
   └─ Si es confirmada: Ver Detalles, Descargar PDF
```

### 4. show.blade.php - Ver Orden Confirmada

- Muestra detalles completos
- Lista de productos con detalles
- Opciones: Editar, Descargar, Imprimir, etc.

## 🔄 Flujo de Órdenes

### Crear Borrador

```
Usuario rellena formulario
         ↓
Agrega productos (dinámico)
         ↓
Click "Guardar Borrador"
         ↓
AJAX POST /asesores/ordenes/guardar-borrador
         ↓
OrdenController::guardarBorrador()
         ↓
[Transacción]
├─ Crear OrdenAsesor (es_borrador=true, pedido=NULL)
├─ Crear ProductoPedido para cada producto
└─ Retornar JSON
         ↓
JS muestra: "Borrador guardado: BORRADOR-123"
         ↓
Usuario puede: continuar editando u otra acción
```

### Confirmar Orden

```
Usuario hace click "Confirmar y Crear Orden"
         ↓
AJAX POST /asesores/ordenes/confirmar-orden
         ↓
Validaciones:
├─ ¿Es borrador?
├─ ¿Tiene productos?
└─ ¿Tiene cliente?
         ↓
Transacción con Lock (evita race conditions):
├─ Lock en ordenes_asesores
├─ Obtener último número pedido
├─ Calcular siguiente: siguiente = último + 1
├─ Update orden:
│  ├─ pedido = siguiente
│  ├─ es_borrador = false
│  ├─ estado_pedido = 'confirmado'
│  └─ fecha_confirmacion = now()
└─ Retornar JSON
         ↓
JS muestra: "Orden confirmada: PEDIDO-789"
         ↓
Redirigir a: /asesores/ordenes/{id}
```

## 🔒 Manejo de Concurrencia

### Problema

Si múltiples asesores confirman órdenes simultáneamente, pueden obtener el mismo número de pedido.

### Solución - Lock Pessimista

```php
// En modelo: OrdenAsesor::confirmar()

DB::transaction(function () {
    // 1. Lock en lectura/actualización
    $orden = OrdenAsesor::lockForUpdate()->find($this->id);
    
    // 2. Lock al obtener máximo
    $ultimoPedido = DB::table('ordenes_asesores')
        ->lockForUpdate()
        ->whereNotNull('pedido')
        ->max('pedido');
    
    // 3. Calcular siguiente número (seguro)
    $siguiente = $ultimoPedido ? $ultimoPedido + 1 : 1;
    
    // 4. Actualizar (transacción garantizada)
    $orden->update(['pedido' => $siguiente, ...]);
}, attempts: 3); // Reintentar 3 veces si hay deadlock
```

### Job en Cola (Opcional - para mayor confiabilidad)

```php
// Si deseas procesar en background:
ConfirmarOrdenJob::dispatch($ordenId);

// El Job maneja:
- Lock pessimista
- Reintentos automáticos
- Logging detallado
- Manejo de errores
```

## 📊 Rutas

```php
// Listar órdenes
GET /asesores/ordenes
    - query params: estado=(borradores|confirmados), cliente=...

// Crear (mostrar formulario)
GET /asesores/ordenes/create

// Guardar como borrador (AJAX)
POST /asesores/ordenes/guardar-borrador
    - body: JSON con datos de la orden

// Confirmar y crear orden (AJAX)
POST /asesores/ordenes/confirmar-orden
    - body: JSON con datos de la orden

// Editar borrador
GET /asesores/ordenes/{id}/edit
    - Redirige a 404 si no es borrador

// Actualizar borrador
PATCH /asesores/ordenes/{id}
    - body: JSON con cambios

// Confirmar orden
POST /asesores/ordenes/{id}/confirmar
    - Retorna JSON

// Ver orden confirmada
GET /asesores/ordenes/{id}

// Eliminar borrador
DELETE /asesores/ordenes/{id}
    - Solo borradores

// Estadísticas
GET /asesores/ordenes/stats
    - Retorna: borradores, confirmados_hoy, mes, total_mes
```

## 🎯 Validaciones

### Al crear/editar borrador

- ✓ Cliente (requerido)
- ✓ Email (opcional, validar si se proporciona)
- ✓ Teléfono (opcional)
- ✓ Productos (dinámico, validar cada uno)
- ✓ Cantidad productos (min 1)
- ✓ Precio unitario (opcional, validar si se proporciona)
- ✓ Fecha entrega (opcional, debe ser >= hoy)

### Al confirmar orden

- ✓ Debe ser borrador (es_borrador = true)
- ✓ Debe tener al menos 1 producto
- ✓ Debe tener cliente asignado
- ✓ No puede haber confirmado antes

## 📈 Estadísticas

```php
// Stats disponibles
$stats = [
    'borradores' => cantidad,           // Total borradores
    'confirmados_hoy' => cantidad,      // Confirmadas hoy
    'confirmados_mes' => cantidad,      // Confirmadas este mes
    'total_mes' => decimal              // Monto total mes
];
```

## 🔄 Ciclo de Vida de una Orden

```
CREACIÓN (create.blade.php)
        ↓
BORRADOR (es_borrador=true, pedido=NULL)
        ├─ ✏️ Editable (edit.blade.php)
        │  └─ AJAX PATCH /ordenes/{id}
        ├─ 💾 Guardable múltiples veces
        │  └─ AJAX POST guardar-borrador
        ├─ 🗑️ Eliminable
        │  └─ DELETE /ordenes/{id}
        └─ ✅ Confirmable
           └─ POST /ordenes/{id}/confirmar
           
        ↓
CONFIRMADO (es_borrador=false, pedido=123)
        ├─ 👁️ Visualizable (show.blade.php)
        ├─ 📥 NO editable como borrador
        ├─ 🗑️ NO eliminable
        ├─ 📄 Descargable (PDF)
        └─ 📊 En proceso de producción
```

## 💡 Características Clave

### 1. Sin Migración Nueva
- ✅ Usa la migración existente de `ordenes_asesores`
- ✅ Campos ya están en la tabla

### 2. Numeración Consecutiva
- ✅ Solo números en confirmados
- ✅ Sin huecos en la secuencia
- ✅ Asignación segura con locks

### 3. Soporte Multi-usuario
- ✅ Lock pessimista para evitar race conditions
- ✅ Transacciones con reintentos
- ✅ Logging de operaciones

### 4. Experiencia de Usuario
- ✅ Guardar sin confirmar
- ✅ Editar múltiples veces
- ✅ Confirmación clara y visible
- ✅ Identificadores claros (BORRADOR vs PEDIDO)

## 🚀 Cómo Usar

### Para Asesores

1. **Crear nueva orden**
   - Click "Nueva Orden" en `/asesores/ordenes`
   - Llenar datos básicos
   - Agregar productos
   - Click "Guardar Borrador" o "Confirmar y Crear"

2. **Editar borrador**
   - En lista, click "Continuar" en borrador
   - Modificar datos/productos
   - Click "Guardar Cambios"

3. **Confirmar orden**
   - Option A: Click "Confirmar" en lista
   - Option B: Click "Confirmar y Crear Orden" en formulario
   - Se asigna número automáticamente

4. **Ver órdenes confirmadas**
   - Click "Ver Detalles" en lista
   - Ver información completa
   - Opciones: Descargar PDF, etc.

## 🔧 Configuración

### Para activar colas (opcional)

En `.env`:
```
QUEUE_CONNECTION=database
# o redis, beanstalkd, etc.
```

En `config/queue.php`:
```php
'connections' => [
    'ordenes' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'ordenes',
        'retry_after' => 90,
    ],
],
```

Luego ejecutar:
```bash
php artisan queue:work --queue=ordenes
```

## 📝 Resumen Final

✅ Sistema completo de órdenes con borradores
✅ Sin necesidad de migración nueva
✅ Manejo seguro de concurrencia
✅ Numeración consecutiva garantizada
✅ Interfaz amigable para asesores
✅ Estadísticas en tiempo real
✅ Listo para múltiples usuarios simultáneamente

---

**¡Sistema de órdenes implementado y listo para producción!** 🚀
