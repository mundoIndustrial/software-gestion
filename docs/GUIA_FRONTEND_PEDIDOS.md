# 🎯 GUÍA: FRONTEND PROFESIONAL DE PEDIDOS

**Arquitectura moderna, escalable y mantenible para capturar pedidos de producción textil**

---

## 📋 TABLA DE CONTENIDOS

1. [Arquitectura](#arquitectura)
2. [Componentes](#componentes)
3. [Uso básico](#uso-básico)
4. [Ejemplos avanzados](#ejemplos-avanzados)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)
7. [Extensión](#extensión)

---

## 🏗️ ARQUITECTURA

### Capas

```
┌─────────────────────────────────────────────────┐
│         UI Layer (usuario interactúa)           │
│    crear-pedido-completo.blade.php              │
└─────────────┬───────────────────────────────────┘
              │
┌─────────────▼───────────────────────────────────┐
│      Handlers Layer (eventos y lógica)          │
│    form-handlers.js (PedidoFormHandlers)        │
└─────────────┬───────────────────────────────────┘
              │
      ┌───────┴────────┐
      │                │
┌─────▼──────┐   ┌──────▼──────┐
│  Manager   │   │ Components  │
│ (estado)   │   │ (rendering) │
└─────┬──────┘   └──────┬──────┘
      │                │
 ┌────▼──────┐    ┌────▼──────┐
 │FormManager│    │    UI     │
 │.js        │    │Components │
 │           │    │.js        │
 └────┬──────┘    └───────────┘
      │
      └──────────┬──────────────┐
                 │              │
          ┌──────▼────┐   ┌────▼──────┐
          │Validation │   │ Storage   │
          │(Validator)│   │(localStorage)
          └───────────┘   └───────────┘
      
      
┌──────────────────────────────────────┐
│     API Layer (backend)              │
│  POST /api/pedidos/guardar-desde-json│
└──────────────────────────────────────┘
```

### Flujo de datos

```
Usuario escribe → FormHandler captura evento → FormManager actualiza estado
                  ↓
            Listeners notificados → UIComponents renderizan
                  ↓
            localStorage auto-guarda → Usuario ve cambios
                  ↓
         Usuario hace click "Enviar" → Validator valida
                  ↓
           Si válido → FormData + fetch → Backend API
                  ↓
           Backend responde → Toast de éxito/error
```

---

## 🧩 COMPONENTES

### 1. **PedidoFormManager.js** (300+ líneas)

**Responsabilidad:** Gestionar TODO el estado de la aplicación

**Métodos públicos:**

```javascript
// Inicialización
const manager = new PedidoFormManager(config);

// Pedido
manager.setPedidoId(1);
manager.getPedidoId();

// Prendas
manager.addPrenda({ nombre_prenda: 'Polo' });
manager.editPrenda(prendaId, { descripcion: 'nueva' });
manager.deletePrenda(prendaId);
manager.getPrenda(prendaId);
manager.getPrendas();

// Variantes
manager.addVariante(prendaId, { talla: 'M', cantidad: 50 });
manager.editVariante(prendaId, varianteId, updates);
manager.deleteVariante(prendaId, varianteId);
manager.getVariantes(prendaId);

// Fotos
manager.addFotoPrenda(prendaId, { file, nombre });
manager.addFotoTela(prendaId, { file, color });
manager.deleteFoto(prendaId, fotoId, 'prenda');
manager.getFotos(prendaId);
manager.getFotosTela(prendaId);

// Procesos
manager.addProceso(prendaId, { tipo_proceso_id: 1 });
manager.editProceso(prendaId, procesoId, updates);
manager.deleteProceso(prendaId, procesoId);
manager.getProcesos(prendaId);

// Utilities
manager.getState();           // Estado completo
manager.getSummary();         // Resumen de pedido
manager.clear();              // Limpiar todo
manager.saveToStorage();      // Guardar en localStorage

// Listeners
manager.on('prenda:added', (data) => {...});
manager.off('prenda:added', callback);
```

**Características:**

- ✅ Auto-guardado en localStorage cada 30s
- ✅ Persistencia entre sesiones
- ✅ Event emitters para cambios
- ✅ Validación de estructura en métodos
- ✅ IDs únicos para cada elemento

### 2. **PedidoValidator.js** (150+ líneas)

**Responsabilidad:** Validar reglas de negocio

**Métodos públicos:**

```javascript
// Validación completa
const result = PedidoValidator.validar(state);
// { valid: true/false, errors: {...}, mensaje: '...' }

// Validar un campo específico (en tiempo real)
const fieldResult = PedidoValidator.validarCampo('nombre_prenda', value, context);
// { valid: true/false, errors: [...] }

// Obtener reporte detallado
const reporte = PedidoValidator.obtenerReporte(state);
// { valid, mensaje, totalErrores, errores, resumen }

// Verificar si está completo
const completo = PedidoValidator.estaCompleto(state);
```

**Reglas de validación:**

- `pedido_produccion_id` obligatorio
- ≥1 prenda
- ≥1 variante por prenda
- `talla` obligatoria
- `cantidad` > 0 y ≤ 10000
- Si `tiene_bolsillos === true` → `bolsillos_obs` obligatorio
- Si `tipo_manga_id` existe → `manga_obs` recomendado
- Si `tipo_broche_boton_id` existe → `broche_boton_obs` recomendado
- `tipo_proceso_id` obligatorio si hay procesos
- ≥1 ubicación si hay procesos

### 3. **ui-components.js** (200+ líneas)

**Responsabilidad:** Renderizar HTML

**Métodos públicos:**

```javascript
// Componentes principales
UIComponents.renderPrendaCard(prenda, actions);
UIComponents.renderVarianteRow(variante, prendaId);
UIComponents.renderProcesoCard(proceso, prendaId);
UIComponents.renderFotoThumb(foto, prendaId, tipo);

// Modales
UIComponents.renderModal(title, content, actions);
UIComponents.renderToast(type, message, duration);

// Resúmenes
UIComponents.renderResumen(summary);
UIComponents.renderValidationErrors(errors);

// Utilidades
UIComponents.escape(text);           // Escapar HTML
UIComponents.capitalize(text);       // Capitalizar
UIComponents.formatFileSize(bytes);  // Formato archivo
```

**Características:**

- ✅ Componentes sin estado (funciones puras)
- ✅ HTML seguro (escape de caracteres especiales)
- ✅ Responsive design
- ✅ Animaciones suaves
- ✅ Bootstrap 4 compatible

### 4. **form-handlers.js** (400+ líneas)

**Responsabilidad:** Orquestar eventos y coordinar entre componentes

**Métodos públicos:**

```javascript
const handlers = new PedidoFormHandlers(formManager, validator, uiComponents);
handlers.init('container-id');     // Inicializar
handlers.render();                 // Re-renderizar
handlers.destroy();                // Limpiar
```

**Eventos capturados:**

- ✅ Clicks en botones (add, edit, delete)
- ✅ Cambios en inputs
- ✅ Cargas de archivos
- ✅ Envíos de formularios
- ✅ Cambios en el manager (listeners)

---

## 🚀 USO BÁSICO

### Instalación

1. **Copiar archivos JavaScript:**
   ```bash
   cp public/js/pedidos-produccion/*.js /tu/carpeta/
   ```

2. **Incluir en Blade:**
   ```blade
   <script src="{{ asset('js/pedidos-produccion/PedidoFormManager.js') }}"></script>
   <script src="{{ asset('js/pedidos-produccion/PedidoValidator.js') }}"></script>
   <script src="{{ asset('js/pedidos-produccion/ui-components.js') }}"></script>
   <script src="{{ asset('js/pedidos-produccion/form-handlers.js') }}"></script>
   ```

3. **Inicializar:**
   ```javascript
   const formManager = new PedidoFormManager();
   const handlers = new PedidoFormHandlers(formManager, PedidoValidator, UIComponents);
   handlers.init('prendas-container');
   ```

### Ejemplo 1: Agregar una prenda simple

```javascript
// 1. Crear manager
const manager = new PedidoFormManager();

// 2. Establecer pedido
manager.setPedidoId(1);

// 3. Agregar prenda
const prenda = manager.addPrenda({
    nombre_prenda: 'Polo clásico',
    descripcion: 'Polo de algodón',
    genero: 'dama',
    de_bodega: false
});

// 4. Agregar variante
manager.addVariante(prenda._id, {
    talla: 'M',
    cantidad: 50,
    color_id: 5,
    tela_id: 3
});

// 5. Obtener estado
const state = manager.getState();
console.log(state);
```

### Ejemplo 2: Validar y enviar

```javascript
// 1. Validar
const resultado = PedidoValidator.validar(state);
if (!resultado.valid) {
    console.error('Errores:', resultado.errors);
    return;
}

// 2. Enviar
const formData = new FormData();
formData.append('pedido_produccion_id', state.pedido_produccion_id);
formData.append('prendas', JSON.stringify(state.prendas));

const response = await fetch('/api/pedidos/guardar-desde-json', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: formData
});

const result = await response.json();
console.log('✅ Pedido guardado:', result);
```

---

## 🎓 EJEMPLOS AVANZADOS

### Ejemplo 3: Listeners en tiempo real

```javascript
const manager = new PedidoFormManager();

// Escuchar cuando se agrega prenda
manager.on('prenda:added', (data) => {
    console.log('Nueva prenda:', data.prenda.nombre_prenda);
    // Actualizar UI, hacer request, etc.
});

// Escuchar cuando se agrega variante
manager.on('variante:added', (data) => {
    console.log(`Variante agregada a prenda ${data.prendaId}`);
    updateStats();
});

// Múltiples listeners
manager.on('estado:actualizado', () => {
    const summary = manager.getSummary();
    console.log(`Pedido: ${summary.prendas} prendas, ${summary.items} items`);
});
```

### Ejemplo 4: Validación en tiempo real

```javascript
const inputNombre = document.getElementById('nombre-prenda');

inputNombre.addEventListener('input', (e) => {
    const validation = PedidoValidator.validarCampo(
        'nombre_prenda',
        e.target.value
    );

    if (!validation.valid) {
        e.target.classList.add('is-invalid');
        console.log(validation.errors);
    } else {
        e.target.classList.remove('is-invalid');
    }
});
```

### Ejemplo 5: Importar desde JSON existente

```javascript
const manager = new PedidoFormManager();

// Datos externos
const datosExternos = {
    pedido_produccion_id: 1,
    prendas: [
        {
            nombre_prenda: 'Polo',
            descripcion: 'De algodón',
            variantes: [
                { talla: 'M', cantidad: 50 }
            ]
        }
    ]
};

// Importar manualmente
manager.setPedidoId(datosExternos.pedido_produccion_id);

datosExternos.prendas.forEach(prendaData => {
    const prenda = manager.addPrenda(prendaData);
    
    prendaData.variantes.forEach(varianteData => {
        manager.addVariante(prenda._id, varianteData);
    });
});

// ¡Listo!
console.log(manager.getState());
```

### Ejemplo 6: Exportar con transformación

```javascript
const manager = new PedidoFormManager();
// ... agregar prendas ...

// Obtener estado
const state = manager.getState();

// Transformar para envío
const payload = {
    ...state,
    prendas: state.prendas.map(prenda => ({
        ...prenda,
        // Remover campos internos
        _id: undefined,
        fotos_prenda: prenda.fotos_prenda.map(f => ({
            nombre: f.nombre,
            observaciones: f.observaciones,
            // No enviar el archivo aquí (se envía en FormData)
        }))
    }))
};

console.log(JSON.stringify(payload, null, 2));
```

---

## 🧪 TESTING

### Test 1: Verificar estado base

```javascript
function testEstadoBase() {
    const manager = new PedidoFormManager();
    
    assert(manager.getState().pedido_produccion_id === null, 'Pedido debe ser null');
    assert(manager.getState().prendas.length === 0, 'Prendas debe estar vacío');
    assert(manager.getSummary().completo === false, 'No debe estar completo');
    
    console.log('✅ Test 1 pasado');
}

// Ejecutar: testEstadoBase();
```

### Test 2: CRUD de prendas

```javascript
function testPrendaCRUD() {
    const manager = new PedidoFormManager();
    manager.setPedidoId(1);
    
    // Create
    const prenda = manager.addPrenda({ nombre_prenda: 'Test' });
    assert(prenda._id, 'Debe tener ID');
    
    // Read
    const found = manager.getPrenda(prenda._id);
    assert(found.nombre_prenda === 'Test', 'Debe encontrar prenda');
    
    // Update
    manager.editPrenda(prenda._id, { descripcion: 'Actualizado' });
    assert(found.descripcion === 'Actualizado', 'Debe actualizar');
    
    // Delete
    manager.deletePrenda(prenda._id);
    assert(!manager.getPrenda(prenda._id), 'Debe estar eliminada');
    
    console.log('✅ Test 2 pasado');
}
```

### Test 3: Validación exhaustiva

```javascript
function testValidacion() {
    const manager = new PedidoFormManager();
    
    // Sin pedido
    let result = PedidoValidator.validar(manager.getState());
    assert(!result.valid, 'Debe fallar sin pedido');
    
    // Con pedido pero sin prendas
    manager.setPedidoId(1);
    result = PedidoValidator.validar(manager.getState());
    assert(!result.valid, 'Debe fallar sin prendas');
    
    // Con prenda pero sin variantes
    const prenda = manager.addPrenda({ nombre_prenda: 'Test' });
    result = PedidoValidator.validar(manager.getState());
    assert(!result.valid, 'Debe fallar sin variantes');
    
    // Con variante válida
    manager.addVariante(prenda._id, { talla: 'M', cantidad: 10 });
    result = PedidoValidator.validar(manager.getState());
    assert(result.valid, 'Debe ser válido');
    
    console.log('✅ Test 3 pasado');
}
```

### Test 4: localStorage

```javascript
function testPersistencia() {
    const key1 = 'pedidoFormState';
    localStorage.clear();
    
    const m1 = new PedidoFormManager();
    m1.setPedidoId(1);
    m1.addPrenda({ nombre_prenda: 'Polo' });
    m1.saveToStorage();
    
    const saved = JSON.parse(localStorage.getItem(key1));
    assert(saved.pedido_produccion_id === 1, 'Debe guardar pedido ID');
    assert(saved.prendas.length === 1, 'Debe guardar prendas');
    
    // Simular nuevo manager
    const m2 = new PedidoFormManager();
    assert(m2.getState().pedido_produccion_id === 1, 'Debe cargar pedido ID');
    assert(m2.getPrendas().length === 1, 'Debe cargar prendas');
    
    console.log('✅ Test 4 pasado');
}
```

---

## 🔧 TROUBLESHOOTING

### Problema: "FormManager no está definido"

**Causa:** No se incluyeron los scripts correctamente

**Solución:**
```blade
<!-- En orden correcto -->
<script src="{{ asset('js/pedidos-produccion/PedidoFormManager.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/PedidoValidator.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/ui-components.js') }}"></script>
<script src="{{ asset('js/pedidos-produccion/form-handlers.js') }}"></script>
```

### Problema: localStorage lleno ("QuotaExceededError")

**Causa:** Demasiadas fotos o datos grandes

**Solución:**
```javascript
// Incrementar límite o limpiar fotos no esenciales
manager.config.maxFileSizeMB = 5;
manager.config.maxFotosPerPrenda = 5;

// O remover fotos antiguas
const prendas = manager.getPrendas();
prendas.forEach(p => {
    if (p.fotos_prenda.length > 10) {
        // Limpiar
    }
});
```

### Problema: Cambios no se muestran en UI

**Causa:** No se llamó a `handlers.render()`

**Solución:**
```javascript
// Actualizar manualmente
manager.addPrenda({ nombre_prenda: 'Polo' });
handlers.render();  // ← Requerido si no estás usando listeners
```

### Problema: Validación falla pero los datos parecen válidos

**Solución:** Verificar en consola
```javascript
const result = PedidoValidator.obtenerReporte(manager.getState());
console.log(result);  // Ver errores específicos
```

### Problema: Fotos no se cargan

**Causa:** CORS o tipo de archivo incorrecto

**Solución:**
```javascript
// Verificar tipo y tamaño
const file = document.getElementById('foto-input').files[0];
console.log('Tipo:', file.type);
console.log('Tamaño:', file.size / (1024*1024), 'MB');

// Validar manualmente
if (!file.type.startsWith('image/')) {
    console.error('No es imagen');
}
if (file.size > 10 * 1024 * 1024) {
    console.error('Muy grande');
}
```

---

## 🔌 EXTENSIÓN

### Agregar nuevo campo a variante

**Paso 1:** Actualizar template en FormManager
```javascript
// En PedidoFormManager.js → createPrendaTemplate()
const variante = {
    // ... campos existentes ...
    nuevo_campo: data.nuevo_campo || 'default'
};
```

**Paso 2:** Agregar validación
```javascript
// En PedidoValidator.js → validarVariante()
if (!variante.nuevo_campo) {
    if (!errors[prefix]) errors[prefix] = [];
    errors[prefix].push('nuevo_campo es obligatorio');
}
```

**Paso 3:** Actualizar formulario en handlers
```javascript
// En form-handlers.js → showAddVarianteModal()
<div class="form-group">
    <label for="nuevo_campo">Nuevo campo</label>
    <input type="text" class="form-control" id="nuevo_campo" name="nuevo_campo">
</div>
```

**Paso 4:** Procesar en saveVariante
```javascript
const data = {
    // ... datos existentes ...
    nuevo_campo: formData.nuevo_campo
};
```

### Agregar nuevo tipo de proceso

```javascript
// En form-handlers.js → showAddProcesoModal()
<option value="6">6 - Nuevo proceso</option>

// En PedidoValidator.js → validarCampo('tipo_proceso_id')
// Ya soporta cualquier ID numérico, no requiere cambio
```

### Personalizar estilos

```css
/* En crear-pedido-completo.blade.php */
.card.border-left-primary {
    border-left: 8px solid #your-color;  /* Más ancho */
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

---

## 📊 ESTRUCTURA DE DATOS FINAL

```javascript
{
    pedido_produccion_id: 1,
    prendas: [
        {
            _id: '_1234567890_abc123def',
            nombre_prenda: 'Polo clásico',
            descripcion: 'Polo de algodón',
            genero: 'dama',
            de_bodega: true,
            
            fotos_prenda: [
                {
                    _id: '_...',
                    file: File {},
                    nombre: 'foto1.jpg',
                    tipo_archivo: 'image/jpeg',
                    tamanio: 2048000,
                    fecha_carga: '2026-01-16T10:30:00.000Z',
                    observaciones: 'Vista frontal'
                }
            ],
            
            fotos_tela: [
                {
                    _id: '_...',
                    file: File {},
                    nombre: 'tela_azul.jpg',
                    color: 'Azul marino',
                    observaciones: 'Tela base del proyecto',
                    tipo_archivo: 'image/jpeg',
                    tamanio: 1024000,
                    fecha_carga: '2026-01-16T10:30:00.000Z'
                }
            ],
            
            variantes: [
                {
                    _id: '_...',
                    talla: 'M',
                    cantidad: 50,
                    color_id: 5,
                    tela_id: 3,
                    tipo_manga_id: 1,
                    manga_obs: 'Manga corta',
                    tipo_broche_boton_id: 2,
                    broche_boton_obs: 'Botón blanco',
                    tiene_bolsillos: true,
                    bolsillos_obs: 'Dos bolsillos en el pecho'
                }
            ],
            
            procesos: [
                {
                    _id: '_...',
                    tipo_proceso_id: 1,  // Bordado
                    ubicaciones: ['pecho', 'espalda'],
                    observaciones: 'Bordado del logo',
                    imagenes: [
                        {
                            _id: '_...',
                            file: File {},
                            nombre: 'logo.png',
                            observaciones: 'Logo a 10cm de pecho',
                            tipo_archivo: 'image/png',
                            tamanio: 512000,
                            fecha_carga: '2026-01-16T10:30:00.000Z'
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## 🎯 CHECKLIST DE INTEGRACIÓN

- [ ] Archivos JS copiados a `public/js/pedidos-produccion/`
- [ ] Vista Blade creada en `resources/views/asesores/pedidos/`
- [ ] Ruta registrada en `routes/web.php` o `routes/api.php`
- [ ] Bootstrap CSS/JS incluido
- [ ] Backend API lista en `/api/pedidos/guardar-desde-json`
- [ ] Meta CSRF token en layout Blade
- [ ] localStorage habilitado en navegador
- [ ] Testeado en navegador (dev console)
- [ ] Validación completa en frontend
- [ ] Feedback visual (toasts) funcionando

---

## 📞 SOPORTE

**Documentación relacionada:**
- [Backend API](../docs/GUIA_FLUJO_JSON_BD.md)
- [Instrucciones de migración](../docs/INSTRUCCIONES_MIGRACION.md)
- [Checklist de implementación](../docs/CHECKLIST_IMPLEMENTACION.md)

**Debugging:**
```javascript
// En consola del navegador
console.log(window.formManager.getState());      // Ver estado
console.log(window.formManager.getSummary());    // Ver resumen
console.log(PedidoValidator.obtenerReporte(...));// Ver errores
```

---

**¡Listo para producción! ✅**
