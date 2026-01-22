# Cambios: Carga de Datos Directamente desde BD al Editar Prenda

## Problema Identificado
Cuando el usuario hacía clic en "Editar" una prenda, se cargaban los datos desde memoria (`window.datosEdicionPedido.prendas`), que podría estar desactualizada. Si alguien hubiese agregado imágenes o telas después de la carga inicial, no se vería en la edición.

## Solución Implementada
Se agregó un nuevo endpoint que consulta la BD directamente cuando se va a editar una prenda, garantizando que siempre se obtengan los datos más frescos.

---

## 🔧 Cambios Realizados

### 1. Backend - Controller Nuevo Método
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`

Se agregó el método:
```php
public function obtenerDatosUnaPrenda($pedidoId, $prendaId)
```

**Características:**
- Valida que la prenda exista y pertenezca al pedido
- Obtiene imágenes desde tabla `prenda_fotos_pedido` (no desde JSON)
- Obtiene telas desde `prenda_pedido_colores_telas`
- Obtiene imágenes de telas desde `prenda_fotos_tela_pedido`
- Devuelve estructura completa con:
  - `imagenes[]`: Array de rutas /storage/
  - `telasAgregadas[]`: Telas con colores e imágenes
  - `generosConTallas`: Tallas de la prenda
  - Todos los campos necesarios para edición
- Incluye logging para debugging

**Respuesta JSON:**
```json
{
  "success": true,
  "prenda": {
    "id": 3418,
    "nombre_prenda": "RET",
    "imagenes": ["/storage/prendas/foto1.webp", ...],
    "telasAgregadas": [
      {
        "tela": "Drill",
        "color": "Azul",
        "referencia": "DR-001",
        "imagenes": ["/storage/telas/tela1.webp", ...]
      }
    ],
    "tallas": {"XS": 2, "S": 3, ...},
    "procesos": [...],
    ... más campos
  }
}
```

### 2. Ruta Web
**Archivo:** `routes/web.php` (Línea 519)

```php
Route::get('/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos', 
  [PedidosProduccionViewController::class, 'obtenerDatosUnaPrenda'])->name('pedidos-produccion.prenda.datos');
```

- Endpoint: `GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos`
- Devuelve datos frescos de la BD en JSON

### 3. Frontend - Modificación
**Archivo:** `public/js/componentes/prenda-card-editar-simple.js`

Función `abrirEditarPrendaModal()` modificada:
- Ahora es `async` para poder hacer fetch
- Antes de abrir el modal, intenta obtener datos frescos de la BD
- Si tiene `pedidoId` y `prenda.id`, llama al nuevo endpoint
- Si falla, usa los datos de memoria como fallback
- Log detallado para debugging

**Lógica:**
```javascript
1. Verificar que tenga pedidoId y prenda.id
2. Hacer fetch a /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
3. Si éxito: usar datos frescos de la BD
4. Si falla: usar datos de memoria (fallback)
5. Abrir modal con los datos (frescos o memoria)
```

**Console logs agregados:**
- `[EDITAR-MODAL] Abriendo prenda para editar`
- `Obteniendo datos frescos de la BD para prenda {id}...`
- ` Datos obtenidos desde BD: {...}`
- `⚠️  Respuesta sin datos válidos, usando prenda de memoria`
- ` Error obteniendo datos frescos: {error}`

---

## 📝 Flujo Completo

```
Usuario hace clic en "EDITAR" prenda
    ↓
prenda-card-handlers.js detecta .btn-editar-prenda
    ↓
Llama a abrirEditarPrendaModal(prenda, index, pedidoId)
    ↓
prenda-card-editar-simple.js:abrirEditarPrendaModal()
    ├─ Es async
    ├─ Si tiene pedidoId + prenda.id:
    │   └─ Hace fetch a /pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
    │       ├─ Response OK → Usa datos frescos de BD 
    │       └─ Error → Usa datos de memoria (fallback) ⚠️
    ├─ Genera HTML del modal
    ├─ Carga imágenes en window.imagenesPrendaStorage
    ├─ Abre Swal.fire con formulario editable
    └─ En confirmación: guardar con guardarPrendaEnBD()
```

---

## 🔍 Cómo Verificar

### 1. Browser Console
Abrir DevTools (F12) → Console

Cuando haga clic en Editar:
```
🖊️  [EDITAR-MODAL] Abriendo prenda para editar
   Prenda: {...}
   Pedido ID: 12345
   Obteniendo datos frescos de la BD para prenda 3418...
    Datos obtenidos desde BD: {
     id: 3418,
     imagenes: ["/storage/prendas/...", ...],
     telasAgregadas: [...],
     ...
   }
```

### 2. Network Tab
En DevTools → Network

Cuando hace clic en Editar, debería ver:
```
GET /asesores/pedidos-produccion/12345/prenda/3418/datos
Status: 200
Response: {...datos JSON...}
```

### 3. Laravel Logs
En `storage/logs/laravel.log`:
```
[PRENDA-DATOS] Cargando datos de prenda para edición
[PRENDA-DATOS] Imágenes encontradas: cantidad = 3
[PRENDA-DATOS] Datos compilados correctamente
```

---

##  Beneficios

 **Datos siempre frescos**: Cada edición consulta la BD directamente
 **Imágenes correctas**: Se obtienen desde `prenda_fotos_pedido`, no JSON
 **Telas sincronizadas**: Se obtienen las telas actuales con sus imágenes
 **Fallback seguro**: Si falla, usa datos de memoria
 **Debugging claro**: Logs detallados en console y Laravel
 **Sin cambios en edición**: El resto del flujo (guardar, actualizar) funciona igual

---

## 📌 Notas

- Endpoint requiere autenticación (está dentro del middleware de asesores)
- Compatible con prendas de pedidos nuevos y guardados
- El método también se usa para crear nuevas prendas (cargar en modal), así que beneficia ambos flujos
- No afecta prendas en modo "crear-nuevo" sin BD

---

## 🚀 Próximos Pasos (Opcional)

Si quiere optimizar más:
1. Agregar caché de 5 minutos para prenda.js (evita múltiples queries si edita varias veces)
2. Sincronizar automáticamente imágenes de `imagenes_path` a `prenda_fotos_pedido` para prendas antiguas
3. Agregar validación que `prenda.id` sea válido antes de hacer fetch
