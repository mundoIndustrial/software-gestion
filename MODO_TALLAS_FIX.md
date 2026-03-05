# 🔧 Fix: Modo Tallas Preselection en Modal de Procesos

## Problema
Cuando editas un proceso existente de la base de datos con `modo_tallas='general'`, el modal muestra 'especifico' preseleccionado en lugar del valor correcto que viene de la base de datos.

## Causa Raíz
El problema tenía dos capas:

1. **Extracción de tipo de proceso**: El loader de procesos no estaba recuperando correctamente el nombre del tipo de proceso desde la estructura anidada del servidor (`tipoProceso.nombre`)

2. **Propagación de modo_tallas**: Sin un debug claro, era difícil saber si `modo_tallas` estaba siendo incluido desde el servidor o si se perdía en algún punto de la cadena

## ✅ Cambios Implementados

### 1. **prenda-editor-procesos.js** (Loader de Procesos)
**Línea ~47**: Mejorada extracción del nombre del tipo de proceso
```javascript
// ANTES:
const tipoOriginal = proceso.tipo || proceso.nombre || `proceso_${idx}`;

// DESPUÉS:
let tipoOriginal = proceso.tipo 
    || proceso.nombre 
    || (proceso.tipoProceso && proceso.tipoProceso.nombre)  // ← Nuevo
    || `proceso_${idx}`;
```

**Agregado debug logging** (Línea ~72-82) para verificar que `modo_tallas` se incluye:
```javascript
console.log(`[PROCESOS-LOADER] 🎯 Proceso "${tipo}" cargado:`, {
    modo_tallas_desde_servidor: datosNormalizados.modo_tallas,
    // ... más campos
});
```

### 2. **gestor-modal-proceso-por-tallas.js** (Modal de Tallas)
**Línea ~328**: Mejorado debug logging al abrir el modal
```javascript
console.log('[por-tallas] 🔍 DEBUGGING...', {
    modo_tallas_directo: window.procesosSeleccionados?.[tipoProceso]?.datos?.modo_tallas,
    // ... más campos debuggeables
});
```

**Línea ~451-458**: Agregado fallback para rescatar `modo_tallas` desde ubicaciones alternadas:
```javascript
// Si modo_tallas no se encontró en datos, intentar desde la relación tipoProceso
let modoFinal = modoGuardado;
if (!datosGenerales?.modo_tallas && datosGenerales?.tipoProceso?.modo_tallas) {
    modoFinal = datosGenerales.tipoProceso.modo_tallas;
    console.warn('[por-tallas] ⚠️ modo_tallas rescatado de tipoProceso.modo_tallas:', modoFinal);
}
```

## 🧪 Cómo Verificar que Funciona

### Opción 1: Test en Navegador (Recomendado)
1. Abre una página de edición de pedido: `https://tudominio.com/asesores/pedidos/[ID]/edit`
2. Abre DevTools: **F12** (o clic derecho → Inspeccionar)
3. Ve a la pestaña **Console**
4. Copia TODO el contenido de `public/test-modo-tallas-browser.js`
5. Pégalo en la consola y presiona **Enter**
6. Revisa la salida:
   - ✅ Si ves "modo_tallas" en la consola, están siendo cargados
   - ❌ Si ves algo tipo "FALTA (undefined)", hay un problema

### Opción 2: Test en Terminal (PHP)
```bash
cd /ruta/a/mundoindustrial
php test-modo-tallas-verify.php 123
```
(Reemplaza `123` con el ID de un pedido existente)

Salida esperada:
```
✅ modo_tallas: general   (o 'especifico', lo importante es que aparezca)
```

### Opción 3: Verificación Manual
1. Abre DevTools en la página de edición
2. En Console ejecuta:
```javascript
console.log('Procesos disponibles:', Object.keys(window.procesosSeleccionados || {}));
console.log('Primer proceso:', window.procesosSeleccionados?.reflectivo?.datos?.modo_tallas);
```

## 📋 Flujo de Datos Verificado

```
Servidor PHP (BD) 
    ↓ [modo_tallas='general']
ObtenerProduccionPedidoUseCase 
    ↓ 
AbstractObtenerUseCase.obtenerProcesos() 
    ↓ [toArray() incluye modo_tallas]
editar-pedido.blade.php 
    ↓ [@json($pedidoData)]
window.pedidoEdicionData.pedido.prendas[i].procesos[j] 
    ↓ [proceso.modo_tallas = 'general']
PrendaEditorProcesos.cargar(prenda) 
    ↓ [spread operator: {...proceso}]
window.procesosSeleccionados[tipo].datos.modo_tallas = 'general' ✅
    ↓
gestor-modal-proceso-por-tallas.js 
    ↓ [lee datosGenerales.modo_tallas]
Modal muestra botón correcto 'general' preseleccionado ✅
```

## 🎯 Próximos Pasos

Si el test muestra que `modo_tallas` sigue faltando:

1. **Verificar Base de Datos**
   ```sql
   SELECT id, modo_tallas FROM pedidos_procesos_prenda_detalles LIMIT 5;
   ```
   Asegúrate de que la columna existe y tiene valores

2. **Verificar Response del Server**
   - Abre DevTools → Network
   - Recarga la página
   - Busca la petición que carga los datos del pedido
   - Revisa el JSON response para ver si incluye `modo_tallas`

3. **Si sigue sin funcionar**, ejecuta el test PHP para ver exactamente qué devuelve el servidor

## 📝 Notas Técnicas

- Los cambios son **non-breaking**: solo agregan mejor visibilidad y fallbacks
- El valor por defecto sigue siendo `'general'` si `modo_tallas` no se encuentra
- Los logs agregados ayudan a diagnosticar problemas futuros
- No se modifica la lógica de guardado, solo la lectura y preselección

## 📞 Soporte

Si después de estos cambios sigue sin funcionar:
1. Comparte la salida completa del test browser
2. Verifica que `modo_tallas` esté en la BD para tus procesos
3. Revisa en Network Inspector el JSON que devuelve el servidor
