# 📚 Módulos SOLID - Cotización de Prendas

## Índice de Módulos

###  ValidationModule
**Archivo:** `ValidationModule.js`  
**Responsabilidad:** Validación de datos  
**Patrón:** Strategy

#### Métodos
```javascript
addRule(field, validator)                    // Agregar regla de validación
validarCampo(field, value)                   // Validar un campo
validarMultiples(fields)                     // Validar varios campos
validarFormularioCompleto()                  // Validar todo
```

#### Ejemplo
```javascript
// Validar cliente
const result = validationModule.validarCampo('cliente', 'Juan Pérez');
if (!result.valid) {
    console.error(result.message);
}

// Agregar validación personalizada
validationModule.addRule('telefono', (value) => {
    return {
        valid: /^\d{10}$/.test(value),
        message: 'Teléfono debe tener 10 dígitos'
    };
});
```

---

###  TallasModule
**Archivo:** `TallasModule.js`  
**Responsabilidad:** Gestión de tallas  
**Patrón:** Factory

#### Métodos
```javascript
actualizarSelectTallas(selectElement)        // Actualiza selector según tipo
crearBotonesTallas(formCol, tallas)         // Crea botones de tallas
agregarTallasRango(btn)                      // Agrega rango de tallas
agregarTallasSeleccionadas(btn)             // Agrega tallas de botones
obtenerTallasSeleccionadas(productoCard)    // Obtiene tallas del producto
validarTallasSeleccionadas(productoCard)    // Valida que haya tallas
```

#### Tipos de Tallas
```javascript
// Letra
['XS', 'S', 'M', 'L', 'XL', 'XXL']

// Número Dama
['32', '34', '36', '38', '40', '42', '44']

// Número Caballero
['28', '30', '32', '34', '36', '38', '40', '42']
```

#### Ejemplo
```javascript
// Usuario selecciona "Letra"
tallasModule.actualizarSelectTallas(selectElement);
// → Muestra botones: XS, S, M, L, XL, XXL

// Usuario selecciona tallas y hace click en "Agregar"
tallasModule.agregarTallasSeleccionadas(btn);
// → Guarda: ["M", "L", "XL"]
```

---

###  EspecificacionesModule
**Archivo:** `EspecificacionesModule.js`  
**Responsabilidad:** Especificaciones de cotización  
**Patrón:** Observer

#### Métodos
```javascript
init()                                       // Inicializa listeners
abrirModal()                                 // Abre modal
cerrarModal()                                // Cierra modal
guardarEspecificaciones()                    // Guarda selecciones
extraerEspecificaciones()                    // Extrae del modal
agregarFila(categoria)                       // Agrega fila personalizada
getEspecificaciones()                        // Obtiene actuales
validar()                                    // Valida que haya selecciones
limpiar()                                    // Limpia todo
```

#### Categorías
```javascript
{
    'tbody_disponibilidad': 'disponibilidad',
    'tbody_pago': 'forma_pago',
    'tbody_regimen': 'regimen',
    'tbody_vendido': 'se_ha_vendido',
    'tbody_ultima_venta': 'ultima_venta',
    'tbody_flete': 'flete'
}
```

#### Ejemplo
```javascript
// Abrir modal
especificacionesModule.abrirModal();

// Usuario selecciona especificaciones...

// Guardar
especificacionesModule.guardarEspecificaciones();
// → Guarda en window.especificacionesSeleccionadas

// Obtener
const specs = especificacionesModule.getEspecificaciones();
// → {
//     disponibilidad: ['Bodega'],
//     forma_pago: ['Contado'],
//     flete: ['Incluido']
//   }
```

---

### 4️⃣ ProductoModule
**Archivo:** `ProductoModule.js`  
**Responsabilidad:** CRUD de productos  
**Patrón:** Factory

#### Métodos
```javascript
init()                                       // Inicializa
agregarProducto()                            // Agrega nueva prenda
eliminarProducto(card)                       // Elimina prenda
toggleProductoBody(card)                     // Expande/contrae
handlePrendaChange(card)                     // Maneja cambio de prenda
getTodosProductos()                          // Obtiene todos
getNumeroProductos()                         // Cuenta productos
validarProductos()                           // Valida que haya productos
```

#### Datos por Producto
```javascript
{
    productoId: 'producto-1702000000-1',
    nombre_producto: 'JEAN SKINNY',
    descripcion: 'Jean azul oscuro',
    tallas: ['M', 'L', 'XL'],
    fotos: [File, File],
    telas: [File],
    variantes: { /* datos específicos */ }
}
```

#### Ejemplo
```javascript
// Agregar producto
const id = productoModule.agregarProducto();
// → 'producto-1702000000-1'

// Obtener todos
const productos = productoModule.getTodosProductos();
// → [elemento DOM, elemento DOM, ...]

// Validar
const validation = productoModule.validarProductos();
// → { valid: true, message: '' }

// Eliminar
productoModule.eliminarProducto(card);
```

---

### 5️⃣ FormModule
**Archivo:** `FormModule.js`  
**Responsabilidad:** Gestión del formulario  
**Patrón:** Facade

#### Métodos
```javascript
init()                                       // Inicializa módulo
syncHeaderWithForm()                         // Sincroniza header
validate()                                   // Valida completo
buildFormData(action)                        // Construye FormData
addProductToFormData(formData, card, idx)   // Agrega producto
submitForm(formData)                         // Envía al servidor
handleSave(action)                           // Maneja guardado
handleSuccess(data)                          // Maneja éxito
handleError(data)                            // Maneja error
showValidationErrors(errors)                 // Muestra errores
getState()                                   // Obtiene estado
updateButtonStates()                         // Actualiza botones
```

#### Estado del Formulario
```javascript
{
    cliente: 'Nombre Cliente',
    asesora: 'Nombre Asesor',
    fecha: '2024-01-15',
    tipo_cotizacion: 'M',
    productos: [],
    especificaciones: {}
}
```

#### Ejemplo
```javascript
// Validar
const validation = formModule.validate();
if (!validation.valid) {
    formModule.showValidationErrors(validation.errors);
}

// Obtener estado
const state = formModule.getState();
// → {
//     cliente: 'Juan',
//     asesora: 'María',
//     ...
//   }
```

---

### 6️⃣ CotizacionPrendaApp
**Archivo:** `CotizacionPrendaApp.js`  
**Responsabilidad:** Orquestación  
**Patrón:** Mediator/Facade

#### Métodos
```javascript
init()                                       // Inicializa todos los módulos
setupGlobalListeners()                       // Configura listeners globales
toggleMenuFlotante()                         // Toggle menú flotante
onAgregarProducto()                          // Maneja adición de producto
onEliminarProducto(card)                     // Maneja eliminación
getState()                                   // Obtiene estado completo
validate()                                   // Valida todo
guardar(action)                              // Guarda cotización
submitForm(formData)                         // Envía FormData
showValidationErrors(errors)                 // Muestra errores
reset()                                      // Reinicia
```

#### Funciones Globales Exportadas
```javascript
window.agregarProductoPrenda               // Agrega producto
window.eliminarProductoPrenda              // Elimina producto
window.guardarCotizacionPrenda             // Guarda cotización
```

#### Ejemplo
```javascript
// Inicializar (automático en DOMContentLoaded)
app.init();

// Agregar producto (desde HTML: onclick)
window.agregarProductoPrenda();

// Guardar
window.guardarCotizacionPrenda();

// Obtener estado
const state = app.getState();
// → {
//     form: {...},
//     productos: [elemento, elemento, ...],
//     especificaciones: {...}
//   }
```

---

##  Relaciones entre Módulos

```
┌─────────────────────────────────────────────────────┐
│           CotizacionPrendaApp (Mediator)            │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌──────────────────┐  ┌─────────────────────┐     │
│  │ ValidationModule │  │  TallasModule       │     │
│  │                  │  │                     │     │
│  │ • validar campo  │  │ • actualizar tipo   │     │
│  │ • validar todo   │  │ • agregar rango     │     │
│  │ • agregar reglas │  │ • crear botones     │     │
│  └──────────────────┘  └─────────────────────┘     │
│                                                      │
│  ┌──────────────────────┐  ┌──────────────────┐   │
│  │ EspecificacionesModule│  │ ProductoModule   │   │
│  │                      │  │                  │   │
│  │ • abrir/cerrar modal │  │ • agregar        │   │
│  │ • guardar especif.   │  │ • eliminar       │   │
│  │ • extraer datos      │  │ • validar        │   │
│  └──────────────────────┘  └──────────────────┘   │
│                                                      │
│  ┌──────────────────────────────────────────────┐  │
│  │          FormModule                          │  │
│  │                                              │  │
│  │ • sincronizar header                         │  │
│  │ • validar                                    │  │
│  │ • construir FormData                         │  │
│  │ • enviar servidor                            │  │
│  │ • manejar respuesta                          │  │
│  └──────────────────────────────────────────────┘  │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

##  Patrones de Testing

### Test del Módulo de Validación
```javascript
describe('ValidationModule', () => {
    test('debe validar cliente válido', () => {
        const result = validationModule.validarCampo('cliente', 'Juan Pérez');
        expect(result.valid).toBe(true);
    });

    test('debe rechazar cliente vacío', () => {
        const result = validationModule.validarCampo('cliente', '');
        expect(result.valid).toBe(false);
    });

    test('debe validar tipo de cotización', () => {
        const result = validationModule.validarCampo('tipo_cotizacion', 'M');
        expect(result.valid).toBe(true);
    });
});
```

### Test del Módulo de Productos
```javascript
describe('ProductoModule', () => {
    test('debe agregar un producto', () => {
        const id = productoModule.agregarProducto();
        expect(id).toBeDefined();
        expect(id).toMatch(/^producto-\d+-\d+$/);
    });

    test('debe contar productos correctamente', () => {
        productoModule.agregarProducto();
        productoModule.agregarProducto();
        expect(productoModule.getNumeroProductos()).toBe(2);
    });
});
```

---

##  Checklist de Integración

- [ ] Crear estructura de carpetas
- [ ] Crear todos los módulos
- [ ] Crear archivo index.js
- [ ] Actualizar create.blade.php
- [ ] Cargar módulos en orden correcto
- [ ] Probar funcionalidad básica
- [ ] Crear tests unitarios
- [ ] Documentar API
- [ ] Refactorizar backend (Laravel)
- [ ] Implementar error handling
- [ ] Performance testing
- [ ] Deploy a producción

---

## Próximas Mejoras

1. **TypeScript:** Migrar a TypeScript para mejor tipado
2. **Service Layer:** Crear capa de servicios en el backend
3. **Event Bus:** Implementar event bus para comunicación
4. **State Management:** Usar Vuex o similar para estado global
5. **API REST:** Crear API REST limpia
6. **Testing:** Suite completa de tests
7. **CI/CD:** Integración continua y deployment

---

 **Documentación Completada**
