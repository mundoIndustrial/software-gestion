# 🔍 ANÁLISIS DETALLADO: LÓGICA DE EDICIÓN DE PRENDAS

## 📋 TABLA DE CONTENIDOS
1. [Flujo Actual](#flujo-actual)
2. [Estructura de Datos](#estructura-de-datos)
3. [Problemas Identificados](#problemas-identificados)
4. [Flujo de Base de Datos](#flujo-de-base-de-datos)
5. [Manejo de Imágenes](#manejo-de-imágenes)
6. [Ciclo CRUD Completo](#ciclo-crud-completo)

---

## 🔄 FLUJO ACTUAL

### Dos Escenarios de Uso

#### **ESCENARIO 1: Creación de Pedido (crear-nuevo)**
```
Usuario → Click en "Editar" → abrirEditarPrendaEspecifica()
    ↓
[SIN API] Carga LOCAL desde window.datosCreacionPedido.prendas
    ↓
cargarItemEnModal() → luego PrendaEditor.cargarPrendaEnModal()
    ↓
window.prendaEditorLegacy (✅ FUNCIONA - Inicializado en crear-nuevo)
    ↓
Modal se llena con datos locales
```
**Estado en Logs**: ✅ Éxito
- `[CARGAR-PRENDA] Iniciando carga de prenda en modal...`
- `[CARGAR-PRENDA] Prenda cargada completamente`

---

#### **ESCENARIO 2: Edición de Pedido EXISTENTE (pedidos)**
```
Usuario → Click en "Editar" → abrirEditarPrendaEspecifica()
    ↓
[CON API] Llama `/api/pedidos/{id}/obtener-datos-completos`
    ↓
Backend retorna respuesta.data.prendas[] con datos COMPLETOS de BD
    ↓
cargarItemEnModal() → luego PrendaEditor.cargarPrendaEnModal()
    ↓
❌ window.prendaEditorLegacy NO ESTÁ INICIALIZADO
    ↓
ERROR: TypeError: Cannot read properties of undefined
```
**Estado en Logs**: ❌ Error en línea 87 de prenda-editor.js

```
[CARGAR-PRENDA] Error: TypeError: Cannot read properties of undefined 
(reading 'aplicarOrigenAutomaticoDesdeCotizacion')
at PrendaEditor.cargarPrendaEnModal (prenda-editor.js:87:63)
```

---

## 📊 ESTRUCTURA DE DATOS

### Tabla: `prendas_pedido` (Datos Principales)
```sql
┌─────────────────────────────────────────┐
│ prendas_pedido                          │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ pedido_produccion_ │ bigint (FK)        │
│ nombre_prenda      │ varchar(500)       │
│ descripcion        │ longtext           │
│ de_bodega          │ tinyint(0/1)       │  ← Origen (bodega vs confección)
│ created_at         │ timestamp          │
│ updated_at         │ timestamp          │
│ deleted_at         │ timestamp (soft)   │
└────────────────────┴────────────────────┘
```

### Tabla: `prenda_pedido_tallas` (Tallas/Cantidades)
```sql
┌─────────────────────────────────────────┐
│ prenda_pedido_tallas                    │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ prenda_pedido_id   │ bigint (FK)        │
│ genero             │ enum (DAMA/CAB)    │
│ talla              │ varchar(50)        │
│ cantidad           │ int                │
│ colores            │ json (obsoleto)    │
│ created_at         │ timestamp          │
└────────────────────┴────────────────────┘
```

### Tabla: `prenda_pedido_colores_telas` (Telas/Colores)
```sql
┌─────────────────────────────────────────┐
│ prenda_pedido_colores_telas             │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ prenda_pedido_id   │ bigint (FK)        │
│ tela_id            │ bigint (FK → telas)│
│ color_id           │ bigint (FK → cols) │
│ referencia         │ varchar(500)       │
│ created_at         │ timestamp          │
└────────────────────┴────────────────────┘
```

### Tabla: `prenda_fotos_pedido` (Imágenes Principales)
```sql
┌─────────────────────────────────────────┐
│ prenda_fotos_pedido                     │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ prenda_pedido_id   │ bigint (FK)        │  ← Vinculado a prenda
│ ruta_original      │ varchar(255)       │  ← Path original (sin webp)
│ ruta_webp         │ varchar(255)       │  ← Path optimizado
│ orden              │ int                │
│ deleted_at         │ timestamp (soft)   │
└────────────────────┴────────────────────┘
```

### Tabla: `prenda_pedido_variantes` (Variaciones de Diseño)
```sql
┌─────────────────────────────────────────┐
│ prenda_pedido_variantes                 │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ prenda_pedido_id   │ bigint (FK)        │
│ tipo_manga_id      │ bigint (FK)        │
│ tipo_broche...id   │ bigint (FK)        │
│ manga_obs          │ longtext           │
│ broche_boton_obs   │ longtext           │
│ tiene_bolsillos    │ tinyint(0/1)       │
└────────────────────┴────────────────────┘
```

### Tabla: `pedidos_procesos_prenda_detalles` (Procesos/Recibos)
```sql
┌─────────────────────────────────────────┐
│ pedidos_procesos_prenda_detalles        │
├────────────────────┬────────────────────┤
│ id                 │ bigint (PK)        │
│ prenda_pedido_id   │ bigint (FK)        │
│ tipo_proceso_id    │ bigint (FK)        │
│ tipo_recibo        │ enum (COSTURA...)  │
│ numero_recibo      │ varchar(20)        │
│ estado             │ enum (PENDIENTE...)│
│ ubicaciones        │ json               │
│ observaciones      │ text               │
│ data_adicionales   │ json               │
└────────────────────┴────────────────────┘
```

─

## 🚨 PROBLEMAS IDENTIFICADOS

### Problema 1: `window.prendaEditorLegacy` NO INICIALIZADO
**Ubicación**: `prenda-editor.js:87`
**Causa**: `PrendaEditor.cargarPrendaEnModal()` espera que `window.prendaEditorLegacy` esté globalmente disponible

**Stack**:
```javascript
// prenda-editor.js:87
const prendaProcesada = window.prendaEditorLegacy.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
// window.prendaEditorLegacy === undefined
```

**¿Por qué ocurre en edición pero NO en creación?**
- En `crear-nuevo`: El HTML carga Scripts en order específico que INICIALIZA `window.prendaEditorLegacy`
- En `pedidos`: Cuando se abre el modal editar, los scripts de `crear-nuevo` NO se han cargado

---

### Problema 2: Dos Métodos de Carga CONFLICTIVOS
**En prenda-editor.js existen:**

1. ✅ **`cargarPrendaEnModal()`** - Para crear-nuevo (requisitos: `prendaEditorLegacy`)
2. ✅ **`cargarPrendaEnModalDDD()`** - Para pedidos (API, no toca legacy)

**El problema**: `cargarItemEnModal()` en `gestion-items-pedido.js:369` llama siempre a la versión **legacy**, asumiendo que `prendaEditorLegacy` existe:

```javascript
// gestion-items-pedido.js:369
window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
    ↓
PrendaEditor.cargarPrendaEnModal() ← ASUME prendaEditorLegacy disponible
    ↓
❌ FALLA EN EDICIÓN (flujo DDD)
```

---

### Problema 3: Datos en Diferentes Formatos
El servidor puede retornar datos en 2-3 formatos según contexto:

```javascript
// FORMATO NUEVO (DDD - pedidos edición)
{
  generosConTallas: { DAMA: {L: 20}, CABALLERO: {} },
  telas_array: [{id: 23, tela_id: 3, color_id: null}],
  variantes: [{manga: "Larga", broche: "Botón"}],
  procesos: [{id: 14, nombre: "Reflectivo"}]
}

// FORMATO ANTIGUO (Legacy - crear-nuevo)
{
  tallas_dama: [{talla: "L", cantidad: 20}],
  tallas_caballero: [{talla: "M", cantidad: 15}],
  colores_telas: [{id: 1, color_id: 61}],
  procesos: [{nombre: "Reflectivo"}]
}

// FORMATO MIXTO (Contenido de cotización)
{
  cantidad_talla: {DAMA: {L: 20}, CABALLERO: {M: 15}},
  telasCreacion: [{...}]
}
```

---

## 💾 FLUJO DE BASE DE DATOS

### Lectura
```
[EDICIÓN] Usuario abre pedido #19
    ↓
GET /api/pedidos/19/obtener-datos-completos
    ↓
Backend ejecuta:
  ├─ SELECT * FROM prendas_pedido WHERE pedido_produccion_id = 19
  ├─ LEFT JOIN prenda_pedido_tallas ON prendas_pedido.id = prenda_pedido_tallas.prenda_pedido_id
  ├─ LEFT JOIN prenda_pedido_colores_telas ...
  ├─ LEFT JOIN prenda_fotos_pedido ...
  ├─ LEFT JOIN prenda_pedido_variantes ...
  └─ LEFT JOIN pedidos_procesos_prenda_detalles ...
    ↓
Transforma: ANTIGUO → NUEVO (DDD)
    ↓
Retorna JSON al Frontend
    ↓
Frontend procesa en prenda-editor-modal.js (línea 350-700)
    → Detecta formato automáticamente
    → Transforma a estructura compatible
    → Carga en Modal
```

### Creación
```
[CREAR PEDIDO] Usuario agrega prenda COTIZACIÓN
    ↓
setTimeout() llama `agregarPrendaAlPedido()`
    ↓
Guarda en window.prendas[] (ARRAY LOCAL)
    → NO toca BD todavía
    ↓
Abre modal: PrendaEditor.cargarPrendaEnModal()
    ↓
Busca datos locales: window.prendas[index]
    ↓
Renderiza con prendaEditorLegacy (cargar telas, tallas, etc)
    ↓
Usuario hace SUBMIT en modal
    ↓
API POST /api/prendas → INSERT en BD
```

### Actualización
```
[EDICIÓN] Usuario modifica prenda e intenta GUARDAR
    ↓
Click "Guardar Cambios"
    ↓
Sistema obtiene datos del modal:
  ├─ Nombre: <input id="nueva-prenda-nombre">
  ├─ Descripción: <textarea>
  ├─ Origen: <select id="nueva-prenda-origen-select">
  ├─ Tallas/Cantidades: window.tallasRelacionales
  ├─ Telas/Colores: vistaTelaActual[]
  ├─ Procesos: window.procesosSeleccionados
  └─ Imágenes: window.imagenesCreacion[]
    ↓
API PUT/PATCH /api/prendas/{id}
    ↓
Backend:
  ├─ UPDATE prendas_pedido SET ... WHERE id = ?
  ├─ DELETE prenda_pedido_tallas WHERE prenda_pedido_id = ?
  ├─ INSERT prenda_pedido_tallas (nuevas)
  ├─ DELETE prenda_fotos_pedido WHERE deleted_at IS NULL (si usuario elimina)
  ├─ INSERT prenda_fotos_pedido (si usuario agrega)
  ├─ DELETE prenda_pedido_colores_telas (si elimina colores)
  ├─ INSERT prenda_pedido_colores_telas (si agrega)
  └─ UPDATE prenda_pedido_variantes (si cambia)
    ↓
Retorna prenda actualizada
    ↓
Frontend cierra modal y actualiza tabla
```

---

## 🖼️ MANEJO DE IMÁGENES

### Storage Actual
```
/storage/
├── pedidos/
│   └── {pedido_id}/
│       └── prenda/
│           ├── prendas_20260213081621_5JZGw13m.webp  ← CREA (frontend transforma)
│           ├── prendas_20260213081623_199yQtHW.webp  ← CREA
│           └── prendas_original_xyz.jpg                  ← SI EL USUARIO SUBE JPG
└── procesos/
    └── {pedido_id}/
        └── proceso_{tipo}/
            ├── imagen_costura_001.webp
            └── imagen_costura_002.webp
```

### Flujo de Imágenes (CREAR PRENDA)
```
[Usuario arrastra/pega imagen en modal]
    ↓
DragDropManager.js capta evento (paste/drop)
    ↓
Valida: ¿es válida? ¿Less than 5MB?
    ↓
Convierte a blob → Crea Data URL (blob:...)
    ↓
Guarda en: window.imagenesCreacion[] = [
  {
    archivo: File object,
    preview: "blob:http://localhost:8000/e373b1a4-d815-486b...",
    nombre: "prendas_TIMESTAMP_RANDOM.webp"
  }
]
    ↓
Renderiza preview
    ↓
[Usuario SUBMIT en modal]
    ↓
FormData API:
  - for each imagen in window.imagenesCreacion[]
  - formData.append('imagenes[]', imagen.archivo)
    ↓
API POST enctype=multipart/form-data
    ↓
Backend procesa (Laravel Storage):
  ├─ Valida archivo
  ├─ Redimensiona si es > cierto tamaño
  ├─ Crea WEBP optimizado
  ├─ Guarda en /storage/pedidos/{id}/prenda/
  ├─ INSERT prenda_fotos_pedido (ruta_original, ruta_webp)
  └─ Retorna URLs guardadas
    ↓
Frontend obtiene: /storage/pedidos/19/prenda/prendas_xyz.webp
```

### Flujo de Imágenes (EDITAR PRENDA)
```
[Usuario abre modal de edición]
    ↓
Backend retorna fotos EXISTENTES en prenda_fotos_pedido:
  {
    fotos: [
      {id: 16, ruta_webp: "/storage/pedidos/19/prenda/prendas_20260213081621_5JZGw13m.webp"},
      {id: 17, ruta_webp: "/storage/pedidos/19/prenda/prendas_20260213081623_199yQtHW.webp"}
    ]
  }
    ↓
prenda-editor-modal.js mapea estas URLs ← línea 2800-2900
    → Guarda en window.prendaEnEdicion.imagenes
    ↓
[Usuario elimina 1 imagen y agrega 1 nueva]
    ↓
Cambios calculados:
  Eliminar:
    - ID 16 (existente)
    → API DELETE /api/prendas/{id}/fotos/16
    → Backend: DELETE FROM prenda_fotos_pedido WHERE id = 16
    → Storage: unlink("/storage/pedidos/19/prenda/prendas_5JZGw13m.webp")
  
  Agregar:
    - nuevo File object
    → FormData append + API POST /api/prendas/{id}/fotos
    → Backend: INSERT prenda_fotos_pedido + Storage save
    ↓
Frontend: actualiza window.imagenesCreacion con cambios
```

---

## 🔄 CICLO CRUD COMPLETO

### CREATE (Crear Prenda en Pedido)
```
FLUJO: prenda-editor-modal.js → abrirEditarPrendaEspecifica()
       (cuando modo es CREAR, no API)

1. LECTURA
   - Datos del localStorage/variable local
   - O desde form si es cotización
   - NO desde BD

2. PROCESAMIENTO
   - Llena modal localmente
   - Maneja con prendaEditorLegacy
   
3. VALIDACIÓN
   - ¿Tiene nombre?
   - ¿Tiene al menos talla?
   - ¿Tiene telas?
   
4. INSERT
   - API POST /api/prendas
   - Body: {nombre, descripcion, tallas[], telas[], imagenes[]}
   - Backend: INSERT prendas_pedido + 7 tablas relacionadas
   
5. RESULT
   - Si éxito: Retorna prenda.id
   - Actualiza window.prendas[] con nuevo ID
   - Cierra modal
   - Anuncia: "Prenda agregada"
```

### READ (Leer Prenda para Editar)
```
FLUJO: prenda-editor-modal.js → abrirEditarPrendaEspecifica()
       (cuando modo es EDITAR)

1. FETCH
   - API GET /api/pedidos/{pedido_id}/obtener-datos-completos
   - Backend:
     ├─ SELECT p.* FROM prendas_pedido p
     ├─ WHERE p.pedido_produccion_id = {pedido_id}
     ├─ LEFT JOIN 7 tablas relacionadas
     └─ RETURN JSON transformado
   
2. TRANSFORMACIÓN (prenda-editor-modal.js:350-750)
   - Detecta formato automáticamente
   - Mapea URLs de storage
   - Estructura datos para modal
   
3. CARGA EN MODAL
   - cargarItemEnModal(prendaParaEditar)
   - Renderiza UI
   - Inicializa drag-drop
   
4. USUARIO VE
   - Todos los datos precargados
   - Puede ver/editar/eliminar
```

### UPDATE (Actualizar Prenda)
```
FLUJO: (depende del origen)

SI ES CREAR-NUEVO:
  - Todo es LOCAL hasta que usuario SUBMIT pedido completo
  - API POST /api/pedidos (envía TODAS las prendas)
  
SI ES EDICIÓN:
  - API PATCH /api/prendas/{id}
  - Body: datos modificados del modal
  - Backend:
    ├─ UPDATE prendas_pedido SET ...
    ├─ DELETE prenda_pedido_tallas + INSERT nuevas
    ├─ DELETE prenda_pedido_colores_telas + INSERT nuevas
    ├─ DELETE prenda_pedido_variantes + INSERT nuevas
    ├─ DELETE prenda_fotos_pedido (de las que usuario eliminó)
    ├─ INSERT prenda_fotos_pedido (nuevas)
    └─ COMMIT transaction
```

### DELETE (Eliminar Prenda)
```
FLUJO: Usuario presiona "Eliminar Prenda"

1. CONFIRMACIÓN
   - Swal.fire({ title: "¿Eliminar?" })
   
2. SI ACEPTA
   - API DELETE /api/prendas/{id}
   
3. BACKEND
   - soft_delete: UPDATE prendas_pedido SET deleted_at = NOW()
   - Automáticamente propaga:
     ├─ prenda_pedido_tallas.deleted_at = NOW()
     ├─ prenda_pedido_colores_telas.deleted_at = NOW()
     ├─ prenda_fotos_pedido.deleted_at = NOW()
     ├─ prenda_pedido_variantes.deleted_at = NOW()
     └─ procesos.deleted_at = NOW()
   
4. STORAGE (si aplica)
   - No elimina archivos automáticamente
   - Requiere comando: php artisan storage:purge-soft-deleted
   
5. FRONTEND
   - Actualiza tabla de prendas
   - Anunci: "Prenda eliminada"
```

---

## 🔗 RELACIONES ENTRE TABLAS

```
pedidos_produccion
    ↓ (pedido_produccion_id)
prendas_pedido (PRENDA PRINCIPAL)
    ├─ prenda_pedido_tallas (TALLAS/CANTIDADES)
    │   └─ género, talla, cantidad
    │
    ├─ prenda_pedido_colores_telas (TELAS/COLORES)
    │   ├─ tela_id → telas
    │   ├─ color_id → colores
    │   └─ referencia
    │
    ├─ prenda_fotos_pedido (IMÁGENES)
    │   └─ ruta_webp, ruta_original
    │
    ├─ prenda_pedido_variantes (DISEÑO)
    │   ├─ tipo_manga_id → tipos_manga
    │   ├─ tipo_broche_boton_id → tipos_broche_boton
    │   └─ tiene_bolsillos
    │
    └─ pedidos_procesos_prenda_detalles (PROCESOS)
        ├─ tipo_proceso_id → tipos_proceso
        ├─ pedidos_procesos_prenda_tallas (tallas x proceso)
        └─ pedidos_procesos_imagenes (imágenes x proceso)
```

---

## 🎯 RESUMEN DEL FLUJO IDEAL

```
┌─────────────┐          ┌─────────────┐
│ CREAR NUEVO │          │   EDITAR    │
└──────┬──────┘          └──────┬──────┘
       │                        │
       │ LOCAL DATA             │ API CALL
       │ window.prendas[]       │ GET /api/pedidos/{id}
       │                        │
       ├─→ cargarPrendaEnModal()│
       │   ├─ prendaEditorLegacy├─→ Buscar en respuesta
       │   │  (telas, tallas)   │   
       │   │                    ├─ prenda-editor-modal.js
       │   │                    │  (transformar formato)
       │   └─ Modal cargado     │
       │                        ├─ cargarItemEnModal()
       │   User modifica        │
       │   User submits         │   User modifica
       │   ↓                    │   User submits
       ├─→ VALIDAR             │   ↓
       │   GUARDAR             └─→ VALIDAR
       │   API POST /api/prendas   GUARDAR
       │   INSERT en BD         API PATCH /api/prendas/{id}
       │                        UPDATE en BD
       │
       └─→ ✅ Éxito
           Cierra modal
           Actualiza UI
```

---

## 📝 NOTAS CRÍTICAS

### Problemas de Integridad
- ❌ `prendaEditorLegacy` no se inicializa en contexto de edición
- ❌ Dos métodos de carga conflictivos (`cargarPrendaEnModal` vs `cargarPrendaEnModalDDD`)
- ❌ Formato de datos inconsistente entre escenarios

### Mejoras Sugeridas
- ✅ Usar SIEMPRE la ruta DDD (no legacy) en edición
- ✅ Unificar los dos métodos de carga en uno solo
- ✅ Normalizar el formato de datos en el backend
- ✅ Validar que exista `prendaEditorLegacy` antes de usarlo

### Dependencias Críticas
- `window.prendaEditorLegacy` - DEBE estar inicializado ANTES de editar
- `window.gestionItemsUI` - DEBE estar inicializado
- `window.prendas[]` / `window.datosCreacionPedido` - Para datos locales
- `/api/pedidos/{id}/obtener-datos-completos` - Para edición (backend)

---

## 🛠️ RECOMENDACIÓN FINAL

**Para edición de pedidos**, considera:

```javascript
// CAMBIO EN: prenda-editor-modal.js:1010
// DE:
window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);

// A:
if (window.gestionItemsUI?.cargarPrendaEnModalDDD) {
  // Usar método DDD si disponible (recomendado para edición)
  window.gestionItemsUI.cargarPrendaEnModalDDD(prendaParaEditar, prendasIndex);
} else {
  // Fallback al método legacy (para crear-nuevo)
  window.gestionItemsUI.cargarItemEnModal(prendaParaEditar, prendasIndex);
}
```

Esto evitaría la dependencia de `prendaEditorLegacy` en el flujo de edición.
