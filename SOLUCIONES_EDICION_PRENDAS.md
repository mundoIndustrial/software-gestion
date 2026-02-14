# 🛠️ GUÍA DE SOLUCIONES - LÓGICA DE EDICIÓN DE PRENDAS

## 📌 PROBLEMA PRINCIPAL
**Error**: `TypeError: Cannot read properties of undefined (reading 'aplicarOrigenAutomaticoDesdeCotizacion')`

**Causa raíz**: 
```
En flujo de EDICIÓN de pedidos, cuando se llama a cargarItemEnModal()
  → PrendaEditor.cargarPrendaEnModal()
    → window.prendaEditorLegacy.aplicarOrigenAutomaticoDesdeCotizacion()
       window.prendaEditorLegacy es undefined
```

**¿Por qué ocurre?**
- En `crear-nuevo`: El HTML carga `prenda-editor-legacy.js` antes de usarlo 
- En `pedidos-editable`: El modal se abre dinámicamente, pero el script legacy puede no estar cargado 

---

##  SOLUCIÓN 1: VALIDACIÓN DEFENSIVA (Parche Rápido)

**Archivo**: `/public/js/modulos/crear-pedido/procesos/services/prenda-editor.js`
**Línea**: 87

### CAMBIO

```javascript
//  ANTES (Sin validación)
const prendaProcesada = window.prendaEditorLegacy.aplicarOrigenAutomaticoDesdeCotizacion(prenda);

//  DESPUÉS (Con validación)
if (!window.prendaEditorLegacy) {
    console.warn('[CARGAR-PRENDA]  Legacy no inicializado, usando método DDD');
    // Delegar al método DDD que no depende de legacy
    return this.cargarPrendaEnModalDDD(prenda.id || prenda.prenda_pedido_id, prendaIndex);
}

const prendaProcesada = window.prendaEditorLegacy.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
```

---

##  SOLUCIÓN 2: INICIALIZACIÓN GARANTIZADA (Recomendado)

**Crear archivo nuevo**: `/public/js/lazy-loaders/ensure-legacy-editor.js`

```javascript
/**
 * 🔒 GARANTIZAR que prendaEditorLegacy está disponible
 * Se ejecuta APENAS se carga, antes que cualquier otro script lo necesite
 */

// Si legacy no está disponible, crearlo
if (!window.prendaEditorLegacy) {
    console.log('[EnsureLegacyEditor]  Creando instancia fallback de PrendaEditorLegacy...');
    
    // Cargar el script si no existe
    if (!window.PrendaEditorLegacy) {
        const script = document.createElement('script');
        script.src = '/js/modulos/crear-pedido/procesos/services/prenda-editor-legacy.js?v=' + Date.now();
        script.onload = () => {
            console.log('[EnsureLegacyEditor] ✓ Script cargado, instancia ya disponible');
        };
        script.onerror = () => {
            console.error('[EnsureLegacyEditor] ✗ Error cargando prenda-editor-legacy.js');
        };
        document.head.appendChild(script);
    } else {
        // Script ya está cargado, crear instancia
        window.prendaEditorLegacy = new window.PrendaEditorLegacy();
        console.log('[EnsureLegacyEditor] ✓ Instancia creada desde clase disponible');
    }
}
```

**Incluir en**: `prenda-editor-loader-modular.js` (línea 44)
```javascript
const scriptsToLoad = [
    // ✨ AGREGADO: Garantizar legacy antes que nada
    '/js/lazy-loaders/ensure-legacy-editor.js',
    
    '/js/modulos/crear-pedido/procesos/services/item-api-service.js?v=' + Date.now(),
    // ... resto de scripts
```

---

##  SOLUCIÓN 3: MÉTODO UNIFICADO (Largo Plazo)

**Objetivo**: Eliminar dependencia de legacy en el flujo de edición

**Cambio en**: `/public/js/componentes/gestion-items-pedido.js`

### Detectar contexto y usar método apropiado

```javascript
// gestion-items-pedido.js - cargarItemEnModal()
async cargarItemEnModal(prendaData, prendaIndex) {
    console.log('[cargarItemEnModal]  Cargando item:', {
        tipo: prendaData.tipo,
        tieneId: !!prendaData.id
    });
    
    // Detectar si es creación o edición
    const esEdicion = !!prendaData.id || !!prendaData.prenda_pedido_id;
    const esLocal = !prendaData.id && !prendaData.prenda_pedido_id;
    
    try {
        if (esLocal) {
            // === CREACIÓN LOCAL ===
            // Usar método legacy (carga datos desde JSON local)
            console.log('[cargarItemEnModal]  Contexto: CREACIÓN LOCAL → Usar Legacy');
            
            if (!window.prendaEditorLegacy) {
                throw new Error('PrendaEditorLegacy no disponible para creación local');
            }
            
            window.prenda = prendaData;
            window.gestionItemsUI.prendaEditIndex = prendaIndex;
            window.prendaEditorLegacy.llenarCamposBasicos(prendaData);
            window.prendaEditorLegacy.cargarImagenes(prendaData);
            window.prendaEditorLegacy.cargarTelas(prendaData);
            window.prendaEditorLegacy.cargarVariaciones(prendaData);
            window.prendaEditorLegacy.cargarTallasYCantidades(prendaData);
            window.prendaEditorLegacy.cargarProcesos(prendaData);
            
        } else {
            // === EDICIÓN DESDE BD ===
            // Usar método DDD (datos ya vienen transformados del backend)
            console.log('[cargarItemEnModal]  Contexto: EDICIÓN → Usar DDD');
            
            // Los datos ya vienen mapeados desde prenda-editor-modal.js
            // Solo cargar en UI (no necesita prendaEditorLegacy)
            this._cargarDatosEnModal(prendaData, prendaIndex);
        }
        
    } catch (error) {
        console.error('[cargarItemEnModal]  Error:', error);
        this.notificationService?.error(`Error cargando ítem: ${error.message}`);
    }
}

// Nuevo método para cargar datos sin dependencia de legacy
_cargarDatosEnModal(prendaData, prendaIndex) {
    console.log('[_cargarDatosEnModal]  Cargando datos en modal...');
    
    // Llenar campos básicos (sin usar legacy)
    const nombreInput = document.getElementById('nueva-prenda-nombre');
    if (nombreInput) nombreInput.value = prendaData.nombre_prenda || prendaData.nombre || '';
    
    const descInput = document.getElementById('nueva-prenda-descripcion');
    if (descInput) descInput.value = prendaData.descripcion || '';
    
    const origenSelect = document.getElementById('nueva-prenda-origen-select');
    if (origenSelect) origenSelect.value = prendaData.origen || 'confeccion';
    
    // TODO: Cargar telas, tallas, procesos, etc...
    // (sin usar métodos de legacy)
    
    console.log('[_cargarDatosEnModal]  ✓ Datos cargados');
}
```

---

## 🔄 FLUJOS CORRECTOS DESPUÉS DE APLICAR SOLUCIONES

### FLUJO CREAR-NUEVO (con Solución 1 o 2)
```
Usuario → Click "Editar"
    ↓
prenda-editor-modal.js: abrirEditarPrendaEspecifica()
    ↓
cargarItemEnModal() → PrendaEditor.cargarPrendaEnModal()
    ↓
 window.prendaEditorLegacy DISPONIBLE (garantizado por Solución 2)
    ↓
window.prendaEditorLegacy.llenarCamposBasicos()
window.prendaEditorLegacy.cargarImagenes()
window.prendaEditorLegacy.cargarTelas()
    ↓
Modal se carga con datos locales
     ÉXITO
```

### FLUJO EDICIÓN PEDIDO (con Solución 1)
```
Usuario → Click "Editar"
    ↓
prenda-editor-modal.js: abrirEditarPrendaEspecifica()
    ├─ API: GET /api/pedidos/{id}/obtener-datos-completos
    └─ Retorna: prendas con formato DDD transformado
    ↓
cargarItemEnModal(prendaTransformada)
    ↓
PrendaEditor.cargarPrendaEnModal()
    ├─  window.prendaEditorLegacy unavailable
    ├─  Detecta con validación defensiva
    └─ Delega a: cargarPrendaEnModalDDD()
    ↓
Modal se carga desde API
     ÉXITO
```

### FLUJO IDEAL (con Solución 3)
```
Usuario → Click "Editar"
    ↓
prenda-editor-modal.js: abrirEditarPrendaEspecifica()
    ├─ SI CREAR: Datos locales
    └─ SI EDITAR: API call com resultado transformado
    ↓
cargarItemEnModal() DETECTA contexto
    ├─ CREAR → Usa legacy (datos locales)
    └─ EDITAR → Usa DDD (datos API)
    ↓
Modal se carga correctamente sin conflictos
     ÉXITO
```

---

## 📊 COMPARACIÓN DE SOLUCIONES

| Aspecto | Sol. 1 (Parche) | Sol. 2 (Init) | Sol. 3 (Unificada) |
|---------|---|---|---|
| **Tiempo impl.** | 5 min | 15 min | 1-2 horas |
| **Riesgo** | Bajo | Muy bajo | Medio |
| **Permanente** | No (parche) | Sí | Sí |
| **Dev tech debt** | Sigue igual | Mejora | Mejora mucho |
| **Escalabilidad** | Limitada | Buena | Excelente |
| **Testing** | Fácil | Fácil | Complejo |

**Recomendación**: Implementar **Solución 2** ahora (rápida y efectiva) + **Solución 3** como roadmap futuro.

---

## 🎯 IMPLEMENTACIÓN PASO A PASO

### Paso 1: Aplicar Solución 2 (15 minutos)

```bash
1. Crear: /public/js/lazy-loaders/ensure-legacy-editor.js
   Copiar código arriba

2. Editar: /public/js/lazy-loaders/prenda-editor-loader-modular.js
   Línea 44, agregar:
   '/js/lazy-loaders/ensure-legacy-editor.js',

3. Test en crear-nuevo
   ✓ Verificar que prendaEditorLegacy existe

4. Test en edición de pedido
   ✓ Verificar que no hay error TypeError
```

### Paso 2: Aplicar Solución 1 (5 minutos)

```bash
1. Editar: /public/js/modulos/crear-pedido/procesos/services/prenda-editor.js
   Línea 87, agregar validación:

   if (!window.prendaEditorLegacy) {
       return this.cargarPrendaEnModalDDD(...);
   }

2. Test completo
   ✓ Crear-nuevo
   ✓ Edición pedido
   ✓ Edición con cotización
```

### Paso 3: Testing Final

```javascript
// Console test - crear-nuevo
window.prendaEditorLegacy  // Debe existir
window.PrendaEditorLegacy   // Debe ser clase

// Console test - edición
abrirEditarPrendaEspecifica(0)  // Debe abrir sin errores
```

---

## 🚨 PROBLEMAS SECUNDARIOS A REVISAR

### 1. Inicialización de `window.gestionItemsUI`
**Ubicación**: `gestion-items-pedido.js:1100`

```javascript
// Problema: Si DOM aún no está listo, GestionItemsUI no se instancia
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.gestionItemsUI) {
            window.gestionItemsUI = new GestionItemsUI(...);
        }
    });
}

// Solución: Agregar fallback si se llama antes
async abrirEditarPrendaEspecifica() {
    if (!window.gestionItemsUI) {
        console.warn('[EDITAR-PRENDA]  GestionItemsUI no disponible, esperando...');
        await new Promise(resolve => {
            const checkInterval = setInterval(() => {
                if (window.gestionItemsUI) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
            setTimeout(() => clearInterval(checkInterval), 5000); // Timeout 5s
        });
    }
    // Continuar...
}
```

### 2. Detección de Formato de Datos
**Ubicación**: `prenda-editor-modal.js:350-380`

El sistema YA detecta formatos automáticamente, pero hay edge cases:

```javascript
//  OBRAS: Nuevo formato DDD
{generosConTallas: {DAMA: {L: 20}}}

//  OBRAS: Formato antiguo
{tallas_dama: [{talla: L, cantidad: 20}]}

//  PROBLEMA: Formato vacío
{generosConTallas: undefined, tallas_dama: undefined}

// SOLUCIÓN: Asegurar siempre estructura válida
const fallbackTallas = {DAMA: {}, CABALLERO: {}, UNISEX: {}};
const tallasPorGenero = prendaCompleta.generosConTallas || fallbackTallas;
```

### 3. URLs de Imágenes en Storage
**Ubicación**: `prenda-editor-modal.js:2800`

```javascript
//  CORRECTO: /storage/pedidos/19/prenda/imagen.webp
//  INCORRECTO: /pedidos/19/prenda/imagen.webp (sin /storage)

const agregarStorage = (url) => {
    if (!url || url.includes('/storage/')) return url;
    if (url.startsWith('/')) return '/storage' + url;
    return '/storage/' + url;
};
```

---

##  CHECKLIST DE IMPLEMENTACIÓN

- [ ] Crear `ensure-legacy-editor.js`
- [ ] Agregar a prenda-editor-loader-modular.js
- [ ] Validación defensiva en prenda-editor.js:87
- [ ] Test crear-nuevo (flujo completo)
- [ ] Test edición pedido (flujo completo)
- [ ] Test edición con cotización
- [ ] Verificar consola sin TypeErrors
- [ ] Performance check (sin scripts innecesarios)
- [ ] Documentar cambios en README
- [ ] Commit + PR review

---

## 📞 DEBUGGING

Si aún hay errores, ejecutar en consola:

```javascript
// Ver si legacy está disponible
console.log('prendaEditorLegacy:', window.prendaEditorLegacy);
console.log('PrendaEditorLegacy:', window.PrendaEditorLegacy);

// Ver si GestionItemsUI está disponible
console.log('gestionItemsUI:', window.gestionItemsUI);

// Ver datos de prenda cargados
console.log('prendaEnEdicion:', window.prendaEnEdicion);
console.log('prendaActual:', window.prendaActual);

// Check modal visible
const modal = document.getElementById('modal-agregar-prenda-nueva');
console.log('Modal visible:', modal?.offsetParent !== null);
```

---

## 📚 ARCHIVOS CLAVE

| Archivo | Propósito | Issue |
|---------|-----------|-------|
| `prenda-editor.js` | Gestor principal | `cargarPrendaEnModal()` línea 87  |
| `prenda-editor-legacy.js` | Métodos legacy (datos locales) | Inicialización global |
| `gestion-items-pedido.js` | Orquestador de carga | `cargarItemEnModal()` |
| `prenda-editor-modal.js` | Modal de edición | Detección de formato  |
| `prenda-editor-loader-modular.js` | Lazy loader | Orden de scripts |
| `ensure-legacy-editor.js` | **NUEVO** | Garantizar inicialización |

