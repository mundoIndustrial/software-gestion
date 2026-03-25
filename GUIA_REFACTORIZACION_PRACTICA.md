# 🎯 SOLUCIONES PRÁCTICAS PARA RECIBOS-COSTURA.BLADE.PHP

## Resumen Rápido de Cambios Necesarios

### Lo que DEBE cambiar

1. **ELIMINAR 2000+ líneas de JavaScript de la vista**
2. **Crear módulos TypeScript/JavaScript aislados**
3. **Implementar State Management (no global window)**
4. **Separar Lógica de Negocio de Presentación**
5. **Usar Value Objects en lugar de strings/números crudos**

---

## 🏗️ ESTRUCTURA DE CARPETAS PROPUESTA

```
resources/
├── js/
│   ├── modules/
│   │   └── recibos-costura/
│   │       ├── application/
│   │       │   ├── dto/
│   │       │   │   ├── FiltroDTO.ts
│   │       │   │   ├── ProcesoCosturaDTO.ts
│   │       │   │   └── ReciboCosturaDTO.ts
│   │       │   ├── use-cases/
│   │       │   │   ├── FiltrarRecibosUseCase.ts
│   │       │   │   ├── AgregarProcesoUseCase.ts
│   │       │   │   └── SuscribirARecibosAprobadosUseCase.ts
│   │       │   └── errors/
│   │       │       ├── FiltrarRecibosError.ts
│   │       │       └── AgregarProcesoError.ts
│   │       ├── domain/
│   │       │   ├── models/
│   │       │   │   ├── ReciboCostura.ts
│   │       │   │   ├── ProcesoCostura.ts
│   │       │   │   └── AreaRecibocostura.ts
│   │       │   ├── value-objects/
│   │       │   │   ├── AreaRecibocostura.ts
│   │       │   │   ├── EncargadoProceso.ts
│   │       │   │   ├── EstadoRecibo.ts
│   │       │   │   └── FechaEntrega.ts
│   │       │   ├── specifications/
│   │       │   │   ├── RecibosFiltrablesByArea.ts
│   │       │   │   └── RecibosFiltrablesByDescripcion.ts
│   │       │   ├── events/
│   │       │   │   └── ProcesoAgregado.ts
│   │       │   └── repositories/
│   │       │       └── ReciboCosturaRepository.ts (interface)
│   │       ├── infrastructure/
│   │       │   ├── http/
│   │       │   │   └── ReciboCosturaHttpRepository.ts
│   │       │   ├── notifications/
│   │       │   │   ├── EventBusNotifications.ts
│   │       │   │   └── RealtimeSubscriber.ts
│   │       │   └── persistence/
│   │       │       └── ActiveRecibosFilter.ts
│   │       ├── presentation/
│   │       │   ├── view-models/
│   │       │   │   ├── RecibosTableViewModel.ts
│   │       │   │   ├── ProcesoCosturaViewModel.ts
│   │       │   │   └── FiltrosViewModel.ts
│   │       │   ├── managers/
│   │       │   │   ├── ModalManager.ts
│   │       │   │   ├── NotificationsManager.ts
│   │       │   │   └── DropdownManager.ts
│   │       │   ├── components/
│   │       │   │   ├── RecibosTable.ts
│   │       │   │   ├── FiltroModal.ts
│   │       │   │   ├── ProcesoCosturaForm.ts
│   │       │   │   └── ToastNotification.ts
│   │       ├── di/
│   │       │   └── register-dependencies.ts
│   │       └── index.ts
├── views/
│   └── registros/
│       └── recibos-costura.blade.php (SIN scripts)
└── styles/
    └── recibos-costura.css
```

---

## 📦 IMPLEMENTACIÓN PASO A PASO

### PASO 1: Value Objects para Control de Dominio

```typescript
// resources/js/modules/recibos-costura/domain/value-objects/EstadoRecibo.ts
export enum ESTADO_RECIBO {
    PENDIENTE = 'PENDIENTE',
    EN_PROCESO = 'EN_PROCESO',
    COMPLETADO = 'COMPLETADO',
    RECHAZADO = 'RECHAZADO'
}

export class EstadoRecibo {
    constructor(readonly valor: ESTADO_RECIBO) {
        this.validar();
    }
    
    private validar(): void {
        if (!Object.values(ESTADO_RECIBO).includes(this.valor)) {
            throw new Error(`Estado inválido: ${this.valor}`);
        }
    }
    
    static desde(texto: string): EstadoRecibo {
        const estado = Object.values(ESTADO_RECIBO).find(
            e => e === texto.toUpperCase()
        );
        if (!estado) throw new Error(`Estado no reconocido: ${texto}`);
        return new EstadoRecibo(estado);
    }
    
    es(otro: EstadoRecibo): boolean {
        return this.valor === otro.valor;
    }
    
    esUno(...estados: ESTADO_RECIBO[]): boolean {
        return estados.includes(this.valor);
    }
    
    toString(): string {
        return this.valor;
    }
}
```

```typescript
// resources/js/modules/recibos-costura/domain/value-objects/AreaRecibocostura.ts
export enum AREA_COSTURA {
    CORTE = 'CORTE',
    COSTURA = 'COSTURA',
    EMPAQUE = 'EMPAQUE',
    CALIDAD = 'CALIDAD'
}

export class AreaRecibocostura {
    constructor(readonly valor: AREA_COSTURA) {
        this.validar();
    }
    
    private validar(): void {
        if (!Object.values(AREA_COSTURA).includes(this.valor)) {
            throw new Error(`Área inválida: ${this.valor}`);
        }
    }
    
    static desde(texto: string): AreaRecibocostura {
        const area = Object.values(AREA_COSTURA).find(
            a => a === texto.toUpperCase()
        );
        if (!area) throw new Error(`Área no reconocida: ${texto}`);
        return new AreaRecibocostura(area);
    }
    
    esIgual(otra: AreaRecibocostura): boolean {
        return this.valor === otra.valor;
    }
    
    getColor(): string {
        const colores: Record<AREA_COSTURA, string> = {
            CORTE: '#8b5cf6',
            COSTURA: '#14b8a6',
            EMPAQUE: '#f97316',
            CALIDAD: '#ec4899'
        };
        return colores[this.valor];
    }
    
    toString(): string {
        return this.valor;
    }
}
```

### PASO 2: Especificaciones para Filtrado

```typescript
// resources/js/modules/recibos-costura/domain/specifications/Specification.ts
export abstract class Specification<T> {
    abstract isSatisfiedBy(candidate: T): boolean;
    
    and(other: Specification<T>): Specification<T> {
        return new AndSpecification(this, other);
    }
    
    or(other: Specification<T>): Specification<T> {
        return new OrSpecification(this, other);
    }
    
    not(): Specification<T> {
        return new NotSpecification(this);
    }
}

class AndSpecification<T> extends Specification<T> {
    constructor(private spec1: Specification<T>, private spec2: Specification<T>) {
        super();
    }
    
    isSatisfiedBy(candidate: T): boolean {
        return this.spec1.isSatisfiedBy(candidate) && this.spec2.isSatisfiedBy(candidate);
    }
}

// Usar:
// const especificacion = byArea.and(byEstado).and(byFecha);
// const recibos = todoRecibos.filter(r => especificacion.isSatisfiedBy(r));
```

```typescript
// resources/js/modules/recibos-costura/domain/specifications/RecibosFiltrablesByArea.ts
export class RecibosFiltrablesByArea extends Specification<ReciboCostura> {
    constructor(private areas: AreaRecibocostura[]) {
        super();
    }
    
    isSatisfiedBy(recibo: ReciboCostura): boolean {
        return this.areas.some(area => area.esIgual(recibo.area));
    }
}

export class RecibosFiltrablesByEstado extends Specification<ReciboCostura> {
    constructor(private estados: EstadoRecibo[]) {
        super();
    }
    
    isSatisfiedBy(recibo: ReciboCostura): boolean {
        return this.estados.some(estado => estado.es(recibo.estado));
    }
}

export class RecibosFiltrablesByDescripcion extends Specification<ReciboCostura> {
    constructor(private descripcionBuscada: string) {
        super();
    }
    
    isSatisfiedBy(recibo: ReciboCostura): boolean {
        return recibo.descripcion.toLowerCase().includes(
            this.descripcionBuscada.toLowerCase()
        );
    }
}

// Uso:
// const areas = [AreaRecibocostura.desde('COSTURA')];
// const estados = [EstadoRecibo.desde('PENDIENTE')];
// const especificacion = new RecibosFiltrablesByArea(areas)
//     .and(new RecibosFiltrablesByEstado(estados));
```

### PASO 3: DTOs para Transferencia de Datos

```typescript
// resources/js/modules/recibos-costura/application/dto/ReciboCosturaDTO.ts
export interface ReciboCosturaDTO {
    id: string;
    numeroRecibo: string;
    cliente: string;
    descripcion: string;
    cantidad: number;
    estado: string;
    area: string;
    totalDias: number;
    fechaCreacion: string;
    fechaEstimadaEntrega: string;
    encargado: string;
    novedades?: string;
}

export class ReciboCosturaMapper {
    static toDTO(recibo: ReciboCostura): ReciboCosturaDTO {
        return {
            id: recibo.id.toString(),
            numeroRecibo: recibo.numeroRecibo,
            cliente: recibo.cliente,
            descripcion: recibo.descripcion,
            cantidad: recibo.cantidad,
            estado: recibo.estado.toString(),
            area: recibo.area.toString(),
            totalDias: recibo.calcularTotalDias(),
            fechaCreacion: recibo.fechaCreacion.toISOString(),
            fechaEstimadaEntrega: recibo.fechaEstimadaEntrega.toISOString(),
            encargado: recibo.encargado
        };
    }
    
    static fromDTO(dto: ReciboCosturaDTO): ReciboCostura {
        return new ReciboCostura(
            dto.id,
            dto.numeroRecibo,
            dto.cliente,
            dto.descripcion,
            dto.cantidad,
            EstadoRecibo.desde(dto.estado),
            AreaRecibocostura.desde(dto.area),
            new Date(dto.fechaCreacion),
            new Date(dto.fechaEstimadaEntrega),
            dto.encargado
        );
    }
}
```

### PASO 4: Use Cases

```typescript
// resources/js/modules/recibos-costura/application/use-cases/FiltrarRecibosUseCase.ts
export interface FiltrarRecibosCommand {
    areas?: string[];
    estados?: string[];
    descripcion?: string;
    cliente?: string;
}

export class FiltrarRecibosUseCase {
    constructor(private reciboRepository: ReciboCosturaRepository) {}
    
    async execute(comando: FiltrarRecibosCommand): Promise<ReciboCosturaDTO[]> {
        // 1. Obtener todos los recibos
        const recibos = await this.reciboRepository.obtenerTodos();
        
        // 2. Construir especificaciones
        const especificaciones: Specification<ReciboCostura>[] = [];
        
        if (comando.areas && comando.areas.length > 0) {
            const areas = comando.areas.map(a => AreaRecibocostura.desde(a));
            especificaciones.push(new RecibosFiltrablesByArea(areas));
        }
        
        if (comando.estados && comando.estados.length > 0) {
            const estados = comando.estados.map(e => EstadoRecibo.desde(e));
            especificaciones.push(new RecibosFiltrablesByEstado(estados));
        }
        
        if (comando.descripcion) {
            especificaciones.push(
                new RecibosFiltrablesByDescripcion(comando.descripcion)
            );
        }
        
        // 3. Combinar especificaciones
        let especificacionFinal = especificaciones[0];
        for (let i = 1; i < especificaciones.length; i++) {
            especificacionFinal = especificacionFinal.and(especificaciones[i]);
        }
        
        // 4. Filtrar
        const recibosFiltrrados = especificacionFinal 
            ? recibos.filter(r => especificacionFinal.isSatisfiedBy(r))
            : recibos;
        
        // 5. Retornar como DTOs
        return recibosFiltrrados.map(r => ReciboCosturaMapper.toDTO(r));
    }
}
```

```typescript
// resources/js/modules/recibos-costura/application/use-cases/AgregarProcesoUseCase.ts
export interface AgregarProcesoCommand {
    pedidoId: string;
    prendaId: string;
    area: string;
    encargado: string;
}

export interface AgregarProcesoResponse {
    procesoCosturaId: string;
    mensaje: string;
}

export class AgregarProcesoUseCase {
    constructor(
        private procesoCosturaRepository: ProcessoCosturaRepository,
        private eventPublisher: EventPublisher
    ) {}
    
    async execute(comando: AgregarProcesoCommand): Promise<AgregarProcesoResponse> {
        // 1. Validar (Value Objects lanzan excepciones)
        const area = AreaRecibocostura.desde(comando.area);
        const encargado = EncargadoProceso.desde(comando.encargado);
        
        // 2. Crear Agregado
        const procesoCostura = ProcesoCostura.crear(
            comando.pedidoId,
            comando.prendaId,
            area,
            encargado
        );
        
        // 3. Guardar
        await this.procesoCosturaRepository.guardar(procesoCostura);
        
        // 4. Publicar evento
        const evento = procesoCostura.obtenerEventosDominio()[0];
        await this.eventPublisher.publicar(evento);
        
        return {
            procesoCosturaId: procesoCostura.id.toString(),
            mensaje: 'Proceso agregado correctamente'
        };
    }
}
```

### PASO 5: ViewModels (Gestión de Estado para UI)

```typescript
// resources/js/modules/recibos-costura/presentation/view-models/RecibosTableViewModel.ts
export class RecibosTableViewModel {
    private recibos: ReciboCosturaDTO[] = [];
    private filtrosActivos: FiltrarRecibosCommand = {};
    private cargando: boolean = false;
    private error: string | null = null;
    
    private listeners: Map<string, Function[]> = new Map();
    
    constructor(private filtrarRecibosUseCase: FiltrarRecibosUseCase) {}
    
    // Observable pattern simple
    on(evento: string, listener: Function): void {
        if (!this.listeners.has(evento)) {
            this.listeners.set(evento, []);
        }
        this.listeners.get(evento)!.push(listener);
    }
    
    private emitir(evento: string, datos?: any): void {
        this.listeners.get(evento)?.forEach(listener => listener(datos));
    }
    
    // Acceso a estado
    obtenerRecibos(): ReciboCosturaDTO[] {
        return [...this.recibos];
    }
    
    obtenerFiltrosActivos(): FiltrarRecibosCommand {
        return { ...this.filtrosActivos };
    }
    
    estaCargando(): boolean {
        return this.cargando;
    }
    
    obtenerError(): string | null {
        return this.error;
    }
    
    // Acciones
    async aplicarFiltros(comando: FiltrarRecibosCommand): Promise<void> {
        this.cargando = true;
        this.error = null;
        this.emitir('cambio');
        
        try {
            this.recibos = await this.filtrarRecibosUseCase.execute(comando);
            this.filtrosActivos = comando;
            this.emitir('recibosCambiados', this.recibos);
        } catch (error) {
            this.error = error instanceof Error ? error.message : 'Error desconocido';
            this.emitir('error', this.error);
        } finally {
            this.cargando = false;
            this.emitir('cambio');
        }
    }
    
    limpiarFiltros(): void {
        this.recibos = [];
        this.filtrosActivos = {};
        this.error = null;
        this.emitir('recibosCambiados', []);
    }
}
```

### PASO 6: Managers para UI (Acceso al DOM)

```typescript
// resources/js/modules/recibos-costura/presentation/managers/ModalManager.ts
export class ModalManager {
    constructor(private modalId: string) {}
    
    open(): void {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.classList.add('show');
            modal.style.display = 'flex';
        }
    }
    
    close(): void {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.classList.remove('show');
            modal.style.display = 'none';
        }
    }
    
    isOpen(): boolean {
        const modal = document.getElementById(this.modalId);
        return modal?.classList.contains('show') ?? false;
    }
    
    toggle(): void {
        this.isOpen() ? this.close() : this.open();
    }
    
    setContent(html: string): void {
        const modal = document.getElementById(this.modalId);
        if (modal) {
            modal.innerHTML = html;
        }
    }
    
    getElement(): HTMLElement | null {
        return document.getElementById(this.modalId);
    }
}

// Uso:
const modalProceso = new ModalManager('addProcesoModal');
modalProceso.open();
```

```typescript
// resources/js/modules/recibos-costura/presentation/managers/NotificationsManager.ts
export enum NotificationType {
    SUCCESS = 'success',
    ERROR = 'error',
    INFO = 'info',
    WARNING = 'warning'
}

export class NotificationsManager {
    private containerId = 'toastContainer';
    
    mostrar(
        mensaje: string,
        tipo: NotificationType = NotificationType.INFO,
        titulo?: string
    ): void {
        const container = document.getElementById(this.containerId) || 
                         this.crearContenedor();
        
        const toast = document.createElement('div');
        toast.className = `toast ${tipo}`;
        
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
    
    private crearContenedor(): HTMLElement {
        const container = document.createElement('div');
        container.id = this.containerId;
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }
    
    private obtenerIcono(tipo: NotificationType): string {
        const iconos: Record<NotificationType, string> = {
            success: '✓',
            error: '✕',
            info: 'ℹ',
            warning: '⚠'
        };
        return iconos[tipo];
    }
    
    limpiar(): void {
        const container = document.getElementById(this.containerId);
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Uso:
const notificaciones = new NotificationsManager();
notificaciones.mostrar('Proceso agregado correctamente', NotificationType.SUCCESS, 'Éxito');
```

### PASO 7: Inicialización en Blade

```blade
<!-- resources/views/registros/recibos-costura.blade.php -->
@extends('layouts.app')

@section('title', 'Recibos de Costura')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <x-recibos.recibos-costura-table />
        </div>
    </div>
</div>

<!-- Contenedor para modales -->
<div id="modals-container"></div>

<!-- Contenedor para notificaciones -->
<div class="toast-container" id="toastContainer"></div>

@endsection

@push('scripts')
<script type="module">
    // Importar DI Container
    import { registerDependencies } from '{{ asset('js/modules/recibos-costura/di/register-dependencies') }}';
    import { RecibosTableViewController } from '{{ asset('js/modules/recibos-costura/presentation/controllers/RecibosTableViewController') }}';
    
    // 1. Registrar dependencias
    const container = registerDependencies();
    
    // 2. Obtener ViewController
    const controller = container.get(RecibosTableViewController);
    
    // 3. Inicializar
    controller.inicializar();
</script>
@endpush
```

### PASO 8: Inyección de Dependencias

```typescript
// resources/js/modules/recibos-costura/di/register-dependencies.ts
export class Container {
    private servicios: Map<string, any> = new Map();
    
    registrar(nombre: string, instancia: any): void {
        this.servicios.set(nombre, instancia);
    }
    
    get(nombre: string): any {
        return this.servicios.get(nombre);
    }
}

export function registerDependencies(): Container {
    const container = new Container();
    
    // Infrastructure
    const httpRepository = new ReciboCosturaHttpRepository();
    container.registrar('reciboRepository', httpRepository);
    
    // Application
    const filtrarUseCase = new FiltrarRecibosUseCase(httpRepository);
    const agregarProcesoUseCase = new AgregarProcesoUseCase(
        new ProcessoCosturaHttpRepository(),
        new EventPublisher()
    );
    
    container.registrar('filtrarRecibosUseCase', filtrarUseCase);
    container.registrar('agregarProcesoUseCase', agregarProcesoUseCase);
    
    // Presentation
    const reciboTableViewModel = new RecibosTableViewModel(filtrarUseCase);
    const procesoCosturaViewModel = new ProcesoCosturaViewModel(agregarProcesoUseCase);
    
    container.registrar('reciboTableViewModel', reciboTableViewModel);
    container.registrar('procesoCosturaViewModel', procesoCosturaViewModel);
    
    // Managers
    container.registrar('notificationsManager', new NotificationsManager());
    container.registrar('modalManager', new ModalManager('addProcesoModal'));
    
    return container;
}
```

---

##  CHECKLIST DE MIGRACIÓN

- [ ] **Paso 1:** Crear estructura de carpetas
- [ ] **Paso 2:** Implementar Value Objects (EstadoRecibo, AreaRecibocostura, etc.)
- [ ] **Paso 3:** Implementar Specifications para filtrado
- [ ] **Paso 4:** Crear DTOs y Mappers
- [ ] **Paso 5:** Implementar Use Cases
- [ ] **Paso 6:** Crear ViewModels
- [ ] **Paso 7:** Implementar Managers
- [ ] **Paso 8:** Configurar Inyección de Dependencias
- [ ] **Paso 9:** Actualizar Blade (eliminar scripts)
- [ ] **Paso 10:** Tests unitarios para cada capa
- [ ] **Paso 11:** Testing de integración
- [ ] **Paso 12:** Dejar recibos-costura.blade.php limpia

---

## 🎉 RESULTADO FINAL

**Antes:** 
- 2000+ líneas de código en un archivo Blade
- Sin tests
- Imposible de mantener
- Acoplamiento total

**Después:**
- Código modular y testeable
- Bajo acoplamiento
- Fácil de mantener
- Reutilizable

