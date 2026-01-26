# 🔍 Logs de Debug Agregados para Procesos

## Resumen
Se han agregado logs detallados en **4 archivos críticos** para rastrear los problemas reportados:

1.  **Nombre del proceso no se muestra**
2.  **Click en tarjeta de proceso no funciona**
3.  **Ubicaciones muestran JSON**
4.  **Imágenes no cargan en galería**

---

## 📝 Cambios Realizados

### 1. `renderizador-tarjetas-procesos.js`

#### Función: `window.renderizarTarjetasProcesos()`
```javascript
✅ Logs agregados:
- 🎨 [RENDER-PROCESOS] Renderizando tarjetas de procesos...
- 📊 [RENDER-PROCESOS] Procesos encontrados
-  [RENDER-PROCESOS] Sin procesos configurados
- 📝 [RENDER-PROCESOS] Renderizando {tipo}
- [RENDER-PROCESOS] Renderizado completado
```

#### Función: `generarTarjetaProceso(tipo, datos)`
```javascript
✅ Logs agregados:
- 🎯 [GENERAR-TARJETA] Generando tarjeta para tipo
- 📛 [GENERAR-TARJETA] Nombre resuelto
- 📏 [GENERAR-TARJETA] Tallas para {tipo}
-  [GENERAR-TARJETA] Ubicaciones raw
- [GENERAR-TARJETA] Ubicaciones parseadas como JSON
-  [GENERAR-TARJETA] No es JSON, tratando como string
- 📄 [GENERAR-TARJETA] Ubicaciones texto final
```

**Problema Detectado**: La línea que resuelve el nombre ahora intenta múltiples fuentes:
```javascript
const nombre = nombresProcesos[tipo] || datos.nombre || datos.nombre_proceso || datos.descripcion || datos.tipo_proceso || tipo.toUpperCase();
```

#### Función: `window.editarProcesoDesdeModal(tipo)`
```javascript
✅ Logs agregados:
-  [EDITAR-PROCESO] Iniciando edición del proceso
- 📦 [EDITAR-PROCESO] Datos del proceso
- [EDITAR-PROCESO] Datos encontrados, cargando en modal
- 🪟 [EDITAR-PROCESO] Abriendo modal genérico de proceso en modo edición
-  [EDITAR-PROCESO] No existe window.abrirModalProcesoGenerico
```

#### Función: `window.abrirGaleriaImagenesProceso(tipoProceso)`
```javascript
✅ Logs agregados:
- 🖼️ [GALERIA] Abriendo galería para proceso
- 🖼️ [GALERIA] Datos del proceso
- 📸 [GALERIA] Imágenes encontradas
- 🖼️ [GALERIA] URL primera imagen procesada
- 🖼️ [GALERIA] Galería modal creada y agregada al DOM

✅ Nueva lógica de procesamiento de URLs:
function procesarUrlImagen(img) {
    if (img instanceof File) return URL.createObjectURL(img);
    if (typeof img === 'string') {
        // Agregar /storage/ si no empieza con / o http
        return img.startsWith('/') || img.startsWith('http') ? img : '/storage/' + img;
    }
    if (typeof img === 'object' && img) {
        const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
        // Aplicar el mismo procesamiento
        return (typeof url === 'string') ? (url.startsWith('/') || url.startsWith('http') ? url : '/storage/' + url) : '';
    }
    return '';
}
```

#### Funciones de Navegación: `navegarGaleriaImagenesProceso()`, `irAImagenProceso()`, `cerrarGaleriaImagenesProceso()`
```javascript
✅ Logs agregados en cada función:
- 🔄 [GALERIA] Navegando galería en dirección
-  [GALERIA] Índice calculado
- 🖼️ [GALERIA] Cambiando imagen a índice
- [GALERIA] Navegación completada

- 👉 [GALERIA] Ir a imagen
- 🖼️ [GALERIA] Mostrando imagen en índice

-  [GALERIA] Cerrando galería
- [GALERIA] Galería removida del DOM
```

---

### 2. `prenda-editor-modal.js`

#### Función: `abrirEditarPrendaEspecifica(prendasIndex)`
```javascript
✅ Logs de transformación de procesos:

En la sección de transformación de procesos se agregó:
-  [EDITAR-PRENDA-PROCESOS] Transformando proceso
- 📸 Imagen transformada
- Proceso transformado
- 🔬 [EDITAR-PRENDA] Procesos para modal

Cada proceso muestra:
{
    procesoId: proc.id,
    tipo: proc.tipo_proceso,
    nombre: proc.nombre,
    nombre_proceso: proc.nombre_proceso,
    tieneImagenes: !!proc.imagenes,
    countImagenes: proc.imagenes?.length || 0,
    tieneUbicaciones: !!proc.ubicaciones,
    ubicaciones: proc.ubicaciones
}
```

---

### 3. `services/prenda-editor.js`

#### Función: `cargarPrendaEnModal(prenda, prendaIndex)`
```javascript
✅ Logs agregados:
- 🔄 [CARGAR-PRENDA] Iniciando carga de prenda en modal
-  [CARGAR-PRENDA] Sobre de cargar procesos...
- [CARGAR-PRENDA] Prenda cargada completamente
-  [CARGAR-PRENDA] Error
```

#### Función: `cargarProcesos(prenda)` - **IMPORTANTE**
```javascript
✅ Logs detallados agregados:
-  [CARGAR-PROCESOS] Sin procesos en la prenda
- 📋 [CARGAR-PROCESOS] Cargando procesos (total y detalles)
- 📌 [CARGAR-PROCESOS] Procesando cada proceso por índice
  * nombreProceso
  * tipoProceso
  * tieneImagenes
  * countImagenes
- 🖼️ [CARGAR-PROCESOS] Imagen procesada (para cada imagen)
- [CARGAR-PROCESOS] Proceso cargado
- ☑️ [CARGAR-PROCESOS] Marcando checkbox
-  [CARGAR-PROCESOS] No se encontró checkbox
- 📊 [CARGAR-PROCESOS] Procesos seleccionados finales
- 🎨 [CARGAR-PROCESOS] Renderizando tarjetas
-  [CARGAR-PROCESOS] window.renderizarTarjetasProcesos no existe
```

---

## 🎯 Qué Observar en Console

Cuando hagas click en "Editar Prenda", deberías ver una secuencia como:

```
🔄 [CARGAR-PRENDA] Iniciando carga de prenda en modal: {...}
 [CARGAR-PRENDA] Sobre de cargar procesos...
📋 [CARGAR-PROCESOS] Cargando procesos: {...}
📌 [CARGAR-PROCESOS] Procesando [0] tipo="reflectivo"
✅ [CARGAR-PROCESOS] Proceso "reflectivo" cargado: {...}
☑️ [CARGAR-PROCESOS] Marcando checkbox para "reflectivo"
📊 [CARGAR-PROCESOS] Procesos seleccionados finales: {...}
🎨 [CARGAR-PROCESOS] Renderizando tarjetas...
🎨 [RENDER-PROCESOS] Renderizando tarjetas de procesos...
📊 [RENDER-PROCESOS] Procesos encontrados: ["reflectivo"]
📝 [RENDER-PROCESOS] Renderizando reflectivo: {...}
🎯 [GENERAR-TARJETA] Generando tarjeta para tipo: reflectivo
📛 [GENERAR-TARJETA] Nombre resuelto para reflectivo: Reflectivo
✅ [RENDER-PROCESOS] Renderizado completado
✅ [CARGAR-PRENDA] Prenda cargada completamente
```

---

## 🔍 Cómo Debuggear

### Para el Nombre del Proceso:
1. Abre DevTools → Console
2. Busca el log: `📛 [GENERAR-TARJETA] Nombre resuelto`
3. Verifica si muestra el nombre correcto
4. Si dice "reflectivo" o "settings", hay un problema en la resolución

### Para Ubicaciones:
1. Busca el log: ` [GENERAR-TARJETA] Ubicaciones raw`
2. Verifica el tipo de datos:
   - Si es string JSON: `✅ Ubicaciones parseadas como JSON`
   - Si es array: El log lo dirá
   - Si falla: ` No es JSON, tratando como string`

### Para Imágenes de Proceso:
1. Busca: `🖼️ [GALERIA] Abriendo galería`
2. Verifica `📸 [GALERIA] Imágenes encontradas: X`
3. Busca: `🖼️ [GALERIA] URL primera imagen procesada`
4. Verifica la URL tenga `/storage/` si es necesario

### Para Click en Tarjeta:
1. Busca: ` [EDITAR-PROCESO] Iniciando edición`
2. Si ves logs de "No existe window.abrirModalProcesoGenerico", ese es el problema
3. Verifica que `📦 [EDITAR-PROCESO] Datos del proceso` muestre datos válidos

---

## 🛠️ Próximos Pasos de Debugging

Una vez hayas revisado los logs:

1. **Si el nombre no sale**: Revisa si `nombresProcesos[tipo]` tiene la key correcta
2. **Si ubicaciones salen en JSON**: Revisa el parsing de JSON en `generarTarjetaProceso`
3. **Si imágenes no cargan**: Revisa la URL en el log `procesarUrlImagen`
4. **Si click no funciona**: Verifica que `window.abrirModalProcesoGenerico` existe

---

## 📌 Notas Importantes

- Los logs están en **ESPAÑOL** con emojis para fácil búsqueda
- Todos los logs incluyen contexto en objetos con detalles relevantes
- Los logs se agrupan por funcionalidad
- Se pueden filtrar en Console con `[RENDER-PROCESOS]`, `[GALERIA]`, etc.

---

*Actualizado: 2026-01-25*
