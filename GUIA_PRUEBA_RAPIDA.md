# 🧪 Guía de Prueba Rápida

## ✅ Pasos para verificar que todo funciona

### 1. Abre cualquiera de estas páginas:
- [ ] `/asesores/pedidos/crear-pedido-nuevo` (Crear pedido nuevo)
- [ ] `/asesores/pedidos/edit/{id}` (Editar pedido existente)
- [ ] `/asesores/pedidos` (Índice de pedidos)
- [ ] `/supervisor-pedidos` (Vista de supervisor)

### 2. En el formulario, marca un checkbox de proceso:
- [ ] ☑ Reflectivo
- [ ] ☑ Bordado
- [ ] ☑ Estampado
- [ ] ☑ DTF
- [ ] ☑ Sublimado

### 3. Se debe abrir el modal del proceso
**Verifica que aparezca:**
- [ ] Título del proceso con icono
- [ ] Campo de UBICACIONES (textarea)
- [ ] Campo de OBSERVACIONES (textarea)
- [ ] Botón "Aplicar para todas"
- [ ] Botón "Editar tallas específicas" ← **El clave**
- [ ] Campo de IMÁGENES (3 previews)

### 4. Haz click en "Editar tallas específicas"
**Debe abrir un segundo modal que muestre:**
- [ ] Tarjetas de tallas en una cuadrícula
- [ ] Cada tarjeta debe tener:
  - [ ] ☑ Checkbox de selección
  - [ ] Nombre de la talla (M, L, S, etc.)
  - [ ] Campo de CANTIDAD (número)
  - [ ] **📍 Ubicación(es)** ← NUEVO
  - [ ] **💬 Observaciones** ← NUEVO
  - [ ] **📷 Imagen para esta talla** ← NUEVO

### 5. Prueba cada funcionalidad nueva:

#### En Ubicaciones:
- [ ] Escribe "Frente" en el input
- [ ] Haz click en "+ Agregar"
- [ ] Aparecerá un tag con "Frente ×"
- [ ] Agrega más ubicaciones: "Espalda", "Manga", etc.
- [ ] Verifica que puedas eliminar cada ubicación haciendo click en "×"

#### En Observaciones:
- [ ] Escribe algo como "Color rojo fuerte, bordado fino"
- [ ] El texto debe guardarse al cambiar de foco

#### En Imagen:
- [ ] Haz click en el área de imagen
- [ ] Selecciona una imagen de tu computadora
- [ ] Debe aparecer un preview de esa imagen
- [ ] Aparecerá un botón "Eliminar"
- [ ] Prueba eliminar y agregar nuevamente

### 6. Guarda los datos:
- [ ] Haz click en "Guardar Tallas" en el modal de tallas
- [ ] Debe cerrar el modal de tallas automaticamente
- [ ] Debe permanecer abierto el modal del proceso
- [ ] El resumen debe actualizarse

### 7. Agrega el proceso final:
- [ ] Haz click en "Agregar Estampado" (o el proceso que hayas elegido)
- [ ] Debe cerrar el modal del proceso
- [ ] Debe mostrar una tarjeta del proceso agregado con los datos

### 8. (Opcional) Verifica en la consola del navegador:
- [ ] Abre F12 → Consola
- [ ] Ejecuta: `console.log(window.procesosSeleccionados)`
- [ ] Debes ver tu proceso con estructura como:
```javascript
{
    estampado: {
        tipo: "estampado",
        datos: {
            ubicaciones: [...],
            observaciones: "...",
            tallas: { dama: {...}, caballero: {...} },
            datosExtendidos: {
                dama: {
                    M: { ubicaciones, observaciones, imagen },
                    L: { ubicaciones, observaciones, imagen }
                }
            }
        }
    }
}
```

## 🔍 Checklist Completo

### Funcionalidad Básica
- [ ] Modal de proceso abre correctamente
- [ ] Botón "Editar tallas específicas" abre el editor
- [ ] Las tarjetas de tallas se renderizan

### Ubicaciones
- [ ] Se puede agregar ubicación
- [ ] Se puede eliminar ubicación
- [ ] Se pueden agregar múltiples ubicaciones
- [ ] Los tags se muestran correctamente

### Observaciones
- [ ] Se puede escribir texto
- [ ] El texto persiste después de cambiar de foco
- [ ] Se puede editar en cualquier momento

### Imágenes
- [ ] Se puede seleccionar una imagen
- [ ] El preview se muestra
- [ ] Se puede eliminar la imagen
- [ ] Se puede cambiar la imagen

### Guardado de Datos
- [ ] Click en "Guardar Tallas" guarda todo
- [ ] Los datos están en `window.procesosSeleccionados`
- [ ] Se pueden agregar múltiples procesos
- [ ] Cada proceso mantiene sus datos

## 🐛 Posibles Problemas y Soluciones

### "El editor de tallas no tiene los campos nuevos"
**Solución:**
- [ ] Verifica que `extension-editor-tallas-multiproducto.js` esté loaded
- [ ] Abre Devtools → Network → ¿Se cargó el archivo?
- [ ] Si no, limpia caché (Ctrl+Shift+Supr)

### "Los botones de agregar ubicación no funcionan"
**Solución:**
- [ ] Abre la consola y busca errores en rojo
- [ ] Verifica que `extension-guardar-datos-tallas-extendida.js` esté loaded
- [ ] Intenta recargar la página (F5)

### "Las imágenes no se guardan"
**Solución:**
- [ ] Verifica que el archivo sea imagen válida (PNG, JPG, GIF, etc.)
- [ ] Verifica el tamaño (no debe ser demasiado grande)
- [ ] Abre consola y busca errores

### "Datos desaparecen al recargar"
**Comportamiento esperado:**
- Los datos se guardan en memoria (no en DB)
- Al recargar se pierden
- Para persistir necesitas guardar el pedido completo

## 💡 Tips Útiles

1. **Para ver los datos en JSON limpio:**
   ```javascript
   JSON.stringify(window.procesosSeleccionados, null, 2)
   ```

2. **Para resetear todo:**
   ```javascript
   window.procesosSeleccionados = {}
   window.datosExtendidosTallasProceso = { dama: {}, caballero: {}, sobremedida: {} }
   ```

3. **Para ver qué funciones están disponibles:**
   ```javascript
   Object.keys(window).filter(k => k.includes('agregarUbicacion') || k.includes('Imagen') || k.includes('Observacion'))
   ```

## 🎯 Resultado Esperado Final

Cuando todo esté funcionando, deberías poder:

1. ✅ Crear un proceso con datos generales
2. ✅ Abrir el editor de tallas
3. ✅ Para CADA TALLA:
   - Especificar una o múltiples ubicaciones
   - Escribir observaciones particulares
   - Cargar una imagen única
4. ✅ Guardar todo al hacer click en "Guardar Tallas"
5. ✅ Ver los datos guardados en `window.procesosSeleccionados`
6. ✅ Enviar al backend con toda la información detallada por talla

---

**Si todo funciona correctamente ✅**
- Felicidades, la implementación está completa y funcionando!
- Los datos están listos para ser enviados al backend

**Si algo no funciona ❌**
- Revisa la consola para errores específicos
- Verifica que los archivos JS estén incluidos en la vista
- Intenta con una prenda/proceso diferente
