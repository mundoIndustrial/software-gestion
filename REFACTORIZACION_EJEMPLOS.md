# Ejemplos de Refactorización: Lógica de Frontend → Backend

## 1. GESTIÓN DE ITEMS - Antes y Después

### ❌ ANTES (Frontend maneja todo)
```javascript
class GestionItemsUI {
    constructor() {
        this.prendas = [];      
        this.epps = [];         
        this.ordenItems = [];   // {tipo, index}
    }

    agregarPrendaAlOrden(prenda) {
        const index = this.prendas.length;
        this.prendas.push(prenda);
        this.ordenItems.push({ tipo: 'prenda', index });
    }

    obtenerItemsOrdenados() {
        const itemsOrdenados = [];
        this.ordenItems.forEach(({ tipo, index }) => {
            if (tipo === 'prenda' && this.prendas[index]) {
                itemsOrdenados.push(this.prendas[index]);
            }
        });
        return itemsOrdenados;
    }

    async eliminarItem(index) {
        const itemsOrdenados = this.obtenerItemsOrdenados();
        const itemEnPosicion = itemsOrdenados[index];
        
        // Buscar tipo
        let tipoBuscado, indiceBuscado;
        if (itemEnPosicion.nombre_prenda) {
            tipoBuscado = 'prenda';
            indiceBuscado = this.prendas.findIndex(p => p === itemEnPosicion);
        } else if (itemEnPosicion.nombre_completo) {
            tipoBuscado = 'epp';
            indiceBuscado = this.epps.findIndex(e => e === itemEnPosicion);
        }
        
        // Eliminar de arrays
        if (tipoBuscado === 'prenda') {
            this.prendas.splice(indiceBuscado, 1);
        } else {
            this.epps.splice(indiceBuscado, 1);
        }
        
        // Reconstruir índices
        let prendaIdx = 0, eppIdx = 0;
        this.ordenItems.forEach(item => {
            if (item.tipo === 'prenda') item.index = prendaIdx++;
            else if (item.tipo === 'epp') item.index = eppIdx++;
        });
        
        this.ordenItems.splice(index, 1);
    }
}
```

### ✅ DESPUÉS (Frontend simple, Backend maneja lógica)
```javascript
class GestionItemsUI {
    constructor() {
        this.items = [];  // Simple: solo almacena
    }

    async agregarPrenda(prendaData) {
        // Frontend: solo recolecta datos
        const response = await this.apiService.crearPrenda(prendaData);
        
        if (response.success) {
            // Backend retorna lista actualizada
            this.items = response.items;
            await this.renderer.actualizar(this.items);
            this.notificationService.exito('Prenda agregada');
        } else {
            // Backend retorna errores específicos
            this.notificationService.error(response.errors?.[0] || 'Error');
        }
    }

    async eliminarItem(itemId) {
        // Frontend: solo envía ID
        const response = await this.apiService.eliminarItem(itemId);
        
        if (response.success) {
            // Backend retorna items actualizados y en el orden correcto
            this.items = response.items;
            await this.renderer.actualizar(this.items);
            this.notificationService.exito('Item eliminado');
        } else {
            this.notificationService.error(response.errors?.[0] || 'Error');
        }
    }
}
```

---

## 2. VALIDACIÓN - Antes y Después

### ❌ ANTES (Validación duplicada en frontend y backend)
```javascript
async agregarPrendaNueva() {
    // Validación en FRONTEND
    const tieneTallas = prendaData.cantidad_talla && 
        Object.values(prendaData.cantidad_talla).some(genero => 
            Object.keys(genero).length > 0
        );

    if (!tieneTallas) {
        this.notificationService?.advertencia('Por favor selecciona al menos una talla');
        return;
    }

    // Luego Backend también valida
    const validacion = await this.apiService.validarPedido(pedidoData);
    if (!validacion.success) {
        alert('Errores en el pedido:\n' + validacion.errores.join('\n'));
        return;
    }
}
```

### ✅ DESPUÉS (Validación centralizada en backend)
```javascript
async agregarPrendaNueva() {
    // Frontend: solo validación UI básica (campos requeridos, etc)
    if (!prendaData.nombre_prenda?.trim()) {
        this.notificationService.error('El nombre es requerido');
        return;
    }

    // Backend: validación de TODAS las reglas de negocio
    const response = await this.apiService.crearPrenda(prendaData);
    
    if (response.success) {
        this.items = response.items;
        this.renderer.actualizar(this.items);
    } else if (response.validationErrors) {
        // Backend retorna errores estructurados
        response.validationErrors.forEach(error => {
            this.notificationService.error(error.message);
        });
    }
}
```

**Backend (Laravel):**
```php
public function crearPrenda(CreatePrendaRequest $request)  // ValidateRequest automático
{
    $prenda = $this->prendaService->crear($request->validated());
    
    // Lanzar excepto si hay error
    if (!$prenda) {
        return response()->json([
            'success' => false,
            'validationErrors' => [
                ['field' => 'tallas', 'message' => 'Debe seleccionar al menos una talla'],
            ]
        ], 422);
    }
    
    return response()->json([
        'success' => true,
        'items' => $this->obtenerItems()
    ]);
}
```

---

## 3. CONSTRUCCIÓN DE DATOS - Antes y Después

### ❌ ANTES (Frontend construye estructura compleja)
```javascript
const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
    this.prendaEditIndex,
    this.prendas
);

// Método construye estructura compleja:
construirPrendaDesdeFormulario() {
    return {
        nombre_prenda: document.getElementById('nombre').value,
        descripcion: document.getElementById('descripcion').value,
        origen: document.getElementById('origen').value,
        imagenes: window.imagenesArray,
        telas: this.telas.map(t => ({
            id: t.id,
            cantidad: t.cantidad,
            costo: t.costo
        })),
        cantidad_talla: {
            dama: { xs: 5, s: 10, ... },
            caballero: { m: 8, l: 12, ... }
        },
        variantes: this.variantes,
        procesos: this.procesosSeleccionados,
        // ... más campos
    };
}
```

### ✅ DESPUÉS (Frontend simple, Backend valida estructura)
```javascript
async agregarPrendaNueva() {
    // Frontend: recolecta datos simples del formulario
    const formData = new FormData();
    formData.append('nombre', document.getElementById('nombre').value);
    formData.append('descripcion', document.getElementById('descripcion').value);
    formData.append('origen', document.getElementById('origen').value);
    formData.append('imagenes', this.imagenes); // archivos
    
    // Tallas como array simple
    this.tallasSeleccionadas.forEach(talla => {
        formData.append('tallas[]', talla.id);
    });
    
    // Backend: procesa, valida, persiste
    const response = await this.apiService.crearPrenda(formData);
    
    if (response.success) {
        this.items = response.items;
        this.renderer.actualizar(this.items);
    }
}
```

**Backend (Laravel):**
```php
public function crearPrenda(CreatePrendaRequest $request)
{
    // Laravel Request valida automático
    // Buildea estructura interna necesaria
    $datosProcessados = [
        'nombre_prenda' => $request->nombre,
        'descripcion' => $request->descripcion,
        'tallas' => $this->procesarTallas($request->tallas), // Backend sabe cómo
        'imagenes' => $this->guardarImagenes($request->file('imagenes')),
        // Backend define estructura interna
    ];
    
    $prenda = $this->prendaService->crear($datosProcessados);
    return response()->json(['success' => true, 'items' => $this->obtenerItems()]);
}
```

---

## 4. SINCRONIZACIÓN - Antes y Después

### ❌ ANTES (Acoplamiento via window global)
```javascript
// En gestion-items-pedido.js
if (tipoBuscado === 'prenda' && window.gestorPrendaSinCotizacion?.eliminar) {
    window.gestorPrendaSinCotizacion.eliminar(indiceBuscado);
}

// window.gestorPrendaSinCotizacion también tiene su propio estado
// window.otrasCosas.prendas también puede tener prendas
// Múltiples fuentes de verdad
```

### ✅ DESPUÉS (EventBus centralizado)
```javascript
// evento-bus.js
class EventBus {
    on(event, callback) { /* subscriber pattern */ }
    off(event, callback) { }
    emit(event, data) { /* notify all listeners */ }
}

// gestion-items-pedido.js
async eliminarItem(itemId) {
    const response = await this.apiService.eliminarItem(itemId);
    if (response.success) {
        // Emitir evento que otros componentes escuchan
        this.eventBus.emit('items:actualizado', response.items);
    }
}

// gestor-prenda-sin-cotizacion.js
constructor() {
    // Escuchar evento global
    this.eventBus.on('items:actualizado', (items) => {
        this.prendas = items.filter(i => i.tipo === 'prenda');
    });
}
```

---

## 5. RESPUESTAS DE API - Backend debe retornar

### ✅ Agregar Item
```json
{
  "success": true,
  "item": { "id": 1, "nombre": "...", "tipo": "prenda" },
  "items": [/* lista actualizada y ordenada por backend */],
  "message": "Prenda agregada correctamente"
}
```

### ✅ Eliminar Item (con cascada manejada por backend)
```json
{
  "success": true,
  "items": [/* lista actualizada, sin el item eliminado */],
  "message": "Item eliminado",
  "relatedDeleted": {
    "procesos": 3,  // Se eliminaron 3 procesos asociados
    "variantes": 2
  }
}
```

### ✅ Error de Validación
```json
{
  "success": false,
  "validationErrors": [
    {
      "field": "tallas",
      "message": "Debe seleccionar al menos una talla"
    },
    {
      "field": "nombre_prenda",
      "message": "El nombre no puede estar vacío"
    }
  ]
}
```

### ✅ Obtener Items Ordenados
```json
{
  "success": true,
  "items": [
    {"id": 1, "tipo": "prenda", "nombre": "Camisa", "orden": 1},
    {"id": 5, "tipo": "epp", "nombre": "Guantes", "orden": 2},
    {"id": 2, "tipo": "prenda", "nombre": "Pantalón", "orden": 3}
  ],
  "total": 3
}
```
**Backend retorna `orden`, no requiere recalcular índices en frontend**

---

## 6. ACTUALIZAR ItemAPIService

```javascript
// ANTES: esperar lista actualizada
class ItemAPIService {
    async eliminarItem(index) {
        // ... lógica mixta
        return resultado;
    }
}

// DESPUÉS: API limpia, Backend responsable
class ItemAPIService {
    async crearItem(itemData) {
        return fetch('/api/items', {
            method: 'POST',
            body: JSON.stringify(itemData)
        }).then(r => r.json());
    }

    async eliminarItem(itemId) {
        return fetch(`/api/items/${itemId}`, {
            method: 'DELETE'
        }).then(r => r.json());
    }

    async actualizarItem(itemId, itemData) {
        return fetch(`/api/items/${itemId}`, {
            method: 'PUT',
            body: JSON.stringify(itemData)
        }).then(r => r.json());
    }

    async obtenerItems(filtros = {}) {
        const query = new URLSearchParams(filtros).toString();
        return fetch(`/api/items?${query}`).then(r => r.json());
    }
}
```
