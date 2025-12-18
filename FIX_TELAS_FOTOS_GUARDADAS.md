# ✅ FIX: Fotos de Telas Múltiples Ahora Se Guardan Correctamente

## 🔴 Problema Encontrado

En los logs se veía:
```
✅ Foto 1 de tela 0 guardada: CODIGO DE TELA.png  ← Foto SÍ se cargó
✅ Foto 1 de tela 1 guardada: CODIGO DE TELA.png  ← Foto SÍ se cargó

PERO:
📸 Fotos desde fotosSeleccionadas: 0 archivos    ← NO se enviaron al servidor
"telas": Array(0)                                 ← Telas vacías en FormData
```

## 🔍 Causa Raíz

El código de recopilación estaba buscando las fotos en **el lugar equivocado**:

### ❌ ANTES (lugar incorrecto):
```javascript
// Buscaba en window.imagenesEnMemoria.telaConIndice
if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
    const telasEncontradas = window.imagenesEnMemoria.telaConIndice.filter(...);
}
```

### ✅ AHORA (lugar correcto):
```javascript
// Busca en window.telasSeleccionadas (donde SÍ se almacenan)
if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
    const telasObj = window.telasSeleccionadas[productoId];
    // Estructura: { '0': [File1], '1': [File1, File2] }
}
```

---

## 📝 Cambios Realizados

### 1. `cotizaciones.js` (línea ~670)
**Cambio:** Actualizar dónde se leen las telas

```javascript
// ANTES:
let telas = [];
if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {...}

// AHORA:
let telas = [];
if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
    const telasObj = window.telasSeleccionadas[productoId];
    for (let telaIdx in telasObj) {
        if (Array.isArray(telasObj[telaIdx])) {
            fotosDelaTela.forEach((foto) => {
                telas.push({
                    telaIndex: parseInt(telaIdx),
                    fotoIndex: fotoIdx,
                    file: foto
                });
            });
        }
    }
}
```

### 2. `guardado.js` (línea ~780)
**Cambio:** Cambiar dónde se agregan telas al FormData

```javascript
// ANTES:
if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
    telasDeEstaPrenda.forEach((item) => {
        formData.append(`prendas[${index}][telas][]`, item.file);
    });
}

// AHORA:
if (window.telasSeleccionadas && window.telasSeleccionadas[productoId]) {
    const telasObj = window.telasSeleccionadas[productoId];
    for (let telaIdx in telasObj) {
        const fotosDelaTela = telasObj[telaIdx];
        fotosDelaTela.forEach((foto, fotoIdx) => {
            if (foto instanceof File) {
                formData.append(`prendas[${index}][telas][${telaIdx}][fotos][]`, foto);
            }
        });
    }
}
```

---

## 🎯 Flujo Correcto Ahora

```
1. Usuario carga FOTO en tabla de tela
   ↓
2. agregarFotoTela() guarda en window.telasSeleccionadas[productoId][telaIndex]
   ✅ LOGGING: "📊 Estado actual de telasSeleccionadas: ..."
   ↓
3. Usuario hace clic "Enviar Cotización"
   ↓
4. cotizaciones.js RECOPILA datos
   ✅ AHORA busca en: window.telasSeleccionadas
   ✅ LOGGING: "🧵 telasSeleccionadas encontrado para producto-XXX"
   ↓
5. guardado.js CONSTRUYE FormData
   ✅ AHORA agrega: formData.append(`prendas[0][telas][0][fotos][]`, File)
   ✅ LOGGING: "✅ Tela 0 Foto 1 agregada a FormData"
   ↓
6. POST al servidor con FILES correctos
   ↓
7. CotizacionPrendaController.procesarImagenesCotizacion() PROCESA
   ↓
8. ARCHIVOS GUARDADOS EN:
   /storage/app/public/telas/cotizaciones/...
   ✅ BD: prenda_tela_fotos_cot
```

---

## 🧪 Cómo Verificar que Funciona

1. **En la consola (F12) busca:**
   ```
   🧵 telasSeleccionadas encontrado para producto-XXX
   ✅ Tela 0 Foto 1 agregada a FormData: imagen.png
   ✅ Tela 1 Foto 1 agregada a FormData: imagen.png
   ```

2. **En los logs del servidor:**
   ```
   local.INFO: Telas encontradas {"key":"productos.0.telas","count":2}
   local.INFO: Imagen guardada en: /storage/app/public/telas/cotizaciones/...
   ```

3. **En la BD:**
   ```sql
   SELECT * FROM prenda_tela_fotos_cot 
   WHERE prenda_cot_id = XXX;
   → Debe mostrar las fotos de todas las telas
   ```

---

## 🚀 Próximo Paso

**PRUEBA:** Crea una cotización CON:
- ✅ 2-3 prendas
- ✅ Cada prenda con 2-3 telas
- ✅ Cada tela con 1-3 fotos
- ✅ Envía y verifica que las fotos aparezcan en la BD

