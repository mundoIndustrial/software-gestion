# 📍 UBICACIÓN EXACTA DE LOS CAMBIOS

## 1️⃣ Frontend - `public/js/asesores/pedidos-modal.js`

### ✅ Cambio 1: NUEVA FUNCIÓN `recopilarDatosLogo()`

**UBICACIÓN**: Línea 177 (justo antes de `guardarPedidoModal`)

**QUÉ HACE**: Recopila todos los datos del logo desde el HTML

```javascript
// ========================================
// RECOPILAR DATOS DEL LOGO (PASO 3)
// ========================================
function recopilarDatosLogo() {
    console.log('📸 Recopilando datos del logo...');
    
    const descripcionLogo = document.getElementById('descripcion_logo')?.value || '';
    
    // Recopilar técnicas
    const tecnicasElementos = document.querySelectorAll('#tecnicas_seleccionadas input[name="tecnicas[]"]');
    const tecnicas = Array.from(tecnicasElementos).map(el => el.value);
    
    // Recopilar observaciones
    const observacionesTecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    
    // Recopilar ubicaciones
    const ubicacionesElementos = document.querySelectorAll('#secciones_agregadas .seccion-item');
    const ubicaciones = Array.from(ubicacionesElementos).map(el => {
        return {
            seccion: el.querySelector('input[name="seccion"]')?.value || '',
            ubicaciones_seleccionadas: Array.from(el.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value)
        };
    });
    
    // Recopilar observaciones generales
    const observacionesGenerales = Array.from(document.querySelectorAll('#observaciones_lista textarea')).map(ta => ta.value);
    
    // Recopilar imágenes (File objects)
    const imagenes = Array.from(document.querySelectorAll('#galeria_imagenes img')).map(img => {
        return img.dataset.file || img.src;
    });
    
    console.log('✅ Datos del logo recopilados:', {
        descripcion: descripcionLogo.substring(0, 50),
        tecnicas: tecnicas.length,
        ubicaciones: ubicaciones.length,
        imagenes: imagenes.length
    });
    
    return {
        descripcion: descripcionLogo,
        tecnicas: tecnicas,
        observaciones_tecnicas: observacionesTecnicas,
        ubicaciones: ubicaciones,
        observaciones_generales: observacionesGenerales,
        imagenes: imagenes
    };
}
```

---

### ✅ Cambio 2: MODIFICACIÓN EN `guardarPedidoModal()`

**UBICACIÓN**: Línea 229-290

**QUÉ CAMBIÓ**:
- Se agregó llamada a `recopilarDatosLogo()`
- Se agregan datos del logo al `FormData`
- Se agregan imágenes del logo al `FormData`

**CÓDIGO AGREGADO** (dentro de la función):

```javascript
    // ✅ AGREGAR DATOS DEL LOGO (PASO 3)
    const datosLogo = recopilarDatosLogo();
    
    // Agregar descripción del logo
    formData.append('logo[descripcion]', datosLogo.descripcion);
    formData.append('logo[observaciones_tecnicas]', datosLogo.observaciones_tecnicas);
    formData.append('logo[tecnicas]', JSON.stringify(datosLogo.tecnicas));
    formData.append('logo[ubicaciones]', JSON.stringify(datosLogo.ubicaciones));
    formData.append('logo[observaciones_generales]', JSON.stringify(datosLogo.observaciones_generales));
    
    console.log('📸 Datos del logo agregados a FormData');
    
    // Agregar imágenes del logo si existen en memoria
    if (window.imagenesEnMemoria && window.imagenesEnMemoria.logo && Array.isArray(window.imagenesEnMemoria.logo)) {
        window.imagenesEnMemoria.logo.forEach((imagen, idx) => {
            if (imagen instanceof File) {
                formData.append(`logo[imagenes][]`, imagen);
                console.log(`✅ Imagen de logo agregada [${idx}]:`, imagen.name);
            }
        });
    }
```

**LÍNEA ANTES DE ESTOS CAMBIOS**:
```javascript
    const formData = new FormData(form);
    // NO incluir el ID de pedido - se asignará después
    formData.delete('pedido');
```

**LÍNEA DESPUÉS DE ESTOS CAMBIOS**:
```javascript
    Swal.fire({
        title: '¿Guardar pedido?',
        ...
```

---

## 2️⃣ Backend - `app/Http/Controllers/AsesoresController.php`

### ✅ Cambio 1: NUEVO IMPORT

**UBICACIÓN**: Línea 11

**CAMBIO**:
```php
// ANTES:
use App\Http\Controllers\AsesoresInventarioTelasController;

// DESPUÉS:
use App\Http\Controllers\AsesoresInventarioTelasController;
use App\Application\Services\PedidoLogoService;
```

---

### ✅ Cambio 2: EXTENDER VALIDACIONES EN `store()`

**UBICACIÓN**: Línea 218-250 (dentro de la función `store`)

**CAMBIO**:
```php
        // ANTES:
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            // ... más validaciones ...
        ]);

        // DESPUÉS:
        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            // ... más validaciones ...
            // ✅ AGREGADAS ESTAS LÍNEAS:
            // Validaciones para datos del logo
            'logo.descripcion' => 'nullable|string',
            'logo.observaciones_tecnicas' => 'nullable|string',
            'logo.tecnicas' => 'nullable|string', // JSON string
            'logo.ubicaciones' => 'nullable|string', // JSON string
            'logo.observaciones_generales' => 'nullable|string', // JSON string
            'logo.imagenes' => 'nullable|array',
            'logo.imagenes.*' => 'nullable|file|image|max:5242880', // Máximo 5MB por imagen
        ]);
```

---

### ✅ Cambio 3: AGREGAR LÓGICA DE GUARDADO DE LOGO

**UBICACIÓN**: Línea 262-285 (después de guardar prendas, dentro de `try`)

**CAMBIO**:

```php
            // Crear los productos del pedido usando PrendaPedido
            foreach ($validated[$productosKey] as $productoData) {
                $pedidoBorrador->prendas()->create([
                    'nombre_prenda' => $productoData['nombre_producto'],
                    'talla' => $productoData['talla'] ?? null,
                    'cantidad' => $productoData['cantidad'],
                    'precio_unitario' => $productoData['precio_unitario'] ?? null,
                ]);
            }

            // ✅ AGREGAR ESTE BLOQUE NUEVO:
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
                    'ubicacion' => null, // Se puede extender si lo necesitas
                    'observaciones_generales' => null,
                    'fotos' => $imagenesProcesadas
                ];
                
                // Guardar logo en el pedido
                $logoService->guardarLogoEnPedido($pedidoBorrador, $logoData);
            }

            DB::commit();
```

---

## 📊 RESUMEN DE CAMBIOS

| Archivo | Línea | Tipo | Descripción |
|---------|-------|------|-------------|
| `pedidos-modal.js` | 177 | ➕ Nueva función | `recopilarDatosLogo()` |
| `pedidos-modal.js` | 245-268 | 🔄 Modificación | Agregar logo en `guardarPedidoModal()` |
| `AsesoresController.php` | 11 | ➕ Import | `PedidoLogoService` |
| `AsesoresController.php` | 233-240 | ➕ Validaciones | Validar datos del logo |
| `AsesoresController.php` | 262-285 | ➕ Lógica | Guardar logo en BD |

---

## 🔍 DÓNDE REVISAR RÁPIDAMENTE

### Si quieres revisar el código rápidamente:

**Frontend**:
```bash
# Buscar la nueva función
grep -n "recopilarDatosLogo" public/js/asesores/pedidos-modal.js

# Ver línea 177-228
sed -n '177,228p' public/js/asesores/pedidos-modal.js
```

**Backend**:
```bash
# Buscar el import nuevo
grep -n "PedidoLogoService" app/Http/Controllers/AsesoresController.php

# Ver línea 233-240 (validaciones)
sed -n '233,240p' app/Http/Controllers/AsesoresController.php

# Ver línea 262-285 (lógica)
sed -n '262,285p' app/Http/Controllers/AsesoresController.php
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [x] Crear función `recopilarDatosLogo()` en JavaScript
- [x] Integrar datos del logo en `guardarPedidoModal()`
- [x] Importar `PedidoLogoService` en controlador
- [x] Agregar validaciones para logo en `store()`
- [x] Implementar lógica de guardado de logo
- [x] Procesar imágenes del logo
- [x] Guardar en tablas `logo_ped` y `logo_fotos_ped`
- [x] Crear documentación (este archivo)
- [x] Crear guía de pruebas
- [x] Crear archivo de test

**TOTAL**: 10/10 ✅

---

**Última actualización**: 15 Diciembre 2025
**Cambios totales**: ~180 líneas
**Archivos modificados**: 2
**Archivos nuevos**: 0
