# 🎯 QUICK REFERENCE: forma_pago en especificaciones

## 📊 Estructura Visual

```
cotizaciones TABLE (MySQL)
│
├── id: 1
├── numero_cotizacion: "COT-2025-001"
├── asesor_id: 5
├── especificaciones: {
│   ├── "forma_pago": [        ← THIS IS forma_pago
│   │   ├── {
│   │   │   "valor": "Contado",
│   │   │   "observacion": "Descuento 5%"
│   │   },
│   │   ├── {
│   │   │   "valor": "Crédito 30 días",
│   │   │   "observacion": "Máximo 2 millones"
│   │   }
│   │   └── {...}
│   ├── "disponibilidad": [...]
│   ├── "regimen": [...]
│   └── ...
└── created_at: "2025-12-18 10:30:00"
```

---

## 🔄 Data Flow

```
┌──────────────────────────────────────┐
│  HTML Modal (tbody_pago)             │
│  ✓ Bodega | Desc 5%                  │
│  ✓ Crédito | 30 días                 │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  JavaScript                          │
│  EspecificacionesModule              │
│  .extraerEspecificaciones()          │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  window.especificacionesSeleccionadas│
│  {                                   │
│    forma_pago: [                     │
│      {valor: "Contado", obs: "5%"}   │
│    ]                                 │
│  }                                   │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  Form Hidden Input                   │
│  JSON.stringify()                    │
│  especificaciones_input.value        │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  POST /cotizaciones                  │
│  especificaciones="{...}"            │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  Laravel Eloquent                    │
│  Cast: 'array'                       │
│  Convert JSON → Array                │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│  MySQL JSON Column                   │
│  especificaciones                    │
│  {"forma_pago": [{...}]}             │
└──────────────────────────────────────┘
```

---

## 💻 Code Examples

### Access in PHP

```php
// ✅ Get cotizacion
$cot = Cotizacion::find(1);

// ✅ Get especificaciones (already an array)
$specs = $cot->especificaciones;

// ✅ Get forma_pago array
$formaPago = $specs['forma_pago'] ?? [];

// ✅ Iterate
foreach ($formaPago as $pago) {
    echo $pago['valor'];         // "Contado"
    echo $pago['observacion'];   // "Descuento 5%"
}

// ✅ Get first value
$primera = $formaPago[0] ?? null;
echo $primera['valor']; // "Contado"
```

### Create in PHP

```php
// ✅ Create with forma_pago
$cotizacion = Cotizacion::create([
    'numero_cotizacion' => 'COT-001',
    'especificaciones' => [
        'forma_pago' => [
            ['valor' => 'Contado', 'observacion' => 'Desc 5%'],
            ['valor' => 'Crédito', 'observacion' => '30 días']
        ],
        'disponibilidad' => [...]
    ]
]);

// ✅ Access after creation
$cot = Cotizacion::find($cotizacion->id);
echo $cot->especificaciones['forma_pago'][0]['valor']; // "Contado"
```

### In JavaScript

```javascript
// ✅ Get especificaciones
const specs = window.especificacionesSeleccionadas;

// ✅ Get forma_pago
const formaPago = specs.forma_pago;

// ✅ Iterate
formaPago.forEach(pago => {
    console.log(pago.valor);        // "Contado"
    console.log(pago.observacion);  // "Descuento 5%"
});

// ✅ Get first value
console.log(formaPago[0].valor); // "Contado"
```

---

## 🛠️ Common Tasks

### Get forma_pago as single string

```php
$formaPago = $cotizacion->especificaciones['forma_pago'] ?? [];
$string = implode(', ', array_column($formaPago, 'valor'));
// Result: "Contado, Crédito 30 días"
```

### Check if forma_pago exists

```php
$existe = isset($cotizacion->especificaciones['forma_pago']) 
          && count($cotizacion->especificaciones['forma_pago']) > 0;
```

### Get observación for first forma_pago

```php
$obs = $cotizacion->especificaciones['forma_pago'][0]['observacion'] ?? '';
// Result: "Descuento 5%"
```

### Extract all observaciones

```php
$observaciones = array_column(
    $cotizacion->especificaciones['forma_pago'] ?? [],
    'observacion'
);
// Result: ["Descuento 5%", "Máximo 2M", ...]
```

---

## 📝 HTML Modal Structure

```html
<!-- Modal especificaciones -->
<tbody id="tbody_pago">
    <!-- Each row: value | checkbox | observation | delete -->
    <tr>
        <td>
            <label>Contado</label>
            <!-- or -->
            <input type="text" value="Custom Payment">
        </td>
        <td>
            <input type="checkbox" checked>  <!-- ✓ Guardar -->
        </td>
        <td>
            <input type="text" value="Descuento 5%">  <!-- Observación -->
        </td>
        <td>
            <button onclick="this.closest('tr').remove()">Delete</button>
        </td>
    </tr>
</tbody>
```

**Mapeo en JavaScript**:
- `tbody_pago` → `forma_pago` (key in especificaciones)
- `label` → `valor`
- `input[type="text"]` (second) → `observacion`
- `input[type="checkbox"]` checked → incluido en array

---

## 🗄️ Database Query

```sql
-- ✅ Get cotizacion with forma_pago
SELECT especificaciones FROM cotizaciones WHERE id = 1;

-- Result (raw JSON):
{
  "forma_pago": [
    {"valor": "Contado", "observacion": "Descuento 5%"}
  ],
  ...
}

-- ✅ Filter by forma_pago value (MySQL 5.7+)
SELECT * FROM cotizaciones 
WHERE JSON_CONTAINS(especificaciones->'$.forma_pago[*].valor', '"Contado"');

-- ✅ Get all forma_pago values
SELECT JSON_EXTRACT(especificaciones, '$.forma_pago[*].valor') 
FROM cotizaciones 
WHERE id = 1;

-- Result: ["Contado", "Crédito"]
```

---

## ✅ Valid Examples

```php
// ✅ VALID - Array de objetos
[
    ['valor' => 'Contado', 'observacion' => 'Desc 5%'],
    ['valor' => 'Crédito', 'observacion' => '']
]

// ✅ VALID - Single item
[
    ['valor' => 'Contado', 'observacion' => 'Pago al contado']
]

// ✅ VALID - Empty observacion
[
    ['valor' => 'Contado', 'observacion' => '']
]

// ✅ VALID - Multiple items, mixed observaciones
[
    ['valor' => 'Contado', 'observacion' => 'Inmediato'],
    ['valor' => 'Crédito 30', 'observacion' => ''],
    ['valor' => 'Crédito 60', 'observacion' => 'Con aval']
]
```

---

## ❌ Invalid Examples

```php
// ❌ INVALID - String instead of array
'forma_pago' => 'Contado'  // Should be array

// ❌ INVALID - Missing estructura
'forma_pago' => ['Contado', 'Crédito']  // Missing valor/observacion keys

// ❌ INVALID - Only valor, no observacion
'forma_pago' => [['valor' => 'Contado']]  // observacion is required

// ❌ INVALID - Nested arrays
'forma_pago' => [
    [
        ['valor' => 'Contado']  // Too deep
    ]
]
```

---

## 📍 File Locations

| File | Purpose |
|------|---------|
| `app/Models/Cotizacion.php` | Model with 'especificaciones' cast |
| `public/js/.../EspecificacionesModule.js` | Captures from modal |
| `app/DTOs/CotizacionSearchDTO.php` | Extracts forma_pago |
| `resources/views/.../modal-especificaciones.blade.php` | Modal HTML |
| `app/Http/Controllers/PDFCotizacionController.php` | Renders in PDF |
| `tests/Unit/EspecificacionesTest.php` | Tests structure |

---

## 🔗 Related Columns in cotizaciones

| Column | Type | Description |
|--------|------|-------------|
| `especificaciones` | JSON | Whole especificaciones object including forma_pago |
| `asesor_id` | FK | Link to user (asesor) |
| `cliente_id` | FK | Link to cliente |
| `numero_cotizacion` | String | COT-XXXX |
| `estado` | String | Active/Inactive status |
| `es_borrador` | Boolean | Draft flag |

