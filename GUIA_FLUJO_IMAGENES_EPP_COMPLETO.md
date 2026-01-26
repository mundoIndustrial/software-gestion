# 📋 GUÍA COMPLETA: Flujo de Imágenes de EPP

**Versión:** 1.0  
**Fecha:** 26 de Enero de 2026  
**Estado:** Documentación de referencia para el flujo completo

---

## 🎯 Resumen Ejecutivo

El sistema debe garantizar que:
1. ✅ Las imágenes de EPP se guardan **físicamente** en `storage/pedido/{pedido_id}/epp/`
2. ✅ Las **rutas** se registran en tabla `pedido_epp_imagenes`
3. ✅ Se soportan **Files nuevos** (formulario) y **rutas existentes** (edición)
4. ✅ **NO** se usa base64
5. ✅ **NO** se crean subcarpetas adicionales
6. ✅ **NO** se borran imágenes automáticamente en edición

---

## 📁 Estructura de Almacenamiento

### Creación del Pedido

```
Paso 1: Crear pedido en BD
│
├─ INSERT INTO pedido_produccions (numero_pedido, cliente, ...) 
│
├─ Obtener pedido_id = 2718
│
└─ CREAR CARPETA FÍSICA: storage/app/public/pedido/2718/
   │
   ├─ prendas/          (imágenes de prendas)
   ├─ telas/            (imágenes de telas)
   ├─ procesos/         (imágenes de procesos)
   └─ epp/              (imágenes de EPP) ← AQUÍ VAN LAS IMÁGENES DE EPP
```

### Rutas de Almacenamiento

**Path físico:**
```
C:\xampp\htdocs\proyecto\storage\app\public\pedido\{pedido_id}\epp\
```

**URL web:**
```
http://localhost/storage/pedido/{pedido_id}/epp/imagen.jpg
```

**Almacenado en BD (pedido_epp_imagenes):**
```
pedido/2718/epp/imagen.jpg  ← Ruta relativa a storage/app/public/
```

---

## 🔄 Flujos Detallados

### Flujo 1: Crear Pedido + Agregar EPP Con Imágenes

```php
// PASO 1: FRONTEND - Recolectar datos
{
  "cliente": "Cliente XYZ",
  "prendas": [...],
  "epps": [
    {
      "epp_id": 5,
      "nombre_completo": "Gafas de seguridad",
      "cantidad": 10,
      "observaciones": "Color azul",
      "imagenes": [File, File]  ← UploadedFile Objects
    }
  ]
}

// PASO 2: BACKEND - CrearPedidoService::crearPedidos()
1. Crear pedido en BD
2. $pedido->id = 2718 (recién creado)
3. Guardar prendas
4. Guardar logo
5. Retornar $pedido

// PASO 3: BACKEND - Frontend recibe pedido_id
// Ahora el frontend DEBE enviar EPP con pedido_id

// PASO 4: FRONTEND - Enviar EPP al backend
POST /api/pedidos/2718/epp/agregar
{
  "epp_id": 5,
  "cantidad": 10,
  "observaciones": "Color azul",
  "imagenes": [File, File]  ← FormData con files
}

// PASO 5: BACKEND - EppController::agregar()
$validated = $request->validate([
    'epp_id' => 'required|integer|exists:epps,id',
    'cantidad' => 'required|integer|min:1',
    'observaciones' => 'nullable|string|max:1000',
    'imagenes' => 'nullable|array|max:5',
    'imagenes.*' => 'nullable|string',
]);

// Procesar imágenes  IMPORTANTE
$imagenes = [];
if ($request->hasFile('imagenes')) {
    foreach ($request->file('imagenes') as $imagen) {
        if ($imagen->isValid()) {
            // Guardar físicamente en storage/app/public/pedido/2718/epp/
            $ruta = $imagen->store("pedido/{$pedidoId}/epp", 'public');
            $imagenes[] = $ruta;  // ej: "pedido/2718/epp/imagen.jpg"
        }
    }
}

// PASO 6: BACKEND - AgregarEppAlPedidoCommand
$command = new AgregarEppAlPedidoCommand(
    pedidoId: 2718,
    eppId: 5,
    cantidad: 10,
    observaciones: "Color azul",
    imagenes: $imagenes  // ["pedido/2718/epp/imagen1.jpg", "pedido/2718/epp/imagen2.jpg"]
);
$resultado = $this->commandBus->execute($command);

// PASO 7: BACKEND - PedidoEppRepository::agregarEppAlPedido()
// Crear relación en BD
$pedidoEpp = PedidoEpp::updateOrCreate([
    'pedido_produccion_id' => 2718,
    'epp_id' => 5,
], [
    'cantidad' => 10,
    'observaciones' => 'Color azul',
]);

// Guardar imágenes en tabla pedido_epp_imagenes
foreach ($imagenes as $index => $imagen) {
    \DB::table('pedido_epp_imagenes')->updateOrCreate([
        'pedido_epp_id' => $pedidoEpp->id,  // ej: 76
        'orden' => $index + 1,
    ], [
        'ruta_original' => "pedido/2718/epp/imagen1.jpg",
        'ruta_web' => "pedido/2718/epp/imagen1.jpg",
        'principal' => ($index === 0) ? 1 : 0,
    ]);
}

// RESULTADO EN BD:
// Tabla pedido_epp_imagenes:
// id | pedido_epp_id | ruta_original              | ruta_web                   | principal | orden
// 1  | 76            | pedido/2718/epp/img1.jpg  | pedido/2718/epp/img1.jpg  | 1         | 1
// 2  | 76            | pedido/2718/epp/img2.jpg  | pedido/2718/epp/img2.jpg  | 0         | 2
```

---

### Flujo 2: Editar EPP - Mantener Imágenes Existentes

```php
// ESCENARIO: Usuario edita EPP del pedido 2718
// La imagen img1.jpg ya existe en storage/app/public/pedido/2718/epp/

// PASO 1: FRONTEND - Obtener datos existentes
GET /api/pedidos/2718/epp/76
Retorna:
{
  "id": 76,
  "pedido_epp_id": 76,
  "epp_id": 5,
  "nombre_completo": "Gafas de seguridad",
  "cantidad": 10,
  "observaciones": "Color azul",
  "imagenes": [
    {
      "id": 1,
      "ruta_web": "pedido/2718/epp/img1.jpg"
    }
  ]
}

// PASO 2: FRONTEND - Usuario edita y envía
{
  "cantidad": 15,  ← Cambió
  "observaciones": "Color azul oscuro",  ← Cambió
  "imagenes": [
    "pedido/2718/epp/img1.jpg",  ← String (existente, NO es File)
    File                           ← File nuevo
  ]
}

// PASO 3: BACKEND - EppController::actualizar()
$imagenes = [];

// Procesar imágenes
if ($request->hasFile('imagenes')) {
    foreach ($request->file('imagenes') as $imagen) {
        if ($imagen instanceof UploadedFile && $imagen->isValid()) {
            // Es un File nuevo
            $ruta = $imagen->store("pedido/{$pedidoId}/epp", 'public');
            $imagenes[] = $ruta;
        }
    }
}

// También procesar strings (imágenes existentes)
if ($request->has('imagenes')) {
    foreach ($request->input('imagenes') as $imagen) {
        if (is_string($imagen) && !empty($imagen)) {
            // Es una ruta string existente
            $imagenes[] = $imagen;
        }
    }
}

// RESULTADO:
// $imagenes = [
//     "pedido/2718/epp/img1.jpg",      ← Original
//     "pedido/2718/epp/imagen_new.jpg" ← Nuevo
// ]

// PASO 4: BACKEND - ActualizarEppCommand
// El repositorio debe hacer updateOrCreate, NO delete + insert
// Esto evita perder imágenes

foreach ($imagenes as $index => $imagen) {
    \DB::table('pedido_epp_imagenes')->updateOrCreate([
        'pedido_epp_id' => 76,
        'orden' => $index + 1,
    ], [
        'ruta_original' => $imagen,
        'ruta_web' => $imagen,
        'principal' => ($index === 0) ? 1 : 0,
    ]);
}

// RESULTADO EN BD (preserva ambas):
// id | pedido_epp_id | ruta_original              | ruta_web                   | principal | orden
// 1  | 76            | pedido/2718/epp/img1.jpg  | pedido/2718/epp/img1.jpg  | 1         | 1
// 3  | 76            | pedido/2718/epp/imagen_new.jpg | pedido/2718/epp/imagen_new.jpg | 0    | 2
```

---

### Flujo 3: Renderizar Factura Con EPP

```php
// GET /asesores/pedidos/2718/factura-datos
// PedidoProduccionRepository::obtenerDatosFactura()

$datos['epps'] = [];

foreach ($pedido->epps as $pedidoEpp) {
    $epp = $pedidoEpp->epp;
    
    $eppFormato = [
        'id' => $pedidoEpp->id,
        'epp_id' => $pedidoEpp->epp_id,
        'nombre' => $epp->nombre_completo ?? '',           // ← NO codigo/categoria
        'nombre_completo' => $epp->nombre_completo ?? '',
        'cantidad' => $pedidoEpp->cantidad ?? 0,
        'observaciones' => $pedidoEpp->observaciones ?? '',
        'imagen' => null,
        'imagenes' => [],
    ];
    
    // Obtener imágenes
    $imagenesData = \DB::table('pedido_epp_imagenes')
        ->where('pedido_epp_id', $pedidoEpp->id)
        ->orderBy('orden', 'asc')
        ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
    
    if ($imagenesData->count() > 0) {
        $imagenes = $imagenesData->pluck('ruta_web')->filter()->toArray();
        $eppFormato['imagenes'] = $imagenes;
        $eppFormato['imagen'] = $imagenes[0] ?? null;
    }
    
    $datos['epps'][] = $eppFormato;
}

// RESPUESTA:
{
  "epps": [
    {
      "id": 76,
      "epp_id": 5,
      "nombre": "Gafas de seguridad",
      "nombre_completo": "Gafas de seguridad",
      "cantidad": 15,
      "observaciones": "Color azul oscuro",
      "imagen": "pedido/2718/epp/img1.jpg",
      "imagenes": [
        "pedido/2718/epp/img1.jpg",
        "pedido/2718/epp/imagen_new.jpg"
      ]
    }
  ]
}

// FRONTEND - invoice-preview-live.js renderiza:
${epp.nombre_completo || epp.nombre || ''}  ← NO fallará si está vacío
```

---

## ✅ Checklist de Validaciones

### Backend

- ✅ Al crear pedido: crear carpeta `storage/pedido/{pedido_id}/` con subcarpetas
- ✅ Al agregar EPP: `$imagen->store("pedido/{$pedidoId}/epp", 'public')`
- ✅ Al guardar en BD: usar rutas relativas `pedido/2718/epp/imagen.jpg`
- ✅ Al editar EPP: usar `updateOrCreate` no delete + insert
- ✅ Si no hay imágenes: no fallar, dejar `imagenes: []`
- ✅ NO acceder a `$epp->codigo` o `$epp->categoria` en factura
- ✅ NO usar soft deletes en verificación de imágenes

### Frontend (JavaScript)

- ✅ Enviar FormData con files de imágenes
- ✅ Después de crear pedido: esperar `pedido_id` antes de enviar EPP
- ✅ Al editar: enviar mix de strings (existentes) y Files (nuevos)
- ✅ Mostrar imágenes: usar `${epp.imagenes.map(img => `<img src="/storage/${img}">`)}`
- ✅ NO mostrar "Sin nombre" si `nombre` está vacío

### Database

- ✅ Tabla `pedido_epp_imagenes` sin soft deletes
- ✅ Columnas: `id`, `pedido_epp_id`, `ruta_original`, `ruta_web`, `principal`, `orden`
- ✅ Indices en `pedido_epp_id` para queries rápidas
- ✅ Cuando se elimina `pedido_epp`: también eliminar registros en `pedido_epp_imagenes`

---

## 🐛 Troubleshooting

### Problema: "Imagenes_count":0 en factura
**Causa:** Las imágenes no se están guardando en BD  
**Solución:** Verificar que `PedidoEppRepository::agregarEppAlPedido()` ejecuta el loop de `updateOrCreate`

### Problema: Imágenes no se ven en storage
**Causa:** Rutas incorrectas  
**Solución:**
```bash
# Verificar que exista la carpeta
ls -la storage/app/public/pedido/2718/epp/

# Crear symbolic link si no existe
php artisan storage:link
```

### Problema: EPP sin nombre en factura
**Causa:** Usando `epp.epp_nombre` pero backend envía `epp.nombre_completo`  
**Solución:** Cambiar a `${epp.nombre_completo || epp.nombre || ''}`

### Problema: Base64 en imágenes
**Causa:** Intentando guardar base64 en storage  
**Solución:** Siempre usar `UploadedFile` + `store()`, nunca base64

---

## 📊 Tabla de Referencia Rápida

| Aspecto | Crear | Editar | Factura |
|--------|-------|--------|---------|
| **Ruta almacenamiento** | `store("pedido/{id}/epp")` | Idem | No aplica |
| **Manejo strings** | N/A | Preservar | Solo lectura |
| **Manejo Files** | Store nuevo | Store nuevo | No aplica |
| **BD updateOrCreate** | Sí | Sí | N/A |
| **Mostrar nombre** | `nombre_completo` | `nombre_completo` | `nombre_completo` |
| **Mostrar imagen** | URL web | URL web | URL web |

---

## 🔗 Archivos Clave

| Archivo | Responsabilidad |
|---------|----------------|
| `CrearPedidoService.php` | Crear pedido + estructura carpetas |
| `EppController::agregar()` | Recibir EPP + guardar imágenes |
| `PedidoEppRepository::agregarEppAlPedido()` | Guardar en BD |
| `PedidoProduccionRepository::obtenerDatosFactura()` | Recuperar para factura |
| `invoice-preview-live.js` | Renderizar factura |

---

## 📝 Notas Importantes

1. **Orden temporal:**
   - Paso 1: Crear pedido → Obtener `pedido_id`
   - Paso 2: Crear carpetas `pedido/{pedido_id}/*`
   - Paso 3: Agregar prendas (con sus imágenes)
   - Paso 4: Agregar EPP (con sus imágenes)

2. **Imágenes no son obligatorias:**
   - Si EPP no tiene imágenes: OK
   - Si pedido no tiene EPP: OK
   - Si EPP tiene 0 imágenes: OK

3. **Caracteres especiales:**
   - Nombres de archivo: sanitizar
   - Rutas: sin espacios, underscore y guiones
   - Subrips: `/pedido/2718/epp/` NO crear más subdirectorios

4. **Seguridad:**
   - Validar `epp_id` existe en tabla `epps`
   - Validar archivos con `$file->isValid()`
   - Limitar tamaño: 5 imágenes máximo
   - Limitar peso: validar en request

