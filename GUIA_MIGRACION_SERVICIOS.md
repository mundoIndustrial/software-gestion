# Guía de Migración: Integración de Servicios en crear-pedido-editable.js

## 📋 Servicios Creados (Fase 1)

1. ✅ **state-service.js** - Gestión de estado centralizada
2. ✅ **api-service.js** - Llamadas al backend
3. ✅ **validation-service.js** - Validaciones cliente
4. ✅ **image-service.js** - Gestión de imágenes

## 🔄 Cómo Migrar el Código Existente

### Paso 1: Cargar los Servicios en la Vista

Agregar en `crear-desde-cotizacion-editable.blade.php` **ANTES** de `crear-pedido-editable.js`:

```html
<!-- SERVICIOS CORE (DEBEN CARGAR PRIMERO) -->
<script src="{{ asset('js/services/state-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/services/api-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/services/validation-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/services/image-service.js') }}?v={{ time() }}"></script>
```

---

### Paso 2: Migrar Variables Globales a State Service

#### ANTES (crear-pedido-editable.js):
```javascript
let tallasDisponiblesCotizacion = [];
let currentLogoCotizacion = null;
let currentEspecificaciones = null;
let currentEsReflectivo = false;
let currentDatosReflectivo = null;
let currentEsLogo = false;
let currentTipoCotizacion = 'P';
window.prendasCargadas = [];
window.prendasFotosNuevas = {};
window.telasFotosNuevas = {};
```

#### DESPUÉS:
```javascript
// Usar PedidoState en lugar de variables globales
// Ya no es necesario declarar estas variables

// Ejemplo de uso:
window.PedidoState.setTipo('P');
window.PedidoState.setPrendas(prendas);
window.PedidoState.setTallasDisponibles(tallas);
```

---

### Paso 3: Migrar Llamadas API a API Service

#### ANTES:
```javascript
async function cargarPrendasDesdeCotizacion(cotizacionId) {
    try {
        const response = await fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacionId}`);
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error('Error al cargar datos');
        }
        
        // Procesar datos...
        return data;
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
}
```

#### DESPUÉS:
```javascript
async function cargarPrendasDesdeCotizacion(cotizacionId) {
    try {
        const data = await window.ApiService.obtenerDatosCotizacion(cotizacionId);
        
        // Procesar datos...
        return data;
    } catch (error) {
        window.ApiService.handleError(error, 'Cargar cotización');
    }
}
```

---

### Paso 4: Migrar Validaciones a Validation Service

#### ANTES:
```javascript
function validarFormulario() {
    let errores = [];
    
    if (!cliente || cliente.trim() === '') {
        errores.push('El cliente es requerido');
    }
    
    if (prendas.length === 0) {
        errores.push('Debe agregar al menos una prenda');
    }
    
    if (errores.length > 0) {
        Swal.fire({
            icon: 'error',
            title: 'Errores de Validación',
            html: errores.join('<br>')
        });
        return false;
    }
    
    return true;
}
```

#### DESPUÉS:
```javascript
function validarFormulario() {
    const formData = {
        cliente: document.getElementById('cliente_editable').value,
        asesora: document.getElementById('asesora_editable').value,
        tipo: window.PedidoState.getTipo(),
        prendas: window.PedidoState.getPrendas(),
        logo: window.PedidoState.getLogo()
    };
    
    return window.ValidationService.validateAndShow(
        () => window.ValidationService.validatePedidoCompleto(formData),
        'Errores de Validación'
    );
}
```

---

### Paso 5: Ejemplos de Refactorización Completos

#### Ejemplo 1: Cargar Cotización

**ANTES:**
```javascript
searchInput.addEventListener('input', async function() {
    const searchTerm = this.value.toLowerCase();
    
    if (searchTerm.length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    const filtered = cotizacionesData.filter(cot => 
        cot.numero.toLowerCase().includes(searchTerm) ||
        cot.cliente.toLowerCase().includes(searchTerm)
    );
    
    // Mostrar resultados...
});

async function seleccionarCotizacion(cotizacion) {
    try {
        const response = await fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacion.id}`);
        const data = await response.json();
        
        // Guardar en variables globales
        currentLogoCotizacion = data.logo;
        currentEspecificaciones = data.especificaciones;
        prendasCargadas = data.prendas;
        tallasDisponiblesCotizacion = data.tallas;
        
        renderizarPrendas();
    } catch (error) {
        console.error('Error:', error);
    }
}
```

**DESPUÉS:**
```javascript
searchInput.addEventListener('input', async function() {
    const searchTerm = this.value.toLowerCase();
    
    if (searchTerm.length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    const filtered = cotizacionesData.filter(cot => 
        cot.numero.toLowerCase().includes(searchTerm) ||
        cot.cliente.toLowerCase().includes(searchTerm)
    );
    
    // Mostrar resultados...
});

async function seleccionarCotizacion(cotizacion) {
    try {
        const data = await window.ApiService.withLoading(
            window.ApiService.obtenerDatosCotizacion(cotizacion.id),
            'Cargando cotización...'
        );
        
        // Guardar en State Service
        window.PedidoState.setCotizacion({
            id: cotizacion.id,
            numero: cotizacion.numero,
            cliente: cotizacion.cliente,
            asesora: cotizacion.asesora
        });
        
        window.PedidoState.setLogo(data.logo);
        window.PedidoState.setEspecificaciones(data.especificaciones);
        window.PedidoState.setPrendas(data.prendas);
        window.PedidoState.setTallasDisponibles(data.tallas);
        
        renderizarPrendas();
    } catch (error) {
        window.ApiService.handleError(error, 'Cargar cotización');
    }
}
```

#### Ejemplo 2: Enviar Formulario

**ANTES:**
```javascript
formCrearPedido.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validar
    if (!cliente || cliente.trim() === '') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'El cliente es requerido'
        });
        return;
    }
    
    // Recopilar datos
    const pedidoData = {
        cotizacion_id: cotizacionId,
        cliente: cliente,
        asesora: asesora,
        prendas: prendasCargadas.map((prenda, index) => {
            // Procesar prenda...
            return {
                id: prenda.id,
                cantidades: obtenerCantidades(index),
                fotos_nuevas: prendasFotosNuevas[index] || []
            };
        })
    };
    
    // Enviar
    try {
        Swal.fire({
            title: 'Creando pedido...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        const response = await fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(pedidoData)
        });
        
        const result = await response.json();
        
        Swal.close();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Pedido creado',
                text: `Pedido #${result.numero_pedido} creado exitosamente`
            }).then(() => {
                window.location.href = '/asesores/pedidos-produccion';
            });
        }
    } catch (error) {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message
        });
    }
});
```

**DESPUÉS:**
```javascript
formCrearPedido.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Validar usando Validation Service
    const formData = {
        cliente: document.getElementById('cliente_editable').value,
        asesora: document.getElementById('asesora_editable').value,
        tipo: window.PedidoState.getTipo(),
        prendas: window.PedidoState.getPrendas(),
        logo: window.PedidoState.getLogo()
    };
    
    if (!window.ValidationService.validateAndShow(
        () => window.ValidationService.validatePedidoCompleto(formData),
        'Errores de Validación'
    )) {
        return;
    }
    
    // Recopilar datos del estado
    const pedidoData = {
        cotizacion_id: window.PedidoState.getCotizacionId(),
        cliente: formData.cliente,
        asesora: formData.asesora,
        prendas: window.PedidoState.getPrendas().map((prenda, index) => ({
            id: prenda.id,
            cantidades: obtenerCantidades(index),
            fotos_nuevas: window.PedidoState.getFotosPrenda(index)
        }))
    };
    
    // Enviar usando API Service
    try {
        const result = await window.ApiService.withLoading(
            window.ApiService.crearPedidoDesdeCotizacion(
                window.PedidoState.getCotizacionId(),
                pedidoData
            ),
            'Creando pedido...'
        );
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Pedido creado',
                text: `Pedido #${result.numero_pedido} creado exitosamente`
            }).then(() => {
                window.location.href = '/asesores/pedidos-produccion';
            });
        }
    } catch (error) {
        window.ApiService.handleError(error, 'Crear pedido');
    }
});
```

---

## 🎯 Beneficios de la Migración

### Antes:
```javascript
// Variables globales dispersas
let tallasDisponiblesCotizacion = [];
let currentLogoCotizacion = null;
window.prendasCargadas = [];

// Fetch manual con manejo de errores repetido
const response = await fetch(url);
if (!response.ok) throw new Error('...');
const data = await response.json();

// Validaciones dispersas
if (!cliente) {
    Swal.fire({ icon: 'error', ... });
    return;
}
```

### Después:
```javascript
// Estado centralizado
window.PedidoState.setTallasDisponibles(tallas);
window.PedidoState.setLogo(logo);
window.PedidoState.setPrendas(prendas);

// API con manejo de errores centralizado
const data = await window.ApiService.obtenerDatosCotizacion(id);

// Validaciones centralizadas
window.ValidationService.validateAndShow(
    () => window.ValidationService.validatePedidoCompleto(data)
);
```

---

## 📝 Checklist de Migración

### Por cada función que uses fetch:
- [ ] Reemplazar con `window.ApiService.request()` o método específico
- [ ] Eliminar manejo de errores manual
- [ ] Usar `withLoading()` para mostrar loading

### Por cada variable global de estado:
- [ ] Mover a `window.PedidoState`
- [ ] Usar getters/setters apropiados
- [ ] Eliminar variable global original

### Por cada validación:
- [ ] Mover a `window.ValidationService`
- [ ] Usar `validateAndShow()` para mostrar errores
- [ ] Eliminar código de validación inline

### Por cada manejo de imágenes:
- [ ] Usar `window.ImageService` (ya migrado)
- [ ] Guardar rutas en `window.PedidoState.fotosNuevas`

---

## 🚀 Próximos Pasos

1. ✅ Servicios core creados
2. ⬜ Actualizar vista blade para cargar servicios
3. ⬜ Migrar función por función en crear-pedido-editable.js
4. ⬜ Probar cada migración
5. ⬜ Crear componentes (Fase 2)

---

**Última actualización:** 12 de enero de 2026  
**Estado:** ✅ Fase 1 completada - Servicios listos para usar
