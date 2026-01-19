# Implementación: Consumo de Endpoint para Eliminar Imágenes

## Fecha: 19 de Enero de 2026

## Resumen
Se implementó el consumo del endpoint `DELETE /imagen/{tipo}/{id}` para eliminar imágenes de prendas y telas directamente desde el modal de edición de prendas.

## Cambios Realizados

### 1. **Almacenamiento de IDs en HTML** (Líneas 145-163)
```javascript
// Prenda images - Agregado data-img-id
<button class="btn-eliminar-prenda" 
        data-img-idx="${imgIdx}" 
        data-img-id="${imgId || ''}"
```

**Por qué**: Cada imagen en la base de datos tiene un ID único que se necesita para el DELETE. Se extrae de:
- `img.id` 
- `img.foto_id`
- `img.image_id`

### 2. **Event Listener Async para Imágenes de Prenda** (Líneas 259-328)

#### Flujo:
1. **Click en botón eliminar**
   ```javascript
   btn.addEventListener('click', async (e) => { ... })
   ```

2. **Validar si tiene ID**
   - Si NO tiene ID → Es imagen nueva, marcar localmente
   - Si SÍ tiene ID → Proceder con eliminación remota

3. **Pedir confirmación**
   ```javascript
   const confirmacion = await Swal.fire({
       title: '¿Eliminar imagen?',
       icon: 'warning',
       showCancelButton: true
   })
   ```

4. **Mostrar loading**
   ```javascript
   Swal.fire({
       title: 'Eliminando...',
       didOpen: () => Swal.showLoading()
   })
   ```

5. **Llamar endpoint DELETE**
   ```javascript
   const response = await fetch(`/imagen/prenda/${imgId}`, {
       method: 'DELETE',
       headers: {
           'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
           'Accept': 'application/json'
       }
   })
   ```

6. **Manejar respuesta**
   - ✅ Éxito: Eliminar del DOM + mostrar toast
   - ❌ Error: Mostrar mensaje de error

### 3. **Event Listener Async para Imágenes de Tela** (Líneas 331-400)

**Idéntico al de prenda, pero**:
- Usa `DELETE /imagen/tela/{imgId}`
- Mensaje de confirmación para telas

## Endpoint Backend

**Ruta**: `DELETE /imagen/{tipo}/{id}`  
**Controlador**: `SupervisorPedidosController@deleteImage`  
**Archivos**: `app/Http/Controllers/SupervisorPedidosController.php` (línea 1151)

### Qué hace el endpoint:
1. Valida el tipo (prenda, tela, logo)
2. Obtiene el modelo de BD correspondiente
3. Elimina archivos físicos:
   - `ruta_original`
   - `ruta_webp`
4. Elimina registro de BD (forceDelete para SoftDeletes)
5. Retorna `{'success': true}` o error

### Respuesta esperada:
```json
{
    "success": true,
    "message": "Imagen eliminada correctamente"
}
```

## Mejoras Implementadas

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Eliminación** | Solo marcar local | Eliminar inmediato en servidor |
| **Feedback** | Toast genérico | Confirmación + Loading + Resultado |
| **Error handling** | Ninguno | Try-catch con mensajes claros |
| **DOM** | Quedaba visible | Se elimina inmediatamente |
| **CSRF** | Ninguna validación | Validación de token |

## Flujo Completo

```
Usuario hace click en "🗑️ Eliminar"
        ↓
¿Tiene ID en BD?
    ├─ NO → Marcar localmente (comportamiento antiguo)
    └─ SÍ → Pedir confirmación
                ↓
            Usuario confirma?
                ├─ NO → Cancelar
                └─ SÍ → Mostrar loading
                        ↓
                    DELETE /imagen/prenda/{id}
                        ↓
                    ¿Éxito?
                        ├─ NO → Mostrar error
                        └─ SÍ → Eliminar del DOM + Toast
```

## Cómo Usar

1. Abrir modal de edición de prenda
2. Ver imágenes (prenda o tela)
3. Hacer click en botón "🗑️ Eliminar"
4. Confirmar eliminación
5. Ver toast de éxito/error

## Casos Especiales

### Imagen sin guardar (nueva)
- Si la imagen no tiene `id`, se marca como `data-eliminada='true'`
- Se elimina en el siguiente guardado con `accion: 'eliminar'`

### Imagen con error
- Si falla el DELETE, se muestra SweetAlert con el mensaje de error
- El DOM NO se elimina
- Usuario puede reintentar

## Testing

Para probar:
1. Crear pedido con imágenes
2. Abrir modal de edición
3. Hacer click en eliminar
4. Verificar que la imagen se elimine de BD y DOM
5. Revisar logs en `storage/logs/laravel.log`

## Dependencias

- **SweetAlert2**: Para modales de confirmación
- **Fetch API**: Para consumo de endpoint
- **CSRF Token**: Debe estar en `<meta name="csrf-token">`

## Archivos Modificados

- `public/js/componentes/prenda-editor-modal.js` (657 líneas)
  - Líneas 145-163: Agregado data-img-id al HTML
  - Líneas 259-328: Event listener para prenda
  - Líneas 331-400: Event listener para tela

## Notas Importantes

1. ⚠️ El endpoint usa `forceDelete()` → Eliminación permanente
2. ⚠️ Se eliminan archivos físicos del storage
3. ✅ Hay logs en `laravel.log` para auditoría
4. ✅ CSRF token es validado automáticamente
5. ✅ Compatible con SoftDeletes en la BD

## Próximos Pasos

Si necesitas:
- [ ] Agregar imágenes (upload)
- [ ] Cambiar imágenes (reemplazar)
- [ ] Sincronizar con base de datos
- [ ] Agregar más tipos de imágenes (logo, reflectivo)
