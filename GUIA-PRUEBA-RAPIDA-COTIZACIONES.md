# 🚀 GUÍA DE PRUEBA RÁPIDA - COTIZACIONES

## ¿Qué es?

Un sistema de botones flotantes que **llena automáticamente** el formulario de cotizaciones sin que tengas que escribir nada manualmente. Perfecto para pruebas rápidas.

## 📍 Dónde aparecen los botones

Los botones aparecen en la **esquina inferior izquierda** del formulario de cotizaciones:

```
┌─────────────────────────────────────┐
│                                     │
│      FORMULARIO DE COTIZACIONES     │
│                                     │
│                                     │
│                                     │
│                                     │
│  ⚡ Llenar Formulario               │
│  📤 Enviar Cotización               │
│  🗑️ Limpiar                         │
│                                     │
└─────────────────────────────────────┘
```

## 🎯 Cómo usar

### Opción 1: Llenar y Enviar Automáticamente

1. **Abre** el formulario de cotizaciones: `/asesores/cotizaciones/crear`
2. **Espera** a que cargue completamente (verás los botones en la esquina inferior izquierda)
3. **Haz clic** en `⚡ Llenar Formulario`
   - Se llenarán automáticamente:
     - Cliente: "CLIENTE PRUEBA [timestamp]"
     - Tipo de cotización: "M"
     - Prenda: "CAMISA DRILL"
     - Descripción: Completa
     - Tallas: S, M, L, XL, XXL, XXXL
     - Color: "Naranja"
     - Tela: "DRILL BORNEO"
     - Manga: "Larga"
     - Reflectivo: "Gris 2" en pecho y espalda"
4. **Haz clic** en `📤 Enviar Cotización`
   - Se irá automáticamente al paso 4 (revisar)
   - Se enviará la cotización
   - Verás el resultado en la consola

### Opción 2: Llenar Manualmente y Luego Enviar

1. **Haz clic** en `⚡ Llenar Formulario`
2. **Modifica** los datos que quieras
3. **Haz clic** en `📤 Enviar Cotización`

### Opción 3: Limpiar y Empezar de Nuevo

1. **Haz clic** en `🗑️ Limpiar`
   - Se borrará todo el formulario
   - Podrás empezar de nuevo

## 📊 Datos que se llenan automáticamente

| Campo | Valor |
|-------|-------|
| Cliente | CLIENTE PRUEBA [timestamp] |
| Tipo de Cotización | M (Muestra) |
| Nombre Prenda | CAMISA DRILL |
| Descripción | Camisa drill con bordado en pecho y espalda, manga larga, con reflectivo gris |
| Tallas | S, M, L, XL, XXL, XXXL |
| Color | Naranja |
| Tela | DRILL BORNEO |
| Manga | Larga (checkbox activado) |
| Reflectivo | Gris 2" en pecho y espalda (checkbox activado) |

## 🔍 Cómo ver lo que está pasando

Abre la **Consola del Navegador** (F12 o Ctrl+Shift+I):

```
✅ Cliente llenado: CLIENTE PRUEBA 1732425600000
✅ Tipo de cotización: M
✅ Prenda agregada
✅ Nombre: CAMISA DRILL
✅ Descripción agregada
✅ Talla S seleccionada
✅ Talla M seleccionada
✅ Talla L seleccionada
✅ Talla XL seleccionada
✅ Talla XXL seleccionada
✅ Talla XXXL seleccionada
✅ Color: Naranja
✅ Tela: DRILL BORNEO
✅ Manga checkbox activado
✅ Manga: Larga
✅ Reflectivo checkbox activado
✅ Reflectivo: Gris 2" en pecho y espalda
✅ Producto completamente llenado
🎯 Ahora puedes hacer clic en SIGUIENTE para continuar
📤 Enviando cotización rápida...
✅ Cotización enviada
```

## ⚙️ Personalizar los datos

Si quieres cambiar los datos que se llenan automáticamente, edita el archivo:

```
public/js/asesores/cotizaciones/test-rapido.js
```

Busca la función `llenarProducto()` y modifica los valores:

```javascript
// Nombre de prenda
inputNombre.value = 'CAMISA DRILL';  // ← Cambiar aquí

// Descripción
textareaDesc.value = 'Camisa drill con bordado...';  // ← Cambiar aquí

// Color
colorInput.value = 'Naranja';  // ← Cambiar aquí

// Tela
telaInput.value = 'DRILL BORNEO';  // ← Cambiar aquí

// Manga
mangaInput.value = 'Larga';  // ← Cambiar aquí

// Reflectivo
reflectivoInput.value = 'Gris 2" en pecho y espalda';  // ← Cambiar aquí
```

## 🐛 Solucionar problemas

### Los botones no aparecen

1. **Recarga** la página (F5)
2. **Espera** 2-3 segundos a que cargue completamente
3. **Abre** la consola (F12) y busca: `✅ Botones de prueba creados`

### El formulario no se llena

1. **Abre** la consola (F12)
2. **Busca** mensajes de error (rojo)
3. **Verifica** que el formulario esté completamente cargado
4. **Intenta** nuevamente

### La cotización no se envía

1. **Verifica** que el formulario esté completamente llenado
2. **Abre** la consola (F12)
3. **Busca** errores en rojo
4. **Intenta** hacer clic en `📤 Enviar Cotización` nuevamente

## 📝 Ejemplo de flujo completo

1. Abre: `http://localhost:8000/asesores/cotizaciones/crear`
2. Espera a que cargue
3. Haz clic en `⚡ Llenar Formulario`
4. Espera a que se llene (verás logs en consola)
5. Haz clic en `📤 Enviar Cotización`
6. ¡Listo! La cotización se ha enviado

## ✨ Ventajas

✅ **Ahorra tiempo** - No tienes que llenar el formulario manualmente
✅ **Datos consistentes** - Siempre los mismos datos para pruebas
✅ **Fácil de personalizar** - Solo edita el archivo JS
✅ **Visible en consola** - Ves exactamente qué está pasando
✅ **Sin afectar producción** - Solo funciona en desarrollo

## 🔐 Seguridad

Este script **SOLO** funciona en el navegador durante el desarrollo. No se ejecuta en el servidor ni afecta la seguridad de la aplicación.

## 📞 Soporte

Si tienes problemas:

1. **Abre** la consola (F12)
2. **Copia** los mensajes de error
3. **Contacta** al equipo de desarrollo

---

**Archivo**: `public/js/asesores/cotizaciones/test-rapido.js`
**Última actualización**: 24 de Noviembre de 2025
