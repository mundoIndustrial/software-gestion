# 🏗️ Arquitectura ModernTable SOLID

## Diagrama de Dependencias

```
┌─────────────────────────────────────────────────────────────┐
│                   MODERN TABLE v2                            │
│               (Orchestrator - 300 líneas)                    │
│  • Coordina todos los módulos                               │
│  • Maneja eventos principales                               │
│  • Expone API pública                                       │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┼─────────────┐
        │             │             │
        ▼             ▼             ▼
    ┌────────────┐ ┌───────────┐ ┌──────────────┐
    │  Storage   │ │  Render   │ │   Style      │
    │  Manager   │ │  Manager  │ │   Manager    │
    │ (60 líneas)│ │ (150 lín) │ │  (120 lín)   │
    └────────────┘ └───────────┘ └──────────────┘
        │             │             │
        ▼             ▼             ▼
    ┌────────────┐ ┌───────────┐ ┌──────────────┐
    │  Filter    │ │  Drag     │ │   Column     │
    │  Manager   │ │  Manager  │ │   Manager    │
    │ (200 lín)  │ │ (130 lín) │ │   (70 lín)   │
    └────────────┘ └───────────┘ └──────────────┘
        │             │             │
        ▼             ▼             ▼
    ┌────────────┐ ┌───────────┐ ┌──────────────┐
    │ Dropdown   │ │Notification│ │ Pagination  │
    │ Manager    │ │  Manager   │ │  Manager    │
    │ (80 lín)   │ │ (70 lín)   │ │  (100 lín)  │
    └────────────┘ └───────────┘ └──────────────┘
                        │
                        ▼
                   ┌──────────────┐
                   │  Search      │
                   │  Manager     │
                   │  (50 lín)    │
                   └──────────────┘
```

---

## Flujo de Inicialización

```
1. DOM Ready
   └─ Detectar tabla #tablaOrdenes
      └─ Crear instancia ModernTableV2
         │
         ├─ StorageManager.loadSettings()
         │  └─ Cargar configuración guardada
         │
         ├─ ColumnManager.extractTableHeaders()
         │  └─ Leer headers de <thead>
         │
         ├─ StyleManager.applySavedSettings()
         │  └─ Aplicar estilos guardados
         │
         ├─ StyleManager.createResizers()
         │  └─ Crear manejadores de columnas
         │
         ├─ ColumnManager.setupColumnResizing()
         │  └─ Listeners para redimensionamiento
         │
         ├─ FilterManager.markActiveFilters()
         │  └─ Marcar filtros en URL
         │
         ├─ DropdownManager.initializeStatusDropdowns()
         │  └─ Preparar dropdowns de estado
         │
         ├─ DropdownManager.initializeAreaDropdowns()
         │  └─ Preparar dropdowns de área
         │
         └─ setupEventListeners()
            ├─ Búsqueda en tiempo real
            ├─ Cambios de dropdown
            ├─ Clics en filtros
            ├─ Doble clic/tap para editar
            └─ Eventos modales
```

---

## Ciclo de Vida - Búsqueda en Tiempo Real

```
Usuario escribe en input #buscarOrden
   │
   ├─ Debounce 300ms
   │
   ├─ SearchManager.performAjaxSearch(term)
   │  ├─ Cancelar búsqueda anterior (AbortController)
   │  ├─ Construir URL con parámetros
   │  └─ Fetch AJAX con signal
   │
   ├─ Respuesta JSON con:
   │  ├─ orders (array de pedidos)
   │  ├─ totalDiasCalculados (object)
   │  └─ pagination (info de paginación)
   │
   ├─ ModernTableV2.updateTableWithData()
   │  │
   │  ├─ TableRenderer.updateTableWithData()
   │  │  ├─ Limpiar tbody
   │  │  ├─ Iterar órdenes
   │  │  └─ Crear filas con acciones
   │  │
   │  ├─ StyleManager.setupCellTextWrapping()
   │  │  └─ Aplicar wrapping de texto
   │  │
   │  ├─ DropdownManager.initializeStatusDropdowns()
   │  │  └─ Re-inicializar listeners
   │  │
   │  └─ DropdownManager.initializeAreaDropdowns()
   │     └─ Re-inicializar listeners
   │
   └─ Tabla actualizada en pantalla
```

---

## Ciclo de Vida - Aplicar Filtro

```
Usuario hace clic en filtro de columna
   │
   ├─ FilterManager.openFilterModal(columnIndex, columnName)
   │  │
   │  ├─ Mostrar overlay + modal con spinner
   │  │
   │  ├─ Fetch valores únicos del servidor
   │  │  GET /registros?get_unique_values=1&column=nombre
   │  │
   │  └─ FilterManager.generateFilterList()
   │     ├─ Renderizar checkboxes
   │     ├─ Marcar valores ya filtrados
   │     └─ Agregar event listeners
   │
   ├─ Usuario selecciona valores y hace clic en "Aplicar"
   │  │
   │  └─ FilterManager.applyServerSideColumnFilter()
   │     │
   │     ├─ Recopilar checkboxes seleccionados
   │     │
   │     ├─ FilterManager.applyServerSideFilter()
   │     │  ├─ Construir URL con filter_columnName
   │     │  └─ Agregar a URL sin recargar
   │     │
   │     └─ FilterManager.loadTableWithAjax()
   │        ├─ Fetch HTML de tabla filtrada
   │        ├─ Reemplazar tbody
   │        ├─ Reemplazar paginación
   │        └─ Reinicializar dropdowns
   │
   └─ Tabla filtrada en pantalla
```

---

## Ciclo de Vida - Editar Celda

```
Usuario hace doble clic en celda
   │
   ├─ ModernTableV2.setupEventListeners() captura evento
   │  │
   │  ├─ Detectar .cell-content
   │  ├─ Obtener orderId y column
   │  │
   │  └─ ModernTableV2.openCellModal()
   │
   ├─ Modal abierto con textarea
   │  ├─ Llenar con contenido actual
   │  ├─ Focus en textarea
   │  └─ Mostrar hint (Enter/Ctrl+Enter para guardar)
   │
   ├─ Usuario edita y presiona Enter (o Ctrl+Enter si multiline)
   │  │
   │  └─ ModernTableV2.saveCellEdit()
   │
   ├─ Fetch POST al servidor
   │  POST /registros/orderId
   │  Body: { column: newValue }
   │
   ├─ Respuesta { success: true }
   │  │
   │  ├─ NotificationManager.show('✅ Cambio guardado', 'success')
   │  │
   │  ├─ Cerrar modal
   │  │
   │  └─ Tabla auto-actualiza si hay listeners WebSocket
   │
   └─ Cambio visible en pantalla
```

---

## Ciclo de Vida - Drag & Drop de Tabla

```
Usuario hace clic y arrastra tabla
   │
   ├─ ModernTableV2.enableTableDragging()
   │  ├─ DragManager.enableTableDragging(storage)
   │  │
   │  ├─ Agregar mousedown listener al wrapper
   │  │  ├─ Guardar posición inicial
   │  │  ├─ Cambiar cursor a 'move'
   │  │  └─ Agregar mousemove/mouseup listeners
   │  │
   │  └─ Mientras arrastra
   │     ├─ Calcular delta (distancia movida)
   │     ├─ Actualizar left/top del wrapper
   │     └─ Prevenir arrastra sobre sidebar
   │
   ├─ Al soltar (mouseup)
   │  ├─ Guardar nueva posición en storage
   │  │
   │  └─ StorageManager.setObject('tablePosition', {x, y})
   │
   └─ Próxima carga recupera posición guardada
```

---

## Interacción entre Módulos

```
ModernTableV2 (Orchestrator)
       ↓
       ├─→ StorageManager (Obtener/Guardar config)
       │
       ├─→ TableRenderer (Renderizar tabla)
       │
       ├─→ StyleManager (Aplicar CSS)
       │
       ├─→ FilterManager (Gestionar filtros)
       │    └─→ SearchManager (Búsqueda)
       │
       ├─→ ColumnManager (Redimensionar columnas)
       │
       ├─→ DropdownManager (Estado/Área)
       │
       ├─→ DragManager (Mover tabla/header)
       │
       ├─→ PaginationManager (Actualizar paginación)
       │
       └─→ NotificationManager (Mostrar mensajes)
```

---

## Patrones de Diseño Utilizados

### IIFE (Immediately Invoked Function Expression)
Encapsulan cada módulo para evitar contaminación global.

```javascript
const ModuleManager = (() => {
    // Variables privadas
    const private = {};
    
    return {
        // API pública
        method1: () => {},
        method2: () => {}
    };
})();
```

### Facade Pattern
ModernTableV2 actúa como fachada para los 10 módulos.

```javascript
class ModernTableV2 {
    openCellModal() {
        // Delega a varios módulos sin que el cliente lo sepa
        this.setupUI();           // StyleManager
        this.createCell();        // TableRenderer
        // ...
    }
}
```

### Dependency Injection
StorageManager se pasa como parámetro a otros módulos.

```javascript
DragManager.enableTableDragging(storage);
StyleManager.applySavedSettings(storage);
```

---

## Flujo Completo: Usuario Busca → Filtra → Edita

```
1. Usuario escribe en búsqueda
   └─ SearchManager.performAjaxSearch()
      └─ ModernTableV2.updateTableWithData()

2. Usuario abre filtro y selecciona valores
   └─ FilterManager.applyServerSideColumnFilter()
      └─ FilterManager.loadTableWithAjax()
         └─ ModernTableV2.updateTableWithData()

3. Usuario hace doble clic en celda
   └─ ModernTableV2.openCellModal()

4. Usuario edita y presiona Enter
   └─ ModernTableV2.saveCellEdit()
      └─ NotificationManager.show()

5. Cambio guardado
   └─ Tabla se actualiza automáticamente
```

---

## Responsabilidades Únicas (Single Responsibility)

| Módulo | Responsabilidad | Métodos |
|--------|-----------------|---------|
| StorageManager | Persistencia en localStorage | get, set, getObject, setObject, loadSettings, saveSettings |
| TableRenderer | Renderizar tabla y celdas | createCell, createVirtualRow, updateTableWithData |
| StyleManager | Aplicar y gestionar estilos | applySavedSettings, createResizers, setupCellTextWrapping |
| FilterManager | Gestionar filtros de tabla | openFilterModal, applyServerSideColumnFilter, clearAllFilters |
| DragManager | Drag & drop tabla/header | enableTableDragging, enableHeaderDragging |
| ColumnManager | Redimensionamiento columnas | setupColumnResizing, extractTableHeaders |
| DropdownManager | Dropdowns estado/área | initializeStatusDropdowns, updateOrderStatus |
| NotificationManager | Notificaciones modernas | show |
| PaginationManager | Gestionar paginación | updateInfo, updateControls |
| SearchManager | Búsqueda AJAX en tiempo real | performAjaxSearch, cancelSearch |
| ModernTableV2 | Orquestar módulos | init, setupEventListeners, openCellModal |

---

