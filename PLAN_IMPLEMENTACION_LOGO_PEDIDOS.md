# Plan de Implementación: Logo en Pedidos (Replicando estructura de Cotización)

## RESUMEN EJECUTIVO

Replicar EXACTAMENTE la estructura, HTML y JavaScript de cotización de logo en el formulario de pedidos. La única diferencia será que en lugar de `LogoCotizacion`, usaremos `LogoPedido`.

---

## 1. ESTRUCTURA DE ARCHIVOS A CREAR/MODIFICAR

### A. Modelos (Laravel)
```
app/Models/
  ├── LogoPedido.php (similar a LogoCotizacion)
  ├── LogoPedidoTecnicaPrenda.php (similar a LogoCotizacionTecnicaPrenda)
  └── LogoPedidoTecnicaPrendaFoto.php (similar a LogoCotizacionTecnicaPrendaFoto)
```

### B. Controlador (Laravel)
```
app/Http/Controllers/Asesores/
  └── LogoPedidoTecnicaController.php (COPIAR y adaptar LogoCotizacionTecnicaController)
```

### C. Rutas (Laravel)
```
routes/web.php
  POST /logo-pedido/agregar-tecnica
  PUT /logo-pedido/tecnica-prenda/{id}
  DELETE /logo-pedido/tecnica-prenda/{id}
```

### D. Vistas (Blade)
```
resources/views/asesores/pedidos/
  ├── crear-desde-cotizacion-editable.blade.php (MODIFICAR - agregar modal de logo)
  └── partials/logo-modal.blade.php (NUEVA)
```

### E. JavaScript
```
public/js/
  ├── logo-pedido-tecnicas.js (COPIAR y adaptar logo-cotizacion-tecnicas.js)
  └── modulos/crear-pedido/logo-pedido-funciones.js (NUEVA - helpers)
```

---

## 2. PASO A PASO DE IMPLEMENTACIÓN

### PASO 1: Modelos en BD

#### Tabla: logo_pedidos
```sql
CREATE TABLE logo_pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED,
    tipo_venta VARCHAR(255),
    observaciones_generales JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos_produccion(id) ON DELETE CASCADE
);
```

#### Tabla: logo_pedido_tecnica_prendas
```sql
CREATE TABLE logo_pedido_tecnica_prendas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    logo_pedido_id BIGINT UNSIGNED,
    tipo_logo_id BIGINT UNSIGNED,
    nombre_prenda VARCHAR(255),
    observaciones LONGTEXT,
    ubicaciones JSON,
    talla_cantidad JSON,
    grupo_combinado BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (logo_pedido_id) REFERENCES logo_pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_logo_id) REFERENCES tipo_logo_cotizaciones(id)
);
```

#### Tabla: logo_pedido_tecnica_prendas_fotos
```sql
CREATE TABLE logo_pedido_tecnica_prendas_fotos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    logo_pedido_tecnica_prenda_id BIGINT UNSIGNED,
    ruta_original VARCHAR(500),
    ruta_webp VARCHAR(500),
    ruta_miniatura VARCHAR(500),
    orden INT,
    ancho INT,
    alto INT,
    tamaño INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (logo_pedido_tecnica_prenda_id) REFERENCES logo_pedido_tecnica_prendas(id) ON DELETE CASCADE
);
```

### PASO 2: Modelos Eloquent

```php
// app/Models/LogoPedido.php
class LogoPedido extends Model {
    protected $table = 'logo_pedidos';
    protected $fillable = ['pedido_id', 'tipo_venta', 'observaciones_generales'];
    protected $casts = ['observaciones_generales' => 'array'];
    
    public function prendas() {
        return $this->hasMany(LogoPedidoTecnicaPrenda::class);
    }
}

// app/Models/LogoPedidoTecnicaPrenda.php
class LogoPedidoTecnicaPrenda extends Model {
    protected $table = 'logo_pedido_tecnica_prendas';
    protected $fillable = ['logo_pedido_id', 'tipo_logo_id', 'nombre_prenda', 
                           'observaciones', 'ubicaciones', 'talla_cantidad', 'grupo_combinado'];
    protected $casts = ['ubicaciones' => 'array', 'talla_cantidad' => 'array'];
    
    public function logoPedido() {
        return $this->belongsTo(LogoPedido::class);
    }
    
    public function tipoLogo() {
        return $this->belongsTo(TipoLogoCotizacion::class, 'tipo_logo_id');
    }
    
    public function fotos() {
        return $this->hasMany(LogoPedidoTecnicaPrendaFoto::class, 'logo_pedido_tecnica_prenda_id');
    }
}

// app/Models/LogoPedidoTecnicaPrendaFoto.php
class LogoPedidoTecnicaPrendaFoto extends Model {
    protected $table = 'logo_pedido_tecnica_prendas_fotos';
    protected $fillable = ['logo_pedido_tecnica_prenda_id', 'ruta_original', 'ruta_webp',
                          'ruta_miniatura', 'orden', 'ancho', 'alto', 'tamaño'];
}
```

### PASO 3: HTML Modal (crear-desde-cotizacion-editable.blade.php)

Agregar el modal IDÉNTICO al de cotización, antes del cierre de `@endsection`:

```blade
<!-- MODAL PARA AGREGAR PRENDAS CON TÉCNICA SELECCIONADA -->
<div id="modalAgregarTecnicaPedido" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 24px; max-width: 650px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <!-- Header del Modal -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600; color: #333;">Agregar Técnica/Prenda</h2>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 0.85rem;">Técnica: <strong id="tecnicaSeleccionadaNombrePedido" style="color: #333;">--</strong></p>
            </div>
            <button type="button" onclick="cerrarModalAgregarTecnicaPedido()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #ccc; padding: 0; line-height: 1; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        
        <!-- Lista de Prendas -->
        <div id="listaPrendasPedido" style="margin-bottom: 16px;">
            <!-- Prendas dinámicas aquí -->
        </div>
        
        <!-- Sin prendas -->
        <div id="noPrendasMsgPedido" style="padding: 16px; text-align: center; background: #f9f9f9; border-radius: 4px; color: #999; margin-bottom: 16px; display: block; font-size: 0.9rem;">
            <p style="margin: 0;">Agrega prendas con el botón de abajo</p>
        </div>
        
        <!-- Botón agregar prenda -->
        <button type="button" onclick="agregarFilaPrendaPedido()" style="width: 100%; background: #f0f0f0; color: #333; border: 1px solid #ddd; font-size: 0.9rem; cursor: pointer; padding: 10px 12px; border-radius: 4px; font-weight: 500; margin-bottom: 16px; transition: background 0.2s;">
            + Agregar prenda
        </button>
        
        <!-- Botones de acción -->
        <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #eee; padding-top: 16px;">
            <button type="button" onclick="cerrarModalAgregarTecnicaPedido()" style="background: white; color: #333; border: 1px solid #ddd; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Cancelar
            </button>
            <button type="button" onclick="guardarTecnicaPedido()" style="background: #333; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 500; font-size: 0.9rem;">
                Guardar
            </button>
        </div>
    </div>
</div>

<!-- Script -->
<script src="{{ asset('js/logo-pedido-tecnicas.js') }}"></script>
```

### PASO 4: JavaScript (logo-pedido-tecnicas.js)

**COPIAR** `public/js/logo-cotizacion-tecnicas.js` COMPLETO y:

1. Cambiar nombres:
   - `modalAgregarTecnica` → `modalAgregarTecnicaPedido`
   - `logoCotizacionId` → `logoPedidoId`
   - `tecnicasAgregadas` → `tecnicasAgregadasPedido`
   - `listaPrendas` → `listaPrendasPedido`
   - Todas las funciones: agregar sufijo `Pedido`

2. Cambiar URLs:
   - `/api/logo-cotizacion-tecnicas/` → `/api/logo-pedido-tecnicas/`
   - `/cotizaciones-bordado/` → `/pedidos-produccion/`

3. Ejemplo de cambios mínimos:
```javascript
// Antes (cotización)
function abrirModalAgregarTecnica() { ... }

// Después (pedido)
function abrirModalAgregarTecnicaPedido() { ... }

// Antes
const modal = document.getElementById('modalAgregarTecnica');

// Después
const modal = document.getElementById('modalAgregarTecnicaPedido');
```

### PASO 5: Actualizar `obtenerDatosCotizacion()` 

En `PedidosProduccionController.php`, modificar el método para retornar `prendas_tecnicas`:

```php
public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
{
    $cotizacion = Cotizacion::with([
        // ... existing relations
        'logoCotizacion.prendas.fotos',  // ← AGREGAR
        'logoCotizacion.prendas.tipoLogo'  // ← AGREGAR
    ])->findOrFail($cotizacionId);
    
    return response()->json([
        // ... existing fields
        
        // ✅ AGREGAR: Prendas técnicas del logo
        'prendas_tecnicas' => $cotizacion->logoCotizacion ? 
            $cotizacion->logoCotizacion->prendas->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'logo_cotizacion_id' => $prenda->logo_cotizacion_id,
                    'tipo_logo_id' => $prenda->tipo_logo_id,
                    'tipo_logo_nombre' => $prenda->tipoLogo ? $prenda->tipoLogo->nombre : null,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'observaciones' => $prenda->observaciones,
                    'ubicaciones' => $prenda->ubicaciones,
                    'talla_cantidad' => $prenda->talla_cantidad,
                    'grupo_combinado' => $prenda->grupo_combinado,
                    'fotos' => $prenda->fotos->map(fn($f) => [
                        'id' => $f->id,
                        'ruta_webp' => '/storage/' . ltrim($f->ruta_webp, '/'),
                        'ruta_original' => '/storage/' . ltrim($f->ruta_original, '/'),
                        'orden' => $f->orden
                    ])->toArray()
                ];
            })->toArray() 
            : []
    ]);
}
```

### PASO 6: Controlador `LogoPedidoTecnicaController.php`

**COPIAR COMPLETO** `app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php` y cambiar:

1. Nombre de clase y namespace
2. Modelos: `LogoCotizacion` → `LogoPedido`
3. URLs en logs: `logo-cotizacion` → `logo-pedido`
4. Rutas: Cambiar a `/api/logo-pedido-tecnicas/`

### PASO 7: Rutas (routes/web.php)

```php
// Rutas de API para Logo en Pedidos
Route::prefix('api')->group(function () {
    Route::prefix('logo-pedido-tecnicas')->group(function () {
        Route::get('/tipos-disponibles', [LogoPedidoTecnicaController::class, 'tiposDisponibles']);
        Route::post('/agregar-tecnica', [LogoPedidoTecnicaController::class, 'agregarTecnica']);
        Route::put('/tecnica-prenda/{id}', [LogoPedidoTecnicaController::class, 'editarTecnicaPrenda']);
        Route::delete('/tecnica-prenda/{id}', [LogoPedidoTecnicaController::class, 'eliminarTecnicaPrenda']);
        Route::get('/prendas', [LogoPedidoTecnicaController::class, 'obtenerPrendas']);
    });
});
```

---

## 3. INTEGRACIÓN EN CREAR-DESDE-COTIZACION-EDITABLE

Cuando detecte que es un pedido de LOGO (tipo 'L'):

1. **Cargar prendas técnicas** desde `obtenerDatosCotizacion()`
2. **Mostrar tabla** con prendas agregadas
3. **Botón "Agregar Técnica"** que abre el modal
4. **Al guardar pedido**, incluir las prendas técnicas en FormData

---

## 4. FLUJO USUARIO EN PEDIDO DE LOGO

```
1. Selecciona cotización tipo LOGO
   ↓
2. Se carga obtenerDatosCotizacion({cotizacionId})
   ↓
3. Renderiza tabla de prendas técnicas (si las hay)
   ↓
4. Usuario hace clic "Agregar Técnica/Prenda"
   ↓
5. Se abre modalAgregarTecnicaPedido
   ↓
6. Usuario:
   - Selecciona tipo de técnica (BORDADO, DTF, etc)
   - Agrega prenda (CAMISA, GORRO, etc)
   - Define ubicaciones (PECHO, ESPALDA, etc)
   - Define tallas y cantidades
   - Sube imágenes (máx 3)
   ↓
7. Hace clic "Guardar"
   ↓
8. POST /api/logo-pedido-tecnicas/agregar-tecnica
   ↓
9. Controlador crea LogoPedidoTecnicaPrenda + Fotos
   ↓
10. Vuelve a listaPrendasPedido
    ↓
11. Usuario repite paso 4-10 si quiere más prendas
    ↓
12. Hace clic "Crear Pedido"
    ↓
13. FormData incluye todas las prendas técnicas
    ↓
14. POST /pedidos-produccion/crear-logo
    ↓
15. Controlador crea LogoPedido + todas sus prendas
```

---

## 5. CHECKLISTA DE IMPLEMENTACIÓN

- [ ] Crear tablas en BD
- [ ] Crear modelos Eloquent (3 modelos)
- [ ] Crear LogoPedidoTecnicaController (copiar y adaptar)
- [ ] Agregar rutas en routes/web.php
- [ ] Agregar modal HTML en crear-desde-cotizacion-editable.blade.php
- [ ] Copiar y adaptar logo-cotizacion-tecnicas.js → logo-pedido-tecnicas.js
- [ ] Actualizar obtenerDatosCotizacion() para retornar prendas_tecnicas
- [ ] Actualizar createForm editable para detectar tipo LOGO
- [ ] Agregar botón "Agregar Técnica" en formulario
- [ ] Crear endpoint POST /pedidos-produccion/crear-logo (o adaptar existente)
- [ ] Pruebas end-to-end

---

## 6. NOTAS IMPORTANTES

1. **NO reinventar la rueda**: Copiar exactamente la estructura de cotización
2. **URLs de fotos**: `/storage/...` deben ser accesibles desde Blade
3. **TipoLogoCotizacion**: Reutilizar tabla existente, NO crear una nueva
4. **Validaciones**: Iguales a cotización (al menos 1 prenda, tallas con cantidad > 0)
5. **FormData**: Al enviar pedido, incluir como JSON stringificado

---

## 7. ARCHIVOS A MODIFICAR vs CREAR

### MODIFICAR
- [ ] routes/web.php
- [ ] app/Http/Controllers/Asesores/PedidosProduccionController.php (obtenerDatosCotizacion)
- [ ] resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php

### CREAR
- [ ] app/Models/LogoPedido.php
- [ ] app/Models/LogoPedidoTecnicaPrenda.php
- [ ] app/Models/LogoPedidoTecnicaPrendaFoto.php
- [ ] app/Http/Controllers/Asesores/LogoPedidoTecnicaController.php
- [ ] public/js/logo-pedido-tecnicas.js
- [ ] database/migrations/[fecha]_create_logo_pedidos_tables.php
