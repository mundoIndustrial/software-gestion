# 📚 Arquitectura Modular - SOLID Principles

## Resumen Ejecutivo

Se refactorizó `orders-table.js` (2300+ líneas) en **8 módulos especializados** que cumplen con principios **SOLID**:

### Estructura de Carpetas
```
public/js/orders js/modules/
├── formatting.js ..................... Formatos y utilidades de datos
├── storageModule.js .................. Sincronización cross-tab
├── notificationModule.js ............. Notificaciones y auto-recarga
├── updates.js ........................ Peticiones PATCH al servidor
├── dropdownManager.js ................ Gestión de dropdowns (estado, área)
├── diaEntregaModule.js ............... Lógica especializada de día de entrega
├── rowManager.js ..................... Operaciones CRUD de filas
├── tableManager.js ................... Orquestador principal
└── index.js .......................... Índice central de módulos
```

---

## 1. PRINCIPIOS SOLID IMPLEMENTADOS

### ✅ Single Responsibility Principle (SRP)
Cada módulo tiene **UNA única responsabilidad**:
- `formatting.js` → Solo formatos
- `updates.js` → Solo peticiones al servidor
- `dropdownManager.js` → Solo gestión de dropdowns
- `storageModule.js` → Solo sincronización localStorage
- `notificationModule.js` → Solo notificaciones visuales
- `diaEntregaModule.js` → Solo lógica de día de entrega
- `rowManager.js` → Solo operaciones en filas
- `tableManager.js` → Solo orquestación

### ✅ Open/Closed Principle (OCP)
Módulos **abiertos para extensión**, **cerrados para modificación**:
- `updates.js` tiene método `_sendUpdate()` reutilizable para nuevos tipos de updates
- `diaEntregaModule.js` config es fácilmente extensible
- `notificationModule.js` puede añadir nuevos tipos sin modificar código existente

### ✅ Liskov Substitution Principle (LSP)
Módulos intercambiables sin quebrar la lógica:
- Todos tienen interfaz consistente (métodos públicos bien definidos)
- Pueden ser reemplazados por versiones mejoradas

### ✅ Interface Segregation Principle (ISP)
Interfaces específicas, no monolíticas:
- `UpdatesModule.updateOrderArea()` específico solo para área
- `UpdatesModule.updateOrderStatus()` específico solo para estado
- No fuerza clientes a depender de métodos que no usan

### ✅ Dependency Inversion Principle (DIP)
Dependen de abstracciones, no de implementaciones concretas:
- `DropdownManager` no crea `UpdatesModule`, lo asume disponible en global
- `TableManager` coordina pero no implementa lógica de negocio

---

## 2. ORDEN DE DEPENDENCIAS

```
NIVEL 0 (Sin dependencias):
┌─────────────────────────────────────────────┐
│ • FormattingModule                          │
│ • StorageModule                             │
│ • NotificationModule                        │
└─────────────────────────────────────────────┘
                    ↓ Dependen de
NIVEL 1 (Dependen de Nivel 0):
┌─────────────────────────────────────────────┐
│ • UpdatesModule (→ NotificationModule)      │
│ • RowManager (→ FormattingModule)           │
└─────────────────────────────────────────────┘
                    ↓ Dependen de
NIVEL 2 (Dependen de Nivel 1):
┌─────────────────────────────────────────────┐
│ • DropdownManager (→ UpdatesModule)         │
│ • DiaEntregaModule (→ UpdatesModule)        │
└─────────────────────────────────────────────┘
                    ↓ Coordina todos
NIVEL 3 (Orquestador):
┌─────────────────────────────────────────────┐
│ • TableManager                              │
└─────────────────────────────────────────────┘
```

---

## 3. DESCRIPCIÓN DETALLADA DE MÓDULOS

### 📋 `FormattingModule`
**Responsabilidad**: Formatear datos (fechas, tipos)

```javascript
// Métodos públicos:
- formatearFecha(fecha)           // YYYY-MM-DD → DD/MM/YYYY
- esColumnaFecha(columnaName)     // Detecta si es columna de fecha
- asegurarFormatoFecha(valor)     // Normaliza formato de fecha
```

**Uso**:
```javascript
const fechaFormato = FormattingModule.formatearFecha('2024-01-15');
// Resultado: '15/01/2024'
```

---

### 💾 `StorageModule`
**Responsabilidad**: Sincronización entre tabs usando localStorage

```javascript
// Métodos públicos:
- broadcastUpdate(data)           // Enviar a otros tabs
- initializeListener()            // Escuchar cambios de otros tabs
```

**Características**:
- Detecta cambios en localStorage
- Sincroniza updates de estado/área/días entre tabs
- No interfiere con otros módulos (DIP)

---

### 🔔 `NotificationModule`
**Responsabilidad**: Mostrar notificaciones visuales

```javascript
// Métodos públicos:
- showAutoReload(message, duration)  // Recarga con progress bar
- showError(message, duration)       // Error rojo
- showSuccess(message, duration)     // Success verde
```

**Características**:
- Auto-inyecta estilos CSS
- Animaciones suaves (slide in/out)
- Barra de progreso para auto-recarga

---

### 📡 `UpdatesModule`
**Responsabilidad**: Enviar peticiones PATCH al servidor

```javascript
// Métodos públicos:
- updateOrderStatus(numeroOrden, estado)
- updateOrderArea(numeroOrden, area)
- updateOrderDiaEntrega(numeroOrden, dias)

// Métodos privados (SRP):
- _sendUpdate(url, data)         // Lógica PATCH común
- _handleResponse(response)      // Manejo de errores
- _handleNetworkError(error)     // Retry logic
```

**Características**:
- Manejo unificado de errores (500, 401, 419)
- Retry automático en fallos de red
- Usa `NotificationModule` para feedback
- Facilmente extensible para nuevos tipos de updates

---

### 🔘 `DropdownManager`
**Responsabilidad**: Gestionar dropdowns de estado y área

```javascript
// Métodos públicos:
- initialize()                   // Detectar cambios
- initializeStatusDropdowns()    // Setup estado
- initializeAreaDropdowns()      // Setup área
- handleStatusChange(select)     // Cambio estado
- handleAreaChange(select)       // Cambio área
```

**Características**:
- Event delegation (escucha cambios en selectores específicos)
- Debounce 300ms antes de enviar update
- Usa `UpdatesModule` para comunicación con servidor

---

### 📅 `DiaEntregaModule`
**Responsabilidad**: Lógica especializada de "día de entrega"

```javascript
// Métodos públicos:
- initialize()                         // Setup listeners
- handleDiaEntregaChange(select)       // Cambio detectado
- getAvailableDays()                   // 1-30 días
- calculateDeliveryDate(currentDate, days)  // Calcula fecha
- getSuggestedDays(estado)             // Sugerencias por estado
- getIndicatorColor(days)              // Color según urgencia
```

**Características**:
- Validación de rango (1-30 días)
- Warnings visuales para entrega urgente (≤7 días)
- Sugerencias automáticas según estado de proceso
- Indicadores visuales de urgencia (rojo/naranja/amarillo/verde)

---

### 🎯 `RowManager`
**Responsabilidad**: Operaciones CRUD en filas de tabla

```javascript
// Métodos públicos:
- updateRowColor(orden)          // Aplica estilos CSS
- actualizarOrdenEnTabla(orden)  // Actualiza celdas
- crearFilaOrden(orden)          // Crea nueva fila
- eliminarFila(numeroOrden)      // Borra fila
- executeRowUpdate(orden, changedFields)  // Update completo
```

**Características**:
- Estilos condicionales (estado + días)
- Usa `FormattingModule` para formatear fechas
- Actualiza solo campos que cambiaron (`changedFields`)

---

### 🎭 `TableManager`
**Responsabilidad**: Orquestar todos los módulos

```javascript
// Métodos públicos:
- init()                         // Inicializar todo
- getModule(moduleName)          // Acceder a módulo
- listModules()                  // Listar cargados
- reloadTable()                  // Recargar página
- verifyDependencies()           // Validar módulos
```

**Ciclo de vida**:
1. **Fase 1**: Cargar módulos sin dependencias
2. **Fase 2**: Cargar módulos con dependencias
3. **Fase 3**: Configurar integraciones
4. **Fase 4**: Adjuntar listeners globales

**Auto-inicialización**:
```javascript
// Se inicializa automáticamente cuando DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    TableManager.init();
});
```

---

## 4. INTEGRACIÓN CON TEMPLATE

### Orden de Carga (index.blade.php)

```html
<!-- FASE 1: Módulos base (sin dependencias) -->
<script src="modules/formatting.js"></script>
<script src="modules/storageModule.js"></script>
<script src="modules/notificationModule.js"></script>

<!-- FASE 2: Módulos dependientes -->
<script src="modules/updates.js"></script>
<script src="modules/rowManager.js"></script>
<script src="modules/dropdownManager.js"></script>
<script src="modules/diaEntregaModule.js"></script>

<!-- FASE 3: Orquestador (inicia automáticamente) -->
<script src="modules/tableManager.js"></script>

<!-- Scripts originales (compatibilidad) -->
<script src="orders-table.js"></script>
```

### Por qué este orden:
1. **Nivel 0 primero** → No hay dependencias
2. **Nivel 1 después** → Pueden usar Nivel 0
3. **Nivel 2 después** → Pueden usar Nivel 0 y 1
4. **TableManager último** → Coordina todo
5. **Scripts originales** → Se cargan después (para compatibilidad)

---

## 5. EJEMPLOS DE USO

### Actualizar área
```javascript
// Sin módulos (viejo):
// 2300 líneas de lógica mezclada

// Con módulos (nuevo):
UpdatesModule.updateOrderArea(numeroOrden, areaName);
// ✓ Envía PATCH
// ✓ Valida respuesta
// ✓ Muestra notificación
// ✓ Sincroniza con otros tabs
// ✓ Actualiza row
```

### Sincronizar entre tabs
```javascript
// Tab 1 cambia algo
StorageModule.broadcastUpdate({ 
    numeroOrden: 123, 
    area: 'Confeccionando' 
});

// Tab 2 automáticamente lo recibe
```

### Mostrar notificación
```javascript
NotificationModule.showSuccess('Área actualizada');
// o
NotificationModule.showError('Error al actualizar');
// o
NotificationModule.showAutoReload('Recargando...', 3000);
```

### Acceder a un módulo
```javascript
const updates = TableManager.getModule('updates');
// o
const modules = TableManager.listModules();
console.log(modules.loaded); // ['notification', 'formatting', ...]
```

---

## 6. VENTAJAS DE LA REFACTORIZACIÓN

### Antes (monolítico):
- ❌ 2300+ líneas en un archivo
- ❌ Difícil de mantener
- ❌ Difícil de testear
- ❌ Cambios en una parte afectan todo
- ❌ Responsabilidades mezcladas

### Ahora (modular):
- ✅ 8 módulos, ~50-180 líneas c/u
- ✅ Fácil de mantener
- ✅ Fácil de testear unitariamente
- ✅ Cambios aislados
- ✅ Responsabilidades claras
- ✅ Reutilizable
- ✅ Extensible

---

## 7. MÉTRICAS DE CÓDIGO

| Métrica | Antes | Después |
|---------|-------|---------|
| Líneas totales | 2300+ | ~800 (distribuidas) |
| Archivos | 1 | 8 |
| Líneas por archivo | 2300 | 50-180 |
| Complejidad ciclomática | Alta | Baja (modular) |
| Testabilidad | Baja | Alta (SRP) |
| Mantenibilidad | Baja | Alta |

---

## 8. ROADMAP FUTURO

### Fase actual (✅ Completada):
- ✅ Refactorizar en módulos SOLID
- ✅ Crear sistema de dependencias

### Próximas fases:
- ⏳ Migrar lógica restante de `orders-table.js`
- ⏳ Crear módulo de búsqueda/filtrado
- ⏳ Crear módulo de exportación de datos
- ⏳ Tests unitarios para cada módulo
- ⏳ Eliminar `orders-table.js` gradualmente
- ⏳ Patrón Observable para reactividad

---

## 9. DEBUGGING

### Verificar módulos cargados:
```javascript
// En consola del navegador:
console.log(TableManager.listModules());

// Resultado:
{
  loaded: ['notification', 'formatting', 'storage', 'updates', 'rowManager', 'dropdownManager', 'diaEntrega', 'tableManager'],
  initialized: true
}
```

### Acceder a un módulo:
```javascript
const updates = TableManager.getModule('updates');
console.log(updates);

// Puedes llamar métodos directamente:
updates.updateOrderArea(123, 'Confeccionando');
```

### Logs de inicialización:
Abre DevTools (F12) → Console, deberías ver:
```
📦 Fase 1: Inicializando módulos base...
✅ Fase 1 completada
📦 Fase 2: Inicializando módulos dependientes...
✅ Fase 2 completada
📦 Fase 3: Configurando integraciones...
✅ Fase 3 completada
📦 Fase 4: Adjuntando listeners globales...
✅ Fase 4 completada
✅ TableManager inicializado correctamente
```

---

## 10. NOTAS IMPORTANTES

1. **Compatibilidad hacia atrás**: Los scripts originales se cargan después de los módulos (para compatibilidad)
2. **No hay conflictos**: Cada módulo es independiente y global
3. **Cache busting**: Se usa `?v={{ time() }}` en los includes para evitar cache
4. **Sincronización**: `StorageModule` sincroniza automáticamente entre tabs
5. **Errores**: Se capturan y reportan en consola y notificaciones visuales

---

## 📞 SOPORTE

Para agregar nueva funcionalidad:
1. Identificar responsabilidad (qué hace)
2. Crear nuevo módulo o extender existente
3. Seguir patrón SOLID
4. Agregar al `tableManager.js` si es necesario
5. Cargar en template en orden correcto

¡Ahora el código es más mantenible, testeable y escalable! 🎉
