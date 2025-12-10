# 🔗 INTEGRACIÓN DE PRENDAS CON TU WIZARD (PASO-UNO, PASO-DOS, PASO-TRES)

## 📋 CONTEXTO

Tu sistema actual usa un **wizard de 4 pasos**:
- **PASO 1:** Información del cliente
- **PASO 2:** Prendas del pedido
- **PASO 3:** Logo/Bordado/Técnicas
- **PASO 4:** Revisión y envío

La arquitectura de prendas se integra principalmente en **PASO 2** (Prendas del pedido).

---

## 🎯 CÓMO INTEGRAR LA ARQUITECTURA DE PRENDAS

### 1. PASO 2 - AGREGAR PRENDA CON ARQUITECTURA LIMPIA

En lugar de agregar prendas manualmente, usaremos la API de prendas para:
- Listar prendas disponibles
- Seleccionar prendas existentes
- Crear nuevas prendas on-the-fly

#### Modificación en `paso-dos.blade.php`:

```blade
<!-- PASO 2 -->
<div class="form-step" data-step="2">
    <div class="step-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 2: PRENDAS DEL PEDIDO</h2>
            <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">AGREGA LAS PRENDAS QUE TU CLIENTE QUIERE</p>
        </div>
        
        <!-- Selector de tipo de cotización -->
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
                <select id="selector_prendas" style="flex: 1; padding: 10px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem;">
                    <option value="">-- Buscar prenda --</option>
                </select>
                <button type="button" onclick="agregarPrendaSeleccionada()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold;">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
        </div>

        <!-- CONTENEDOR DE PRENDAS AGREGADAS -->
        <div class="productos-container" id="productosContainer">
            @if(isset($esEdicion) && $esEdicion && isset($cotizacion) && $cotizacion->productos)
                <!-- Cargar productos guardados -->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const productos = {!! json_encode($cotizacion->productos) !!};
                        console.log('📦 Productos a cargar:', productos);
                        
                        productos.forEach((producto, idx) => {
                            agregarProductoFriendly();
                            
                            setTimeout(() => {
                                const ultimoProducto = document.querySelectorAll('.producto-card')[document.querySelectorAll('.producto-card').length - 1];
                                
                                if (ultimoProducto) {
                                    const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
                                    if (inputNombre) inputNombre.value = producto.nombre_producto || '';
                                    
                                    const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
                                    if (textareaDesc) textareaDesc.value = producto.descripcion || '';
                                    
                                    if (producto.tallas && Array.isArray(producto.tallas)) {
                                        producto.tallas.forEach(talla => {
                                            const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla}"]`);
                                            if (tallaBtn) tallaBtn.click();
                                        });
                                    }
                                    
                                    console.log('✅ Producto cargado:', producto.nombre_producto);
                                }
                            }, 500);
                        });
                    });
                </script>
            @endif
        </div>
    </div>

    <!-- Botón flotante para agregar prenda -->
    <div style="position: fixed; bottom: 30px; right: 30px; z-index: 1000;">
        <!-- Menú flotante -->
        <div id="menuFlotante" style="display: none; position: absolute; bottom: 70px; right: 0; background: white; border-radius: 12px; box-shadow: 0 5px 40px rgba(0,0,0,0.16); overflow: hidden; min-width: 200px;">
            <button type="button" onclick="agregarProductoFriendly(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-plus" style="color: #1e40af; font-size: 1.2rem;"></i>
                <span>Agregar Prenda Manual</span>
            </button>
            <button type="button" onclick="abrirModalEspecificaciones(); document.getElementById('menuFlotante').style.display='none'; document.getElementById('btnFlotante').style.transform='scale(1) rotate(0deg)'" style="width: 100%; padding: 14px 18px; border: none; background: white; cursor: pointer; text-align: left; font-size: 0.95rem; color: #333; display: flex; align-items: center; gap: 12px; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                <i class="fas fa-sliders-h" style="color: #ff9800; font-size: 1.2rem;"></i>
                <span>Especificaciones</span>
            </button>
        </div>
        
        <!-- Botón principal flotante -->
        <button type="button" id="btnFlotante" onclick="const menu = document.getElementById('menuFlotante'); menu.style.display = menu.style.display === 'none' ? 'block' : 'none'; this.style.transform = menu.style.display === 'block' ? 'scale(1) rotate(45deg)' : 'scale(1) rotate(0deg)'" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #0ea5e9); color: white; border: none; cursor: pointer; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.4); transition: all 0.3s ease; position: relative;" onmouseover="this.style.boxShadow='0 6px 20px rgba(30, 64, 175, 0.5)'; this.style.transform='scale(1.1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')" onmouseout="this.style.boxShadow='0 4px 12px rgba(30, 64, 175, 0.4)'; this.style.transform='scale(1) ' + (document.getElementById('menuFlotante').style.display === 'block' ? 'rotate(45deg)' : 'rotate(0deg)')">
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

## 🔧 JAVASCRIPT PARA INTEGRACIÓN

### Crear archivo: `public/js/prendas/integracion-wizard.js`

```javascript
// ============================================
// INTEGRACIÓN DE PRENDAS CON WIZARD
// ============================================

let prendas = [];
let prendaSeleccionada = null;

/**
 * Cargar prendas disponibles al iniciar
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarPrendasDisponibles();
});

/**
 * Cargar prendas desde la API
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

        // Llenar selector de prendas
        const selector = document.getElementById('selector_prendas');
        selector.innerHTML = '<option value="">-- Buscar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            selector.appendChild(option);
        });

        console.log('✅ Prendas cargadas:', prendas);
    } catch (error) {
        console.error('❌ Error cargando prendas:', error);
    }
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

    // Agregar como producto
    agregarProductoFriendly();

    // Esperar a que se cree el elemento
    setTimeout(() => {
        const ultimoProducto = document.querySelectorAll('.producto-card')[
            document.querySelectorAll('.producto-card').length - 1
        ];

        if (ultimoProducto) {
            // Nombre
            const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
            if (inputNombre) inputNombre.value = prenda.nombre_producto;

            // Descripción
            const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
            if (textareaDesc) textareaDesc.value = prenda.descripcion || '';

            // Tallas
            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                prenda.tallas.forEach(talla => {
                    const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla.talla}"]`);
                    if (tallaBtn) tallaBtn.click();
                });
            }

            console.log('✅ Prenda agregada:', prenda.nombre_producto);
        }
    }, 500);

    // Limpiar selector
    selector.value = '';
}

/**
 * Buscar prendas en tiempo real
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
        selector.innerHTML = '<option value="">-- Buscar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            selector.appendChild(option);
        });
    })
    .catch(error => console.error('❌ Error buscando prendas:', error));
}

/**
 * Crear nueva prenda desde el wizard
 */
async function crearNuevaPrendaDesdeWizard(datos) {
    try {
        const formData = new FormData();

        // Datos básicos
        formData.append('nombre_producto', datos.nombre);
        formData.append('descripcion', datos.descripcion);
        formData.append('tipo_prenda', datos.tipo || 'OTRO');
        formData.append('genero', datos.genero || '');

        // Tallas
        if (datos.tallas && Array.isArray(datos.tallas)) {
            datos.tallas.forEach((talla, idx) => {
                formData.append(`tallas[${idx}]`, talla);
            });
        }

        // Variantes
        if (datos.variantes && Array.isArray(datos.variantes)) {
            datos.variantes.forEach((variante, idx) => {
                formData.append(`variantes[${idx}][tipo_manga_id]`, variante.manga || null);
                formData.append(`variantes[${idx}][tipo_broche_id]`, variante.broche || null);
                formData.append(`variantes[${idx}][tiene_bolsillos]`, variante.bolsillos || false);
                formData.append(`variantes[${idx}][tiene_reflectivo]`, variante.reflectivo || false);
            });
        }

        // Telas
        if (datos.telas && Array.isArray(datos.telas)) {
            datos.telas.forEach((tela, idx) => {
                formData.append(`telas[${idx}][nombre]`, tela.nombre);
                formData.append(`telas[${idx}][referencia]`, tela.referencia);
                formData.append(`telas[${idx}][color]`, tela.color);
            });
        }

        const response = await fetch('/api/prendas', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            console.log('✅ Prenda creada:', data.data);
            cargarPrendasDisponibles();
            return data.data;
        } else {
            console.error('❌ Error creando prenda:', data.message);
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('❌ Error:', error);
        alert('Error creando prenda: ' + error.message);
    }
}
```

---

## 📝 AGREGAR SCRIPT AL BLADE

En tu vista principal (donde incluyes los pasos), agrega:

```blade
<!-- Al final del archivo, antes de cerrar body -->
<script src="{{ asset('js/prendas/integracion-wizard.js') }}"></script>
```

---

## 🔄 FLUJO DE INTEGRACIÓN

### 1. Usuario abre el wizard
```
↓
Paso 1: Ingresa datos del cliente
↓
Paso 2: Selecciona prendas existentes O crea nuevas
  - Opción A: Selecciona de lista existente
  - Opción B: Crea nueva prenda on-the-fly
↓
Paso 3: Agrega logo, bordado, técnicas
↓
Paso 4: Revisa y envía
```

---

## 🎯 VENTAJAS DE ESTA INTEGRACIÓN

✅ **Reutilización:** Las prendas creadas se guardan en la BD
✅ **Eficiencia:** No duplicar prendas, seleccionar existentes
✅ **Flexibilidad:** Crear nuevas prendas on-the-fly
✅ **Escalabilidad:** Fácil agregar más funcionalidades
✅ **Limpieza:** Arquitectura separada en servicios
✅ **API:** Acceso a datos desde cualquier lugar

---

## 🚀 PRÓXIMOS PASOS

1. **Copiar `integracion-wizard.js`** a `public/js/prendas/`
2. **Actualizar `paso-dos.blade.php`** con el código anterior
3. **Incluir script** en tu vista principal
4. **Probar** agregar prendas desde el selector
5. **Crear nuevas prendas** on-the-fly si es necesario

---

## 📊 ESTRUCTURA FINAL

```
Tu Wizard (4 pasos)
├── Paso 1: Cliente ✅
├── Paso 2: Prendas ✅ (Integración con API)
│   ├── Selector de prendas existentes
│   ├── Agregar prenda manual
│   └── Crear nueva prenda
├── Paso 3: Logo/Bordado ✅
└── Paso 4: Revisión ✅

API de Prendas
├── GET /api/prendas (Listar)
├── POST /api/prendas (Crear)
├── GET /api/prendas/search (Buscar)
└── GET /api/prendas/{id} (Obtener)
```

---

**¡Integración lista para usar!** 🎉

