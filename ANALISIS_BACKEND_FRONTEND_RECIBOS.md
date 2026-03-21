# 🔄 Análisis: Qué Debe Ir en el Backend

**Archivo:** resources/views/registros/recibos-costura.blade.php  
**Análisis:** Lógica que actualmente está en Frontend → Backend

---

## 🔴 CRÍTICO: Lógica que DEBE estar en Backend

### 1. **Cargar Nombres de Prendas (Línea ~625-645)**

**ACTUAL (Frontend - ❌ MAL):**
```javascript
// DOMContentLoaded event
filasRecibos.forEach(fila => {
    const reciboId = fila.getAttribute('data-orden-id');
    const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
    
    if (descripcionElemento) {
        const enlacePedido = fila.querySelector('a[href*="/registros/"]');
        let pedidoProduccionId = null;
        
        if (enlacePedido) {
            const href = enlacePedido.getAttribute('href');
            const match = href.match(/\/registros\/(\d+)/);
            if (match) {
                pedidoProduccionId = match[1];
            }
        }
        
        if (pedidoProduccionId) {
            // ❌ PROBLEMA: N llamadas fetch por N recibos
            fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                .then(response => response.json())
                .then(datos => {
                    if (datos.prendas && datos.prendas.length > 0) {
                        const nombrePrenda = datos.prendas[0].nombre || 'Sin nombre';
                        descripcionElemento.textContent = nombrePrenda;
                    }
                });
        }
    }
});
```

**DEBERÍA SER (Backend - ✅ CORRECTO):**
```blade
<!-- En el controller, pasar datos ya procesados -->
@foreach($recibos as $recibo)
    <tr data-orden-id="{{ $recibo->id }}">
        <td class="descripcion-prenda-texto">
            {{ $recibo->primeraPrenda->nombre ?? 'Sin nombre' }}
        </td>
        <!-- otro contenido -->
    </tr>
@endforeach
```

**Ventajas:**
- ✅ 0 fetch requests
- ✅ Datos confiables (DB directo)
- ✅ SEO friendly
- ✅ Más rápido

**Backend Controller:**
```php
// app/Http/Controllers/RecibosController.php
public function index()
{
    $recibos = ReciboCostura::with(['orden.prendas'])
        ->whereHas('orden')
        ->get()
        ->map(function($recibo) {
            $recibo->primeraPrenda = $recibo->orden?->prendas->first();
            return $recibo;
        });
    
    return view('registros.recibos-costura', [
        'recibos' => $recibos,
        'totalCantidadGlobal' => collect($recibos)->sum('cantidad')
    ]);
}
```

---

### 2. **Lógica de Filtros (Línea ~320-550)**

**ACTUAL (Frontend - ❌ MAL):**
```javascript
window.openFilterModal = function(filterType) { ... }
window.applyFilters = function() {
    const tbody = document.getElementById('tablaRecibosBody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const cellText = cells[columnIndex].textContent.trim();
        
        if (isVisible) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
};
```

**PROBLEMAS:**
- ❌ Filtra múltiples filtros en memoria (lento con 1000+ filas)
- ❌ No persiste filtros (si recargas página se pierden)
- ❌ No se puede paginar
- ❌ No hay historial de búsquedas

**DEBERÍA SER (Backend - ✅ CORRECTO):**
```php
// app/Http/Controllers/RecibosController.php
public function index(Request $request)
{
    $query = ReciboCostura::query();
    
    // Filtrar por estado
    if ($request->has('estados') && !empty($request->estado)) {
        $query->whereIn('estado', (array)$request->estado);
    }
    
    // Filtrar por área
    if ($request->has('areas') && !empty($request->area)) {
        $query->whereIn('area', (array)$request->area);
    }
    
    // Filtrar por cliente
    if ($request->has('clientes') && !empty($request->cliente)) {
        $query->whereIn('cliente_id', (array)$request->cliente);
    }
    
    // Filtrar por descripción
    if ($request->filled('descripcion')) {
        $query->whereHas('orden.prendas', function($q) {
            $q->where('nombre', 'like', '%' . request('descripcion') . '%');
        });
    }
    
    // Aplicar ordenamiento
    $query->orderBy(
        $request->get('sort_by', 'created_at'),
        $request->get('sort_dir', 'desc')
    );
    
    // Paginar
    $recibos = $query->paginate(50);
    
    // Si es AJAX, retornar JSON
    if ($request->ajax()) {
        return response()->json($recibos);
    }
    
    return view('registros.recibos-costura', compact('recibos'));
}
```

**Frontend (simplificado):**
```javascript
window.applyFilters = function() {
    const filterType = document.getElementById('filterModal').getAttribute('data-filter-type');
    const selectedValues = Array.from(
        document.querySelectorAll('input[type="checkbox"]:checked')
    ).map(cb => cb.value);
    
    // Enviar al backend
    const params = new URLSearchParams();
    params.append(filterType, selectedValues);
    
    // Recargar tabla o paginar
    window.location.search = params.toString();  // O hacer fetch AJAX
};
```

**Ventajas:**
- ✅ Maneja millones de registros
- ✅ Filtros persistentes (en URL)
- ✅ Paginación
- ✅ Búsqueda de texto rápida (índices DB)
- ✅ Auditoría/historial

---

### 3. **Extracción de Pedido ID (Línea ~655-695, ~1280)**

**ACTUAL (Frontend - ❌ MAL):**
```javascript
function verDetallesRecibo(reciboId) {
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    let pedidoId = null;
    
    // Intentar extraer ID de 3 formas diferentes
    const enlacePedido = fila.querySelector('a[href*="/registros/"]');
    if (enlacePedido) {
        const href = enlacePedido.getAttribute('href');
        const pedidoIdMatch = href.match(/\/registros\/(\d+)/);
        if (pedidoIdMatch) {
            pedidoId = parseInt(pedidoIdMatch[1]);
        }
    }
    
    if (!pedidoId) {
        const pedidoIdAttr = fila.getAttribute('data-pedido-id');
        if (pedidoIdAttr) {
            pedidoId = parseInt(pedidoIdAttr);
        }
    }
    
    if (!pedidoId) {
        const dropdownDiaEntrega = fila.querySelector('.dia-entrega-dropdown');
        if (dropdownDiaEntrega) {
            const dropdownIdAttr = dropdownDiaEntrega.getAttribute('data-orden-id');
            if (dropdownIdAttr) {
                pedidoId = parseInt(dropdownIdAttr);
            }
        }
    }
    // ...
}
```

**PROBLEMAS:**
- ❌ 3 formas diferentes de obtener el mismo dato
- ❌ Frágil (si cambias HTML, se rompe)
- ❌ Duplicado en 3 funciones
- ❌ Parsing ad-hoc de URLs

**DEBERÍA SER (Backend - ✅ CORRECTO):**
```blade
<!-- En el Blade template -->
<tr data-orden-id="{{ $recibo->id }}" 
    data-pedido-id="{{ $recibo->orden->id }}"
    data-prenda-id="{{ $recibo->orden->prendas->first()->id ?:'null' }}">
    
    <td>
        <button class="btn-ver-dropdown"
                data-recibo-id="{{ $recibo->id }}"
                data-pedido-id="{{ $recibo->orden->id }}"
                data-prenda-id="{{ $recibo->orden->prendas->first()->id }}">
            Ver
        </button>
    </td>
</tr>
```

**Frontend:**
```javascript
function verDetallesRecibo(reciboId) {
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    const pedidoId = fila.getAttribute('data-pedido-id');  // ✅ Directo
    const prendaId = fila.getAttribute('data-prenda-id');  // ✅ Directo
    
    if (!pedidoId || !prendaId) {
        showError('Datos incompletos: contacta a soporte');
        return;
    }
    
    openRecibo(pedidoId, prendaId);
}
```

---

### 4. **Validaciones (Línea ~1080-1110)**

**ACTUAL (Frontend - ❌ MAL):**
```javascript
async function handleAgregarProcesoDesdeBadge() {
    const area = document.getElementById('procesoArea').value;
    let encargado = '';
    const selectEncargado = document.getElementById('procesoEncargadoSelect');
    const inputEncargado = document.getElementById('procesoEncargado');
    
    if (selectEncargado && selectEncargado.offsetParent !== null) {
        const selectedOption = selectEncargado.options[selectEncargado.selectedIndex];
        encargado = selectedOption ? selectedOption.text : '';
    } else if (inputEncargado) {
        encargado = inputEncargado.value.toUpperCase();
    }
    
    if (!area) {
        showError('Por favor selecciona un área/proceso');
        return;
    }
    
    const areaLower = area.toLowerCase();
    const needsEncargado = ['corte', 'costura', 'control de calidad'];
    const areaRequiresEncargado = needsEncargado.some(
        reqArea => areaLower.includes(reqArea)
    );
    
    if (areaRequiresEncargado && !encargado.trim()) {
        showError('Por favor selecciona o ingresa el encargado');
        return;
    }
}
```

**PROBLEMAS:**
- ❌ Validación se puede bypassear con DevTools
- ❌ Reglas hardcodeadas en JS
- ❌ Comportamiento inconsistente

**DEBERÍA SER (Backend - ✅ CORRECTO):**
```php
// app/Models/Proceso.php
protected $rules = [
    'pedido_produccion_id' => 'required|integer|exists:pedido_produccion',
    'prenda_id' => 'required|integer',
    'area' => 'required|in:' . implode(',', config('recibos.areas')),
    'encargado' => 'required_if:area,corte,costura,control de calidad|string|max:100',
    'estado' => 'required|in:pendiente,en_proceso,completado',
];

// app/Http/Controllers/SeguimientoProcesosController.php
public function guardar(Request $request)
{
    $validated = $request->validate(Proceso::$rules);  // ✅ Validación segura
    
    // Verificar permisos
    $this->authorize('crear-proceso', Proceso::class);
    
    $proceso = Proceso::create([
        ...$validated,
        'usuario_id' => auth()->id(),  // Auditoría
        'ip_address' => request()->ip(),
    ]);
    
    return response()->json([
        'success' => true,
        'action' => 'creado',
        'proceso' => $proceso
    ]);
}
```

---

## 🟡 IMPORTANTE: Opciones de Datos

**ACTUAL (Frontend - ❌ MAL):**
```javascript
const titles = {
    'descripcion': 'Filtrar por Descripción',
    'cliente': 'Filtrar por Cliente',
    'estado': 'Filtrar por Estado',
    'area': 'Filtrar por Área',
    // ... 8 más hardcodeados
};
```

**DEBERÍA SER (Backend - ✅ CORRECTO):**
```php
// app/Http/Controllers/RecibosController.php
public function index()
{
    $recibos = ReciboCostura::paginate(50);
    $filterOptions = [
        'estados' => ReciboCostura::distinct()->pluck('estado'),
        'areas' => Proceso::distinct()->pluck('area'),
        'clientes' => Cliente::pluck('nombre', 'id'),
    ];
    
    return view('registros.recibos-costura', [
        'recibos' => $recibos,
        'filterOptions' => $filterOptions,  // ✅ Dinámico
    ]);
}
```

```blade
<!-- En Blade -->
@foreach($filterOptions['estados'] as $estado)
    <label>
        <input type="checkbox" name="estado" value="{{ $estado }}">
        {{ $estado }}
    </label>
@endforeach
```

---

## ✅ RESUMEN: Qué Va Dónde

| Componente | Actual | Debería Ser | Impacto |
|-----------|--------|-------------|---------|
| **Cargar nombres prendas** | JS fetch N+1 | Backend render | -50 requests |
| **Filtros avanzados** | Frontend (display:none) | Backend API | Soporta 1M+ rows |
| **Validaciones** | Frontend | **Backend + Frontend** | Seguridad |
| **Datos de filtros** | Hardcoded JS | Backend dinámico | Mantenible |
| **Extracción IDs** | Parsing URL | HTML attributes | Robusto |
| **Permisos** | NO existe | Backend Gate/Policy | Seguridad |
| **Auditoría** | NO existe | Backend middleware | Legal/Soporte |
| **Toasts/Notificaciones** | Frontend | Frontend ✓ | OK así |
| **Dropdowns UI** | Frontend | Frontend ✓ | OK así |
| **Animaciones** | Frontend | Frontend ✓ | OK así |

---

## 🚀 Plan Refactorización Backend-Primero

### FASE 1: Datos Base
```php
// app/Http/Controllers/RecibosController.php
public function index(Request $request)
{
    // 1. Cargar recibos con relaciones
    $query = ReciboCostura::with(['orden.prendas', 'procesos'])
        ->when($request->filled('estado'), fn($q) => 
            $q->where('estado', $request->estado)
        )
        ->when($request->filled('area'), fn($q) => 
            $q->whereHas('procesos', fn($sq) => 
                $sq->where('area', $request->area)
            )
        );
    
    // 2. Proporcionar opciones de filtro
    $filterOptions = $this->getFilterOptions();
    
    // 3. Retornar vista con todo
    return view('registros.recibos-costura', [
        'recibos' => $query->paginate(50),
        'filterOptions' => $filterOptions,
        'totalCantidadGlobal' => $query->sum('cantidad'),
    ]);
}

private function getFilterOptions()
{
    return [
        'estados' => ReciboCostura::distinct('estado')->pluck('estado'),
        'areas' => Proceso::distinct('area')->pluck('area'),
        'clientes' => Cliente::pluck('nombre', 'id'),
    ];
}
```

### FASE 2: Rutas API para operaciones
```php
// routes/api.php
Route::prefix('recibos-costura')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [RecibosController::class, 'index']);
    Route::get('{recibo}/detalles', [RecibosController::class, 'show']);
    Route::post('{recibo}/agregar-proceso', [ProcesosController::class, 'store']);
    Route::put('{recibo}/actualizar-proceso', [ProcesosController::class, 'update']);
    Route::get('{recibo}/seguimiento', [SeguimientoController::class, 'show']);
});
```

### FASE 3: Simplificar Frontend
```javascript
// js/modules/recibos-manager.js
class RecibosManager {
    async loadRecibos(filters = {}) {
        const params = new URLSearchParams(filters);
        const response = await fetch(`/api/recibos-costura/?${params}`);
        return response.json();
    }
    
    async agregarProceso(reciboId, area, encargado) {
        const response = await fetch(`/api/recibos-costura/${reciboId}/agregar-proceso`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ area, encargado }),
        });
        return response.json();
    }
}

// Uso:
const manager = new RecibosManager();
manager.agregarProceso(123, 'costura', 'Juan').then(result => {
    if (result.success) showSuccess('Proceso agregado');
});
```

---

## 📋 Checklist de Migración

- [ ] Crear queries backend para cargar nombres de prendas
- [ ] Implementar filtrado server-side en RecibosController
- [ ] Crear API endpoint para opciones de filtro
- [ ] Agregar validación server-side en ProcesosController
- [ ] Refactorizar frontend para consumir API
- [ ] Remover lógica duplicada de extracción de IDs
- [ ] Agregar auditoría a procesos guardados
- [ ] Implementar gates/policies para permisos
- [ ] Tests de API endpoints

