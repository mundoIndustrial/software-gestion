# MAPEO EXACTO DE FUNCIONES A MIGRAR

## 📍 FUNCIONES EN index.blade.php QUE VAN AL BACKEND

### 1. FUNCIONES DE FORMATEO

#### `construirDescripcionComoPrenda()` (Línea ~1330)
**Actual (JavaScript):**
```js
function construirDescripcionComoPrenda(prenda, numero) {
    const lineas = [];
    // Construye: PRENDA 1: NOMBRE
    //            TELA: ... | COLOR: ... | MANGA: ...
    //            Detalles técnicos, tallas
}
```

**Migrar a:**
```php
// app/Services/Pedidos/PedidoFormatService.php
public function formatPrendaDescription(Prenda $prenda): string {
    $lines = [];
    $lines[] = sprintf("PRENDA: %s", strtoupper($prenda->nombre));
    $lines[] = sprintf("TELA: %s | COLOR: %s | MANGA: %s", 
        strtoupper($prenda->tela),
        strtoupper($prenda->color),
        strtoupper($prenda->variantes[0]->manga ?? '')
    );
    return implode("\n", $lines);
}
```

---

#### `construirDescripcionComoProceso()` (Línea ~1400)
**Actual (JavaScript):**
```js
function construirDescripcionComoProceso(prenda, proceso) {
    // Construye: COSTURA
    //            Ubicaciones, observaciones, tallas
}
```

**Migrar a:**
```php
public function formatProcesoDescription(Proceso $proceso): string {
    $lines = [];
    $lines[] = strtoupper($proceso->tipo_proceso);
    if ($proceso->ubicaciones) {
        $lines[] = "UBICACIONES: " . implode(", ", $proceso->ubicaciones);
    }
    if ($proceso->observaciones) {
        $lines[] = "OBSERVACIONES: " . $proceso->observaciones;
    }
    return implode("\n", $lines);
}
```

---

#### `construirTallasFormato()` (Línea ~1460)
**Actual (JavaScript):**
```js
function construirTallasFormato(tallas, generoDefault = 'dama') {
    const tallasDama = {};
    const tallasCalballero = {};
    // Organiza por género: Dama: L(30), M(20)
    //                      Caballero: XL(10)
}
```

**Migrar a:**
```php
public function formatSizes(Pedido $pedido): array {
    return [
        'dama' => ['L' => 30, 'M' => 20],
        'caballero' => ['XL' => 10]
    ];
}
```

---

### 2. FUNCIONES DE CARGA DE DATOS

#### `abrirModalDescripcion()` (Línea ~1220)
**Actual (JavaScript):**
```js
async function abrirModalDescripcion(pedidoId, tipo) {
    const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
    const data = await response.json();
    // Carga recibos y muestra modal
}
```

**Migrar a:**
```php
// app/Http/Controllers/Asesores/PedidosController.php
public function getReciboDatos($id) {
    $pedido = Pedido::with('prendas.procesos.ubicaciones')->find($id);
    
    return response()->json([
        'success' => true,
        'data' => new ReciboDatosResource($pedido)
    ]);
}

// app/Http/Resources/ReciboDatosResource.php
public function toArray($request) {
    return [
        'prendas' => PrendaRecurso::collection($this->prendas),
        'procesos' => ProcesosResource::collection($this->procesos)
    ];
}
```

---

#### `editarPedido()` (Línea ~1798)
**Actual (JavaScript):**
```js
async function editarPedido(pedidoId) {
    const respuesta = await this.pedidoService.getPedidoParaEdicion(pedidoId);
    // Abre modal con datos
}
```

**Migrar a:**
```php
public function edit($id) {
    $pedido = Pedido::with('prendas', 'epp')->find($id);
    
    if (!$pedido) abort(404);
    $this->authorize('edit', $pedido);
    
    return response()->json([
        'success' => true,
        'data' => new PedidoResource($pedido)
    ]);
}
```

---

### 3. FUNCIONES DE ACCIONES

#### `eliminarPedido()` (Línea ~2107)
**Actual (JavaScript):**
```js
async function eliminarPedido(pedidoId) {
    const response = await fetch(`/asesores/pedidos-produccion/${pedidoId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    // Solo hace llamada HTTP
}
```

**Migrar a:**
```php
public function destroy($id) {
    $pedido = Pedido::findOrFail($id);
    $this->authorize('delete', $pedido);
    
    // Validar permisos de estado
    if (!$this->actionService->canDelete($pedido, auth()->user())) {
        return response()->json(['success' => false], 403);
    }
    
    DB::transaction(function () use ($pedido) {
        $pedido->prendas()->delete();
        $pedido->epp()->delete();
        $pedido->delete();
    });
    
    return response()->json(['success' => true]);
}
```

---

#### `anularPedido()` (Línea ~?)
**Actual (JavaScript):**
```js
async function anularPedido(pedidoId, motivo) {
    // Crea modal de motivo y envía al backend
}
```

**Migrar a:**
```php
public function cancel(Request $request, $id) {
    $pedido = Pedido::findOrFail($id);
    $this->authorize('cancel', $pedido);
    
    $motivo = $request->validate([
        'motivo' => 'required|string|max:500'
    ])['motivo'];
    
    $this->actionService->cancel($pedido, $motivo);
    
    return response()->json(['success' => true]);
}
```

---

#### `cambiarEstado()` (Línea ~?)
**Actual (JavaScript):**
```js
async function cambiarEstado(pedidoId, nuevoEstado) {
    // Envía cambio de estado
}
```

**Migrar a:**
```php
public function changeStatus(Request $request, $id) {
    $pedido = Pedido::findOrFail($id);
    $this->authorize('update', $pedido);
    
    $status = $request->validate([
        'status' => 'required|in:activo,anulado,completado'
    ])['status'];
    
    $this->actionService->changeStatus($pedido, $status);
    
    return response()->json(['success' => true]);
}
```

---

## 📍 FUNCIONES EN index.blade.php QUE VAN AL FRONTEND (Modules)

### 1. BÚSQUEDA

#### `searchOrders()` (Línea ~2191)
**Actual:**
```js
function searchOrders() {
    const searchTerm = searchInput.value.toLowerCase().trim();
    rows.forEach(row => {
        const matches = !searchTerm || 
                       numeroPedido.includes(searchTerm) ||
                       cliente.includes(searchTerm);
    });
}
```

**Refactorizar a:**
```js
// resources/views/asesores/pedidos/scripts/modules/search-module.js
class SearchModule {
    init(apiClient, uiService) {
        this.apiClient = apiClient;
        this.uiService = uiService;
        this.attachListeners();
    }
    
    attachListeners() {
        const input = document.getElementById('mainSearchInput');
        input.addEventListener('input', this.debounce((e) => {
            const term = e.target.value;
            
            // ENVIAR AL BACKEND
            this.apiClient.get('/asesores/pedidos', {
                search: term
            }).then(data => {
                this.renderResults(data.data);
            });
        }, 300));
    }
    
    renderResults(pedidos) {
        // Actualiza tabla con resultados
    }
}
```

---

### 2. FILTRADOS

#### `applyFilters()` (Línea ~?)
**Actual:**
```js
function applyFilters() {
    const selectedValues = Array.from(modal.querySelectorAll('input[type="checkbox"]:checked'))
        .map(cb => cb.value);
    // Aplica data-attributes
}
```

**Refactorizar a:**
```js
// resources/views/asesores/pedidos/scripts/modules/filter-module.js
class FilterModule {
    init(apiClient) {
        this.apiClient = apiClient;
        this.attachListeners();
    }
    
    applyFilters(columnName, values) {
        // REDIRECT CON QUERY PARAMS
        const params = new URLSearchParams({
            [columnName]: values.join(',')
        });
        window.location.href = `/asesores/pedidos?${params.toString()}`;
    }
}
```

---

### 3. ACCIONES

#### `abrirEditarDatos()` (Línea ~1912)
#### `abrirEditarEPP()` (Línea ~1945)
#### `abrirEditarEPPEspecifico()` (Línea ~2011)

**Refactorizar a:**
```js
// resources/views/asesores/pedidos/scripts/modules/actions-module.js
class ActionsModule {
    init(apiClient, uiService) {
        this.apiClient = apiClient;
        this.uiService = uiService;
        this.attachListeners();
    }
    
    attachListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="edit"]')) {
                this.handleEdit(e.target.closest('[data-pedido-id]'));
            }
            if (e.target.closest('[data-action="delete"]')) {
                this.handleDelete(e.target.closest('[data-pedido-id]'));
            }
        });
    }
    
    async handleEdit(element) {
        const pedidoId = element.dataset.pedidoId;
        const data = await this.apiClient.get(`/asesores/pedidos/${pedidoId}/edit`);
        
        // Abre modal con datos del backend
        this.uiService.openEditModal(data);
    }
    
    async handleDelete(element) {
        const confirmed = await this.uiService.confirmDelete();
        if (!confirmed) return;
        
        const pedidoId = element.dataset.pedidoId;
        await this.apiClient.delete(`/asesores/pedidos/${pedidoId}`);
        
        this.uiService.showToast('Eliminado', 'success');
        location.reload();
    }
}
```

---

## 🔗 FUNCIONES QUE SE ELIMNAN (NO VAN A NINGÚN LADO)

Estas funciones helper son SOLO para el código anterior y no se necesitan:

```js
// ELIMINAR:
function navegarFiltro(url, event) { }  // Ya no se necesita

function getFiltersState() { }  // localStorage será en backend via URL

function getDataAttributeFromColumn() { }  // No existe en nueva arquitectura
```

---

##  CHECKLIST DE MIGRACIÓN

### Backend
- [ ] `PedidoFilterService` con `applyFilters()`, `applySearch()`
- [ ] `PedidoActionService` con `canEdit()`, `canDelete()`, `cancel()`
- [ ] `PedidoFormatService` con `formatDescription()`, `formatSizes()`
- [ ] `PedidosController@index` - aplica filtros en BD
- [ ] `PedidosController@edit` - retorna JSON
- [ ] `PedidosController@update` - valida y guarda
- [ ] `PedidosController@destroy` - valida y elimina
- [ ] `PedidosController@cancel` - anula con motivo
- [ ] `PedidoResource` serializa JSON
- [ ] `ReciboDatosResource` para modales

### Frontend
- [ ] `api-client.js` - HTTP calls
- [ ] `ui-service.js` - modales y toasts
- [ ] `search-module.js` - búsqueda
- [ ] `filter-module.js` - filtros
- [ ] `actions-module.js` - acciones
- [ ] `modals-module.js` - gestión modales
- [ ] CSS extraído a `/styles/`
- [ ] HTML extraído a `/components/`

---

**Total líneas a mover:**
- Backend: ~400 líneas de PHP (Services)
- Frontend: ~600 líneas de JS (Modules + Services)
- Blade: ~100 líneas (Components simples)
- CSS: ~250 líneas
- **Ahorro: 1800+ líneas en index.blade.php**

