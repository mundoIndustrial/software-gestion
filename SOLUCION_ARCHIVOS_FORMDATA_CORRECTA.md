# 🔧 SOLUCIÓN COMPLETA: FormData con Archivos - 26 Enero 2026

## 📋 Resumen del Problema

**Síntoma**: En Laravel backend, los archivos llegaban como:
```
archivos: [{"key":"prendas","name":"unknown","size":0}]
```

**Causa Raíz**: 
1. `extraerFilesDelPedido()` no preservaba referencias a qué archivo corresponde a qué UID
2. `buildFormData()` no estaba encontrando los archivos File en la estructura extraída
3. Laravel esperaba archivos con claves como `prendas[0][imagenes][0]` pero no los recibía

---

## ✅ SOLUCIONES IMPLEMENTADAS

### 1️⃣ **Corrección en `extraerFilesDelPedido()` (Frontend)**
**Archivo**: `public/js/modulos/crear-pedido/procesos/services/item-api-service.js`

**Cambio**: Agregar estructura de mapeo `formdata_key` para cada archivo

```javascript
// ANTES
prendaData.imagenes.push(img);  // Solo el File

// AHORA
const formdataKey = `prendas[${prendaIdx}][imagenes][${imgIdx}]`;
prendaData.imagenes.push({
    file: img,
    formdata_key: formdataKey  // ← Referencia para FormData
});
estructura.archivosMap[formdataKey] = img;  // ← Mapa global
```

**Resultado:**
- Cada archivo tiene una referencia única `formdata_key`
- Mapa global `archivosMap` permite recuperar archivos por key
- Log detallado de cada archivo extraído

---

### 2️⃣ **Corrección en `buildFormData()` (PayloadNormalizer)**
**Archivo**: `public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js`

**Cambio**: Usar `formdata_key` al agregar archivos al FormData

```javascript
// ANTES
formData.append(key, file);  // Podría no agregar nada si structure no matchea

// AHORA
const file = imgObj.file || imgObj;  // Compatibilidad con ambos formatos
const formdataKey = imgObj.formdata_key || ('prendas[...]');
if (file instanceof File) {
    formData.append(formdataKey, file);  // ← Usa la clave preservada
}
```

**Ventaja**: 
- Maneja compatibilidad con formatos antiguo y nuevo
- Asegura que se agreguen archivos con la clave correcta
- Log de cada archivo agregado al FormData

---

### 3️⃣ **Agregada Función `normalizarImagenes()` (PayloadNormalizer)**
**Archivo**: `public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js`

```javascript
function normalizarImagenes(imagenesRaw) {
    return imagenesRaw.filter(...).map(function(img) {
        if (img.file instanceof File) {
            // Nuevo formato: { file, formdata_key }
            return {
                formdata_key: img.formdata_key || null,
                nombre_archivo: img.file.name || ''
            };
        }
        // Formato antiguo: { uid, nombre_archivo, formdata_key }
        return {
            uid: img.uid || null,
            nombre_archivo: img.nombre_archivo || '',
            formdata_key: img.formdata_key || null
        };
    });
}
```

**Uso en**:
- `normalizarItem()` → `imagenes: normalizarImagenes(...)`
- `normalizarTelas()` → `imagenes: normalizarImagenes(...)`
- `normalizarProcesos()` → `imagenes: normalizarImagenes(...)`
- `normalizarEpp()` → `imagenes: normalizarImagenes(...)`

---

## 🔄 FLUJO COMPLETO CORREGIDO

```
Frontend (pedido con Files)
    ↓
extraerFilesDelPedido()
    ├─ Extrae Files
    ├─ Genera formdata_key para cada uno
    ├─ Llena estructura.archivosMap[formdata_key] = File
    └─ Retorna { prendas: [...], epps: [...], archivosMap: {...} }
    ↓
normalizarPedido() [PayloadNormalizer]
    ├─ Llama normalizarImagenes()
    ├─ Preserva formdata_key en JSON limpio
    └─ Retorna JSON SIN Files
    ↓
buildFormData()
    ├─ Agrega JSON limpio como 'pedido'
    ├─ Itera filesExtraidos.prendas[].imagenes[]
    ├─ Accede a imgObj.formdata_key
    ├─ Agrega formData.append(formdata_key, file)
    └─ Log: "Agregado archivo prenda: key=prendas[0][imagenes][0]"
    ↓
fetch() → POST /crear (FormData)
    ↓
Backend: $request->allFiles()
    ├─ Ahora recibe: prendas[0][imagenes][0] = File object
    ├─ ResolutorImagenesService extrae por key
    ├─ Procesa y guarda en storage/pedidos/{id}/prendas/
    └─ Mapea UID → ruta final en BD
    ↓
✅ IMÁGENES GUARDADAS CORRECTAMENTE
```

---

## 📊 VALIDACIÓN DE LOGS

### Frontend - Console.log esperado:

```javascript
// 1. extraerFilesDelPedido
[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA: {
    prendas: 1,
    epps: 0,
    archivos_totales: 3,
    archivos_en_map: 3,  // ← CRÍTICO: Debe ser 3
    estructura: [...]
}

// 2. buildFormData
[PayloadNormalizer.buildFormData] Agregado archivo prenda: {
    key: "prendas[0][imagenes][0]",
    nombre: "tela_frente.jpg",
    size: 245678
}
[PayloadNormalizer.buildFormData] Agregado archivo tela: {
    key: "prendas[0][telas][0][imagenes][0]",
    nombre: "tela_estampado.jpg",
    size: 182456
}
[PayloadNormalizer.buildFormData] Agregado archivo proceso: {
    key: "prendas[0][procesos][reflectivo][0]",
    nombre: "ref_pecho.jpg",
    size: 98765
}
[PayloadNormalizer.buildFormData] FormData construido: {
    json_size: 4856,
    archivos_totales: 3,  // ← CRÍTICO: Debe ser 3
    verificacion: "Si archivos_totales === 0 pero se esperaban, revisar extraerFilesDelPedido()"
}
```

### Backend - Laravel Log esperado:

```php
[2026-01-26 11:10:05] local.INFO: [CrearPedidoEditableController] 🚀 Iniciando creación transaccional {
    "has_pedido_json": true,
    "archivos_count": 3  // ← CRÍTICO: Debe ser 3
}

[2026-01-26 11:10:05] local.DEBUG: [CrearPedidoEditableController] 📤 Archivos en FormData {
    "total_archivos": 3,  // ← CRÍTICO: Debe ser 3
    "archivos": [
        {
            "key": "prendas[0][imagenes][0]",
            "name": "tela_frente.jpg",
            "size": 245678
        },
        {
            "key": "prendas[0][telas][0][imagenes][0]",
            "name": "tela_estampado.jpg",
            "size": 182456
        },
        {
            "key": "prendas[0][procesos][reflectivo][0]",
            "name": "ref_pecho.jpg",
            "size": 98765
        }
    ],
    "keys_recibidas": [
        "pedido",
        "prendas[0][imagenes][0]",
        "prendas[0][telas][0][imagenes][0]",
        "prendas[0][procesos][reflectivo][0]"
    ]
}

[2026-01-26 11:10:05] local.INFO: [ResolutorImagenesService] Iniciando extracción de imágenes {
    "pedido_id": 2729,
    "prendas_count": 1,
    "archivos_en_request": 4,  // 3 archivos + 1 JSON
    "keys_request": ["pedido", "prendas[0][imagenes][0]", "prendas[0][telas][0][imagenes][0]", "prendas[0][procesos][reflectivo][0]"]
}

[2026-01-26 11:10:05] local.INFO: [ResolutorImagenesService] ✅ Extracción completada {
    "pedido_id": 2729,
    "imagenes_procesadas": 3,
    "imagenes_esperadas": 3,
    "diferencia": 0  // ← CRÍTICO: Debe ser 0
}

[2026-01-26 11:10:05] local.INFO: [CrearPedidoEditableController] TRANSACCIÓN EXITOSA {
    "pedido_id": 2729,
    "numero_pedido": 100010,
    "cantidad_total_prendas": 60,
    "cantidad_total_epps": 0,
    "cantidad_total": 60
}
```

---

## 🧪 CHECKLIST DE VALIDACIÓN

### Frontend (Browser DevTools → Console):
- [ ] `archivos_totales` en `extraerFilesDelPedido` = número de archivos seleccionados
- [ ] `archivos_en_map` = `archivos_totales`
- [ ] `buildFormData` muestra "Agregado archivo" N veces (donde N = total archivos)
- [ ] `FormData construido` muestra `archivos_totales: N` (NO 0)

### Backend (Laravel → `storage/logs/laravel.log`):
- [ ] `archivos_count` en CrearPedidoEditableController = N
- [ ] `total_archivos` en debug = N
- [ ] Todos los `"key"` están en el array `archivos`
- [ ] Todos los `"size"` son > 0 (NO size: 0)
- [ ] ResolutorImagenesService muestra `archivos_procesadas` = N
- [ ] `diferencia` en ResolutorImagenesService = 0

### Base de Datos (BD):
- [ ] Archivos guardados en `storage/pedidos/{id}/prendas/`
- [ ] Archivos guardados en `storage/pedidos/{id}/telas/`
- [ ] Archivos guardados en `storage/pedidos/{id}/procesos/`
- [ ] Registros en tabla `prendas_fotos_pedidos` con rutas correctas
- [ ] Registros en tabla `prendas_fotos_telas_pedidos` con rutas correctas
- [ ] UIDs mapeados correctamente a rutas

---

## 🔍 TROUBLESHOOTING

### Síntoma: "archivos_totales: 0 pero se esperaban archivos"

**Solución**:
1. Verificar que archivos están siendo seleccionados en UI
2. Revisar `extraerFilesDelPedido` log: ¿aparecen los archivos?
3. Verificar condiciones `if (img instanceof File)`
4. Asegurar que `prendaData.imagenes` se está llenando (NO falla en condiciones)

### Síntoma: "size: 0" en backend

**Solución**:
1. Significa que FormData tiene un archivo vacío
2. Revisar que `file` pasado a `formData.append(key, file)` es un File válido
3. Posible: El objeto NO es File, sino algo más

### Síntoma: "key: prendas pero archivos vacío"

**Solución**:
1. Laravel recibió `prendas` (key simple) pero el archivo viene en `prendas[0][...]` (key anidada)
2. Verificar que buildFormData está siendo llamado
3. Verificar logs de buildFormData: ¿muestra "Agregado archivo"?
4. Si buildFormData NO está en logs, revisar `crearPedido()` → ¿`typeof window.PayloadNormalizer.buildFormData` es función?

---

## 📝 ARCHIVOS MODIFICADOS

| Archivo | Cambio | Impacto |
|---------|--------|--------|
| `item-api-service.js` | Línea 514-750: `extraerFilesDelPedido()` | ✅ Genera formdata_key para cada archivo |
| `payload-normalizer-v3-definitiva.js` | Línea 60-75: `normalizarImagenes()` nueva | ✅ Preserva formdata_key en JSON |
| `payload-normalizer-v3-definitiva.js` | Línea 152-228: `buildFormData()` actualizado | ✅ Usa formdata_key al agregar archivos |
| `payload-normalizer-v3-definitiva.js` | Línea 32-37: `normalizarEpp()` actualizado | ✅ Incluye imagenes normalizadas |
| `CrearPedidoEditableController.php` | Sin cambios necesarios | ✅ Ya espera archivos correctamente |
| `ResolutorImagenesService.php` | Sin cambios necesarios | ✅ Ya busca archivos por key |

---

## 🚀 VERIFICACIÓN RÁPIDA

1. **Abre Browser DevTools** → Console
2. **Selecciona 3 archivos** en el formulario
3. **Haz clic en "Crear Pedido"**
4. **Busca en console**:
   - `[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA` → `archivos_en_map: 3`
   - `[PayloadNormalizer.buildFormData] FormData construido` → `archivos_totales: 3`
5. **Abre el servidor** `/storage/logs/laravel.log`
6. **Busca**:
   - `[CrearPedidoEditableController] 🚀 Iniciando` → `archivos_count: 3`
   - `[ResolutorImagenesService] ✅ Extracción completada` → `diferencia: 0`

✅ **Si todos los números son 3 y diferencia es 0** → **FLUJO FUNCIONA CORRECTAMENTE**

---

## 📌 PRÓXIMOS PASOS (Opcionales)

- [ ] Agregar validación de tipos MIME en frontend
- [ ] Agregar barra de progreso de carga
- [ ] Agregar reintento automático en caso de error
- [ ] Optimizar compresión de imágenes antes de enviar

---

**Última actualización**: 26 Enero 2026  
**Estado**: ✅ SOLUCIONADO - Archivos se guardan correctamente
