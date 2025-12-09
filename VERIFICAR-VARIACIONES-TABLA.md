# 🔍 VERIFICACIÓN - VARIACIONES ESPECÍFICAS TABLA

## ✅ CHECKLIST DE VERIFICACIÓN

### 1. Acceso a la Página
- [ ] Abre `http://servermi:8000/asesores/cotizaciones/prenda/crear`
- [ ] La página carga sin errores
- [ ] El formulario se muestra correctamente

### 2. Sección VARIACIONES ESPECÍFICAS
- [ ] La sección aparece después de "FOTOS DE LA PRENDA"
- [ ] Se muestra como una tabla (no como grid de tarjetas)
- [ ] La tabla tiene 3 columnas: Checkbox, Variación, Observación

### 3. Header de la Tabla
- [ ] Header tiene fondo azul gradiente
- [ ] Texto es blanco y bold
- [ ] Icono de check-circle en primera columna
- [ ] Icono de list en segunda columna
- [ ] Icono de comment en tercera columna

### 4. Filas de la Tabla
- [ ] Hay 4 filas: Manga, Bolsillos, Broche, Reflectivo
- [ ] Las filas alternan colores (blanco y gris)
- [ ] Cada fila tiene bordes horizontales sutiles
- [ ] El padding es generoso (no apretado)

### 5. Manga
- [ ] Checkbox está presente
- [ ] Icono de shirt (👕) aparece
- [ ] Select con opciones: Corta, Larga, 3/4, Raglan, Campana, Otra
- [ ] Input para observaciones está presente

### 6. Bolsillos
- [ ] Checkbox está presente
- [ ] Icono de square (📦) aparece
- [ ] Input para observaciones está presente
- [ ] Placeholder: "Ej: 4 bolsillos, con cierre..."

### 7. Broche/Botón
- [ ] Checkbox está presente
- [ ] Icono de link (🔗) aparece
- [ ] Select con opciones: Broche, Botón
- [ ] Input para observaciones está presente
- [ ] Placeholder: "Ej: Botones de madera..."

### 8. Reflectivo
- [ ] Checkbox está presente
- [ ] Icono de star (⭐) aparece
- [ ] Input para observaciones está presente
- [ ] Placeholder: "Ej: En brazos y espalda..."

### 9. Funcionalidad
- [ ] Puedo marcar/desmarcar checkboxes
- [ ] Puedo seleccionar opciones en los dropdowns
- [ ] Puedo escribir en los campos de texto
- [ ] Los datos se mantienen al cambiar entre campos

### 10. Guardado
- [ ] Completo el formulario
- [ ] Hago clic en "GUARDAR" o "ENVIAR"
- [ ] La cotización se guarda sin errores
- [ ] Los datos de variaciones se guardan en BD

## 🐛 SI ALGO NO FUNCIONA

### La tabla no aparece
1. Limpia el caché:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   ```
2. Recarga la página (Ctrl+F5)
3. Verifica que no haya errores en la consola (F12)

### Los estilos no se ven correctamente
1. Verifica que el navegador sea moderno (Chrome, Firefox, Safari, Edge)
2. Limpia el caché del navegador:
   - Presiona Ctrl+Shift+Delete
   - Selecciona "Archivos en caché"
   - Haz clic en "Borrar"
3. Recarga la página

### Los datos no se guardan
1. Abre la consola del navegador (F12)
2. Verifica que no haya errores JavaScript
3. Revisa la pestaña "Network" para ver si hay errores en las peticiones
4. Verifica que el backend esté funcionando correctamente

### Los checkboxes no funcionan
1. Verifica que JavaScript esté habilitado
2. Abre la consola (F12) y busca errores
3. Intenta en otro navegador

## 📊 COMPARATIVA VISUAL

### Antes (Grid)
```
┌─────────────┐ ┌─────────────┐
│   Manga     │ │ Bolsillos   │
│ [Checkbox]  │ │ [Checkbox]  │
│ [Select]    │ │ [Input]     │
│ [Input]     │ │             │
└─────────────┘ └─────────────┘

┌─────────────┐ ┌─────────────┐
│   Broche    │ │ Reflectivo  │
│ [Checkbox]  │ │ [Checkbox]  │
│ [Select]    │ │ [Input]     │
│ [Input]     │ │             │
└─────────────┘ └─────────────┘
```

### Después (Tabla)
```
┌─────────────────────────────────────────────────────────────┐
│ ☑ │ Variación      │ Observación                           │
├─────────────────────────────────────────────────────────────┤
│ ☐ │ 👕 Manga       │ [Select] [Input]                      │
│ ☐ │ 📦 Bolsillos   │ [Input]                               │
│ ☐ │ 🔗 Broche      │ [Select] [Input]                      │
│ ☐ │ ⭐ Reflectivo  │ [Input]                               │
└─────────────────────────────────────────────────────────────┘
```

## 🎯 RESULTADO ESPERADO

✅ Tabla profesional con 3 columnas
✅ Header azul con texto blanco
✅ Filas alternadas (blanco y gris)
✅ Todos los campos funcionales
✅ Datos se guardan correctamente
✅ Diseño responsive
✅ Accesible en todos los navegadores

## 📞 SOPORTE

Si encuentras algún problema:

1. **Verifica el archivo**: `resources/views/cotizaciones/prenda/create.blade.php`
2. **Busca la línea**: 1122 (inicio de VARIACIONES ESPECÍFICAS)
3. **Verifica que sea tabla**: Debe tener `<table>` no `<div class="variaciones-grid">`
4. **Revisa la consola**: F12 → Console para ver errores

## ✅ CONFIRMACIÓN

Cuando todo funcione correctamente, deberías ver:

```
✅ Tabla con 3 columnas
✅ Header azul gradiente
✅ 4 filas (Manga, Bolsillos, Broche, Reflectivo)
✅ Todos los campos funcionales
✅ Datos se guardan sin errores
```

---

**Documento**: VERIFICAR-VARIACIONES-TABLA.md
**Fecha**: 9 de Diciembre de 2025
**Estado**: Listo para verificación

