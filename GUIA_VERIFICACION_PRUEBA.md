# ✅ GUÍA DE VERIFICACIÓN Y PRUEBA

## 1. Verificación de Código (Ya Completada)

✅ **Sintaxis validada** en los 3 archivos principales:
- `app/Application/Pedidos/DTOs/ActualizarPrendaCompletaDTO.php` → No errors
- `app/Application/Pedidos/UseCases/ActualizarPrendaCompletaUseCase.php` → No errors
- `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` → No errors

---

## 2. Prueba Manual (Paso a Paso)

### Paso 1: Preparación
1. Abre la aplicación en navegador
2. Login como Asesor
3. Navega a: **Asesores → Pedidos en Producción**

### Paso 2: Abrir Prenda para Editar
1. Selecciona un pedido (ej: Pedido #3)
2. Busca una prenda existente (ej: Prenda ID 3)
3. Haz clic en el botón **"Editar"** 

**Esperado**: Modal "Editar Prenda" abre correctamente

### Paso 3: Modificar Telas
1. En el modal, ve a la sección **"Telas/Colores"**
2. **Opción A - Agregar Tela Nueva**:
   - Haz clic en **"Agregar Tela"**
   - Selecciona un Color diferente (ej: ROJO RETRO)
   - Selecciona una Tela diferente (ej: TELA_RETRO)
   - La fila se agrega a la tabla

3. **Para la tela nueva**, haz clic en **"Agregar Imagen"**:
   - Selecciona una imagen JPG/PNG del filesystem
   - Se abre un preview con nombre y tamaño
   - Verifica que aparezca en el campo imagenes

### Paso 4: Guardar Cambios
1. Desplázate al botón **"Guardar Cambios"** al final del modal
2. Haz clic en **"Guardar Cambios"**
3. Espera a que la operación complete (~5-10 segundos)

**Esperado**: 
- Modal se cierra automáticamente ✅
- No aparecen errores en rojo ✅
- Mensaje de éxito (si lo hay) ✅

---

## 3. Verificación en Logs

### Archivo: `storage/logs/laravel.log`

**Abre el archivo y busca por**: `[PedidosProduccionController] Imagen de tela procesada`

**Debería verse**:
```json
[2026-02-04 16:15:30] local.INFO: [PedidosProduccionController] Imagen de tela procesada {
    "key": "fotos_tela[0]",
    "indice": 0,
    "archivo": "tela_roja.jpg",
    "ruta_webp": "/storage/pedidos/3/tela/telas_20260204161530_XyZ123.webp",
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204161530_XyZ123.jpg"
}
```

### Busca también por: `[ActualizarPrendaCompletaUseCase] Foto creada`

**Debería verse**:
```json
[2026-02-04 16:15:30] local.INFO: [ActualizarPrendaCompletaUseCase] Foto creada {
    "foto_id": 456,
    "color_tela_id": 10,
    "ruta_original": "/storage/pedidos/3/tela/telas_20260204161530_XyZ123.jpg"
}
```

---

## 4. Verificación en Base de Datos

### Opción A: PHPMyAdmin

1. Abre PHPMyAdmin
2. Selecciona la base de datos
3. Navega a tabla: `prenda_fotos_tela_pedido`
4. Filtra por la prenda (ID 3):
   ```sql
   SELECT * FROM prenda_fotos_tela_pedido 
   WHERE prenda_pedido_colores_telas_id IN (
       SELECT id FROM prenda_pedido_colores_telas 
       WHERE prenda_pedido_id = 3
   )
   ORDER BY id DESC LIMIT 5
   ```

### Esperado en resultados:
```
id | prenda_pedido_colores_telas_id | ruta_original           | ruta_webp                  | orden
---|--------------------------------|------------------------|----------------------------|------
456| 10                             | /storage/...jpg        | /storage/...webp           | 1
```

✅ La `ruta_original` debe tener una ruta válida (NO NULL)
✅ La `ruta_webp` debe tener una ruta válida (NO NULL)
✅ El `prenda_pedido_colores_telas_id` debe coincidir con la nueva tela

### Opción B: Artisan Console

```bash
php artisan tinker

# Ver últimas fotos creadas
\App\Models\PrendaFotoTelaPedido::latest()->take(5)->get()

# Ver fotos de prenda específica
\App\Models\PrendaPedido::find(3)->fotosTelas()->get()
```

---

## 5. Verificación en Frontend (Operario)

### Paso 1: Navegar a Operario
1. Logout del Asesor (si estás en esa cuenta)
2. Login como Operario
3. Navega a: **Operario → Pedidos**

### Paso 2: Buscar el Pedido
1. Busca por número del pedido (ej: Pedido #3)
2. Haz clic para abrir

### Paso 3: Ver Galería de Telas
1. En la vista de prenda, busca la sección de **"Telas"** o **"Colores"**
2. Debería haber una **galería de miniaturas** mostrando cada tela

### Esperado:
✅ La tela nueva debería aparecer en la galería
✅ La imagen debería cargarse correctamente
✅ Al hacer hover, debería mostrar la imagen grande
✅ Las imágenes deben estar en formato WebP (más rápido)

---

## 6. Checklist de Validación

- [ ] Modal de edición abre sin errores
- [ ] Se puede agregar tela nueva (color + fabric)
- [ ] Se puede agregar imagen a tela nueva
- [ ] El save completa sin errores
- [ ] Log muestra: "Imagen de tela procesada"
- [ ] Log muestra: "Foto creada"
- [ ] BD tiene registro en `prenda_fotos_tela_pedido`
- [ ] `ruta_original` NOT NULL ✅
- [ ] `ruta_webp` NOT NULL ✅
- [ ] Operario ve la imagen en galería
- [ ] Imagen carga rápido (WebP optimizado)

---

## 7. Troubleshooting

### Problema: Log muestra "Imagen de tela procesada" pero NO "Foto creada"

**Causa**: UseCase no está encontrando la ruta procesada
**Verificación**: 
1. Log debe mostrar: `"fotos_procesadas_disponibles": 1` (o más)
2. Log de UseCase debe mostrar: `"Usando ruta procesada para foto nueva"`

**Solución**:
- Verificar que Controller pasa `$fotosTelasProcesadas` al DTO
- Verificar que DTO recibe como último parámetro

### Problema: "Foto ignorada (sin color_tela_id o ruta)"

**Causa**: Las rutas procesadas no llegaron al UseCase

**Verificación**:
1. Ver log: ¿Aparece "Imagen de tela procesada"?
   - SI: El archivo se procesó ✅
   - NO: El error está en Controller ❌

2. Ver log: ¿Aparece `"fotos_procesadas_disponibles": 1`?
   - SI: Se pasó al UseCase ✅
   - NO: El problema está en DTO ❌

**Solución**:
```php
// En PedidosProduccionController.php línea 947
// Verificar que se está pasando:
$dto = ActualizarPrendaCompletaDTO::fromRequest(
    $validated['prenda_id'], 
    $validated, 
    $imagenesGuardadas, 
    $imagenesExistentes, 
    $fotosTelasProcesadas  // ← Este parámetro DEBE estar
);
```

### Problema: Error "Unknown column: prenda_pedido_colores_telas_id"

**Causa**: Typo en nombre de columna

**Verificación**:
1. Abre BD
2. Ver estructura de tabla: `prenda_fotos_tela_pedido`
3. Verificar nombres exactos de columnas

**Solución**:
Si el nombre de columna es diferente, actualizar en UseCase línea ~486:
```php
$datosFoto = [
    'prenda_pedido_colores_telas_id' => $colorTelaId,  // ← Nombre correcto
    // ...
];
```

---

## 8. Comandos Útiles

### Ver logs en tiempo real
```bash
tail -f storage/logs/laravel.log | grep "Foto\|tela\|procesada"
```

### Limpiar cache
```bash
php artisan cache:clear
php artisan config:clear
```

### Resetear storage de fotos (⚠️ CUIDADO)
```bash
rm -rf storage/app/public/pedidos/*
# Luego regenerar symlink:
php artisan storage:link
```

### Ver tabla completa
```bash
php artisan tinker
\App\Models\PrendaFotoTelaPedido::select('id', 'prenda_pedido_colores_telas_id', 'ruta_original', 'orden')->get()
```

---

## 9. Indicadores de Éxito

| Indicador | Valor Esperado | Ubicación |
|-----------|---|---|
| Fotos procesadas | 1 o más | Log Controller |
| Fotos creadas | 1 o más | Log UseCase |
| Registros en BD | 1 o más | `prenda_fotos_tela_pedido` |
| ruta_original | /storage/... | Columna BD |
| ruta_webp | /storage/...webp | Columna BD |
| Galería operario | Visible | Frontend |

---

## 10. Siguiente Paso

Una vez validado que TODO funciona:
1. ✅ Hacer varias pruebas con diferentes imágenes
2. ✅ Probar editar foto existente + agregar foto nueva en el mismo save
3. ✅ Probar eliminar una tela (debería eliminar sus fotos)
4. ✅ Probar cambiar tela existente por otra diferente

---

**Documento de Validación**: 2026-02-04
**Versión**: 1.0
**Estado**: Ready for Testing ✅

