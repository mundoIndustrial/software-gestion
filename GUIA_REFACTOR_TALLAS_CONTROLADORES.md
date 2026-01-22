#  GUÍA: Refactorizar Métodos de Controladores

**Referencia:** `GestionaTallasRelacional` trait  
**Patrón:** Inyectar repositorio, usar métodos del trait

---

## 📝 PATRÓN GENERAL

### Inyectar el repositorio en el controlador
```php
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class PedidosProduccionController extends Controller
{
    public function __construct(
        private PedidoProduccionRepository $prendaPedidoRepository
    ) {}
}
```

---

## 1️⃣ REFACTORIZAR: `agregarPrendaCompleta()`

**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (línea ~720)

### ANTES ( Incorrecto)
```php
// Construir datos de la prenda para el comando
$prendaData = [
    'nombre_prenda' => $validated['nombre_prenda'],
    'descripcion' => $validated['descripcion'] ?? '',
    'origen' => $validated['origen'],
    'imagenes' => $imagenesGuardadas,
    'telas' => $telasGuardadas,
    'cantidad_talla' => $validated['cantidad_talla'] 
        ? json_decode($validated['cantidad_talla'], true) 
        : [],
    'procesos' => $validated['procesos'] ? json_decode($validated['procesos'], true) : [],
    'novedad' => $validated['novedad'],
    'cantidad' => 1,
    'tipo_manga' => null,
    'tipo_broche' => null,
    'color_id' => null,
    'tela_id' => null,
];

// ... Guardar comando
$prendaGuardada = $this->commandBus->execute(new AgregarPrendaAlPedidoCommand(...));
```

### DESPUÉS ( Correcto)
```php
// Construir datos de la prenda para el comando
$prendaData = [
    'nombre_prenda' => $validated['nombre_prenda'],
    'descripcion' => $validated['descripcion'] ?? '',
    'origen' => $validated['origen'],
    'imagenes' => $imagenesGuardadas,
    // telas, cantidad_talla NO van aquí
    'procesos' => $validated['procesos'] ? json_decode($validated['procesos'], true) : [],
    'novedad' => $validated['novedad'],
    'cantidad' => 1,
    'tipo_manga' => null,
    'tipo_broche' => null,
    'color_id' => null,
    'tela_id' => null,
];

// Guardar prenda
$prendaGuardada = $this->commandBus->execute(new AgregarPrendaAlPedidoCommand(...));

// LUEGO: Guardar tallas por separado
if ($prendaGuardada && !empty($validated['cantidad_talla'])) {
    $this->prendaPedidoRepository->guardarTallasDesdeJson(
        $prendaGuardada->id,
        $validated['cantidad_talla']
    );
}

// LUEGO: Guardar imágenes en prenda_fotos_pedido (ya existe)
// ... código de imágenes
```

---

## 2️⃣ REFACTORIZAR: `actualizarPrendaCompleta()`

**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (línea ~900)

### ANTES ( Incorrecto)
```php
// Actualizar campos de la prenda (SOLO las columnas que existen)
$prenda->nombre_prenda = $validated['nombre_prenda'];
$prenda->descripcion = $validated['descripcion'] ?? '';
$prenda->cantidad_talla = $validated['cantidad_talla'] 
    ? json_decode($validated['cantidad_talla'], true) 
    : [];
$prenda->save();
```

### DESPUÉS ( Correcto)
```php
// Actualizar SOLO campos de prendas_pedido (sin cantidad_talla JSON)
$prenda->nombre_prenda = $validated['nombre_prenda'];
$prenda->descripcion = $validated['descripcion'] ?? '';
$prenda->save();

// LUEGO: Guardar tallas en tabla relacional
if (!empty($validated['cantidad_talla'])) {
    $this->prendaPedidoRepository->guardarTallasDesdeJson(
        $validated['prenda_id'],
        $validated['cantidad_talla']
    );
}

// Imágenes ya están implementadas correctamente
```

---

## 3️⃣ REFACTORIZAR: `obtenerDatosUnaPrenda()`

**Ubicación:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (línea ~421)

### ANTES ( Incorrecto)
```php
$tallas = [];
if ($prenda->cantidad_talla) {
    if (is_array($prenda->cantidad_talla)) {
        $tallas = $prenda->cantidad_talla;
    } else if (is_string($prenda->cantidad_talla)) {
        $tallas = json_decode($prenda->cantidad_talla, true) ?? [];
    }
}

return response()->json([
    'success' => true,
    'data' => [
        'nombre_prenda' => $prenda->nombre_prenda,
        'cantidad_talla' => $tallas,  //  JSON
        // ... otros datos
    ]
]);
```

### DESPUÉS ( Correcto)
```php
// Cargar tallas desde tabla relacional
$tallas = $this->prendaPedidoRepository->obtenerTallas($prenda->id);

return response()->json([
    'success' => true,
    'data' => [
        'nombre_prenda' => $prenda->nombre_prenda,
        'tallas' => $tallas,  //  Array estructurado
        // ... otros datos
    ]
]);
```

---

## 4️⃣ REFACTORIZAR: `loadPrendas()` en Repository

**Ubicación:** `app/Domain/PedidoProduccion/Repositories/PedidoProduccionRepository.php` (línea ~261)

### ANTES ( Incorrecto)
```php
// Tallas desde JSON (cantidad_talla es string JSON en la BD)
$tallas = [];
if ($prenda->cantidad_talla) {
    if (is_array($prenda->cantidad_talla)) {
        $tallas = $prenda->cantidad_talla;
    } else if (is_string($prenda->cantidad_talla)) {
        $tallas = json_decode($prenda->cantidad_talla, true) ?? [];
    }
}

'tallas' => $tallas,
'cantidad_talla_raw' => $prenda->cantidad_talla,
```

### DESPUÉS ( Correcto)
```php
// Tallas desde tabla relacional (ya cargadas en relación)
$tallas = $this->obtenerTallas($prenda->id);

'tallas' => $tallas,
// cantidad_talla_raw NO va aquí
```

---

## 5️⃣ REFACTORIZAR: Views/Blade Templates

**Archivos afectados:**
- `resources/views/components/invoice-factura.blade.php`
- `resources/views/vistas/index.blade.php`
- `resources/views/supervisor-asesores/pedidos/index.blade.php`
- `resources/views/orders/index.blade.php`

### ANTES ( Incorrecto)
```blade
@if($prenda->cantidad_talla)
    @php
        $tallas = is_string($prenda->cantidad_talla) 
            ? json_decode($prenda->cantidad_talla, true) 
            : $prenda->cantidad_talla;
    @endphp
    @foreach($tallas as $talla => $cantidad)
        <span>{{ $talla }}: {{ $cantidad }}</span>
    @endforeach
@endif
```

### DESPUÉS ( Correcto)
```blade
@if($prenda->tallas->count() > 0)
    @foreach($prenda->tallas as $tallaRecord)
        <span>
            {{ $tallaRecord->genero }}-{{ $tallaRecord->talla }}: 
            {{ $tallaRecord->cantidad }}
        </span>
    @endforeach
@endif
```

---

## 6️⃣ REFACTORIZAR: JavaScript

**Archivos afectados:**
- `public/js/modulos/crear-pedido/procesos/services/prenda-editor.js`
- `public/js/orders\ js/order-detail-modal-manager.js`
- `public/js/orders\ js/modules/cellEditModal.js`

### ANTES ( Incorrecto)
```javascript
// Intentar cargar desde cantidad_talla (prendas de BD - estructura JSON)
else if (prenda.cantidad_talla) {
    let cantidadTalla = prenda.cantidad_talla;
    
    if (typeof cantidadTalla === 'string') {
        cantidadTalla = JSON.parse(cantidadTalla);
    }
    
    // Procesar cantidad_talla
    Object.entries(cantidadTalla).forEach(([generoTalla, cantidad]) => {
        // ... procesamiento complejo
    });
}
```

### DESPUÉS ( Correcto)
```javascript
// Cargar desde array estructurado de tallas
else if (prenda.tallas && Array.isArray(prenda.tallas)) {
    prenda.tallas.forEach(tallaRecord => {
        const genero = tallaRecord.genero;
        const talla = tallaRecord.talla;
        const cantidad = tallaRecord.cantidad;
        
        // ... procesamiento simple
    });
}
```

---

##  CHECKLIST DE REFACTORIZACIÓN

### Por cada método refactorizado:
- [ ] Remover parseo JSON de `cantidad_talla`
- [ ] Usar método del trait (`guardarTallas`, `obtenerTallas`, etc.)
- [ ] No guardar JSON en `prendas_pedido.cantidad_talla`
- [ ] Guardar datos en `prenda_pedido_tallas`
- [ ] Responder con array estructurado (no JSON string)
- [ ] Validar sintaxis PHP
- [ ] Testear endpoint con curl
- [ ] Verificar BD para datos correctos

---

## 🧪 TESTING

### Verificar Datos en BD
```sql
-- Ver tallas guardadas
SELECT * FROM prenda_pedido_tallas WHERE prenda_pedido_id = 3418;

-- Ver estructura
DESC prenda_pedido_tallas;

-- Contar por género
SELECT genero, COUNT(*) as cantidad_tallas 
FROM prenda_pedido_tallas 
GROUP BY genero;
```

### Curl Test
```bash
curl -X POST http://localhost:8000/asesores/pedidos/123/actualizar-prenda \
  -H "Content-Type: application/json" \
  -d '{
    "prenda_id": 456,
    "nombre_prenda": "Camisa",
    "cantidad_talla": "{\"DAMA\":{\"M\":10,\"L\":20}}",
    "novedad": "Prueba",
    "origen": "bodega"
  }'
```

### Verificar Respuesta
```json
{
  "success": true,
  "data": {
    "nombre_prenda": "Camisa",
    "tallas": {
      "DAMA": {
        "M": 10,
        "L": 20
      }
    }
  }
}
```

---

##  GARANTÍAS

```
 NUNCA se guardará cantidad_talla JSON en prendas_pedido
 SIEMPRE se guardará en prenda_pedido_tallas
 NUNCA se parsea JSON en vistas
 SIEMPRE se usa relación de Eloquent
```

**Status:**  REFACTORIZACIÓN LISTA PARA IMPLEMENTAR

