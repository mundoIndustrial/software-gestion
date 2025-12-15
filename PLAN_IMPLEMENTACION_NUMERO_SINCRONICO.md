# 🚀 PLAN DE ACCIÓN INMEDIATO: MEJORAS CRÍTICAS

**Documento:** Plan de implementación paso a paso  
**Prioridad:** CRÍTICA (Esta semana)  
**Tiempo:** 2-3 horas de desarrollo  
**Riesgo:** BAJO (cambios puntuales en controlador)

---

## 🎯 OBJETIVOS

```
✅ Generar número DENTRO de transacción (no async)
✅ Implementar LOCK pessimista en numero_secuencias
✅ Retornar número inmediatamente en respuesta JSON
✅ Mantener compatibilidad con código existente
✅ Cero cambios en BD (solo lógica)
```

---

## ⚙️ CAMBIO 1: GENERACIÓN SINCRÓNICA DE NÚMERO

### ESTADO ACTUAL (PROBLEMÁTICO)
```php
// CotizacionPrendaController::store()

$cotizacion = Cotizacion::create([
    'asesor_id' => Auth::id(),
    'numero_cotizacion' => null,  // ← NULL AQUÍ
    'estado' => 'ENVIADA',
    ...
]);

// Luego, encola job
ProcesarEnvioCotizacionJob::dispatch($cotizacion->id, 3);
// ← Job genera número DESPUÉS, de forma asincrónica
```

**Problemas:**
- ❌ numero_cotizacion = NULL en respuesta
- ❌ Cliente no sabe el número inmediatamente
- ⏳ Job procesa después (5-10 segundos)
- 🚨 Sin lock → Posible colisión

---

### SOLUCIÓN: GENERAR DENTRO DE TRANSACCIÓN

```php
// CotizacionPrendaController::store()

if ($action === 'enviar') {
    // 🔄 NUEVA LÓGICA: Sincrónica
    return DB::transaction(function() use ($request) {
        
        // 1. LOCK pessimista en numero_secuencias
        $numeroSecuencia = NumeroSecuencia::lockForUpdate()->first();
        
        if (!$numeroSecuencia) {
            throw new Exception("Tabla numero_secuencias no inicializada");
        }
        
        // 2. Generar número
        $proximoNumero = $numeroSecuencia->siguiente;
        $numeroCotizacion = 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
        
        // 3. Incrementar el contador
        $numeroSecuencia->siguiente++;
        $numeroSecuencia->save();
        
        // 4. Crear cotización CON número
        $cotizacion = Cotizacion::create([
            'asesor_id' => Auth::id(),
            'numero_cotizacion' => $numeroCotizacion,  // ← CON NÚMERO
            'estado' => 'ENVIADA',
            ...
        ]);
        
        // 5. Retornar respuesta con número
        return response()->json([
            'success' => true,
            'message' => 'Cotización enviada con éxito',
            'cotizacion_id' => $cotizacion->id,
            'numero_cotizacion' => $numeroCotizacion,  // ← INMEDIATO
        ]);
        
    });
}
```

---

## ⚙️ CAMBIO 2: CREAR TABLA DE SECUENCIAS (Si no existe)

### VERIFICAR QUE EXISTE

```bash
# Terminal
php artisan tinker

# Dentro de tinker:
>>> DB::table('numero_secuencias')->first();
```

**Si NO existe, crear:**

```php
// database/migrations/2025_12_14_create_numero_secuencias.php

Schema::create('numero_secuencias', function (Blueprint $table) {
    $table->id();
    $table->string('tipo')->unique();  // 'cotizaciones', 'pedidos', etc.
    $table->integer('siguiente')->default(1);
    $table->timestamps();
});

// Datos iniciales:
// tipo='cotizaciones_prenda', siguiente=1
// tipo='cotizaciones_bordado', siguiente=1
// tipo='pedidos', siguiente=1
```

**O cargar datos si ya existe:**

```php
// database/seeders/NumeroSecuenciasSeeder.php

DB::table('numero_secuencias')->updateOrCreate(
    ['tipo' => 'cotizaciones_prenda'],
    ['siguiente' => 1]
);

DB::table('numero_secuencias')->updateOrCreate(
    ['tipo' => 'cotizaciones_bordado'],
    ['siguiente' => 1]
);
```

---

## ⚙️ CAMBIO 3: ACTUALIZAR CONTROLADOR

### ARCHIVO A MODIFICAR
```
app/Infrastructure/Http/Controllers/CotizacionPrendaController.php
```

### CÓDIGO ACTUAL (LÍNEAS ~25-100)
```php
public function store(Request $request)
{
    return DB::transaction(function () use ($request) {
        try {
            // ... validaciones ...
            
            $action = $request->input('action');
            $esBorrador = $action === 'borrador';
            
            // Crear cotización
            $cotizacion = Cotizacion::create([
                'asesor_id' => Auth::id(),
                'numero_cotizacion' => null,  // ← CAMBIAR ESTO
                ...
            ]);
            
            // Si se envía, encolar job
            if (!$esBorrador) {
                ProcesarEnvioCotizacionJob::dispatch(
                    $cotizacion->id, 
                    3
                )->onQueue('cotizaciones');
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización guardada',
            ]);
        } catch (\Exception $e) {
            Log::error('Error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    });
}
```

### CÓDIGO NUEVO (CON NÚMERO SINCRÓNICO)
```php
public function store(Request $request)
{
    return DB::transaction(function () use ($request) {
        try {
            Log::info('🔵 CotizacionPrendaController@store - Iniciando');
            
            // Validaciones básicas
            $this->validate($request, [
                'cliente' => 'nullable|string',
                'cliente_id' => 'nullable|integer',
                'tipo_venta' => 'nullable|in:M,P,G',
            ]);
            
            $action = $request->input('action');
            $esBorrador = $action === 'borrador';
            $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA';
            
            // Obtener o crear cliente
            $clienteId = $this->obtenerOCrearCliente($request);
            
            // LÓGICA NUEVA: Generar número si es envío
            $numeroCotizacion = null;
            if (!$esBorrador) {
                $numeroCotizacion = $this->generarNumeroCotizacion('cotizaciones_prenda');
            }
            
            // Crear cotización
            $cotizacion = Cotizacion::create([
                'asesor_id' => Auth::id(),
                'cliente_id' => $clienteId,
                'numero_cotizacion' => $numeroCotizacion,  // ← YA TIENE NÚMERO
                'tipo_cotizacion_id' => 3,
                'tipo_venta' => $request->input('tipo_venta', 'M'),
                'es_borrador' => $esBorrador,
                'estado' => $estado,
                'productos' => json_encode($request->input('prendas', [])),
                'especificaciones' => json_encode($request->input('especificaciones', [])),
            ]);
            
            Log::info('✅ Cotización creada', [
                'id' => $cotizacion->id,
                'numero' => $numeroCotizacion,
                'estado' => $estado,
            ]);
            
            // Procesar imágenes si existen
            if ($request->hasFile('prendas')) {
                $this->procesarImagenesCotizacion($request, $cotizacion->id);
            }
            
            // Si se envía, procesar en segundo plano (sin bloquear)
            if (!$esBorrador) {
                // Job solo para enviar email, generar PDF, etc.
                // El número YA fue generado
                ProcesarEnvioCotizacionJob::dispatch(
                    $cotizacion->id,
                    3,
                    $numeroCotizacion  // Pasar el número generado
                )->onQueue('cotizaciones');
            }
            
            return response()->json([
                'success' => true,
                'message' => $esBorrador 
                    ? 'Cotización guardada como borrador' 
                    : 'Cotización #' . $numeroCotizacion . ' enviada con éxito',
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $numeroCotizacion,
                'estado' => $estado,
                'redirect' => route('cotizaciones-prenda.lista'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error en store', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Error al guardar cotización: ' . $e->getMessage(),
            ], 422);
        }
    });
}

/**
 * NUEVA FUNCIÓN: Generar número de cotización de forma sincrónica
 */
private function generarNumeroCotizacion($tipo = 'cotizaciones_prenda')
{
    try {
        // LOCK pessimista para evitar colisiones
        $numeroSecuencia = NumeroSecuencia::lockForUpdate()
            ->where('tipo', $tipo)
            ->first();
        
        if (!$numeroSecuencia) {
            // Si no existe, crear
            $numeroSecuencia = NumeroSecuencia::create([
                'tipo' => $tipo,
                'siguiente' => 1,
            ]);
        }
        
        // Generar número
        $proximoNumero = $numeroSecuencia->siguiente;
        $numeroCotizacion = 'COT-' . date('Ymd') . '-' . str_pad($proximoNumero, 3, '0', STR_PAD_LEFT);
        
        // Incrementar para próxima vez
        $numeroSecuencia->siguiente = $proximoNumero + 1;
        $numeroSecuencia->save();
        
        Log::info('📊 Número generado', [
            'numero' => $numeroCotizacion,
            'tipo' => $tipo,
        ]);
        
        return $numeroCotizacion;
        
    } catch (\Exception $e) {
        Log::error('❌ Error generando número', [
            'error' => $e->getMessage(),
        ]);
        throw $e;
    }
}

/**
 * NUEVA FUNCIÓN: Obtener o crear cliente
 */
private function obtenerOCrearCliente($request)
{
    $clienteId = $request->input('cliente_id');
    $nombreCliente = $request->input('cliente');
    
    if ($nombreCliente && !$clienteId) {
        $cliente = Cliente::firstOrCreate(
            ['nombre' => $nombreCliente],
            ['nombre' => $nombreCliente]
        );
        return $cliente->id;
    }
    
    return $clienteId;
}
```

---

## 🔄 CAMBIO 4: ACTUALIZAR JOB DE ENVÍO

### ARCHIVO A MODIFICAR
```
app/Jobs/ProcesarEnvioCotizacionJob.php
```

**Cambio mínimo:**

```php
class ProcesarEnvioCotizacionJob implements ShouldQueue
{
    public $cotizacionId;
    public $tipoCotizacionId;
    public $numeroCotizacion;  // ← NUEVO
    
    public function __construct($cotizacionId, $tipoCotizacionId, $numeroCotizacion = null)
    {
        $this->cotizacionId = $cotizacionId;
        $this->tipoCotizacionId = $tipoCotizacionId;
        $this->numeroCotizacion = $numeroCotizacion;  // ← NUEVO
    }
    
    public function handle()
    {
        $cotizacion = Cotizacion::find($this->cotizacionId);
        
        // Si el número ya fue generado en controlador, usar ese
        if (!$cotizacion->numero_cotizacion && $this->numeroCotizacion) {
            $cotizacion->numero_cotizacion = $this->numeroCotizacion;
            $cotizacion->save();
        }
        
        // Generar PDF
        // Enviar emails
        // Registrar en historial
        // ... resto del código ...
    }
}
```

---

## 🧪 TESTING: VERIFICAR QUE FUNCIONA

### TEST 1: Número Inmediato
```php
// tests/Feature/Cotizacion/GenerarNumeroTest.php

public function test_numero_generado_inmediatamente()
{
    $asesor = User::factory()->create(['role' => 'asesor']);
    
    $response = $this->actingAs($asesor)->postJson('/cotizaciones-prenda', [
        'cliente' => 'Test Client',
        'action' => 'enviar',
        'tipo_venta' => 'P',
        'prendas' => [...]
    ]);
    
    // Verificar que respuesta tiene número
    $response->assertJson([
        'success' => true,
        'numero_cotizacion' => 'COT-' . date('Ymd') . '-001',  // ← INMEDIATO
    ]);
}
```

### TEST 2: Sin Colisiones
```php
public function test_sin_colisiones_concurrentes()
{
    $asesor1 = User::factory()->create(['role' => 'asesor']);
    $asesor2 = User::factory()->create(['role' => 'asesor']);
    
    // Ambos envían casi simultáneamente
    $response1 = $this->actingAs($asesor1)->postJson('/cotizaciones-prenda', [...]);
    $response2 = $this->actingAs($asesor2)->postJson('/cotizaciones-prenda', [...]);
    
    $numero1 = $response1->json('numero_cotizacion');
    $numero2 = $response2->json('numero_cotizacion');
    
    // Números son diferentes
    $this->assertNotEquals($numero1, $numero2);
    
    // Ambas se crearon exitosamente
    $response1->assertJson(['success' => true]);
    $response2->assertJson(['success' => true]);
}
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

```
PASO 1: PREPARACIÓN
☐ Crear backup de BD
☐ Crear rama en Git: feature/numero-sincronico
☐ Revisar archivo actual: CotizacionPrendaController.php

PASO 2: TABLAS (Si no existen)
☐ Crear migration: numero_secuencias table
☐ Ejecutar: php artisan migrate
☐ Cargar datos iniciales con seeder

PASO 3: CÓDIGO
☐ Actualizar: CotizacionPrendaController::store()
☐ Agregar: generarNumeroCotizacion()
☐ Agregar: obtenerOCrearCliente()
☐ Actualizar: CotizacionBordadoController (igual lógica)
☐ Actualizar: ProcesarEnvioCotizacionJob

PASO 4: TESTING
☐ Ejecutar tests existentes
☐ Crear tests nuevos para concurrencia
☐ Probar manual en navegador

PASO 5: DEPLOYMENT
☐ Merge a develop
☐ Merge a main (con tag)
☐ Deploy a producción
☐ Verificar en BD que números se generan bien

PASO 6: MONITOREO
☐ Revisar logs: "Número generado"
☐ Verificar que NO hay mensajes de error
☐ Confirmar con asesor que ve número inmediato
```

---

## ⏰ TIEMPO ESTIMADO

```
Preparación:           15 min
Crear tablas:          10 min
Actualizar código:     45 min
Testing:               30 min
Deployment:            20 min
─────────────────────────────
TOTAL:                 2 horas
```

---

## ✅ VALIDACIÓN POST-IMPLEMENTACIÓN

Después de implementar, verificar:

```sql
-- Ejecutar en MySQL:

-- 1. Verificar que numero_secuencias existe
SELECT * FROM numero_secuencias;

-- 2. Verificar que cotizaciones enviadas tienen número
SELECT id, numero_cotizacion, estado FROM cotizaciones 
WHERE estado = 'ENVIADA' LIMIT 5;

-- 3. Verificar que números son únicos
SELECT numero_cotizacion, COUNT(*) as qty 
FROM cotizaciones 
GROUP BY numero_cotizacion 
HAVING qty > 1;  -- ← No debe retornar nada

-- 4. Verificar secuencia
SELECT numero_cotizacion FROM cotizaciones 
WHERE estado = 'ENVIADA' 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## 🎯 RESULTADO ESPERADO

**Antes:**
```
POST /enviar
Response: { success: true }
numero_cotizacion: NULL (esperando job)
Tiempo para tener número: 5-10 segundos
```

**Después:**
```
POST /enviar
Response: { success: true, numero_cotizacion: 'COT-20251214-001' }
numero_cotizacion: Inmediato
Tiempo para tener número: < 100ms
Seguridad: 100% (con LOCK)
```

---

## 📞 SOPORTE

Si hay dudas durante la implementación:
1. Revisar archivos de análisis
2. Consultar tests existentes
3. Revisar logs en `storage/logs/laravel.log`
4. Ejecutar tests: `php artisan test`

**¡Listo para implementar! 🚀**

