# 🏛️ ANÁLISIS SOLID y REFACTORING - tracking-modal-handler.js

**Fecha:** Marzo 24, 2026  
**Archivo:** `tracking-modal-handler.js` (2,055 líneas)  
**Objetivo:** Mejora arquitectónica basada en principios SOLID sin sacrificar DDD

---

## 📊 Estado Actual

### Fortalezas Existentes
-  **DDD Layer Separation:** Domain/Infrastructure/Application claramente delimitados
-  **9 Helper Functions** extraídas y reutilizables
-  **Centralización API:** OrderApiService con 8 métodos
-  **Modal utilities:** ModalUtils consolidado

### Debilidades Identificadas (SOLID violations)

---

## 🔴 VIOLACIONES SOLID

### 1. **Single Responsibility Principle (SRP) - VIOLADO**

#### Problema 1.1: `renderPrendaTrackingTimeline()` (línea ~963)
**Responsabilidades múltiples:**
- ✗ Lógica de renderización
- ✗ Gestión de validación de datos
- ✗ Log de depuración
- ✗ Orquestación de sub-renders

```javascript
// ACTUAL - 3+ responsabilidades
function renderPrendaTrackingTimeline(prenda) {
  const container = document.getElementById('trackingTimelineContainer');
  console.log('[renderPrendaTrackingTimeline] Renderizando timeline');
  container.innerHTML = '';
  renderSeguimientosPorArea(prenda, container);  // Sub-render
  if (!prenda.seguimientos_por_area || ...) {    // Validación
    renderNoSeguimiento(container);
  }
}
```

**Propuesta:**
- `PrendaTrackingRenderer` — solo renderización
- `PrendaTrackingValidator` — validación de datos
- `TrackingOrchestrator` — orquestación

---

#### Problema 1.2: `executeDeleteProcess()` (línea ~1256)
**Responsabilidades múltiples (5+):**
- ✗ Validación de estado
- ✗ UI de loading (mostrar/ocultar)
- ✗ Llamada API
- ✗ Reload de datos
- ✗ Actualización de vista
- ✗ Gestión de errores

```javascript
// ACTUAL - 6 responsabilidades
async function executeDeleteProcess() {
  if (!orderState.getProcessToDelete()) return;
  setButtonLoading(..., true);           // 1. UI Loading
  try {
    await OrderApiService.deleteProceso(procesoId);  // 2. API
    closeConfirmDeleteModal();           // 3. Modal
    await loadPrendasWithTracking(...);  // 4. Reload
    renderPrendaTrackingTimeline(...);   // 5. View update
    showSuccess(...);                    // 6. UX feedback
  } catch (error) { ... }
  finally { setButtonLoading(..., false); }
}
```

**Propuesta:**
- `ProcessDeleteService` — orquestación de eliminación
- `ProcessDeleteCommand` — encapsula parámetros y lógica
- `DataReloadService` — reload de datos post-cambios
- `UIFeedbackService` — success/error messages

---

#### Problema 1.3: `handleAgregarProceso()` (línea ~1900)
**Líneas: ~140** con responsabilidades:
- ✗ Validación de formulario
- ✗ Transformación de datos
- ✗ Llamada API
- ✗ Gestión de estado local
- ✗ Manejo de errores
- ✗ Actualización de UI

**Propuesta:**
- `ProcessFormValidator` — validación
- `ProcessDataTransformer` — transformación
- `ProcessCreateService` — orquestación

---

#### Problema 1.4: `renderSeguimientosPorArea()` (línea ~987)
**Responsabilidades:**
- ✗ Render de sección de activación
- ✗ Render de sección de áreas
- ✗ Cálculos de fechas
- ✗ Lógica de inyección de Insumos virtual

```javascript
// Current: 4 responsabilidades
function renderSeguimientosPorArea(prenda, container) {
  // 1. Crear sección de activación
  const activationSection = ...;
  // 2. Calcular fechas y tiempos
  let tiempoTranscurridoText = ...;
  // 3. Inyectar área virtual
  const mergedAreas = { ...seguimientosPorArea };
  if (!hasInsumos && reciboCreatedAt) { /* inyectar */ }
  // 4. Render de áreas ordenadas
  orderedEntries.forEach(...);
}
```

**Propuesta:**
- `ActivationSectionRenderer` — sección activación
- `AreaSectionRenderer` — sección áreas
- `VirtualAreaInjector` — inyección de Insumos
- `AreaOrderingStrategy` — ordenamiento de áreas

---

### 2. **Open/Closed Principle (OCP) - VIOLADO**

#### Problema 2.1: Process Handlers Hardcoded
**Actual:**
```javascript
window.handleCrearProcesoDesdeArea = function() { ... }
window.handleEliminarProceso = async function() { ... }
window.handleEditarProceso = function() { ... }
window.handleActualizarProceso = async function() { ... }
```

**Problema:** Para agregar nuevos tipos de handlers (ej: `handleDuplicarProceso`, `handleBulkActions`), hay que modificar el handler principal.

**Propuesta: Strategy Pattern**
```javascript
// Cerrado para modificación, abierto para extensión
class ProcessHandlerStrategy {
  execute(params) { throw new Error('Implement execute()'); }
}

class CreateProcessStrategy extends ProcessHandlerStrategy {
  execute(areaName, event, encargado) { ... }
}

class DeleteProcessStrategy extends ProcessHandlerStrategy {
  execute(procesoId, areaName, event) { ... }
}

class EditProcessStrategy extends ProcessHandlerStrategy {
  execute(procesoId, areaName, data, event) { ... }
}

// Registry
const processHandlers = {
  create: new CreateProcessStrategy(),
  delete: new DeleteProcessStrategy(),
  edit: new EditProcessStrategy(),
};

// Extensión sin modificación:
// processHandlers.duplicate = new DuplicateProcessStrategy();
```

---

#### Problema 2.2: Renderers Acoplados a Estructura HTML
**Actual:** Funciones como `createAreaCard()`, `createPrendasTable()` generan HTML hardcodeado.

**Propuesta: Template Pattern + Adapter**
```javascript
// Interfaz abstracta
class AreaRenderer {
  render(area, data, readonly) { throw new Error(); }
}

// Implementaciones específicas
class AreaCardRenderer extends AreaRenderer {
  render(area, data, readonly) { /* generar tarjeta */ }
}

class AreaTableRowRenderer extends AreaRenderer {
  render(area, data, readonly) { /* generar fila tabla */ }
}

// Fácil agregar nuevas vistas sin modificar existentes
```

---

### 3. **Interface Segregation Principle (ISP) - VIOLADO**

#### Problema 3.1: `getProcessFormElements()` retorna "bag of properties"
**Actual:**
```javascript
function getProcessFormElements() {
  return {
    area: document.getElementById('procesoArea'),
    estado: document.getElementById('procesoEstado'),
    fechaInicio: document.getElementById('procesoFechaInicio'),
    observaciones: document.getElementById('procesoObservaciones'),
    inputEncargado: document.getElementById('procesoEncargado'),
    selectEncargado: document.getElementById('procesoEncargadoSelect')
  };
}
```

**Problema:** Funciones que solo necesitan `area` deben recibir TODO el objeto.

**Propuesta: Segregar interfaces**
```javascript
// Interfaces específicas
interface AreaElement {
  value: string;
  dispatchEvent(event: Event): void;
}

interface EstadoElement {
  value: string;
}

interface EncargadoElements {
  input?: HTMLInputElement;
  select?: HTMLSelectElement;
}

// Funciones reciben solo lo que necesitan
function setAreaValue(areaElement: AreaElement, value: string) {
  areaElement.value = value;
  areaElement.dispatchEvent(new Event('change'));
}

function setEncargado(encargadoElements: EncargadoElements, value: string) {
  // solo responsable de encargado
}
```

---

#### Problema 3.2: `collectProcessFormData()` asume estructura fija
**Actual:**
```javascript
function collectProcessFormData(elements, encargado) {
  return {
    area: elements.area.value,
    estado: elements.estado ? elements.estado.value : 'Pendiente',
    fecha_inicio: fechaInicio || null,
    encargado: encargado,
    observaciones: observaciones
  };
}
```

**Propuesta: Builder Pattern**
```javascript
class ProcessDataBuilder {
  constructor(elements) {
    this.elements = elements;
  }
  
  withArea(value) {
    this.area = value;
    return this;
  }
  
  withEstado(value) {
    this.estado = value ?? 'Pendiente';
    return this;
  }
  
  withEncargado(value) {
    this.encargado = value;
    return this;
  }
  
  build() {
    return {
      area: this.area,
      estado: this.estado,
      encargado: this.encargado,
      // ... solo lo que se configuró
    };
  }
}

// Uso flexible
const data = new ProcessDataBuilder(elements)
  .withArea('COSTURA')
  .withEncargado('Juan')
  .build();
```

---

### 4. **Dependency Inversion Principle (DIP) - VIOLADO**

#### Problema 4.1: Funciones dependen directamente de DOM
**Actual:**
```javascript
function showPrendasSelector() {
  ModalUtils.open('trackingPrendasSelectorOverlay');  // ← Hardcoded modal ID
}

function getProcessFormElements() {
  return {
    area: document.getElementById('procesoArea'),           // ← Direct DOM access
    estado: document.getElementById('procesoEstado'),       // ← Tightly coupled
    // ...
  };
}
```

**Problema:** Si los IDs del HTML cambian, todo se rompe.

**Propuesta: Abstraction Layer (Adapter Pattern)**
```javascript
// Abstracción de DOM access
class DOMQueryService {
  constructor(idMap) {
    this.idMap = idMap;
  }
  
  getElementById(key) {
    const id = this.idMap[key];
    return document.getElementById(id);
  }
  
  querySelectorAll(key) {
    const selector = this.idMap[key];
    return document.querySelectorAll(selector);
  }
}

// Inyectar en servicios
const domService = new DOMQueryService({
  'procesoArea': 'procesoArea',
  'procesoEstado': 'procesoEstado',
  'trackingModal': 'orderTrackingModal',
  // ... configuración centralizada
});

// Uso: desacoplado de IDs hardcodeados
function getFormArea(domService) {
  return domService.getElementById('procesoArea');
}

// Si cambias ID en HTML: solo cambia la configuración, no el código
```

---

#### Problema 4.2: Funciones dependen de OrderApiService concreto
**Actual:**
```javascript
async function executeDeleteProcess() {
  const result = await OrderApiService.deleteProceso(procesoId);
  // Tightly coupled a OrderApiService específico
}
```

**Propuesta: Inyección de dependencias**
```javascript
// Interfaz (contrato)
interface ProcessRepository {
  delete(procesoId: string): Promise<Result>;
  update(procesoId: string, data: any): Promise<Result>;
}

// Implementación concreta
class ProcessApiRepository implements ProcessRepository {
  async delete(procesoId) {
    return await OrderApiService.deleteProceso(procesoId);
  }
}

// Servicios reciben la abstracción
class ProcessDeleteService {
  constructor(repository: ProcessRepository) {
    this.repository = repository;
  }
  
  async execute(procesoId) {
    // Usa repository, no OrderApiService directamente
    return await this.repository.delete(procesoId);
  }
}

// Fácil testear e intercambiar implementaciones
const mockRepository = {
  delete: () => Promise.resolve({ success: true })
};
const service = new ProcessDeleteService(mockRepository);
```

---

### 5. **Liskov Substitution Principle (LSP) - VIOLADO**

#### Problema 5.1: Handlers similares no son intercambiables
**Actual:**
```javascript
window.handleCrearProcesoDesdeArea(areaName, event, encargadoPrefill)
window.handleEliminarProceso(procesoId, areaName, event)
window.handleEditarProceso(procesoId, areaName, processData, event)
window.handleActualizarProceso(procesoId)  // Diferentes parámetros!
```

**Problema:** No puedes intercambiar handlers sin conocer sus firmas específicas.

**Propuesta: Contrato uniforme**
```javascript
// Contrato único para todos los handlers
interface ProcessHandler {
  canHandle(action: string): boolean;
  execute(context: ProcessContext): Promise<void>;
}

interface ProcessContext {
  action: 'create' | 'edit' | 'delete' | 'update';
  procesoId?: string;
  areaName?: string;
  encargado?: string;
  processData?: object;
  event?: Event;
}

// Implementaciones intercambiables
class ProcessCreateHandler implements ProcessHandler {
  canHandle(action) { return action === 'create'; }
  async execute(context) { /* crear */ }
}

class ProcessDeleteHandler implements ProcessHandler {
  canHandle(action) { return action === 'delete'; }
  async execute(context) { /* eliminar */ }
}

// Router/Dispatcher
class ProcessHandlerDispatcher {
  constructor(handlers) {
    this.handlers = handlers;
  }
  
  async dispatch(context) {
    const handler = this.handlers.find(h => h.canHandle(context.action));
    if (!handler) throw new Error(`No handler for ${context.action}`);
    return await handler.execute(context);
  }
}

// Uso uniforme
const dispatcher = new ProcessHandlerDispatcher([
  new ProcessCreateHandler(),
  new ProcessDeleteHandler(),
  new ProcessEditHandler(),
  new ProcessUpdateHandler()
]);

// onclick="dispatcher.dispatch({action: 'create', areaName: 'COSTURA', ...})"
```

---

## 🚀 PLAN DE REFACTORING (9 FASES)

### **Fase 9: Service Layer Extraction** (SRP + DIP)
**Objetivo:** Extraer servicios de aplicación que no dependan directamente de DOM

**Servicios a crear:**
1. `ProcessService` — orquestación de procesos
2. `ProcessDeleteService` — lógica de eliminación
3. `ProcessFormValidationService` — validación
4. `FormStateManager` — gestión de estado del formulario
5. `DataReloadService` — recarga post-operación

**Beneficio:** Código reutilizable, testeable, independiente de UI

**Estimado:** Fase 9 (sub-fases 9a-9e)

---

### **Fase 10: Renderer Abstraction** (OCP)
**Objetivo:** Separar lógica de renderización en clases con interfaz uniforme

**Classes a crear:**
1. `PrendaTrackingRenderer` — renderiza timeline
2. `AreaCardRenderer` — tarjeta de área
3. `PrendasTableRenderer` — tabla de prendas
4. `BadgeRenderer` — badges genéricos

**Beneficio:** Fácil agregar nuevas vistas, extender sin modificar

**Estimado:** Fase 10 (sub-fases 10a-10b)

---

### **Fase 11: Dependency Injection** (DIP)
**Objetivo:** Crear contenedor de IoC para gestionar dependencias

**Componentes:**
1. `DIContainer` — registro y resolución de dependencias
2. `DOMQueryService` — abstracción de acceso a DOM
3. `ModalService` — abstracción de modales
4. `StorageService` — abstracción de persistencia

**Beneficio:** Desacoplamiento máximo, fácil testing, configuración centralizada

**Estimado:** Fase 11 (sub-fase 11a)

---

### **Fase 12: Handler Strategy Pattern** (OCP + LSP)
**Objetivo:** Implementar Strategy Pattern para handlers

**Clases a crear:**
1. `ProcessHandlerStrategy` (abstracción)
2. `CreateProcessStrategy`
3. `DeleteProcessStrategy`
4. `EditProcessStrategy`
5. `UpdateProcessStrategy`
6. `ProcessHandlerDispatcher` (router)

**Beneficio:** Handlers intercambiables, fácil agregar nuevos

**Estimado:** Fase 12 (sub-fase 12a-12b)

---

### **Fase 13: Form Builder Pattern** (ISP)
**Objetivo:** Implementar Builder para construcción de datos de formulario

**Clases a crear:**
1. `ProcessDataBuilder`
2. `FormValidationBuilder`
3. `ProcessContextBuilder`

**Beneficio:** Construcción flexible de objetos complejos

**Estimado:** Fase 13 (sub-fase 13a)

---

## 📐 ARQUITECTURA PROPUESTA (Post-Refactoring)

```
tracking-modal-handler.js (Presentation/Orchestration)
    │
    ├─── domain/
    │    ├── OrderState.js (Entity)
    │    ├── DateFormatter.js (Value Object)
    │    └── ProcessContext.js (NEW - Value Object)
    │
    ├─── infrastructure/
    │    ├── DateUtils.js
    │    ├── ModalUtils.js
    │    ├── DOMQueryService.js (NEW)
    │    ├── ModalService.js (NEW)
    │    └── StorageService.js (NEW)
    │
    ├─── application/
    │    ├── OrderApiService.js
    │    ├── ProcessService.js (NEW)
    │    ├── ProcessDeleteService.js (NEW)
    │    ├── ProcessFormValidationService.js (NEW)
    │    ├── DataReloadService.js (NEW)
    │    └── FormStateManager.js (NEW)
    │
    ├─── presentation/
    │    ├── renderers/ (NEW)
    │    │   ├── PrendaTrackingRenderer.js
    │    │   ├── AreaCardRenderer.js
    │    │   ├── PrendasTableRenderer.js
    │    │   └── BadgeRenderer.js
    │    │
    │    └── handlers/ (NEW)
    │        ├── strategies/ (NEW)
    │        │   ├── ProcessHandlerStrategy.js
    │        │   ├── CreateProcessStrategy.js
    │        │   ├── DeleteProcessStrategy.js
    │        │   ├── EditProcessStrategy.js
    │        │   └── UpdateProcessStrategy.js
    │        │
    │        └── ProcessHandlerDispatcher.js (NEW)
    │
    └─── bootstrap/
         ├── DIContainer.js (NEW)
         └── index.js (NEW - inicializa todo)
```

---

## 📝 COMPARATIVA: Antes vs Después SOLID

### Antes (Current)
```javascript
// 6 responsabilidades entrelazadas
async function executeDeleteProcess() {
  // 1. Validación
  if (!orderState.getProcessToDelete()) return;
  
  // 2. UI Loading
  setButtonLoading('deleteButtonContent', 'deleteButtonLoading', 'btnConfirmDelete', true);
  
  // 3. API Call
  const result = await OrderApiService.deleteProceso(procesoId);
  
  // 4. Modal Management
  closeConfirmDeleteModal();
  
  // 5. Data Reload
  await loadPrendasWithTracking(orderState.getOrderId());
  
  // 6. View Update
  renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
}

// Difícil de testear: depende de 6+ objetos globales
// Difícil de extender: lógica hardcodeada
// Difícil de mantener: cambios afectan múltiples áreas
```

### Después (SOLID)
```javascript
// Cada servicio tiene una responsabilidad
class ProcessDeleteService {
  constructor(processRepository, dataReloadService, uiFeedbackService) {
    this.processRepository = processRepository;        // DIP: inyectado
    this.dataReloadService = dataReloadService;
    this.uiFeedbackService = uiFeedbackService;
  }
  
  async execute(procesoId) {
    // 1. Eliminar via repository (abstracción)
    const result = await this.processRepository.delete(procesoId);
    
    // 2. Delegar recarga de datos (SRP)
    await this.dataReloadService.reloadAfterDelete();
    
    // 3. Delegar feedback de usuario (SRP)
    this.uiFeedbackService.showSuccess('Proceso eliminado');
    
    return result;
  }
}

// Fácil de testear: inyectar mocks
const mockRepository = { delete: () => Promise.resolve() };
const service = new ProcessDeleteService(mockRepository, {}, {});
await service.execute(123); //  Funciona sin DOM, OrderApiService, etc.

// Fácil de extender: agregar nuevos servicios
// Fácil de mantener: cada cosa en su lugar
```

---

## 🎯 IMPACTO ESPERADO

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Líneas por función** | 50-140 | 10-30 |
| **Responsabilidades** | 3-6 | 1 |
| **Acoplamiento** | Alto (DOM, API, State) | Bajo (interfaces) |
| **Testabilidad** | 20% | 90%+ |
| **Extensibilidad** | Modificación código | Agregar nuevas clases |
| **Mantenibilidad** | Difícil | Fácil |
| **Reutilización** | Nula | Alta |

---

## 🔄 PRÓXIMOS PASOS

1. **Aceptación:** ¿Deseas proceder con Phase 9 (Services)?
2. **Priorización:** ¿SRP primero o DIP primero?
3. **Parallelización:** ¿Fases simultáneas o secuenciales?

**Estimado Total:** 5-8 horas (150-200 líneas código nuevo + refactoring existente)

---

**Nota:** Este refactoring es incremental y compatible con DDD existente. Se construye sobre las capas ya establecidas (Domain/Infrastructure/Application) agregando estructura y patrones SOLID.
