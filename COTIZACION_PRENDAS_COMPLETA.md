# 🎯 COTIZACIÓN COMPLETA DE PRENDAS - IMPLEMENTACIÓN FINAL

## 📋 OBJETIVO

Crear un flujo completo de cotización que integre:
1. **Arquitectura limpia de prendas** (Backend)
2. **Wizard de 4 pasos** (Frontend)
3. **Gestión de logo/bordado** (Paso 3)
4. **Revisión y envío** (Paso 4)

---

## 🔄 FLUJO COMPLETO

```
PASO 1: INFORMACIÓN DEL CLIENTE
├── Nombre cliente
├── Asesor/Asesora
└── Fecha cotización

PASO 2: PRENDAS DEL PEDIDO
├── Selector de prendas existentes (API)
├── Agregar prenda manual
├── Crear nueva prenda on-the-fly
├── Tipo de cotización (M/D/X)
└── Tallas por prenda

PASO 3: LOGO/BORDADO/TÉCNICAS
├── Descripción del logo
├── Imágenes del logo (drag & drop)
├── Técnicas (Bordado, DTF, Estampado, Sublimado)
├── Ubicación (Camisa, Jean/Sudadera, Gorras)
└── Observaciones generales

PASO 4: REVISIÓN Y ENVÍO
├── Resumen de cliente
├── Resumen de prendas
├── Resumen de técnicas
├── Botón Guardar/Enviar
└── Botón Descargar PDF
```

---

## 🏗️ ARQUITECTURA DE DATOS

### Base de Datos

```sql
-- Tabla principal de cotizaciones
CREATE TABLE cotizaciones (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cliente VARCHAR(255) NOT NULL,
    asesor_id BIGINT,
    tipo_cotizacion ENUM('M', 'D', 'X'),
    fecha_cotizacion DATE,
    productos JSON,  -- Array de prendas
    logo_descripcion TEXT,
    logo_imagenes JSON,  -- Array de URLs
    tecnicas JSON,  -- Array de técnicas
    ubicaciones JSON,  -- Array de ubicaciones
    observaciones_generales JSON,
    estado ENUM('borrador', 'enviada', 'aceptada', 'rechazada'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabla de prendas (desde arquitectura limpia)
CREATE TABLE prendas_cot (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    nombre_producto VARCHAR(255),
    descripcion TEXT,
    tipo_prenda_id BIGINT,
    genero_id BIGINT,
    estado ENUM('activo', 'inactivo'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabla de variantes de prendas
CREATE TABLE prenda_variantes_cot (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    prenda_cot_id BIGINT,
    tipo_manga_id BIGINT,
    tipo_broche_id BIGINT,
    tiene_bolsillos BOOLEAN,
    tiene_reflectivo BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Tabla de telas
CREATE TABLE prenda_telas_cot (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    variante_prenda_cot_id BIGINT,
    color_id BIGINT,
    tela_id BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## 🔌 ENDPOINTS API UTILIZADOS

### Prendas
```
GET    /api/prendas              - Listar prendas
POST   /api/prendas              - Crear prenda
GET    /api/prendas/{id}         - Obtener prenda
GET    /api/prendas/search?q=... - Buscar prendas
```

### Cotizaciones (Crear/Actualizar)
```
POST   /api/cotizaciones         - Crear cotización
PUT    /api/cotizaciones/{id}    - Actualizar cotización
GET    /api/cotizaciones/{id}    - Obtener cotización
DELETE /api/cotizaciones/{id}    - Eliminar cotización
```

---

## 📝 PASO 1: INFORMACIÓN DEL CLIENTE

### Blade (paso-uno.blade.php)

```blade
<!-- PASO 1 -->
<div class="form-step active" data-step="1">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 1: INFORMACIÓN DEL CLIENTE</h2>
        <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">CUÉNTANOS QUIÉN ES TU CLIENTE</p>
    </div>

    <div style="background: #f0f7ff; border-left: 4px solid #3498db; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>{{ Auth::user()->genero === 'F' ? 'ASESORA COMERCIAL' : 'ASESOR COMERCIAL' }}:</strong>
                    {{ Auth::user()->name }}
                </p>
            </div>
            <div>
                <p style="margin: 0; font-size: 0.9rem; color: #666;">
                    <strong>FECHA:</strong>
                    <input type="date" id="fechaActual" name="fecha_cotizacion" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; font-size: 0.9rem; cursor: pointer;">
                </p>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-group-large">
            <label for="cliente"><i class="fas fa-user"></i> NOMBRE DEL CLIENTE *</label>
            <input type="text" id="cliente" name="cliente" class="input-large" placeholder="EJ: JUAN GARCÍA, EMPRESA ABC..." required>
            <small class="help-text">EL NOMBRE DE TU CLIENTE O EMPRESA</small>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-next" onclick="irAlPaso(2)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
```

---

## 🎨 PASO 2: PRENDAS DEL PEDIDO

### Blade (paso-dos.blade.php)

```blade
<!-- PASO 2 -->
<div class="form-step" data-step="2">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 2: PRENDAS DEL PEDIDO</h2>
            <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE</p>
        </div>
        
        <div style="display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #0066cc, #0052a3); border: 2px solid #0052a3; border-radius: 8px; padding: 0.8rem 1.2rem; box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);">
            <label for="tipo_cotizacion" style="font-weight: 700; font-size: 0.85rem; color: white; white-space: nowrap; display: flex; align-items: center; gap: 6px; margin: 0;">
                <i class="fas fa-tag"></i> Tipo
            </label>
            <select id="tipo_cotizacion" name="tipo_cotizacion" style="padding: 0.5rem 0.6rem; border: 2px solid white; border-radius: 6px; font-size: 0.85rem; cursor: pointer; background-color: white; text-align: center; color: #0066cc; font-weight: 600; min-width: 80px;">
                <option value="">Selecciona</option>
                <option value="M">M</option>
                <option value="D">D</option>
                <option value="X">X</option>
            </select>
        </div>
    </div>

    <div class="form-section">
        <!-- SELECTOR DE PRENDAS EXISTENTES -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <label style="font-weight: bold; font-size: 1rem; display: block; margin-bottom: 10px;">
                <i class="fas fa-search"></i> Seleccionar Prenda Existente
            </label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="buscar_prenda" placeholder="Buscar prenda..." style="flex: 1; padding: 10px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem;" onkeyup="buscarPrendas(this.value)">
                <select id="selector_prendas" style="flex: 1; padding: 10px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem;">
                    <option value="">-- Seleccionar prenda --</option>
                </select>
                <button type="button" onclick="agregarPrendaSeleccionada()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
        </div>

        <!-- CONTENEDOR DE PRENDAS AGREGADAS -->
        <div class="productos-container" id="productosContainer"></div>
    </div>

    <!-- Botón flotante -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
        <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
            <button type="button" onclick="agregarProductoFriendly(); document.getElementById('menuFlotante').style.display='none';" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-plus" style="color: #1e40af; font-size: 1.2rem;"></i>
                <span>Agregar Prenda Manual</span>
            </button>
            <button type="button" onclick="abrirModalCrearPrenda(); document.getElementById('menuFlotante').style.display='none';" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-plus-circle" style="color: #27ae60; font-size: 1.2rem;"></i>
                <span>Crear Nueva Prenda</span>
            </button>
        </div>
        
        <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none';" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4);">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(1)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="irAlPaso(3)">
            SIGUIENTE <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
```

---

## 🎨 PASO 3: LOGO/BORDADO/TÉCNICAS

### Blade (paso-tres.blade.php)

```blade
<!-- PASO 3: LOGO -->
<div class="form-step" data-step="3">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 3: LOGO/BORDADO/TÉCNICAS</h2>
        <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">ESPECIFICA LOS DETALLES DE BORDADO Y ESTAMPADO</p>
    </div>

    <div class="form-section">
        <!-- DESCRIPCIÓN DEL LOGO/BORDADO -->
        <div class="form-group-large">
            <label for="descripcion_logo"><i class="fas fa-pen"></i> DESCRIPCIÓN DEL LOGO/BORDADO</label>
            <textarea id="descripcion_logo" name="descripcion_logo" class="input-large" rows="3" placeholder="Describe el logo, bordado o estampado que deseas..." style="width: 100%; padding: 12px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem; font-family: inherit;"></textarea>
            <small class="help-text">Incluye detalles sobre colores, tamaño, posición, etc.</small>
        </div>

        <!-- IMÁGENES -->
        <div class="form-group-large">
            <label for="imagenes_bordado"><i class="fas fa-images"></i> IMÁGENES (MÁXIMO 5)</label>
            <div id="drop_zone_imagenes" style="border: 2px dashed #3498db; border-radius: 8px; padding: 30px; text-align: center; background: #f0f7ff; cursor: pointer; margin-bottom: 10px;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #3498db; margin-bottom: 10px; display: block;"></i>
                <p style="margin: 10px 0; color: #3498db; font-weight: 600;">ARRASTRA IMÁGENES AQUÍ O HAZ CLIC</p>
                <p style="margin: 5px 0; color: #666; font-size: 0.9rem;">Máximo 5 imágenes</p>
                <input type="file" id="imagenes_bordado" name="imagenes_bordado[]" accept="image/*" multiple style="display: none;">
            </div>
            <div id="galeria_imagenes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; margin-top: 10px;"></div>
        </div>

        <!-- TÉCNICAS -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Técnicas disponibles</label>
                <button type="button" onclick="agregarTecnica()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold;">+</button>
            </div>
            
            <select id="selector_tecnicas" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="if(this.value) { agregarTecnica(); }">
                <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                <option value="BORDADO">BORDADO</option>
                <option value="DTF">DTF</option>
                <option value="ESTAMPADO">ESTAMPADO</option>
                <option value="SUBLIMADO">SUBLIMADO</option>
            </select>
            
            <div id="tecnicas_seleccionadas" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 30px;"></div>
            
            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Observaciones</label>
            <textarea id="observaciones_tecnicas" name="observaciones_tecnicas" class="input-large" rows="2" placeholder="Observaciones..." style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;"></textarea>
        </div>

        <!-- UBICACIÓN -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Ubicación</label>
                <button type="button" onclick="agregarSeccion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold;">+</button>
            </div>
            
            <label for="seccion_prenda" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
            <select id="seccion_prenda" class="input-large" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                <option value="CAMISA">CAMISA</option>
                <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                <option value="GORRAS">GORRAS</option>
            </select>
            
            <div id="secciones_agregadas" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;"></div>
        </div>

        <!-- OBSERVACIONES GENERALES -->
        <div style="background: #f9f9f9; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <label style="font-weight: bold; font-size: 1.1rem; margin: 0;">Observaciones Generales</label>
                <button type="button" onclick="agregarObservacion()" style="background: #3498db; color: white; border: none; border-radius: 50%; width: 36px; height: 36px; cursor: pointer; font-size: 1.5rem; font-weight: bold;">+</button>
            </div>
            
            <div id="observaciones_lista" style="display: flex; flex-direction: column; gap: 10px;"></div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(2)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-next" onclick="irAlPaso(4)">
            REVISAR <i class="fas fa-arrow-right"></i>
        </button>
    </div>
</div>
```

---

## 📊 PASO 4: REVISIÓN Y ENVÍO

### Blade (paso-cuatro.blade.php)

```blade
<!-- PASO 4: REVISIÓN -->
<div class="form-step" data-step="4">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 4: REVISIÓN Y ENVÍO</h2>
        <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">REVISA TODOS LOS DATOS ANTES DE ENVIAR</p>
    </div>

    <div class="form-section">
        <!-- RESUMEN CLIENTE -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">📋 INFORMACIÓN DEL CLIENTE</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Cliente:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_cliente">-</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Asesor/a:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_asesor">-</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Fecha:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_fecha">-</p>
                </div>
                <div>
                    <p style="margin: 0; font-size: 0.9rem; color: #666;"><strong>Tipo:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 1rem;" id="resumen_tipo">-</p>
                </div>
            </div>
        </div>

        <!-- RESUMEN PRENDAS -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">👕 PRENDAS</h3>
            <div id="resumen_prendas" style="display: grid; gap: 10px;"></div>
        </div>

        <!-- RESUMEN LOGO/TÉCNICAS -->
        <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #0066cc;">🎨 LOGO/BORDADO/TÉCNICAS</h3>
            <div>
                <p style="margin: 0 0 10px 0; font-size: 0.9rem;"><strong>Descripción:</strong></p>
                <p style="margin: 0; font-size: 0.9rem; color: #666;" id="resumen_logo_desc">-</p>
            </div>
            <div style="margin-top: 10px;">
                <p style="margin: 0 0 10px 0; font-size: 0.9rem;"><strong>Técnicas:</strong></p>
                <div id="resumen_tecnicas" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn-prev" onclick="irAlPaso(3)">
            <i class="fas fa-arrow-left"></i> ANTERIOR
        </button>
        <button type="button" class="btn-success" onclick="guardarCotizacion()">
            <i class="fas fa-save"></i> GUARDAR COTIZACIÓN
        </button>
        <button type="button" class="btn-primary" onclick="enviarCotizacion()">
            <i class="fas fa-paper-plane"></i> ENVIAR COTIZACIÓN
        </button>
    </div>
</div>
```

---

## 🔧 JAVASCRIPT PRINCIPAL

### Crear archivo: `public/js/cotizacion-prendas.js`

```javascript
// ============================================
// COTIZACIÓN COMPLETA DE PRENDAS
// ============================================

let prendas = [];
let cotizacionData = {
    cliente: '',
    asesor: '',
    fecha: '',
    tipo_cotizacion: '',
    productos: [],
    logo_descripcion: '',
    logo_imagenes: [],
    tecnicas: [],
    ubicaciones: [],
    observaciones_generales: []
};

/**
 * Inicializar
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarPrendasDisponibles();
    establecerFechaActual();
});

/**
 * Establecer fecha actual
 */
function establecerFechaActual() {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaActual').value = hoy;
}

/**
 * Cargar prendas desde API
 */
async function cargarPrendasDisponibles() {
    try {
        const response = await fetch('/api/prendas', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        prendas = data.data;

        const selector = document.getElementById('selector_prendas');
        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });

        console.log('✅ Prendas cargadas:', prendas);
    } catch (error) {
        console.error('❌ Error cargando prendas:', error);
    }
}

/**
 * Buscar prendas
 */
function buscarPrendas(termino) {
    if (!termino) {
        cargarPrendasDisponibles();
        return;
    }

    fetch(`/api/prendas/search?q=${termino}`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        prendas = data.data;

        const selector = document.getElementById('selector_prendas');
        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });
    })
    .catch(error => console.error('❌ Error buscando prendas:', error));
}

/**
 * Agregar prenda seleccionada
 */
function agregarPrendaSeleccionada() {
    const selector = document.getElementById('selector_prendas');
    const prendaId = selector.value;

    if (!prendaId) {
        alert('Por favor selecciona una prenda');
        return;
    }

    const prenda = prendas.find(p => p.id == prendaId);
    if (!prenda) return;

    agregarProductoFriendly();

    setTimeout(() => {
        const ultimoProducto = document.querySelectorAll('.producto-card')[
            document.querySelectorAll('.producto-card').length - 1
        ];

        if (ultimoProducto) {
            const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
            if (inputNombre) inputNombre.value = prenda.nombre_producto;

            const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
            if (textareaDesc) textareaDesc.value = prenda.descripcion || '';

            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                prenda.tallas.forEach(talla => {
                    const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla.talla}"]`);
                    if (tallaBtn) tallaBtn.click();
                });
            }

            console.log('✅ Prenda agregada:', prenda.nombre_producto);
        }
    }, 500);

    selector.value = '';
}

/**
 * Guardar cotización
 */
async function guardarCotizacion() {
    try {
        // Recopilar datos
        const cliente = document.getElementById('cliente').value;
        const fecha = document.getElementById('fechaActual').value;
        const tipo = document.getElementById('tipo_cotizacion').value;

        if (!cliente || !fecha) {
            alert('Por favor completa todos los campos requeridos');
            return;
        }

        const productos = recopilarProductos();
        const tecnicas = recopilarTecnicas();
        const ubicaciones = recopilarUbicaciones();
        const observaciones = recopilarObservaciones();

        const datos = {
            cliente,
            fecha_cotizacion: fecha,
            tipo_cotizacion: tipo,
            productos,
            logo_descripcion: document.getElementById('descripcion_logo').value,
            logo_imagenes: cotizacionData.logo_imagenes,
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas').value,
            estado: 'borrador'
        };

        const response = await fetch('/api/cotizaciones', {
            method: 'POST',
            body: JSON.stringify(datos),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Cotización guardada:', data.data);
            alert('✅ Cotización guardada como borrador');
            // Redirigir o limpiar formulario
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('❌ Error guardando cotización:', error);
        alert('Error: ' + error.message);
    }
}

/**
 * Enviar cotización
 */
async function enviarCotizacion() {
    try {
        const cliente = document.getElementById('cliente').value;
        const fecha = document.getElementById('fechaActual').value;
        const tipo = document.getElementById('tipo_cotizacion').value;

        if (!cliente || !fecha) {
            alert('Por favor completa todos los campos requeridos');
            return;
        }

        const productos = recopilarProductos();
        const tecnicas = recopilarTecnicas();
        const ubicaciones = recopilarUbicaciones();
        const observaciones = recopilarObservaciones();

        const datos = {
            cliente,
            fecha_cotizacion: fecha,
            tipo_cotizacion: tipo,
            productos,
            logo_descripcion: document.getElementById('descripcion_logo').value,
            logo_imagenes: cotizacionData.logo_imagenes,
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas').value,
            estado: 'enviada'
        };

        const response = await fetch('/api/cotizaciones', {
            method: 'POST',
            body: JSON.stringify(datos),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Cotización enviada:', data.data);
            alert('✅ Cotización enviada exitosamente');
            // Redirigir a lista de cotizaciones
            window.location.href = '/cotizaciones';
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        console.error('❌ Error enviando cotización:', error);
        alert('Error: ' + error.message);
    }
}

/**
 * Recopilar productos
 */
function recopilarProductos() {
    const productos = [];
    document.querySelectorAll('.producto-card').forEach(card => {
        const nombre = card.querySelector('input[name*="nombre_producto"]')?.value;
        const descripcion = card.querySelector('textarea[name*="descripcion"]')?.value;
        const tallas = Array.from(card.querySelectorAll('.talla-btn.active')).map(btn => btn.dataset.talla);

        if (nombre) {
            productos.push({
                nombre_producto: nombre,
                descripcion,
                tallas
            });
        }
    });
    return productos;
}

/**
 * Recopilar técnicas
 */
function recopilarTecnicas() {
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas .tecnica-tag').forEach(tag => {
        tecnicas.push(tag.textContent.replace('✕', '').trim());
    });
    return tecnicas;
}

/**
 * Recopilar ubicaciones
 */
function recopilarUbicaciones() {
    const ubicaciones = [];
    document.querySelectorAll('#secciones_agregadas .seccion-card').forEach(card => {
        const seccion = card.querySelector('input[name*="seccion"]')?.value;
        if (seccion) ubicaciones.push(seccion);
    });
    return ubicaciones;
}

/**
 * Recopilar observaciones
 */
function recopilarObservaciones() {
    const observaciones = [];
    document.querySelectorAll('#observaciones_lista .observacion-item').forEach(item => {
        const texto = item.querySelector('input[name*="observacion"]')?.value;
        if (texto) observaciones.push({ texto });
    });
    return observaciones;
}

/**
 * Actualizar resumen (Paso 4)
 */
function actualizarResumen() {
    document.getElementById('resumen_cliente').textContent = document.getElementById('cliente').value || '-';
    document.getElementById('resumen_asesor').textContent = document.querySelector('[name="asesor"]')?.value || '-';
    document.getElementById('resumen_fecha').textContent = document.getElementById('fechaActual').value || '-';
    document.getElementById('resumen_tipo').textContent = document.getElementById('tipo_cotizacion').value || '-';

    // Resumen de prendas
    const resumenPrendas = document.getElementById('resumen_prendas');
    resumenPrendas.innerHTML = '';
    const productos = recopilarProductos();
    productos.forEach((prod, idx) => {
        const div = document.createElement('div');
        div.style.cssText = 'background: white; padding: 10px; border-radius: 4px; border-left: 4px solid #3498db;';
        div.innerHTML = `
            <strong>${idx + 1}. ${prod.nombre_producto}</strong><br>
            <small>Tallas: ${prod.tallas.join(', ')}</small>
        `;
        resumenPrendas.appendChild(div);
    });

    // Resumen de técnicas
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    resumenTecnicas.innerHTML = '';
    const tecnicas = recopilarTecnicas();
    tecnicas.forEach(tec => {
        const span = document.createElement('span');
        span.style.cssText = 'background: #3498db; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem;';
        span.textContent = tec;
        resumenTecnicas.appendChild(span);
    });

    document.getElementById('resumen_logo_desc').textContent = document.getElementById('descripcion_logo').value || '-';
}

/**
 * Navegar entre pasos
 */
function irAlPaso(paso) {
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });

    const pasoElement = document.querySelector(`[data-step="${paso}"]`);
    if (pasoElement) {
        pasoElement.classList.add('active');
        
        // Actualizar resumen si vamos al paso 4
        if (paso === 4) {
            actualizarResumen();
        }

        // Scroll al inicio
        window.scrollTo(0, 0);
    }
}
```

---

## 📋 CONTROLADOR BACKEND

### Crear: `app/Http/Controllers/CotizacionPrendaController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;

class CotizacionPrendaController extends Controller
{
    /**
     * Crear cotización
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'fecha_cotizacion' => 'required|date',
                'tipo_cotizacion' => 'nullable|in:M,D,X',
                'productos' => 'required|array',
                'logo_descripcion' => 'nullable|string',
                'logo_imagenes' => 'nullable|array',
                'tecnicas' => 'nullable|array',
                'ubicaciones' => 'nullable|array',
                'observaciones_generales' => 'nullable|array',
                'observaciones_tecnicas' => 'nullable|string',
                'estado' => 'required|in:borrador,enviada,aceptada,rechazada'
            ]);

            $cotizacion = Cotizacion::create([
                'cliente' => $validated['cliente'],
                'asesor_id' => auth()->id(),
                'fecha_cotizacion' => $validated['fecha_cotizacion'],
                'tipo_cotizacion' => $validated['tipo_cotizacion'],
                'productos' => json_encode($validated['productos']),
                'logo_descripcion' => $validated['logo_descripcion'],
                'logo_imagenes' => json_encode($validated['logo_imagenes'] ?? []),
                'tecnicas' => json_encode($validated['tecnicas'] ?? []),
                'ubicaciones' => json_encode($validated['ubicaciones'] ?? []),
                'observaciones_generales' => json_encode($validated['observaciones_generales'] ?? []),
                'observaciones_tecnicas' => $validated['observaciones_tecnicas'],
                'estado' => $validated['estado']
            ]);

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
                'message' => 'Cotización creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creando cotización:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cotización
     */
    public function show($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $cotizacion
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        }
    }

    /**
     * Actualizar cotización
     */
    public function update(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);

            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'fecha_cotizacion' => 'required|date',
                'tipo_cotizacion' => 'nullable|in:M,D,X',
                'productos' => 'required|array',
                'logo_descripcion' => 'nullable|string',
                'logo_imagenes' => 'nullable|array',
                'tecnicas' => 'nullable|array',
                'ubicaciones' => 'nullable|array',
                'observaciones_generales' => 'nullable|array',
                'observaciones_tecnicas' => 'nullable|string',
                'estado' => 'required|in:borrador,enviada,aceptada,rechazada'
            ]);

            $cotizacion->update([
                'cliente' => $validated['cliente'],
                'fecha_cotizacion' => $validated['fecha_cotizacion'],
                'tipo_cotizacion' => $validated['tipo_cotizacion'],
                'productos' => json_encode($validated['productos']),
                'logo_descripcion' => $validated['logo_descripcion'],
                'logo_imagenes' => json_encode($validated['logo_imagenes'] ?? []),
                'tecnicas' => json_encode($validated['tecnicas'] ?? []),
                'ubicaciones' => json_encode($validated['ubicaciones'] ?? []),
                'observaciones_generales' => json_encode($validated['observaciones_generales'] ?? []),
                'observaciones_tecnicas' => $validated['observaciones_tecnicas'],
                'estado' => $validated['estado']
            ]);

            return response()->json([
                'success' => true,
                'data' => $cotizacion,
                'message' => 'Cotización actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización
     */
    public function destroy($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error eliminando cotización'
            ], 500);
        }
    }
}
```

---

## 🛣️ RUTAS

### Agregar a `routes/api.php`:

```php
Route::middleware('auth:sanctum')->group(function () {
    // Prendas
    Route::apiResource('prendas', PrendaController::class);
    Route::get('prendas/search', [PrendaController::class, 'search']);
    
    // Cotizaciones
    Route::apiResource('cotizaciones', CotizacionPrendaController::class);
});
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Crear migraciones de cotizaciones
- [ ] Crear modelo Cotizacion
- [ ] Crear CotizacionPrendaController
- [ ] Crear archivo `cotizacion-prendas.js`
- [ ] Crear vistas de pasos (paso-uno, paso-dos, paso-tres, paso-cuatro)
- [ ] Registrar rutas API
- [ ] Probar flujo completo
- [ ] Agregar validaciones frontend
- [ ] Agregar notificaciones
- [ ] Generar PDF de cotización

---

**¡Cotización de prendas completamente integrada!** 🎉

