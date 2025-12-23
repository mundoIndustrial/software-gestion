# ✅ IMPLEMENTACIÓN COMPLETADA: Creación de 2 Pedidos desde Cotizaciones Combinadas (PL)

## 🎯 OBJETIVO
Cuando un usuario crea un pedido desde una **cotización combinada (PL)**, el sistema debe crear **2 pedidos independientes**:
1. **Pedido de PRENDAS** en tabla `pedidos_produccion`
2. **Pedido de LOGO** en tabla `logo_pedidos`

## 📋 CAMBIOS REALIZADOS

### 1️⃣ BASE DE DATOS ✅
- ✅ Migración creada: Columna `cantidad` agregada a `logo_pedidos`

### 2️⃣ BACKEND - `PedidosProduccionController.php` ✅

#### Cambio 1: Detectar tipo de cotización
```php
$tipoCotizacionCodigo = strtoupper(trim($cotizacion->tipoCotizacion?->codigo ?? ''));

// Detecta:
// - 'L'  → LOGO SOLO (crea solo logo_pedido)
// - 'PL' → COMBINADA (crea AMBOS: pedidos_produccion + logo_pedido)
```

#### Cambio 2: Crear pedido de logo cuando es COMBINADA (PL)
**Ubicación:** Fin del método `crearDesdeCotizacion()`, antes de `DB::commit()`

```php
// ✅ Si es cotización COMBINADA (PL), TAMBIÉN crear pedido de LOGO
if ($tipoCotizacionCodigo === 'PL') {
    // 1. Obtener logo_cotizacion_id
    // 2. Generar número LOGO
    // 3. Crear registro en logo_pedidos VINCULADO a pedidos_produccion
    // 4. Crear proceso inicial para el logo
}
```

#### Cambio 3: Devolver ambos IDs cuando es COMBINADA
```php
if ($tipoCotizacionCodigo === 'PL') {
    return response()->json([
        'success' => true,
        'message' => 'Cotización aceptada y AMBOS pedidos creados',
        'pedido_id' => $pedido->id,           // ID de pedidos_produccion
        'logo_pedido_id' => $logoPedidoId,    // ID de logo_pedidos ✅ NUEVO
        'tipo_cotizacion' => 'PL'
    ]);
}
```

### 3️⃣ FRONTEND - `crear-pedido-editable.js` ✅

#### Cambio 1: Detectar tipo de cotización
```javascript
const tipoCotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
const esCombinada = tipoCotizacion === 'PL';
const esLogoSolo = tipoCotizacion === 'L';
```

#### Cambio 2: Manejar COMBINADA en el flujo de envío
```javascript
if (esLogoSolo || esCombinada) {
    // NUEVO: Incluye lógica para recopilar prendas si es COMBINADA
    // Para COMBINADA: envía prendas
    // Para LOGO SOLO: envía array vacío
}
```

#### Cambio 3: Mostrar ambos números cuando es COMBINADA
```javascript
if (esCombinada) {
    // Mostrar AMBOS números:
    // 📦 Pedido Producción: PED-XXXXX
    // 🎨 Pedido Logo: LOGO-XXXXX
}
```

## 🔄 FLUJO COMPLETO ACTUALIZADO

```
Usuario selecciona cotización COMBINADA (PL)
         ↓
PASO 1-2: Buscar y seleccionar cotización
         ↓
PASO 3: Renderizar 2 TABS
        Tab PRENDAS: Mostrar prendas con cantidades
        Tab LOGO: Mostrar logo con técnicas, ubicaciones, tallas
         ↓
Usuario rellena AMBOS tabs
         ↓
Click "Crear Pedido"
         ↓
┌──────────────────────────────────────────┐
│ Frontend (JavaScript):                   │
│ 1. Detecta que es 'PL' (esCombinada)    │
│ 2. Recopila PRENDAS de tab PRENDAS      │
│ 3. Prepara payload con prendas          │
│ 4. Calcula cantidad total de tallas     │
│    para logo                            │
└──────────────────────────────────────────┘
         ↓
POST /asesores/pedidos-produccion/crear-desde-cotizacion/{id}
{
    cotizacion_id: 123,
    forma_de_pago: "CONTADO",
    prendas: [...]  // ← Contiene prendas para COMBINADA
}
         ↓
┌──────────────────────────────────────────┐
│ Backend (Controller):                    │
│ 1. Detecta tipoCotizacion = 'PL'        │
│ 2. Crea pedido en pedidos_produccion    │
│ 3. Crea prendas del pedido              │
│ 4. ✅ TAMBIÉN crea pedido en            │
│    logo_pedidos (AUTOMÁTICAMENTE)       │
│ 5. Vincula ambos pedidos                │
│ 6. Devuelve ambos IDs                   │
└──────────────────────────────────────────┘
         ↓
Respuesta:
{
    success: true,
    pedido_id: 45,              // ID de pedidos_produccion
    logo_pedido_id: 6,          // ID de logo_pedidos ✅
    tipo_cotizacion: 'PL',
    message: "AMBOS pedidos creados"
}
         ↓
POST /asesores/pedidos/guardar-logo-pedido
{
    pedido_id: 6,
    descripcion: "...",
    cantidad: 150,  // ← Suma de tallas
    tecnicas: [...],
    ubicaciones: [...],
    fotos: [...]
}
         ↓
✅ ÉXITO: Mostrar ambos números
   📦 Pedido Producción: PED-00045
   🎨 Pedido Logo: LOGO-00006
         ↓
Redirigir a /asesores/pedidos
```

## 📊 REGISTROS CREADOS EN BD

### Tabla `pedidos_produccion`
```sql
INSERT INTO pedidos_produccion VALUES (
    45,
    123,              -- cotizacion_id
    'PED-00045',      -- numero_pedido
    'Cliente XYZ',
    1,                -- asesor_id
    'CONTADO',
    'Pendiente',
    NOW(),
    ...
);
```

### Tabla `logo_pedidos` (vinculado al anterior)
```sql
INSERT INTO logo_pedidos VALUES (
    6,
    45,               -- pedido_id ← Vinculado a pedidos_produccion(45)
    12,               -- logo_cotizacion_id
    'LOGO-00006',     -- numero_pedido
    'Logo bordado uniforme',
    150,              -- cantidad ← Suma de tallas (5+10+8+20+...)
    '["BORDADO"]',    -- tecnicas
    'Bordado de buena calidad',
    '[...]',          -- ubicaciones
    'CONTADO',
    'Usuario1',
    NOW(),
    ...
);
```

## ✅ VALIDACIÓN

### Antes de los cambios:
- ❌ Crear pedido desde PL: Solo crea pedidos_produccion
- ❌ No crea logo_pedidos automáticamente

### Después de los cambios:
- ✅ Crear pedido desde PL: Crea AMBOS
- ✅ Vincula automáticamente (pedido_id en logo_pedidos)
- ✅ Guarda cantidad total de tallas
- ✅ Muestra ambos números de pedido al usuario

## 🧪 TESTING MANUAL RECOMENDADO

1. **Crear Cotización Combinada (PL)**
   - Ir a: Crear Cotización → Tipo "Bordado" → Agregar Prendas
   - Verificar que aparecen ambos tabs

2. **Crear Pedido desde esa Cotización**
   - Tab PRENDAS: Llenar cantidades
   - Tab LOGO: Llenar descripción, técnicas, ubicaciones, tallas

3. **Verificar en BD**
   ```sql
   -- Verificar que se creó en pedidos_produccion
   SELECT * FROM pedidos_produccion WHERE numero_pedido LIKE 'PED-%' ORDER BY id DESC LIMIT 1;
   
   -- Verificar que TAMBIÉN se creó en logo_pedidos CON VINCULACIÓN
   SELECT * FROM logo_pedidos WHERE pedido_id = <id_pedido_anterior>;
   
   -- Verificar que cantidad se guardó correctamente
   SELECT id, numero_pedido, cantidad FROM logo_pedidos ORDER BY id DESC LIMIT 1;
   ```

4. **Verificar en Interfaz**
   - Ir a /asesores/pedidos
   - Debe aparecer AMBOS pedidos (uno de prendas, uno de logo)

## 📁 ARCHIVOS MODIFICADOS

| Archivo | Cambios |
|---------|---------|
| `app/Http/Controllers/Asesores/PedidosProduccionController.php` | Detectar PL, crear logo_pedido automáticamente |
| `public/js/crear-pedido-editable.js` | Detectar PL, recopilar prendas, mostrar ambos números |
| `database/migrations/2025_12_23_add_cantidad_to_logo_pedidos_table.php` | Columna cantidad (ya creada) |
| `app/Models/LogoPedido.php` | Incluir cantidad en $fillable (ya actualizado) |

## 🎯 RESULTADO FINAL

El sistema ahora:
✅ Detecta automáticamente cuando es una cotización **COMBINADA (PL)**
✅ Crea **2 pedidos independientes** pero vinculados
✅ El pedido de logo se crea **automáticamente** (no requiere paso adicional)
✅ Guarda **cantidad total** (suma de tallas) en logo_pedidos
✅ Mantiene **toda la información** del logo (descripción, técnicas, ubicaciones, fotos)
✅ Muestra **ambos números** de pedido al usuario

