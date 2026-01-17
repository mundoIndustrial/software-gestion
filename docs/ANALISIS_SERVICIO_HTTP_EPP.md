# 📡 Análisis: Servicio HTTP para Consumo de APIs en Frontend

## 1. Estructura del Servicio (EppHttpService)

El `EppHttpService` es una clase que encapsula todas las llamadas HTTP a la API de EPP. Esta es una arquitectura de **Service Pattern** muy común en frontend moderno.

### Ventajas de este patrón:

```javascript
// ❌ SIN SERVICIO (Código repetido, difícil de mantener)
async function agregarEPP() {
    const response = await fetch('/api/pedidos/123/epp/agregar', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({...})
    });
}

async function obtenerEPP() {
    const response = await fetch('/api/epp', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}

async function eliminarEPP() {
    const response = await fetch('/api/pedidos/123/epp/456', {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });
}

// ✅ CON SERVICIO (Código limpio, reutilizable, mantenible)
const eppService = new EppHttpService('/api');
await eppService.agregarAlPedido(123, 456, 'M', 10);
await eppService.buscar();
await eppService.eliminarDelPedido(123, 456);
```

---

## 2. Arquitectura del Servicio

```
┌─────────────────────────────────────────┐
│      Frontend (modal-agregar-epp.js)    │
│  - Evento: Click en "Agregar EPP"       │
│  - Llama: agregarEPPAlPedido()          │
└─────────────────────┬───────────────────┘
                      │ utiliza
                      ▼
┌─────────────────────────────────────────┐
│   EppHttpService (Capa Abstracción)     │
│  - Encapsula fetch() calls               │
│  - Headers estándar                     │
│  - Error handling                       │
│  - URL construction                     │
└─────────────────────┬───────────────────┘
                      │ realiza
                      ▼
┌─────────────────────────────────────────┐
│     HTTP Requests (Fetch API)            │
│  - POST /api/pedidos/{id}/epp/agregar   │
│  - GET /api/epp                         │
│  - DELETE /api/pedidos/{id}/epp/{id}    │
└─────────────────────┬───────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────┐
│     Backend API (Laravel DDD)            │
│  - EppController.php                    │
│  - QueryBus/CommandBus (CQRS)           │
│  - Domain Services                      │
│  - Repositories                         │
│  - Database                             │
└─────────────────────────────────────────┘
```

---

## 3. Desglose de Cada Método

### 3.1. Constructor
```javascript
class EppHttpService {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;  // URL base reutilizable
    }
}

// Uso:
const eppService = new EppHttpService('/api');
```

**Ventaja**: Si la API cambia de `/api` a `/v2/api`, cambias UNA línea.

---

### 3.2. Buscar EPP

```javascript
async buscar(termino = null, categoria = null) {
    // Construye URL dinámicamente
    let url = `${this.baseUrl}/epp`;
    const params = new URLSearchParams();

    if (termino) {
        params.append('q', termino);
    }
    if (categoria) {
        params.append('categoria', categoria);
    }

    if (params.toString()) {
        url += `?${params.toString()}`;  // ?q=casco&categoria=CABEZA
    }

    // Request estándar
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    // Error handling
    if (!response.ok) {
        throw new Error(`Error buscando EPP: ${response.statusText}`);
    }

    // Parse respuesta
    const data = await response.json();
    return data.data || [];  // Retorna solo el array de datos
}
```

**Flujo de Uso en Modal:**
```javascript
// En modal-agregar-epp.js
async function filtrarEPPBuscador(valor) {
    try {
        // Llamada simple y clara
        const epps = await eppService.buscar(valor);
        mostrarResultadosEPP(epps);
    } catch (error) {
        console.error('Error:', error);
        mostrarErrorEPP(error.message);
    }
}
```

**Requests generados:**
- `GET /api/epp` → Listar todos
- `GET /api/epp?q=casco` → Buscar "casco"
- `GET /api/epp?categoria=CABEZA` → Por categoría
- `GET /api/epp?q=casco&categoria=CABEZA` → Ambos filtros

---

### 3.3. Obtener EPP por ID

```javascript
async obtenerPorId(id) {
    const response = await fetch(`${this.baseUrl}/epp/${id}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    if (!response.ok) {
        if (response.status === 404) {
            return null;  // EPP no existe
        }
        throw new Error(`Error obteniendo EPP: ${response.statusText}`);
    }

    const data = await response.json();
    return data.data || null;
}
```

**Requests generados:**
- `GET /api/epp/1` → Obtener EPP con ID 1
- Respuesta 404 → Retorna `null` (no lanza error)
- Otros errores → Lanza excepción

---

### 3.4. Agregar EPP al Pedido

```javascript
async agregarAlPedido(pedidoId, eppId, talla, cantidad, observaciones = null) {
    const response = await fetch(`${this.baseUrl}/pedidos/${pedidoId}/epp/agregar`, {
        method: 'POST',  // ← Crear recurso
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        // Body serializado a JSON
        body: JSON.stringify({
            epp_id: eppId,           // 5
            talla: talla,            // "L"
            cantidad: cantidad,      // 10
            observaciones: observaciones,  // "Referencia especial"
        }),
    });

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error agregando EPP');
    }

    return await response.json();
}
```

**Request HTTP real:**
```
POST /api/pedidos/12345/epp/agregar HTTP/1.1
Content-Type: application/json
X-Requested-With: XMLHttpRequest

{
    "epp_id": 5,
    "talla": "L",
    "cantidad": 10,
    "observaciones": "Referencia especial"
}
```

**Uso en Frontend:**
```javascript
async function agregarEPPAlPedido() {
    const pedidoId = window.pedidoIdActual;  // Pedido actual
    const talla = document.getElementById('medidaTallaEPP').value;
    const cantidad = parseInt(document.getElementById('cantidadEPP').value);
    const observaciones = document.getElementById('observacionesEPP').value;

    try {
        await eppService.agregarAlPedido(
            pedidoId,
            productoSeleccionadoEPP.id,
            talla,
            cantidad,
            observaciones
        );
        
        // Éxito: Crear item visual
        crearItemEPP(...);
        cerrarModalAgregarEPP();
        
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
```

---

### 3.5. Eliminar EPP del Pedido

```javascript
async eliminarDelPedido(pedidoId, eppId) {
    const response = await fetch(
        `${this.baseUrl}/pedidos/${pedidoId}/epp/${eppId}`,
        {
            method: 'DELETE',  // ← Eliminar recurso
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }
    );

    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Error eliminando EPP');
    }

    return await response.json();
}
```

**Request HTTP real:**
```
DELETE /api/pedidos/12345/epp/5 HTTP/1.1
X-Requested-With: XMLHttpRequest
```

---

## 4. Headers Explicados

Todos los métodos incluyen estos headers:

```javascript
headers: {
    'Accept': 'application/json',
    // ↑ "Espero recibir JSON"
    
    'Content-Type': 'application/json',
    // ↑ "Estoy enviando JSON" (solo en POST/PUT/PATCH)
    
    'X-Requested-With': 'XMLHttpRequest',
    // ↑ Identifica como AJAX request (Laravel lo valida)
}
```

**¿Por qué `X-Requested-With`?**
- Laravel verifica este header en middleware CSRF
- Si no está, la request podría ser rechazada

---

## 5. Manejo de Errores

### Patrón consistente:

```javascript
try {
    const response = await fetch(url, { /* ... */ });
    
    // ❌ Status error (400, 404, 500, etc.)
    if (!response.ok) {
        if (response.status === 404) {
            return null;  // Caso especial
        }
        throw new Error('Descripción del error');
    }
    
    // ✅ Parsear JSON
    const data = await response.json();
    return data.data || [];
    
} catch (error) {
    console.error('Error:', error);
    throw error;  // Propagar error al llamador
}
```

**En el Frontend:**
```javascript
try {
    const epps = await eppService.buscar(termino);
    mostrarResultados(epps);
} catch (error) {
    mostrarErrorEPP(error.message);  // Usuario ve error amigable
}
```

---

## 6. Flujo Completo: Agregar EPP a Pedido

```
1️⃣ Usuario hace click en "Agregar EPP"
   └─ abrirModalAgregarEPP()

2️⃣ Modal se abre y carga EPPs disponibles
   └─ cargarEPPBuscador()
      └─ eppService.buscar()
         └─ GET /api/epp
            └─ Backend: QueryDispatcher → ListarEppActivosQuery → EppRepository
               └─ Retorna: [{id: 1, nombre: "Casco", ...}, ...]

3️⃣ Usuario busca "Casco"
   └─ filtrarEPPBuscador("CASCO")
      └─ eppService.buscar("CASCO")
         └─ GET /api/epp?q=CASCO
            └─ Retorna: Resultados filtrados

4️⃣ Usuario selecciona un EPP
   └─ seleccionarEPPDelBuscador(1, "Casco", "EPP-CAB-001", ...)
      └─ Muestra card con detalles
      └─ Habilita campos (talla, cantidad)

5️⃣ Usuario llena talla, cantidad, observaciones
   └─ Inputs son validados por JavaScript

6️⃣ Usuario hace click en "Agregar al Pedido"
   └─ agregarEPPAlPedido()
      └─ Valida datos
      └─ eppService.agregarAlPedido(pedidoId, eppId, talla, cantidad, obs)
         └─ POST /api/pedidos/12345/epp/agregar
            {
                "epp_id": 1,
                "talla": "L",
                "cantidad": 10,
                "observaciones": "Referencia especial"
            }
            └─ Backend: CommandDispatcher → AgregarEppAlPedidoCommand
               → AgregarEppAlPedidoHandler
               → PedidoEppRepository.agregarEppAlPedido()
               └─ INSERT INTO pedido_epps(...)
                  Retorna: {success: true, message: "EPP agregado"}

7️⃣ Frontend recibe respuesta exitosa
   └─ crearItemEPP(id, nombre, codigo, talla, cantidad, obs)
      └─ Crea elemento visual en DOM
      └─ Agrega a lista de items del pedido
      └─ cerrarModalAgregarEPP()

8️⃣ Modal se cierra
   └─ Usuario ve EPP agregado en la lista
```

---

## 7. Comparación: Antes vs Después del Servicio

### ❌ Antes (Hardcoded data - Primera versión)

```javascript
// modal-agregar-epp.js - PRIMERA VERSION
const eppDatos = [
    { id: 1, nombre: "Casco", ... },
    { id: 2, nombre: "Guantes", ... },
];

function filtrarEPPBuscador(valor) {
    const resultados = eppDatos.filter(epp => 
        epp.nombre.toLowerCase().includes(valor.toLowerCase())
    );
    // Mostrar resultados
}

// ❌ Problemas:
// - Datos estáticos no se actualizan
// - Sin comunicación con backend
// - Cambios en datos requieren cambiar JS
// - No escalable
```

### ✅ Después (Con Servicio - Versión actual)

```javascript
// modal-agregar-epp.js - VERSIÓN ACTUAL
const eppService = new EppHttpService('/api');

async function filtrarEPPBuscador(valor) {
    const epps = await eppService.buscar(valor);
    // Mostrar resultados dinámicos
}

// ✅ Ventajas:
// - Datos del backend en tiempo real
// - Frontend agnóstico de la BD
// - Cambios en BD se reflejan automáticamente
// - Código limpio y mantenible
// - Fácil de testear
// - Escalable a múltiples servicios
```

---

## 8. Patrones para Crear Otros Servicios

Una vez entiendas `EppHttpService`, puedes crear servicios similares:

```javascript
// Plantilla genérica para cualquier servicio

class GenericHttpService {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    /**
     * GET - Obtener lista
     */
    async obtenerTodos(endpoint, filtros = {}) {
        const params = new URLSearchParams(filtros);
        const url = params.toString() 
            ? `${this.baseUrl}/${endpoint}?${params}`
            : `${this.baseUrl}/${endpoint}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: this.getHeaders(),
        });

        if (!response.ok) throw new Error(`Error: ${response.statusText}`);
        return (await response.json()).data || [];
    }

    /**
     * GET - Obtener uno
     */
    async obtenerPorId(endpoint, id) {
        const response = await fetch(`${this.baseUrl}/${endpoint}/${id}`, {
            method: 'GET',
            headers: this.getHeaders(),
        });

        if (!response.ok) {
            if (response.status === 404) return null;
            throw new Error(`Error: ${response.statusText}`);
        }
        return (await response.json()).data || null;
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
            throw new Error(error.message || 'Error creando recurso');
        }
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

        if (!response.ok) throw new Error(`Error: ${response.statusText}`);
        return await response.json();
    }

    /**
     * Headers estándar
     */
    getHeaders(contentType = null) {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (contentType) {
            headers['Content-Type'] = contentType;
        }
        return headers;
    }
}

// Uso para otras entidades:
const prendasService = new GenericHttpService('/api');
const telesService = new GenericHttpService('/api');
const clientesService = new GenericHttpService('/api');

// Llamadas:
await prendasService.obtenerTodos('prendas', { activas: true });
await telesService.obtenerPorId('telas', 5);
await clientesService.crear('clientes', { nombre: 'Nuevo Cliente' });
```

---

## 9. Resumen: ¿Por qué es importante este patrón?

| Aspecto | Sin Servicio | Con Servicio |
|--------|-------------|--------------|
| **Mantenibilidad** | Difícil, fetch esparcido | Centralizado |
| **Reutilización** | Duplicado de código | DRY (Don't Repeat Yourself) |
| **Testing** | Difícil testear lógica HTTP | Fácil de mockear |
| **Cambios de API** | Editar múltiples archivos | Editar 1 archivo |
| **Error Handling** | Inconsistente | Consistente |
| **Headers** | Repetidos en cada fetch | Una sola vez |
| **Escalabilidad** | Crece desordenadamente | Patrón replicable |

Este es el **Service Pattern** usado en frameworks como Angular, Vue, React de forma profesional.

