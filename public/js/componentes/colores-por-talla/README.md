# Sistema de Colores por Talla - Arquitectura Desacoplada

## 📁 Estructura de Módulos

El sistema original `colores-por-talla.js` ha sido desacoplado en los siguientes módulos:

### 🏗️ Módulos Principales

#### 1. **StateManager.js** - Gestión de Estado
- Responsable de manejar todo el estado global de la aplicación
- Gestiona asignaciones de colores, estado del wizard, y tallas disponibles
- Proporciona API inmutable para acceso y modificación del estado

**API Principal:**
```javascript
StateManager.getAsignaciones()
StateManager.setAsignaciones(asignaciones)
StateManager.getWizardState()
StateManager.setWizardState(state)
StateManager.tieneAsignaciones()
```

#### 2. **DOMUtils.js** - Utilidades DOM
- Funciones reutilizables para manipulación del DOM
- Abstracción sobre operaciones comunes del DOM
- Manejo de errores y validación de elementos

**API Principal:**
```javascript
DOMUtils.getElement(id)
DOMUtils.querySelector(selector)
DOMUtils.createElement(tag, options)
DOMUtils.showNotification(message, type)
DOMUtils.setStyles(element, styles)
```

#### 3. **AsignacionManager.js** - Gestión de Asignaciones
- Lógica de negocio para CRUD de asignaciones de colores
- Validaciones y reglas de negocio
- Integración con StateManager para persistencia

**API Principal:**
```javascript
AsignacionManager.agregarColorPersonalizado(genero, talla, color, cantidad)
AsignacionManager.guardarAsignacionColores(genero, talla, colores)
AsignacionManager.eliminarAsignacion(genero, talla, color)
AsignacionManager.obtenerColoresDisponibles()
```

#### 4. **WizardManager.js** - Gestión del Wizard
- Controla la navegación y flujo del wizard de 3 pasos
- Manejo de estados de la interfaz del wizard
- Validaciones entre pasos

**API Principal:**
```javascript
WizardManager.seleccionarGenero(genero)
WizardManager.pasoSiguiente()
WizardManager.irPaso(numeroPaso)
WizardManager.cargarTallasParaGenero(genero)
```

#### 5. **UIRenderer.js** - Renderizado de Interfaz
- Creación y actualización de componentes visuales
- Generación dinámica de HTML complejo
- Manejo de eventos de la interfaz

**API Principal:**
```javascript
UIRenderer.actualizarTablaAsignaciones()
UIRenderer.actualizarResumenAsignaciones()
UIRenderer.generarInterfazColoresPorTalla(genero, tallas, tipo)
UIRenderer.cargarColoresDispAsignacion()
```

#### 6. **ColoresPorTalla.js** - Orquestador Principal
- Coordina todos los módulos
- Expone la API pública principal
- Manejo de eventos globales
- Punto de entrada único del sistema

**API Principal:**
```javascript
ColoresPorTalla.init()
ColoresPorTalla.toggleVistaAsignacion()
ColoresPorTalla.obtenerDatosAsignaciones()
ColoresPorTalla.limpiarAsignaciones()
```

#### 7. **compatibilidad.js** - Compatibilidad hacia Atrás
- Mantiene la API antigua funcionando
- Traduce llamadas antiguas a la nueva arquitectura
- Facilita migración gradual

##  Flujo de Datos

```
Usuario → ColoresPorTalla → [StateManager, AsignacionManager, WizardManager, UIRenderer] → DOM
                ↓
        compatibilidad.js (API antigua)
```

## 📋 Carga de Módulos

En los archivos Blade, cargar en este orden específico:

```html
<script src="{{ asset('js/componentes/colores-por-talla/StateManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/DOMUtils.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/AsignacionManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/WizardManager.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/UIRenderer.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/ColoresPorTalla.js') }}"></script>
<script src="{{ asset('js/componentes/colores-por-talla/compatibilidad.js') }}"></script>
```

## 🎯 Beneficios del Desacoplamiento

### 1. **Mantenibilidad**
- Cada módulo tiene una responsabilidad única
- Código más fácil de entender y modificar
- Menos acoplamiento entre componentes

### 2. **Testabilidad**
- Cada módulo puede ser probado independientemente
- Mocking de dependencias más sencillo
- Cobertura de prueba más alta

### 3. **Reutilización**
- Módulos pueden ser reutilizados en otros contextos
- DOMUtils puede usarse en cualquier parte de la aplicación
- StateManager puede gestionar otros estados similares

### 4. **Escalabilidad**
- Fácil agregar nuevas funcionalidades
- Los módulos pueden evolucionar independientemente
- Mejor organización del código

### 5. **Debugging**
- Logs más específicos por módulo
- Más fácil identificar el origen de problemas
- Mejor trazabilidad de errores

##  Migración desde API Antigua

### Para código existente que usa la API antigua:

```javascript
// Antiguo (sigue funcionando)
const datos = obtenerDatosAsignacionesColores();
limpiarAsignacionesColores();

// Nuevo (recomendado)
const datos = window.ColoresPorTalla.obtenerDatosAsignaciones();
window.ColoresPorTalla.limpiarAsignaciones();
```

### Para nuevo código:

```javascript
// Usar siempre la nueva API
window.ColoresPorTalla.init();
const datos = window.ColoresPorTalla.obtenerDatosAsignaciones();
window.ColoresPorTalla.toggleVistaAsignacion();
```

## 🐛 Debugging y Troubleshooting

### Logs por Módulo:
- `[StateManager]` - Operaciones de estado
- `[DOMUtils]` - Operaciones del DOM
- `[AsignacionManager]` - Lógica de asignaciones
- `[WizardManager]` - Navegación del wizard
- `[UIRenderer]` - Renderizado de interfaz
- `[ColoresPorTalla]` - Operaciones del orquestador
- `[Compatibilidad]` - Traducciones de API antigua

### Errores Comunes:
1. **Módulo no cargado**: Verificar orden de carga de scripts
2. **Dependencia faltante**: Revisar que todos los módulos estén disponibles
3. **Estado inconsistente**: Usar `StateManager.getState()` para depurar

## 📚 Patrones Utilizados

### 1. **Module Pattern**
- Cada módulo usa IIFE para encapsulación
- API pública controlada
- Estado privado protegido

### 2. **Observer Pattern**
- StateManager notifica cambios de estado
- UIRenderer reacciona a cambios
- Desacoplamiento entre estado y UI

### 3. **Facade Pattern**
- ColoresPorTalla actúa como fachada
- Simplifica interacción compleja
- API unificada para el cliente

### 4. **Adapter Pattern**
- compatibilidad.js actúa como adaptador
- Traduce API antigua a nueva
- Mantiene compatibilidad hacia atrás

##  Futuras Mejoras

1. **TypeScript**: Migrar a TypeScript para mejor tipado
2. **Unit Tests**: Agregar suite de pruebas unitarias
3. **Event Bus**: Implementar sistema de eventos más robusto
4. **State Persistence**: Guardar estado en localStorage
5. **Modularización**: Considerar ES Modules para import/export

## 📝 Notas de Mantenimiento

- Al modificar un módulo, verificar no romper dependencias
- Mantener la compatibilidad hacia atrás mientras sea posible
- Documentar cambios en la API en este README
- Seguir convención de logs con prefijo de módulo
