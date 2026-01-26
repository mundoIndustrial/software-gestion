# 🔍 AUDITORÍA COMPLETA: PÉRDIDA DE DATOS EN PROCESOS, TALLAS E IMÁGENES

**Fecha:** 26 Enero 2026  
**Estado:** 🚨 CRÍTICO - DATOS NO SE GUARDAN  
**Responsabilidad:** Flujo Frontend → Command → Handler → DB

---

## 📌 RESUMEN EJECUTIVO

El flujo **SÍ CREA** el pedido y la prenda base, pero **NO PERSISTE**:
-  `ubicaciones` (JSON en `pedidos_procesos_prenda_detalles`)
-  `observaciones` (TEXT en `pedidos_procesos_prenda_detalles`)
-  `datos_adicionales` (JSON en `pedidos_procesos_prenda_detalles`)
-  Tallas en `pedidos_procesos_prenda_tallas` (tabla relacional)
-  Imágenes en `prenda_fotos_pedido` y `prenda_fotos_tela_pedido`

### 🎯 Raíz del Problema

El **GeneradorPedidoCompleto** (o función equivalente en tu controlador) **NO ENVÍA LOS PROCESOS** en el payload. El builder `PedidoCompletoUnificado.js` está preparado, pero:
1. No se llama `agregarPrenda()` con procesos
2. O los procesos llegan vacíos `{}`
3. O se pierden entre frontend y backend

---

## 🔗 FLUJO COMPLETO DE DATOS

### PROBLEMA IDENTIFICADO

```
┌─ FRONTEND
│  ├─ [ FALLAN] Procesos NO se cargan en UI
│  ├─ [ FALLAN] PedidoCompletoUnificado.agregarPrenda() recibe procesos: {}
│  └─ [ FALLAN] Payload enviado: procesos vacío
│
├─ BACKEND - HTTP POST
│  ├─ CrearPedidoCompletoRequest::validate() PASA
│  ├─ CrearPedidoEditableController::crearPedido() CREA COMMAND
│  └─ Payload en Command: items[].procesos = {}  VACÍO
│
├─ COMMAND BUS
│  ├─ CrearPedidoCompletoCommand recibe items[]
│  ├─ AgregarPrendaAlPedidoCommand recibe prendaData (sin procesos o vacío)
│  └─ Command NO tiene validación de procesos
│
└─ PERSISTENCIA
   ├─ PedidoPrendaService.guardarProcesosPrenda() se llama
   ├─ PrendaProcesoService.guardarProcesosPrenda() se llama
   ├─ Pero $procesos array está VACÍO 
   └─ Resultado: Registros NO se crean en BD
```

---

## VERIFICACIÓN: LO QUE SÍ FUNCIONA

### 1. **Backend: Modelos con $casts correcto**

```php
// app/Models/PedidosProcesosPrendaDetalle.php
protected $fillable = [
    'prenda_pedido_id',
    'tipo_proceso_id',
    'ubicaciones',          // JSON
    'observaciones',        // TEXT
    'tallas_dama',         // JSON (legacy)
    'tallas_caballero',    // JSON (legacy)
    'datos_adicionales',   // JSON
    'estado',
];

protected $casts = [
    'ubicaciones' => 'array',           // CAST CORRECTO
    'tallas_dama' => 'array',
    'tallas_caballero' => 'array',
    'datos_adicionales' => 'array',
    'fecha_aprobacion' => 'datetime',
];
```

### 2. **Backend: PrendaProcesoService ESTÁ LISTO**

```php
// app/Domain/Pedidos/Services/PrendaProcesoService.php
public function guardarProcesosPrenda(int $prendaId, int $pedidoId, array $procesos): void
{
    Log::info('[PrendaProcesoService::guardarProcesosPrenda] Guardando procesos', [
        'prenda_id' => $prendaId,
        'cantidad_procesos' => count($procesos),  //  Si es 0, nada se guarda
    ]);

    foreach ($procesos as $procesoIndex => $proceso) {
        // Código que SERÍA correcto si $procesos no estuviera vacío
        $procesoDetalleId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
            'prenda_pedido_id' => $prendaId,
            'tipo_proceso_id' => $tipoProcesoId,
            'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
            'observaciones' => $proceso['observaciones'] ?? null,
            'tallas_dama' => !empty($proceso['tallas']['dama']) ? json_encode($proceso['tallas']['dama']) : null,
            'tallas_caballero' => !empty($proceso['tallas']['caballero']) ? json_encode($proceso['tallas']['caballero']) : null,
        ]);
    }
}
```

### 3. **Frontend: PedidoCompletoUnificado ESTÁ LISTO**

```javascript
// public/js/pedidos-produccion/PedidoCompletoUnificado.js
_sanitizarProcesos(raw) {
    // SI raw tiene procesos, los limpia correctamente
    const cleaned = {};
    const tiposProceso = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];
    
    tiposProceso.forEach(tipo => {
        if (raw[tipo]) {  //  AQUÍ: raw[tipo] es undefined o {}
            const datos = raw[tipo].datos || raw[tipo];
            cleaned[tipo] = {
                tipo: tipo,
                datos: this._sanitizarDatosProceso(datos, tipo)
            };
        }
    });
    return cleaned;  //  Retorna {} vacío
}
```

---

## 🎯 SOLUCIONES POR CAPAS

### 1️⃣ FRONTEND - PedidoCompletoUnificado.js

**PROBLEMA:** Los procesos NO se agregan cuando se llama `agregarPrenda()`

**CULPA:** La función que llama al builder NO está pasando procesos

```javascript
//  ACTUAL (incorrecto)
window.crearPedidoConBuilderUnificado = async function() {
    const prendas = gestor.obtenerTodas(); // ← Procesos NO están aquí
    
    prendas.forEach(prenda => {
        builder.agregarPrenda({
            nombre_prenda: prenda.nombre,
            cantidad_talla: prenda.cantidades,
            //  NO VIENE: procesos
            //  NO VIENE: telas
            //  NO VIENE: imagenes
        });
    });
};
```

**✅ SOLUCIÓN 1: Verificar que el gestor tiene procesos**

```javascript
// En tu gestor (GestorPedidoSinCotizacion.js o equivalente)
window.GestorPedidoSinCotizacion = class {
    // ... constructor ...
    
    obtenerTodas() {
        // Incluir procesos en el retorno
        return this.prendas.map(p => ({
            id: p.id,
            nombre: p.nombre,
            cantidades: p.cantidades,
            procesos: p.procesos || {},        // ← AGREGAR
            telas: p.telas || [],               // ← AGREGAR
            imagenes: p.imagenes || [],         // ← AGREGAR
            variaciones: p.variaciones || {},   // ← AGREGAR
        }));
    }
    
    // Método para agregar proceso a una prenda
    agregarProceso(prendaId, procesoData) {
        const prenda = this.prendas.find(p => p.id === prendaId);
        if (!prenda) throw new Error(`Prenda ${prendaId} no encontrada`);
        
        const tipoProc = procesoData.tipo || 'reflectivo';
        prenda.procesos = prenda.procesos || {};
        
        prenda.procesos[tipoProc] = {
            tipo: tipoProc,
            datos: {
                tipo: tipoProc,
                ubicaciones: procesoData.ubicaciones || [],
                observaciones: procesoData.observaciones || '',
                tallas: procesoData.tallas || { dama: {}, caballero: {} },
                imagenes: procesoData.imagenes || []
            }
        };
        
        Log.info(`✅ Proceso ${tipoProc} agregado a prenda ${prendaId}`);
    }
};
```

**✅ SOLUCIÓN 2: Actualizar la función que construye el pedido**

```javascript
window.crearPedidoConBuilderUnificado = async function() {
    try {
        console.log('[Builder] Iniciando creación de pedido unificado');
        
        const gestor = window.gestorPedidoSinCotizacion;
        if (!gestor) throw new Error('Gestor no inicializado');
        
        const prendas = gestor.obtenerTodas();  // ← Ahora incluye procesos
        if (prendas.length === 0) throw new Error('No hay prendas agregadas');
        
        const cliente = document.getElementById('cliente_editable')?.value;
        const asesora = document.getElementById('asesora_editable')?.value;
        const formaPago = document.getElementById('forma_de_pago_editable')?.value;
        
        if (!cliente) throw new Error('Cliente es requerido');
        
        const builder = new PedidoCompletoUnificado();
        
        builder
            .setCliente(cliente)
            .setAsesora(asesora || '')
            .setFormaPago(formaPago || 'CONTADO');
        
        // AGREGAR CADA PRENDA CON TODOS SUS DATOS
        prendas.forEach(prenda => {
            console.log('[Builder] Agregando prenda:', {
                nombre: prenda.nombre,
                procesos_count: Object.keys(prenda.procesos || {}).length,
                telas_count: (prenda.telas || []).length,
                imagenes_count: (prenda.imagenes || []).length,
            });
            
            builder.agregarPrenda({
                tipo: prenda.tipo || 'prenda_nueva',
                nombre_prenda: prenda.nombre,
                descripcion: prenda.descripcion,
                origen: prenda.origen || 'bodega',
                de_bodega: prenda.de_bodega ? 1 : 0,
                cantidad_talla: prenda.cantidades || {},
                variaciones: prenda.variaciones || {},
                telas: prenda.telas || [],          // AGREGAR
                imagenes: prenda.imagenes || [],    // AGREGAR
                procesos: prenda.procesos || {}     // AGREGAR - CRÍTICO
            });
        });
        
        builder.validate();
        const payloadLimpio = builder.build();
        
        console.log('[Builder] Payload final construido:', {
            cliente: payloadLimpio.cliente,
            items: payloadLimpio.items.length,
            procesos_totales: payloadLimpio.items.reduce((sum, item) => 
                sum + Object.keys(item.procesos || {}).length, 0
            ),
            telas_totales: payloadLimpio.items.reduce((sum, item) => 
                sum + (item.telas || []).length, 0
            ),
            imagenes_totales: payloadLimpio.items.reduce((sum, item) => 
                sum + (item.imagenes || []).length, 0
            ),
        });
        
        // ENVIAR AL SERVIDOR
        const response = await fetch('/asesores/pedidos-editable/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payloadLimpio)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error al crear pedido');
        }
        
        console.log('✅ Pedido creado exitosamente:', data);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Pedido creado correctamente',
                confirmButtonColor: '#10b981'
            }).then(() => {
                if (data.pedido_id) {
                    window.location.href = `/asesores/pedidos/${data.pedido_id}/editar`;
                }
            });
        }
        
    } catch (error) {
        console.error(' Error:', error.message);
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message,
                confirmButtonColor: '#ef4444'
            });
        }
    }
};
```

---

### 2️⃣ BACKEND - Logging de Rastreo

**PROBLEMA:** No sabemos si los datos llegan o se pierden en el camino

**✅ SOLUCIÓN: Agregar logs en puntos críticos**

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

```php
public function crearPedido(CrearPedidoCompletoRequest $request): JsonResponse
{
    try {
        $validated = $request->validated();

        //  LOG 1: Ver qué datos llegaron del frontend
        Log::info('[CrearPedidoEditableController] Datos validados del frontend', [
            'cliente' => $validated['cliente'],
            'items_count' => count($validated['items'] ?? []),
            'primer_item_procesos' => isset($validated['items'][0]['procesos']) 
                ? array_keys($validated['items'][0]['procesos']) 
                : 'NO EXISTE',
            'primer_item_telas' => isset($validated['items'][0]['telas'])
                ? count($validated['items'][0]['telas'])
                : 'NO EXISTE',
            'primer_item_imagenes' => isset($validated['items'][0]['imagenes'])
                ? count($validated['items'][0]['imagenes'])
                : 'NO EXISTE',
        ]);

        // Obtener o crear cliente
        $clienteNombre = trim($request->input('cliente'));
        $cliente = $this->obtenerOCrearCliente($clienteNombre);

        //  LOG 2: Antes de crear el command
        Log::info('[CrearPedidoEditableController] Creando Command con items', [
            'items_count' => count($validated['items']),
            'items_debug' => collect($validated['items'])->map(function($item, $idx) {
                return [
                    'índice' => $idx,
                    'nombre' => $item['nombre_prenda'] ?? 'SIN NOMBRE',
                    'tiene_procesos' => !empty($item['procesos']),
                    'procesos_tipos' => $item['procesos'] ? array_keys($item['procesos']) : [],
                    'tiene_telas' => !empty($item['telas']),
                    'tiene_imagenes' => !empty($item['imagenes']),
                ];
            })->toArray(),
        ]);

        // Crear command
        $command = new CrearPedidoCompletoCommand(
            cliente: $cliente->id,
            formaPago: $validated['forma_de_pago'] ?? 'CONTADO',
            asesorId: \Illuminate\Support\Facades\Auth::id(),
            items: $validated['items'],  // Aquí van los procesos
            descripcion: $validated['descripcion'] ?? null,
        );

        //  LOG 3: Después de crear el command
        Log::info('[CrearPedidoEditableController] Command CrearPedidoCompletoCommand creado', [
            'command_items_count' => count($command->getItems()),
            'primer_item_procesos' => count($command->getItems()[0]['procesos'] ?? []) > 0 
                ? 'SÍ TIENE' 
                : 'NO TIENE',
        ]);

        // Ejecutar handler
        $pedido = app(\App\Domain\Shared\CQRS\CommandBus::class)->execute($command);

        //  LOG 4: Pedido creado, pero ¿se guardaron los procesos?
        Log::info('[CrearPedidoEditableController] Pedido creado - Verificando procesos', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'prendas_creadas' => count($pedido->prendas),
            'procesos_totales_en_db' => \DB::table('pedidos_procesos_prenda_detalles')
                ->whereIn('prenda_pedido_id', $pedido->prendas->pluck('id'))
                ->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
        
    } catch (\Exception $e) {
        Log::error('[CrearPedidoEditableController] Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
    }
}
```

**Archivo:** `app/Application/Services/PedidoPrendaService.php`

```php
private function guardarProcesosPrenda(PrendaPedido $prenda, array $procesos): void
{
    //  LOG 1: Procesos que se intentan guardar
    Log::info('[PedidoPrendaService::guardarProcesosPrenda] INICIO', [
        'prenda_id' => $prenda->id,
        'procesos_count' => count($procesos),
        'procesos_tipos' => array_keys($procesos),
        'procesos_completo' => $procesos,  // ← DEBUG: Ver estructura completa
    ]);
    
    if (empty($procesos)) {
        Log::warning('[PedidoPrendaService::guardarProcesosPrenda] SIN PROCESOS', [
            'prenda_id' => $prenda->id,
        ]);
        return;
    }

    // Normalizar y guardar
    $this->prendaProcesoService->guardarProcesosPrenda(
        $prenda->id,
        $prenda->pedido_produccion_id,
        $procesos
    );
    
    //  LOG 2: Verificar que se guardaron
    $procesosGuardados = \DB::table('pedidos_procesos_prenda_detalles')
        ->where('prenda_pedido_id', $prenda->id)
        ->get();
    
    Log::info('[PedidoPrendaService::guardarProcesosPrenda] GUARDADOS EN BD', [
        'prenda_id' => $prenda->id,
        'procesos_guardados' => count($procesosGuardados),
        'detalles' => $procesosGuardados->map(function($p) {
            return [
                'id' => $p->id,
                'tipo_proceso_id' => $p->tipo_proceso_id,
                'ubicaciones' => $p->ubicaciones,
                'observaciones' => $p->observaciones,
                'tallas_dama' => $p->tallas_dama,
                'tallas_caballero' => $p->tallas_caballero,
            ];
        })->toArray(),
    ]);
}
```

**Archivo:** `app/Domain/Pedidos/Services/PrendaProcesoService.php`

```php
public function guardarProcesosPrenda(int $prendaId, int $pedidoId, array $procesos): void
{
    Log::info('[PrendaProcesoService::guardarProcesosPrenda] INICIO', [
        'prenda_id' => $prendaId,
        'pedido_id' => $pedidoId,
        'procesos_count' => count($procesos),
        'estructura_procesos' => array_map(function($p) {
            return [
                'tipo' => $p['tipo'] ?? 'SIN TIPO',
                'tiene_ubicaciones' => !empty($p['ubicaciones']),
                'ubicaciones' => $p['ubicaciones'] ?? [],
                'tiene_observaciones' => !empty($p['observaciones']),
                'observaciones' => $p['observaciones'] ?? '',
                'tiene_tallas' => !empty($p['tallas']),
                'tallas' => $p['tallas'] ?? {},
                'tiene_imagenes' => !empty($p['imagenes']),
                'imagenes_count' => count($p['imagenes'] ?? []),
            ];
        }, $procesos),
    ]);

    foreach ($procesos as $procesoIndex => $proceso) {
        try {
            Log::debug('[PrendaProcesoService] Procesando item', [
                'index' => $procesoIndex,
                'tipo' => $proceso['tipo'] ?? 'DESCONOCIDO',
                'ubicaciones' => $proceso['ubicaciones'] ?? [],
                'observaciones' => $proceso['observaciones'] ?? '',
            ]);
            
            // ... resto del código de guardar ...
            
        } catch (\Exception $e) {
            Log::error('[PrendaProcesoService] Error en proceso', [
                'prenda_id' => $prendaId,
                'proceso_index' => $procesoIndex,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

### 3️⃣ BACKEND - Validación en FormRequest

**PROBLEMA:** CrearPedidoCompletoRequest NO valida procesos

**✅ SOLUCIÓN: Agregar validación de procesos**

```php
// app/Http/Requests/CrearPedidoCompletoRequest.php

public function rules(): array
{
    return [
        'cliente' => 'required|string|max:255',
        'forma_de_pago' => 'required|string|in:CONTADO,CREDITO,TRANSFERENCIA',
        'descripcion' => 'nullable|string',
        
        // Items (prendas)
        'items' => 'required|array|min:1',
        'items.*.nombre_prenda' => 'required|string|max:255',
        'items.*.descripcion' => 'nullable|string',
        'items.*.cantidad_talla' => 'required|array',
        'items.*.cantidad_talla.*.*' => 'integer|min:0',
        
        // Variaciones
        'items.*.variaciones' => 'nullable|array',
        'items.*.variaciones.tipo_manga' => 'nullable|string',
        'items.*.variaciones.obs_manga' => 'nullable|string',
        
        // Telas
        'items.*.telas' => 'nullable|array',
        'items.*.telas.*.tela' => 'nullable|string',
        'items.*.telas.*.color' => 'nullable|string',
        'items.*.telas.*.imagenes' => 'nullable|array',
        
        // Imágenes
        'items.*.imagenes' => 'nullable|array',
        
        // AGREGAR: Procesos
        'items.*.procesos' => 'nullable|array',
        'items.*.procesos.*' => 'nullable|array',
        'items.*.procesos.*.tipo' => 'nullable|string|in:reflectivo,bordado,estampado,dtf,sublimado',
        'items.*.procesos.*.datos.ubicaciones' => 'nullable|array',
        'items.*.procesos.*.datos.observaciones' => 'nullable|string',
        'items.*.procesos.*.datos.tallas' => 'nullable|array',
        'items.*.procesos.*.datos.imagenes' => 'nullable|array',
    ];
}
```

---

## 🧪 VERIFICACIÓN POST-CORRECCIÓN

### Checklist de Validación

```bash
# 1. Crear un pedido con procesos
curl -X POST http://localhost/asesores/pedidos-editable/crear \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(token)" \
  -d '{
    "cliente": "TEST CLIENT",
    "forma_de_pago": "CONTADO",
    "items": [{
      "nombre_prenda": "CAMISA",
      "cantidad_talla": {"DAMA": {"S": 10, "M": 20}},
      "procesos": {
        "reflectivo": {
          "tipo": "reflectivo",
          "datos": {
            "ubicaciones": ["Manga izq", "Manga der"],
            "observaciones": "Tiras de 5cm",
            "tallas": {"dama": {"S": 10, "M": 20}, "caballero": {}},
            "imagenes": []
          }
        }
      }
    }]
  }'

# 2. Verificar logs
tail -f storage/logs/laravel.log | grep "PrendaProcesoService"

# 3. Consultar base de datos
SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = 1;
```

### Query SQL para Validar

```sql
-- Procesos guardados
SELECT 
    p.id as prenda_id,
    p.nombre_prenda,
    proc.id as proceso_id,
    tp.nombre as tipo_proceso,
    proc.ubicaciones,
    proc.observaciones,
    COUNT(pt.id) as tallas_count
FROM prendas_pedido p
LEFT JOIN pedidos_procesos_prenda_detalles proc ON proc.prenda_pedido_id = p.id
LEFT JOIN tipos_procesos tp ON tp.id = proc.tipo_proceso_id
LEFT JOIN pedidos_procesos_prenda_tallas pt ON pt.proceso_prenda_detalle_id = proc.id
WHERE p.pedido_produccion_id = 123
GROUP BY p.id, proc.id;

-- Imágenes de procesos
SELECT 
    p.id as prenda_id,
    proc.id as proceso_id,
    img.id as imagen_id,
    img.ruta_archivo
FROM prendas_pedido p
LEFT JOIN pedidos_procesos_prenda_detalles proc ON proc.prenda_pedido_id = p.id
LEFT JOIN pedidos_procesos_imagenes img ON img.proceso_prenda_detalle_id = proc.id
WHERE p.pedido_produccion_id = 123;
```

---

## 📋 RESUMEN DE CAMBIOS NECESARIOS

| Capa | Archivo | Cambio | Prioridad |
|------|---------|--------|-----------|
| **Frontend** | `GestorPedidoSinCotizacion.js` | Incluir procesos, telas, imagenes en `obtenerTodas()` | 🔴 CRÍTICO |
| **Frontend** | `inicializador-pedido-completo.js` | Actualizar `crearPedidoConBuilderUnificado()` para pasar procesos | 🔴 CRÍTICO |
| **Backend** | `CrearPedidoEditableController.php` | Agregar logs en `crearPedido()` | 🟡 IMPORTANTE |
| **Backend** | `CrearPedidoCompletoRequest.php` | Agregar validación de procesos | 🟡 IMPORTANTE |
| **Backend** | `PedidoPrendaService.php` | Agregar logs en `guardarProcesosPrenda()` | 🟡 IMPORTANTE |
| **Backend** | `PrendaProcesoService.php` | Agregar logs detallados | 🟡 IMPORTANTE |

---

## 🎯 PRÓXIMOS PASOS

1. **Hoy (Prioridad 1):** Implementar cambios Frontend + Logging Backend
2. **Mañana (Prioridad 2):** Validar con base de datos + Ajustar según logs
3. **Día 3:** Implementar validación en FormRequest
4. **Día 4:** Testing de extremo a extremo

---

## 📞 CONTACTO PARA DUDAS

Si persisten problemas después de estos cambios, revisar:
1. Logs en `storage/logs/laravel.log`
2. Estructura JSON en frontend (puede haber nulos inesperados)
3. Validación en FormRequest (puede estar rechazando procesos)

