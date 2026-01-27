# 📖 GUÍA RÁPIDA: USAR LA EDICIÓN SEGURA DE PRENDAS

**Fecha:** 27 de enero de 2026  
**Propósito:** Ejemplos prácticos para integración en frontend y backend

---

## 🚀 Inicio Rápido

### 1. Editar nombre de prenda (más simple)

```javascript
// Frontend
async function editarNombrePrenda(prendaId, nuevoNombre) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/campos`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre_prenda: nuevoNombre
            })
        }
    );
    return response.json();
}

// Uso
editarNombrePrenda(42, "CAMISA NUEVA");
```

**Resultado:**
```json
{
    "success": true,
    "message": "Prenda actualizada exitosamente",
    "prenda_id": 42,
    "fields_updated": ["nombre_prenda"]
}
```

---

### 2. Editar cantidad con validación automática

```javascript
async function editarCantidad(prendaId, nuevaCantidad) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/campos`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cantidad: nuevaCantidad
            })
        }
    );
    
    if (!response.ok) {
        const error = await response.json();
        console.error("Error:", error.errors?.cantidad[0]);
        return null;
    }
    
    return response.json();
}

// Uso
const resultado = await editarCantidad(42, 80);
if (resultado) {
    console.log("✅ Cantidad actualizada");
} else {
    console.log("❌ Cantidad no válida (procesos asignados)");
}
```

---

### 3. Agregar talla a prenda (MERGE)

```javascript
async function agregarTalla(prendaId) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/tallas`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tallas: [
                    {
                        genero: "dama",
                        talla: "XL",
                        cantidad: 15
                    }
                ]
            })
        }
    );
    return response.json();
}
```

**Garantía:** Las tallas existentes se conservan, solo se agrega la nueva.

---

### 4. Actualizar talla existente (MERGE)

```javascript
async function actualizarTalla(prendaId, tallaId, nuevaCantidad) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/tallas`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tallas: [
                    {
                        id: tallaId,
                        cantidad: nuevaCantidad
                    }
                ]
            })
        }
    );
    return response.json();
}
```

**Nota:** Si no tiene procesos asignados, se actualiza. Si tiene, valida cantidad mínima.

---

### 5. Editar variante (solo campos)

```javascript
async function editarVarianteCampos(prendaId, varianteId) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/variantes/${varianteId}/editar/campos`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_manga_id: 2,
                tiene_bolsillos: true,
                obs_bolsillos: "Un bolsillo en el pecho"
            })
        }
    );
    return response.json();
}
```

---

### 6. MERGE de colores en variante

```javascript
async function actualizarColoresVariante(prendaId, varianteId) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/variantes/${varianteId}/colores`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                colores: [
                    {
                        id: 5,           // ← Existe, se actualiza
                        color_id: 10     // Cambiar de color_id 8 a 10
                    },
                    {
                        color_id: 12     // ← Nuevo, se crea
                    }
                ]
            })
        }
    );
    return response.json();
}
```

**Resultado:**
- Color con id 5 → Actualizado a color_id 10
- Colores existentes no mencionados → Conservados
- Color_id 12 → Creado nuevo registro

---

### 7. Obtener estado actual (para auditoría)

```javascript
async function obtenerEstadoPrenda(prendaId) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/estado`,
        {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        }
    );
    const data = await response.json();
    console.log(data.data);
}

// Resultado
{
    id: 42,
    nombre_prenda: "CAMISA POLO",
    descripcion: "Camisa casual",
    cantidad: 100,
    de_bodega: false,
    tallas_count: 3,
    variantes_count: 2,
    procesos_count: 5
}
```

---

## 🔄 Flujos Completos

### Flujo 1: Edición Simple (Nombre + Cantidad)

```javascript
async function editarPrendaSimple(prendaId, nuevoNombre, nuevaCantidad) {
    // Opción A: Dos llamadas separadas (recomendado para UI feedback)
    
    // 1. Editar nombre
    const result1 = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/campos`,
        {
            method: 'PATCH',
            body: JSON.stringify({ nombre_prenda: nuevoNombre })
        }
    ).then(r => r.json());
    
    if (!result1.success) {
        console.error("Error nombre:", result1.errors);
        return false;
    }
    
    // 2. Editar cantidad
    const result2 = await fetch(
        `/api/prendas-pedido/${prendaId}/editar/campos`,
        {
            method: 'PATCH',
            body: JSON.stringify({ cantidad: nuevaCantidad })
        }
    ).then(r => r.json());
    
    if (!result2.success) {
        console.error("Error cantidad:", result2.errors);
        return false;
    }
    
    console.log("✅ Prenda actualizada");
    return true;
    
    // Opción B: Una sola llamada (si ambos cambios van juntos)
    // Usar: /api/prendas-pedido/{id}/editar
}
```

---

### Flujo 2: Edición Compleja (Variantes + Tallas + Colores)

```javascript
async function editarPrendaCompleta(prendaId, cambios) {
    const response = await fetch(
        `/api/prendas-pedido/${prendaId}/editar`,
        {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                nombre_prenda: cambios.nombre,
                cantidad: cambios.cantidad,
                tallas: cambios.tallas,           // MERGE
                variantes: cambios.variantes      // MERGE
            })
        }
    );
    
    if (!response.ok) {
        const error = await response.json();
        console.error("Error:", error.errors);
        return null;
    }
    
    return response.json();
}

// Uso
const cambios = {
    nombre: "NUEVA CAMISA",
    cantidad: 120,
    tallas: [
        { id: 1, cantidad: 50 },
        { genero: "caballero", talla: "L", cantidad: 20 }
    ],
    variantes: [
        {
            id: 1,
            tipo_manga_id: 2,
            colores: [
                { id: 5, color_id: 3 },
                { color_id: 7 }
            ]
        }
    ]
};

const resultado = await editarPrendaCompleta(42, cambios);
```

---

## ✋ Casos de Error

### Error 1: Reducir cantidad por debajo de procesos

```http
PATCH /api/prendas-pedido/42/editar/campos

{
  "cantidad": 40
}
```

**Respuesta 422:**
```json
{
    "success": false,
    "errors": {
        "cantidad": ["No se puede reducir la cantidad por debajo de 50 (cantidad ya usada en procesos). Cantidad actual: 100, Nueva: 40"]
    }
}
```

**Solución:** Aumentar cantidad a valor mayor o igual a 50.

---

### Error 2: Intentar editar procesos

```http
PATCH /api/prendas-pedido/42/editar

{
  "procesos": [...]  // ❌ NO permitido desde aquí
}
```

**Respuesta 422:**
```json
{
    "success": false,
    "errors": {
        "procesos": ["Los procesos no pueden editarse desde este endpoint. Use el endpoint de procesos."]
    }
}
```

---

### Error 3: Talla no existe (UPDATE fallido)

```http
PATCH /api/prendas-pedido/42/editar/tallas

{
  "tallas": [
    {
      "id": 999,        // ← No existe
      "cantidad": 30
    }
  ]
}
```

**Comportamiento:** El UPDATE simplemente no ocurre (la talla no se encuentra). No es error.

---

## 📋 Checklist para Frontend

- ✅ Usar PATCH, no PUT
- ✅ Enviar solo campos que cambian
- ✅ Manejar respuestas 422 (validación)
- ✅ Mostrar errores en UI cuando sea necesario
- ✅ Usar GET para obtener estado actual (si lo necesita)
- ✅ No mezclar creación con edición (son flujos separados)
- ✅ Usar `id` en arrays para UPDATE vs sin `id` para CREATE

---

## 🛠️ Uso en Backend (PHP)

### Inyectar Service

```php
// En controlador
public function __construct(
    PrendaPedidoEditService $editService
) {
    $this->editService = $editService;
}

// Usar el servicio
public function miMetodo() {
    $prenda = PrendaPedido::find(42);
    
    $dto = new EditPrendaPedidoDTO(
        nombre_prenda: "Nuevo nombre"
    );
    
    $resultado = $this->editService->edit($prenda, $dto);
    return response()->json($resultado);
}
```

### Validación personalizada

```php
use App\Infrastructure\Services\Validators\PrendaEditSecurityValidator;

$prenda = PrendaPedido::find(42);
$dto = EditPrendaPedidoDTO::fromPayload($request->all());

try {
    PrendaEditSecurityValidator::validateEdit($prenda, $dto);
    // Proceder
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
}
```

---

## 🧪 Tests Recomendados

```php
// Test: Actualizar nombre conserva cantidad
test('puede actualizar nombre sin afectar cantidad')
    ->prenda(cantidad: 100)
    ->patch('/api/prendas-pedido/1/editar/campos', 
        ['nombre_prenda' => 'NUEVO'])
    ->assertJsonPath('success', true)
    ->assertDatabaseHas('prendas_pedido', [
        'id' => 1,
        'nombre_prenda' => 'NUEVO',
        'cantidad' => 100  // ← Sin cambios
    ]);

// Test: MERGE conserva tallas no mencionadas
test('merge de tallas conserva existentes no mencionadas')
    ->prenda(id: 1)
    ->withTallas([
        ['id' => 1, 'talla' => 'M'],
        ['id' => 2, 'talla' => 'L']
    ])
    ->patch('/api/prendas-pedido/1/editar/tallas', [
        'tallas' => [
            ['id' => 1, 'cantidad' => 50]
        ]
    ])
    ->assertDatabaseHas('prenda_pedido_tallas', ['id' => 2]); // ← Conservada

// Test: Reduce cantidad con procesos = ERROR
test('no puede reducir cantidad por debajo de procesos')
    ->prenda(id: 1, cantidad: 100)
    ->withProcesos(cantidad_total: 80)
    ->patch('/api/prendas-pedido/1/editar/campos',
        ['cantidad' => 70])
    ->assertStatus(422)
    ->assertJsonPath('errors.cantidad.0', 
        'No se puede reducir...');
```

---

## 📞 Troubleshooting

### P: ¿Cómo sé si una relación se conservó?
R: Use `GET /api/prendas-pedido/{id}/estado` antes y después.

### P: ¿Puedo editar sin mencionar un campo?
R: Sí, solo edita lo que menciones. El resto se conserva.

### P: ¿Cómo agrego una talla SIN actualizar la existente?
R: Envíe solo la nueva (sin `id`):
```json
{
  "tallas": [
    {"genero": "dama", "talla": "XL", "cantidad": 10}
  ]
}
```

### P: ¿Qué pasa si el ID de talla no existe?
R: Se ignora silenciosamente (no error).

### P: ¿Puedo editar procesos desde aquí?
R: No, está prohibido. Use `/api/procesos/{id}/editar`.

---

**Fin de Guía Rápida**
