# 🔄 MODIFICACIONES AL CÓDIGO VIEJO - IMPLEMENTACIÓN NUEVA

## 📋 RESUMEN

Tu código viejo ya tiene una buena estructura. Solo necesitamos:
1. **Agregar selector de prendas existentes** en PASO 2
2. **Agregar JavaScript de integración** con la API
3. **Modificar PASO 4** para mostrar resumen completo
4. **Crear archivo de cotización** para guardar/enviar

---

## 🔧 MODIFICACIÓN 1: PASO 2 (paso-dos.blade.php)

### Cambio: Agregar selector de prendas existentes

**Ubicación:** Antes del `<div class="productos-container">`

**Reemplazar:**
```blade
<div class="form-section">
    <div class="productos-container" id="productosContainer">
```

**Por:**
```blade
<div class="form-section">
    <!-- SELECTOR DE PRENDAS EXISTENTES (NUEVO) -->
    <div style="background: #f0f7ff; border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
        <label style="font-weight: bold; font-size: 1rem; display: block; margin-bottom: 10px;">
            <i class="fas fa-search"></i> Seleccionar Prenda Existente
        </label>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" id="buscar_prenda" placeholder="Buscar prenda..." style="flex: 1; min-width: 200px; padding: 10px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem;" onkeyup="buscarPrendas(this.value)">
            <select id="selector_prendas" style="flex: 1; min-width: 200px; padding: 10px; border: 2px solid #3498db; border-radius: 6px; font-size: 0.9rem;">
                <option value="">-- Seleccionar prenda --</option>
            </select>
            <button type="button" onclick="agregarPrendaSeleccionada()" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; white-space: nowrap;">
                <i class="fas fa-plus"></i> Agregar
            </button>
        </div>
    </div>

    <div class="productos-container" id="productosContainer">
```

---

## 🔧 MODIFICACIÓN 2: PASO 4 (paso-cuatro.blade.php)

### Cambio: Mejorar resumen y agregar botones de guardar/enviar

**Reemplazar todo el contenido de paso-cuatro.blade.php por:**

```blade
<!-- PASO 4 -->
<div class="form-step" data-step="4">
    <div class="step-header">
        <h2 style="font-size: 1rem !important; margin: 0 0 0.2rem 0 !important;">PASO 4: REVISAR COTIZACIÓN</h2>
        <p style="font-size: 0.45rem !important; margin: 0 !important; color: #666 !important;">VERIFICA QUE TODO ESTÉ CORRECTO</p>
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
                    <p style="margin: 5px 0 0 0; font-size: 1rem;">{{ Auth::user()->name }}</p>
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
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-submit" id="btnGuardarBorrador" onclick="guardarCotizacion()" style="background: #95a5a6;">
                <i class="fas fa-save"></i> GUARDAR COMO BORRADOR
            </button>
            <button type="button" class="btn-submit" id="btnEnviarCotizacion" onclick="enviarCotizacion()" style="background: #27ae60;">
                <i class="fas fa-paper-plane"></i> ENVIAR COTIZACIÓN
            </button>
        </div>
    </div>
</div>
```

---

## 🔧 MODIFICACIÓN 3: Crear archivo JavaScript de integración

### Crear archivo: `public/js/prendas/integracion-cotizacion.js`

```javascript
// ============================================
// INTEGRACIÓN DE PRENDAS CON COTIZACIÓN
// ============================================

let prendas = [];

/**
 * Inicializar al cargar la página
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
        prendas = data.data || [];

        const selector = document.getElementById('selector_prendas');
        if (!selector) return;

        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });

        console.log('✅ Prendas cargadas:', prendas.length);
    } catch (error) {
        console.error('❌ Error cargando prendas:', error);
    }
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
        prendas = data.data || [];

        const selector = document.getElementById('selector_prendas');
        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });

        console.log('✅ Búsqueda completada:', prendas.length);
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

    // Llamar función existente para agregar producto
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
 * Recopilar datos de productos
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
    const cliente = document.getElementById('cliente')?.value || '-';
    const fecha = document.getElementById('fechaActual')?.value || '-';
    const tipo = document.getElementById('tipo_cotizacion')?.value || '-';

    document.getElementById('resumen_cliente').textContent = cliente;
    document.getElementById('resumen_fecha').textContent = fecha;
    document.getElementById('resumen_tipo').textContent = tipo;

    // Resumen de prendas
    const resumenPrendas = document.getElementById('resumen_prendas');
    if (resumenPrendas) {
        resumenPrendas.innerHTML = '';
        const productos = recopilarProductos();
        
        if (productos.length === 0) {
            resumenPrendas.innerHTML = '<p style="color: #999;">No hay prendas agregadas</p>';
        } else {
            productos.forEach((prod, idx) => {
                const div = document.createElement('div');
                div.style.cssText = 'background: white; padding: 10px; border-radius: 4px; border-left: 4px solid #3498db;';
                div.innerHTML = `
                    <strong>${idx + 1}. ${prod.nombre_producto}</strong><br>
                    <small>Tallas: ${prod.tallas.join(', ') || 'Sin tallas'}</small>
                `;
                resumenPrendas.appendChild(div);
            });
        }
    }

    // Resumen de técnicas
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    if (resumenTecnicas) {
        resumenTecnicas.innerHTML = '';
        const tecnicas = recopilarTecnicas();
        
        if (tecnicas.length === 0) {
            resumenTecnicas.innerHTML = '<p style="color: #999; font-size: 0.9rem;">No hay técnicas agregadas</p>';
        } else {
            tecnicas.forEach(tec => {
                const span = document.createElement('span');
                span.style.cssText = 'background: #3498db; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem;';
                span.textContent = tec;
                resumenTecnicas.appendChild(span);
            });
        }
    }

    document.getElementById('resumen_logo_desc').textContent = document.getElementById('descripcion_logo')?.value || '-';
}

/**
 * Guardar cotización como borrador
 */
async function guardarCotizacion() {
    try {
        const cliente = document.getElementById('cliente')?.value;
        const fecha = document.getElementById('fechaActual')?.value;
        const tipo = document.getElementById('tipo_cotizacion')?.value;

        if (!cliente || !fecha) {
            alert('Por favor completa los datos del cliente');
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
            logo_descripcion: document.getElementById('descripcion_logo')?.value || '',
            logo_imagenes: [],
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas')?.value || '',
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
            alert('✅ Cotización guardada como borrador');
            console.log('✅ Cotización guardada:', data.data);
            // Redirigir a lista de cotizaciones
            // window.location.href = '/cotizaciones';
        } else {
            alert('❌ Error: ' + (data.message || 'Error desconocido'));
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
        const cliente = document.getElementById('cliente')?.value;
        const fecha = document.getElementById('fechaActual')?.value;
        const tipo = document.getElementById('tipo_cotizacion')?.value;

        if (!cliente || !fecha) {
            alert('Por favor completa los datos del cliente');
            return;
        }

        const productos = recopilarProductos();
        if (productos.length === 0) {
            alert('Por favor agrega al menos una prenda');
            return;
        }

        const tecnicas = recopilarTecnicas();
        const ubicaciones = recopilarUbicaciones();
        const observaciones = recopilarObservaciones();

        const datos = {
            cliente,
            fecha_cotizacion: fecha,
            tipo_cotizacion: tipo,
            productos,
            logo_descripcion: document.getElementById('descripcion_logo')?.value || '',
            logo_imagenes: [],
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas')?.value || '',
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
            alert('✅ Cotización enviada exitosamente');
            console.log('✅ Cotización enviada:', data.data);
            // Redirigir a lista de cotizaciones
            window.location.href = '/cotizaciones';
        } else {
            alert('❌ Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('❌ Error enviando cotización:', error);
        alert('Error: ' + error.message);
    }
}

/**
 * Modificar función irAlPaso para actualizar resumen
 */
const irAlPasoOriginal = window.irAlPaso;
window.irAlPaso = function(paso) {
    irAlPasoOriginal(paso);
    
    // Actualizar resumen si vamos al paso 4
    if (paso === 4) {
        actualizarResumen();
    }
};
```

---

## 🔧 MODIFICACIÓN 4: Incluir script en tu vista

### En tu archivo principal de cotización (donde incluyes los pasos):

**Agregar antes de cerrar `</body>`:**

```blade
<!-- Scripts de integración de prendas -->
<script src="{{ asset('js/prendas/integracion-cotizacion.js') }}"></script>
```

---

## 🔧 MODIFICACIÓN 5: Crear Controlador de Cotizaciones

### Crear archivo: `app/Http/Controllers/CotizacionPrendaController.php`

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
}
```

---

## 🔧 MODIFICACIÓN 6: Crear Modelo Cotizacion

### Crear archivo: `app/Models/Cotizacion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente',
        'asesor_id',
        'fecha_cotizacion',
        'tipo_cotizacion',
        'productos',
        'logo_descripcion',
        'logo_imagenes',
        'tecnicas',
        'ubicaciones',
        'observaciones_generales',
        'observaciones_tecnicas',
        'estado'
    ];

    protected $casts = [
        'productos' => 'array',
        'logo_imagenes' => 'array',
        'tecnicas' => 'array',
        'ubicaciones' => 'array',
        'observaciones_generales' => 'array',
        'fecha_cotizacion' => 'date'
    ];

    public function asesor()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
```

---

## 🔧 MODIFICACIÓN 7: Crear Migración

### Crear migración:

```bash
php artisan make:migration create_cotizaciones_table
```

**Contenido:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('cliente', 255);
            $table->unsignedBigInteger('asesor_id')->nullable();
            $table->enum('tipo_cotizacion', ['M', 'D', 'X'])->nullable();
            $table->date('fecha_cotizacion');
            $table->json('productos');
            $table->text('logo_descripcion')->nullable();
            $table->json('logo_imagenes')->nullable();
            $table->json('tecnicas')->nullable();
            $table->json('ubicaciones')->nullable();
            $table->json('observaciones_generales')->nullable();
            $table->text('observaciones_tecnicas')->nullable();
            $table->enum('estado', ['borrador', 'enviada', 'aceptada', 'rechazada'])->default('borrador');
            $table->timestamps();

            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('set null');
            $table->index('estado');
            $table->index('fecha_cotizacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
```

---

## 🔧 MODIFICACIÓN 8: Registrar Rutas

### En `routes/api.php`:

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

- [ ] Modificar `paso-dos.blade.php` - Agregar selector
- [ ] Modificar `paso-cuatro.blade.php` - Mejorar resumen
- [ ] Crear `integracion-cotizacion.js`
- [ ] Incluir script en vista principal
- [ ] Crear `CotizacionPrendaController.php`
- [ ] Crear modelo `Cotizacion.php`
- [ ] Crear migración `create_cotizaciones_table`
- [ ] Registrar rutas en `routes/api.php`
- [ ] Ejecutar migraciones: `php artisan migrate`
- [ ] Probar flujo completo

---

## 🚀 PRÓXIMOS PASOS

1. Aplicar modificaciones a tus archivos Blade
2. Crear archivos PHP (Controlador, Modelo)
3. Crear archivo JavaScript
4. Ejecutar migraciones
5. Probar el flujo completo

---

**¡Listo para implementar!** 🎉

