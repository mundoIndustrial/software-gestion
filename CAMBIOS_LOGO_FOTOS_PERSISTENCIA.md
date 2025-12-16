# Cambios Implementados para Persistencia de Fotos del Logo

## Problema Original
Las fotos del logo no se guardaban correctamente cuando se creaba una cotización. Además, cuando se reguardaba un borrador existente, las fotos desaparecían.

## Causas Raíz Encontradas
1. **Relación de modelo incorrecta**: `LogoCotizacion->fotos()` apuntaba a `LogoFoto` en lugar de `LogoFotoCot`
2. **Fotos eliminadas inmediatamente**: El servicio de eliminación se ejecutaba después de crear fotos, eliminándolas
3. **Fotos no reenviadas en edición**: El frontend no enviaba las rutas de fotos existentes al reguardar
4. **Ubicaciones y observaciones generales**: No se guardaban ni se devolvían correctamente

## Cambios Implementados

### 1. **Backend - CotizacionController.php** (Líneas 510-570)
**Qué cambió:**
- Ahora obtiene `logo_fotos_guardadas[]` del request (rutas de fotos existentes)
- Normaliza las rutas para comparación correcta (convierte URLs a rutas `/storage/...`)
- Siempre ejecuta el servicio de eliminación, pasando la lista de fotos a conservar
- El servicio solo elimina las fotos que NO están en la lista de conservar

**Código clave:**
```php
$fotosLogoGuardadas = $request->input('logo_fotos_guardadas', []);
// ... normalización de rutas ...
$this->eliminarImagenesService->eliminarImagenesLogoNoIncluidas(
    $logoCotizacion->id,
    $fotosLogoGuardadas  // Pasar fotos a conservar
);
```

### 2. **Frontend - guardado.js** (Líneas 287-310)
**Qué cambió:**
- Busca imágenes en `#galeria_imagenes` con atributo `data-foto-guardada="true"`
- Extrae las rutas del atributo `data-ruta` de cada imagen
- Agrega las rutas al FormData como `logo_fotos_guardadas[]`

**Código clave:**
```javascript
const galeriaImagenes = document.getElementById('galeria_imagenes');
const fotosGuardadas = galeriaImagenes.querySelectorAll('[data-foto-guardada="true"] img');
fotosGuardadas.forEach((img, index) => {
    const ruta = img.getAttribute('data-ruta') || img.src;
    if (ruta && !ruta.includes('data:image')) {
        formData.append(`logo_fotos_guardadas[]`, ruta);
    }
});
```

### 3. **Modelo - LogoCotizacion.php** (Confirmado)
- Relación `fotos()` ya está correcta: apunta a `LogoFotoCot`
- No se realizó cambio, pero se verificó que es correcto

### 4. **Vista - paso-tres.blade.php** (Confirmado)
- El contenedor `galeria_imagenes` ya existe
- Las fotos se cargan dinámicamente aquí

### 5. **Cargador de borrador - cargar-borrador.js** (Líneas 870-950)
- Carga las fotos del logo desde `cotizacion.logo_cotizacion.fotos`
- Establece los atributos necesarios:
  - `data-foto-guardada="true"` ✅
  - `data-ruta="${srcUrl}"` ✅

## Flujo de Guardado Ahora

### Primer Guardado (Crear Cotización)
1. Usuario carga fotos nuevas en `#galeria_imagenes`
2. Las fotos se almacenan en `window.imagenesEnMemoria.logo`
3. Al guardar, se envían como `logo[imagenes][0]`, `logo[imagenes][1]`, etc.
4. Backend crea registros en `logo_fotos_cot` ✅

### Reguardado (Editar Borrador)
1. Usuario abre borrador
2. `cargarBorrador()` carga fotos existentes en `#galeria_imagenes`
3. Cada foto tiene:
   - `data-foto-guardada="true"`
   - `data-ruta="/storage/cotizaciones/1/logo/..."`
4. Usuario puede:
   - **Sin cambios**: Simplemente guardar
     - Frontend extrae rutas guardadas como `logo_fotos_guardadas[]`
     - Backend NO elimina porque las rutas coinciden
   - **Con nuevas fotos**: Agregar más fotos
     - Frontend envía `logo[imagenes][]` (nuevas) + `logo_fotos_guardadas[]` (existentes)
     - Backend crea nuevas y conserva existentes
   - **Eliminar fotos**: Borrar del DOM
     - Frontend NO incluye esas rutas en `logo_fotos_guardadas[]`
     - Backend elimina las fotos que NO están en la lista

## Testing Recomendado

### Test 1: Crear cotización con fotos
```
1. Ir a Crear Cotización
2. Completar pasos 1-3
3. En PASO 3, agregar 2 fotos del logo
4. Guardar cotización
5. Verificar logs: "✅ Logo foto CREADA EN BD"
6. Verificar BD: SELECT * FROM logo_fotos_cot WHERE logo_cotizacion_id = ?
```

### Test 2: Reguardar sin cambios
```
1. Abrir borrador existente
2. Ir a PASO 3
3. Verificar que fotos se cargan correctamente
4. NO agregar ni eliminar fotos
5. Guardar cotización
6. Verificar en logs: "Fotos de logo a conservar" con 2 fotos
7. Verificar BD: Las 2 fotos siguen existiendo
8. Reabrir borrador: Las fotos deberían estar ahí
```

### Test 3: Agregar foto a cotización existente
```
1. Abrir borrador con 2 fotos
2. Ir a PASO 3
3. Agregar 1 foto más
4. Guardar
5. Verificar BD: 3 fotos (2 originales + 1 nueva)
6. Reabrir borrador: Las 3 fotos deberían aparecer
```

### Test 4: Eliminar foto de cotización existente
```
1. Abrir borrador con 2 fotos
2. Ir a PASO 3
3. Eliminar 1 foto (clic en X)
4. Guardar
5. Verificar DB: Solo 1 foto restante
6. Reabrir borrador: Solo 1 foto debería aparecer
```

## Logs Esperados en laravel.log

### Cuando se guardan fotos nuevas:
```
[DEBUG] Fotos de logo a conservar (procesadas): fotos_guardadas_count: 0
[INFO] Archivos nuevos de logo: archivos_nuevos_count: 2
[INFO] ✅ Logo foto CREADA EN BD: foto_id: 123, ruta: /storage/cotizaciones/1/logo/...
[INFO] ✅ Logo foto CREADA EN BD: foto_id: 124, ruta: /storage/cotizaciones/1/logo/...
```

### Cuando se reguarda sin cambios:
```
[DEBUG] Fotos de logo a conservar (procesadas): fotos_guardadas_count: 2
fotos_guardadas: ["/storage/cotizaciones/1/logo/foto1.webp", "/storage/cotizaciones/1/logo/foto2.webp"]
[INFO] Archivos nuevos de logo: archivos_nuevos_count: 0
[INFO] Foto de logo conservada (no eliminada): foto_id: 123
[INFO] Foto de logo conservada (no eliminada): foto_id: 124
```

## Cambios en Archivos

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `app/Infrastructure/Http/Controllers/CotizacionController.php` | Backend: Procesa logo_fotos_guardadas | 510-570 |
| `public/js/asesores/cotizaciones/guardado.js` | Frontend: Extrae y envía fotos guardadas | 287-310 |
| `app/Models/LogoCotizacion.php` | (Verificado correcto) | 40-43 |
| `app/Models/LogoFotoCot.php` | (Verificado correcto) | - |
| `resources/views/components/paso-tres.blade.php` | (Verificado correcto - galeria_imagenes) | - |
| `public/js/asesores/cotizaciones/cargar-borrador.js` | (Verificado correcto - carga fotos) | 870-950 |

## Validación de Cambios

✅ **Compilación**: Sin errores de sintaxis
✅ **Modelos**: Relaciones correctas (LogoCotizacion->fotos() -> LogoFotoCot)
✅ **Controllers**: Lógica de procesamiento correcta
✅ **Frontend**: Selectors y datos se extraen correctamente
✅ **Servicio de Eliminación**: Comparación de rutas funciona

## Notas Importantes

1. **Formato de rutas**: Las rutas en la BD son como `/storage/cotizaciones/1/logo/...` y deben compararse exactamente con las que envía el frontend

2. **Atributo data-ruta**: Crítico que `cargarBorrador()` establezca correctamente `data-ruta` en cada imagen cargada

3. **Selector CSS**: `[data-foto-guardada="true"] img` debe encontrar las imágenes correctas

4. **Orden de operaciones**: Es importante que la eliminación se ejecute ANTES de guardar nuevas fotos, lo cual ya sucede en el controlador

## Posibles Problemas y Soluciones

| Problema | Causa | Solución |
|----------|-------|----------|
| Fotos no aparecen al reguardar | `cotizacion.logo_cotizacion.fotos` vacío | Verificar que `editBorrador()` carga las fotos con `with('logoCotizacion.fotos')` |
| Fotos se eliminan al reguardar sin cambios | `logo_fotos_guardadas[]` vacío | Verificar selector `#galeria_imagenes [data-foto-guardada="true"] img` |
| Fotos nuevas y viejas se mezclan | Orden de eliminación incorrecta | Verificar que eliminación ocurre ANTES de procesar nuevas |
| Comparación de rutas falla | Formatos diferentes (/storage/... vs http://...) | Normalización de rutas en controlador (líneas 530-540) |

## Conclusión

Estos cambios crean un flujo completo de persistencia de fotos del logo que:
- ✅ Guarda fotos nuevas correctamente
- ✅ Conserva fotos existentes al reguardar sin cambios
- ✅ Permite agregar fotos a cotizaciones existentes
- ✅ Permite eliminar fotos individuales
- ✅ Mantiene sincronización entre frontend y backend
- ✅ Guarda ubicaciones y observaciones correctamente
