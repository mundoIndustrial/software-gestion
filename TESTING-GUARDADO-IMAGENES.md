# Guía de Testing: Guardado de Imágenes en Cotizaciones Tipo Prenda

## Requisitos Previos
- El servidor está corriendo
- Las migraciones están actualizadas
- El directorio `storage/public/` existe y tiene permisos de escritura
- El comando `php artisan storage:link` ha sido ejecutado

## Pasos de Testing

### 1. Crear una Nueva Cotización Tipo Prenda

1. Ir a `/asesores/cotizaciones/prenda/create`
2. Completar datos básicos:
   - **Cliente**: "Cliente Test"
   - **Tipo de Venta**: "M" (Mayoreo)
   - **Asesor**: Tu nombre
   - **Fecha**: Hoy

### 2. Agregar Primera Prenda

1. Hacer clic en "AGREGAR PRENDA"
2. Completar:
   - **Nombre Prenda**: "CAMISETA BÁSICA"
   - **Tallas**: Seleccionar S, M, L
   - **Descripción**: "Camiseta 100% algodón"

3. **Subir Fotos de Prenda** (hasta 3):
   - Hacer clic en "FOTOS PRENDA"
   - Seleccionar 2-3 imágenes JPG/PNG desde tu PC
   - Verificar que aparezcan en miniatura

4. **Subir Telas** (hasta 3):
   - Hacer clic en "TELA MUESTRA"
   - Seleccionar 1-2 imágenes de telas
   - Verificar que aparezcan en miniatura

### 3. Agregar Segunda Prenda

1. Hacer clic en "AGREGAR PRENDA"
2. Completar:
   - **Nombre Prenda**: "PANTALÓN JEAN"
   - **Tipo de Jean**: "SKINNY"
   - **Tallas**: M, L, XL
   - **Descripción**: "Jean azul oscuro"

3. Subir fotos (2 imágenes)
4. Subir telas (1 imagen)

### 4. Guardar como Borrador

1. Hacer clic en **"GUARDAR COMO BORRADOR"**
2. Esperar a que procese (verás un spinner)
3. Debería redirigir a la lista de cotizaciones

**Verificar en logs:**
```
[ÉXITO] 🖼️ Iniciando procesamiento de imágenes desde FormData
[ÉXITO] 📸 Guardando fotos de prenda
[ÉXITO] ✅ Fotos guardadas en prenda
[ÉXITO] 🧵 Guardando telas de prenda
[ÉXITO] ✅ Telas guardadas en prenda
[ÉXITO] 🎉 Procesamiento de imágenes completado
```

### 5. Verificar que se Guardaron las Imágenes

#### Opción A: Verificar en Base de Datos
```sql
-- Ver las prendas creadas
SELECT id, nombre_producto, fotos, telas 
FROM prendas_cotizacion_friendly 
ORDER BY id DESC LIMIT 2;

-- Verificar que fotos y telas contienen JSON arrays con rutas:
-- fotos: ["/storage/cotizaciones/123/prenda/123_prenda_20251205_001.jpg", ...]
-- telas: ["/storage/cotizaciones/123/tela/123_tela_20251205_001.jpg", ...]
```

#### Opción B: Verificar en File System
```powershell
# Ver archivos guardados en storage
ls storage/public/cotizaciones/

# Debería verse algo como:
# 123/
#   prenda/
#     123_prenda_20251205_001.jpg
#     123_prenda_20251205_002.jpg
#   tela/
#     123_tela_20251205_001.jpg
```

#### Opción C: Verificar en la UI
1. Ir a la lista de cotizaciones
2. Hacer clic en la cotización guardada
3. Ir al tab de "DETALLES" o "PRENDAS"
4. Debería mostrar las miniaturas de las fotos y telas

### 6. Editar el Borrador

1. Hacer clic en "EDITAR" el borrador
2. Agregar más imágenes a una prenda existente
3. Guardar de nuevo
4. Verificar que se agregaron nuevas imágenes sin borrar las anteriores

## Casos de Prueba Adicionales

### Caso: Cotización sin Imágenes
1. Crear una cotización sin subir imágenes
2. Guardar como borrador
3. **Esperado**: Debería guardarse sin errores (las imágenes son opcionales)

### Caso: Múltiples Prendas con Muchas Imágenes
1. Crear 5 prendas
2. Subir 3 fotos + 2 telas por prenda
3. Guardar
4. **Esperado**: Se guardaran todos los archivos sin problemas

### Caso: Archivos Grandes (> 5MB)
1. Intentar subir una imagen > 5MB
2. **Esperado**: Error de validación antes de enviar (o rechazo del servidor)

### Caso: Formatos No Permitidos
1. Intentar subir un .PDF o .TXT
2. **Esperado**: Error de validación

## Logs para Monitorear

Ubicación: `storage/logs/laravel.log`

**Buscar estas líneas para verificar éxito:**

```
🖼️ Iniciando procesamiento de imágenes desde FormData
📸 Guardando fotos de prenda
✅ Fotos guardadas en prenda
🧵 Guardando telas de prenda
✅ Telas guardadas en prenda
🎉 Procesamiento de imágenes completado
```

**Si hay errores:**
```
❌ Error procesando imágenes desde FormData
```

## Chequeo Final

Después de implementar, ejecutar:

```bash
# 1. Limpiar cache
php artisan cache:clear
php artisan config:clear

# 2. Verificar permisos en storage
ls -la storage/public/

# 3. Si es necesario, re-crear el link
php artisan storage:link

# 4. Ejecutar tests (si existen)
php artisan test tests/Feature/CotizacionesTest.php
```

## Problemas Comunes

### Las imágenes se guardan pero NO aparecen en la BD
**Causa**: El método `procesarImagenesDesdeFormData()` no se llama
**Solución**: Verificar que se haya agregado la línea en `guardar()`:
```php
$this->procesarImagenesDesdeFormData($request, $cotizacion, $datosFormulario);
```

### Error 413 "Payload Too Large"
**Causa**: Las imágenes son muy grandes o hay muchas
**Solución**: 
- Aumentar `upload_max_filesize` en `php.ini`
- Aumentar `post_max_size` en `php.ini`
- Reducir tamaño de imágenes

### Las imágenes se guardan en storage pero las rutas no se guardan en BD
**Causa**: Error en la actualización de la prenda
**Solución**: Verificar logs, posible falta de permisos DB

### "Disk [public] does not exist"
**Causa**: No se ejecutó `php artisan storage:link`
**Solución**:
```bash
php artisan storage:link
```

## Rollback (Si es Necesario)

Si algo sale mal y necesitas revertir:

```bash
# 1. Revertir código
git checkout -- app/Http/Controllers/Asesores/CotizacionesController.php
git checkout -- app/Http/Requests/StoreCotizacionRequest.php

# 2. Limpiar imágenes de prueba
rm -rf storage/public/cotizaciones/*

# 3. Borrar cotizaciones de prueba de BD
DELETE FROM cotizaciones WHERE cliente = 'Cliente Test';
DELETE FROM prendas_cotizacion_friendly WHERE nombre_producto IN ('CAMISETA BÁSICA', 'PANTALÓN JEAN');
```
