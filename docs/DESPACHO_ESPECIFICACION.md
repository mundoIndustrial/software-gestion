# Módulo de Despacho - Especificación de Almacenamiento

## Resumen General

El módulo de Despacho controla entregas parciales de prendas y EPP. **NO realiza cálculos automáticos** sobre los valores ingresados por el usuario. Cada talla de una prenda representa una fila independiente en el formulario.

---

## Estructura de Datos

### Tabla: `despacho_parciales`

```sql
Campos principales:
- id (PK)
- pedido_id (FK) → pedidos_produccion.id
- tipo_item ENUM('prenda', 'epp')
- item_id → ID de prenda_pedido_tallas (para prendas) o pedido_epp.id (para EPP)
- talla_id → ID de prenda_pedido_tallas.id (para prendas)

Valores del Usuario (SIN MODIFICACIÓN):
- pendiente_inicial → Ingresado por usuario
- parcial_1 → Ingresado por usuario
- pendiente_1 → Ingresado por usuario
- parcial_2 → Ingresado por usuario
- pendiente_2 → Ingresado por usuario
- parcial_3 → Ingresado por usuario
- pendiente_3 → Ingresado por usuario

Valores Automáticos:
- fecha_despacho → now()
- usuario_id → Auth::id()
- observaciones → "Cliente empresa: {nombre}"
- created_at, updated_at, deleted_at → Sistema
```

---

## Flujo de Datos

### 1. Frontend (Vista Blade)
**Archivo**: `resources/views/despacho/show.blade.php`

#### Tabla de Despacho
```html
<!-- Encabezados -->
Descripción | Talla | Cantidad | Pendiente | Parcial 1 | Pendiente | Parcial 2 | Pendiente | Parcial 3 | Pendiente

<!-- Cada fila representa UNA TALLA -->
data-tipo="prenda"
data-id="<prenda_pedido_tallas.id>"  ← ID de la talla
data-talla-id="<prenda_pedido_tallas.id>"
```

**Campos de Entrada (tipo number)**:
- `.pendiente-inicial` - Cantidad pendiente inicial
- `.parcial-1` - Cantidad parcial 1
- `.pendiente-1` - Cantidad pendiente después parcial 1
- `.parcial-2` - Cantidad parcial 2
- `.pendiente-2` - Cantidad pendiente después parcial 2
- `.parcial-3` - Cantidad parcial 3
- `.pendiente-3` - Cantidad pendiente después parcial 3

**Valores por defecto en UI**: 0 (ceros, pero el usuario puede cambiarlos)

### 2. JavaScript (Captura de Datos)
**Archivo**: `resources/views/despacho/show.blade.php` (función `guardarDespacho()`)

```javascript
// Para CADA fila de la tabla:
despachos.push({
    tipo: fila.dataset.tipo,                    // "prenda"
    id: parseInt(fila.dataset.id),              // ID de prenda_pedido_tallas
    talla_id: parseInt(fila.dataset.tallaId),   // ID de prenda_pedido_tallas
    
    // VALORES EXACTOS del usuario SIN MODIFICACIÓN
    pendiente_inicial: parseInt(fila.querySelector('.pendiente-inicial').value) || 0,
    parcial_1: parseInt(fila.querySelector('.parcial-1').value) || 0,
    pendiente_1: parseInt(fila.querySelector('.pendiente-1').value) || 0,
    parcial_2: parseInt(fila.querySelector('.parcial-2').value) || 0,
    pendiente_2: parseInt(fila.querySelector('.pendiente-2').value) || 0,
    parcial_3: parseInt(fila.querySelector('.parcial-3').value) || 0,
    pendiente_3: parseInt(fila.querySelector('.pendiente-3').value) || 0,
});
```

**POST a**: `route('despacho.guardar', $pedido->id)`
```json
{
    "fecha_hora": "2026-01-28 10:30:00",
    "cliente_empresa": "Acme Corp",
    "despachos": [
        {
            "tipo": "prenda",
            "id": 42,
            "talla_id": 42,
            "pendiente_inicial": 10,
            "parcial_1": 5,
            "pendiente_1": 5,
            "parcial_2": 0,
            "pendiente_2": 5,
            "parcial_3": 0,
            "pendiente_3": 5
        },
        {
            "tipo": "prenda",
            "id": 43,
            "talla_id": 43,
            "pendiente_inicial": 8,
            "parcial_1": 3,
            "pendiente_1": 5,
            ...
        }
    ]
}
```

### 3. Backend - Controller
**Archivo**: `app/Infrastructure/Http/Controllers/Despacho/DespachoController.php`

- Valida los datos con `DespachoControllerValidator`
- Convierte a DTO `ControlEntregasDTO`
- Ejecuta `GuardarDespachoUseCase`

### 4. Backend - Use Case
**Archivo**: `app/Application/Pedidos/Despacho/UseCases/GuardarDespachoUseCase.php`

```
1. Validar que el pedido existe
2. Convertir snake_case → camelCase en DespachoParcialesDTO
3. Validar múltiples despachos (DespachoValidadorService)
   - Solo valida que no haya negativos
   - NO valida contra cantidad disponible
4. Procesar cada despacho (logging/auditoría)
5. Persistir múltiples despachos (DesparChoParcialesPersistenceService)
   - Dentro de transacción DB::transaction()
   - Asigna automáticamente:
     * usuario_id = Auth::id()
     * fecha_despacho = now()
     * observaciones = "Cliente empresa: {clienteEmpresa}"
```

### 5. Backend - Persistencia
**Archivo**: `app/Domain/Pedidos/Despacho/Services/DesparChoParcialesPersistenceService.php`

```php
crearYGuardarMultiples([
    [
        'pedido_id' => 1,
        'tipo_item' => 'prenda',
        'item_id' => 42,                    // ID de prenda_pedido_tallas
        'talla_id' => 42,                   // ID de prenda_pedido_tallas
        'pendiente_inicial' => 10,          // Del usuario
        'parcial_1' => 5,                   // Del usuario
        'pendiente_1' => 5,                 // Del usuario
        'parcial_2' => 0,                   // Del usuario
        'pendiente_2' => 5,                 // Del usuario
        'parcial_3' => 0,                   // Del usuario
        'pendiente_3' => 5,                 // Del usuario
        'observaciones' => 'Cliente empresa: Acme Corp',
    ],
    // ... más despachos
], $usuarioId);
```

### 6. Base de Datos - Inserción
**Tabla**: `despacho_parciales`

**Un registro por cada fila (talla)**:
```
id | pedido_id | tipo_item | item_id | talla_id | pendiente_inicial | parcial_1 | ... | usuario_id | fecha_despacho | created_at
1  | 1         | prenda    | 42      | 42       | 10                | 5         | ... | 5          | 2026-01-28...  | 2026-01-28...
2  | 1         | prenda    | 43      | 43       | 8                 | 3         | ... | 5          | 2026-01-28...  | 2026-01-28...
```

---

## Puntos Clave de Validación

###  Lo que SÍ hace el sistema:

1. **Almacena valores exactamente como fueron ingresados**
   - Todos los campos numéricos (pendiente_inicial, parcial_*, pendiente_*) se guardan tal cual

2. **Un registro por talla**
   - Cada `prenda_pedido_tallas.id` genera una fila independiente
   - Cada EPP genera una fila independiente

3. **Asignación automática de campos**
   - `tipo_item = 'prenda'` (automático desde tipo enviado)
   - `item_id = prenda_pedido_tallas.id` (enviado desde frontend)
   - `usuario_id = Auth::id()` (automático del usuario autenticado)
   - `fecha_despacho = now()` (timestamp automático)
   - `pedido_id` (del pedido siendo despachado)

4. **Validación Mínima**
   - Solo rechaza valores negativos en parciales
   - NO valida contra cantidad disponible
   - NO calcula pendientes automáticamente
   - NO modifican valores del usuario

###  Lo que NO hace el sistema:

1.  NO realiza cálculos automáticos de pendientes
2.  NO modifica valores ingresados por el usuario
3.  NO valida que parciales no excedan cantidad total
4.  NO calcula totales automáticamente
5.  NO combina filas de la misma talla

---

## Generación de Filas (Frontend)

**Archivo**: `app/Domain/Pedidos/Despacho/Services/DespachoGeneradorService.php`

```php
// Para CADA prenda con tallas:
foreach ($tallas as $talla) {
    $filas->push(new FilaDespachoDTO(
        tipo: 'prenda',
        id: $talla->id,                    // ← ID de prenda_pedido_tallas
        tallaId: $talla->id,               // ← Se guarda también aquí
        descripcion: "nombre - genero",
        cantidadTotal: $talla->cantidad,
        talla: $talla->talla,
    ));
}
```

**Resultado en HTML**:
```html
<tr data-tipo="prenda" 
    data-id="42"              <!-- ID de prenda_pedido_tallas -->
    data-talla-id="42">
    ...
</tr>
```

---

## Campos en la Tabla Final

| Campo | Origen | Tipo | Validación |
|-------|--------|------|-----------|
| id | Auto | PK | - |
| pedido_id | Backend (del pedido) | FK | ✓ Existe |
| tipo_item | Frontend/Backend | ENUM | ✓ prenda\|epp |
| item_id | Frontend (prenda_pedido_tallas.id) | INT | ✓ Sin negativo |
| talla_id | Frontend (prenda_pedido_tallas.id) | INT | ✓ Sin negativo |
| pendiente_inicial | **Usuario** | INT | ✓ Sin negativo |
| parcial_1 | **Usuario** | INT | ✓ Sin negativo |
| pendiente_1 | **Usuario** | INT | ✓ Sin negativo |
| parcial_2 | **Usuario** | INT | ✓ Sin negativo |
| pendiente_2 | **Usuario** | INT | ✓ Sin negativo |
| parcial_3 | **Usuario** | INT | ✓ Sin negativo |
| pendiente_3 | **Usuario** | INT | ✓ Sin negativo |
| observaciones | Backend | TEXT | Automático |
| fecha_despacho | Backend | TIMESTAMP | now() |
| usuario_id | Backend | FK | Auth::id() |
| created_at | Sistema | TIMESTAMP | - |
| updated_at | Sistema | TIMESTAMP | - |
| deleted_at | Sistema | TIMESTAMP | NULL |

---

## Restricciones Implementadas

### En la Validación (DespachoValidadorService)
```php
// ÚNICO que rechaza:
if ($despacho->parcial1 < 0 || $despacho->parcial2 < 0 || $despacho->parcial3 < 0) {
    throw new DespachoInvalidoException(...);
}

// NOTA: No validamos contra cantidad disponible porque 
// los campos de pendiente se ingresan manualmente sin cálculos automáticos
```

### En la Migración
- Todos los campos numéricos tienen `default(0)`
- `fecha_despacho` usa `useCurrent()`
- `usuario_id` permite NULL
- Soft deletes activados

---

## Ejemplo Completo: Guardar una Prenda de 2 Tallas

### Pedido con 1 prenda de 2 tallas:
```
Prenda: "Camiseta Roja"
├─ Talla M (id: 42, cantidad: 10)
└─ Talla L (id: 43, cantidad: 8)
```

### Usuario llena el formulario:
```
Talla M: pendiente_inicial=10, parcial_1=5, pendiente_1=5, parcial_2=0, pendiente_2=5, parcial_3=0, pendiente_3=5
Talla L: pendiente_inicial=8, parcial_1=3, pendiente_1=5, parcial_2=0, pendiente_2=5, parcial_3=0, pendiente_3=5
```

### Frontend envía:
```json
{
    "despachos": [
        {
            "tipo": "prenda",
            "id": 42,
            "talla_id": 42,
            "pendiente_inicial": 10,
            "parcial_1": 5,
            "pendiente_1": 5,
            "parcial_2": 0,
            "pendiente_2": 5,
            "parcial_3": 0,
            "pendiente_3": 5
        },
        {
            "tipo": "prenda",
            "id": 43,
            "talla_id": 43,
            "pendiente_inicial": 8,
            "parcial_1": 3,
            "pendiente_1": 5,
            "parcial_2": 0,
            "pendiente_2": 5,
            "parcial_3": 0,
            "pendiente_3": 5
        }
    ]
}
```

### Base de datos genera 2 registros:
```sql
INSERT INTO despacho_parciales VALUES
(NULL, 1, 'prenda', 42, 42, 10, 5, 5, 0, 5, 0, 5, 'Cliente empresa: Acme', NOW(), 3, NOW(), NOW(), NULL),
(NULL, 1, 'prenda', 43, 43, 8, 3, 5, 0, 5, 0, 5, 'Cliente empresa: Acme', NOW(), 3, NOW(), NOW(), NULL);
```

**Resultado Final**:
- 2 registros en `despacho_parciales`
- Cada uno con sus valores exactos del usuario
- Sin modificaciones ni cálculos automáticos
- Datos de auditoría automáticos (usuario, fecha, pedido)

---

## Conclusión

El módulo cumple con los requisitos:

 **NO realiza cálculos automáticos**
 **NO modifica valores ingresados por el usuario**
 **Cada fila es un registro independiente**
 **`item_id` almacena ID de `prenda_pedido_tallas`**
 **Campos de pendientes se guardan exactamente como ingresados**
 **`tipo_item='prenda'` es automático**
 **`usuario_id` y `fecha_despacho` se asignan automáticamente**
 **Itera por cada talla enviada desde frontend**
