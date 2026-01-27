# ⚡ VERIFICACIÓN RÁPIDA - Fixes Tabla Telas

## 🚀 En 5 Minutos

### Paso 1: Recargar caché
```
Ctrl+Shift+R  (o Cmd+Shift+R en Mac)
```

### Paso 2: Abrir Modal de Edición
1. Ir a: Pedidos → Editar Producción
2. Buscar prenda con telas (ej: Prenda CAMISA DRILL)
3. Clic en botón EDITAR

### Paso 3: Verificar Tabla
Debe verse:

```
┌─────────────┬──────────┬────────────┬─────────────┬──────────┐
│ TELA        │ COLOR    │ REFERENCIA │ FOTO        │ ACCIONES │
├─────────────┼──────────┼────────────┼─────────────┼──────────┤
│ drill       │ dsfdfs   │ ABC-123    │ [THUMBNAIL] │ [X]      │
└─────────────┴──────────┴────────────┴─────────────┴──────────┘
```

✅ Si ves esto → **Todo funciona**

### Paso 4: Verificar Consola (F12)
Abrir DevTools → Console, buscar:

```
[actualizarTablaTelas] 📋 Modo: EDICIÓN
```

✅ Si ves "EDICIÓN" → **Detección correcta**

---

## 🔍 Casos de Uso

### ✅ Debe Funcionar (Edición)
- [ ] Tabla muestra telas de BD
- [ ] Nombres correctos
- [ ] Colores correctos  
- [ ] Referencias correctas (de `prenda_pedido_colores_telas`)
- [ ] Fotos se ven como thumbnail

### ✅ Debe Funcionar (Creación)
- [ ] Crear prenda nueva
- [ ] Agregar telas nuevas
- [ ] Tabla muestra nuevas telas
- [ ] Sin errores

### ✅ Debe Funcionar (Gestión)
- [ ] Clic en botón eliminar (X rojo)
- [ ] Confirmar eliminación
- [ ] Tela desaparece de tabla
- [ ] Sin errores

---

## ❌ Problemas Comunes

### Problema: Tabla vacía
```
✓ Recargar: Ctrl+Shift+R
✓ Abrir console: F12
✓ Ejecutar: console.log(window.telasAgregadas)
✓ Debe mostrar array con 1+ elementos
```

### Problema: "Sin nombre" o "Sin color"
```
✓ Estructura de datos incorrecta
✓ Verificar en console:
  window.telasAgregadas[0]
✓ Debe tener propiedades correctas
```

### Problema: Foto no aparece
```
✓ URL de imagen incorrecta
✓ Verificar en console:
  window.telasAgregadas[0].imagenes[0]
✓ Debe tener previewUrl, url o ruta_webp
```

---

## 📊 Logs Esperados

En Console (F12), debe verse:

```javascript
[actualizarTablaTelas] 🔄 Iniciando actualización de tabla...
[actualizarTablaTelas] 📋 Modo: EDICIÓN, Telas a mostrar: 1
[actualizarTablaTelas] 🧵 Procesando tela 0: {
  nombre: "drill",
  color: "dsfdfs",
  referencia: "ABC-123",
  imagenes_count: 1
}
[actualizarTablaTelas] 📸 Primera imagen de tela 0: {previewUrl: "/storage/..."}
[actualizarTablaTelas] 📋 Caso previewUrl: /storage/pedidos/2763/...
[actualizarTablaTelas] ✅ blobUrl para imagen 0: /storage/pedidos/2763/...
```

✅ Si ves todos estos logs → **Correcto**

---

## 🧪 Test Rápido

### En Console (F12)

```javascript
// 1. Ver variables globales
console.log('telasAgregadas:', window.telasAgregadas);

// 2. Ver estructura de tela
console.log('Primera tela:', window.telasAgregadas?.[0]);

// 3. Ver estructura de imagen
console.log('Primera imagen:', window.telasAgregadas?.[0]?.imagenes?.[0]);

// 4. Forzar actualización
window.actualizarTablaTelas();
```

---

## ✅ Checklist Final

- [ ] Recargar página (Ctrl+Shift+R)
- [ ] Abrir modal edición
- [ ] Tabla visible con datos
- [ ] Console sin errores rojo
- [ ] Logs muestran "EDICIÓN"
- [ ] Referencia viene de pedido
- [ ] Foto se ve en tabla
- [ ] Botón eliminar funciona
- [ ] Crear prenda nueva sigue funcionando
- [ ] Sin regresiones

---

## 📞 Si Algo No Funciona

1. **Limpiar caché:**
   - Ctrl+Shift+R en navegador
   - Limpiar cookies si es necesario

2. **Revisar console:**
   - F12 → Console
   - Buscar errores rojos
   - Copiar mensaje de error

3. **Verificar datos en BD:**
   ```sql
   SELECT * FROM prenda_pedido_colores_telas WHERE id = 101;
   ```
   Debe mostrar referencia con valor

4. **Verificar en code:**
   - Archivo modificado: `gestion-telas.js`
   - Función: `window.actualizarTablaTelas()`
   - Debe detectar modo "EDICIÓN"

---

**Última actualización:** 27 ENE 2026  
**Status:** ✅ Listo para Producción
