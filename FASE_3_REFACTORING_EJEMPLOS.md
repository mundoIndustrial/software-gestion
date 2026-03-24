# FASE 3: Refactorización de Funciones Principales

## 📋 Resumen de Cambios por Función

### 1. `setupAddProcesoModalListeners()` → Usar `ModalEventBinder`

**ANTES (~30 líneas):**
```javascript
function setupAddProcesoModalListeners() {
  const openBtn = document.getElementById('btnOpenAddProcesoModal');
  if (openBtn) {
    openBtn.onclick = openAddProcesoModal;
  }
  
  const closeBtn = document.getElementById('closeAddProcesoModal');
  if (closeBtn) {
    closeBtn.onclick = closeAddProcesoModal;
  }
  
  // ... más búsquedas y bindings
}
```

**DESPUÉS (~8 líneas):**
```javascript
function setupAddProcesoModalListeners() {
  const binder = container.get('modalEventBinderFactory')('addProcesoModal');
  
  binder
    .bindCloseButtons({
      closeButtonId: 'closeAddProcesoModal',
      cancelButtonId: 'btnCancelAddProceso',
      overlaySelector: '#addProcesoOverlay',
      callback: closeAddProcesoModal
    })
    .bindActionButton({
      buttonId: 'btnConfirmAddProceso',
      callback: handleAgregarProceso
    });
}
```

---

### 2. `handleAgregarProceso()` → Usar `ProcessWorkflowService`

**ANTES (~120 líneas):**
```javascript
async function handleAgregarProceso() {
  // Validación manual
  if (!area) { showError(...); return; }
  if (!encargado) { showError(...); return; }
  if (!orderState.getCurrentPrenda()) { showError(...); return; }
  
  // Preparación manual de datos
  const procesoData = { ... };
  
  // Llamada a API
  const result = await OrderApiService.saveProceso(...);
  
  // Recarga manual
  await loadPrendasWithTracking(...);
  
  // Ui manual
  limpiarFormularioProceso();
  closeAddProcesoModal();
  renderPrendaTrackingTimeline(...);
  
  // Feedback manual
  showSuccess(...);
}
```

**DESPUÉS (~20 líneas):**
```javascript
async function handleAgregarProceso() {
  const buttonMgr = new ButtonLoadingManager('btnConfirmAddProceso', {
    contentId: 'addProcesoButtonContent',
    loadingId: 'addProcesoButtonLoading'
  });

  const workflowService = container.get('processWorkflowService');
  
  const result = await workflowService.executeCompleteWorkflow({
    onBeforeSave: () => buttonMgr.setLoading(true),
    onComplete: () => {
      formManager.clear();
      ModalUtils.close('addProcesoModal');
      renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
    },
    onError: () => buttonMgr.setLoading(false)
  });
}
```

---

### 3. `setupEncargadoDynamicSelector()` → Usar `AreasConfigService`

**ANTES (~25 líneas):**
```javascript
function setupEncargadoDynamicSelector() {
  const procesoArea = document.getElementById('procesoArea');
  procesoArea.addEventListener('change', async function(e) {
    const area = e.target.value.toLowerCase().trim();
    const areasConfig = orderState.getAreasConfig();
    const areasConSelectorDinamico = areasConfig?.areas_con_selector_dinamico || [...];
    
    if (areasConSelectorDinamico.some(a => area.includes(a))) {
      // convertir a select
    } else {
      // convertir a input
    }
  });
}
```

**DESPUÉS (~10 líneas):**
```javascript
function setupEncargadoDynamicSelector() {
  const formMgr = container.get('processFormManager');
  const areaMgr = container.get('areasConfigService');
  const procesoArea = document.getElementById('procesoArea');
  
  procesoArea.addEventListener('change', async function(e) {
    const area = e.target.value;
    const fieldType = areaMgr.getEncargadoFieldType(area);
    
    // Crear el campo apropiado (select o input)
    fieldType === 'select' 
      ? await createEncargadoSelect(area, formMgr)
      : createEncargadoInput(fieldType, formMgr);
  });
}
```

---

### 4. `handleEditarProceso()` + `handleActualizarProceso()` → Fusion

**ANTES (~40 líneas dispersas):**
```javascript
window.handleEditarProceso = function(procesoId, areaName, processData, event) {
  openAddProcesoModal();
  setProcessFormData(elements, processData);
  orderState.setEditingProcessId(procesoId);
  setTimeout(() => { setEncargadoValue(...); }, 150);
};

window.handleActualizarProceso = async function(procesoId) {
  // ... validar
  // ... preparar datos
  // ... llamar API
  // ... recargar
};
```

**DESPUÉS (~15 líneas):**
```javascript
window.handleEditarProceso = async function(procesoId, areaName, processData, event) {
  stopEventPropagation(event);
  const workflowService = container.get('processWorkflowService');
  
  await workflowService.prepareForEdit(processData);
  openAddProcesoModal();
};

// handleActualizarProceso usa el mismo workflow que handleAgregarProceso
// Solo cambia que updateProceso en lugar de saveProceso
```

---

### 5. `convertEncargadoToSelect()` + `convertEncargadoToInput()` → ProcessFormManager

**ANTES (~40 líneas):**
```javascript
async function convertEncargadoToSelect(area, container) {
  const existingInput = document.getElementById('procesoEncargado');
  const existingSelect = document.getElementById('procesoEncargadoSelect');
  if (existingInput) existingInput.remove();
  if (existingSelect) existingSelect.remove();
  
  const select = document.createElement('select');
  // ... populizar select
  container.appendChild(select);
}

function convertEncargadoToInput(container) {
  // ... mismo patrón
}
```

**DESPUÉS (~5 líneas reutilizables):**
```javascript
async function createEncargadoSelect(area, formMgr) {
  const container = formMgr.getElements().area.parentElement;
  const encargados = await orderApiService.loadEncargados(area);
  formMgr.createEncargadoField(container, 'select', 'procesoEncargado', encargados);
}

function createEncargadoInput(fieldType, formMgr) {
  const container = formMgr.getElements().area.parentElement;
  formMgr.createEncargadoField(container, 'input', 'procesoEncargado');
}
```

---

### 6. `setButtonLoading()` → ButtonLoadingManager (reutilizable)

**ANTES (~10 líneas dispersas x3 lugares):**
```javascript
function setButtonLoading(contentId, loadingId, buttonId, isLoading = true) {
  const content = document.getElementById(contentId);
  const loading = document.getElementById(loadingId);
  const button = document.getElementById(buttonId);
  
  if (isLoading) {
    content.style.display = 'none';
    loading.style.display = 'flex';
    button.disabled = true;
  } else { /* ... */ }
}

// Usado en 3 lugares:
// setButtonLoading('deleteButtonContent', 'deleteButtonLoading', 'btnConfirmDelete', true);
// setButtonLoading('addProcesoButtonContent', 'addProcesoButtonLoading', 'btnConfirmAddProceso', true);
```

**DESPUÉS (~1 línea cada vez):**
```javascript
// En executeDeleteProcess:
const deleteBtn = new ButtonLoadingManager('btnConfirmDelete', {
  contentId: 'deleteButtonContent',
  loadingId: 'deleteButtonLoading'
});
deleteBtn.setLoading(true);

// En handleAgregarProceso:
const addBtn = new ButtonLoadingManager('btnConfirmAddProceso', {
  contentId: 'addProcesoButtonContent',
  loadingId: 'addProcesoButtonLoading'
});
await addBtn.executeAsync(async () => {
  // ... código async
});
```

---

## 📊 Líneas de Código Eliminadas

| Función | Antes | Después | Reducción |
|---------|-------|---------|-----------|
| setupAddProcesoModalListeners | 35 | 10 | -71% |
| handleAgregarProceso | 120 | 20 | -83% |
| setupEncargadoDynamicSelector | 25 | 10 | -60% |
| convertEncargado (ambas) | 40 | 5 | -87% |
| handleEditarProceso | 20 | 10 | -50% |
| Duplicado setButtonLoading | 30 (total) | 3 reutilizables | -90% |
| **TOTAL** | **~330** | **~80** | **-75% ✅** |

---

## 🎯 Próximos Pasos (FASE 4)

1. Importar nuevas clases en tracking-modal-handler.js
2. Actualizar DIContainer para incluir nuevas dependencias
3. Refactorizar las 6 funciones según ejemplos arriba
4. Eliminar funciones deprecated
5. Testar en browser

---

## 💾 Archivos a Modificar en Fase 3

- `tracking-modal-handler.js` - RERFACTORIZAR
- `application/ContainerFactory.js` - Registrar todas las clases nuevas
- `application/index.js` - (YA ACTUALIZADO)

