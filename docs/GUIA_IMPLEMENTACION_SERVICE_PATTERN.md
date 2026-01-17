# 🏗️ Guía de Implementación: Service Pattern + DDD en Frontend

## 1. Estructura de Carpetas Detallada

```
public/js/
│
├── services/                           🏗️ INFRAESTRUCTURA + SERVICIOS
│   │
│   ├── http/                          🔌 CAPA HTTP (Infraestructura)
│   │   ├── HttpServiceConfig.js       ⚙️  Configuración centralizada
│   │   ├── GenericHttpService.js      📦 Base genérica CRUD
│   │   └── HttpErrorHandler.js        ⚠️  Manejo de errores HTTP
│   │
│   ├── epp/                           📋 DOMINIO: EPP
│   │   ├── EppHttpService.js          🎯 Servicio EPP
│   │   ├── EppValidators.js           ✅ Validaciones EPP
│   │   └── EppMappers.js              🔄 Transformación de datos
│   │
│   ├── prendas/                       👔 DOMINIO: Prendas
│   │   ├── PrendasHttpService.js      🎯 Servicio Prendas
│   │   ├── PrendasValidators.js       ✅ Validaciones
│   │   └── PrendasMappers.js          🔄 Mapeos
│   │
│   ├── pedidos/                       📦 DOMINIO: Pedidos
│   │   ├── PedidosHttpService.js      🎯 Servicio Pedidos
│   │   ├── PedidosValidators.js       ✅ Validaciones
│   │   └── PedidosMappers.js          🔄 Mapeos
│   │
│   ├── procesos/                      ⚙️  DOMINIO: Procesos
│   │   ├── ProcesosHttpService.js     🎯 Servicio Procesos
│   │   └── ProcesosMappers.js         🔄 Mapeos
│   │
│   └── index.js                       📤 Exportar todos (point of entry)
│
├── modulos/                            📱 COMPONENTES / UI
│   │
│   ├── crear-pedido/
│   │   │
│   │   ├── modales/                   🎭 MODALES
│   │   │   ├── modal-agregar-epp.js   🛡️  (Usa EppHttpService)
│   │   │   ├── modal-agregar-prenda.js 👔 (Usa PrendasHttpService)
│   │   │   └── modal-proceso.js       ⚙️  (Usa ProcesosHttpService)
│   │   │
│   │   ├── procesos/                  ⚙️  MANEJO DE PROCESOS
│   │   │   ├── gestion-procesos.js
│   │   │   └── render-procesos.js
│   │   │
│   │   └── crear-nuevo.js             🚀 Entry point del módulo
│   │
│   └── otros-modulos/
│       └── ...
│
└── utils/                              🛠️  UTILIDADES
    ├── formatters.js
    ├── validators.js
    └── constants.js
```

---

## 2. Código Base para Cada Capa

### 2.1 HttpServiceConfig.js (Configuración)

```javascript
// public/js/services/http/HttpServiceConfig.js

const HttpServiceConfig = {
    // URL base de la API
    baseUrl: '/api',
    
    // Timeouts
    timeout: 30000,
    
    // Reintentos
    retryAttempts: 3,
    retryDelay: 1000,
    
    // Headers globales
    headers: {
        'X-App-Version': '1.0.0',
        'X-Client': 'Vanilla JS',
    },
    
    // Logging
    debug: true,
    logRequests: true,
    logResponses: true,
};

// Exportar global
window.HttpServiceConfig = HttpServiceConfig;
```

---

### 2.2 GenericHttpService.js (Infraestructura Base)

```javascript
// public/js/services/http/GenericHttpService.js

/**
 * Servicio genérico de HTTP
 * 
 * Responsabilidades:
 * - Fetch base
 * - Headers estándar
 * - Manejo de errores HTTP
 * - Serialización JSON
 * - Construcción de URLs
 * 
 * No contiene lógica de negocio
 */
class GenericHttpService {
    constructor(baseUrl = null) {
        this.baseUrl = baseUrl || HttpServiceConfig.baseUrl;
        this.config = HttpServiceConfig;
    }

    /**
     * GET - Obtener lista
     */
    async obtenerTodos(endpoint, filtros = {}) {
        const params = new URLSearchParams(filtros);
        const url = params.toString() 
            ? `${this.baseUrl}/${endpoint}?${params}`
            : `${this.baseUrl}/${endpoint}`;

        if (this.config.logRequests) {
            console.group(`[API] GET ${endpoint}`);
            console.log('URL:', url);
            console.log('Filtros:', filtros);
            console.groupEnd();
        }

        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders(),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (this.config.logResponses) {
                console.group(`[API] Respuesta GET ${endpoint}`);
                console.log('Data:', data);
                console.groupEnd();
            }

            return data.data || [];
        } catch (error) {
            console.error(`[API ERROR] GET ${endpoint}:`, error);
            throw error;
        }
    }

    /**
     * GET - Obtener uno
     */
    async obtenerUno(endpoint, id) {
        const response = await fetch(`${this.baseUrl}/${endpoint}/${id}`, {
            method: 'GET',
            headers: this.getHeaders(),
        });

        if (!response.ok) {
            if (response.status === 404) return null;
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        return data.data || null;
    }

    /**
     * POST - Crear
     */
    async crear(endpoint, datos) {
        const response = await fetch(`${this.baseUrl}/${endpoint}`, {
            method: 'POST',
            headers: this.getHeaders('application/json'),
            body: JSON.stringify(datos),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || `HTTP ${response.status}`);
        }

        return await response.json();
    }

    /**
     * PUT - Actualizar
     */
    async actualizar(endpoint, id, datos) {
        const response = await fetch(`${this.baseUrl}/${endpoint}/${id}`, {
            method: 'PUT',
            headers: this.getHeaders('application/json'),
            body: JSON.stringify(datos),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    }

    /**
     * DELETE - Eliminar
     */
    async eliminar(endpoint, id) {
        const response = await fetch(`${this.baseUrl}/${endpoint}/${id}`, {
            method: 'DELETE',
            headers: this.getHeaders(),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return await response.json();
    }

    /**
     * Headers estándar
     */
    getHeaders(contentType = null) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...this.config.headers,
        };

        if (contentType) {
            headers['Content-Type'] = contentType;
        }

        return headers;
    }
}

// Exportar global
window.GenericHttpService = GenericHttpService;
```

---

### 2.3 EppHttpService.js (Servicio de Dominio EPP)

```javascript
// public/js/services/epp/EppHttpService.js

/**
 * Servicio EPP
 * 
 * Responsabilidades:
 * - Lógica de negocio EPP
 * - Métodos con semántica clara
 * - Encapsular endpoints específicos
 * 
 * No contiene lógica de UI
 * Extiende GenericHttpService
 */
class EppHttpService extends GenericHttpService {
    constructor(baseUrl = null) {
        super(baseUrl);
        this.endpoint = 'epp';
    }

    /**
     * Buscar EPP por término o categoría
     * @param {string|null} termino
     * @param {string|null} categoria
     * @returns {Promise<Array>}
     */
    async buscar(termino = null, categoria = null) {
        const filtros = {};
        if (termino) filtros.q = termino;
        if (categoria) filtros.categoria = categoria;

        return this.obtenerTodos(this.endpoint, filtros);
    }

    /**
     * Obtener EPP por ID
     * @param {number} id
     * @returns {Promise<Object|null>}
     */
    async obtenerPorId(id) {
        return this.obtenerUno(this.endpoint, id);
    }

    /**
     * Obtener todas las categorías
     * @returns {Promise<Array>}
     */
    async obtenerCategorias() {
        return this.obtenerTodos(`${this.endpoint}/categorias`);
    }

    /**
     * Obtener EPP de un pedido
     * @param {number} pedidoId
     * @returns {Promise<Array>}
     */
    async obtenerDelPedido(pedidoId) {
        return this.obtenerTodos(`pedidos/${pedidoId}/epp`);
    }

    /**
     * Agregar EPP a un pedido
     * @param {number} pedidoId
     * @param {number} eppId
     * @param {string} talla
     * @param {number} cantidad
     * @param {string|null} observaciones
     * @returns {Promise<Object>}
     */
    async agregarAlPedido(pedidoId, eppId, talla, cantidad, observaciones = null) {
        return this.crear(`pedidos/${pedidoId}/epp/agregar`, {
            epp_id: eppId,
            talla: talla,
            cantidad: cantidad,
            observaciones: observaciones,
        });
    }

    /**
     * Eliminar EPP del pedido
     * @param {number} pedidoId
     * @param {number} eppId
     * @returns {Promise<Object>}
     */
    async eliminarDelPedido(pedidoId, eppId) {
        return this.eliminar(`pedidos/${pedidoId}/epp`, eppId);
    }
}

// Exportar global
window.EppHttpService = EppHttpService;
```

---

### 2.4 Modal Component (Uso del Servicio)

```javascript
// public/js/modulos/crear-pedido/modales/modal-agregar-epp.js

// 1. Inicializar servicio
const eppService = new EppHttpService('/api');
let productoSeleccionadoEPP = null;

// 2. Buscar EPP (usa el servicio)
async function filtrarEPPBuscador(valor) {
    try {
        const epps = await eppService.buscar(valor);
        mostrarResultadosEPP(epps);
    } catch (error) {
        console.error('Error:', error);
        mostrarErrorEPP(error.message);
    }
}

// 3. Agregar EPP (usa el servicio)
async function agregarEPPAlPedido() {
    if (!productoSeleccionadoEPP) {
        alert('Selecciona un EPP');
        return;
    }

    const talla = document.getElementById('medidaTallaEPP').value;
    const cantidad = parseInt(document.getElementById('cantidadEPP').value);
    const observaciones = document.getElementById('observacionesEPP').value;

    try {
        const pedidoId = window.pedidoIdActual;
        
        // Llamada limpia al servicio
        await eppService.agregarAlPedido(
            pedidoId,
            productoSeleccionadoEPP.id,
            talla,
            cantidad,
            observaciones
        );

        // Solo UI logic después
        crearItemEPP(...);
        cerrarModalAgregarEPP();

    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// 4. Funciones UI (sin lógica HTTP)
function mostrarResultadosEPP(epps) {
    // Renderizar UI
}

function mostrarErrorEPP(mensaje) {
    // Mostrar error en UI
}
```

---

## 3. Carga en HTML (Orden Correcto)

```html
<!-- resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php -->

@push('scripts')
    <!-- 1. Configuración centralizada -->
    <script src="{{ asset('js/services/http/HttpServiceConfig.js') }}"></script>
    
    <!-- 2. Infraestructura HTTP base -->
    <script src="{{ asset('js/services/http/GenericHttpService.js') }}"></script>
    
    <!-- 3. Servicios de dominio (pueden cargarse en cualquier orden) -->
    <script src="{{ asset('js/services/epp/EppHttpService.js') }}"></script>
    <script src="{{ asset('js/services/prendas/PrendasHttpService.js') }}"></script>
    <script src="{{ asset('js/services/pedidos/PedidosHttpService.js') }}"></script>
    
    <!-- 4. Componentes UI (usan los servicios) -->
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-agregar-epp.js') }}"></script>
    <script src="{{ asset('js/modulos/crear-pedido/modales/modal-agregar-prenda.js') }}"></script>
    
    <!-- 5. Main app -->
    <script src="{{ asset('js/crear-pedido-editable.js') }}"></script>
@endpush
```

---

## 4. Diagrama de Dependencias

```
HttpServiceConfig
    ↓
GenericHttpService
    ↓
┌───────────────────────────────┐
│ Servicios de Dominio          │
├───────────────────────────────┤
│ ├─ EppHttpService             │
│ ├─ PrendasHttpService         │
│ ├─ PedidosHttpService         │
│ └─ ProcesosHttpService        │
└───────────────────────────────┘
    ↓
┌───────────────────────────────┐
│ Componentes UI                │
├───────────────────────────────┤
│ ├─ modal-agregar-epp.js       │
│ ├─ modal-agregar-prenda.js    │
│ ├─ modal-proceso.js           │
│ └─ ...otros componentes       │
└───────────────────────────────┘
    ↓
┌───────────────────────────────┐
│ App Principal                 │
└───────────────────────────────┘
```

---

## 5. Flujo de Ejecución Completo

```
USUARIO INTERACTÚA CON MODAL
    ↓
modal-agregar-epp.js (UI)
    ↓ user clicks "Buscar"
filtrarEPPBuscador(valor)
    ↓
await eppService.buscar(valor)
    ↓ [EppHttpService]
this.obtenerTodos('epp', { q: valor })
    ↓ [GenericHttpService]
fetch(/api/epp?q=valor)
    ↓ [Fetch API]
GET /api/epp?q=valor
    ↓ [Browser HTTP]
┌─────────────────────────────┐
│ BACKEND LARAVEL             │
│ GET /api/epp                │
│ ├─ EppController            │
│ ├─ QueryDispatcher          │
│ ├─ BuscarEppQuery           │
│ ├─ EppRepository            │
│ ├─ Database                 │
│ └─ return JSON              │
└─────────────────────────────┘
    ↓
Response { data: [...] }
    ↓ [GenericHttpService.obtenerTodos()]
return data.data
    ↓ [EppHttpService.buscar()]
return resultados
    ↓
mostrarResultadosEPP(resultados)
    ↓ [UI Rendering]
USUARIO VE RESULTADOS
```

---

## 6. Extensión: Agregar Nuevo Dominio (Telas)

### Paso 1: Crear servicio

```javascript
// public/js/services/telas/TelasHttpService.js

class TelasHttpService extends GenericHttpService {
    constructor(baseUrl = null) {
        super(baseUrl);
        this.endpoint = 'telas';
    }

    async buscar(termino = null) {
        return this.obtenerTodos(this.endpoint, { q: termino });
    }

    async obtenerPorId(id) {
        return this.obtenerUno(this.endpoint, id);
    }

    async obtenerStock() {
        return this.obtenerTodos(`${this.endpoint}/stock`);
    }
}

window.TelasHttpService = TelasHttpService;
```

### Paso 2: Cargar en HTML

```html
<!-- Solo agregar 1 línea más -->
<script src="{{ asset('js/services/telas/TelasHttpService.js') }}"></script>
```

### Paso 3: Usar en UI

```javascript
const telasService = new TelasHttpService('/api');

async function buscarTelas(termino) {
    const telas = await telasService.buscar(termino);
    mostrarTelas(telas);
}
```

**✅ Sin cambiar GenericHttpService o servicios anteriores**

---

## 7. Testing (Jest)

```javascript
// __tests__/services/epp/EppHttpService.test.js

describe('EppHttpService', () => {
    let eppService;

    beforeEach(() => {
        eppService = new EppHttpService('http://api.test');
    });

    test('buscar() debe llamar obtenerTodos()', async () => {
        const spy = jest.spyOn(eppService, 'obtenerTodos');
        spy.mockResolvedValue([{ id: 1, nombre: 'Casco' }]);

        const resultado = await eppService.buscar('casco');

        expect(spy).toHaveBeenCalledWith('epp', { q: 'casco' });
        expect(resultado).toEqual([{ id: 1, nombre: 'Casco' }]);
    });

    test('agregarAlPedido() debe hacer POST', async () => {
        const spy = jest.spyOn(eppService, 'crear');
        spy.mockResolvedValue({ success: true });

        await eppService.agregarAlPedido(123, 5, 'L', 10);

        expect(spy).toHaveBeenCalledWith('pedidos/123/epp/agregar', {
            epp_id: 5,
            talla: 'L',
            cantidad: 10,
            observaciones: null,
        });
    });
});
```

---

## 8. Checklist de Implementación

- [ ] Crear carpeta `public/js/services/`
- [ ] Crear `HttpServiceConfig.js`
- [ ] Crear `GenericHttpService.js`
- [ ] Crear `services/epp/EppHttpService.js`
- [ ] Crear `services/prendas/PrendasHttpService.js` (opcional)
- [ ] Crear `services/pedidos/PedidosHttpService.js` (opcional)
- [ ] Actualizar `modal-agregar-epp.js` para usar servicio
- [ ] Actualizar `crear-pedido-nuevo.blade.php` con cargas
- [ ] Verificar orden de carga
- [ ] Testear en navegador (abrir DevTools → Console)
- [ ] Confirmar que no hay errores de `undefined`
- [ ] Crear tests unitarios

---

## 9. Debugging

```javascript
// Verificar que servicios están disponibles
console.log(window.GenericHttpService);     // ✅ debe existir
console.log(window.EppHttpService);         // ✅ debe existir
console.log(window.HttpServiceConfig);      // ✅ debe existir

// Instanciar y verificar
const eppService = new EppHttpService('/api');
console.log(eppService);                    // ✅ debe mostrar instancia

// Llamar método y ver request
await eppService.buscar('casco');
// Verás en Network tab: GET /api/epp?q=casco
```

---

## Conclusión

Esta estructura es:
- ✅ **Profesional** (usada en empresas Fortune 500)
- ✅ **Escalable** (agregar servicios sin cambiar existentes)
- ✅ **Mantenible** (centralizado y ordenado)
- ✅ **Testeable** (fácil de mockear)
- ✅ **Alineada con DDD** (paralela a backend)

**Implementa con confianza.**
