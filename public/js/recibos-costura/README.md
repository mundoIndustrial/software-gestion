# 📦 Módulo Recibos de Costura - Arquitectura DDD + Clean Code

**Estado:** ✅ PHASE 1 Completado  
**Stack:** Vanilla JavaScript ES6+ | Bootstrap 5 | Vite  
**Arquitectura:** Clean Architecture + Domain-Driven Design (DDD)  

---

## 📋 Quick Start

### 1. Incluir en Blade (único archivo necesario)

```blade
<!-- En resources/views/registros/recibos-costura.blade.php -->
<script src="{{ asset('js/recibos-costura/api/ReciboCosturaAPI.js') }}"></script>
<script src="{{ asset('js/recibos-costura/domain/value-objects/EstadoRecibo.js') }}"></script>
<script src="{{ asset('js/recibos-costura/domain/value-objects/AreaRecibo.js') }}"></script>
<script src="{{ asset('js/recibos-costura/domain/value-objects/DiasTranscurridos.js') }}"></script>
<script src="{{ asset('js/recibos-costura/domain/value-objects/EncargadoProceso.js') }}"></script>
<script src="{{ asset('js/recibos-costura/infrastructure/state/RecibosState.js') }}"></script>
<script src="{{ asset('js/recibos-costura/presentation/controllers/RecibosTableController.js') }}"></script>
<script src="{{ asset('js/recibos-costura/init-recibos-costura.js') }}"></script>
```

### 2. Estructura HTML requerida

```blade
<!-- Tabla -->
<table class="table table-hover">
    <thead>
        <tr>
            <th>Acciones</th>
            <th>Estado</th>
            <th>Área</th>
            <th>Días</th>
            <th>N° Recibo</th>
            <th>Cliente</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>Novedades</th>
            <th>Fecha Creación</th>
            <th>Día Entrega</th>
            <th>Encargado</th>
        </tr>
    </thead>
    <tbody></tbody>
</table>

<!-- Paginación -->
<div class="pagination-container"></div>

<!-- Filtros -->
<div class="filter-container mb-3">
    <button class="btn btn-outline-primary" data-filter-type="estado">Estado</button>
    <button class="btn btn-outline-primary" data-filter-type="area">Área</button>
    <button class="btn btn-outline-secondary" data-action="limpiar-filtros">Limpiar</button>
</div>

<!-- UI Componentes -->
<div class="loading-spinner d-none">
    <div class="spinner-border"></div>
</div>
<div class="alert alert-danger d-none"></div>
<div class="alert alert-success d-none"></div>
```

### 3. ¡Listo! El módulo se inicializa automáticamente

---

## 📁 Estructura de Carpetas

```
recibos-costura/
│
├── api/
│   └── ReciboCosturaAPI.js              # Cliente HTTP
│
├── domain/
│   └── value-objects/
│       ├── EstadoRecibo.js              # Estados válidos
│       ├── AreaRecibo.js                # Áreas de proceso
│       ├── DiasTranscurridos.js         # Días + colores
│       ├── EncargadoProceso.js          # Persona responsable
│       └── index.js                     # Exporta todos
│
├── infrastructure/
│   └── state/
│       └── RecibosState.js              # State manager singleton
│
├── presentation/
│   └── controllers/
│       └── RecibosTableController.js    # Orquestador de tabla
│
├── init-recibos-costura.js              # Inicializador (punto de entrada)
└── README.md                            # Este archivo
```

---

## 🏗️ Arquitectura

```
PRESENTACIÓN          INFRAESTRUCTURA         DOMINIO
───────────────────────────────────────────────────────
RecibosTableController (orchestrate)
    ↓                   ↓
  render         RecibosState (state)
                        ↓
                    subscribe
                    
            ReciboCosturaAPI ← /api/recibos-costura
            
                            EstadoRecibo (VO)
                            AreaRecibo (VO)
                            DiasTranscurridos (VO)
                            EncargadoProceso (VO)
```

---

## 💎 Value Objects (Domain Layer)

### EstadoRecibo
```javascript
const estado = new EstadoRecibo('En Ejecución');
estado.getColorBadge();    // "info"
estado.enEjecucion();      // true
estado.getIcon();          // "fa-spinner"
```

### AreaRecibo
```javascript
const area = new AreaRecibo('Costura');
area.getColorBadge();      // "primary"
area.esAreaProduccion();   // true
```

### DiasTranscurridos
```javascript
const dias = DiasTranscurridos.fromFechas('2026-01-01', '2026-01-15');
dias.getRango();           // "amarillo"
dias.esRetrasado();        // false
```

### EncargadoProceso
```javascript
const encargado = new EncargadoProceso('Juan García');
encargado.getIniciales();  // "JG"
encargado.getAvatarUrl();  // URL con color automático
```

---

## 🎯 State Manager

### Acceder al estado (Singleton)
```javascript
const state = RecibosState.getInstance();
```

### Leer valores
```javascript
const recibos = state.get('recibos');
const filtros = state.get('filtrosActivos');
const error = state.get('error');
```

### Escribir valores
```javascript
state.setRecibos(nuevos_recibos);
state.setFiltrosActivos({ estado: 'En Ejecución' });
state.setError('Algo salió mal');
state.setSuccess('Operación exitosa');
```

### Suscribirse a cambios
```javascript
const unsubscribe = state.subscribe('recibos', (recibos) => {
    console.log('Recibos cambiaron:', recibos);
});

// Desuscribirse
unsubscribe();
```

### Gestionar modales
```javascript
state.abrirModalSeguimiento(numeroPedido);
state.cerrarModalSeguimiento();

state.abrirModalNovedades(numeroPedido, numeroRecibo);
state.cerrarModalNovedades();
```

---

## 🎮 Table Controller

### Inicializar
```javascript
const controller = new RecibosTableController();
await controller.init({
    tbody: document.querySelector('tbody'),
    paginationContainer: document.querySelector('.pagination-container'),
    filterContainer: document.querySelector('.filter-container'),
    loadingSpinner: document.querySelector('.loading-spinner'),
    errorAlert: document.querySelector('.alert-danger'),
    successAlert: document.querySelector('.alert-success')
});
```

### Operaciones
```javascript
// Cargar recibos específicos
await controller.cargarRecibos(1);

// Aplicar filtros
await controller.aplicarFiltros({ area: 'Costura', estado: 'En Ejecución' });

// Limpiar filtros
await controller.limpiarFiltros();

// Ir a página
await controller.irAPageina(2);
```

### Acceder desde otros scripts
```javascript
// El módulo se expone globalmente después de inicializar
const module = window.recibosCostruaModule;
const state = module.state;
const controller = module.tableController;
```

---

## 🔗 API Endpoints (Backend)

### GET /api/recibos-costura
Retorna lista de recibos con paginación y filtros

```javascript
const respuesta = await api.getRecibos({
    page: 1,
    per_page: 25,
    estado: 'En Ejecución',
    area: 'Costura',
    numero_recibo: '12345',
    cliente: 'ACME Corp',
    dia_entrega: '2026-01-20'
});

// Respuesta:
{
    data: [
        {
            id: 1,
            numero_recibo: '12345',
            estado: 'En Ejecución',
            area: 'Costura',
            cliente: 'ACME Corp',
            descripcion: 'Camiseta azul',
            cantidad: 50,
            // ...
        }
    ],
    pagination: {
        current_page: 1,
        last_page: 3,
        per_page: 25,
        total: 75
    }
}
```

### GET /api/recibos-costura/filter-options
Retorna opciones para los filtros (cache 1 hora)

```javascript
const opciones = await api.getFilterOptions();

// Respuesta:
{
    estados: ['En Ejecución', 'Pendiente Insumos', 'No iniciado'],
    areas: ['Costura', 'Corte', 'Insumos', ...],
    numeros_recibo: ['12345', '12346', ...],
    clientes: ['ACME Corp', 'Bob Inc', ...],
    dias_entrega: ['2026-01-15', '2026-01-20', ...]
}
```

---

## 🧪 Testing

### Unit Testing (Value Objects)
```javascript
describe('EstadoRecibo', () => {
    it('debe crear estado válido', () => {
        const estado = new EstadoRecibo('En Ejecución');
        expect(estado.enEjecucion()).toBe(true);
    });

    it('debe lanzar error para estado inválido', () => {
        expect(() => {
            new EstadoRecibo('INVALIDO');
        }).toThrowError();
    });
});
```

### Testing Manual en DevTools
```javascript
// En la consola del navegador:

// 1. Verificar que el módulo está listo
console.log(window.recibosCostruaModule.initialized);

// 2. Acceder al state
const state = window.recibosCostruaModule.state;
console.log(state.getState());

// 3. Probar Value Objects
const estado = new EstadoRecibo('En Ejecución');
console.log(estado.getColorBadge()); // "info"

// 4. Suscribirse a cambios
state.subscribe('recibos', (recibos) => {
    console.log('Recibos actualizados:', recibos);
});

// 5. Aplicar filtros
await window.recibosCostruaModule.tableController.aplicarFiltros({
    area: 'Costura'
});
```

---

## 🚀 Comparativa: ANTES vs DESPUÉS

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| **Líneas Blade** | 1500+ | ~50 |
| **Filtrado** | DOM manipulation | SQL backend |
| **Estado Global** | `window.*` | `RecibosState` |
| **Testeable** | No | Sí |
| **Performance** | 500+ items en memoria | 25 items paginados |
| **Mantenibilidad** | Baja | Alta |

---

## 📝 Mejores Prácticas

### ✅ DO's
- Usar Value Objects para lógica de dominio
- Suscribirse a cambios en lugar de polling
- Mantener controllers delgados
- Cache en API client para datos no frecuentes
- Separar concerns: Domain → Infrastructure → Presentation

### ❌ DON'Ts
- No manipular DOM en el State Manager
- No mezclar lógica de negocio con UI
- No usar `window.*` globales
- No hacer múltiples llamadas API
- No validar datos en el controller

---

## 🔄 Próximas Fases (PHASE 2+)

- [ ] RecibosFilterManager (modal de filtros avanzados)
- [ ] RecibosDetailsModal (detalles del recibo)
- [ ] RecibosSeguimientoModal (seguimiento en tiempo real)
- [ ] Unit tests automatizados
- [ ] Integración con WebSockets (Reverb)
- [ ] Exportar a Excel/PDF

---

## 📞 Soporte

Para cambios en:
- **Backend API**: Ver `PHASE_0_BACKEND_API_RECIBOS.md`
- **Value Objects**: Documentación inline en cada archivo
- **State Manager**: Revisar métodos disponibles en RecibosState.js
- **Blade Template**: Verificar selectores CSS en init-recibos-costura.js

---

**Desarrollado:** 24 Marzo 2026  
**Última actualización:** 24 Marzo 2026  
**Responsable:** Sistema de Refactorización DDD  
