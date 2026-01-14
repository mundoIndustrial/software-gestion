# 🔍 Guía de Logs para Debugging de Tallas

Se han añadido logs detallados en toda la cadena de selección de tallas. Abre la **Consola del navegador** (F12 → Console tab) para ver el flujo completo.

## 📋 Flujo de Ejecución y Logs

### 1️⃣ **Abriendo el Modal de Tallas**
```
🎯 [MODAL TALLAS] Abriendo modal para género: dama
📊 [MODAL TALLAS] Estado actual de tallas: {...}
📌 [MODAL TALLAS] Otro género: caballero | Tipo del otro género: null
```
- Verifica que el género correcto está siendo seleccionado
- Muestra el estado ANTES de abrir el modal

### 2️⃣ **Seleccionando Tipo de Talla (Letra o Número)**
```
✏️ [SELECCIONAR TIPO] Tipo: letra | Género Actual: dama
📝 [SELECCIONAR TIPO] Mostrando tallas de LETRA
```
o
```
✏️ [SELECCIONAR TIPO] Tipo: numero | Género Actual: caballero
🔢 [SELECCIONAR TIPO] Mostrando tallas NUMÉRICAS
```

### 3️⃣ **Mostrando Tallas Disponibles**
```
📋 [MOSTRAR TALLAS] Tipo: letra | Género: dama
📝 [MOSTRAR TALLAS] Tallas letras disponibles: ['XS','S','M','L','XL','XXL']
```
o
```
📋 [MOSTRAR TALLAS] Tipo: numero | Género: dama
👗 [MOSTRAR TALLAS] Tallas numéricas DAMA disponibles: ['32','34','36',...]
```

### 4️⃣ **Seleccionando/Deseleccionando Tallas**
```
🎯 [TOGGLE TALLA] Talla: M | Seleccionada: true
🎯 [TOGGLE TALLA] Talla: M | Seleccionada: false
```
- Confirma cuando haces clic en cada talla

### 5️⃣ **Confirmando Selección de Tallas**
```
✅ [CONFIRMAR TALLAS] Confirmando selección para género: dama
📊 [CONFIRMAR TALLAS] Tallas seleccionadas: ['M','L','XL']
💾 [CONFIRMAR TALLAS] Guardando tipo: letra
💾 [CONFIRMAR TALLAS] Estado guardado: {"dama":{"tallas":["M","L","XL"],"tipo":"letra"},"caballero":{"tallas":[],"tipo":null}}
```

### 6️⃣ **Actualizando Tarjetas de Géneros**
```
🔄 [ACTUALIZAR TARJETAS] Actualizando estado de géneros
📊 [ACTUALIZAR TARJETAS] Estado actual: {...}
👗 [ACTUALIZAR TARJETAS] DAMA seleccionada: ['M','L','XL']
👔 [ACTUALIZAR TARJETAS] CABALLERO sin selecciones
🎨 [ACTUALIZAR TARJETAS] Creando tarjeta para: dama
```

### 7️⃣ **Cerrando Modal de Tallas**
```
❌ [CERRAR MODAL] Cerrando modal de tallas
📊 [CERRAR MODAL] Estado final de tallas: {...}
```

### 8️⃣ **Agregando Prenda Nueva**
```
⭐ [AGREGAR PRENDA] Iniciando agregar prenda
📊 [AGREGAR PRENDA] Tallas seleccionadas ANTES de agregar: {...}
📋 [AGREGAR PRENDA] Tallas para agregar: [{"genero":"dama","talla":"M","cantidad":5}...]
➕ [AGREGAR PRENDA] Agregando prenda nueva: {...}
✅ [AGREGAR PRENDA] Prenda "POLERA" agregada como 1 ítem (sin procesos)
🧹 [AGREGAR PRENDA] Limpiando tallas después de confirmar prenda
📊 [AGREGAR PRENDA] Tallas DESPUÉS de limpiar: {"dama":{"tallas":[],"tipo":null},"caballero":{"tallas":[],"tipo":null}}
🔐 [AGREGAR PRENDA] Cerrando modal
```

### 9️⃣ **Eliminando Género**
```
🗑️ [ELIMINAR GÉNERO] Eliminando género: dama
📊 [ELIMINAR GÉNERO] Estado antes: {"dama":{"tallas":["M","L"],"tipo":"letra"},"caballero":...}
📊 [ELIMINAR GÉNERO] Estado después: {"dama":{"tallas":[],"tipo":null},"caballero":...}
🔘 [ELIMINAR GÉNERO] Reseteando botón de: dama
🔄 [ELIMINAR GÉNERO] Actualizando tarjetas
```

## 🎯 Casos de Prueba

### Prueba 1: Seleccionar DAMA → CABALLERO
1. Abre el modal
2. Ve los logs de `DAMA` siendo abierto
3. Selecciona 3 tallas de DAMA
4. Confirma
5. Abre modal para CABALLERO
6. **Verifica**: El estado debe mostrar DAMA con tallas y CABALLERO vacío
7. Selecciona tallas de CABALLERO
8. **Verifica**: Ambos géneros deben tener tallas sin conflictos

### Prueba 2: Eliminar y Re-agregar
1. Agrega DAMA con tallas M, L
2. Abre modal para CABALLERO
3. Elimina DAMA (botón X)
4. **Verifica en logs**: "Estado después" debe mostrar `dama: {tallas: [], tipo: null}`
5. Intenta seleccionar DAMA de nuevo
6. **Verifica**: El botón debe permitir selección

### Prueba 3: Sincronización de Tipo
1. Selecciona DAMA con tipo LETRA y tallas M, L
2. Abre modal para CABALLERO
3. **Verifica en logs**: "Tipo del otro género: letra"
4. **Verifica en UI**: El tipo LETRA debe estar pre-seleccionado y bloqueado

## 🐛 Qué Buscar si Hay Problemas

- **Tallas desaparecen**: Busca si aparece un log con `Estado después` o `Tallas DESPUÉS de limpiar` que no esperabas
- **Tipo no sincroniza**: Busca en "Tipo del otro género" si dice `null` cuando debería tener un valor
- **Botón no se resetea**: Busca en "Reseteando botón de" para confirmar que se ejecuta
- **Cantidad incorrecta**: Busca en "Tallas para agregar" para ver qué se está enviando

## 💡 Cómo Usar

1. Abre la **Consola del Navegador** (F12)
2. Realiza las acciones en el formulario
3. Los logs aparecerán con colores e iconos para fácil identificación
4. Copia/pega los logs en un documento si necesitas comparar comportamientos

¡Los logs te mostrarán exactamente dónde se pierden o se transforman las tallas! 🎯
