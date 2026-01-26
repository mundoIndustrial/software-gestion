# ✅ CORRECCIONES FINALES - FormData + Archivos (Solución Concreta)

## 🔍 Problema Identificado

El archivo `api-pedidos-editable.js` **NO está usando** `extraerFilesDelPedido()` ni `buildFormData()`.

En su lugar, usa `convertirPedidoAFormData()` que **NO preserva los UIDs ni formdata_key**.

## ✅ SOLUCIÓN: Corregir `convertirPedidoAFormData()`

Reemplazar la función existente para que:
1. Preserve UIDs en JSON
2. Agregue formdata_key en el JSON
3. Use la estructura correcta de archivos

---

## 📝 CÓDIGO CORREGIDO

### Archivo: `public/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js`

**Reemplazar la función `crearPedido()` completa:**

```javascript
/**
 * Crear el pedido - VERSION CORREGIDA
 * CRÍTICO: Usa formdata_key para que backend localice archivos
 */
async crearPedido(pedidoData) {
    try {
        const tieneArchivos = this.tieneArchivosEnPedido(pedidoData);

        // PASO 1: Extraer archivos y generar formdata_key
        const filesExtraidos = this.extraerFilesConFormDataKey(pedidoData);
        
        // PASO 2: Normalizar pedido (limpia Files, agrega formdata_key)
        const pedidoNormalizado = this.normalizarPedidoConFormDataKey(pedidoData, filesExtraidos);
        
        // PASO 3: Construir FormData
        const formData = new FormData();
        formData.append('pedido', JSON.stringify(pedidoNormalizado));
        
        // PASO 4: Agregar archivos con formdata_key correcto
        this.agregarArchivosAFormData(formData, filesExtraidos);
        
        // PASO 5: Enviar
        const response = await fetch(`${this.baseUrl}/crear`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                // NO incluir Content-Type, FormData lo hace
            },
            body: formData,
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'Error al crear pedido');
        }

        return data;
    } catch (error) {
        console.error('[crearPedido] Error:', error);
        throw error;
    }
}

/**
 * NUEVO: Extraer archivos y generar formdata_key para cada uno
 */
extraerFilesConFormDataKey(pedidoData) {
    const estructura = { prendas: [], epps: [], archivosMap: {} };
    
    if (!pedidoData.items) return estructura;
    
    pedidoData.items.forEach((item, itemIdx) => {
        if (item.tipo === 'epp') {
            // EPP
            const eppData = { idx: itemIdx, imagenes: [] };
            
            if (item.imagenes && Array.isArray(item.imagenes)) {
                item.imagenes.forEach((img, imgIdx) => {
                    if (img instanceof File) {
                        const formdataKey = `epps[${itemIdx}][imagenes][${imgIdx}]`;
                        eppData.imagenes.push({
                            file: img,
                            formdata_key: formdataKey
                        });
                        estructura.archivosMap[formdataKey] = img;
                    }
                });
            }
            estructura.epps.push(eppData);
        } else {
            // PRENDA
            const prendaData = { idx: itemIdx, imagenes: [], telas: [], procesos: {} };
            
            // Imágenes de prenda
            if (item.imagenes && Array.isArray(item.imagenes)) {
                item.imagenes.forEach((img, imgIdx) => {
                    if (img instanceof File) {
                        const formdataKey = `prendas[${itemIdx}][imagenes][${imgIdx}]`;
                        prendaData.imagenes.push({
                            file: img,
                            formdata_key: formdataKey
                        });
                        estructura.archivosMap[formdataKey] = img;
                    }
                });
            }
            
            // Imágenes de telas
            if (item.telas && Array.isArray(item.telas)) {
                item.telas.forEach((tela, telaIdx) => {
                    if (!prendaData.telas[telaIdx]) prendaData.telas[telaIdx] = [];
                    
                    if (tela.imagenes && Array.isArray(tela.imagenes)) {
                        tela.imagenes.forEach((img, imgIdx) => {
                            if (img instanceof File) {
                                const formdataKey = `prendas[${itemIdx}][telas][${telaIdx}][imagenes][${imgIdx}]`;
                                prendaData.telas[telaIdx].push({
                                    file: img,
                                    formdata_key: formdataKey
                                });
                                estructura.archivosMap[formdataKey] = img;
                            }
                        });
                    }
                });
            }
            
            // Imágenes de procesos
            if (item.procesos && typeof item.procesos === 'object') {
                Object.entries(item.procesos).forEach(([procKey, procData]) => {
                    prendaData.procesos[procKey] = [];
                    
                    let imagenes = [];
                    if (procData?.imagenes) imagenes = procData.imagenes;
                    else if (procData?.datos?.imagenes) imagenes = procData.datos.imagenes;
                    
                    imagenes.forEach((img, imgIdx) => {
                        if (img instanceof File) {
                            const formdataKey = `prendas[${itemIdx}][procesos][${procKey}][${imgIdx}]`;
                            prendaData.procesos[procKey].push({
                                file: img,
                                formdata_key: formdataKey
                            });
                            estructura.archivosMap[formdataKey] = img;
                        }
                    });
                });
            }
            
            estructura.prendas.push(prendaData);
        }
    });
    
    console.log('[extraerFilesConFormDataKey] Archivos extraídos:', {
        total: Object.keys(estructura.archivosMap).length,
        keys: Object.keys(estructura.archivosMap)
    });
    
    return estructura;
}

/**
 * NUEVO: Normalizar pedido preservando formdata_key pero limpiando Files
 */
normalizarPedidoConFormDataKey(pedidoData, filesExtraidos) {
    const pedidoNorm = {
        cliente: pedidoData.cliente || '',
        asesora: pedidoData.asesora || '',
        forma_de_pago: pedidoData.forma_de_pago || '',
        prendas: [],
        epps: []
    };
    
    if (!pedidoData.items) return pedidoNorm;
    
    pedidoData.items.forEach((item, itemIdx) => {
        if (item.tipo === 'epp') {
            // EPP normalizado
            const eppDatos = filesExtraidos.epps.find(e => e.idx === itemIdx);
            pedidoNorm.epps.push({
                uid: item.uid || null,
                epp_id: item.epp_id || null,
                nombre: item.nombre || '',
                cantidad: item.cantidad || 1,
                categoria: item.categoria || '',
                observaciones: item.observaciones || '',
                imagenes: eppDatos?.imagenes?.map(img => ({
                    formdata_key: img.formdata_key,
                    nombre_archivo: img.file.name
                })) || []
            });
        } else {
            // PRENDA normalizada
            const prendaDatos = filesExtraidos.prendas.find(p => p.idx === itemIdx);
            
            pedidoNorm.prendas.push({
                uid: item.uid || null,
                nombre_prenda: item.prenda || item.nombre_prenda || '',
                descripcion: item.descripcion || '',
                origen: item.origen || 'bodega',
                cantidad_talla: item.cantidad_talla || {},
                telas: (item.telas || []).map((tela, telaIdx) => ({
                    uid: tela.uid || null,
                    tela_id: tela.tela_id || 0,
                    color_id: tela.color_id || 0,
                    nombre: tela.nombre || '',
                    color: tela.color || '',
                    imagenes: prendaDatos?.telas[telaIdx]?.map(img => ({
                        formdata_key: img.formdata_key,
                        nombre_archivo: img.file.name
                    })) || []
                })),
                procesos: Object.entries(item.procesos || {}).reduce((acc, [procKey, procData]) => {
                    acc[procKey] = {
                        uid: procData?.uid || null,
                        nombre: procKey,
                        ubicaciones: procData?.ubicaciones || [],
                        observaciones: procData?.observaciones || '',
                        tallas: procData?.tallas || {},
                        imagenes: prendaDatos?.procesos[procKey]?.map(img => ({
                            formdata_key: img.formdata_key,
                            nombre_archivo: img.file.name
                        })) || []
                    };
                    return acc;
                }, {}),
                imagenes: prendaDatos?.imagenes?.map(img => ({
                    formdata_key: img.formdata_key,
                    nombre_archivo: img.file.name
                })) || []
            });
        }
    });
    
    return pedidoNorm;
}

/**
 * NUEVO: Agregar archivos a FormData usando formdata_key
 */
agregarArchivosAFormData(formData, filesExtraidos) {
    let archivosCont = 0;
    
    // Prendas
    filesExtraidos.prendas.forEach(prenda => {
        // Imágenes de prenda
        prenda.imagenes.forEach(img => {
            formData.append(img.formdata_key, img.file);
            archivosCont++;
            console.log(`[agregarArchivosAFormData] Agregado: ${img.formdata_key}`);
        });
        
        // Imágenes de telas
        prenda.telas.forEach(telas => {
            if (Array.isArray(telas)) {
                telas.forEach(img => {
                    formData.append(img.formdata_key, img.file);
                    archivosCont++;
                    console.log(`[agregarArchivosAFormData] Agregado: ${img.formdata_key}`);
                });
            }
        });
        
        // Imágenes de procesos
        Object.values(prenda.procesos).forEach(procesos => {
            if (Array.isArray(procesos)) {
                procesos.forEach(img => {
                    formData.append(img.formdata_key, img.file);
                    archivosCont++;
                    console.log(`[agregarArchivosAFormData] Agregado: ${img.formdata_key}`);
                });
            }
        });
    });
    
    // EPPs
    filesExtraidos.epps.forEach(epp => {
        epp.imagenes.forEach(img => {
            formData.append(img.formdata_key, img.file);
            archivosCont++;
            console.log(`[agregarArchivosAFormData] Agregado: ${img.formdata_key}`);
        });
    });
    
    console.log(`[agregarArchivosAFormData] Total archivos agregados: ${archivosCont}`);
}
```

---

## 📊 LOG ESPERADO EN BACKEND (Correcto)

```php
[2026-01-26 12:00:00] local.INFO: [CrearPedidoEditableController] 🚀 Iniciando creación {
    "has_pedido_json": true,
    "archivos_count": 3
}

[2026-01-26 12:00:00] local.DEBUG: [CrearPedidoEditableController] 📤 Archivos en FormData {
    "total_archivos": 3,
    "archivos": [
        {
            "key": "prendas[0][imagenes][0]",
            "name": "prenda_001.jpg",
            "size": 245678
        },
        {
            "key": "prendas[0][telas][0][imagenes][0]",
            "name": "tela_001.jpg",
            "size": 182456
        },
        {
            "key": "prendas[0][procesos][reflectivo][0]",
            "name": "ref_001.jpg",
            "size": 98765
        }
    ],
    "keys_recibidas": ["pedido", "prendas[0][imagenes][0]", "prendas[0][telas][0][imagenes][0]", "prendas[0][procesos][reflectivo][0]"]
}

[2026-01-26 12:00:00] local.INFO: [ResolutorImagenesService] ✅ Extracción completada {
    "pedido_id": 2730,
    "imagenes_procesadas": 3,
    "imagenes_esperadas": 3,
    "diferencia": 0
}

[2026-01-26 12:00:00] local.INFO: [CrearPedidoEditableController] TRANSACCIÓN EXITOSA {
    "pedido_id": 2730,
    "numero_pedido": 100011,
    "cantidad_total": 60
}
```

---

## 🎯 RESUMEN DE CAMBIOS

| Función | Cambio | Por qué |
|---------|--------|--------|
| `crearPedido()` | Agregó PASO 1-5 explícitos | Flujo claro y debuggeable |
| `extraerFilesConFormDataKey()` | NUEVA | Genera formdata_key para cada archivo |
| `normalizarPedidoConFormDataKey()` | NUEVA | Preserva formdata_key en JSON |
| `agregarArchivosAFormData()` | NUEVA | Agrega con formdata_key correcto |

---

## ✅ VALIDACIÓN

Después de reemplazar el código, verifica en console:

```javascript
// Console log esperado:
[agregarArchivosAFormData] Agregado: prendas[0][imagenes][0]
[agregarArchivosAFormData] Agregado: prendas[0][telas][0][imagenes][0]
[agregarArchivosAFormData] Agregado: prendas[0][procesos][reflectivo][0]
[agregarArchivosAFormData] Total archivos agregados: 3
```

Si ves "3" → ✅ **Funciona correctamente**

---

**Tiempo de implementación**: 5 minutos  
**Complejidad**: Baja (solo copiar/pegar)  
**Impacto**: Archivos se guardan correctamente ✅
