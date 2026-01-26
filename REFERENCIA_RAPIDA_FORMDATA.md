# 🎯 REFERENCIA RÁPIDA - Solución FormData

## Problema Original
```
❌ Frontend: archivos_totales: 3
❌ Backend: archivos_count: 1, archivo.name: "unknown", size: 0
```

## Solución Aplicada
```
✅ Frontend: archivos_totales: 3, archivos_en_map: 3
✅ Backend: archivos_count: 3, archivos con name y size correcto
```

---

## 📌 3 Cambios Principales

### 1. Generar `formdata_key` para cada archivo
**Archivo**: `item-api-service.js` (línea 514+)
```javascript
const formdataKey = `prendas[${prendaIdx}][imagenes][${imgIdx}]`;
prendaData.imagenes.push({ file: img, formdata_key: formdataKey });
estructura.archivosMap[formdataKey] = img;
```
**Por qué**: Cada archivo necesita una referencia única que se pueda recuperar en `buildFormData()`

---

### 2. Nueva función `normalizarImagenes()`
**Archivo**: `payload-normalizer-v3-definitiva.js` (línea 60+)
```javascript
function normalizarImagenes(imagenesRaw) {
    return imagenesRaw.map(function(img) {
        if (img.file instanceof File) {
            return { formdata_key: img.formdata_key, nombre_archivo: img.file.name };
        }
        return { uid: img.uid, nombre_archivo: img.nombre_archivo, formdata_key: img.formdata_key };
    });
}
```
**Por qué**: Preserva el `formdata_key` en el JSON normalizado para que `buildFormData()` lo encuentre

---

### 3. Actualizar `buildFormData()` para usar `formdata_key`
**Archivo**: `payload-normalizer-v3-definitiva.js` (línea 165+)
```javascript
const file = imgObj.file || imgObj;
const formdataKey = imgObj.formdata_key || ('prendas[...]');
if (file instanceof File) {
    formData.append(formdataKey, file);
}
```
**Por qué**: Accede correctamente al File y usa la clave correcta al agregarlo al FormData

---

## ✅ Validación en 30 segundos

### 1. Abre navegador en `/crear-pedido`
```javascript
// Copia y pega en console:
window.monitorFormData = function() {
    FormData.prototype.append = (function(original) {
        return function(key, value) {
            if (value instanceof File) console.log('📎', key, value.name, value.size);
            return original.call(this, key, value);
        };
    })(FormData.prototype.append);
};
window.monitorFormData();
```

### 2. Selecciona 3 archivos
```
Prenda: cualquier_prenda.jpg
Tela: cualquier_tela.jpg
Proceso: cualquier_proceso.jpg
```

### 3. Haz clic en "Crear Pedido"

### 4. En Console deberías ver:
```
📎 pedido (JSON string)
📎 prendas[0][imagenes][0] (archivo.jpg)
📎 prendas[0][telas][0][imagenes][0] (archivo.jpg)
📎 prendas[0][procesos][reflectivo][0] (archivo.jpg)
```

✅ Si ves eso → **Funciona correctamente**

---

## 🔍 Quick Troubleshooting

| Síntoma | Causa | Solución |
|---------|-------|----------|
| Console vacía | PayloadNormalizer no cargó | Recargar página |
| `archivos_totales: 0` | `extraerFilesDelPedido()` falla | Revisar que archivos son `instanceof File` |
| `size: 0` en backend | Archivo no se agregó correctamente | Ver logs de buildFormData |
| `key: prendas` (simple) | FormData no se construyó | Verificar que buildFormData se ejecutó |

---

## 📊 Comparativa de Logs

### ANTES ❌
```
[extraerFilesDelPedido] archivos_totales: 3
[PayloadNormalizer.buildFormData] archivos_totales: 0  ← PROBLEMA
[CrearPedidoEditableController] archivos_count: 1
Archivo recibido: name: "unknown", size: 0
```

### DESPUÉS ✅
```
[extraerFilesDelPedido] archivos_totales: 3, archivos_en_map: 3
[PayloadNormalizer.buildFormData] archivos_totales: 3
[CrearPedidoEditableController] archivos_count: 3
Archivos recibidos: name: "prenda.jpg", size: 245678
```

---

## 📁 Archivos Modificados

```
public/js/modulos/crear-pedido/procesos/services/
├── item-api-service.js (línea 514-750)
│   └─ extraerFilesDelPedido() ← Agrega formdata_key
│
└── payload-normalizer-v3-definitiva.js (línea 60-230)
   ├─ normalizarImagenes() ← Nueva función
   ├─ buildFormData() ← Usa formdata_key
   ├─ normalizarItem()
   ├─ normalizarTelas()
   ├─ normalizarEpp()
   └─ normalizarProcesos()
```

---

## 🚀 Testing Automático

Crear test file: `public/js/tests/test-formdata.js`

```javascript
describe('FormData con Archivos', function() {
    it('extraerFilesDelPedido debe generar formdata_key', function() {
        const pedido = {
            prendas: [{
                imagenes: [new File(['test'], 'test.jpg')]
            }]
        };
        
        const resultado = extraerFilesDelPedido(pedido);
        expect(resultado.archivosMap).toBeDefined();
        expect(Object.keys(resultado.archivosMap).length).toBeGreaterThan(0);
    });
    
    it('buildFormData debe agregar archivos con formdata_key', function() {
        const filesExtraidos = { 
            prendas: [{
                imagenes: [{
                    file: new File(['test'], 'test.jpg'),
                    formdata_key: 'prendas[0][imagenes][0]'
                }]
            }],
            archivosMap: {}
        };
        
        const pedido = { prendas: [] };
        const formData = buildFormData(pedido, filesExtraidos);
        
        // Contar archivos en FormData
        let fileCount = 0;
        for (let [key, value] of formData.entries()) {
            if (value instanceof File) fileCount++;
        }
        expect(fileCount).toBe(1);
    });
});
```

---

## 📞 Support

Si algo no funciona:

1. **Revisar logs en Console** (Frontend)
   - `[extraerFilesDelPedido]` 
   - `[PayloadNormalizer.buildFormData]`

2. **Revisar logs en Laravel** (`storage/logs/laravel.log`)
   - `[CrearPedidoEditableController]`
   - `[ResolutorImagenesService]`

3. **Verificar que archivos existen**
   ```bash
   ls -la storage/app/public/pedidos/[ID]/prendas/
   ls -la storage/app/public/pedidos/[ID]/telas/
   ls -la storage/app/public/pedidos/[ID]/procesos/
   ```

4. **Revisar BD**
   ```sql
   SELECT * FROM prendas_fotos_pedidos WHERE pedido_id = [ID];
   SELECT * FROM prendas_fotos_telas_pedidos WHERE pedido_id = [ID];
   ```

---

**Última actualización**: 26 Enero 2026  
**Estado**: ✅ LISTO
