#  SOLUCIÓN IMPLEMENTADA: FLUJO DE DATOS PROCESOS, TELAS, IMÁGENES

**Fecha:** 26 Enero 2026  
**Estado:** 🟢 EN IMPLEMENTACIÓN  
**Cambios:** Frontend + Backend Logging

---

## CAMBIOS IMPLEMENTADOS

### 1️⃣ FRONTEND: GestorPedidoSinCotizacion.js

**Archivo:** `public/js/modulos/crear-pedido/gestores/gestor-pedido-sin-cotizacion.js`

#### Cambio 1: agregarPrenda() ahora incluye procesos, telas, imagenes

```javascript
agregarPrenda() {
    const index = this.prendas.length;
    
    const prenda = {
        index: index,
        nombre_producto: '',
        descripcion: '',
        genero: '',
        cantidadesPorTalla: {},
        fotos: [],
        // NUEVO: Incluir procesos, telas, imagenes, variaciones
        procesos: {},
        telas: [],
        imagenes: [],
        variaciones: {}
    };
    
    this.prendas.push(prenda);
    return index;
}
```

#### Cambio 2: Métodos para agregar procesos, telas, imágenes

```javascript
/**
 * Agregar proceso a una prenda
 */
agregarProcesoAPrenda(prendaIndex, procesoData) {
    if (prendaIndex >= 0 && prendaIndex < this.prendas.length) {
        const tipoProc = procesoData.tipo || 'reflectivo';
        this.prendas[prendaIndex].procesos = this.prendas[prendaIndex].procesos || {};
        this.prendas[prendaIndex].procesos[tipoProc] = procesoData;
    }
}

agregarTelaAPrenda(prendaIndex, telaData) {
    if (prendaIndex >= 0 && prendaIndex < this.prendas.length) {
        this.prendas[prendaIndex].telas = this.prendas[prendaIndex].telas || [];
        this.prendas[prendaIndex].telas.push(telaData);
    }
}

agregarImagenAPrenda(prendaIndex, archivo) {
    if (prendaIndex >= 0 && prendaIndex < this.prendas.length) {
        this.prendas[prendaIndex].imagenes = this.prendas[prendaIndex].imagenes || [];
        this.prendas[prendaIndex].imagenes.push(archivo);
    }
}
```

#### Cambio 3: obtenerTodas() retorna estructura completa

```javascript
obtenerTodas() {
    // Asegurar que todas las prendas tengan los campos necesarios
    return this.prendas.map(p => ({
        index: p.index,
        nombre_producto: p.nombre_producto || '',
        nombre_prenda: p.nombre_producto || '',
        descripcion: p.descripcion || '',
        genero: p.genero || '',
        cantidadesPorTalla: p.cantidadesPorTalla || {},
        cantidad_talla: p.cantidadesPorTalla || {},
        fotos: p.fotos || [],
        imagenes: p.imagenes || [],
        procesos: p.procesos || {},
        telas: p.telas || [],
        variaciones: p.variaciones || {},
        tipo: p.tipo || 'prenda_nueva',
        origen: p.origen || 'bodega',
        de_bodega: p.de_bodega ? 1 : 0
    }));
}
```

#### Cambio 4: recopilarDatosDelDOM() mantiene procesos, telas, imagenes

```javascript
recopilarDatosDelDOM() {
    this.cliente = document.getElementById('cliente_editable')?.value || '';
    this.asesora = document.getElementById('asesora_editable')?.value || '';
    this.formaPago = document.getElementById('forma_de_pago_editable')?.value || '';
    
    const prendasContainer = document.getElementById('prendas-container-editable');
    const prendaCards = prendasContainer?.querySelectorAll('.prenda-card-editable') || [];
    
    const prendasDelDOM = [];
    
    prendaCards.forEach((card, index) => {
        const prenda = {
            index: index,
            nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
            descripcion: card.querySelector('.prenda-descripcion')?.value || '',
            genero: card.querySelector(`select[name="genero[${index}]"]`)?.value || '',
            cantidadesPorTalla: {},
            // NUEVO: Mantener procesos, telas, imagenes, variaciones del estado anterior
            procesos: this.prendas[index]?.procesos || {},
            telas: this.prendas[index]?.telas || [],
            imagenes: this.prendas[index]?.imagenes || [],
            variaciones: this.prendas[index]?.variaciones || {},
            fotos: this.prendas[index]?.fotos || []
        };
        
        // Recopilar cantidades...
        card.querySelectorAll('.talla-cantidad').forEach(input => {
            const talla = input.getAttribute('data-talla');
            const cantidad = parseInt(input.value) || 0;
            if (talla && cantidad > 0) {
                prenda.cantidadesPorTalla[talla] = cantidad;
            }
        });
        
        prendasDelDOM.push(prenda);
    });
    
    this.prendas = prendasDelDOM;
}
```

---

### 2️⃣ FRONTEND: inicializador-pedido-completo.js

**Archivo:** `public/js/pedidos-produccion/inicializador-pedido-completo.js`

#### Logging detallado en crearPedidoConBuilderUnificado()

```javascript
window.crearPedidoConBuilderUnificado = async function() {
    try {
        console.log('[Builder] Iniciando creación de pedido unificado');
        
        const gestor = window.gestorPedidoSinCotizacion;
        if (!gestor) throw new Error('Gestor no inicializado');
        
        const prendas = gestor.obtenerTodas();
        if (prendas.length === 0) throw new Error('No hay prendas agregadas');
        
        // LOG 1: Ver procesos ANTES
        console.log('[Builder] Estado de prendas ANTES:', {
            cantidad_prendas: prendas.length,
            procesos_totales: prendas.reduce((sum, p) => sum + Object.keys(p.procesos || {}).length, 0),
            telas_totales: prendas.reduce((sum, p) => sum + (p.telas || []).length, 0),
            imagenes_totales: prendas.reduce((sum, p) => sum + (p.imagenes || []).length, 0),
            prendas_detail: prendas.map((p, idx) => ({
                idx,
                nombre: p.nombre_producto || p.nombre_prenda,
                procesos_tipos: Object.keys(p.procesos || {}),
                procesos_count: Object.keys(p.procesos || {}).length
            }))
        });
        
        const cliente = document.getElementById('cliente_editable')?.value;
        const asesora = document.getElementById('asesora_editable')?.value;
        const formaPago = document.getElementById('forma_de_pago_editable')?.value;
        
        if (!cliente) throw new Error('Cliente requerido');
        
        const builder = new PedidoCompletoUnificado();
        builder.setCliente(cliente).setAsesora(asesora).setFormaPago(formaPago);
        
        // Agregar prendas con logging individual
        prendas.forEach((prenda, idx) => {
            console.log(`[Builder] Agregando prenda ${idx}:`, {
                nombre: prenda.nombre_producto,
                procesos_tipos: Object.keys(prenda.procesos || {}),
                telas_count: (prenda.telas || []).length
            });
            builder.agregarPrenda(prenda);
        });
        
        builder.validate();
        const payloadLimpio = builder.build();
        
        // LOG 2: Ver procesos DESPUÉS
        console.log('[Builder] Payload FINAL:', {
            procesos_totales: payloadLimpio.items.reduce((sum, i) => sum + Object.keys(i.procesos || {}).length, 0),
            items_detail: payloadLimpio.items.map((item, idx) => ({
                idx,
                procesos: item.procesos,
                tiene_procesos: !!Object.keys(item.procesos || {}).length
            }))
        });
        
        // Enviar...
        const response = await fetch('/asesores/pedidos-editable/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payloadLimpio)
        });
        
        // ... resto del código ...
    } catch (error) {
        console.error('[Builder] Error:', error);
        // ... manejo de error ...
    }
};
```

---

### 3️⃣ BACKEND: Logging en CrearPedidoEditableController

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

```php
public function crearPedido(CrearPedidoCompletoRequest $request): JsonResponse
{
    try {
        $validated = $request->validated();

        //  LOG 1: Datos recibidos del frontend
        Log::info('[CrearPedidoEditableController::crearPedido] INICIO', [
            'cliente' => $validated['cliente'],
            'items_count' => count($validated['items'] ?? []),
            'primer_item_procesos_tipos' => isset($validated['items'][0]['procesos']) 
                ? array_keys($validated['items'][0]['procesos']) 
                : 'NO_EXISTE',
            'primer_item_procesos_count' => isset($validated['items'][0]['procesos'])
                ? count($validated['items'][0]['procesos'])
                : 0,
            'primer_item_telas_count' => isset($validated['items'][0]['telas'])
                ? count($validated['items'][0]['telas'])
                : 0,
        ]);

        $clienteNombre = trim($request->input('cliente'));
        $cliente = $this->obtenerOCrearCliente($clienteNombre);

        //  LOG 2: Items antes de crear command
        $itemsDebug = collect($validated['items'])->map(function($item, $idx) {
            return [
                'idx' => $idx,
                'nombre' => $item['nombre_prenda'] ?? 'SIN_NOMBRE',
                'procesos_count' => count($item['procesos'] ?? []),
                'procesos_tipos' => array_keys($item['procesos'] ?? []),
                'telas_count' => count($item['telas'] ?? []),
                'imagenes_count' => count($item['imagenes'] ?? []),
                'tiene_procesos' => !empty($item['procesos']),
            ];
        })->toArray();

        Log::info('[CrearPedidoEditableController] Items detalle:', $itemsDebug);

        // Crear command
        $command = new CrearPedidoCompletoCommand(
            cliente: $cliente->id,
            formaPago: $validated['forma_de_pago'] ?? 'CONTADO',
            asesorId: \Illuminate\Support\Facades\Auth::id(),
            items: $validated['items'],
            descripcion: $validated['descripcion'] ?? null,
        );

        //  LOG 3: Command creado
        Log::info('[CrearPedidoEditableController] Command CrearPedidoCompletoCommand creado', [
            'items_count' => count($command->getItems()),
            'primer_item_procesos' => !empty($command->getItems()[0]['procesos']) ? 'SÍ_TIENE' : 'NO_TIENE',
        ]);

        // Ejecutar handler
        $pedido = app(\App\Domain\Shared\CQRS\CommandBus::class)->execute($command);

        //  LOG 4: Verificar que se guardaron procesos en BD
        $procesosEnBD = \DB::table('pedidos_procesos_prenda_detalles')
            ->whereIn('prenda_pedido_id', $pedido->prendas->pluck('id'))
            ->count();

        Log::info('[CrearPedidoEditableController] Pedido creado - Procesos en BD', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'prendas_creadas' => count($pedido->prendas),
            'procesos_en_bd' => $procesosEnBD,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pedido creado exitosamente',
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);
        
    } catch (\Exception $e) {
        Log::error('[CrearPedidoEditableController::crearPedido] ERROR', [
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

---

### 4️⃣ BACKEND: Logging en PedidoPrendaService

**Archivo:** `app/Application/Services/PedidoPrendaService.php`

Actualizar el método `guardarProcesosPrenda()`:

```php
private function guardarProcesosPrenda(PrendaPedido $prenda, array $procesos): void
{
    //  LOG 1: Procesos que se reciben
    Log::info('[PedidoPrendaService::guardarProcesosPrenda] INICIO', [
        'prenda_id' => $prenda->id,
        'prenda_nombre' => $prenda->nombre_prenda,
        'procesos_count' => count($procesos),
        'procesos_tipos' => array_keys($procesos),
        'procesos_data' => $procesos, // DEBUG: Ver estructura completa
    ]);
    
    if (empty($procesos)) {
        Log::warning('[PedidoPrendaService::guardarProcesosPrenda]  SIN PROCESOS', [
            'prenda_id' => $prenda->id,
        ]);
        return;
    }

    // Llamar al servicio de dominio
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
            ];
        })->toArray(),
    ]);
}
```

---

### 5️⃣ BACKEND: Logging en PrendaProcesoService

**Archivo:** `app/Domain/Pedidos/Services/PrendaProcesoService.php`

Actualizar el método `guardarProcesosPrenda()`:

```php
public function guardarProcesosPrenda(int $prendaId, int $pedidoId, array $procesos): void
{
    //  LOG 1: INICIO
    Log::info('[PrendaProcesoService::guardarProcesosPrenda] INICIO', [
        'prenda_id' => $prendaId,
        'pedido_id' => $pedidoId,
        'procesos_count' => count($procesos),
        'procesos_estructura' => array_map(function($p) {
            return [
                'tipo' => $p['tipo'] ?? 'SIN_TIPO',
                'tiene_ubicaciones' => !empty($p['ubicaciones']),
                'ubicaciones' => $p['ubicaciones'] ?? [],
                'tiene_observaciones' => !empty($p['observaciones']),
                'observaciones' => $p['observaciones'] ?? '',
                'tiene_tallas' => !empty($p['tallas']),
                'tallas_count' => count($p['tallas'] ?? []),
                'tiene_imagenes' => !empty($p['imagenes']),
                'imagenes_count' => count($p['imagenes'] ?? []),
            ];
        }, $procesos),
    ]);

    foreach ($procesos as $procesoIndex => $proceso) {
        try {
            // Obtener tipo_proceso_id
            $tipoProcesoId = $proceso['tipo_proceso_id'] ?? $proceso['id'] ?? null;
            
            if (!$tipoProcesoId && !empty($proceso['tipo'])) {
                $tipoNombre = $proceso['tipo'];
                $tipoProcesoObj = DB::table('tipos_procesos')
                    ->where('nombre', 'like', "%{$tipoNombre}%")
                    ->first();
                
                if ($tipoProcesoObj) {
                    $tipoProcesoId = $tipoProcesoObj->id;
                } else {
                    $tipoProcesoId = DB::table('tipos_procesos')->insertGetId([
                        'nombre' => $tipoNombre,
                        'descripcion' => "Proceso: {$tipoNombre}",
                        'activo' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            if (!$tipoProcesoId) {
                Log::warning('[PrendaProcesoService] Tipo proceso no especificado', [
                    'prenda_id' => $prendaId,
                    'proceso_index' => $procesoIndex,
                ]);
                continue;
            }

            // Crear detalle
            $procesoDetalleId = DB::table('pedidos_procesos_prenda_detalles')->insertGetId([
                'prenda_pedido_id' => $prendaId,
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
                'observaciones' => $proceso['observaciones'] ?? null,
                'tallas_dama' => !empty($proceso['tallas']['dama']) ? json_encode($proceso['tallas']['dama']) : null,
                'tallas_caballero' => !empty($proceso['tallas']['caballero']) ? json_encode($proceso['tallas']['caballero']) : null,
                'estado' => 'PENDIENTE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            //  LOG 2: Proceso creado
            Log::info('[PrendaProcesoService] Detalle proceso creado', [
                'prenda_id' => $prendaId,
                'proceso_detalle_id' => $procesoDetalleId,
                'tipo_proceso_id' => $tipoProcesoId,
                'tipo_proceso_nombre' => $proceso['tipo'] ?? 'DESCONOCIDO',
                'ubicaciones_count' => count($proceso['ubicaciones'] ?? []),
                'tiene_observaciones' => !!($proceso['observaciones'] ?? null),
            ]);

            // Guardar tallas
            $this->guardarTallasProceso($procesoDetalleId, $proceso);

            // Guardar imágenes
            $imagenes = $proceso['imagenes'] ?? [];
            if (!empty($imagenes)) {
                Log::info('[PrendaProcesoService] Guardando imágenes del proceso', [
                    'proceso_detalle_id' => $procesoDetalleId,
                    'imagenes_count' => count($imagenes),
                ]);
                
                $this->procesoImagenService->guardarImagenesProcesos(
                    $procesoDetalleId,
                    $pedidoId,
                    $imagenes
                );
            }
            
        } catch (\Exception $e) {
            Log::error('[PrendaProcesoService] Error guardando proceso', [
                'prenda_id' => $prendaId,
                'proceso_index' => $procesoIndex,
                'error' => $e->getMessage(),
            ]);
        }
    }

    //  LOG 3: Resumen final
    Log::info('[PrendaProcesoService] COMPLETO', [
        'prenda_id' => $prendaId,
        'procesos_guardados' => count($procesos),
    ]);
}
```

---

## 🧪 CÓMO PROBAR

### Paso 1: Abrir DevTools del navegador

```javascript
// En la consola del navegador, ejecutar:
console.log('[Builder] Procesos en prendas:', window.gestorPedidoSinCotizacion.obtenerTodas().map(p => ({ nombre: p.nombre_producto, procesos: Object.keys(p.procesos || {}) })));
```

### Paso 2: Crear un pedido con proceso

1. Agregar una prenda
2. Agregar un proceso (ej: reflectivo)
3. Ver en consola que `[Builder] Procesos...` muestre los procesos

### Paso 3: Verificar logs del servidor

```bash
tail -f storage/logs/laravel.log | grep "PrendaProcesoService"
```

Deberías ver:
```
[PrendaProcesoService::guardarProcesosPrenda] INICIO
procesos_count: 1
procesos_tipos: ["reflectivo"]
...
[PrendaProcesoService] COMPLETO
```

### Paso 4: Verificar base de datos

```sql
SELECT * FROM pedidos_procesos_prenda_detalles WHERE prenda_pedido_id = X;
```

Deberías ver registros con:
- `ubicaciones` (JSON): `["ubicacion1"]`
- `observaciones`: "texto"
- `tallas_dama`: `{"S": 10, "M": 20}`

---

## 📋 CHECKLIST

- [x] GestorPedidoSinCotizacion.agregarPrenda() incluye procesos, telas, imagenes
- [x] GestorPedidoSinCotizacion.obtenerTodas() retorna estructura completa
- [x] crearPedidoConBuilderUnificado() loguea procesos
- [ ] ⏳ Agregar logs en CrearPedidoEditableController
- [ ] ⏳ Agregar logs en PedidoPrendaService
- [ ] ⏳ Agregar logs en PrendaProcesoService
- [ ] ⏳ Probar con datos reales
- [ ] ⏳ Verificar base de datos

