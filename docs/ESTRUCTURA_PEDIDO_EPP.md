# Estructura de Tablas: EPP en Pedidos

## Diagrama de Relaciones

```
┌─────────────┐         ┌──────────────────┐         ┌──────────────────┐
│   pedidos   │─────────│   pedido_epp     │─────────│  pedido_epp_ima  │
├─────────────┤ (1:N)   ├──────────────────┤ (1:N)   │   genes          │
│ id (PK)     │         │ id (PK)          │         ├──────────────────┤
│ numero      │         │ pedido_id (FK)   │         │ id (PK)          │
│ cliente_id  │         │ epp_id (FK) ─────┼─────────→ pedido_epp_id    │
│ estado      │         │ cantidad         │         │ (FK)             │
│ ...         │         │ tallas_medidas   │         │ archivo          │
└─────────────┘         │ observaciones    │         │ principal        │
                        └──────────────────┘         │ orden            │
                               ↑                     └──────────────────┘
                               │
                               │ (FK)
                        ┌──────────────┐
                        │     epps     │
                        ├──────────────┤
                        │ id (PK)      │
                        │ codigo       │
                        │ nombre       │
                        │ categoria_id │
                        │ descripcion  │
                        │ activo       │
                        └──────────────┘
```

## Descripción de Tablas

### 1. `pedidos`
Almacena los pedidos principales del sistema.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint (PK) | Identificador único |
| numero | varchar(255) | Número único del pedido |
| cliente_id | bigint (FK) | Referencia al cliente |
| estado | varchar(255) | Estado: pendiente, procesando, completado, cancelado |
| descripcion | longtext | Descripción general del pedido |
| observaciones | longtext | Observaciones adicionales |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |
| deleted_at | timestamp | Fecha de eliminación (soft delete) |

### 2. `pedido_epp`
Almacena los EPP específicos agregados a cada pedido. **Este es el vínculo entre un pedido y los EPP que contiene.**

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint (PK) | Identificador único |
| pedido_id | bigint (FK) | Referencia al pedido |
| epp_id | bigint (FK) | Referencia al EPP elegido |
| cantidad | int | Cantidad de este EPP en el pedido |
| tallas_medidas | json | Tallas y medidas seleccionadas: `{"talla": "M", "medida": "100cm", ...}` |
| observaciones | longtext | Observaciones específicas para este EPP |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |
| deleted_at | timestamp | Fecha de eliminación (soft delete) |

**Índices:**
- PK: id
- FK: pedido_id → pedidos(id) ON DELETE CASCADE
- FK: epp_id → epps(id) ON DELETE RESTRICT
- Index: pedido_id, epp_id

### 3. `pedido_epp_imagenes`
Almacena las imágenes específicas de cada EPP agregado a un pedido.

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint (PK) | Identificador único |
| pedido_epp_id | bigint (FK) | Referencia al EPP del pedido |
| archivo | varchar(255) | Ruta del archivo de imagen |
| principal | boolean | Si es la imagen principal (true/false) |
| orden | int | Orden de presentación (0, 1, 2, ...) |
| created_at | timestamp | Fecha de creación |
| updated_at | timestamp | Fecha de actualización |

**Índices:**
- PK: id
- FK: pedido_epp_id → pedido_epp(id) ON DELETE CASCADE
- Index: pedido_epp_id

## Flujo de Uso

### Crear un Pedido con EPP

```
1. Crear Pedido
   → INSERT INTO pedidos (numero, cliente_id, estado, ...) VALUES (...)

2. Agregar EPP al Pedido
   → INSERT INTO pedido_epp (pedido_id, epp_id, cantidad, tallas_medidas, observaciones)
      VALUES (1, 5, 2, '{"talla": "M"}', 'Observación...')

3. Agregar Imágenes al EPP del Pedido
   → INSERT INTO pedido_epp_imagenes (pedido_epp_id, archivo, principal, orden)
      VALUES (1, '/storage/pedidos/1/epp/imagen.jpg', true, 0)
```

## Relaciones en Modelos Eloquent

### Modelo: Pedido
```php
$pedido->epps()           // Todos los EPP en este pedido (relación muchos-a-muchos)
$pedido->pedidosEpp()     // Registros PedidoEpp (relación 1:N)
```

### Modelo: PedidoEpp
```php
$pedidoEpp->pedido()      // El pedido al que pertenece
$pedidoEpp->epp()         // El EPP referenciado
$pedidoEpp->imagenes()    // Todas las imágenes de este EPP
$pedidoEpp->imagenPrincipal() // La imagen principal
```

### Modelo: PedidoEppImagen
```php
$imagen->pedidoEpp()      // El EPP del pedido al que pertenece
```

### Modelo: Epp
```php
$epp->pedidosEpp()        // Todos los registros PedidoEpp que usan este EPP
$epp->pedidos()           // Todos los pedidos que contienen este EPP
```

## Ventajas de Esta Estructura

 **Flexibilidad:** Un pedido puede tener múltiples EPP, cada uno con diferentes cantidades, tallas y observaciones.

 **Independencia:** Las imágenes están asociadas al EPP del pedido específico, no al EPP genérico. Permite versionar imágenes por pedido.

 **Rastreabilidad:** Se mantiene registro de qué EPP se agregó a qué pedido y cuándo.

 **Escalabilidad:** Soporta múltiples imágenes por EPP con orden definido.

 **Integridad:** Constraints previenen eliminación de EPPs si están en uso, pero permite eliminar pedidos (cascade).

## Próximos Pasos

1. Ejecutar migraciones: `php artisan migrate`
2. Crear controlador: `PedidoEppController` para CRUD
3. Crear servicios: Para agregar/actualizar EPP en pedidos
4. Actualizar frontend: Para manejar el flujo de agregar EPP a pedidos
