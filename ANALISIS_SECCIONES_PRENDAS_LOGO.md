# 📊 ANÁLISIS: Secciones de Prendas en Tab Logo para Cotizaciones Combinadas

## 🎯 Resumen Ejecutivo

Cuando se crea un **pedido a partir de una cotización COMBINADA (PL)**, el sistema divide la información en 2 tabs:
- **Tab PRENDAS**: Mostrará las prendas regulares con su estructura actual (nombre, descripción, tallas, variaciones, telas, fotos)
- **Tab LOGO**: Mostrará la información de logo, que INCLUYE las "Secciones de Prendas" (ubicaciones donde se aplicará el bordado)

---

## 📁 ESTRUCTURA DE DATOS GUARDADA EN BD

### 1️⃣ **Tabla: `logo_cotizaciones`**

```sql
CREATE TABLE logo_cotizaciones (
    id INT PRIMARY KEY,
    cotizacion_id INT,
    descripcion TEXT,
    imagenes JSON,           -- ← Array de URLs
    tecnicas JSON,          -- ← Array ["BORDADO", "DTF", ...]
    observaciones_tecnicas TEXT,
    secciones JSON,         -- ⭐ ESTO ES LO IMPORTANTE
    observaciones_generales JSON,
    tipo_venta VARCHAR,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 2️⃣ **Estructura de SECCIONES (JSON dentro de logo_cotizaciones)**

```json
{
  "secciones": [
    {
      "ubicacion": "CAMISA",
      "opciones": ["PECHO", "ESPALDA", "MANGA IZQUIERDA"],
      "tallas": [
        {
          "talla": "S",
          "cantidad": 5
        },
        {
          "talla": "M",
          "cantidad": 10
        }
      ],
      "observaciones": "Bordado de alta calidad con hilo plateado"
    },
    {
      "ubicacion": "GORRAS",
      "opciones": ["FRENTE"],
      "tallas": [
        {
          "talla": "ÚNICA",
          "cantidad": 20
        }
      ],
      "observaciones": "Estampado directamente en la tela"
    }
  ]
}
```

---

## 🔄 CÓMO SE TRAE LA INFORMACIÓN DESDE BD ACTUALMENTE

### En `PedidosProduccionController.php` - Línea ~1395

```php
// Respuesta AJAX en obtener-datos-cotizacion/{cotizacionId}
'logo' => $cotizacion->logoCotizacion ? [
    'id' => $cotizacion->logoCotizacion->id,
    'descripcion' => $cotizacion->logoCotizacion->descripcion,
    'tipo_venta' => $cotizacion->logoCotizacion->tipo_venta,
    'imagenes' => $cotizacion->logoCotizacion->imagenes ?? [],
    'tecnicas' => (is_array(...) ? ... : json_decode(...)) ?? [],
    'observaciones_tecnicas' => $cotizacion->logoCotizacion->observaciones_tecnicas,
    'ubicaciones' => json_decode($cotizacion->logoCotizacion->ubicaciones, true) ?? [],
    'observaciones_generales' => json_decode($cotizacion->logoCotizacion->observaciones_generales, true) ?? [],
    'fotos' => $cotizacion->logoCotizacion->fotos->map(...)->toArray(),
] : null
```

---

## 🎨 CÓMO SE CREA UNA COTIZACIÓN DE LOGO

### Flujo en `resources/views/cotizaciones/bordado/create.blade.php`

#### 1️⃣ **Usuario selecciona PRENDA (sección)**
```javascript
// Línea ~776
function agregarSeccion() {
    const ubicacion = document.getElementById('seccion_prenda').value.toUpperCase();
    // Opción: CAMISA, JEAN_SUDADERA, GORRAS
}
```

#### 2️⃣ **Se abre MODAL con opciones según prenda**
```javascript
// logoOpcionesPorUbicacion
{
    'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
    'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
    'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
}
```

#### 3️⃣ **En el MODAL, usuario selecciona:**
- ✅ Ubicaciones específicas (checkboxes)
- ✅ Tallas (con cantidades)
- ✅ Observaciones

#### 4️⃣ **Estructura guardada**
```javascript
seccionesSeleccionadas = [
    {
        ubicacion: "CAMISA",
        opciones: ["PECHO", "ESPALDA"],
        tallas: [
            { talla: "S", cantidad: 5 },
            { talla: "M", cantidad: 10 }
        ],
        observaciones: "Bordado de alta resolución"
    }
]
```

---

## 📤 CÓMO SE ENVÍA ACTUALMENTE AL GUARDAR COTIZACIÓN LOGO

### En el JavaScript de cotizaciones/bordado/create.blade.php

```javascript
// Cuando hace submit
const cotizacionData = {
    cliente_id: clienteId,
    tipo_venta: tipoVentaSelect.value,
    especificaciones: { ... },
    logo: {
        descripcion: descripcionLogo.value,
        tecnicas: logoTecnicasSeleccionadas,
        observaciones_tecnicas: obsTextarea.value,
        secciones: seccionesSeleccionadas,  // ⭐ Array con estructura completa
        fotos: logoFotosSeleccionadas,
        imagenes: logoImagenesSeleccionadas
    }
};

// Envía POST /asesores/cotizaciones/guardar-logo-cotizacion
fetch('/asesores/cotizaciones/guardar-logo-cotizacion', {
    method: 'POST',
    body: JSON.stringify(cotizacionData)
}).then(...)
```

---

## 🛠️ ESTRUCTURA EN EL FORMULARIO DE CREAR PEDIDO (ACTUAL)

### En `crear-desde-cotizacion-editable.blade.php`

Actualmente, cuando se renderiza el tab de LOGO para cotizaciones combinadas, se llama:

```javascript
// Línea ~265 en crear-pedido-editable.js
renderizarPrendasEditables(
    prendas,           // Array de prendas normales
    logoCotizacion,    // Objeto con logo data (SIN SECCIONES EDITABLES)
    especificaciones,
    esReflectivo,
    datosReflectivo,
    esLogo             // false para combinadas, true para solo logo
);
```

---

## ⚠️ PROBLEMA IDENTIFICADO

Actualmente en la función `renderizarCamposLogo()` (línea ~1138):

```javascript
function renderizarCamposLogo(logoCotizacion) {
    // Solo renderiza campos para LOGO SOLO (sin prendas)
    // NO trae la información de "secciones" de la cotización
    // El usuario tiene que volver a agregar las secciones
}
```

### Lo que FALTA:
1. ❌ No se trae `logoCotizacion.secciones` desde la BD
2. ❌ No se cargan las secciones preexistentes al renderizar
3. ❌ No son editables las secciones guardadas
4. ❌ No se pueden modificar tallas/cantidades dentro de secciones

---

## ✅ SOLUCIÓN PROPUESTA

### Para **Cotizaciones Combinadas (PL)** - Tab LOGO con Secciones Editables

#### 1️⃣ **Traer secciones desde BD**
```javascript
// En cargarPrendasDesdeCotizacion()
const data = {
    prendas: [...],     // Prendas normales
    logo: {
        id: 5,
        descripcion: "...",
        secciones: [      // ⭐ AGREGAR ESTO
            {
                ubicacion: "CAMISA",
                opciones: ["PECHO", "ESPALDA"],
                tallas: [{talla: "S", cantidad: 5}, ...],
                observaciones: "..."
            }
        ],
        // ... resto de datos
    }
}
```

#### 2️⃣ **Crear NUEVA función `renderizarSeccionesEditables()`**
```javascript
function renderizarSeccionesEditables(secciones) {
    // Similar a la estructura de TALLAS/VARIACIONES
    // Grid con:
    // - Prenda (ubicación)
    // - Ubicaciones seleccionadas (tags)
    // - Tallas con cantidades (tabla)
    // - Observaciones
    // - Botones: Editar, Eliminar
}
```

#### 3️⃣ **Actualizar `renderizarPrendasEditables()` para cotizaciones PL**
```javascript
// Para cotizaciones COMBINADAS, renderizar:
if (tienePrendas) {
    // Tab PRENDAS con prendas normales
}

if (tieneLogoPrendas) {
    // Tab LOGO con:
    // - Descripción
    // - Secciones de prendas (EDITABLE)
    // - Técnicas
    // - Observaciones
    // - Fotos
}
```

#### 4️⃣ **Estructura HTML del tab LOGO con secciones**
```html
<div id="tab-logo" class="tab-content">
    <!-- Descripción -->
    <textarea name="logo_descripcion">...</textarea>
    
    <!-- SECCIONES DE PRENDAS (Nuevo) -->
    <div class="secciones-logo-container">
        <h3>📋 Secciones de Prendas</h3>
        <button onclick="agregarSeccionLogo()">+ Agregar Sección</button>
        
        <!-- Cada sección renderizada similar a tallas -->
        <div class="seccion-logo-card" data-seccion-idx="0">
            <div class="grid">
                <div>Ubicación: CAMISA</div>
                <div>Ubicaciones: PECHO, ESPALDA</div>
                <div>Tallas y cantidades (tabla)</div>
                <div>Botones: Editar, Eliminar</div>
            </div>
        </div>
    </div>
    
    <!-- Técnicas -->
    <!-- Observaciones -->
    <!-- Fotos -->
</div>
```

---

## 📊 COMPARACIÓN: Estructura en Cotización vs Pedido

### COTIZACIÓN LOGO (crear)
```
Tab LOGO:
├─ Descripción (EDITABLE)
├─ Secciones de Prendas
│  ├─ CAMISA → [PECHO, ESPALDA] → Tallas: S(5), M(10)
│  └─ GORRAS → [FRENTE] → Tallas: ÚNICA(20)
├─ Técnicas: BORDADO, DTF
├─ Observaciones Técnicas
├─ Observaciones Generales
└─ Fotos
```

### PEDIDO PRODUCCIÓN (crear desde cotización combinada PL)
```
Tab PRENDAS:
├─ Prenda 1 (Nombre, Desc, Tallas, Variaciones, Telas, Fotos)
├─ Prenda 2
└─ ...

Tab LOGO: ⭐ DEBE INCLUIR AHORA
├─ Descripción (EDITABLE)
├─ Secciones de Prendas (EDITABLE) ✅ NUEVO
│  ├─ CAMISA → [PECHO, ESPALDA] → Tallas: S(5), M(10) (EDITABLE)
│  └─ GORRAS → [FRENTE] → Tallas: ÚNICA(20) (EDITABLE)
├─ Técnicas (EDITABLE)
├─ Observaciones Técnicas (EDITABLE)
├─ Observaciones Generales (EDITABLE)
└─ Fotos (EDITABLE)
```

---

## 🔧 ARCHIVOS A MODIFICAR

### 1️⃣ `public/js/crear-pedido-editable.js`
- ✏️ Actualizar `renderizarPrendasEditables()` para renderizar secciones en tab LOGO
- ✏️ Crear `renderizarSeccionesLogo()` para mostrar secciones cargadas
- ✏️ Crear `agregarSeccionLogo()` para agregar nuevas secciones
- ✏️ Crear `editarSeccionLogo()` para editar secciones existentes
- ✏️ Actualizar `guardarSeccionLogo()` para guardar cambios

### 2️⃣ `resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php`
- ✏️ Agregar contenedor HTML para secciones en tab logo
- ✏️ Agregar estilos CSS para secciones

### 3️⃣ `app/Http/Controllers/Asesores/PedidosProduccionController.php`
- ✏️ Asegurar que `obtener-datos-cotizacion` INCLUYA secciones en la respuesta

---

## 📋 CHECKLIST IMPLEMENTACIÓN

- [ ] Traer `secciones` desde BD en endpoint `/obtener-datos-cotizacion/{id}`
- [ ] Crear función `renderizarSeccionesEditables(secciones)`
- [ ] Agregar contenedor HTML en tab LOGO para secciones
- [ ] Crear función `agregarSeccionLogo()` con modal
- [ ] Crear función `editarSeccionLogo(index)`
- [ ] Crear función `eliminarSeccionLogo(index)`
- [ ] Actualizar guardado de pedido para incluir secciones
- [ ] Pruebas en cotización combinada (PL)

---

## 🎯 PRÓXIMOS PASOS RECOMENDADOS

1. **Primero**: Verificar que el endpoint trae `secciones` correctamente
2. **Luego**: Renderizar secciones existentes como READ-ONLY (solo lectura)
3. **Después**: Hacer secciones EDITABLES con modal similar a bordado/create
4. **Finalmente**: Guardar cambios en tabla `pedido_logo_secciones` o JSON

---

## 📚 REFERENCIAS

- Crear Cotización Logo: `resources/views/cotizaciones/bordado/create.blade.php`
- Mostrar Cotización Logo: `resources/views/components/cotizaciones/show/logo-tab.blade.php`
- Crear Pedido: `public/js/crear-pedido-editable.js`
- Modelo: `app/Models/LogoCotizacion.php`

