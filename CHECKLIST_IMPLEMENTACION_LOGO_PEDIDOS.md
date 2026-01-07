# CHECKLIST TÉCNICA: Implementación Logo en Pedidos

## FASE 1: BASE DE DATOS (Migrations)

### ✅ Crear Migration: logo_pedidos
```php
Schema::create('logo_pedidos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pedido_id')->constrained('pedidos_produccion')->onDelete('cascade');
    $table->string('tipo_venta')->nullable();
    $table->json('observaciones_generales')->nullable();
    $table->timestamps();
});
```

### ✅ Crear Migration: logo_pedido_tecnica_prendas
```php
Schema::create('logo_pedido_tecnica_prendas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('logo_pedido_id')->constrained('logo_pedidos')->onDelete('cascade');
    $table->foreignId('tipo_logo_id')->constrained('tipo_logo_cotizaciones');
    $table->string('nombre_prenda');
    $table->longText('observaciones')->nullable();
    $table->json('ubicaciones')->nullable();
    $table->json('talla_cantidad')->nullable();
    $table->bigInteger('grupo_combinado')->nullable();
    $table->timestamps();
});
```

### ✅ Crear Migration: logo_pedido_tecnica_prendas_fotos
```php
Schema::create('logo_pedido_tecnica_prendas_fotos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('logo_pedido_tecnica_prenda_id')
        ->constrained('logo_pedido_tecnica_prendas')->onDelete('cascade');
    $table->string('ruta_original');
    $table->string('ruta_webp');
    $table->string('ruta_miniatura')->nullable();
    $table->integer('orden')->default(0);
    $table->integer('ancho')->nullable();
    $table->integer('alto')->nullable();
    $table->integer('tamaño')->nullable();
    $table->timestamps();
});
```

---

## FASE 2: MODELOS ELOQUENT

### ✅ app/Models/LogoPedido.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoPedido extends Model
{
    protected $table = 'logo_pedidos';
    protected $fillable = ['pedido_id', 'tipo_venta', 'observaciones_generales'];
    protected $casts = ['observaciones_generales' => 'array'];

    public function prendas() {
        return $this->hasMany(LogoPedidoTecnicaPrenda::class);
    }
}
```

### ✅ app/Models/LogoPedidoTecnicaPrenda.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoPedidoTecnicaPrenda extends Model
{
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
```

### ✅ app/Models/LogoPedidoTecnicaPrendaFoto.php
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoPedidoTecnicaPrendaFoto extends Model
{
    protected $table = 'logo_pedido_tecnica_prendas_fotos';
    protected $fillable = ['logo_pedido_tecnica_prenda_id', 'ruta_original', 'ruta_webp',
                          'ruta_miniatura', 'orden', 'ancho', 'alto', 'tamaño'];
}
```

---

## FASE 3: CONTROLADOR

### ✅ Copiar archivo
```
app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php
        ↓
app/Http/Controllers/Asesores/LogoPedidoTecnicaController.php
```

### ✅ Cambios en LogoPedidoTecnicaController.php
```php
// Cambio 1: Nombre de clase
-class LogoCotizacionTecnicaController extends Controller {
+class LogoPedidoTecnicaController extends Controller {

// Cambio 2: Modelos
-$logoCotizacion = LogoCotizacion::findOrFail($request->input('logo_cotizacion_id'));
+$logoPedido = LogoPedido::findOrFail($request->input('logo_pedido_id'));

// Cambio 3: Crear prenda
-LogoCotizacionTecnicaPrenda::create([
-    'logo_cotizacion_id' => $logoCotizacion->id,
+LogoPedidoTecnicaPrenda::create([
+    'logo_pedido_id' => $logoPedido->id,
     ...
 ]);

// Cambio 4: Fotos
-LogoCotizacionTecnicaPrendaFoto::create([
-    'logo_cotizacion_tecnica_prenda_id' => $prenda->id,
+LogoPedidoTecnicaPrendaFoto::create([
+    'logo_pedido_tecnica_prenda_id' => $prenda->id,
     ...
 ]);

// Cambio 5: Logs
-Log::info('🔵 agregarTecnica() - Request FormData recibido', [
-    'logo_cotizacion_id' => $request->input('logo_cotizacion_id'),
+Log::info('🔵 agregarTecnica() - Request FormData recibido', [
+    'logo_pedido_id' => $request->input('logo_pedido_id'),
     ...
 ]);

// Cambio 6: Paths de almacenamiento
-'logo_cotizacion/' . $logoCotizacion->id,
+'logo_pedido/' . $logoPedido->id,
```

---

## FASE 4: JAVASCRIPT

### ✅ Copiar archivo
```
public/js/logo-cotizacion-tecnicas.js
        ↓
public/js/logo-pedido-tecnicas.js
```

### ✅ Cambios en logo-pedido-tecnicas.js (Find & Replace)

```javascript
// Buscar → Reemplazar
"logoCotizacionId" → "logoPedidoId"
"LogoCotizacion" → "LogoPedido"
"logoCotizacion" → "logoPedido"
"Cotizacion" → "Pedido"

"modalAgregarTecnica" → "modalAgregarTecnicaPedido"
"cerrarModalAgregarTecnica" → "cerrarModalAgregarTecnicaPedido"
"abrirModalAgregarTecnica" → "abrirModalAgregarTecnicaPedido"
"agregarFilaPrenda" → "agregarFilaPrendaPedido"
"guardarTecnica" → "guardarTecnicaPedido"
"listaPrendas" → "listaPrendasPedido"
"noPrendasMsg" → "noPrendasMsgPedido"
"tecnicasAgregadas" → "tecnicasAgregadasPedido"

"/api/logo-cotizacion-tecnicas/" → "/api/logo-pedido-tecnicas/"
"/cotizaciones-bordado/" → "/pedidos-produccion/"
"modalValidacionTecnica" → "modalValidacionTecnicaPedido"
```

---

## FASE 5: RUTAS

### ✅ Agregar en routes/web.php
```php
Route::prefix('api')->group(function () {
    Route::prefix('logo-pedido-tecnicas')->group(function () {
        Route::get('/tipos-disponibles', 
            [App\Http\Controllers\Asesores\LogoPedidoTecnicaController::class, 'tiposDisponibles']);
        Route::post('/agregar-tecnica', 
            [App\Http\Controllers\Asesores\LogoPedidoTecnicaController::class, 'agregarTecnica']);
        Route::put('/tecnica-prenda/{id}', 
            [App\Http\Controllers\Asesores\LogoPedidoTecnicaController::class, 'editarTecnicaPrenda']);
        Route::delete('/tecnica-prenda/{id}', 
            [App\Http\Controllers\Asesores\LogoPedidoTecnicaController::class, 'eliminarTecnicaPrenda']);
        Route::get('/prendas', 
            [App\Http\Controllers\Asesores\LogoPedidoTecnicaController::class, 'obtenerPrendas']);
    });
});
```

---

## FASE 6: VISTA HTML

### ✅ Modificar: crear-desde-cotizacion-editable.blade.php

**ANTES del cierre `@endsection`, agregar:**

```blade
<!-- MODAL PARA AGREGAR PRENDAS CON TÉCNICA SELECCIONADA -->
<div id="modalAgregarTecnicaPedido" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 24px; max-width: 650px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h2 style="margin: 0; font-size: 1.2rem; font-weight: 600; color: #333;">Agregar Técnica/Prenda</h2>
                <p style="margin: 8px 0 0 0; color: #666; font-size: 0.85rem;">Técnica: <strong id="tecnicaSeleccionadaNombrePedido" style="color: #333;">--</strong></p>
            </div>
            <button type="button" onclick="cerrarModalAgregarTecnicaPedido()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #ccc; padding: 0;">&times;</button>
        </div>
        
        <div id="listaPrendasPedido" style="margin-bottom: 16px;"></div>
        
        <div id="noPrendasMsgPedido" style="padding: 16px; text-align: center; background: #f9f9f9; border-radius: 4px; color: #999; margin-bottom: 16px; display: block; font-size: 0.9rem;">
            <p style="margin: 0;">Agrega prendas con el botón de abajo</p>
        </div>
        
        <button type="button" onclick="agregarFilaPrendaPedido()" style="width: 100%; background: #f0f0f0; color: #333; border: 1px solid #ddd; font-size: 0.9rem; cursor: pointer; padding: 10px 12px; border-radius: 4px; font-weight: 500; margin-bottom: 16px;">
            + Agregar prenda
        </button>
        
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

<script src="{{ asset('js/logo-pedido-tecnicas.js') }}"></script>
```

---

## FASE 7: ACTUALIZAR CONTROLADOR DE PEDIDOS

### ✅ Modificar: PedidosProduccionController.php - obtenerDatosCotizacion()

```php
// EN EL METHOD obtenerDatosCotizacion(), AGREGAR CARGAS:

'logoCotizacion.prendas',  // ← AGREGAR ESTA LÍNEA
'logoCotizacion.prendas.fotos',  // ← AGREGAR ESTA LÍNEA
'logoCotizacion.prendas.tipoLogo',  // ← AGREGAR ESTA LÍNEA

// EN EL RETURN JSON, AGREGAR:

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
```

---

## FASE 8: INTEGRACIÓN EN VISTA

### ✅ Agregar al formulario de pedidos

En la sección donde se renderizan las prendas/logo, agregar:

```blade
@if($currentEsLogo && !empty($logoPrendas))
    <div class="logo-prendas-section">
        <h3>Técnicas y Prendas del Logo</h3>
        <button type="button" onclick="abrirModalAgregarTecnicaPedido()">
            + Agregar Técnica/Prenda
        </button>
        <div id="logo-prendas-table">
            <!-- Se renderiza dinámicamente con JavaScript -->
        </div>
    </div>
@endif

<script>
    // Cargar prendas técnicas cuando se carga la cotización
    window.logoPrendas = <?php echo json_encode($logoPrendas ?? []); ?>;
    window.logoPedidoId = <?php echo json_encode($logoPedidoId ?? null); ?>;
    renderizarTablaPrendasTecnicas();
</script>
```

---

## ORDEN DE IMPLEMENTACIÓN

1. ✅ Crear migrations (DB)
2. ✅ Crear 3 modelos
3. ✅ Crear controlador (copiar y adaptar)
4. ✅ Crear JavaScript (copiar y adaptar)
5. ✅ Agregar rutas
6. ✅ Agregar modal HTML
7. ✅ Actualizar obtenerDatosCotizacion()
8. ✅ Integrar en vista
9. ✅ Probar

---

## VALIDACIÓN FINAL

```bash
# 1. Verificar tablas creadas
mysql> SHOW TABLES LIKE 'logo_pedido%';

# 2. Verificar modelos se cargan
php artisan tinker
> App\Models\LogoPedido::count()

# 3. Verificar rutas
php artisan route:list | grep logo-pedido

# 4. Verificar JavaScript en console
> window.logoPedidoId
> abrirModalAgregarTecnicaPedido()

# 5. Prueba end-to-end
- Ir a /pedidos-produccion/crear
- Seleccionar cotización de logo
- Verificar que se carguen prendas técnicas
- Hacer clic "Agregar Técnica"
- Completar modal y guardar
```
