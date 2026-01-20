# PLAN DE MIGRACIÓN AL BACKEND - INDEX PEDIDOS

##  CONTENIDO ACTUAL EN index.blade.php (2329 líneas)

### 1. CSS Inline (250+ líneas)
**Ubicación:** Líneas 8-380  
**QUÉ:** Estilos para tabla, filtros, modales, botones  
**DÓNDE MIGRAR:** `/resources/views/asesores/pedidos/styles/`

```
├── index.css (estilos generales)
├── table.css (tabla, scroll, grid)
├── filters.css (botones filtro, badges)
├── modals.css (modales, overlays)
└── animations.css (keyframes)
```

---

### 2. HTML/BLADE (500+ líneas)
**Ubicación:** Líneas 380-1000  
**QUÉ:** Estructura HTML de tabla, componentes  
**DÓNDE MIGRAR:** `/resources/views/asesores/pedidos/components/`

**Componentes a crear:**
```
├── header.blade.php
│   ├── Título con ícono
│   ├── Buscador
│   └── Botón Registrar
├── filters-bar.blade.php
│   └── Botones filtros rápidos
├── table.blade.php
│   ├── Contenedor con scroll
│   ├── Header azul con filtros
│   └── Grid responsive
├── table-rows.blade.php
│   └── @foreach con filas dinámicas
├── empty-state.blade.php
│   └── Mensaje sin pedidos
└── actions.blade.php
    └── Botones acciones (editar, eliminar, etc)
```

---

### 3. LÓGICA PHP EN BLADE (Funciones de formateo)
**Ubicación:** Líneas 576-620 (el @php loop)  
**QUÉ:** Formateo de datos en Blade directamente

```php
// ACTUALM ENTE:
@php
    $procesosJson = json_encode($procesosInfo);
    // Cálculos, transformaciones aquí
@endphp

// MIGRAR A: Backend Service (PedidoFormatService)
```

**QUÉ MIGRAR:**
- `construirDescripcionComoPrenda()` → `PedidoFormatService::formatDescription()`
- `construirDescripcionComoProceso()` → `PedidoFormatService::formatProcess()`
- `construirTallasFormato()` → `PedidoFormatService::formatSizes()`
- Lógica de serialización JSON → `PedidoResource`

---

### 4. JAVASCRIPT INLINE (1000+ líneas)
**Ubicación:** Líneas 1000-2329  
**QUÉ:** Funciones de UI, eventos, modales, etc.

#### 🔴 DEBE IR AL BACKEND:

| Función | Por qué | Dónde |
|---------|--------|-------|
| `editarPedido()` | Cargar datos - Validar | `PedidosController@edit` |
| `eliminarPedido()` | Validar antes de borrar | `PedidosController@destroy` |
| `anularPedido()` | Lógica de negocio | `PedidoActionService::cancel()` |
| `cambiarEstado()` | Validación de transiciones | `PedidoActionService::changeStatus()` |
| `construirDescripcion*()` | Formateo de datos | `PedidoFormatService` |
| `construirTallasFormato()` | Serialización | `PedidoFormatService::formatSizes()` |

####  SE QUEDA EN FRONTEND (JavaScript Modules):

| Función | Por qué | Dónde |
|---------|--------|-------|
| `mostrarNotificacion()` | Solo UI/UX | `ui-service.js` |
| `abrirModalCelda()` | Renderizar modal | `modals-module.js` |
| `navegarFiltro()` | Navegación | `filter-module.js` |
| Event listeners | Interacción usuario | `modules/*.js` |
| Loading spinners | Efectos visuales | `ui-service.js` |

---

## 🔄 PLAN DE MIGRACIÓN PASO A PASO

### FASE 1: BACKEND - Crear Services

#### 1.1 Crear `PedidoFilterService.php`
```php
namespace App\Services\Pedidos;

class PedidoFilterService {
    
    public function applyFilters(Builder $query, array $filters): Builder {
        // Aplica WHERE por estado, área, etc
        return $query;
    }
    
    public function applySearch(Builder $query, string $term): Builder {
        // Búsqueda en numero_pedido, cliente, descripción
        return $query;
    }
    
    public function validate(array $filters): bool {
        // Valida que los filtros sean válidos
        return true;
    }
    
    public function getAvailableOptions(): array {
        // Retorna: estados[], áreas[], formas_pago[]
        return [];
    }
}
```

#### 1.2 Crear `PedidoActionService.php`
```php
namespace App\Services\Pedidos;

class PedidoActionService {
    
    public function canEdit(Pedido $pedido, User $user): bool {
        // Valida permisos y estado
    }
    
    public function canDelete(Pedido $pedido, User $user): bool {
        // Valida permisos y estado
    }
    
    public function cancel(Pedido $pedido, string $motivo): void {
        // Anula con motivo y auditoría
    }
    
    public function changeStatus(Pedido $pedido, string $status): void {
        // Cambiar estado con validaciones
    }
}
```

#### 1.3 Crear `PedidoFormatService.php`
```php
namespace App\Services\Pedidos;

class PedidoFormatService {
    
    public function formatForTable(Pedido $pedido): array {
        // Serializa para tabla: estado formateado, área nombre, etc
    }
    
    public function formatDescription(Pedido $pedido): string {
        // PRENDA 1: NOMBRE | TELA | COLOR | ...
    }
    
    public function formatProcess(Proceso $proceso): string {
        // COSTURA | UBICACIONES | OBSERVACIONES
    }
    
    public function formatSizes(array $tallas): array {
        // Dama: L(30), M(20) | Caballero: ...
    }
}
```

---

### FASE 2: BACKEND - Actualizar PedidosController

#### 2.1 Refactorizar `index()`
```php
public function index(Request $request)
{
    $filters = $request->validate([
        'estado' => 'nullable|in:activo,anulado,completado',
        'area' => 'nullable|string',
        'search' => 'nullable|string|max:100'
    ]);
    
    $query = Pedido::query();
    
    // Aplicar filtros EN LA BD
    $query = $this->filterService->applyFilters($query, $filters);
    
    // Aplicar búsqueda EN LA BD
    if ($filters['search']) {
        $query = $this->filterService->applySearch($query, $filters['search']);
    }
    
    // Paginar
    $pedidos = $query->paginate(20);
    
    // Serializar
    $pedidos = PedidoResource::collection($pedidos);
    
    return view('asesores.pedidos.index', ['pedidos' => $pedidos]);
}
```

#### 2.2 Crear `edit()`
```php
public function edit($id)
{
    $pedido = Pedido::with('prendas.procesos.ubicaciones')->find($id);
    
    if (!$pedido) abort(404);
    
    $this->authorize('edit', $pedido);
    
    return response()->json([
        'success' => true,
        'data' => new PedidoResource($pedido)
    ]);
}
```

#### 2.3 Actualizar `update()` y `destroy()`
```php
public function update(Request $request, $id)
{
    $pedido = Pedido::findOrFail($id);
    $this->authorize('update', $pedido);
    
    $validated = $request->validate([...]);
    
    $pedido->update($validated);
    
    return response()->json(['success' => true, 'message' => 'Actualizado']);
}

public function destroy($id)
{
    $pedido = Pedido::findOrFail($id);
    $this->authorize('delete', $pedido);
    
    $this->actionService->canDelete($pedido, auth()->user());
    
    $pedido->delete();
    
    return response()->json(['success' => true, 'message' => 'Eliminado']);
}
```

#### 2.4 Crear `cancel()`
```php
public function cancel(Request $request, $id)
{
    $pedido = Pedido::findOrFail($id);
    $this->authorize('cancel', $pedido);
    
    $motivo = $request->validate(['motivo' => 'required|string|max:500']);
    
    $this->actionService->cancel($pedido, $motivo['motivo']);
    
    return response()->json(['success' => true, 'message' => 'Anulado']);
}
```

---

### FASE 3: BACKEND - Crear Resources

#### 3.1 `PedidoResource.php`
```php
class PedidoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'estado' => $this->estado,
            'area' => $this->area,
            'forma_de_pago' => $this->forma_de_pago,
            'descripcion' => PedidoFormatService::formatDescription($this),
            'tallas' => PedidoFormatService::formatSizes($this->tallas),
            'prendas' => PrendaResource::collection($this->prendas),
            'created_at' => $this->created_at->format('d/m/Y'),
        ];
    }
}
```

---

### FASE 4: FRONTEND - Crear Carpetas

```bash
mkdir -p resources/views/asesores/pedidos/{scripts/services,scripts/modules,scripts/utils,styles,components}
```

---

### FASE 5: FRONTEND - API Client

#### 5.1 `api-client.js`
```js
class ApiClient {
    async get(url, params = {}) {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`${url}?${query}`);
        return response.json();
    }
    
    async post(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        return response.json();
    }
    
    async delete(url) {
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
            }
        });
        return response.json();
    }
}
```

---

### FASE 6: FRONTEND - UI Services

#### 6.1 `ui-service.js`
```js
class UIService {
    openModal(title, content, actions = []) {
        // Crea y abre modal
    }
    
    closeModal(id) {
        // Cierra modal
    }
    
    showToast(message, type = 'info') {
        // Muestra notificación
    }
}
```

---

### FASE 7: FRONTEND - Modules

#### 7.1 `search-module.js`
```js
class SearchModule {
    init() {
        this.attachListener();
    }
    
    attachListener() {
        const input = document.getElementById('mainSearchInput');
        input.addEventListener('input', (e) => {
            const search = e.target.value;
            // Enviar al backend: /pedidos?search=X
        });
    }
}
```

#### 7.2 `filter-module.js`
```js
class FilterModule {
    init() {
        this.attachListeners();
    }
    
    attachListeners() {
        const buttons = document.querySelectorAll('.btn-filter-column');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.openFilterModal();
            });
        });
    }
    
    openFilterModal() {
        // Abre modal, carga opciones DEL BACKEND
        // Aplica: window.location.href = '/pedidos?estado=activo&area=corte'
    }
}
```

---

##  RESUMEN: QUÉ MIGRAR

###  AL BACKEND:

| Qué | Archivo | Método |
|-----|---------|--------|
| Filtrado | `PedidoFilterService` | `applyFilters()` |
| Búsqueda | `PedidoFilterService` | `applySearch()` |
| Validación filtros | `PedidoFilterService` | `validate()` |
| Cargar para edición | `PedidosController@edit()` | Retorna JSON |
| Eliminar | `PedidosController@destroy()` | Valida + elimina |
| Anular | `PedidoActionService` | `cancel()` |
| Cambiar estado | `PedidoActionService` | `changeStatus()` |
| Formateo descr. | `PedidoFormatService` | `formatDescription()` |
| Formateo procesos | `PedidoFormatService` | `formatProcess()` |
| Formateo tallas | `PedidoFormatService` | `formatSizes()` |
| Serializar JSON | `PedidoResource` | `toArray()` |

###  AL FRONTEND:

| Qué | Archivo | Método |
|-----|---------|--------|
| HTTP calls | `api-client.js` | `get(), post(), delete()` |
| Modales | `ui-service.js` | `openModal(), closeModal()` |
| Notificaciones | `ui-service.js` | `showToast()` |
| Event listeners | `modules/*.js` | `init(), attachListener()` |
| Efectos visuales | `ui-service.js` | Animaciones |
| Navegación | `filter-module.js` | Query params |

---

##  ORDEN DE IMPLEMENTACIÓN

1. **Backend primero** - Services + Controller
2. **Frontend después** - Modules + Services
3. **Blade al final** - Componentes simples

**Estimado:** 3-5 días de trabajo

