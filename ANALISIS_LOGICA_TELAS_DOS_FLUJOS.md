# Análisis: Lógica de Telas - Dos Flujos

## 📋 Resumen Ejecutivo

Hay **DOS flujos diferentes** para gestionar telas:

### Flujo 1: CREAR PRENDA (Nuevo pedido)
- **Archivo:** `gestion-telas.js` (783 líneas)
- **Contexto:** Usuario crea una prenda desde cero
- **Objetivo:** Capturar múltiples telas nuevas
- **Estado Global:** `window.telasAgregadas = []`
- **Funciones Clave:**
  - `agregarTelaNueva()` - Valida y agrega tela nueva
  - `actualizarTablaTelas()` - Renderiza tabla en UI
  - `removerTela()` - Elimina tela
  - `obtenerTelasFinales()` - Retorna array para envío

### Flujo 2: EDITAR PRENDA (Prenda ya creada)
- **Archivo:** `modal-novedad-edicion.js` + `tela-processor.js`
- **Contexto:** Usuario edita prenda ya guardada
- **Objetivo:** Modificar/agregar telas a existentes
- **Patrón:** **MERGE** (conservar + agregar)
- **Estado Global:** `window.telasAgregadas = []` (reutilizado)
- **Funciones Clave:**
  - `cargarTelaDesdeBaseDatos()` - Carga telas existentes de BD
  - `agregarTelaAlStorage()` - Agrega al array global
  - Modal captura cambios en `telasAgregadas`
  - Envío diferencia entre old/new

---

## 🔍 FLUJO 1: CREAR PRENDA (gestion-telas.js)

### Inicio
```javascript
// Línea 13
window.telasAgregadas = [];
```

### Ciclo Vida
1. **Usuario hace click "Agregar Tela"**
   - Abre formulario modal inline
   - Campos: Color, Tela, Referencia, Imágenes (hasta 3)

2. **Validación** (línea ~95)
   ```javascript
   - Color: REQUERIDO
   - Tela: REQUERIDO  
   - Referencia: OPCIONAL
   - Imágenes: 0-3 archivos
   ```

3. **Agregar a Global** (línea 223)
   ```javascript
   window.telasAgregadas.push({ 
       color, tela, referencia, imagenes: []
   });
   ```

4. **Renderizar Tabla** (línea 291)
   - Muestra cada tela en fila
   - Botones: Ver imágenes, Editar, Eliminar

5. **Envío al Backend** (línea 606)
   ```javascript
   const telasFinales = window.telasAgregadas;
   // FormData: colores_telas = JSON.stringify(telasFinales)
   ```

### Características
- ✅ Múltiples telas
- ✅ Validación visual (campos rojo si error)
- ✅ Preview de imágenes en galería modal
- ✅ Editable: color, tela, referencia
- ✅ Limpiable: botón eliminar

---

## 🔍 FLUJO 2: EDITAR PRENDA (modal-novedad-edicion.js + tela-processor.js)

### Inicio
```javascript
// Cuando abre modal de edición de prenda existente
// modal-novedad-edicion.js línea 92
async mostrarModalYActualizar(pedidoId, prendaData, prendaIndex) {
    this.pedidoId = pedidoId;
    this.prendaData = prendaData;
```

### Ciclo Vida

1. **Cargar Telas Existentes** (tela-processor.js línea 64)
   ```javascript
   static cargarTelaDesdeBaseDatos(prenda) {
       // Desde BD: tela, color, ref, imagenes_tela
       const telaObj = {
           color: prenda.color,
           tela: prenda.tela,
           referencia: prenda.ref,
           imagenes: prenda.imagenes_tela  // Array de imágenes guardadas
       };
       return { telaObj, procesada: true };
   }
   ```

2. **Agregar al Storage Global** (tela-processor.js línea 102)
   ```javascript
   static agregarTelaAlStorage(telaObj) {
       if (!window.telasAgregadas) {
           window.telasAgregadas = [];
       }
       window.telasAgregadas.length = 0;  // LIMPIA anteriores
       window.telasAgregadas.push(telaObj);
   }
   ```

3. **Usuario Edita en Modal**
   - Campos: Color, Tela, Referencia (igual a Flujo 1)
   - Puede agregar imágenes nuevas
   - Conserva imágenes existentes

4. **Captura en Envío** (modal-novedad-edicion.js línea 181)
   ```javascript
   if (window.telasAgregadas && window.telasAgregadas.length > 0) {
       const telasArray = window.telasAgregadas.map((tela, idx) => {
           const obj = { nombre: tela.nombre, color: tela.color };
           
           // SI tiene ID = existente (MERGE)
           if (tela.id) {
               obj.id = tela.id;
           }
           
           // Procesa imágenes
           if (tela.imagenes && tela.imagenes.length > 0) {
               tela.imagenes.forEach((img, imgIdx) => {
                   if (img instanceof File) {
                       // Nueva imagen - append a FormData
                       formData.append(`telas[${idx}][imagenes][${imgIdx}]`, img);
                   } else if (img.urlDesdeDB || img.url) {
                       // Existente - guardar URL
                       obj.imagenes.push({ url: img.url });
                   }
               });
           }
           return obj;
       });
       formData.append('colores_telas', JSON.stringify(telasArray));
   }
   ```

5. **Backend Procesa (MERGE Pattern)**
   - Si `id` presente: UPDATE tela existente
   - Si no `id`: CREATE nueva tela
   - Las imágenes se guardan separadamente

---

## ⚠️ PROBLEMAS IDENTIFICADOS

### Problema 1: Reutilización de `window.telasAgregadas`
```javascript
// AMBOS flujos usan el MISMO array global
window.telasAgregadas = [];

// RIESGO:
- Cuando abre modal de edición, carga telas existentes
- Si usuario luego crea OTRA prenda sin cerrar modal, se mezclan
- Las telas de prenda 1 pueden contaminarse con prenda 2
```

### Problema 2: Limpieza Inconsistente
```javascript
// tela-processor.js línea 101
window.telasAgregadas.length = 0;  // Limpia antes de agregar

// PERO gestion-telas.js línea 614
window.telasAgregadas = [];  // Reinicia array

// RIESGO:
- En algunos casos limpia, en otros no
- Referencias pueden quedar obsoletas
```

### Problema 3: Campos Faltantes en Edición
```javascript
// gestion-telas.js CREA:
{
    color: "ROJO",
    tela: "DRILL",
    referencia: "REF123",
    imagenes: [File, File]  // Objects File
}

// modal-novedad-edicion.js ENVÍA:
{
    nombre: tela.nombre || '',  // ⚠️ No existe este campo!
    color: tela.color || '',
    id: tela.id  // ⚠️ No existe para nuevas telas
}

// RIESGO:
- Campo "nombre" nunca se llena
- Las nuevas telas no se distinguen de existentes
```

### Problema 4: Estructura Inconsistente de Imágenes
```javascript
// Flujo 1 (Crear):
imagenes: [File, File, File]  // File objects directamente

// Flujo 2 (Editar - de BD):
imagenes: [
    { url: "/storage/...", nombre: "..." },  // Objetos con propiedades
    { urlDesdeDB: "/storage/..." }
]

// RIESGO:
- El código asume ambas estructuras (línea 194 de modal)
- Inconsistencia causa bugs en procesamiento
```

---

## 📌 RECOMENDACIONES

### Opción A: SEPARAR Estados (Recomendado)
```javascript
// Para Creación:
window.telasCreacion = [];

// Para Edición:
window.telasEdicion = [];

// Ventajas:
✅ No hay contaminación
✅ Código más claro
✅ Fácil de debuggear
✅ Cada flujo independiente
```

### Opción B: UNIFICAR Estructuras
```javascript
// Definir estructura única SIEMPRE:
{
    id: null,  // null si es nueva, número si existe
    nombre: "ROJO",  // Nuevo: usar este siempre
    color: "ROJO",
    tela: "DRILL", 
    referencia: "REF123",
    imagenes: [
        {
            file: File,  // Si es nueva
            url: "/storage/...",  // Si existe
            urlDesdeDB: "/storage/...",  // Si desde BD
            estado: "NUEVA" | "EXISTENTE"
        }
    ]
}

// Ventajas:
✅ Un solo array: window.telasAgregadas
✅ Backend sabe qué hacer (id presente = update)
✅ Menos conversiones
```

### Opción C: CREAR Clase Unificada (Mejor)
```javascript
class GestorTelas {
    constructor(tipo = 'crear') {  // 'crear' o 'editar'
        this.tipo = tipo;
        this.telas = [];
        this.cambios = {
            nuevas: [],
            modificadas: [],
            eliminadas: []
        };
    }

    agregarTela(datos) { ... }
    editarTela(id, datos) { ... }
    eliminarTela(id) { ... }
    obtenerParaEnvio() { ... }  // Retorna lo que backend espera
}

// Uso:
// Creación
const gestorCrear = new GestorTelas('crear');

// Edición
const gestorEditar = new GestorTelas('editar');
gestorEditar.cargarExistentes(prendaData);
```

---

## 🎯 PRÓXIMOS PASOS

1. **Definir cuál es la estructura correcta**
   - Backend espera qué campos?
   - Cómo se diferencia tela nueva vs existente?

2. **Unificar o Separar**
   - ¿Separamos window.telasCreacion vs window.telasEdicion?
   - ¿Unificamos estructura siempre?

3. **Revisar Backend**
   - ActualizarPrendaCompletaUseCase.php
   - MergeRelationshipStrategy.php
   - ¿Cómo procesan `colores_telas` en creación vs edición?

4. **Revisar Limpieza**
   - modal-cleanup.js línea 81
   - Cuando cierra modal, limpia correctamente?

---

## 📂 Archivos Involucrados

| Flujo | Archivo | Líneas | Propósito |
|-------|---------|--------|-----------|
| **Crear** | `gestion-telas.js` | 1-783 | Captura telas nuevas |
| **Crear** | `prenda-form-collector.js` | 146 | Incluye telasAgregadas en datos |
| **Editar** | `modal-novedad-edicion.js` | 181-213 | Envía telas en PATCH |
| **Editar** | `tela-processor.js` | 1-211 | Procesa telas de BD |
| **Limpiar** | `modal-cleanup.js` | 81-82 | Limpia telas al cerrar |
| **Backend** | `ActualizarPrendaCompletaUseCase.php` | ? | Procesa colores_telas |
| **Backend** | `MergeRelationshipStrategy.php` | ? | UPDATE/CREATE telas |

