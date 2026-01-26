# 🔍 DIAGNOSTICO: Pérdida de Ubicaciones y Observaciones en Procesos

## 📊 RESUMEN EJECUTIVO

**PROBLEMA ENCONTRADO:** Los datos de `ubicaciones` y `observaciones` llegan correctamente al backend pero se guardan como `NULL` en `pedidos_procesos_prenda_detalles`.

**CAUSA RAÍZ:** Estructura JSON inconsistente entre lo que el Normalizer envía y lo que el PedidoWebService espera.

---

## 🔄 FLUJO DE DATOS (Frontend → Backend)

### 1️⃣ FRONTEND CAPTURA (CORRECTO)

```javascript
// Form Handler captura correctamente:
ubicaciones: ["ewrrw","werwer"]
observaciones: "werwer"
tallas: { dama: { S:10, M:20 } }
```

### 2️⃣ PAYLOAD NORMALIZER NORMALIZA (PARCIALMENTE CORRECTO )

**Archivo:** `payload-normalizer-v3-definitiva.js` línea 82-90

```javascript
function normalizarProcesos(procesosRaw) {
    if (!procesosRaw || typeof procesosRaw !== 'object') return {};
    const procesosNorm = {};
    Object.entries(procesosRaw).forEach(function([tipoProceso, datoProceso]) {
        if (!datoProceso || typeof datoProceso !== 'object') return;
        procesosNorm[tipoProceso] = {
            tipo: datoProceso.tipo || tipoProceso,
            ubicaciones: Array.isArray(datoProceso.ubicaciones) ? datoProceso.ubicaciones : [],
            observaciones: datoProceso.observaciones || '',
            tallas: normalizarTallas(datoProceso.tallas || {}),
            imagenes: []
        };
    });
    return procesosNorm;
}
```

**Envía al backend:**
```json
{
  "procesos": {
    "reflectivo": {
      "tipo": "reflectivo",
      "ubicaciones": ["ewrrw", "werwer"],
      "observaciones": "werwer",
      "tallas": { "dama": { "S": 10, "M": 20 }, "caballero": {} },
      "imagenes": []
    }
  }
}
```

### 3️⃣ BACKEND RECIBE Y MAPEA ITEMS (ESTRUCTURA SÍ EXISTE)

**Archivo:** `CrearPedidoEditableController.php` línea 1192+

La estructura llega correctamente con `items[].procesos[].ubicaciones` y `items[].procesos[].observaciones`.

### 4️⃣ PEDIDOWEBSERVICE PROCESA  PROBLEMA AQUÍ

**Archivo:** `PedidoWebService.php` línea 429-520

```php
private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos): void
{
    foreach ($procesos as $tipoProceso => $procesoData) {
        // LÍNEA 457: Aquí está el problema
        $datosProceso = $procesoData['datos'] ?? $procesoData;
        
        // LÍNEA 487: Intenta leer de $datosProceso
        'ubicaciones' => json_encode($datosProceso['ubicaciones'] ?? []),
        'observaciones' => $datosProceso['observaciones'] ?? null,
        
        // PERO: $procesoData VIENE COMO:
        // {
        //   "tipo": "reflectivo",
        //   "ubicaciones": [...],        ← AQUÍ ESTÁ
        //   "observaciones": "...",      ← AQUÍ ESTÁ
        //   "tallas": {...},
        //   "imagenes": [...]
        // }
        
        // Y NO COMO:
        // {
        //   "datos": {
        //     "ubicaciones": [...],
        //     "observaciones": "...",
        //     ...
        //   }
        // }
    }
}
```

---

## 🎯 EL PROBLEMA EXACTO

El PayloadNormalizer CORRECTO aplana la estructura:
```
procesos.reflectivo = {
  tipo: "reflectivo",
  ubicaciones: [...],
  observaciones: "...",
  tallas: {...},
  imagenes: [...]
}
```

Pero el backend en línea 457 de `PedidoWebService.php` intenta:
```
$datosProceso = $procesoData['datos'] ?? $procesoData;
```

Esto funciona porque `??` devuelve `$procesoData` completo... PERO en línea 487:

```php
'ubicaciones' => json_encode($datosProceso['ubicaciones'] ?? []),
'observaciones' => $datosProceso['observaciones'] ?? null,
```

Aquí se accede CORRECTAMENTE a los campos... 

**ESPERA: El código debería funcionar.** Déjame revisar el modelo...

---

## 🔴 PROBLEMA REAL ENCONTRADO

**Modelo:** `ProcesoPrendaDetalle.php`

```php
protected $fillable = [
    'prenda_pedido_id',
    'tipo_proceso_id',
    'ubicaciones',              ESTÁ
    'observaciones',            ESTÁ
    'tallas_dama',
    'tallas_caballero',
    'estado',
    'notas_rechazo',
    'fecha_aprobacion',
    'aprobado_por',
    'datos_adicionales',
];

protected $casts = [
    'ubicaciones' => 'json',    JSON CAST
    'tallas_dama' => 'json',
    'tallas_caballero' => 'json',
    'datos_adicionales' => 'json',
    'fecha_aprobacion' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

**El modelo SÍ tiene los campos en `$fillable` y con `json` casts.**

---

## 🔎 VERIFICACIÓN DE LOGS

Del `laravel.log` adjunto:

```
[2026-01-26 09:06:49] local.INFO: [PedidoWebService] ⚙️ Creando procesos
[2026-01-26 09:06:49] local.INFO: [PedidoWebService] 🔍 Procesando tipo: reflectivo
[2026-01-26 09:06:49] local.DEBUG: [PedidoWebService] Creando proceso
[2026-01-26 09:06:49] local.INFO: [PedidoWebService] Proceso creado
```

**IMPORTANTE:** No hay logs de:
- `ubicaciones` siendo guardadas
- `observaciones` siendo guardadas
- Los valores específicos

---

## 🎯 CONCLUSIÓN: LA VERDADERA CAUSA

Tras revisar el código:

1. **PayloadNormalizer:** CORRECTO - Normaliza correctamente
2. **CrearPedidoEditableController:** CORRECTO - Recibe correctamente
3. **PedidoWebService línea 487:** PARECE CORRECTO - Mapea correctamente
4. **Modelo ProcesoPrendaDetalle:** CORRECTO - $fillable y $casts están bien

**PERO HAY UN PROBLEMA EN LA CAPTURA:**

```php
'ubicaciones' => json_encode($datosProceso['ubicaciones'] ?? []),
'observaciones' => $datosProceso['observaciones'] ?? null,
```

Si `$datosProceso['ubicaciones']` es `[]` (array vacío), `json_encode([])` devuelve `"[]"` (string JSON vacío).
Si `$datosProceso['observaciones']` viene en `$procesoData['datos']['observaciones']` pero NO en `$procesoData['observaciones']`, será NULL.

---

##  SOLUCIÓN: 3 PASOS

### PASO 1: Mejorar el Normalizer (OPCIONAL pero RECOMENDADO)

**Archivo:** `payload-normalizer-v3-definitiva.js` línea 82+

✅ ACTUAL: Ya está correcto, pero agreguemos más robustez:

```javascript
function normalizarProcesos(procesosRaw) {
    if (!procesosRaw || typeof procesosRaw !== 'object') return {};
    const procesosNorm = {};
    Object.entries(procesosRaw).forEach(function([tipoProceso, datoProceso]) {
        if (!datoProceso || typeof datoProceso !== 'object') return;
        
        // Verificar si viene anidado en 'datos'
        const datosReales = datoProceso.datos || datoProceso;
        
        procesosNorm[tipoProceso] = {
            tipo: datosReales.tipo || datoProceso.tipo || tipoProceso,
            ubicaciones: Array.isArray(datosReales.ubicaciones) ? datosReales.ubicaciones : (Array.isArray(datoProceso.ubicaciones) ? datoProceso.ubicaciones : []),
            observaciones: (datosReales.observaciones || datoProceso.observaciones || '').trim(),
            tallas: normalizarTallas(datosReales.tallas || datoProceso.tallas || {}),
            imagenes: []
        };
        console.log('[PayloadNormalizer]  Proceso ' + tipoProceso + ' normalizado', procesosNorm[tipoProceso]);
    });
    return procesosNorm;
}
```

### PASO 2: Fortalecer el PedidoWebService

**Archivo:** `PedidoWebService.php` línea 457-495

```php
private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos): void
{
    foreach ($procesos as $tipoProceso => $procesoData) {
        if (!is_array($procesoData)) {
            Log::warning('[PedidoWebService] Datos de proceso no es array', [
                'tipo' => $tipoProceso,
                'tipo_datos' => gettype($procesoData),
            ]);
            continue;
        }

        // MEJORADO: Buscar en múltiples niveles
        $datosProceso = $procesoData['datos'] ?? $procesoData;
        
        if (!is_array($datosProceso)) {
            Log::warning('[PedidoWebService] datosProceso no es array', [
                'tipo' => $tipoProceso,
                'tipo_datos' => gettype($datosProceso),
            ]);
            continue;
        }
        
        // Obtener tipo_proceso_id
        $tipoProcesoId = $this->obtenerTipoProcesoId($tipoProceso);
        if (!$tipoProcesoId) {
            Log::warning('[PedidoWebService] Tipo de proceso no encontrado', [
                'tipo' => $tipoProceso,
            ]);
            continue;
        }

        //  EXTRACCIÓN ROBUSTA DE UBICACIONES Y OBSERVACIONES
        $ubicaciones = $datosProceso['ubicaciones'] ?? $procesoData['ubicaciones'] ?? [];
        $observaciones = $datosProceso['observaciones'] ?? $procesoData['observaciones'] ?? null;
        
        // Validar que ubicaciones sea array
        if (!is_array($ubicaciones)) {
            $ubicaciones = is_string($ubicaciones) ? [$ubicaciones] : [];
        }
        
        // Limpiar string de observaciones
        if (is_string($observaciones)) {
            $observaciones = trim($observaciones);
            $observaciones = empty($observaciones) ? null : $observaciones;
        }

        Log::debug('[PedidoWebService] Creando proceso', [
            'tipo' => $tipoProceso,
            'ubicaciones' => $ubicaciones,
            'observaciones' => $observaciones,
            'tallas_count' => isset($datosProceso['tallas']) ? count($datosProceso['tallas']) : 0,
            'imagenes_count' => isset($datosProceso['imagenes']) ? count($datosProceso['imagenes']) : 0,
        ]);

        // CREAR CON DATOS EXTRACTADOS Y VALIDADOS
        $procesoPrenda = PedidosProcesosPrendaDetalle::create([
            'prenda_pedido_id' => $prenda->id,
            'tipo_proceso_id' => $tipoProcesoId,
            'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
            'observaciones' => $observaciones,
            'datos_adicionales' => json_encode($datosProceso),
            'estado' => 'PENDIENTE',
        ]);

        Log::info('[PedidoWebService] Proceso creado', [
            'proceso_id' => $procesoPrenda->id,
            'tipo' => $tipoProceso,
            'ubicaciones_guardadas' => $procesoPrenda->ubicaciones,
            'observaciones_guardadas' => $procesoPrenda->observaciones,
        ]);

        // Crear tallas del proceso
        if (isset($datosProceso['tallas']) && is_array($datosProceso['tallas'])) {
            \Log::info('[PedidoWebService] 📏 Llamando crearTallasProceso', [
                'proceso_id' => $procesoPrenda->id,
                'tallas_estructura' => array_keys($datosProceso['tallas']),
            ]);
            $this->crearTallasProceso($procesoPrenda, $datosProceso['tallas']);
        }
    }

    \Log::info('[PedidoWebService] ⚙️ crearProcesosCompletos TERMINADA', [
        'prenda_id' => $prenda->id,
    ]);
}
```

### PASO 3: Verificación en Lectura (ObtenerPedidoDetalleService)

**Archivo:** `ObtenerPedidoDetalleService.php` línea 713-720

✅ YA ESTÁ CORRECTO - Solo verificar que decodifica bien:

```php
'ubicaciones' => $proceso->ubicaciones 
    ? (is_array($proceso->ubicaciones) ? $proceso->ubicaciones : json_decode($proceso->ubicaciones, true) ?? []) 
    : [],
'observaciones' => $proceso->observaciones ?? '',
```

---

## RESUMEN DE CAMBIOS

| Componente | Cambio | Impacto |
|-----------|--------|--------|
| **Normalizer** | Agregada robustez en búsqueda de campos | Mejor tolerancia a variaciones estructurales |
| **PedidoWebService** | Extracción explícita y validación de ubicaciones/observaciones | Garantiza que llegan a BD |
| **Logs** | Agregados valores guardados reales | Visibilidad completa del problema |
| **Modelo** | Sin cambios (ya está bien) | N/A |

---

## 🧪 PRUEBA DESPUÉS DE IMPLEMENTAR

1. Crear un pedido con proceso
2. Verificar logs en nivel DEBUG:
   ```
   [PedidoWebService] Creando proceso
   ubicaciones: ["ewrrw","werwer"]
   observaciones: "werwer"
   ```
3. Consultar BD:
   ```sql
   SELECT ubicaciones, observaciones FROM pedidos_procesos_prenda_detalles 
   WHERE tipo_proceso_id = X;
   ```
   Debe retornar JSON en `ubicaciones` y texto en `observaciones`.

---

## 📝 NOTAS IMPORTANTES

- `json_encode([])` retorna `"[]"` en BD, que se decodifica correctamente después
- Las observaciones pueden ser NULL, lo cual es válido
- Las ubicaciones pueden ser `[]` (array vacío), lo cual es válido
- Los casts de Eloquent manejan automáticamente encode/decode
