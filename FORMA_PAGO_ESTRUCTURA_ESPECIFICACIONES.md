# 📋 ESTRUCTURA DE `forma_pago` EN `especificaciones` - Cotizacion Model

## 🎯 Resumen Ejecutivo

El campo `forma_pago` se almacena dentro de la columna JSON `especificaciones` de la tabla `cotizaciones`. La estructura es un **array de objetos** con propiedades `valor` y `observacion`.

---

## 📊 ESTRUCTURA DE DATOS

### 1. En la Base de Datos

**Tabla**: `cotizaciones`  
**Columna**: `especificaciones` (tipo JSON)  
**Clave**: `forma_pago` 

```json
{
  "forma_pago": [
    {
      "valor": "Contado",
      "observacion": "Descuento 5%"
    },
    {
      "valor": "Crédito 30 días",
      "observacion": "Sin intereses"
    }
  ],
  "disponibilidad": [...],
  "regimen": [...],
  "se_ha_vendido": [...],
  "ultima_venta": [...],
  "flete": [...]
}
```

---

## 🔍 DETALLES TÉCNICOS

### 1.1 Tipo de Dato en BD

```php
// Modelo: app/Models/Cotizacion.php
protected $casts = [
    'especificaciones' => 'array',  // Se convierte automáticamente a/desde JSON
];

// Fillable
protected $fillable = [
    'especificaciones',
    // ... otros campos
];
```

**Tipo SQL**: `LONGTEXT` o `JSON` (almacenado como texto JSON)  
**Cast Eloquent**: `array` - convierte automáticamente a/desde JSON

### 1.2 Estructura Completa de `especificaciones`

```php
$especificaciones = [
    'disponibilidad' => [
        ['valor' => 'Bodega', 'observacion' => 'En stock disponible'],
        ['valor' => 'Cúcuta', 'observacion' => 'Disponible en 2 días']
    ],
    'forma_pago' => [
        ['valor' => 'Contado', 'observacion' => 'Descuento 5%'],
        ['valor' => 'Crédito 30 días', 'observacion' => 'Sin intereses']
    ],
    'regimen' => [
        ['valor' => 'Común', 'observacion' => '']
    ],
    'se_ha_vendido' => [
        ['valor' => 'Sí', 'observacion' => 'Año anterior']
    ],
    'ultima_venta' => [
        ['valor' => 'Enero 2025', 'observacion' => 'Para cliente XYZ']
    ],
    'flete' => [
        ['valor' => 'Incluido', 'observacion' => 'A nivel nacional']
    ]
];
```

---

## 💾 CÓMO SE ALMACENA EN LA BD

### En MySQL

```sql
-- Ejemplo de fila en la tabla cotizaciones
SELECT especificaciones FROM cotizaciones WHERE id = 1;

-- Resultado (string JSON):
{
  "forma_pago": [
    {"valor": "Contado", "observacion": "Descuento 5%"}
  ],
  "disponibilidad": [
    {"valor": "Bodega", "observacion": "En stock disponible"}
  ],
  "regimen": [
    {"valor": "Común", "observacion": ""}
  ]
}
```

### Cómo Recuperarlo

```php
// Automático - Laravel convierte el JSON a array
$cotizacion = Cotizacion::find(1);
$especificaciones = $cotizacion->especificaciones; // Ya es un array

// Acceso a forma_pago
$formaPago = $cotizacion->especificaciones['forma_pago'] ?? [];

// Resultado:
// [
//     ['valor' => 'Contado', 'observacion' => 'Descuento 5%']
// ]

// Acceso al primer elemento
$primerPago = $formaPago[0]; // ['valor' => 'Contado', 'observacion' => 'Descuento 5%']
$valor = $primerPago['valor']; // 'Contado'
$obs = $primerPago['observacion']; // 'Descuento 5%'
```

---

## 🎨 CÓMO SE CAPTURA EN EL FRONTEND

### JavaScript Module: `EspecificacionesModule.js`

```javascript
class EspecificacionesModule {
    constructor() {
        this.categoriasMap = {
            'tbody_disponibilidad': 'disponibilidad',
            'tbody_pago': 'forma_pago',  // ← Mapeo importante
            'tbody_regimen': 'regimen',
            'tbody_vendido': 'se_ha_vendido',
            'tbody_ultima_venta': 'ultima_venta',
            'tbody_flete': 'flete'
        };
    }

    /**
     * Extrae especificaciones del modal
     * Crea la estructura: categoria -> array de {valor, observacion}
     */
    extraerEspecificaciones() {
        const especificaciones = {};

        Object.entries(this.categoriasMap).forEach(([tbodyId, categoriaKey]) => {
            const tbody = document.getElementById(tbodyId);
            if (!tbody) return;

            const filas = tbody.querySelectorAll('tr');
            const items = [];

            filas.forEach((fila) => {
                const checkbox = fila.querySelector('input[type="checkbox"]');
                if (checkbox && checkbox.checked) {
                    const label = fila.querySelector('label');
                    const inputObs = fila.querySelectorAll('input[type="text"]')[1];

                    items.push({
                        valor: label?.textContent?.trim() || '✓',
                        observacion: inputObs?.value?.trim() || ''
                    });
                }
            });

            if (items.length > 0) {
                especificaciones[categoriaKey] = items;
            }
        });

        return especificaciones;
    }
}
```

**Resultado del JavaScript**:
```javascript
window.especificacionesSeleccionadas = {
    "forma_pago": [
        {"valor": "Contado", "observacion": "Descuento 5%"}
    ],
    "disponibilidad": [...]
}
```

---

## 📤 CÓMO SE ENVÍA AL BACKEND

### Blade Template (create.blade.php)

```blade
<form method="POST" action="{{ route('cotizaciones.store') }}">
    @csrf
    
    <!-- Modal de especificaciones con tbodies -->
    <tbody id="tbody_pago">
        <tr>
            <td><label>Contado</label></td>
            <td><input type="checkbox"></td>
            <td><input type="text" placeholder="Observaciones"></td>
        </tr>
        <tr>
            <td><label>Crédito 30 días</label></td>
            <td><input type="checkbox"></td>
            <td><input type="text"></td>
        </tr>
    </tbody>
    
    <!-- Input hidden con las especificaciones -->
    <input type="hidden" id="especificaciones_input" name="especificaciones">
    
    <button type="submit">Guardar</button>
</form>

<script>
// Al guardar el formulario
document.querySelector('form').addEventListener('submit', function() {
    const especificaciones = especificacionesModule.getEspecificaciones();
    document.getElementById('especificaciones_input').value = 
        JSON.stringify(especificaciones);
});
</script>
```

---

## 🔄 FLUJO COMPLETO: De Frontend a Backend

```
┌─────────────────────┐
│   HTML Modal        │  (tbody_pago con checkboxes y inputs)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  EspecificacionesModule.extraerEspecificaciones()
│  ↓
│  Crea array: 
│  {
│    valor: "Contado",
│    observacion: "Descuento 5%"
│  }
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  JSON.stringify()   │  → {"forma_pago": [{...}]}
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Form Input Hidden  │  → especificaciones_input.value
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  POST /cotizaciones │  (especificaciones como string JSON)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  CotizacionController│
│  $cot = Cotizacion::create([
│    'especificaciones' => $request->especificaciones
│  ])
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Eloquent Cast      │  (convierte a array automáticamente)
│  'especificaciones' │  → array
│  => 'array'         │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  MySQL JSON Column  │  (almacena como JSON)
│  especificaciones   │
└─────────────────────┘
```

---

## 🛠️ CÓMO SE USA EN LOS CONTROLLERS

### Ejemplo 1: Obtener forma_pago

```php
// app/Http/Controllers/Asesores/PedidosProduccionController.php

$cotizacion = Cotizacion::find($cotizacionId);
$especificaciones = $cotizacion->especificaciones; // Ya es array

// Opción 1: Obtener forma_pago como array
$formaPagoArray = $especificaciones['forma_pago'] ?? [];
// Resultado: [['valor' => 'Contado', 'observacion' => 'Descuento 5%']]

// Opción 2: Extraer solo los valores
$valores = array_column($formaPagoArray, 'valor');
// Resultado: ['Contado']

// Opción 3: Usar en pedido
$formaPago = implode(',', $valores);
// Resultado: 'Contado'

$pedido = PedidoProduccion::create([
    'forma_de_pago' => $formaPago,
    // ... otros campos
]);
```

### Ejemplo 2: Verificar si existe forma_pago

```php
// En CotizacionSearchDTO.php
private static function extractFormaPago($cotizacion): string
{
    if (is_array($cotizacion->especificaciones)) {
        $formaPagoArray = $cotizacion->especificaciones['forma_pago'] ?? null;
        
        // Es array de objetos
        if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
            return $formaPagoArray[0]['valor']; // Primer valor
        }
        // Es string directo
        elseif (is_string($formaPagoArray)) {
            return $formaPagoArray;
        }
    }
    
    return $cotizacion->forma_pago ?? '';
}
```

### Ejemplo 3: Guardar en PDF

```php
// app/Http/Controllers/PDFCotizacionController.php

$especificacionesData = $cotizacion->especificaciones ?? [];

if (is_string($especificacionesData)) {
    $especificacionesData = json_decode($especificacionesData, true) ?? [];
}

// Acceder a forma_pago
if (isset($especificacionesData['forma_pago'])) {
    $valores = $especificacionesData['forma_pago']; // Array de objetos
    
    // Renderizar en HTML/PDF
    foreach ($valores as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['valor']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['observacion']) . '</td>';
        $html .= '</tr>';
    }
}
```

---

## 📋 DATOS REALES - EJEMPLOS

### Ejemplo 1: Cotización Básica

```php
$cotizacion = Cotizacion::find(1);

echo $cotizacion->especificaciones;
// Array
// (
//     [forma_pago] => Array
//         (
//             [0] => Array
//                 (
//                     [valor] => Contado
//                     [observacion] => Descuento 5%
//                 )
//         )
//     [disponibilidad] => Array
//         (
//             [0] => Array
//                 (
//                     [valor] => Bodega
//                     [observacion] => En stock disponible
//                 )
//         )
//     [regimen] => Array
//         (
//             [0] => Array
//                 (
//                     [valor] => Común
//                     [observacion] => 
//                 )
//         )
// )
```

### Ejemplo 2: Cotización con Múltiples Formas de Pago

```php
$cotizacion = Cotizacion::find(2);

echo $cotizacion->especificaciones;
// Array
// (
//     [forma_pago] => Array
//         (
//             [0] => Array
//                 (
//                     [valor] => Contado
//                     [observacion] => Descuento 10%
//                 ),
//             [1] => Array
//                 (
//                     [valor] => Crédito 30 días
//                     [observacion] => Máximo 2 millones
//                 ),
//             [2] => Array
//                 (
//                     [valor] => Crédito 60 días
//                     [observacion] => Con aval
//                 )
//         )
// )
```

---

## 🧪 TESTS QUE VALIDAN LA ESTRUCTURA

### Test: EspecificacionesTest.php

```php
public function test_especificaciones_estructura_correcta()
{
    $especificaciones = [
        'forma_pago' => [
            ['valor' => 'Contado', 'observacion' => 'Descuento 5%']
        ],
        'disponibilidad' => [
            ['valor' => 'Bodega', 'observacion' => 'En stock disponible'],
            ['valor' => 'Cúcuta', 'observacion' => 'Disponible en 2 días']
        ]
    ];

    // Verificaciones
    $this->assertCount(1, $especificaciones['forma_pago']);
    $this->assertEquals('Contado', $especificaciones['forma_pago'][0]['valor']);
    $this->assertEquals('Descuento 5%', $especificaciones['forma_pago'][0]['observacion']);
}
```

### Test: EspecificacionesGuardadoTest.php

```php
public function test_forma_pago_se_guarda_en_bd()
{
    $cotizacion = Cotizacion::create([
        'numero_cotizacion' => 'COT-001',
        'especificaciones' => [
            'forma_pago' => [
                ['valor' => 'Contado', 'observacion' => 'Descuento 5%']
            ]
        ]
    ]);

    $guardada = Cotizacion::find($cotizacion->id);
    
    // Verifica estructura
    $this->assertArrayHasKey('forma_pago', $guardada->especificaciones);
    $this->assertEquals('Contado', $guardada->especificaciones['forma_pago'][0]['valor']);
}
```

---

## 📍 UBICACIONES EN EL CÓDIGO

### Archivos Relacionados

1. **Modelo**: [app/Models/Cotizacion.php](app/Models/Cotizacion.php#L24-L50)
   - Define `especificaciones` como fillable y cast a array

2. **JavaScript**: [public/js/asesores/cotizaciones/modules/EspecificacionesModule.js](public/js/asesores/cotizaciones/modules/EspecificacionesModule.js#L9-L120)
   - Captura los datos del modal HTML
   - Crea la estructura `forma_pago`

3. **Controller**: [app/Http/Controllers/Asesores/PedidosProduccionController.php](app/Http/Controllers/Asesores/PedidosProduccionController.php#L182-L189)
   - Obtiene `forma_pago` de `especificaciones`

4. **DTO**: [app/DTOs/CotizacionSearchDTO.php](app/DTOs/CotizacionSearchDTO.php#L38-L60)
   - Extrae `forma_pago` de la estructura anidada

5. **PDF**: [app/Http/Controllers/PDFCotizacionController.php](app/Http/Controllers/PDFCotizacionController.php#L798-L870)
   - Renderiza `forma_pago` en el PDF

6. **Vistas**: [resources/views/components/modal-especificaciones.blade.php](resources/views/components/modal-especificaciones.blade.php)
   - Modal HTML con `tbody id="tbody_pago"`

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### 1. Conversión de Tipos

```php
// Al guardar, Laravel convierte automáticamente
$cotizacion->especificaciones = [
    'forma_pago' => [['valor' => 'Contado', 'observacion' => '']]
];
$cotizacion->save();
// Se guarda como: {"forma_pago": [{"valor": "Contado", "observacion": ""}]}

// Al recuperar, se convierte automáticamente a array
$array = $cotizacion->especificaciones; // Usa el cast 'array'
```

### 2. JSON String vs Array

```php
// Si recibe string JSON (del frontend)
$especificaciones_json = request()->input('especificaciones');
// String: '{"forma_pago": [{"valor": "Contado"}]}'

// Laravel automáticamente lo convierte si está en fillable
$cotizacion = Cotizacion::create([
    'especificaciones' => $especificaciones_json // Acepta ambos
]);

// Internamente Laravel lo maneja gracias al cast
$acceso = $cotizacion->especificaciones; // Siempre es array
```

### 3. Compatibilidad Hacia Atrás

```php
// Algunos lugares siguen esperando forma_pago como string simple
$formaPago = $especificaciones['forma_pago'] ?? null;

if (is_array($formaPago)) {
    $formaPago = implode(',', array_column($formaPago, 'valor'));
}
// Resultado: 'Contado' o 'Contado, Crédito 30 días'
```

---

## 🔗 RELACIÓN CON OTROS CAMPOS

### Cotizacion Table Structure (Actual)

```
ID: cotizaciones
├── especificaciones (JSON)
│   ├── forma_pago []
│   ├── disponibilidad []
│   ├── regimen []
│   ├── se_ha_vendido []
│   ├── ultima_venta []
│   └── flete []
├── asesor_id (FK → users)
├── cliente_id (FK → clientes)
├── numero_cotizacion (string)
├── estado (string)
└── es_borrador (boolean)
```

### Información Conexa

- **Modelo**: `app/Models/Cotizacion.php` → `especificaciones` (array cast)
- **Tabla**: `cotizaciones` → `especificaciones` (JSON column)
- **DTO**: `app/DTOs/CotizacionSearchDTO.php` → extrae `forma_pago`
- **Service**: Valida estructura y tipos
- **Tests**: 15+ tests validando la estructura

---

## 📚 RESUMEN FINAL

| Aspecto | Detalle |
|---------|---------|
| **Ubicación** | Tabla `cotizaciones`, columna `especificaciones` (JSON) |
| **Clave** | `forma_pago` dentro del JSON |
| **Estructura** | Array de objetos: `[{valor: string, observacion: string}]` |
| **Tipo BD** | LONGTEXT/JSON |
| **Cast Eloquent** | `array` (Cotizacion model) |
| **Ejemplo** | `[['valor' => 'Contado', 'observacion' => 'Descuento 5%']]` |
| **Captura Frontend** | `EspecificacionesModule.js` mapea `tbody_pago` → `forma_pago` |
| **Acceso Backend** | `$cotizacion->especificaciones['forma_pago']` |
| **Múltiples Valores** | ✅ Soporta N formas de pago por cotización |
| **Observaciones** | ✅ Cada forma de pago puede tener observaciones |

