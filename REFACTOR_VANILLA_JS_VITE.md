# 🔧 REFACTORIZACIÓN RECIBOS-COSTURA - JavaScript Vanilla + Vite + Alpine.js

> Tu stack actual: **Laravel + Vite + Alpine.js + JavaScript Vanilla**

---

## 📦 ESTRUCTURA DE CARPETAS PROPUESTA

```
resources/
├── js/
│   ├── app.js                 (punto de entrada Vite)
│   └── modules/
│       └── recibos-costura/
│           ├── domain/
│           │   ├── value-objects/
│           │   │   ├── EstadoRecibo.js
│           │   │   ├── AreaRecibocostura.js
│           │   │   ├── EncargadoProceso.js
│           │   │   └── FechaEntrega.js
│           │   ├── entities/
│           │   │   ├── ReciboCostura.js
│           │   │   └── ProcesoCostura.js
│           │   ├── specifications/
│           │   │   ├── Specification.js
│           │   │   ├── RecibosFiltrablesByArea.js
│           │   │   └── RecibosFiltrablesByEstado.js
│           │   └── errors/
│           │       └── DomainError.js
│           ├── application/
│           │   ├── dto/
│           │   │   ├── ReciboCosturaDTO.js
│           │   │   ├── ProcesoCosturaDTO.js
│           │   │   └── FiltroDTO.js
│           │   ├── use-cases/
│           │   │   ├── FiltrarRecibosUseCase.js
│           │   │   ├── AgregarProcesoUseCase.js
│           │   │   └── SuscribirARecibosUseCase.js
│           │   ├── services/
│           │   │   └── ReciboCosturaApplicationService.js
│           │   └── errors/
│           │       ├── ApplicationError.js
│           │       ├── FiltrarRecibosError.js
│           │       └── AgregarProcesoError.js
│           ├── infrastructure/
│           │   ├── http/
│           │   │   ├── ReciboCosturaHttpRepository.js
│           │   │   └── ProcessoCosturaHttpRepository.js
│           │   ├── state/
│           │   │   └── RecibosStateManager.js
│           │   └── events/
│           │       └── EventBus.js
│           ├── presentation/
│           │   ├── alpine-components/
│           │   │   ├── recibos-table.js
│           │   │   ├── filtros-modal.js
│           │   │   └── proceso-form.js
│           │   ├── managers/
│           │   │   ├── ModalManager.js
│           │   │   ├── NotificationsManager.js
│           │   │   └── DOMManager.js
│           │   └── view-models/
│           │       ├── RecibosTableViewModel.js
│           │       ├── FiltrosViewModel.js
│           │       └── ProcesoCosturaViewModel.js
│           ├── di/
│           │   └── container.js
│           └── index.js
├── css/
│   └── recibos-costura.css
└── views/
    └── registros/
        └── recibos-costura.blade.php (SIN scripts)
```

---

## 🎯 PASO 1: VALUE OBJECTS (Lógica de Dominio)

### EstadoRecibo.js
```javascript
// resources/js/modules/recibos-costura/domain/value-objects/EstadoRecibo.js

const ESTADOS = {
    PENDIENTE: 'PENDIENTE',
    EN_PROCESO: 'EN_PROCESO',
    COMPLETADO: 'COMPLETADO',
    RECHAZADO: 'RECHAZADO'
};

class EstadoRecibo {
    constructor(valor) {
        if (!Object.values(ESTADOS).includes(valor)) {
            throw new Error(`Estado inválido: ${valor}`);
        }
        this.valor = valor;
    }
    
    static desde(texto) {
        const estado = Object.values(ESTADOS).find(
            e => e === texto?.toUpperCase()
        );
        if (!estado) throw new Error(`Estado no reconocido: ${texto}`);
        return new EstadoRecibo(estado);
    }
    
    static PENDIENTE() { return new EstadoRecibo(ESTADOS.PENDIENTE); }
    static EN_PROCESO() { return new EstadoRecibo(ESTADOS.EN_PROCESO); }
    static COMPLETADO() { return new EstadoRecibo(ESTADOS.COMPLETADO); }
    static RECHAZADO() { return new EstadoRecibo(ESTADOS.RECHAZADO); }
    
    es(otro) {
        return this.valor === otro.valor;
    }
    
    esUno(...estados) {
        return estados.some(e => e.valor === this.valor);
    }
    
    toString() {
        return this.valor;
    }
    
    toJSON() {
        return this.valor;
    }
}

export { EstadoRecibo, ESTADOS };
```

### AreaRecibocostura.js
```javascript
// resources/js/modules/recibos-costura/domain/value-objects/AreaRecibocostura.js

const AREAS = {
    CORTE: 'CORTE',
    COSTURA: 'COSTURA',
    EMPAQUE: 'EMPAQUE',
    CALIDAD: 'CALIDAD'
};

const COLORES = {
    CORTE: '#8b5cf6',
    COSTURA: '#14b8a6',
    EMPAQUE: '#f97316',
    CALIDAD: '#ec4899'
};

class AreaRecibocostura {
    constructor(valor) {
        if (!Object.values(AREAS).includes(valor)) {
            throw new Error(`Área inválida: ${valor}`);
        }
        this.valor = valor;
    }
    
    static desde(texto) {
        const area = Object.values(AREAS).find(
            a => a === texto?.toUpperCase()
        );
        if (!area) throw new Error(`Área no reconocida: ${texto}`);
        return new AreaRecibocostura(area);
    }
    
    static CORTE() { return new AreaRecibocostura(AREAS.CORTE); }
    static COSTURA() { return new AreaRecibocostura(AREAS.COSTURA); }
    static EMPAQUE() { return new AreaRecibocostura(AREAS.EMPAQUE); }
    static CALIDAD() { return new AreaRecibocostura(AREAS.CALIDAD); }
    
    esIgual(otra) {
        return this.valor === otra.valor;
    }
    
    getColor() {
        return COLORES[this.valor];
    }
    
    toString() {
        return this.valor;
    }
    
    toJSON() {
        return this.valor;
    }
}

export { AreaRecibocostura, AREAS, COLORES };
```

### EncargadoProceso.js
```javascript
// resources/js/modules/recibos-costura/domain/value-objects/EncargadoProceso.js

class EncargadoProceso {
    constructor(nombre) {
        if (!nombre || typeof nombre !== 'string') {
            throw new Error('Encargado debe ser un string válido');
        }
        
        const nombreTrimmed = nombre.trim();
        if (nombreTrimmed.length < 2) {
            throw new Error('Nombre debe tener al menos 2 caracteres');
        }
        if (nombreTrimmed.length > 100) {
            throw new Error('Nombre muy largo (máx 100 caracteres)');
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
    
    toJSON() {
        return this.nombre;
    }
}

export { EncargadoProceso };
```

---

## 🔧 PASO 2: SPECIFICATIONS (Filtrado por Dominio)

### Specification.js
```javascript
// resources/js/modules/recibos-costura/domain/specifications/Specification.js

class Specification {
    isSatisfiedBy(candidate) {
        throw new Error('Debe implementar isSatisfiedBy()');
    }
    
    and(other) {
        return new AndSpecification(this, other);
    }
    
    or(other) {
        return new OrSpecification(this, other);
    }
    
    not() {
        return new NotSpecification(this);
    }
}

class AndSpecification extends Specification {
    constructor(spec1, spec2) {
        super();
        this.spec1 = spec1;
        this.spec2 = spec2;
    }
    
    isSatisfiedBy(candidate) {
        return this.spec1.isSatisfiedBy(candidate) && 
               this.spec2.isSatisfiedBy(candidate);
    }
}

class OrSpecification extends Specification {
    constructor(spec1, spec2) {
        super();
        this.spec1 = spec1;
        this.spec2 = spec2;
    }
    
    isSatisfiedBy(candidate) {
        return this.spec1.isSatisfiedBy(candidate) || 
               this.spec2.isSatisfiedBy(candidate);
    }
}

class NotSpecification extends Specification {
    constructor(spec) {
        super();
        this.spec = spec;
    }
    
    isSatisfiedBy(candidate) {
        return !this.spec.isSatisfiedBy(candidate);
    }
}

export { Specification };
```

### RecibosFiltrablesByArea.js
```javascript
// resources/js/modules/recibos-costura/domain/specifications/RecibosFiltrablesByArea.js

import { Specification } from './Specification.js';

class RecibosFiltrablesByArea extends Specification {
    constructor(areas) {
        super();
        this.areas = areas; // Array de AreaRecibocostura
    }
    
    isSatisfiedBy(recibo) {
        return this.areas.some(area => area.esIgual(recibo.area));
    }
}

export { RecibosFiltrablesByArea };
```

### RecibosFiltrablesByEstado.js
```javascript
// resources/js/modules/recibos-costura/domain/specifications/RecibosFiltrablesByEstado.js

import { Specification } from './Specification.js';

class RecibosFiltrablesByEstado extends Specification {
    constructor(estados) {
        super();
        this.estados = estados; // Array de EstadoRecibo
    }
    
    isSatisfiedBy(recibo) {
        return this.estados.some(estado => estado.es(recibo.estado));
    }
}

export { RecibosFiltrablesByEstado };
```

---

## 🛍️ PASO 3: DTOs (Transferencia de Datos)

### ReciboCosturaDTO.js
```javascript
// resources/js/modules/recibos-costura/application/dto/ReciboCosturaDTO.js

import { EstadoRecibo } from '../../domain/value-objects/EstadoRecibo.js';
import { AreaRecibocostura } from '../../domain/value-objects/AreaRecibocostura.js';

class ReciboCosturaDTO {
    constructor(datos) {
        this.id = datos.id;
        this.numeroRecibo = datos.numeroRecibo;
        this.cliente = datos.cliente;
        this.descripcion = datos.descripcion;
        this.cantidad = datos.cantidad;
        this.estado = datos.estado; // string
        this.area = datos.area; // string
        this.totalDias = datos.totalDias;
        this.fechaCreacion = datos.fechaCreacion; // ISO string
        this.fechaEstimadaEntrega = datos.fechaEstimadaEntrega; // ISO string
        this.encargado = datos.encargado;
        this.novedades = datos.novedades || null;
    }
    
    // Convertir DTO a Entidad (si lo necesitas)
    toReciboCostura() {
        return {
            id: this.id,
            numeroRecibo: this.numeroRecibo,
            cliente: this.cliente,
            descripcion: this.descripcion,
            cantidad: this.cantidad,
            estado: EstadoRecibo.desde(this.estado),
            area: AreaRecibocostura.desde(this.area),
            encargado: this.encargado,
            fechaCreacion: new Date(this.fechaCreacion),
            fechaEstimadaEntrega: new Date(this.fechaEstimadaEntrega)
        };
    }
    
    static fromJSON(json) {
        return new ReciboCosturaDTO(json);
    }
    
    toJSON() {
        return {
            id: this.id,
            numeroRecibo: this.numeroRecibo,
            cliente: this.cliente,
            descripcion: this.descripcion,
            cantidad: this.cantidad,
            estado: this.estado,
            area: this.area,
            totalDias: this.totalDias,
            fechaCreacion: this.fechaCreacion,
            fechaEstimadaEntrega: this.fechaEstimadaEntrega,
            encargado: this.encargado,
            novedades: this.novedades
        };
    }
}

export { ReciboCosturaDTO };
```

---

## 🎬 PASO 4: USE CASES (Lógica de Aplicación)

### FiltrarRecibosUseCase.js
```javascript
// resources/js/modules/recibos-costura/application/use-cases/FiltrarRecibosUseCase.js

import { EstadoRecibo } from '../../domain/value-objects/EstadoRecibo.js';
import { AreaRecibocostura } from '../../domain/value-objects/AreaRecibocostura.js';
import { RecibosFiltrablesByArea } from '../../domain/specifications/RecibosFiltrablesByArea.js';
import { RecibosFiltrablesByEstado } from '../../domain/specifications/RecibosFiltrablesByEstado.js';

class FiltrarRecibosUseCase {
    constructor(reciboRepository) {
        this.reciboRepository = reciboRepository;
    }
    
    async execute(comando) {
        try {
            console.log('[FiltrarRecibosUseCase] Filtrando con:', comando);
            
            // 1. Obtener todos los recibos
            const recibos = await this.reciboRepository.obtenerTodos();
            
            if (Object.keys(comando).length === 0) {
                return recibos;
            }
            
            // 2. Construir especificaciones
            let especificaciones = [];
            
            if (comando.areas && comando.areas.length > 0) {
                const areas = comando.areas.map(a => AreaRecibocostura.desde(a));
                especificaciones.push(new RecibosFiltrablesByArea(areas));
            }
            
            if (comando.estados && comando.estados.length > 0) {
                const estados = comando.estados.map(e => EstadoRecibo.desde(e));
                especificaciones.push(new RecibosFiltrablesByEstado(estados));
            }
            
            if (especificaciones.length === 0) {
                return recibos;
            }
            
            // 3. Combinar con AND
            let especificacionFinal = especificaciones[0];
            for (let i = 1; i < especificaciones.length; i++) {
                especificacionFinal = especificacionFinal.and(especificaciones[i]);
            }
            
            // 4. Filtrar
            const recibosFiltrrados = recibos.filter(r => 
                especificacionFinal.isSatisfiedBy(r.toReciboCostura())
            );
            
            console.log(`[FiltrarRecibosUseCase] Filtrado: ${recibosFiltrrados.length}/${recibos.length} recibos`);
            return recibosFiltrrados;
            
        } catch (error) {
            console.error('[FiltrarRecibosUseCase] Error:', error);
            throw new Error(`Error al filtrar recibos: ${error.message}`);
        }
    }
}

export { FiltrarRecibosUseCase };
```

### AgregarProcesoUseCase.js
```javascript
// resources/js/modules/recibos-costura/application/use-cases/AgregarProcesoUseCase.js

import { AreaRecibocostura } from '../../domain/value-objects/AreaRecibocostura.js';
import { EncargadoProceso } from '../../domain/value-objects/EncargadoProceso.js';

class AgregarProcesoUseCase {
    constructor(procesoCosturaRepository, eventBus) {
        this.procesoCosturaRepository = procesoCosturaRepository;
        this.eventBus = eventBus;
    }
    
    async execute(comando) {
        try {
            console.log('[AgregarProcesoUseCase] Agregando proceso:', comando);
            
            // 1. Validar (Value Objects lanzan excepciones)
            const area = AreaRecibocostura.desde(comando.area);
            const encargado = EncargadoProceso.desde(comando.encargado);
            
            // 2. Preparar payload
            const payload = {
                pedido_produccion_id: comando.pedidoId,
                prenda_id: comando.prendaId,
                area: area.toString(),
                encargado: encargado.toString()
            };
            
            // 3. Guardar
            const respuesta = await this.procesoCosturaRepository.guardar(payload);
            
            // 4. Publicar evento
            if (this.eventBus) {
                this.eventBus.emit('proceso.agregado', {
                    procesoCosturaId: respuesta.id,
                    pedidoId: comando.pedidoId,
                    prendaId: comando.prendaId,
                    area: area.toString(),
                    encargado: encargado.toString()
                });
            }
            
            return respuesta;
            
        } catch (error) {
            console.error('[AgregarProcesoUseCase] Error:', error);
            throw new Error(`Error al agregar proceso: ${error.message}`);
        }
    }
}

export { AgregarProcesoUseCase };
```

---

## 💾 PASO 5: REPOSITORIO (Acceso a Datos)

### ReciboCosturaHttpRepository.js
```javascript
// resources/js/modules/recibos-costura/infrastructure/http/ReciboCosturaHttpRepository.js

import { ReciboCosturaDTO } from '../../application/dto/ReciboCosturaDTO.js';

class ReciboCosturaHttpRepository {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }
    
    async obtenerTodos() {
        try {
            const response = await fetch(`${this.baseUrl}/recibos-costura`);
            if (!response.ok) throw new Error(response.statusText);
            
            const data = await response.json();
            return data.map(item => ReciboCosturaDTO.fromJSON(item));
            
        } catch (error) {
            console.error('[ReciboCosturaHttpRepository] Error:', error);
            throw error;
        }
    }
    
    async obtenerPorId(id) {
        try {
            const response = await fetch(`${this.baseUrl}/recibos-costura/${id}`);
            if (!response.ok) throw new Error(response.statusText);
            
            const data = await response.json();
            return ReciboCosturaDTO.fromJSON(data);
            
        } catch (error) {
            console.error('[ReciboCosturaHttpRepository] Error:', error);
            throw error;
        }
    }
}

export { ReciboCosturaHttpRepository };
```

### ProcessoCosturaHttpRepository.js
```javascript
// resources/js/modules/recibos-costura/infrastructure/http/ProcessoCosturaHttpRepository.js

class ProcessoCosturaHttpRepository {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }
    
    async guardar(datos) {
        try {
            const response = await fetch(`${this.baseUrl}/procesos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(datos)
            });
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || response.statusText);
            }
            
            return await response.json();
            
        } catch (error) {
            console.error('[ProcessoCosturaHttpRepository] Error:', error);
            throw error;
        }
    }
}

export { ProcessoCosturaHttpRepository };
```

---

## 🎨 PASO 6: STATE MANAGER (Estado Global Simple)

### RecibosStateManager.js
```javascript
// resources/js/modules/recibos-costura/infrastructure/state/RecibosStateManager.js

class RecibosStateManager {
    constructor() {
        this.recibos = [];
        this.filtrosActivos = {};
        this.cargando = false;
        this.error = null;
        this.listeners = new Map();
    }
    
    // Observables simples
    on(evento, callback) {
        if (!this.listeners.has(evento)) {
            this.listeners.set(evento, []);
        }
        this.listeners.get(evento).push(callback);
    }
    
    off(evento, callback) {
        if (this.listeners.has(evento)) {
            const callbacks = this.listeners.get(evento);
            const index = callbacks.indexOf(callback);
            if (index > -1) callbacks.splice(index, 1);
        }
    }
    
    emitir(evento, datos) {
        console.log(`[StateManager] Evento: ${evento}`, datos);
        this.listeners.get(evento)?.forEach(cb => cb(datos));
    }
    
    // Estado
    setRecibos(recibos) {
        this.recibos = recibos;
        this.emitir('recibos-cambio', recibos);
    }
    
    getRecibos() {
        return [...this.recibos];
    }
    
    setFiltrosActivos(filtros) {
        this.filtrosActivos = filtros;
        this.emitir('filtros-cambio', filtros);
    }
    
    getFiltrosActivos() {
        return { ...this.filtrosActivos };
    }
    
    setCargando(valor) {
        this.cargando = valor;
        this.emitir('cargando-cambio', valor);
    }
    
    estaCargando() {
        return this.cargando;
    }
    
    setError(error) {
        this.error = error;
        this.emitir('error-cambio', error);
    }
    
    getError() {
        return this.error;
    }
    
    limpiar() {
        this.recibos = [];
        this.filtrosActivos = {};
        this.error = null;
        this.emitir('limpiar');
    }
}

export { RecibosStateManager };
```

---

## 🔔 PASO 7: EVENT BUS (Notificaciones entre módulos)

### EventBus.js
```javascript
// resources/js/modules/recibos-costura/infrastructure/events/EventBus.js

class EventBus {
    constructor() {
        this.eventos = new Map();
    }
    
    on(nombre, callback) {
        if (!this.eventos.has(nombre)) {
            this.eventos.set(nombre, []);
        }
        this.eventos.get(nombre).push(callback);
        
        // Retornar función para desuscribirse
        return () => this.off(nombre, callback);
    }
    
    off(nombre, callback) {
        if (this.eventos.has(nombre)) {
            const callbacks = this.eventos.get(nombre);
            const index = callbacks.indexOf(callback);
            if (index > -1) callbacks.splice(index, 1);
        }
    }
    
    emit(nombre, datos) {
        console.log(`[EventBus] ${nombre}`, datos);
        this.eventos.get(nombre)?.forEach(cb => cb(datos));
    }
    
    async emitAsync(nombre, datos) {
        const callbacks = this.eventos.get(nombre) || [];
        for (const cb of callbacks) {
            await cb(datos);
        }
    }
}

export { EventBus };
```

---

## 🎮 PASO 8: MANAGERS (Acceso a DOM)

### ModalManager.js
```javascript
// resources/js/modules/recibos-costura/presentation/managers/ModalManager.js

class ModalManager {
    constructor(modalId) {
        this.modalId = modalId;
    }
    
    open() {
        const modal = document.getElementById(this.modalId);
        if (!modal) {
            console.warn(`[ModalManager] Modal ${this.modalId} no encontrado`);
            return;
        }
        modal.classList.add('show');
        modal.style.display = 'flex';
    }
    
    close() {
        const modal = document.getElementById(this.modalId);
        if (modal) {
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

### NotificationsManager.js
```javascript
// resources/js/modules/recibos-costura/presentation/managers/NotificationsManager.js

const TIPOS = {
    SUCCESS: 'success',
    ERROR: 'error',
    INFO: 'info',
    WARNING: 'warning'
};

class NotificationsManager {
    constructor(containerId = 'toastContainer') {
        this.containerId = containerId;
    }
    
    mostrar(mensaje, tipo = TIPOS.INFO, titulo = '') {
        const container = document.getElementById(this.containerId) || 
                         this.crearContenedor();
        
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        toast.style.pointerEvents = 'auto';
        
        const icono = this.obtenerIcono(tipo);
        toast.innerHTML = `
            <div class="toast-icon">${icono}</div>
            <div class="toast-content">
                ${titulo ? `<div class="toast-title">${titulo}</div>` : ''}
                <div class="toast-message">${mensaje}</div>
            </div>
            <button class="toast-close" onclick="this.parentNode.remove()">×</button>
        `;
        
        container.appendChild(toast);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            toast.classList.add('removing');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    exito(mensaje, titulo = 'Éxito') {
        this.mostrar(mensaje, TIPOS.SUCCESS, titulo);
    }
    
    error(mensaje, titulo = 'Error') {
        this.mostrar(mensaje, TIPOS.ERROR, titulo);
    }
    
    info(mensaje, titulo = 'Información') {
        this.mostrar(mensaje, TIPOS.INFO, titulo);
    }
    
    advertencia(mensaje, titulo = 'Advertencia') {
        this.mostrar(mensaje, TIPOS.WARNING, titulo);
    }
    
    private crearContenedor() {
        const container = document.createElement('div');
        container.id = this.containerId;
        container.className = 'toast-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999999;
            pointer-events: none;
        `;
        document.body.appendChild(container);
        return container;
    }
    
    private obtenerIcono(tipo) {
        const iconos = {
            success: '✓',
            error: '✕',
            info: 'ℹ',
            warning: '⚠'
        };
        return iconos[tipo] || '•';
    }
}

export { NotificationsManager, TIPOS };
```

---

## 🧩 PASO 9: INYECCIÓN DE DEPENDENCIAS

### container.js
```javascript
// resources/js/modules/recibos-costura/di/container.js

import { ReciboCosturaHttpRepository } from '../infrastructure/http/ReciboCosturaHttpRepository.js';
import { ProcessoCosturaHttpRepository } from '../infrastructure/http/ProcessoCosturaHttpRepository.js';
import { RecibosStateManager } from '../infrastructure/state/RecibosStateManager.js';
import { EventBus } from '../infrastructure/events/EventBus.js';
import { FiltrarRecibosUseCase } from '../application/use-cases/FiltrarRecibosUseCase.js';
import { AgregarProcesoUseCase } from '../application/use-cases/AgregarProcesoUseCase.js';
import { ModalManager } from '../presentation/managers/ModalManager.js';
import { NotificationsManager } from '../presentation/managers/NotificationsManager.js';

class Container {
    constructor() {
        this.servicios = new Map();
        this.registrarServicios();
    }
    
    registrarServicios() {
        // Infraestructura
        this.registrar('reciboRepository', new ReciboCosturaHttpRepository());
        this.registrar('procesoRepository', new ProcessoCosturaHttpRepository());
        this.registrar('stateManager', new RecibosStateManager());
        this.registrar('eventBus', new EventBus());
        
        // Casos de Uso
        this.registrar('filtrarRecibosUseCase', 
            new FiltrarRecibosUseCase(this.get('reciboRepository'))
        );
        this.registrar('agregarProcesoUseCase',
            new AgregarProcesoUseCase(
                this.get('procesoRepository'),
                this.get('eventBus')
            )
        );
        
        // Managers
        this.registrar('modalManager', new ModalManager('addProcesoModal'));
        this.registrar('notificationsManager', new NotificationsManager());
    }
    
    registrar(nombre, instancia) {
        this.servicios.set(nombre, instancia);
    }
    
    get(nombre) {
        const servicio = this.servicios.get(nombre);
        if (!servicio) {
            throw new Error(`Servicio "${nombre}" no registrado`);
        }
        return servicio;
    }
}

// Instancia global (sigleton)
const container = new Container();

export { Container, container };
```

---

## ⚙️ PASO 10: APLICACIÓN BLADE (SIN scripts)

```blade
<!-- resources/views/registros/recibos-costura.blade.php -->
@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Componente de tabla sin lógica inline -->
            <x-recibos.recibos-costura-table :recibos="$recibos" />
        </div>
    </div>
</div>

<!-- Contenedor para modales -->
<div id="modals-container"></div>

<!-- Contenedor para notificaciones -->
<div class="toast-container" id="toastContainer"></div>
@endsection

@push('scripts')
<!-- ÚNICA LÍNEA DE JAVASCRIPT -->
<script type="module">
    import { RecibosInitializer } from '{{ asset('js/modules/recibos-costura/initializer.js') }}';
    new RecibosInitializer().init();
</script>
@endpush
```

---

## 🚀 PASO 11: INICIALIZADOR

### initializer.js
```javascript
// resources/js/modules/recibos-costura/initializer.js

import { container } from './di/container.js';
import { FiltrosViewController } from './presentation/controllers/FiltrosViewController.js';
import { ProcesoCosturaViewController } from './presentation/controllers/ProcesoCosturaViewController.js';

class RecibosInitializer {
    async init() {
        console.log('[Recibos] Inicializando módulo...');
        
        try {
            // 1. Cargar datos iniciales
            const stateManager = container.get('stateManager');
            const filtrarUseCase = container.get('filtrarRecibosUseCase');
            
            console.log('[Recibos] Cargando recibos...');
            const recibos = await filtrarUseCase.execute({});
            stateManager.setRecibos(recibos);
            
            // 2. Inicializar controllers
            new FiltrosViewController();
            new ProcesoCosturaViewController();
            
            // 3. Configurar listeners de eventos
            this.configurarEventos();
            
            console.log('[Recibos] Módulo inicializado ✅');
            
        } catch (error) {
            console.error('[Recibos] Error al inicializar:', error);
            const notif = container.get('notificationsManager');
            notif.error('Error al cargar recibos de costura');
        }
    }
    
    configurarEventos() {
        const stateManager = container.get('stateManager');
        const eventBus = container.get('eventBus');
        const notif = container.get('notificationsManager');
        
        // Escuchar cambios de estado
        stateManager.on('error-cambio', (error) => {
            if (error) notif.error(error);
        });
        
        // Escuchar eventos de negocio
        eventBus.on('proceso.agregado', (datos) => {
            console.log('[Recibos] Proceso agregado:', datos);
            notif.exito(`Proceso agregado por ${datos.encargado}`);
            // Aquí podrías recargar la tabla
        });
    }
}

export { RecibosInitializer };
```

---

## 🎯 ESTRUCTURA FINAL DE IMPORTS

```javascript
// En tu app.js (punto de entrada Vite)
import './modules/recibos-costura/index.js';

// Eso carga el módulo automáticamente mediante vite
```

---

## 📝 COMPARACIÓN ANTES vs DESPUÉS

| Aspecto | ❌ Antes | ✅ Después |
|---------|---------|-----------|
| **Líneas en Blade** | 2000+ | < 10 |
| **Archivos JS** | 1 (inline) | 25+ (modular) |
| **Testabilidad** | 0% | 90%+ |
| **State global** | window.* | StateManager |
| **Lógica de negocio** | DOM mezclada | Domain models |
| **Mantenibilidad** | Imposible | Fácil |

---

## ✅ PRÓXIMOS PASOS

1. **Crear la estructura de carpetas**
2. **Implementar Value Objects** (EstadoRecibo, AreaRecibocostura, etc.)
3. **Implementar Specifications** para filtrado
4. **Crear Repositorios HTTP**
5. **Implementar Use Cases**
6. **Crear State Manager y EventBus**
7. **Implementar Managers** (Modal, Notificaciones)
8. **Controllers** para orquestar todo
9. **Actualizar Blade** (eliminar scripts)
10. **Testing**

