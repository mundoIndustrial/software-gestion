# ✅ GUARDADO DE LOGO EN PEDIDO BORRADOR

## 🎯 QUÉ SE IMPLEMENTÓ

Se agregó la funcionalidad para guardar los datos del logo (paso 3) cuando se guarda un **pedido como borrador** en el modal de creación.

## 📝 CAMBIOS REALIZADOS

### 1️⃣ **Frontend** - `public/js/asesores/pedidos-modal.js`

#### ✅ Nueva Función: `recopilarDatosLogo()`
- Recopila todos los datos del logo del paso 3:
  - ✓ Descripción del logo
  - ✓ Técnicas seleccionadas
  - ✓ Observaciones técnicas
  - ✓ Ubicaciones seleccionadas
  - ✓ Observaciones generales
  - ✓ Imágenes del logo

```javascript
// Recopila descripción, técnicas, ubicaciones, imágenes, etc.
const datosLogo = recopilarDatosLogo();
```

#### ✅ Modificación: `guardarPedidoModal()`
- Ahora incluye los datos del logo en el `FormData` ANTES de enviar:
  ```javascript
  formData.append('logo[descripcion]', datosLogo.descripcion);
  formData.append('logo[observaciones_tecnicas]', datosLogo.observaciones_tecnicas);
  formData.append('logo[tecnicas]', JSON.stringify(datosLogo.tecnicas));
  formData.append('logo[ubicaciones]', JSON.stringify(datosLogo.ubicaciones));
  formData.append('logo[observaciones_generales]', JSON.stringify(datosLogo.observaciones_generales));
  
  // Agregar imágenes del logo si existen
  if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo) {
      window.imagenesEnMemoria.logo.forEach((imagen, idx) => {
          if (imagen instanceof File) {
              formData.append(`logo[imagenes][]`, imagen);
          }
      });
  }
  ```

### 2️⃣ **Backend** - `app/Http/Controllers/AsesoresController.php`

#### ✅ Nuevo Import
```php
use App\Application\Services\PedidoLogoService;
```

#### ✅ Modificación: `store()` - Validaciones
Agregadas validaciones para los datos del logo:
```php
// Validaciones para datos del logo
'logo.descripcion' => 'nullable|string',
'logo.observaciones_tecnicas' => 'nullable|string',
'logo.tecnicas' => 'nullable|string', // JSON string
'logo.ubicaciones' => 'nullable|string', // JSON string
'logo.observaciones_generales' => 'nullable|string', // JSON string
'logo.imagenes' => 'nullable|array',
'logo.imagenes.*' => 'nullable|file|image|max:5242880', // Máximo 5MB por imagen
```

#### ✅ Modificación: `store()` - Lógica de Guardado
Después de guardar el pedido y sus prendas, ahora también guarda el logo:

```php
// ✅ GUARDAR LOGO Y SUS IMÁGENES (PASO 3)
if (!empty($request->get('logo.descripcion')) || $request->hasFile('logo.imagenes')) {
    $logoService = new PedidoLogoService();
    
    // Procesar imágenes del logo
    $imagenesProcesadas = [];
    if ($request->hasFile('logo.imagenes')) {
        foreach ($request->file('logo.imagenes') as $imagen) {
            if ($imagen->isValid()) {
                // Guardar en storage y obtener la ruta
                $rutaGuardada = $imagen->store('logos/pedidos', 'public');
                $imagenesProcesadas[] = [
                    'ruta_original' => Storage::url($rutaGuardada),
                    'ruta_webp' => null,
                    'ruta_miniatura' => null
                ];
            }
        }
    }
    
    // Preparar datos del logo
    $logoData = [
        'descripcion' => $validated['logo.descripcion'] ?? null,
        'ubicacion' => null,
        'observaciones_generales' => null,
        'fotos' => $imagenesProcesadas
    ];
    
    // Guardar logo en el pedido usando el servicio
    $logoService->guardarLogoEnPedido($pedidoBorrador, $logoData);
}
```

## 🔄 FLUJO DE GUARDADO

### Cuando el usuario guarda un pedido como borrador:

```
1. Usuario rellena formulario modal
   ├─ Paso 1: Cliente, Forma de Pago
   ├─ Paso 2: Productos
   └─ Paso 3: Logo (descripción, técnicas, imágenes)

2. Click en "Guardar Pedido"

3. Frontend: `guardarPedidoModal()`
   ├─ Validar formulario
   ├─ Crear FormData con productos
   ├─ Recopilar datos del logo → recopilarDatosLogo()
   ├─ Agregar logo al FormData
   └─ POST /asesores/pedidos.store

4. Backend: `AsesoresController@store()`
   ├─ Validar datos (incluyendo logo)
   ├─ Crear PedidoProduccion
   ├─ Guardar prendas
   ├─ Guardar logo usando PedidoLogoService
   │  ├─ Guardar en tabla logo_ped
   │  └─ Guardar imágenes en logo_fotos_ped
   └─ Retornar JSON { success: true }

5. Frontend: Toast de éxito
   └─ "¡Pedido guardado! ¿Deseas crear ahora?"
```

## 📊 DATOS GUARDADOS EN BD

### Tabla `logo_ped`:
```sql
INSERT INTO logo_ped (
    pedido_produccion_id,
    descripcion,
    ubicacion,
    observaciones_generales,
    created_at
) VALUES (
    123,
    'Logo bordado en pecho',
    NULL,
    NULL,
    NOW()
);
```

### Tabla `logo_fotos_ped`:
```sql
INSERT INTO logo_fotos_ped (
    logo_ped_id,
    ruta_original,
    ruta_webp,
    ruta_miniatura,
    orden,
    created_at
) VALUES (
    45,
    '/storage/logos/pedidos/image1.jpg',
    NULL,
    NULL,
    1,
    NOW()
);
```

## 🧪 CÓMO PROBAR

### Escenario 1: Modal de Creación de Pedido (Recomendado)

1. Ir a `/asesores/pedidos`
2. Click en "Crear Pedido" (si hay un botón)
3. Rellenar datos:
   - **Paso 1**: Cliente, Forma de Pago
   - **Paso 2**: Agregar productos
   - **Paso 3**: Logo (descripción + imágenes)
4. Click en "Guardar Pedido"
5. **Verificación**:
   ```sql
   SELECT * FROM logo_ped WHERE pedido_produccion_id = 123;
   SELECT * FROM logo_fotos_ped WHERE logo_ped_id IN (SELECT id FROM logo_ped WHERE pedido_produccion_id = 123);
   ```

### Escenario 2: Formulario Amigable (create-friendly)

Este flujo **YA FUNCIONA** porque usa `guardado.js` que ya maneja el logo correctamente.

1. Ir a `/asesores/pedidos/create?tipo=PB`
2. Rellenar: Cliente, Prendas, Logo
3. Click "Guardar Borrador"
4. El logo **ya se guarda** porque usa el flujo de cotizaciones

## ✅ VERIFICACIÓN

### En la Consola del Navegador (DevTools):
```javascript
// Ver datos del logo que se envían
console.log('📸 Datos del logo recopilados:', datosLogo);

// Ver imágenes en memoria
console.log('📸 Imágenes en memoria:', window.imagenesEnMemoria.logo);
```

### En los Logs del Servidor:
```
📸 Recopilando datos del logo...
✅ Datos del logo recopilados: { descripcion: '...', tecnicas: 3, ... }
✅ Imagen de logo agregada [0]: image1.jpg
```

### En la BD:
```sql
-- Verificar que el logo se guardó
SELECT COUNT(*) as logos_guardados FROM logo_ped;

-- Ver detalles de un logo
SELECT p.numero_pedido, l.descripcion, COUNT(lf.id) as fotos
FROM pedidos_produccion p
JOIN logo_ped l ON l.pedido_produccion_id = p.id
LEFT JOIN logo_fotos_ped lf ON lf.logo_ped_id = l.id
GROUP BY l.id
ORDER BY p.created_at DESC;
```

## 🔧 MANTENIMIENTO

Si necesitas extender la funcionalidad:

1. **Agregar más campos del logo**: Modifica `recopilarDatosLogo()` en `pedidos-modal.js`
2. **Validaciones adicionales**: Agrega en `AsesoresController.store()` en las validaciones
3. **Procesar imágenes especiales**: Modifica la lógica en el bloque `if ($request->hasFile('logo.imagenes'))`

## 📝 NOTAS IMPORTANTES

- ✅ Las imágenes se guardan en `storage/logos/pedidos/`
- ✅ Se usa `PedidoLogoService` (servicio existente) para consistencia
- ✅ Los datos se guardan en transacción para integridad referencial
- ⚠️ Las imágenes deben ser File objects válidos (no Base64)
- ⚠️ Máximo 5MB por imagen, según validación

## 🚀 PRÓXIMOS PASOS

- Implementar carga de logo desde borrador (cargar datos guardados al editar)
- Agregar vista para mostrar logo guardado en pedido
- Extender a otros tipos de pedidos (Prenda, Reflectivo, etc.)
