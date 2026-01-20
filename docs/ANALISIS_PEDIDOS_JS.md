 ANÁLISIS EXHAUSTIVO: pedidos.js
================================================================================

ARCHIVO: public/js/asesores/pedidos.js
TAMAÑO: ~600 líneas
ESTADO:  Alto Acoplamiento + Duplicación Masiva

================================================================================
1️⃣ DUPLICACIÓN CRÍTICA - RECOLECCIÓN DE PRODUCTOS
================================================================================

LÍNEAS: guardarPedido() (40 líneas) + actualizarPedido() (40 líneas)
PROBLEMA: 100% código duplicado para recolectar productos

 ANTES (guardarPedido):
```javascript
const productosItems = document.querySelectorAll('.producto-item');
productosItems.forEach((item, index) => {
    const nombreInput = item.querySelector(`[name*="nombre_producto"]`);
    const descripcionInput = item.querySelector(`[name*="descripcion"]`);
    const telaInput = item.querySelector(`[name*="tela"]`);
    const tipoMangaInput = item.querySelector(`[name*="tipo_manga"]`);
    const colorInput = item.querySelector(`[name*="color"]`);
    const tallaInput = item.querySelector(`[name*="talla"]`);
    const generoInput = item.querySelector(`[name*="genero"]`);
    const cantidadInput = item.querySelector(`[name*="cantidad"]`);
    const refHiloInput = item.querySelector(`[name*="ref_hilo"]`);
    const precioInput = item.querySelector(`[name*="precio_unitario"]`);
    
    if (nombreInput && cantidadInput) {
        productos.push({...});
    }
});
```

 IDÉNTICO EN: actualizarPedido() (líneas duplicadas 1:1)

 SOLUCIÓN: ProductCollectorService
```javascript
// Centralizar en UN servicio
ProductCollector.recolectar();
```

IMPACTO: ⭐⭐⭐⭐⭐ Muy Alto - Reduce 40 líneas

================================================================================
2️⃣ DUPLICACIÓN - PATTERN FETCH (guardarPedido + actualizarPedido)
================================================================================

LÍNEAS: guardarPedido() + actualizarPedido()
PROBLEMA: Mismo fetch, mismo manejo de respuesta, 95% idéntico

 ANTES:
```javascript
// EN AMBAS FUNCIONES - IDENTICO
const response = await fetch(ruta, {
    method: 'POST/PUT',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify(data)
});

const result = await response.json();

if (result.success) {
    mostrarToast('...', 'success');
    setTimeout(() => {
        window.location.href = '/...';
    }, 1500);
} else {
    mostrarToast(result.message || 'Error', 'error');
}
```

 SOLUCIÓN: PedidoAPIService
```javascript
class PedidoAPIService {
    async crearPedido(data) { return this.enviar('POST', '/asesores/pedidos', data); }
    async actualizarPedido(id, data) { return this.enviar('PUT', `/asesores/pedidos/${id}`, data); }
    
    #enviar(method, url, data) {
        return fetch(url, { method, headers: {...}, body: JSON.stringify(data) })
            .then(r => r.json());
    }
}
```

IMPACTO: ⭐⭐⭐⭐ Alto - Reduce 50 líneas

================================================================================
3️⃣ DUPLICACIÓN - EVENT LISTENERS
================================================================================

LÍNEAS: agregarProducto() (~30 líneas) + agregarEventListenersProductos() (~50 líneas)
PROBLEMA: Mismo addEventListener duplicado en dos funciones

 PROBLEMA:
```javascript
// EN agregarProducto() - LÍNEAS 33-47
const cantidad = clone.querySelector('.producto-cantidad');
cantidad.addEventListener('input', function() {
    calcularSubtotal(this.closest('.producto-item'));
    actualizarResumen();
});

const precio = clone.querySelector('.producto-precio');
precio.addEventListener('input', function() {
    calcularSubtotal(this.closest('.producto-item'));
    actualizarResumen();
});

// EN agregarEventListenersProductos() - LÍNEAS 76-95
// EXACTAMENTE LO MISMO otra vez
const cantidad = producto.querySelector('.producto-cantidad');
cantidad.addEventListener('input', function() {
    calcularSubtotal(this.closest('.producto-item'));
    actualizarResumen();
});
```

 SOLUCIÓN: EventListenerService
```javascript
class ProductEventManager {
    setupListeners(productElement) {
        this.onCantidadChange(productElement);
        this.onPrecioChange(productElement);
    }
}

// USO:
new ProductEventManager().setupListeners(clone);
```

IMPACTO: ⭐⭐⭐ Medio - Reduce 25 líneas

================================================================================
4️⃣ CUSTOM TOAST vs UIModalService
================================================================================

LÍNEAS: mostrarToast() (35 líneas)
PROBLEMA: Reimplementando lo que UIModalService ya hace

 ACTUAL:
```javascript
function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `...`;
    
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `...`; // 30 líneas de CSS
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
```

 USAR UIModalService:
```javascript
// Reemplazar TODAS las llamadas a mostrarToast() con:
UI.toastExito('Pedido creado exitosamente');
UI.toastError('Error al guardar');
```

IMPACTO: ⭐⭐⭐⭐ Alto - Elimina 35 líneas + CSS incrustado

================================================================================
5️⃣ VALIDACIÓN REPETIDA
================================================================================

LÍNEAS: guardarPedido() línea 86 + actualizarPedido() SIN VALIDACIÓN
PROBLEMA: Solo valida en crear, no en editar

 ACTUAL:
```javascript
if (crear && productos.length === 0) {
    mostrarToast('Debes agregar al menos un producto', 'error');
    return;
}
```

 SOLUCIÓN: ProductValidator
```javascript
if (!Validator.check(productos.length > 0 || !crear, 'Debes agregar al menos un producto')) {
    return;
}
```

IMPACTO: ⭐⭐⭐ Medio - Consistencia + Validación uniforme

================================================================================
6️⃣ GALERÍA DE COSTURA - CÓDIGO PROCEDURAL (150+ líneas)
================================================================================

LÍNEAS: toggleFactura(), toggleGaleria(), loadGaleria(), mostrarImagenGrande(), etc
PROBLEMA: Todo procedural, sin abstracción, reutilizable en otros modales

 ESTADO ACTUAL:
- toggleFactura(): 15 líneas de manipulación DOM
- toggleGaleria(): 20 líneas de manipulación DOM  
- loadGaleria(): 60 líneas de construcción HTML
- mostrarImagenGrande(): 30 líneas
- cambiarImagen(): 10 líneas
- cerrarImagenGrande(): 5 líneas
- TOTAL: ~150 líneas

 SOLUCIÓN: GaleriaService
```javascript
class GaleriaService {
    constructor(containerId, pedidoId) { /* ... */ }
    mostrar() { /* 15 líneas limpias */ }
    cargar() { /* 40 líneas limpias */ }
    mostrarImagen(index) { /* 20 líneas limpias */ }
    cambiarImagen(direction) { /* 5 líneas */ }
}

// USO:
const galeria = new GaleriaService('container', pedidoId);
galeria.cargar();
```

IMPACTO: ⭐⭐⭐⭐⭐ Muy Alto - Reduce 80+ líneas + Reutilizable

================================================================================
7️⃣ CALCULAR SUBTOTAL - LÓGICA ESPARCIDA
================================================================================

LÍNEAS: calcularSubtotal() (10 líneas) + EN DOS event listeners (2x 3 líneas)
PROBLEMA: Lógica aritmética sin validación

 ESTADO ACTUAL:
```javascript
function calcularSubtotal(productoItem) {
    const cantidad = productoItem.querySelector('.producto-cantidad');
    const precio = productoItem.querySelector('.producto-precio');
    const subtotal = productoItem.querySelector('.producto-subtotal');
    
    if (cantidad && precio && subtotal) {
        const cantidadVal = parseFloat(cantidad.value) || 0;
        const precioVal = parseFloat(precio.value) || 0;
        const subtotalVal = cantidadVal * precioVal;
        
        subtotal.value = subtotalVal > 0 ? `$${subtotalVal.toFixed(2)}` : '$0.00';
    }
}
```

 USAR Validator:
```javascript
Validator.check(cantidad && precio && subtotal, 'Inputs no encontrados', () => {
    const resultado = Calculator.multiplicar(cantidad.value, precio.value);
    subtotal.value = Formatter.dinero(resultado);
});
```

IMPACTO: ⭐⭐ Bajo - Validación mejorada

================================================================================
 RESUMEN DE IMPACTO
================================================================================

| Duplicación | Líneas | Impacto | Solución |
|-------------|--------|---------|----------|
| Recolección Productos | 40 | ⭐⭐⭐⭐⭐ | ProductCollectorService |
| Fetch Pattern | 50 | ⭐⭐⭐⭐ | PedidoAPIService |
| Event Listeners | 25 | ⭐⭐⭐ | ProductEventManager |
| Toast Notifications | 35 | ⭐⭐⭐⭐ | UIModalService |
| Galería Procedural | 150 | ⭐⭐⭐⭐⭐ | GaleriaService |
| Validación | 10 | ⭐⭐ | ValidationService |
| **TOTAL** | **310 líneas** | **CRÍTICO** | **Refactorizar YA** |

================================================================================
 REFACTORIZACIÓN PROPUESTA
================================================================================

PASO 1: Crear ProductCollectorService (20 líneas)
├── recolectar(selector)
├── validar(productos)

PASO 2: Crear PedidoAPIService (30 líneas)
├── crearPedido(data)
├── actualizarPedido(id, data)
└── #enviar(method, url, data)

PASO 3: Crear ProductEventManager (25 líneas)
├── setupListeners(element)
├── onCantidadChange()
├── onPrecioChange()

PASO 4: Reemplazar mostrarToast con UI.*
├── mostrarToast() → UI.toastExito()
├── Global 15 líneas ahorradas

PASO 5: Crear GaleriaService (80 líneas)
├── mostrar()
├── cargar()
├── mostrarImagen()
├── cambiarImagen()

PASO 6: Refactorizar guardarPedido + actualizarPedido
├── Usar ProductCollector
├── Usar PedidoAPIService
├── Usar Validator
├── Usar UI.toastExito()

================================================================================
📈 RESULTADOS ESPERADOS
================================================================================

ANTES:
- pedidos.js: 600 líneas
- Duplicación: 310 líneas
- Acoplamiento: ALTO
- Reutilización: NULA

DESPUÉS:
- pedidos.js: 250 líneas (-58%)
- ProductCollectorService: 20 líneas (REUTILIZABLE)
- PedidoAPIService: 30 líneas (REUTILIZABLE)
- ProductEventManager: 25 líneas (REUTILIZABLE)
- GaleriaService: 80 líneas (REUTILIZABLE)
- Duplicación: 0
- Acoplamiento: BAJO
- Reutilización: ALTA

================================================================================
 PATRONES A APLICAR
================================================================================

1. COLLECTION PATTERN - ProductCollectorService
2. API PATTERN - PedidoAPIService  
3. EVENT MANAGER PATTERN - ProductEventManager
4. SERVICE LOCATOR - GaleriaService
5. VALIDATOR PATTERN - ValidationService (ya existe)
6. COMMAND PATTERN - Operaciones fetch/actualizar

================================================================================
