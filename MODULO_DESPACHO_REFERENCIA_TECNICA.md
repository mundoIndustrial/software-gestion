# 🔨 REFERENCIA TÉCNICA DESPACHO

## Modelos

### PedidoProduccion - Métodos nuevos

```php
/**
 * Obtener todas las filas de despacho (prendas + EPP)
 * @return \Illuminate\Support\Collection
 */
public function getFilasDespacho()

/**
 * Obtener solo prendas con tallas
 * @return \Illuminate\Database\Eloquent\Collection
 */
public function getPrendasParaDespacho()

/**
 * Obtener solo EPP
 * @return \Illuminate\Database\Eloquent\Collection
 */
public function getEppParaDespacho()
```

### PrendaPedido - Nuevo alias

```php
/**
 * Alias para compatibilidad con getFilasDespacho()
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function prendaPedidoTallas(): HasMany
{
    return $this->tallas();
}
```

---

## Controlador

**Archivo:** `app/Http/Controllers/DespachoController.php`

### Métodos públicos

```php
// Listar pedidos
public function index()

// Ver despacho de un pedido
public function show(PedidoProduccion $pedido)

// Guardar despacho (POST)
public function guardarDespacho(Request $request, PedidoProduccion $pedido)

// Vista de impresión
public function printDespacho(PedidoProduccion $pedido)
```

### Métodos privados

```php
// Guardar despacho de prenda
private function guardarDespachoPrenda(array $despacho)

// Guardar despacho de EPP
private function guardarDespachoEpp(array $despacho)
```

---

## Rutas

**Archivo:** `routes/despacho.php`

```php
GET    /despacho                    → index
GET    /despacho/{pedido}           → show
POST   /despacho/{pedido}/guardar   → guardarDespacho
GET    /despacho/{pedido}/print     → printDespacho
```

**Nombres:**
- `despacho.index`
- `despacho.show`
- `despacho.guardar`
- `despacho.print`

---

## Vistas

| Vista | Ubicación | Responsabilidad |
|-------|-----------|-----------------|
| index | `despacho/index.blade.php` | Lista de pedidos |
| show | `despacho/show.blade.php` | Tabla interactiva |
| print | `despacho/print.blade.php` | Documento de impresión |

---

## JavaScript (en show.blade.php)

### Funciones globales

```javascript
// Calcular pendientes al cambiar un input
function calcularPendientes(event)

// Guardar despacho al servidor
async function guardarDespacho()
```

### Selectores clave

```css
#formDespacho          /* Formulario principal */
#tablaDespacho         /* Body de la tabla */
.parcial-input         /* Inputs de parciales */
.parcial-1/2/3         /* Inputs específicos */
.pendiente-inicial     /* Elementos de pendiente inicial */
.pendiente-1/2/3       /* Elementos de pendientes */
tr[data-tipo]          /* Filas de ítem */
```

### Atributos data

```html
<tr data-tipo="prenda|epp"
    data-id="1"
    data-talla-id="5"
    data-cantidad="50">
```

---

## Estructura de datos

### Fila de despacho

```php
// Tipo: Prenda con talla
[
    'tipo' => 'prenda',
    'id' => 1,                          // prenda_pedido.id
    'talla_id' => 5,                    // prenda_pedido_tallas.id
    'descripcion' => 'Polo - Hombre',
    'cantidad_total' => 50,
    'talla' => 'XL',
    'genero' => 'Hombre',
    'objeto_prenda' => PrendaPedido,
    'objeto_talla' => PrendaPedidoTalla,
    'objeto_epp' => null,
]

// Tipo: EPP
[
    'tipo' => 'epp',
    'id' => 2,                          // pedido_epp.id
    'talla_id' => null,
    'descripcion' => 'Casco (CASCO-001)',
    'cantidad_total' => 10,
    'talla' => '—',
    'genero' => null,
    'objeto_prenda' => null,
    'objeto_talla' => null,
    'objeto_epp' => PedidoEpp,
]
```

### Request POST /despacho/{id}/guardar

```php
// Validación
[
    'fecha_hora' => 'nullable|date',
    'cliente_empresa' => 'nullable|string|max:255',
    'despachos' => 'required|array',
    'despachos.*.tipo' => 'required|in:prenda,epp',
    'despachos.*.id' => 'required|integer',
    'despachos.*.parcial_1' => 'nullable|integer|min:0',
    'despachos.*.parcial_2' => 'nullable|integer|min:0',
    'despachos.*.parcial_3' => 'nullable|integer|min:0',
]
```

### Response JSON

```json
{
  "success": true|false,
  "message": "Descripción",
  "pedido_id": 123,
  "errors": {} // Si hay validación
}
```

---

## Cálculos

### Pendiente automático

```javascript
const cantidadTotal = 50;
const parcial1 = 10;
const parcial2 = 5;
const parcial3 = 0;

const p1 = cantidadTotal - parcial1;      // 40
const p2 = p1 - parcial2;                  // 35
const p3 = Math.max(0, p2 - parcial3);     // 35
```

### Validaciones

```javascript
// No negativo
if (valor < 0) valor = 0;

// No exceder cantidad
if (valor > cantidadTotal) valor = cantidadTotal;

// Al menos un parcial antes de guardar
if (total parciales === 0) error();
```

---

## Consultas Eloquent

### Cargar pedido con todo

```php
$pedido = PedidoProduccion::with([
    'prendas.prendaPedidoTallas',
    'epps.epp',
    'epps.imagenes',
])->find($id);
```

### Filtrar pedidos para despacho

```php
$pedidos = PedidoProduccion::where('estado', '!=', 'Anulada')
    ->orderBy('fecha_de_creacion_de_orden', 'desc')
    ->paginate(15);
```

### Obtener prendas con tallas

```php
$prendas = $pedido->prendas()
    ->with('prendaPedidoTallas')
    ->get();
```

### Obtener EPP

```php
$epps = $pedido->epps()
    ->with(['epp', 'imagenes'])
    ->get();
```

---

## CSS TailwindCSS

### Clases aplicadas

```
Colores:
- Prendas: bg-blue-50, text-blue-900, border-blue-300
- EPP: bg-green-50, text-green-900, border-green-300

Inputs:
- focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200

Tablas:
- bg-slate-100, border-slate-300, divide-slate-200
- hover:bg-slate-50

Completado:
- bg-green-100 (cuando pendiente final = 0)
```

---

## Ciclo de vida

```
1. Usuario va a /despacho
2. DespachoController::index() → index.blade.php
3. Usuario click en pedido
4. GET /despacho/{id}
5. DespachoController::show() → show.blade.php
6. JavaScript carga y espera input
7. Usuario ingresa parciales
8. calcularPendientes() se dispara en cada cambio
9. Usuario hace click guardar
10. guardarDespacho() recoge datos
11. POST /despacho/{id}/guardar
12. DespachoController::guardarDespacho() valida
13. Guarda en logs/auditoría
14. Responde JSON success
15. JavaScript recarga página
```

---

## Debugging

### Logs importantes

```
storage/logs/laravel.log

Buscar:
- "Despacho prenda"
- "Despacho EPP"
- "Error al guardar despacho"
```

### Consultas útiles en tinker

```php
php artisan tinker

$pedido = PedidoProduccion::find(1);
$filas = $pedido->getFilasDespacho();
$filas->count(); // Cuántos ítems
$filas->where('tipo', 'prenda')->count(); // Cuántas prendas
$filas->where('tipo', 'epp')->count(); // Cuántos EPP
```

---

## Extensiones posibles

### Guardar histórico de despachos

```php
// Crear tabla: despacho_historico
// Campos: id, pedido_epp_id|prenda_pedido_talla_id, parcial_1/2/3, fecha_creacion

// En guardarDespacho():
foreach ($validated['despachos'] as $d) {
    DespachoHistorico::create([
        'pedido_produccion_id' => $pedido->id,
        'tipo' => $d['tipo'],
        'item_id' => $d['id'],
        'parcial_1' => $d['parcial_1'],
        'parcial_2' => $d['parcial_2'],
        'parcial_3' => $d['parcial_3'],
    ]);
}
```

### Generar PDF

```php
// En show.blade.php: usar librería TCPDF o Dompdf
// Hacer PDF desde print.blade.php

public function pdfDespacho(PedidoProduccion $pedido) {
    $filas = $pedido->getFilasDespacho();
    $pdf = new Pdf();
    // ... generar
    return $pdf->download("despacho-{$pedido->numero_pedido}.pdf");
}
```

### API REST

```php
// Agregar a routes/api.php
Route::get('/despacho/{pedido}/filas', function(PedidoProduccion $pedido) {
    return response()->json([
        'filas' => $pedido->getFilasDespacho(),
    ]);
});
```

---

## Testing

### Prueba controlador

```php
// tests/Feature/DespachoControllerTest.php

public function test_index_muestra_pedidos() {
    $response = $this->get('/despacho');
    $response->assertStatus(200);
}

public function test_show_carga_filas_correctamente() {
    $pedido = PedidoProduccion::factory()->create();
    $response = $this->get("/despacho/{$pedido->id}");
    $response->assertStatus(200);
}

public function test_guardar_despacho_valida() {
    $pedido = PedidoProduccion::factory()->create();
    $response = $this->post("/despacho/{$pedido->id}/guardar", [
        'despachos' => [],
    ]);
    $response->assertStatus(422); // Sin parciales
}
```

---

**Última actualización:** 23 de enero de 2026
