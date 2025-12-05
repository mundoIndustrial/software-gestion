# ✅ VALIDACIÓN DE ESPECIFICACIONES EN COTIZACIONES

## 🎯 Objetivo
Implementar un sistema visual que recuerde al usuario completar las especificaciones antes de enviar una cotización.

## 🔴 Botón ROJO = Falta Especificaciones
## 🟢 Botón VERDE = Especificaciones Completadas

---

## 📋 Cambios Implementados

### 1. **Botón ENVIAR Dinámico**
- **Estado Inicial**: ROJO (#ef4444) - Falta especificaciones
- **Después de Guardar Especificaciones**: VERDE (#10b981)
- **Tooltip**: Muestra estado actual

### 2. **Modal de Advertencia**
Cuando intenta enviar sin especificaciones:
```
⚠️ Falta completar especificaciones

No has completado las especificaciones de la cotización.

Las especificaciones son importantes para que el cliente 
entienda todos los detalles de su pedido.

📋 Especificaciones requeridas:
• Régimen
• Se ha vendido
• Última venta
• Flete de envío

¿Deseas continuar sin completarlas?

[Enviar sin especificaciones] [Completar especificaciones]
```

### 3. **Toast Recordatorio**
Si elige "Completar especificaciones":
```
📋 Completa las especificaciones en PASO 3
```

---

## 🧪 Cómo Probar

### Prueba 1: Botón Rojo al Cargar
1. Abrir: `/asesores/cotizaciones/crear`
2. Ir a PASO 4 (REVISAR COTIZACIÓN)
3. ✅ Botón ENVIAR debe estar en ROJO
4. Pasar mouse sobre botón → Debe mostrar tooltip: "⚠️ Falta completar especificaciones"

### Prueba 2: Intentar Enviar sin Especificaciones
1. Hacer clic en botón ENVIAR (rojo)
2. ✅ Debe mostrar modal de advertencia
3. ✅ Botón debe cambiar a rojo más intenso con sombra
4. Opciones:
   - "Enviar sin especificaciones" → Envía igual
   - "Completar especificaciones" → Cierra modal y muestra toast

### Prueba 3: Completar Especificaciones
1. Ir a PASO 3 (LOGO)
2. Hacer clic en botón "ESPECIFICACIONES" (abajo)
3. Completar al menos una especificación:
   - Marcar checkbox en "RÉGIMEN"
   - O marcar checkbox en "SE HA VENDIDO"
   - O marcar checkbox en "ÚLTIMA VENTA"
   - O marcar checkbox en "FLETE DE ENVÍO"
4. Hacer clic en "GUARDAR"
5. ✅ Botón ENVIAR debe cambiar a VERDE
6. Pasar mouse sobre botón → Debe mostrar tooltip: "✅ Especificaciones completadas - Listo para enviar"

### Prueba 4: Enviar con Especificaciones
1. Con botón en VERDE, hacer clic en ENVIAR
2. ✅ Debe mostrar modal de confirmación normal (sin advertencia)
3. ✅ Debe permitir envío sin problemas

---

## 🔍 Debugging en Consola

Abre DevTools (F12) → Console para ver logs:

### Logs Esperados

**Al cargar página:**
```
✅ Botón ENVIAR en ROJO - Falta completar especificaciones
```

**Al guardar especificaciones:**
```
🔍 Buscando especificaciones en modal...
📋 Procesando disponibilidad (tbody_disponibilidad)
📋 Procesando forma_pago (tbody_pago)
📋 Procesando regimen (tbody_regimen)
📋 Procesando se_ha_vendido (tbody_vendido)
📋 Procesando ultima_venta (tbody_ultima_venta)
📋 Procesando flete (tbody_flete)
✅ Especificaciones guardadas: {...}
📊 Total categorías: 1
✅ Botón ENVIAR en VERDE - Especificaciones completadas
```

**Al intentar enviar sin especificaciones:**
```
🔴 Botón ENVIAR en ROJO - Falta completar especificaciones
```

---

## 📁 Archivos Modificados

1. **`public/js/asesores/cotizaciones/guardado.js`**
   - Líneas 303-370: Validación de especificaciones en `enviarCotizacion()`
   - Líneas 372-377: Resetear color de botón si hay especificaciones

2. **`public/js/asesores/cotizaciones/especificaciones.js`**
   - Línea 107: Llamar a `actualizarColorBotonEnviar()` después de guardar
   - Líneas 113-133: Nueva función `actualizarColorBotonEnviar()`
   - Líneas 161-165: Inicializar color al cargar página

---

## ✨ Características

✅ Botón rojo al cargar (recordatorio visual)
✅ Botón verde después de completar especificaciones
✅ Modal de advertencia si intenta enviar sin especificaciones
✅ Permite envío forzado si usuario lo desea
✅ Toast recordatorio para completar
✅ Tooltip informativo en botón
✅ Logs en consola para debugging
✅ Sin conflictos con código existente
✅ Funciona en todos los navegadores modernos

---

## 🎯 Resultado Final

**Antes:**
- Usuario podía enviar cotización sin especificaciones
- No había recordatorio visual
- Fácil olvidar completar especificaciones

**Ahora:**
- Botón ROJO es recordatorio visual claro
- Modal de advertencia si intenta enviar sin especificaciones
- Botón VERDE cuando especificaciones están completas
- Experiencia de usuario mejorada
- Menos cotizaciones incompletas

---

## 📝 Notas Técnicas

- El color del botón se actualiza automáticamente
- Se usa `window.especificacionesSeleccionadas` para almacenar datos
- La validación ocurre ANTES de enviar
- El usuario puede forzar envío sin especificaciones si lo desea
- Los logs ayudan a debuggear problemas

---

## 🚀 Próximos Pasos (Opcional)

1. Agregar animación de parpadeo al botón rojo
2. Agregar sonido de alerta cuando intenta enviar sin especificaciones
3. Guardar especificaciones en localStorage para persistencia
4. Mostrar contador de especificaciones completadas
5. Hacer especificaciones obligatorias (no permitir envío forzado)

---

**Estado**: ✅ COMPLETADO Y FUNCIONAL
**Fecha**: 5 de Diciembre de 2025
**Versión**: 1.0
