# 🚀 PLAN DE REFACTORIZACIÓN POR FASES - recibos-costura.blade.php

> **Ubicación:** Todos los archivos en **`public/js/modules/recibos-costura/`**

---

## 📋 RESUMEN DE FASES

| Fase | Duración | Qué incluye | Resultado |
|------|----------|-----------|-----------|
| **Fase 1** | 2-3 horas | Value Objects | Validaciones centralizadas |
| **Fase 2** | 2-3 horas | State Manager | Estado global controlado |
| **Fase 3** | 2-3 horas | Controllers simples | Lógica de UI separada |
| **Fase 4** | 1-2 horas | Extrae del Blade | Blade limpia + módulos |
| **TOTAL** | ~10 horas | Todo funcional | Código 90% mejor |

---

## 🎯 FASE 1: VALUE OBJECTS (Validaciones de Dominio)

### ¿Qué es?
Clases simples que **validan datos** y **encapsulan reglas de negocio**.

### Estructura:
```
public/js/modules/recibos-costura/
└── domain/
    └── value-objects/
        ├── EstadoRecibo.js
        ├── AreaRecibocostura.js
        ├── EncargadoProceso.js
        └── index.js
```

### Archivo 1: EstadoRecibo.js
```javascript
// public/js/modules/recibos-costura/domain/value-objects/EstadoRecibo.js

const ESTADOS_VALIDOS = {
    PENDIENTE: 'PENDIENTE',
    EN_PROCESO: 'EN_PROCESO',
    COMPLETADO: 'COMPLETADO',
    RECHAZADO: 'RECHAZADO'
};

class EstadoRecibo {
    constructor(valor) {
        if (!Object.values(ESTADOS_VALIDOS).includes(valor)) {
            throw new Error(`❌ Estado inválido: "${valor}". Válidos: ${Object.values(ESTADOS_VALIDOS).join(', ')}`);
        }
        this.valor = valor;
    }
    
    static desde(texto) {
        const estado = Object.values(ESTADOS_VALIDOS).find(
            e => e === texto?.toUpperCase()
        );
        if (!estado) {
            throw new Error(`❌ Estado no reconocido: "${texto}"`);
        }
        return new EstadoRecibo(estado);
    }
    
    // Factory methods
    static PENDIENTE() { return new EstadoRecibo(ESTADOS_VALIDOS.PENDIENTE); }
    static EN_PROCESO() { return new EstadoRecibo(ESTADOS_VALIDOS.EN_PROCESO); }
    static COMPLETADO() { return new EstadoRecibo(ESTADOS_VALIDOS.COMPLETADO); }
    static RECHAZADO() { return new EstadoRecibo(ESTADOS_VALIDOS.RECHAZADO); }
    
    // Comparaciones
    es(otro) {
        return this.valor === otro.valor;
    }
    
    esUno(...estados) {
        return estados.some(e => e.es(this));
    }
    
    toString() {
        return this.valor;
    }
}

export { EstadoRecibo, ESTADOS_VALIDOS };
```

### Archivo 2: AreaRecibocostura.js
```javascript
// public/js/modules/recibos-costura/domain/value-objects/AreaRecibocostura.js

const AREAS_VALIDAS = {
    CORTE: 'CORTE',
    COSTURA: 'COSTURA',
    EMPAQUE: 'EMPAQUE',
    CALIDAD: 'CALIDAD'
};

const COLORES_AREA = {
    CORTE: '#8b5cf6',
    COSTURA: '#14b8a6',
    EMPAQUE: '#f97316',
    CALIDAD: '#ec4899'
};

class AreaRecibocostura {
    constructor(valor) {
        if (!Object.values(AREAS_VALIDAS).includes(valor)) {
            throw new Error(`❌ Área inválida: "${valor}". Válidas: ${Object.values(AREAS_VALIDAS).join(', ')}`);
        }
        this.valor = valor;
    }
    
    static desde(texto) {
        const area = Object.values(AREAS_VALIDAS).find(
            a => a === texto?.toUpperCase()
        );
        if (!area) {
            throw new Error(`❌ Área no reconocida: "${texto}"`);
        }
        return new AreaRecibocostura(area);
    }
    
    // Factory methods
    static CORTE() { return new AreaRecibocostura(AREAS_VALIDAS.CORTE); }
    static COSTURA() { return new AreaRecibocostura(AREAS_VALIDAS.COSTURA); }
    static EMPAQUE() { return new AreaRecibocostura(AREAS_VALIDAS.EMPAQUE); }
    static CALIDAD() { return new AreaRecibocostura(AREAS_VALIDAS.CALIDAD); }
    
    // Comparaciones
    esIgual(otra) {
        return this.valor === otra.valor;
    }
    
    // Presentación
    getColor() {
        return COLORES_AREA[this.valor];
    }
    
    toString() {
        return this.valor;
    }
}

export { AreaRecibocostura, AREAS_VALIDAS, COLORES_AREA };
```

### Archivo 3: EncargadoProceso.js
```javascript
// public/js/modules/recibos-costura/domain/value-objects/EncargadoProceso.js

class EncargadoProceso {
    constructor(nombre) {
        const nombreTrimmed = nombre?.trim();
        
        if (!nombreTrimmed) {
            throw new Error('❌ Nombre de encargado no puede estar vacío');
        }
        if (nombreTrimmed.length < 2) {
            throw new Error('❌ Nombre muy corto (mínimo 2 caracteres)');
        }
        if (nombreTrimmed.length > 100) {
            throw new Error('❌ Nombre muy largo (máximo 100 caracteres)');
        }
        
        this.nombre = nombreTrimmed;
    }
    
    static desde(nombre) {
        return new EncargadoProceso(nombre);
    }
    
    esIgual(otro) {
        return this.nombre.toLowerCase() === otro.nombre.toLowerCase();
    }
    
    toString() {
        return this.nombre;
    }
}

export { EncargadoProceso };
```

### Archivo 4: index.js (Export central)
```javascript
// public/js/modules/recibos-costura/domain/value-objects/index.js

export { EstadoRecibo, ESTADOS_VALIDOS } from './EstadoRecibo.js';
export { AreaRecibocostura, AREAS_VALIDAS, COLORES_AREA } from './AreaRecibocostura.js';
export { EncargadoProceso } from './EncargadoProceso.js';
```

### ✅ Fase 1 - Usos en el código actual:

```javascript
// ANTES ❌ (en recibos-costura.blade.php)
const estado = 'PENDIENTE'; // String crudo sin validación
const area = selectArea.value; // Qué pasa si es inválido?
selectArea.value = '';

// DESPUÉS ✅ (en tu código)
import { EstadoRecibo, AreaRecibocostura } from './domain/value-objects/index.js';

try {
    const estado = EstadoRecibo.desde('PENDIENTE');
    const area = AreaRecibocostura.desde(selectArea.value);
} catch (error) {
    console.error('Validación fallida:', error.message);
}
```

---

## 🎯 FASE 2: STATE MANAGER (Estado Global Controlado)

### ¿Qué es?
Un gestor de estado centralizado que **reemplaza los `window.*` globales**.

### Estructura:
```
public/js/modules/recibos-costura/
└── infrastructure/
    └── state/
        ├── StateManager.js
        └── index.js
```

### Archivo: StateManager.js
```javascript
// public/js/modules/recibos-costura/infrastructure/state/StateManager.js

class StateManager {
    constructor() {
        // Estado
        this.state = {
            recibos: [],
            filtrosActivos: {},
            cargando: false,
            error: null
        };
        
        // Listeners
        this.listeners = new Map();
    }
    
    // ========== OBSERVABLES ==========
    on(evento, callback) {
        if (!this.listeners.has(evento)) {
            this.listeners.set(evento, []);
        }
        this.listeners.get(evento).push(callback);
        
        // Retornar función para desuscribirse
        return () => this.off(evento, callback);
    }
    
    off(evento, callback) {
        if (this.listeners.has(evento)) {
            const callbacks = this.listeners.get(evento);
            const index = callbacks.indexOf(callback);
            if (index > -1) callbacks.splice(index, 1);
        }
    }
    
    private emitir(evento, datos) {
        console.log(`[State] 📢 ${evento}`, datos);
        this.listeners.get(evento)?.forEach(cb => cb(datos));
    }
    
    // ========== RECIBOS ==========
    setRecibos(recibos) {
        this.state.recibos = [...recibos];
        this.emitir('recibos:cambio', this.state.recibos);
    }
    
    getRecibos() {
        return [...this.state.recibos];
    }
    
    // ========== FILTROS ==========
    setFiltrosActivos(filtros) {
        this.state.filtrosActivos = { ...filtros };
        this.emitir('filtros:cambio', this.state.filtrosActivos);
    }
    
    getFiltrosActivos() {
        return { ...this.state.filtrosActivos };
    }
    
    limpiarFiltros() {
        this.state.filtrosActivos = {};
        this.emitir('filtros:limpiar');
    }
    
    // ========== CARGA ==========
    setCargando(valor) {
        this.state.cargando = valor;
        this.emitir('cargando:cambio', valor);
    }
    
    estaCargando() {
        return this.state.cargando;
    }
    
    // ========== ERRORES ==========
    setError(error) {
        this.state.error = error;
        this.emitir('error:cambio', error);
    }
    
    getError() {
        return this.state.error;
    }
    
    limpiarError() {
        this.state.error = null;
        this.emitir('error:limpiar');
    }
    
    // ========== DEBUG ==========
    getState() {
        return { ...this.state };
    }
    
    debug() {
        console.table(this.state);
    }
}

// Singleton
const stateManager = new StateManager();

export { StateManager, stateManager };
```

### ✅ Fase 2 - Usos en el código:

```javascript
// ANTES ❌ 
window.currentOrderData = datos;
window.activeFilters = {};

// DESPUÉS ✅
import { stateManager } from './infrastructure/state/StateManager.js';

stateManager.setRecibos(datos);
stateManager.setFiltrosActivos({});

// Suscribirse a cambios
stateManager.on('recibos:cambio', (recibos) => {
    console.log('Recibos actualizados:', recibos);
    renderizarTabla(recibos);
});
```

---

## 🎯 FASE 3: CONTROLLERS (Lógica de Interfaz)

### ¿Qué es?
Controllers que orquestan lógica entre estado, DOM y eventos.

### Estructura:
```
public/js/modules/recibos-costura/
└── presentation/
    ├── controllers/
    │   ├── FiltrosController.js
    │   ├── ProcesoCosturaController.js
    │   └── index.js
    └── managers/
        ├── ModalManager.js
        ├── NotificationsManager.js
        └── index.js
```

### Archivo: FiltrosController.js
```javascript
// public/js/modules/recibos-costura/presentation/controllers/FiltrosController.js

import { stateManager } from '../../infrastructure/state/StateManager.js';
import { AreaRecibocostura, EstadoRecibo } from '../../domain/value-objects/index.js';
import { NotificationsManager } from '../managers/NotificationsManager.js';

class FiltrosController {
    constructor(filtrarRecibosUseCase) {
        this.filtrarRecibosUseCase = filtrarRecibosUseCase;
        this.notif = new NotificationsManager();
        this.init();
    }
    
    init() {
        console.log('[FiltrosController] ✅ Inicializado');
        
        // Escuchar cambios en filtros
        stateManager.on('filtros:cambio', (filtros) => {
            this.aplicarFiltros(filtros);
        });
    }
    
    async aplicarFiltros(comando) {
        try {
            console.log('[FiltrosController] 🔍 Aplicando filtros:', comando);
            
            stateManager.setCargando(true);
            stateManager.setError(null);
            
            const recibos = await this.filtrarRecibosUseCase.execute(comando);
            stateManager.setRecibos(recibos);
            
            this.notif.exito(`${recibos.length} recibos encontrados`);
            
        } catch (error) {
            console.error('[FiltrosController] ❌ Error:', error);
            stateManager.setError(error.message);
            this.notif.error(error.message);
        } finally {
            stateManager.setCargando(false);
        }
    }
    
    limpiarFiltros() {
        console.log('[FiltrosController] 🧹 Limpiando filtros');
        stateManager.limpiarFiltros();
        stateManager.setRecibos([]);
    }
}

export { FiltrosController };
```

### Archivo: ProcesoCosturaController.js
```javascript
// public/js/modules/recibos-costura/presentation/controllers/ProcesoCosturaController.js

import { stateManager } from '../../infrastructure/state/StateManager.js';
import { AreaRecibocostura, EncargadoProceso } from '../../domain/value-objects/index.js';
import { ModalManager } from '../managers/ModalManager.js';
import { NotificationsManager } from '../managers/NotificationsManager.js';

class ProcesoCosturaController {
    constructor(agregarProcesoUseCase) {
        this.agregarProcesoUseCase = agregarProcesoUseCase;
        this.modalManager = new ModalManager('addProcesoModal');
        this.notif = new NotificationsManager();
        this.init();
    }
    
    init() {
        console.log('[ProcesoCosturaController] ✅ Inicializado');
        
        // Escuchar evento de guardar desde el formulario
        window.addEventListener('agregarProceso', (e) => {
            this.agregarProceso(e.detail);
        });
    }
    
    async agregarProceso(datos) {
        try {
            console.log('[ProcesoCosturaController] ➕ Agregando proceso:', datos);
            
            // Validar con Value Objects
            const area = AreaRecibocostura.desde(datos.area);
            const encargado = EncargadoProceso.desde(datos.encargado);
            
            stateManager.setCargando(true);
            stateManager.setError(null);
            
            const resultado = await this.agregarProcesoUseCase.execute(datos);
            
            this.notif.exito('✅ Proceso agregado correctamente');
            this.modalManager.close();
            
            // Emit evento para que otros módulos sepan que se agregó un proceso
            window.dispatchEvent(new CustomEvent('procesoAgregado', { detail: resultado }));
            
        } catch (error) {
            console.error('[ProcesoCosturaController] ❌ Error:', error);
            stateManager.setError(error.message);
            this.notif.error(error.message);
        } finally {
            stateManager.setCargando(false);
        }
    }
    
    abrirModal() {
        this.modalManager.open();
    }
    
    cerrarModal() {
        this.modalManager.close();
    }
}

export { ProcesoCosturaController };
```

### Archivo: ModalManager.js
```javascript
// public/js/modules/recibos-costura/presentation/managers/ModalManager.js

class ModalManager {
    constructor(modalId) {
        this.modalId = modalId;
    }
    
    open() {
        const modal = document.getElementById(this.modalId);
        if (!modal) {
            console.warn(`[ModalManager] ⚠️ Modal #${this.modalId} no encontrado`);
            return;
        }
        console.log(`[ModalManager] 🔓 Abriendo modal #${this.modalId}`);
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
    
    close() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            console.log(`[ModalManager] 🔒 Cerrando modal #${this.modalId}`);
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }
    
    isOpen() {
        const modal = document.getElementById(this.modalId);
        return modal?.classList.contains('show') ?? false;
    }
    
    toggle() {
        this.isOpen() ? this.close() : this.open();
    }
}

export { ModalManager };
```

### Archivo: NotificationsManager.js
```javascript
// public/js/modules/recibos-costura/presentation/managers/NotificationsManager.js

class NotificationsManager {
    constructor() {
        this.containerId = 'toastContainer';
    }
    
    mostrar(mensaje, tipo = 'info', titulo = '') {
        const container = document.getElementById(this.containerId) || this.crearContenedor();
        
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        toast.innerHTML = `
            <div class="toast-icon">${this.obtenerIcono(tipo)}</div>
            <div class="toast-content">
                ${titulo ? `<div class="toast-title">${titulo}</div>` : ''}
                <div class="toast-message">${mensaje}</div>
            </div>
            <button class="toast-close" onclick="this.parentNode.remove()">×</button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    exito(mensaje, titulo = 'Éxito') { this.mostrar(mensaje, 'success', titulo); }
    error(mensaje, titulo = 'Error') { this.mostrar(mensaje, 'error', titulo); }
    info(mensaje, titulo = 'Información') { this.mostrar(mensaje, 'info', titulo); }
    
    private crearContenedor() {
        const container = document.createElement('div');
        container.id = this.containerId;
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    private obtenerIcono(tipo) {
        return { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' }[tipo] || '•';
    }
}

export { NotificationsManager };
```

---

## 🎯 FASE 4: INTEGRACIÓN EN BLADE (Lo final)

### Estructura final:
```
public/js/modules/recibos-costura/
├── domain/
│   └── value-objects/
│       ├── EstadoRecibo.js
│       ├── AreaRecibocostura.js
│       ├── EncargadoProceso.js
│       └── index.js
├── infrastructure/
│   └── state/
│       └── StateManager.js
├── presentation/
│   ├── controllers/
│   │   ├── FiltrosController.js
│   │   ├── ProcesoCosturaController.js
│   │   └── index.js
│   └── managers/
│       ├── ModalManager.js
│       ├── NotificationsManager.js
│       └── index.js
├── repositories.js         # (Traes del código actual)
├── use-cases.js           # (Traes del código actual refactorizado)
└── initializer.js         # (Punto de entrada)
```

### Archivo: initializer.js
```javascript
// public/js/modules/recibos-costura/initializer.js

import { stateManager, StateManager } from './infrastructure/state/StateManager.js';
import { FiltrosController } from './presentation/controllers/FiltrosController.js';
import { ProcesoCosturaController } from './presentation/controllers/ProcesoCosturaController.js';
import { ReciboCosturaHttpRepository } from './repositories.js';
import { FiltrarRecibosUseCase, AgregarProcesoUseCase } from './use-cases.js';

class RecibosInitializer {
    async init() {
        console.log('[RecibosInitializer] 🚀 Inicializando módulo recibos-costura...');
        
        try {
            // Crear repositorios
            const reciboRepo = new ReciboCosturaHttpRepository();
            const procesoRepo = new ProcessoCosturaHttpRepository();
            
            // Crear use cases
            const filtrarUseCase = new FiltrarRecibosUseCase(reciboRepo);
            const agregarUseCase = new AgregarProcesoUseCase(procesoRepo);
            
            // Crear controllers
            this.filtrosController = new FiltrosController(filtrarUseCase);
            this.procesoController = new ProcesoCosturaController(agregarUseCase);
            
            // Cargar datos iniciales
            console.log('[RecibosInitializer] 📥 Cargando recibos iniciales...');
            const recibos = await reciboRepo.obtenerTodos();
            stateManager.setRecibos(recibos);
            
            console.log('[RecibosInitializer] ✅ Módulo inicializado exitosamente');
            
        } catch (error) {
            console.error('[RecibosInitializer] ❌ Error:', error);
            stateManager.setError('Error al inicializar módulo');
        }
    }
}

// Instancia global
window.recibosInitializer = new RecibosInitializer();

// Auto-init cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.recibosInitializer.init());
} else {
    window.recibosInitializer.init();
}

export { RecibosInitializer };
```

### Archivo: Blade limpia
```blade
<!-- resources/views/registros/recibos-costura.blade.php -->
@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Tu tabla original, SIN scripts -->
            <x-recibos.recibos-costura-table :recibos="$recibos" />
        </div>
    </div>
</div>

<!-- Contenedores para modales y notificaciones -->
<div id="modals-container"></div>
<div id="toastContainer"></div>
@endsection

@push('scripts')
<!-- SOLO UNA LÍNEA -->
<script type="module">
    import('./public/js/modules/recibos-costura/initializer.js');
</script>
@endpush
```

---

## 📝 RESUMEN DE CARPETAS A CREAR

```bash
# Ejecuta esto en PowerShell

# FASE 1
mkdir public/js/modules/recibos-costura/domain/value-objects

# FASE 2
mkdir public/js/modules/recibos-costura/infrastructure/state

# FASE 3
mkdir public/js/modules/recibos-costura/presentation/controllers
mkdir public/js/modules/recibos-costura/presentation/managers
```

---

## 🎬 CHECKLIST DE IMPLEMENTACIÓN

### FASE 1 (Hoy - 2-3 horas)
- [ ] Crear carpeta `public/js/modules/recibos-costura/`
- [ ] Crear `domain/value-objects/EstadoRecibo.js`
- [ ] Crear `domain/value-objects/AreaRecibocostura.js`
- [ ] Crear `domain/value-objects/EncargadoProceso.js`
- [ ] Crear `domain/value-objects/index.js`
- [ ] ✅ Probar en console: `EstadoRecibo.desde('PENDIENTE')`

### FASE 2 (Mañana - 2-3 horas)
- [ ] Crear `infrastructure/state/StateManager.js`
- [ ] Probar en console: `stateManager.getState()`
- [ ] Suscribirse a eventos: `stateManager.on('recibos:cambio', ...)`

### FASE 3 (Día 3 - 2-3 horas)
- [ ] Crear `presentation/managers/ModalManager.js`
- [ ] Crear `presentation/managers/NotificationsManager.js`
- [ ] Crear `presentation/controllers/FiltrosController.js`
- [ ] Crear `presentation/controllers/ProcesoCosturaController.js`

### FASE 4 (Día 4 - 1-2 horas)
- [ ] Crear `initializer.js`
- [ ] Limpiar Blade (sacar todos los scripts)
- [ ] Probar que todo funcione
- [ ] ✅ Done!

---

## 🧪 CÓMO PROBAR EN CONSOLA DEL NAVEGADOR

```javascript
// FASE 1
EstadoRecibo.desde('PENDIENTE') // ✅ Funciona
EstadoRecibo.desde('INVALIDO')  // ❌ Error: Estado no reconocido

// FASE 2
stateManager.getState()          // Retorna objeto con estado
stateManager.setRecibos([...])  // Actualiza recibos
stateManager.on('recibos:cambio', (r) => console.log(r)) // Escucha cambios

// FASE 3
const notif = new NotificationsManager();
notif.exito('Funciona!')

const modal = new ModalManager('addProcesoModal');
modal.open()
```

---

## 💡 TIPS IMPORTANTES

1. **Haz un archivo por entidad** - No juntes todo en 1 archivo
2. **Exporte con `index.js`** - Facilita imports
3. **Usa console.log estructurado** - `[Nombre] ✅/❌/📢 Mensaje`
4. **Prueba cada fase antes de seguir** - No acumules errores
5. **Mantén el Blade original** - Hasta que todo funcione

---

¿Por cuál fase empiezo? ¿Quieres que cree ya los archivos?

