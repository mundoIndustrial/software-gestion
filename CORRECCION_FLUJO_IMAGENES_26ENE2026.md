# 🖼️ CORRECCIÓN: Flujo Completo de Imágenes (26 Enero 2026)

## 🔴 PROBLEMA IDENTIFICADO

Las imágenes **NO se guardaban en BD** durante la creación de pedidos, aunque el usuario las seleccionaba.

### Síntomas Observados:
```log
[CrearPedidoEditableController] archivos_count: 0 ← ❌ FormData vacío
[ResolutorImagenesService] archivos_en_request: 0 ← Ningún archivo llegó
[MapeoImagenesService] imagenes_mapeadas: 0 ← Nada qué mapear
```

## 🔍 CAUSA RAÍZ

El problema estaba en **`PayloadNormalizer.buildFormData()`**:

1. **Extraía correctamente los Files** ✅
2. **Limpiaba el JSON** ✅  
3. **Pero NO agregaba los archivos al FormData** ❌

El código anterior usaba claves como:
```javascript
'files_prenda_0_0'        // ← Las claves eran genéricas
'files_tela_0_0_0'        // ← Sin estructura de índices clara
```

**El backend NO podía ubicar estos archivos** porque esperaba claves con estructura anidada:
```javascript
'prendas[0][imagenes][0]'          // ← Estructura esperada
'prendas[0][telas][0][imagenes][0]'
'prendas[0][procesos][reflectivo][0]'
```

---

## ✅ SOLUCIONES IMPLEMENTADAS

### 1️⃣ **Corrección en PayloadNormalizer.buildFormData()** 

**Archivo:** `public/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js`

#### Cambios:
- ✅ Ahora usa claves con **estructura anidada correcta** que el backend puede parsear
- ✅ Agrega **debug logs detallados** para cada archivo
- ✅ Cuenta archivos antes y después

#### Antes:
```javascript
const key = 'files_prenda_' + prendaIdx + '_' + imgIdx;
formData.append(key, file);  // ❌ El backend no puede ubicar esto
```

#### Después:
```javascript
const key = 'prendas[' + prendaIdx + '][imagenes][' + imgIdx + ']';
formData.append(key, file);  // ✅ Estructura clara y parseable

// Logs:
console.debug('[PayloadNormalizer.buildFormData] Agregado archivo prenda:', {
    key: key,
    nombre: file.name,
    size: file.size
});
```

#### Estructura de Claves Ahora:
```javascript
// PRENDAS
prendas[0][imagenes][0]
prendas[0][imagenes][1]

// TELAS
prendas[0][telas][0][imagenes][0]
prendas[0][telas][1][imagenes][0]

// PROCESOS
prendas[0][procesos][reflectivo][0]
prendas[0][procesos][bordado][0]

// EPPs
epps[0][imagenes][0]
epps[1][imagenes][0]
```

---

### 2️⃣ **Mejorado: Log en ItemAPIService.extraerFilesDelPedido()**

**Archivo:** `public/js/modulos/crear-pedido/procesos/services/item-api-service.js`

#### Cambios:
- ✅ Ahora **cuenta archivos extraídos** antes de enviar
- ✅ Log **detallado por tipo de elemento** (prendas, telas, procesos, epps)
- ✅ Facilita debugging si hay inconsistencias

#### Nuevo Log:
```javascript
console.log('[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA:', {
    prendas: 1,
    epps: 0,
    archivos_totales: 3,
    estructura: [
        {
            imagenes_prenda: 1,
            imagenes_telas: 2,
            procesos: [
                { tipo: 'reflectivo', imagenes: 2 }
            ]
        }
    ]
});
```

---

### 3️⃣ **Mejorado: Log en CrearPedidoEditableController**

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

#### Cambios:
- ✅ Log **más claro** sobre archivos recibidos
- ✅ **Explica qué significa cada valor**
- ✅ Hint directo: "Si archivos está vacío, el problema está en el frontend"

#### Nuevo Log:
```php
Log::debug('[CrearPedidoEditableController] 📤 Archivos en FormData', [
    'total_archivos' => 3,
    'archivos' => [
        ['key' => 'prendas[0][imagenes][0]', 'name' => 'prenda.jpg', 'size' => 45000],
        ['key' => 'prendas[0][telas][0][imagenes][0]', 'name' => 'tela1.jpg', 'size' => 38000],
        ['key' => 'prendas[0][telas][1][imagenes][0]', 'name' => 'tela2.jpg', 'size' => 42000],
    ],
    'keys_recibidas' => ['prendas[0][imagenes][0]', 'prendas[0][telas][0][imagenes][0]', ...],
    'nota' => 'Si archivos está vacío aquí, el problema está en el frontend'
]);
```

---

### 4️⃣ **Mejorado: Log en ResolutorImagenesService**

**Archivo:** `app/Domain/Pedidos/Services/ResolutorImagenesService.php`

#### Cambios:
- ✅ **Detección clara del problema** si FormData llega vacío
- ✅ Log con **comparación**: archivos esperados vs. recibidos
- ✅ Error explícito si hay inconsistencia

#### Nuevo Log:
```php
Log::error('[ResolutorImagenesService] ❌ ERROR CRÍTICO: Se esperan imágenes pero FormData vacío', [
    'imagenes_en_dto' => 3,
    'archivos_en_request' => 0,
    'esto_explicaría_por_qué_no_se_guardan_imágenes' => 'Los archivos no llegaron en FormData'
]);

// Y al final:
Log::info('[ResolutorImagenesService] ✅ Extracción completada', [
    'pedido_id' => 2728,
    'imagenes_procesadas' => 3,
    'imagenes_esperadas' => 3,
    'diferencia' => 0,  // ← Debe ser 0 si todo funcionó
]);
```

---

## 📋 FLUJO COMPLETO (AHORA CORRECTO)

```
┌─────────────────────────────────────────────────────────────────┐
│ 1️⃣  USUARIO SELECCIONA IMÁGENES EN FORMULARIO                   │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 2️⃣  ItemFormCollector.recolectarDatosPedido()                   │
│     - Extrae File objects de inputs[type="file"]                │
│     - Genera UIDs para cada imagen                              │
│     - Retorna: { prendas: [...], epps: [...] }                 │
│     ✅ Files aún presentes aquí                                 │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 3️⃣  ItemAPIService.crearPedido()                                │
│     a) Llama: extraerFilesDelPedido(pedidoData)                 │
│        - Extrae recursivamente TODOS los File objects           │
│        - Estructura: { prendas: [...], epps: [...] }            │
│        ✅ Files capturados aquí                                 │
│                                                                  │
│     b) Llama: PayloadNormalizer.normalizar(pedidoData)          │
│        - Normaliza JSON (sin Files)                             │
│        - Retorna: { cliente, asesora, prendas[], epps[] }      │
│        ✅ JSON limpio sin File objects                          │
│                                                                  │
│     c) Llama: PayloadNormalizer.buildFormData(...)              │
│        - Agrega JSON al FormData como 'pedido'                  │
│        - 📌 NUEVO: Agrega archivos con claves correctas         │
│          * prendas[0][imagenes][0] = File                       │
│          * prendas[0][telas][0][imagenes][0] = File            │
│          * prendas[0][procesos][reflectivo][0] = File          │
│        ✅ FormData construido correctamente                     │
│                                                                  │
│     d) Envía POST con FormData                                  │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 4️⃣  CrearPedidoEditableController.crearPedido()                 │
│     - Recibe Request con FormData                               │
│     - 📌 NUEVO: Log claro mostrando archivos recibidos          │
│     - $request->allFiles() ≠ empty ✅                            │
│     - Decodifica JSON                                           │
│     - Valida estructura                                         │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 5️⃣  MapeoImagenesService.mapearYCrearFotos()                   │
│     - Llama: ResolutorImagenesService                           │
│       (ver paso 6)                                              │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 6️⃣  ResolutorImagenesService.extraerYProcesarImagenes()         │
│     - 📌 NUEVO: Log sobre archivos esperados vs. recibidos      │
│     - Obtiene archivos de $request->file('prendas[0][imagenes][0]')
│     - Procesa: redimensiona → convierte a WEBP                  │
│     - Guarda: storage/pedidos/{id}/prendas/                    │
│     - Registra en mapeo: uid → ruta_final                       │
│     ✅ Imágenes guardadas en disco                              │
│     ✅ Mapeo UID→Ruta creado                                    │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 7️⃣  MapeoImagenesService.crearRegistrosPrendas()                │
│     - Crea: PrendaFotoPedido (para cada imagen de prenda)      │
│     - Crea: PrendaFotoTelaPedido (para cada imagen de tela)    │
│     - Crea: ProcesoPrendaFoto (para cada imagen de proceso)    │
│     ✅ Imágenes vinculadas a entidades en BD                    │
└─────────────────────────────────────────────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────────┐
│ 8️⃣  TRANSACCIÓN COMMIT ✅                                       │
│     - Pedido creado                                             │
│     - Imágenes guardadas en storage/ Y BD                       │
│     - Respuesta al cliente: { pedido_id, numero_pedido }        │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🧪 EJEMPLO DE LOG ESPERADO (CUANDO TODO FUNCIONA)

### FRONTEND (Console del navegador):

```javascript
// 1. ItemFormCollector recolecta
[ItemFormCollector] Estructura pedidoFinal: {
  cliente: "MINCIVIL88",
  asesora: "....",
  prendas: [
    {
      uid: "uid-prd-abc123",
      nombre_prenda: "Camisa",
      imagenes: [File, File],
      telas: [
        {
          uid: "uid-tela-xyz789",
          imagenes: [File, File]
        }
      ],
      procesos: {
        reflectivo: {
          uid: "uid-proc-def456",
          imagenes: [File]
        }
      }
    }
  ],
  epps: []
}

// 2. ItemAPIService.extraerFilesDelPedido
[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA: {
  prendas: 1,
  epps: 0,
  archivos_totales: 5,
  estructura: [
    {
      imagenes_prenda: 2,
      imagenes_telas: 2,
      procesos: [
        { tipo: "reflectivo", imagenes: 1 }
      ]
    }
  ]
}

// 3. PayloadNormalizer.normalizar
[PayloadNormalizer] Prenda 0 normalizada
[PayloadNormalizer] Pedido completo normalizado

// 4. PayloadNormalizer.buildFormData
[PayloadNormalizer.buildFormData] Agregado archivo prenda: {
  key: "prendas[0][imagenes][0]",
  nombre: "camisa_frente.jpg",
  size: 125000
}
[PayloadNormalizer.buildFormData] Agregado archivo tela: {
  key: "prendas[0][telas][0][imagenes][0]",
  nombre: "tela_rojo.jpg",
  size: 98000
}
[PayloadNormalizer.buildFormData] Agregado archivo tela: {
  key: "prendas[0][telas][0][imagenes][1]",
  nombre: "tela_rojo_detalle.jpg",
  size: 87000
}
[PayloadNormalizer.buildFormData] Agregado archivo proceso: {
  key: "prendas[0][procesos][reflectivo][0]",
  nombre: "reflectivo_ubicacion.jpg",
  size: 65000
}
[PayloadNormalizer.buildFormData] FormData construido: {
  json_size: 3456,
  archivos_totales: 5  ← ✅ 5 archivos agregados
}
```

### BACKEND (laravel.log):

```log
[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] 🚀 Iniciando creación transaccional {"has_pedido_json":true,"archivos_count":5}

[2026-01-26 14:35:22] local.DEBUG: [CrearPedidoEditableController] 📤 Archivos en FormData {
  "total_archivos": 5,
  "archivos": [
    {"key":"prendas[0][imagenes][0]","name":"camisa_frente.jpg","size":125000},
    {"key":"prendas[0][telas][0][imagenes][0]","name":"tela_rojo.jpg","size":98000},
    {"key":"prendas[0][telas][0][imagenes][1]","name":"tela_rojo_detalle.jpg","size":87000},
    {"key":"prendas[0][procesos][reflectivo][0]","name":"reflectivo_ubicacion.jpg","size":65000}
  ],
  "keys_recibidas": ["prendas[0][imagenes][0]", "prendas[0][telas][0][imagenes][0]", ...],
  "nota": "Si archivos está vacío aquí, el problema está en el frontend"
}

[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] Cliente obtenido/creado {"cliente_id":1003,"nombre":"MINCIVIL88"}

[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] Pedido normalizado {"cliente_id":1003,"prendas":1,"epps":0}

[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] Pedido base creado {"pedido_id":2729,"numero_pedido":100010}

[2026-01-26 14:35:22] local.INFO: [ResolutorImagenesService] Iniciando extracción de imágenes {
  "pedido_id": 2729,
  "prendas_count": 1,
  "archivos_en_request": 5,
  "keys_request": ["prendas[0][imagenes][0]", "prendas[0][telas][0][imagenes][0]", ...],
  "nota": "5 archivos recibidos correctamente"
}

[2026-01-26 14:35:22] local.DEBUG: [ResolutorImagenesService] Imagen procesada {
  "imagen_uid": "uid-prd-abc123-img1",
  "ruta": "pedidos/2729/prendas/uid-prd-abc123-img1.webp",
  "parent_uid": "uid-prd-abc123"
}

[2026-01-26 14:35:22] local.DEBUG: [ResolutorImagenesService] Imagen procesada {
  "imagen_uid": "uid-tela-xyz789-img1",
  "ruta": "pedidos/2729/telas/uid-tela-xyz789-img1.webp",
  "parent_uid": "uid-tela-xyz789"
}

[2026-01-26 14:35:22] local.INFO: [ResolutorImagenesService] ✅ Extracción completada {
  "pedido_id": 2729,
  "imagenes_procesadas": 5,
  "imagenes_esperadas": 5,
  "diferencia": 0
}

[2026-01-26 14:35:22] local.INFO: [MapeoImagenesService] Mapeo UID→Ruta completado {"imagenes_mapeadas":5}

[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] Imágenes mapeadas {
  "pedido_id": 2729,
  "imagenes_mapeadas": 5
}

[2026-01-26 14:35:22] local.INFO: [CrearPedidoEditableController] TRANSACCIÓN EXITOSA {
  "pedido_id": 2729,
  "numero_pedido": "100010",
  "cantidad_total_prendas": 1,
  "cantidad_total_epps": 0,
  "cantidad_total": 1,
  "imagenes_procesadas": 5
}
```

---

## 🧪 CÓMO VERIFICAR QUE FUNCIONA

### 1. Crea un pedido con imágenes:
```
✅ Selecciona 2 imágenes para la prenda
✅ Selecciona 2 imágenes para la tela
✅ Selecciona 1 imagen para un proceso
→ Total: 5 imágenes
```

### 2. Revisa el navegador (Console):
```javascript
// Debe ver:
[extraerFilesDelPedido] ✅ EXTRACCIÓN COMPLETADA: {
  ...
  archivos_totales: 5  ← Debe mostrar 5
}

[PayloadNormalizer.buildFormData] FormData construido: {
  ...
  archivos_totales: 5  ← Debe mostrar 5
}
```

### 3. Revisa el log del servidor (laravel.log):
```bash
tail -f storage/logs/laravel.log | grep ResolutorImagenes
```

Debe ver:
```log
[ResolutorImagenesService] Iniciando extracción {
  "archivos_en_request": 5  ← Debe ser 5, no 0
}

[ResolutorImagenesService] ✅ Extracción completada {
  "imagenes_procesadas": 5,  ← Debe ser 5
  "imagenes_esperadas": 5,
  "diferencia": 0  ← Debe ser 0
}
```

### 4. Verifica archivos en disco:
```bash
ls -la storage/app/public/pedidos/2729/prendas/
ls -la storage/app/public/pedidos/2729/telas/
```

Debe haber archivos .webp con nombres como:
```
uid-prd-abc123-img1.webp
uid-tela-xyz789-img1.webp
```

### 5. Verifica BD:
```sql
SELECT * FROM prenda_fotos_pedido WHERE prenda_pedido_id = 3438;
SELECT * FROM prenda_fotos_tela_pedido WHERE prenda_pedido_color_tela_id = 60;
SELECT * FROM pedidos_procesos_imagenes WHERE proceso_prenda_id = 77;
```

Deben tener registros con:
- `ruta_webp` → apuntando a storage
- `uid_imagen` → el UID original del frontend

---

## 🐛 DIAGNOSTICAR SI ALGO SIGUE FALLANDO

### Si ves: `archivos_count: 0` en el log del servidor

**Diagnóstico:**
```
┌─ ¿El FormData se construyó en frontend?
│  └─ Ver console del navegador:
│     Si ves: [PayloadNormalizer.buildFormData] archivos_totales: 5
│     → El problema NO es el frontend
│
│  └─ Si NO ves ese log:
│     → El problema ES el frontend
│        - ¿PayloadNormalizer.buildFormData se llamó?
│        - ¿buildFormData recibió filesExtraidos?
│        - ¿Los Files son instancia de File?
│
└─ ¿El FormData llegó al servidor?
   └─ Ver log del servidor:
      Si ves: [CrearPedidoEditableController] archivos_count: 5
      → Sí llegó
      
      Si ves: [CrearPedidoEditableController] archivos_count: 0
      → NO llegó (problema en red o navegador)
```

---

## 📚 RESUMEN DE CAMBIOS

| Archivo | Cambio | Impacto |
|---------|--------|--------|
| `payload-normalizer-v3-definitiva.js` | Arregló `buildFormData()` para agregar archivos con claves correctas | ✅ Archivos llegan al backend |
| `item-api-service.js` | Mejoró logs en `extraerFilesDelPedido()` | 🔍 Debugging más fácil |
| `CrearPedidoEditableController.php` | Mejoró logs iniciales mostrando archivos recibidos | 🔍 Claridad sobre flujo |
| `ResolutorImagenesService.php` | Mejoró logs con comparación esperados vs. procesados | 🔍 Identifica cuándo faltan archivos |

---

## ✅ RESULTADO ESPERADO

**Después de estas correcciones:**

```
Usuario selecciona imágenes
         ↓
FormData enviado con archivos correctamente estructurados
         ↓
Backend recibe archivos_count > 0 ✅
         ↓
ResolutorImagenesService procesa imágenes
         ↓
Imágenes guardadas en storage/ como WEBP
         ↓
Registros creados en BD (prenda_fotos_pedido, etc)
         ↓
✅ PEDIDO COMPLETO CON IMÁGENES
```

