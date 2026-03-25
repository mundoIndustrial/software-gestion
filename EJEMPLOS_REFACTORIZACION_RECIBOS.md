# 🔧 EJEMPLOS DE REFACTORIZACIÓN - recibos-costura.blade.php

> Antes ( Actual) vs Después ( Propuesto)

---

## 1. FILTRADO DE RECIBOS

###  ANTES - Lógica mezclada en Blade.js

```javascript
function getDynamicFilterOptions(filterType) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) {
        console.warn('[Filtros] No se encontró la tabla');
        return [];
    }
    
    const options = new Set();
    const columnIndex = getColumnIndex(filterType);
    
    if (columnIndex === -1) return [];
    
    const rows = tbody.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (cells.length > columnIndex) {
            let cellText = '';
            if (filterType === 'descripcion') {
                cellText = cells[columnIndex].getAttribute('data-descripcion-detallada') || '';
            } else {
                cellText = cells[columnIndex].textContent.trim();
            }
            options.add(cellText);
        }
    });
    
    return Array.from(options).sort();
}

window.applyFilters = function() {
    const modal = document.getElementById('filterModal');
    const filterType = modal.getAttribute('data-filter-type');
    const tbody = document.getElementById('tablaRecibosBody');
    const rows = tbody.querySelectorAll('tr');
    const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
    const selectedValues = Array.from(checkboxes).map(cb => cb.value);
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const cellText = cells[columnIndex].textContent.trim();
        const isVisible = selectedValues.some(v => cellText.includes(v));
        row.style.display = isVisible ? '' : 'none';
    });
};
```

###  DESPUÉS - Con DDD

**1. Value Object para Filtro:**
```typescript
// src/Domain/Recibos/FiltroRecibos.ts
export enum TipoFiltro {
    DESCRIPCION = 'descripcion',
    CLIENTE = 'cliente',
    ESTADO = 'estado',
    AREA = 'area',
    TOTAL_DIAS = 'total_dias',
}

export class FiltroRecibos extends ValueObject {
    private constructor(
        readonly tipo: TipoFiltro,
        readonly valores: Set<string>
    ) {
        super();
        this.validar();
    }
    
    private validar(): void {
        if (!Object.values(TipoFiltro).includes(this.tipo)) {
            throw new Error(`Tipo de filtro inválido: ${this.tipo}`);
        }
        if (this.valores.size === 0) {
            throw new Error('El filtro debe contener al menos un valor');
        }
    }
    
    static crear(tipo: TipoFiltro, valores: string[]): FiltroRecibos {
        return new FiltroRecibos(tipo, new Set(valores));
    }
    
    coincideCon(valor: string): boolean {
        return Array.from(this.valores).some(v => 
            v.toLowerCase().includes(valor.toLowerCase())
        );
    }
    
    esIgualA(otro: FiltroRecibos): boolean {
        return this.tipo === otro.tipo && 
               this.valores.size === otro.valores.size &&
               Array.from(this.valores).every(v => otro.valores.has(v));
    }
}
```

**2. Specification para Recibos:**
```typescript
// src/Domain/Recibos/RecibosFiltrablesByArea.ts
export class RecibosFiltrablesByArea extends Specification<ReciboCostura> {
    constructor(private areasSeleccionadas: Set<AreaRecibocostura>) {
        super();
    }
    
    isSatisfiedBy(recibo: ReciboCostura): boolean {
        return this.areasSeleccionadas.has(recibo.area);
    }
}

export class RecibosFiltrablesByDescripcion extends Specification<ReciboCostura> {
    constructor(private descripcionesSeleccionadas: Set<string>) {
        super();
    }
    
    isSatisfiedBy(recibo: ReciboCostura): boolean {
        return this.descripcionesSeleccionadas.has(recibo.descripcion);
    }
}
```

**3. Application Service:**
```typescript
// src/Application/ReciboCostura/UseCases/FiltrarRecibosUseCase.ts
@Injectable()
export class FiltrarRecibosUseCase {
    constructor(
        private reciboRepository: ReciboCosturaRepository,
        private logger: Logger
    ) {}
    
    async execute(comando: FiltrarRecibosCommand): Promise<FiltrarRecibosResponse> {
        this.logger.info('Filtrando recibos', { comando });
        
        try {
            // 1. Obtener todos los recibos
            const recibos = await this.reciboRepository.obtenerTodos();
            
            // 2. Crear especificación según filtro
            let specification: Specification<ReciboCostura>;
            
            switch (comando.tipoFiltro) {
                case TipoFiltro.AREA:
                    specification = new RecibosFiltrablesByArea(
                        new Set(comando.valores.map(AreaRecibocostura.crear))
                    );
                    break;
                case TipoFiltro.DESCRIPCION:
                    specification = new RecibosFiltrablesByDescripcion(
                        new Set(comando.valores)
                    );
                    break;
                // ... más casos
            }
            
            // 3. Aplicar especificación (lógica pura)
            const reciboysFiltrados = recibos.filter(r => 
                specification.isSatisfiedBy(r)
            );
            
            // 4. Mapear a DTOs
            const respuesta = new FiltrarRecibosResponse(
                reciboysFiltrados.map(r => ReciboCosturaDTO.fromRecibo(r))
            );
            
            this.logger.info(`Filtrado completado: ${respuesta.recibos.length} recibos`);
            return respuesta;
            
        } catch (error) {
            this.logger.error('Error al filtrar recibos', error);
            throw new FiltrarRecibosError(error.message);
        }
    }
}
```

**4. ViewModel para la Presentación:**
```typescript
// src/Presentation/ViewModels/RecibosTableViewModel.ts
export class RecibosTableViewModel {
    private recibos$ = new BehaviorSubject<ReciboCosturaDTO[]>([]);
    private filtrosActivos$ = new BehaviorSubject<Map<string, string[]>>(new Map());
    private cargando$ = new BehaviorSubject<boolean>(false);
    private error$ = new BehaviorSubject<string | null>(null);
    
    constructor(private filtrarRecibosUseCase: FiltrarRecibosUseCase) {}
    
    get recibos() { return this.recibos$.asObservable(); }
    get filtrosActivos() { return this.filtrosActivos$.asObservable(); }
    get cargando() { return this.cargando$.asObservable(); }
    get error() { return this.error$.asObservable(); }
    
    async aplicarFiltro(tipoFiltro: TipoFiltro, valores: string[]): Promise<void> {
        this.cargando$.next(true);
        this.error$.next(null);
        
        try {
            const comando = new FiltrarRecibosCommand(tipoFiltro, valores);
            const respuesta = await this.filtrarRecibosUseCase.execute(comando);
            
            this.recibos$.next(respuesta.recibos);
            
            // Guardar filtro activo
            const filtrosActuales = this.filtrosActivos$.getValue();
            filtrosActuales.set(tipoFiltro, valores);
            this.filtrosActivos$.next(filtrosActuales);
            
        } catch (error) {
            this.error$.next(error.message);
        } finally {
            this.cargando$.next(false);
        }
    }
    
    limpiarFiltros(): void {
        this.recibos$.next([]);
        this.filtrosActivos$.next(new Map());
        this.error$.next(null);
    }
}
```

**5. Componente de Presentación (simplificado):**
```html
<!-- recibos-costura.blade.php -->
@push('scripts')
<script>
    // Inyectar ViewModel (podría ser Alpine.js, Vue, React, etc.)
    const viewModel = new RecibosTableViewModel(filtrarRecibosUseCase);
    
    // Suscribirse a cambios
    viewModel.recibos.subscribe(recibos => {
        renderizarTabla(recibos);
    });
    
    viewModel.error.subscribe(error => {
        if (error) showError(error);
    });
    
    // Manejador de eventos
    window.aplicarFiltro = async (tipoFiltro, valores) => {
        await viewModel.aplicarFiltro(tipoFiltro, valores);
    };
    
    window.limpiarFiltros = () => {
        viewModel.limpiarFiltros();
    };
</script>
@endpush
```

---

## 2. AGREGAR PROCESO

###  ANTES - Responsabilidades mezcladas

```javascript
async function handleAgregarProcesoDesdeBadge() {
    try {
        // 1. Obtener datos del formulario
        const selectArea = document.getElementById('procesoArea');
        const inputEncargado = document.getElementById('procesoEncargado');
        const selectEncargado = document.getElementById('procesoEncargadoSelect');
        
        // 2. Validar (lógica de negocio en presentación)
        if (!selectArea.value) {
            alert('Selecciona un área');
            return;
        }
        if (!inputEncargado.value && !selectEncargado.value) {
            alert('Selecciona un encargado');
            return;
        }
        
        // 3. Obtener datos globales (estado inconsistente)
        if (!window.currentOrderData || !window.currentPrendaData) {
            await cargarDatosParaAgregarProceso(...);
        }
        
        // 4. Construir payload (infraestructura)
        const payload = {
            pedido_produccion_id: window.currentOrderData.id,
            prenda_id: window.currentPrendaData.id,
            area: selectArea.value,
            encargado: selectEncargado.value || inputEncargado.value
        };
        
        // 5. Llamada HTTP
        const response = await fetch(`/api/procesos`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        // 6. Manejo de error crudo
        if (!response.ok) {
            alert('Error: ' + response.statusText);
            return;
        }
        
        // 7. Manipular DOM
        document.getElementById('addProcesoModal').classList.remove('show');
        limpiarFormularioProceso();
        
        // 8. Mostrar notificación
        showSuccess('Proceso agregado correctamente');
        
        // 9. Recargar datos (posible inconsistencia)
        await cargarDatosParaAgregarProceso(...);
        
    } catch (error) {
        showError('Error: ' + error.message);
    }
}
```

###  DESPUÉS - Separación de responsabilidades

**1. Command (Caso de Uso):**
```typescript
// src/Application/ReciboCostura/Commands/AgregarProcesoCommand.ts
export class AgregarProcesoCommand implements ICommand {
    constructor(
        readonly pedidoId: string,
        readonly prendaId: string,
        readonly area: string,
        readonly encargado: string
    ) {
        this.validar();
    }
    
    private validar(): void {
        if (!this.pedidoId) throw new Error('Pedido ID requerido');
        if (!this.prendaId) throw new Error('Prenda ID requerida');
        if (this.area.trim() === '') throw new Error('Area requerida');
        if (this.encargado.trim() === '') throw new Error('Encargado requerido');
    }
}
```

**2. Event de Dominio:**
```typescript
// src/Domain/Procesos/Events/ProcesoAgregado.ts
export class ProcesoAgregadoDomainEvent extends DomainEvent {
    constructor(
        readonly procesoCosturaId: string,
        readonly pedidoId: string,
        readonly prendaId: string,
        readonly area: AreaRecibocostura,
        readonly encargado: EncargadoProceso,
        occurredAt: Date = new Date()
    ) {
        super(occurredAt);
    }
}
```

**3. Agregado:**
```typescript
// src/Domain/Procesos/ProcesoCostura.ts
export class ProcesoCostura extends AggregateRoot {
    private constructor(
        readonly id: ProcessoCosturaId,
        readonly pedidoId: PedidoId,
        readonly prendaId: PrendaId,
        readonly area: AreaRecibocostura,
        readonly encargado: EncargadoProceso,
        readonly estado: EstadoProceso,
        readonly fechaCreacion: Date
    ) {
        super(id);
    }
    
    static crear(
        pedidoId: PedidoId,
        prendaId: PrendaId,
        area: AreaRecibocostura,
        encargado: EncargadoProceso
    ): ProcesoCostura {
        const procesoId = ProcessoCosturaId.generar();
        
        const proceso = new ProcesoCostura(
            procesoId,
            pedidoId,
            prendaId,
            area,
            encargado,
            EstadoProceso.PENDIENTE,
            new Date()
        );
        
        // Lanzar evento de dominio
        proceso.addDomainEvent(
            new ProcesoAgregadoDomainEvent(
                procesoId.valor,
                pedidoId.valor,
                prendaId.valor,
                area,
                encargado
            )
        );
        
        return proceso;
    }
}
```

**4. Value Objects:**
```typescript
// src/Domain/Procesos/AreaRecibocostura.ts
export enum AREA_RECIBOCOSTURA_ENUM {
    COSTURA = 'COSTURA',
    CORTE = 'CORTE',
    EMPAQUE = 'EMPAQUE'
}

export class AreaRecibocostura extends ValueObject {
    private constructor(readonly valor: AREA_RECIBOCOSTURA_ENUM) {
        super();
    }
    
    static crear(area: string): AreaRecibocostura {
        const areaUpper = area.toUpperCase();
        if (!Object.values(AREA_RECIBOCOSTURA_ENUM).includes(areaUpper as any)) {
            throw new DomainError(`Area inválida: ${area}`);
        }
        return new AreaRecibocostura(areaUpper as AREA_RECIBOCOSTURA_ENUM);
    }
    
    esIgualA(otra: AreaRecibocostura): boolean {
        return this.valor === otra.valor;
    }
}

// src/Domain/Procesos/EncargadoProceso.ts
export class EncargadoProceso extends ValueObject {
    private constructor(readonly nombre: string) {
        super();
        this.validar();
    }
    
    private validar(): void {
        if (this.nombre.trim().length < 2) {
            throw new DomainError('Nombre de encargado debe tener al menos 2 caracteres');
        }
        if (this.nombre.length > 100) {
            throw new DomainError('Nombre de encargado muy largo');
        }
    }
    
    static crear(nombre: string): EncargadoProceso {
        return new EncargadoProceso(nombre.trim());
    }
    
    esIgualA(otro: EncargadoProceso): boolean {
        return this.nombre.toLowerCase() === otro.nombre.toLowerCase();
    }
}
```

**5. Application Service:**
```typescript
// src/Application/ReciboCostura/UseCases/AgregarProcesoUseCase.ts
@Injectable()
export class AgregarProcesoUseCase implements IUseCase<AgregarProcesoCommand, AgregarProcesoResponse> {
    
    constructor(
        private procesoCosturaRepository: ProcessoCosturaRepository,
        private unitOfWork: UnitOfWork,
        private eventPublisher: EventPublisher,
        private logger: Logger
    ) {}
    
    async execute(comando: AgregarProcesoCommand): Promise<AgregarProcesoResponse> {
        this.logger.info('Iniciando AgregarProcesoUseCase', { comando });
        
        const transaccion = this.unitOfWork.iniciar();
        
        try {
            // 1. Crear Value Objects (validación de dominio)
            const area = AreaRecibocostura.crear(comando.area);
            const encargado = EncargadoProceso.crear(comando.encargado);
            const pedidoId = new PedidoId(comando.pedidoId);
            const prendaId = new PrendaId(comando.prendaId);
            
            // 2. Crear Agregado
            const proceso = ProcesoCostura.crear(pedidoId, prendaId, area, encargado);
            
            // 3. Persistir
            await this.procesoCosturaRepository.guardar(proceso);
            
            // 4. Publicar eventos de dominio
            const eventos = proceso.obtenerEventosDominio();
            for (const evento of eventos) {
                await this.eventPublisher.publicar(evento);
            }
            
            // 5. Confirmar transacción
            await transaccion.confirmar();
            
            this.logger.info('Proceso agregado exitosamente', { procesoCosturaId: proceso.id });
            
            return new AgregarProcesoResponse(
                proceso.id.valor,
                'Proceso agregado correctamente'
            );
            
        } catch (error) {
            await transaccion.revertir();
            
            if (error instanceof DomainError) {
                this.logger.warn('Error de validación', error);
                throw new AgregarProcesoValidationError(error.message);
            }
            
            this.logger.error('Error al agregar proceso', error);
            throw new AgregarProcesoError(error.message);
        }
    }
}
```

**6. Manejador de Evento de Dominio:**
```typescript
// src/Application/ReciboCostura/EventHandlers/ProcesoAgregadoNotificador.ts
@Injectable()
export class ProcesoAgregadoNotificador implements DomainEventHandler {
    
    suscrito(evento: DomainEvent): boolean {
        return evento instanceof ProcesoAgregadoDomainEvent;
    }
    
    async ejecutar(evento: ProcesoAgregadoDomainEvent): Promise<void> {
        console.log('[ProcesoAgregadoNotificador] Proceso agregado:', evento.procesoCosturaId);
        
        // Notificar al encargado
        // Registrar en auditoría
        // Actualizar estado del pedido
        // Etc.
    }
}
```

**7. ViewModel actualizado:**
```typescript
// src/Presentation/ViewModels/ProcesoCosturaViewModel.ts
export class ProcesoCosturaViewModel {
    private cargando$ = new BehaviorSubject<boolean>(false);
    private error$ = new BehaviorSubject<string | null>(null);
    private exito$ = new BehaviorSubject<string | null>(null);
    
    readonly areas = Object.values(AREA_RECIBOCOSTURA_ENUM);
    
    constructor(private agregarProcesoUseCase: AgregarProcesoUseCase) {}
    
    get cargando() { return this.cargando$.asObservable(); }
    get error() { return this.error$.asObservable(); }
    get exito() { return this.exito$.asObservable(); }
    
    async agregar(pedidoId: string, prendaId: string, area: string, encargado: string): Promise<void> {
        this.cargando$.next(true);
        this.error$.next(null);
        this.exito$.next(null);
        
        try {
            const comando = new AgregarProcesoCommand(pedidoId, prendaId, area, encargado);
            const respuesta = await this.agregarProcesoUseCase.execute(comando);
            
            this.exito$.next(respuesta.mensaje);
            
            // El evento de dominio se encargará de las notificaciones
            // y actualizaciones de estado
            
        } catch (error) {
            if (error instanceof AgregarProcesoValidationError) {
                this.error$.next(error.message);
            } else if (error instanceof AgregarProcesoError) {
                this.error$.next('Error al agregar proceso. Intenta de nuevo.');
            } else {
                this.error$.next('Error inesperado');
            }
        } finally {
            this.cargando$.next(false);
        }
    }
}
```

**8. Componente de Presentación:**
```html
<div id="addProcesoModal">
    <form (ngSubmit)="onSubmit()">
        <select [(ngModel)]="area" name="area">
            <option *ngFor="let a of viewModel.areas">{{ a }}</option>
        </select>
        
        <input [(ngModel)]="encargado" name="encargado" type="text">
        
        <button type="submit" [disabled]="(viewModel.cargando | async)">
            Agregar Proceso
        </button>
    </form>
    
    <div *ngIf="(viewModel.error | async) as error" class="alert-error">
        {{ error }}
    </div>
    
    <div *ngIf="(viewModel.exito | async) as exito" class="alert-success">
        {{ exito }}
    </div>
</div>

<script>
    const viewModel = new ProcesoCosturaViewModel(agregarProcesoUseCase);
    
    window.agregarProceso = (pedidoId, prendaId, area, encargado) => {
        viewModel.agregar(pedidoId, prendaId, area, encargado);
    };
</script>
```

---

## 3. COMPARACIÓN ARQUITECTÓNICA

| Aspecto |  Actual |  Propuesto |
|--------|---------|-----------|
| **Líneas de JS en Blade** | 2000+ | < 50 |
| **Testing** | Imposible | 95%+ cobertura |
| **Reusabilidad** | Nula | Alta |
| **Mantenibilidad** | Muy difícil | Fácil |
| **Deuda técnica** | Crítica | Nula |
| **Acoplamiento** | Alto (DOM, API, Estado) | Bajo (Inyección de dependencias) |
| **Performance** | ❓ |  Medible |

---

## 📚 PATRÓN RECOMENDADO

```
Presentación (Vue/Angular/React)
    ↓
ViewModel (Gestiona estado de UI)
    ↓
Application Service (Orquestación)
    ↓
UseCase / Command Handler
    ↓
Domain Model (Agregados, Value Objects)
    ↓
Repository (Abstracción)
    ↓
Infrastructure (HTTP, Database)
```

Cada capa tiene responsabilidades claras y puede testearse independientemente.

