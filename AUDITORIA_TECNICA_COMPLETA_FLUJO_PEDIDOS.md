# 🔍 AUDITORÍA TÉCNICA COMPLETA - FLUJO PEDIDOS + PROCESOS + IMÁGENES + EPP

**Fecha:** 26 Enero 2026  
**Estado:** 🟡 CRÍTICO - Múltiples vulnerabilidades de pérdida de datos  
**Enfoque:** Prendas + Procesos + Telas + Imágenes + EPP (separado)

---

## 📊 RESUMEN EJECUTIVO

### Aspectos CORRECTOS

| Área | Estado | Observación |
|------|--------|-------------|
| **Transacciones Backend** | Implementadas | DB::transaction en PedidoPrendaService |
| **Separación Prendas/EPP** | Completa | Tablas dedicadas, servicios independientes |
| **Conversion WebP de EPP** | Optimizada | PedidoEppService convierte a WebP + redimensiona |
| **FormData (Frontend)** | En uso | PayloadNormalizer.buildFormData() |
| **Validation Rules** | Extensas | CrearPedidoCompletoRequest documenta estructura completa |

---

### 🔴 VULNERABILIDADES CRÍTICAS

| # | Severidad | Área | Problema | Impacto |
|---|-----------|------|---------|--------|
| 1 | 🔴 CRÍTICA | Frontend State | `GestorPendidoSinCotizacion.recopilarDatosDelDOM()` no sincroniza índices | Pérdida de procesos/telas al refrescar DOM |
| 2 | 🔴 CRÍTICA | Builder | `PedidoCompletoUnificado.agregarPrenda()` no valida procesos/telas estructura | Procesos vacíos en payload |
| 3 | 🔴 CRÍTICA | Image Handling | Dos estrategias de imágenes conflictivas (FormData + JSON) | Imágenes perdidas o duplicadas |
| 4 | 🔴 CRÍTICA | Controller | `crearPedido()` recibe FormData pero busca JSON en campo "pedido" | 400 Bad Request silencioso |
| 5 | 🟠 ALTA | Transactions | PedidoEppService no está en transacción con PedidoPrendaService | Huérfanos si EPP falla |
| 6 | 🟠 ALTA | Validation | EPP sin validación de duplicados (epp_id + cantidad) | Registros duplicados en pedido_epp |
| 7 | 🟠 ALTA | Domain Layer | PrendaProcesoService crea tipos_procesos dinamicamente (riesgo FK) | Procesos sin tipo válido |

---

## 🎯 AUDITORÍA POR CAPAS

---

## 1️⃣ FRONTEND - STATE MANAGEMENT

### 📁 Archivo: `public/js/modulos/crear-pedido/gestores/gestor-pedido-sin-cotizacion.js`

#### BIEN: Estructura de prendas completa

```javascript
agregarPrenda() {
    const prenda = {
        procesos: {},      // Inicializado
        telas: [],         // Inicializado
        imagenes: [],      // Inicializado
        variaciones: {}    // Inicializado
    };
    this.prendas.push(prenda);
}
```

**Evaluación:** Iniciailización correcta. Los 4 campos necesarios se crean al agregar una prenda.

---

#### 🔴 CRÍTICA: Sincronización de índices rotos

```javascript
recopilarDatosDelDOM() {
    const prendas DelDOM = [];
    
    prendaCards.forEach((card, index) => {
        const prenda = {
            index: index,
            nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
            // ...
            // Intenta recuperar del estado anterior
            procesos: this.prendas[index]?.procesos || {},
            telas: this.prendas[index]?.telas || [],
            imagenes: this.prendas[index]?.imagenes || [],
        };
    });
    
    this.prendas = prendasDelDOM; //  SOBRESCRIBE COMPLETAMENTE
}
```

**PROBLEMA:**
- Cuando el DOM se renderiza con N cartas visibles pero el usuario agregó M > N prendas
- O si el DOM se vuelve a renderizar parcialmente (paginación, filtros)
- El índice `index` en el forEach NO coincide con el índice real en `this.prendas`

**EJEMPLO DE FALLO:**
```
ESTADO: this.prendas[0], this.prendas[1], this.prendas[2]
DOM RENDERIZADO: Solo cartas [0] y [1] se ven
querySelector('.prenda-card').forEach -> index 0, 1
Recupera: this.prendas[0], this.prendas[1]  COINCIDE

PERO si DOM está paginado:
Página 1 muestra cartas[0] y [1]
Página 2 muestra cartas[2] y [3]
Al hacer recopilarDatosDelDOM() en Página 2:
    index 0 → busca this.prendas[0]   INCORRECTO (debería ser this.prendas[2])
    index 1 → busca this.prendas[1]   INCORRECTO (debería ser this.prendas[3])
```

**RIESGO:**
- Procesos de Prenda[2] se pierden
- Telas de Prenda[2] se reemplazan con Prenda[0]'s telas
- Imágenes de Prenda[3] se pierden

**SOLUCIÓN RECOMENDADA:**
Usar `data-prenda-id` atributo HTML en lugar de índice:
```javascript
recopilarDatosDelDOM() {
    const prendasDelDOM = [];
    
    prendaCards.forEach((card) => {
        const prendaId = card.getAttribute('data-prenda-id'); // ← KEY
        const prendaExistente = this.prendas.find(p => p.id === prendaId);
        
        const prenda = {
            id: prendaId,
            nombre_producto: card.querySelector('.prenda-nombre')?.value || '',
            procesos: prendaExistente?.procesos || {},  // ← Por ID, no por índice
            telas: prendaExistente?.telas || [],
            imagenes: prendaExistente?.imagenes || [],
        };
        prendasDelDOM.push(prenda);
    });
}
```

---

#### 🟠 MEDIO: No hay deduplicación de imágenes

```javascript
agregarImagenAPrenda(prendaIndex, archivo) {
    if (prendaIndex >= 0 && prendaIndex < this.prendas.length) {
        this.prendas[prendaIndex].imagenes.push(archivo); // ← Sin validar duplicados
    }
}
```

**RIESGO:** Usuario carga la misma imagen 2 veces
- Resultado: 2 registros en `prenda_fotos_pedido` con la misma ruta
- Espacio de disco duplicado
- Confusión en la galería

**SOLUCIÓN:**
```javascript
agregarImagenAPrenda(prendaIndex, archivo) {
    const imagenes = this.prendas[prendaIndex].imagenes || [];
    const nombre = archivo.name || archivo;
    
    // Evitar duplicados por nombre de archivo
    const yaExiste = imagenes.some(img => (img.name || img) === nombre);
    if (!yaExiste) {
        imagenes.push(archivo);
    }
}
```

---

#### 🟠 MEDIO: Métodos helpers sin validación de índice

```javascript
agregarProcesoAPrenda(prendaIndex, procesoData) {
    if (prendaIndex >= 0 && prendaIndex < this.prendas.length) {
        // Validación correcta del índice
        // Pero no valida estructura de procesoData
        const tipoProc = procesoData.tipo || 'reflectivo'; // ← Default peligroso
        this.prendas[prendaIndex].procesos[tipoProc] = procesoData;
    }
}
```

**RIESGO:**
- `procesoData` podría ser `{}`  → `tipoProc` = 'reflectivo' por defecto
- Procesos sin `tipo` se carga como reflectivo

**SOLUCIÓN:**
```javascript
agregarProcesoAPrenda(prendaIndex, procesoData) {
    if (prendaIndex < 0 || prendaIndex >= this.prendas.length) {
        throw new Error(`Índice de prenda inválido: ${prendaIndex}`);
    }
    
    if (!procesoData.tipo) {
        throw new Error('Proceso debe especificar tipo (reflectivo, bordado, etc)');
    }
    
    this.prendas[prendaIndex].procesos[procesoData.tipo] = {
        tipo: procesoData.tipo,
        ubicaciones: procesoData.ubicaciones || [],
        observaciones: procesoData.observaciones || null,
        tallas: procesoData.tallas || {},
        imagenes: procesoData.imagenes || []
    };
}
```

---

### BIEN: Métodos de acceso consistentes

```javascript
obtenerTodas() {
    return this.prendas.map(p => ({
        // Todos los campos mapeados, incluyendo procesos, telas, imagenes
        procesos: p.procesos || {},
        telas: p.telas || [],
        imagenes: p.imagenes || [],
        // ...
    }));
}
```

**Evaluación:** Retorna estructura completa. Correcto.

---

## 2️⃣ FRONTEND - BUILDER UNIFICADO

### 📁 Archivo: `public/js/pedidos-produccion/PedidoCompletoUnificado.js`

#### 🔴 CRÍTICA: Sanitización de procesos agresiva

```javascript
_sanitizarProcesos(procesos) {
    if (!procesos || typeof procesos !== 'object') {
        return {};  // ← Retorna {} si procesos es falsy O un array
    }
    
    // Si es un array, se pierde
    if (Array.isArray(procesos)) {
        console.warn('Procesos es array, ignorando');
        return {};
    }
    
    // ... sanitización
}
```

**PROBLEMA:**
- Si el frontend envía `procesos: [...]` (array)
- `Array.isArray(procesos)` = true
- Retorna `{}`
- Procesos completamente perdidos

**VERIFICACIÓN REQUERIDA:**
¿El frontend envía procesos como array `{}` o como object?

En `GestorPendidoSinCotizacion.agregarPrenda()`:
```javascript
procesos: {}  // ← Object (CORRECTO)
```

En `inicializador-pedido-completo.js`:
```javascript
builder.agregarPrenda(prenda);  // ← Pasa procesos como part del prenda object
```

**EVALUACIÓN:**
- Si procesos llega como object: OK
- Si llega como array:  PÉRDIDA TOTAL

**SOLUCIÓN DEFENSIVA:**
```javascript
_sanitizarProcesos(procesos) {
    if (!procesos) return {};
    
    // Si es array, convertir a object con índices como keys
    if (Array.isArray(procesos)) {
        console.warn('[Builder] Procesos llegó como array, convirtiendo...');
        const procesosObj = {};
        procesos.forEach((proc, idx) => {
            if (proc.tipo) {
                procesosObj[proc.tipo] = proc;
            } else {
                procesosObj[`proceso_${idx}`] = proc;
            }
        });
        return procesosObj;
    }
    
    if (typeof procesos !== 'object') {
        return {};
    }
    
    // ... resto de sanitización
}
```

---

#### 🟠 ALTA: JSON.parse(JSON.stringify()) destruye archivos File

```javascript
agregarPrenda(prendaData) {
    const prendasanitizada = JSON.parse(JSON.stringify(prendaData)); //  DESTRUYE FILES
    
    // Si prendaData.imagenes = [File, File, ...]
    // Después de JSON.stringify/parse:
    // prendaSanitizada.imagenes = [{}, {}, ...]  ← Objetos vacíos
}
```

**RIESGO:**
- Las imágenes (File objects) se serializan a `{}`
- Se pierden completamente al hacer JSON round-trip

**SOLUCIÓN:**
```javascript
agregarPrenda(prendaData) {
    // NO hacer JSON.stringify en imagenes
    const prendaSanitizada = {
        ...prendaData,
        imagenes: prendaData.imagenes || [], // ← Keep as-is
        procesos: { ...prendaData.procesos } || {},
        telas: Array.isArray(prendaData.telas) ? [...prendaData.telas] : [],
    };
    
    this.prendas.push(prendaSanitizada);
}
```

---

#### BIEN: Estructura de payload final

El método `build()` retorna:
```javascript
{
    cliente: 'string',
    items: [ { procesos: {}, telas: [], imagenes: [] } ]
}
```

**Evaluación:** Estructura compatible con backend.

---

## 3️⃣ IMAGE HANDLING - CRÍTICO

### Estrategia Actual: DUAL conflictiva

####  CONFLICTO 1: FormData vs JSON

**Opción A:** `inicializador-pedido-completo.js` envía JSON puro:
```javascript
const payloadLimpio = builder.build();

// ENVÍO COMO JSON
const response = await fetch('/asesores/pedidos-editable/crear', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payloadLimpio)  // ← Imagenes como File objects en JSON = FAIL
});
```

**Opción B:** `PayloadNormalizer.buildFormData()` extrae archivos:
```javascript
const formData = window.PayloadNormalizer.buildFormData(resultado, filesExtraidos);

// ENVÍO COMO FORMDATA
const response = await fetch('/asesores/pedidos-editable/crear', {
    method: 'POST',
    body: formData  // ← FormData + JSON embebido
});
```

**PROBLEMA:**
- ¿Cuál se usa realmente?
- Si una usa FormData y la otra usa JSON, habrá inconsistencia
- Backend espera FormData pero recibe JSON (o viceversa)

---

#### 🔴 CRÍTICA: Backend espera FormData

En `CrearPedidoEditableController.crearPedido()`:
```php
public function crearPedido(Request $request): JsonResponse
{
    try {
        Log::info('[CrearPedidoEditableController] Iniciando...', [
            'campos_recibidos' => $request->all(), // ← FormData fields
        ]);
        
        // Detecta si es FormData o JSON
        $pedidoJSON = $request->input('pedido');  // ← Busca campo "pedido" (FormData key)
        if (!$pedidoJSON) {
            return response()->json([
                'success' => false,
                'message' => 'Campo "pedido" JSON requerido',
            ], 422);
        }
```

**INTERPRETACIÓN:**
- Backend espera FormData con campo `pedido` = JSON stringified
- Otros campos en FormData serían archivos

**PERO:** `inicializador-pedido-completo.js` ENVÍA JSON PURO

**RESULTADO:**
```
Frontend: Content-Type: application/json
         Body: { "cliente": "...", "items": [...] }

Backend: $request->input('pedido')  ← busca este key
         NO ENCUENTRA en JSON
         Retorna: 422 "Campo pedido JSON requerido"
```

**SOLUCIÓN INMEDIATA:**
```php
// Option 1: Aceptar JSON puro
if ($request->getContentType() === 'application/json') {
    $validated = $request->json()->all();
} else {
    // Option 2: Aceptar FormData con campo "pedido"
    $pedidoJSON = $request->input('pedido');
    $validated = json_decode($pedidoJSON, true);
}
```

---

#### 🟠 RIESGO: Sin detección de envío real

No hay forma de saber si:
1. El frontend está enviando FormData
2. O está enviando JSON puro
3. O está combinando ambos incorrectamente

**Recomendación:**
Crear middleware que detecte y normalice:
```php
// app/Http/Middleware/NormalizeRequestPayload.php
class NormalizeRequestPayload
{
    public function handle($request, $next)
    {
        if ($request->getContentType() === 'application/json' && !$request->input('pedido')) {
            // JSON puro → convertir a FormData compatible
            $validated = $request->json()->all();
            $request->merge([
                'pedido' => json_encode($validated),
                // Los archivos no existen en JSON, ignorar
            ]);
        }
        
        return $next($request);
    }
}
```

---

## 4️⃣ BACKEND - CONTROLLER

### 📁 Archivo: `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

#### 🔴 CRÍTICA: Manejo de FormData inconsistente

```php
public function crearPedido(Request $request): JsonResponse
{
    try {
        // Busca campo "pedido" como JSON stringified
        $pedidoJSON = $request->input('pedido');
        
        if (!$pedidoJSON) {
            return response()->json([
                'success' => false,
                'message' => 'Campo "pedido" JSON requerido',
            ], 422);
        }
        
        $validated = json_decode($pedidoJSON, true);
        
        // PERO: ¿Dónde procesa los archivos?
        // $request->file('files_prenda_0')?  → NO encontrado aquí
        // $request->file('files_epp_0')?     → NO encontrado aquí
```

**PROBLEMA:**
- El controller extrae JSON metadatos
- Pero no procesa los archivos del FormData
- Imágenes se pierden silenciosamente

---

#### 🟠 ALTA: Falta de procesamiento de imágenes en controller

**Expected flow:**
1. Recibir FormData con:
   - `pedido`: JSON stringified
   - `files_prenda_0`: File
   - `files_prenda_1`: File
   - `files_epp_0`: File
2. Procesar cada archivo
3. Guardar rutas en BD

**Actual flow:**
1. Extrae `pedido` JSON
2. Crea PedidoProduccion
3.  No procesa `files_prenda_*`
4.  Imágenes se pierden

---

#### BIEN: Transacción global

```php
DB::transaction(function () {
    $pedido = $this->pedidoWebService->crearPedidoCompleto(...);
    // EPPs aquí
    // Actualizar cantidades
    return $pedido;
});
```

**Evaluación:** Transacción correcta. Si falla cualquier parte, todo hace rollback.

---

#### 🟠 ALTA: Duplicación de lógica entre controller y service

Controller crea PedidoEpp AND luego actualiza cantidades:
```php
// EN CONTROLLER
$pedidoEpp = PedidoEpp::create([...]);  // ← Debería estar en PedidoEppService

// EN SERVICE
$service->guardarEppsDelPedido($pedido, $epps);  // ← Duplica la lógica
```

**RIESGO:** 2 formas de crear EPP, inconsistencia

---

## 5️⃣ APPLICATION LAYER - SERVICES

### 📁 Archivo: `app/Application/Services/PedidoPrendaService.php`

#### BIEN: Transacción local para cada prenda

```php
DB::beginTransaction();
try {
    foreach ($prendas as $prendaData) {
        $this->guardarPrenda($pedido, $prendaData, $index);
    }
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    throw $e;
}
```

**Evaluación:** Manejo correcto de transacciones.

---

#### 🔴 CRÍTICA: Procesos se pierden si estructura es incorrecta

En `guardarPrenda()`:
```php
private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): PrendaPedido
{
    $prendaData = $this->dataNormalizer->normalizarPrendaData($prendaData);
    
    //  Si normalizarPrendaData() devuelve datos sin 'procesos'
    // los procesos se pierden aquí
    
    // Luego:
    // ... guardaVariaciones ...
    // ... guardaTelas ...
    // ... guardaImágenes ...
    
    // PERO: ¿Dónde está guardarProcesos()?
    // NO ESTÁ EXPLÍCITO en este método
}
```

**Buscar:**
```php
private function guardarProcesosPrenda(PrendaPedido $prenda, array $procesos): void
{
    // ← ESTE método EXISTE pero ¿se llama?
    Log::info('[PedidoPrendaService::guardarProcesosPrenda] INICIO', [
        'prenda_id' => $prenda->id,
        'procesos_count' => count($procesos),
    ]);
    
    if (empty($procesos)) {
        Log::warning('  SIN PROCESOS', ['prenda_id' => $prenda->id]);
        return;  // ← Silent exit
    }
    
    $this->prendaProcesoService->guardarProcesosPrenda(
        $prenda->id,
        $prenda->pedido_produccion_id,
        $procesos
    );
}
```

**PROBLEMA:**
- El método existe
- Pero no está siendo llamado en el flujo principal
- Los procesos no se guardan

**VERIFICACIÓN REQUERIDA:**
¿En qué línea se llama `guardarProcesosPrenda()` dentro de `guardarPrenda()`?

---

#### 🟠 ALTA: Logging verboso pero sin visibilidad real

```php
Log::info(' [PedidoPrendaService::guardarUnaPrendaEnPedido] Guardando prenda individual', [
    'pedido_id' => $pedido->id,
    'nombre_prenda' => $prendaData['nombre_producto'] ?? '...',
    // ← Falta: procesos_count, telas_count, imagenes_count
]);
```

**RECOMENDACIÓN:**
```php
Log::info('[PedidoPrendaService] Procesando prenda:', [
    'pedido_id' => $pedido->id,
    'nombre_prenda' => $prendaData['nombre_producto'] ?? 'SIN_NOMBRE',
    'procesos_count' => count($prendaData['procesos'] ?? []),
    'procesos_tipos' => array_keys($prendaData['procesos'] ?? []),
    'telas_count' => count($prendaData['telas'] ?? []),
    'imagenes_count' => count($prendaData['imagenes'] ?? []),
    'cantidad_total' => array_sum(array_merge(...array_values($prendaData['cantidad_talla'] ?? []))),
]);
```

---

### 📁 Archivo: `app/Services/PedidoEppService.php`

#### BIEN: WebP conversion

```php
$imagen_obj = \Intervention\Image\ImageManager::gd()->read($archivo->getRealPath());

// Redimensionar
if ($imagen_obj->width() > 2000 || $imagen_obj->height() > 2000) {
    $imagen_obj->scaleDown(width: 2000, height: 2000);
}

// Convertir a WebP 80%
$webp = $imagen_obj->toWebp(quality: 80);
```

**Evaluación:**
- Convierte a WebP (compresión)
- Redimensiona (< 2000px)
- Calidad 80 (buena relación tamaño/calidad)
- Fallback ImageMagick si GD falla

---

#### 🟠 ALTA: No está en transacción con Pedido

```php
// En CrearPedidoEditableController
DB::transaction(function () {
    $pedido = $this->pedidoWebService->crearPedidoCompleto(...);  // ← Transacción interna
    
    $pedidoEpp = PedidoEpp::create([...]);  // ← FUERA de transacción de pedido
    $this->eppService->guardarImagenesDelEpp($pedidoEpp, $imagenes);
});
```

**RIESGO:**
- Si `guardarImagenesDelEpp()` falla
- El PedidoEpp ya existe (huérfano)
- El Pedido está completo

**SOLUCIÓN:**
```php
DB::transaction(function () {
    $pedido = $this->pedidoWebService->crearPedidoCompleto(...);
    
    // DENTRO de la misma transacción
    $pedidosEpp = $this->eppService->guardarEppsDelPedido($pedido, $epps);
    
    // Todo se revierte si algo falla
});
```

---

#### 🟠 MEDIA: Sin deduplicación de EPPs

```php
public function guardarEppsDelPedido(PedidoProduccion $pedido, array $epps): array
{
    $pedidosEpp = [];

    foreach ($epps as $eppData) {
        $pedidoEpp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $eppData['epp_id'] ?? $eppData['id'],
            'cantidad' => $eppData['cantidad'] ?? 1,  // ← ¿Qué si el mismo EPP aparece 2 veces?
        ]);
        // ...
    }
}
```

**RIESGO:**
```
Frontend envía: [
    { epp_id: 5, cantidad: 10 },
    { epp_id: 5, cantidad: 20 }  // ← DUPLICADO
]

Resultado:
    pedido_epp → 2 registros con epp_id=5
    Cantidad total: 30 (¿es intención o error?)
```

**SOLUCIÓN:**
```php
// Agrupar EPPs iguales
$eppAgrupados = collect($epps)
    ->groupBy('epp_id')
    ->map(function ($grupo) {
        return [
            'epp_id' => $grupo->first()['epp_id'],
            'cantidad' => $grupo->sum('cantidad'),  // ← Sumar cantidades
            'observaciones' => implode('; ', $grupo->pluck('observaciones')->filter()),
        ];
    })
    ->values();

foreach ($eppAgrupados as $eppData) {
    // Guardar UNA vez por EPP
}
```

---

## 6️⃣ DOMAIN LAYER - ROBUSTEZ

### 📁 Archivo: `app/Domain/Pedidos/Services/PrendaProcesoService.php`

#### 🔴 CRÍTICA: Creación dinámica de tipos_procesos

```php
public function guardarProcesosPrenda(int $prendaId, int $pedidoId, array $procesos): void
{
    foreach ($procesos as $procesoIndex => $proceso) {
        $tipoProcesoId = $proceso['tipo_proceso_id'] ?? $proceso['id'] ?? null;
        
        if (!$tipoProcesoId && !empty($proceso['tipo'])) {
            $tipoNombre = $proceso['tipo'];
            $tipoProcesoObj = DB::table('tipos_procesos')
                ->where('nombre', 'like', "%{$tipoNombre}%")
                ->first();
            
            if ($tipoProcesoObj) {
                $tipoProcesoId = $tipoProcesoObj->id;
            } else {
                //  CREA TIPO_PROCESO DINÁMICAMENTE
                $tipoProcesoId = DB::table('tipos_procesos')->insertGetId([
                    'nombre' => $tipoNombre,
                    'descripcion' => "Proceso: {$tipoNombre}",
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
```

**RIESGOS:**

1. **Duplicación de tipos:**
   ```
   Frontend envía: "Reflectivo"
   Backend busca: LIKE "%Reflectivo%" 
   NO ENCUENTRA (porque existe "REFLECTIVO")
   CREA NUEVO: "Reflectivo"
   
   Resultado: 2 tipos iguales con diferente nombre
   ```

2. **Inyección de datos inválidos:**
   ```
   Frontend envía: tipo: "'; DROP TABLE tipos_procesos; --"
   Backend: insertGetId(['nombre' => "'; DROP ..."])
   SQL Injection risk (aunque Laravel escapa)
   ```

3. **No respeta constraints de BD:**
   ```
   Si tipos_procesos tiene UNIQUE constraint en nombre
   Y llega dos procesos con mismo nombre simultáneamente
   RACE CONDITION → Uno falla, el otro éxito
   Inconsistencia de datos
   ```

**SOLUCIÓN RECOMENDADA:**

Mantener tabla de tipos_procesos cerrada, precargada:
```php
// database/seeders/TiposProcesoSeeder.php
class TiposProcesoSeeder extends Seeder
{
    public function run()
    {
        $tipos = [
            'Reflectivo',
            'Bordado',
            'Estampado',
            'DTF',
            'Sublimado',
            'Tejido',
        ];
        
        foreach ($tipos as $tipo) {
            TipoProceso::firstOrCreate(
                ['nombre' => $tipo],
                ['descripcion' => "Proceso: {$tipo}", 'activo' => true]
            );
        }
    }
}
```

En PrendaProcesoService:
```php
if (!$tipoProcesoId) {
    // NO CREAR, solo throw
    throw new InvalidProcessTypeException(
        "Tipo de proceso '{$tipoNombre}' no válido. Tipos permitidos: " .
        implode(', ', TipoProceso::pluck('nombre')->toArray())
    );
}
```

---

#### 🟠 ALTA: Sin validación de ubicaciones

```php
'ubicaciones' => !empty($proceso['ubicaciones']) ? json_encode($proceso['ubicaciones']) : null,
```

**PROBLEMA:**
```
Frontend podría enviar:
ubicaciones: []  ← Array vacío
json_encode([]) = "[]"  ← Válido JSON pero sin datos útiles

Resultado: 
    pedidos_procesos_prenda_detalles.ubicaciones = "[]"
    Usuario no sabe dónde se aplica el proceso
```

**VALIDACIÓN RECOMENDADA:**
```php
if (!empty($proceso['ubicaciones'])) {
    // Validar que sea array no vacío
    $ubicaciones = (array) $proceso['ubicaciones'];
    $ubicacionesValidas = array_filter($ubicaciones, fn($u) => !empty(trim((string)$u)));
    
    if (empty($ubicacionesValidas)) {
        throw new InvalidProcessDataException('Proceso sin ubicaciones válidas');
    }
    
    'ubicaciones' => json_encode($ubicacionesValidas),
} else {
    'ubicaciones' => null,
}
```

---

## 7️⃣ DATABASE - CONSTRAINTS & CASCADE

### Schema Vulnerabilities

####  Falta: Constraints que eviten huérfanos

**Actual:**
```sql
CREATE TABLE pedidos_procesos_prenda_detalles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    prenda_pedido_id BIGINT,
    tipo_proceso_id BIGINT,
    ubicaciones JSON,
    --  NO HAY: FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id) ON DELETE CASCADE
);
```

**RIESGO:**
- Eliminar una prenda: los procesos quedan huérfanos
- Inconsistencia referencial

**SOLUCIÓN:**
```sql
ALTER TABLE pedidos_procesos_prenda_detalles
ADD CONSTRAINT fk_prenda_pedido_id
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id)
    ON DELETE CASCADE;
```

---

####  Falta: Índices en búsquedas comunes

```php
// En controller
$procesosEnBD = \DB::table('pedidos_procesos_prenda_detalles')
    ->whereIn('prenda_pedido_id', $pedido->prendas->pluck('id'))
    ->count();  // ← Query sin índice = SCAN COMPLETO en tablas grandes
```

**SOLUCIÓN:**
```sql
CREATE INDEX idx_prenda_pedido_id ON pedidos_procesos_prenda_detalles(prenda_pedido_id);
CREATE INDEX idx_pedido_epp_id ON pedido_epp_imagenes(pedido_epp_id);
CREATE INDEX idx_prenda_fotos_pedido_id ON prenda_fotos_pedido(prenda_pedido_id);
```

---

## 📋 MATRIZ DE RIESGOS

| # | Componente | Problema | Probabilidad | Impacto | Mitigation |
|---|-----------|----------|--------------|--------|-----------|
| 1 | GestorPendido | Índices desincronizados | Alta | Pérdida de procesos/telas | Usar `data-prenda-id` |
| 2 | Builder | JSON.stringify destruye Files | Media | Imágenes perdidas | No JSON.stringify en Files |
| 3 | Builder | Procesos como array → {} | Media | Procesos perdidos | Detectar array y convertir |
| 4 | Controller | FormData vs JSON mismatch | Alta | 422 Bad Request | Middleware normalizador |
| 5 | Controller | Archivos no procesados | Alta | Imágenes perdidas | Implementar file handler |
| 6 | Transacción | EPP fuera de transacción | Media | Huérfanos en BD | Mover dentro de transacción |
| 7 | Domain | Crear tipos dinámicamente | Media | Duplicación de tipos | Tabla precargada, throw en error |
| 8 | Domain | Sin validation ubicaciones | Baja | Procesos sin datos útiles | Validar no vacío |
| 9 | Database | Sin ForeignKey CASCADE | Media | Registros huérfanos | Agregar ON DELETE CASCADE |
| 10 | Database | Sin índices | Baja (pero lenta) | Performance degraded | Crear índices estratégicos |

---

## 🎯 PLAN DE REMEDIACIÓN

### FASE 1: CRÍTICA (Implementar INMEDIATAMENTE)

**1. Arreglar sincronización de índices**
- [ ] Cambiar GestorPendido.recopilarDatosDelDOM() usar `data-prenda-id`
- Archivo: [public/js/modulos/crear-pedido/gestores/gestor-pedido-sin-cotizacion.js](public/js/modulos/crear-pedido/gestores/gestor-pedido-sin-cotizacion.js)

**2. Normalizar envío frontend (FormData vs JSON)**
- [ ] Crear middleware NormalizeRequestPayload
- [ ] Detectar Content-Type y normalizar
- Archivo: `app/Http/Middleware/NormalizeRequestPayload.php`

**3. Procesar archivos en controller**
- [ ] Implementar manejador de FormData en crearPedido()
- [ ] Extraer `files_prenda_*` y `files_epp_*`
- Archivo: [app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php](app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php)

### FASE 2: ALTA (Semana 1)

**4. Agregar validación en guardarProcesosPrenda()**
- [ ] Validar ubicaciones no vacías
- [ ] Usar tabla precargada de tipos_procesos
- Archivo: [app/Domain/Pedidos/Services/PrendaProcesoService.php](app/Domain/Pedidos/Services/PrendaProcesoService.php)

**5. Mover EPP dentro de transacción**
- [ ] Incluir $eppService->guardarEppsDelPedido() dentro de DB::transaction()
- Archivo: [app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php](app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php)

**6. Agregar deduplicación de EPP**
- [ ] Agrupar EPP iguales antes de guardar
- Archivo: [app/Services/PedidoEppService.php](app/Services/PedidoEppService.php)

### FASE 3: MEDIA (Semana 2)

**7. Validar estructura de procesos en builder**
- [ ] Detectar si procesos es array y convertir a object
- Archivo: [public/js/pedidos-produccion/PedidoCompletoUnificado.js](public/js/pedidos-produccion/PedidoCompletoUnificado.js)

**8. No JSON.stringify en Files**
- [ ] Preservar File objects en memoria
- Archivo: [public/js/pedidos-produccion/PedidoCompletoUnificado.js](public/js/pedidos-produccion/PedidoCompletoUnificado.js)

**9. Agregar ForeignKey constraints**
- [ ] Crear migration para ON DELETE CASCADE
- Archivo: `database/migrations/2026_01_26_add_fk_cascades.php`

### FASE 4: BAJA (Cuando haya tiempo)

**10. Agregar índices**
- [ ] Índices en prenda_pedido_id, pedido_epp_id, etc.
- Archivo: `database/migrations/2026_01_26_add_indexes.php`

**11. Mejorar logging**
- [ ] Contar procesos/telas/imagenes en cada etapa
- Archivo: Múltiples

---

## CHECKLIST PRODUCCIÓN

### ANTES de ir a producción:

- [ ] **Prendas:** GestorPendido usa data-prenda-id (no índices)
- [ ] **Procesos:** Tabla tipos_procesos precargada, sin inserts dinámicos
- [ ] **Telas:** PedidoCompletoUnificado preserva estructura
- [ ] **Imágenes:** FormData enviado Y procesado en controller
- [ ] **Imágenes:** No hay JSON.stringify en File objects
- [ ] **EPP:** Dentro de transacción con Pedido
- [ ] **EPP:** Sin duplicados (agrupar antes de guardar)
- [ ] **BD:** ForeignKey constraints con ON DELETE CASCADE
- [ ] **BD:** Índices en columnas de búsqueda común
- [ ] **Validación:** CrearPedidoCompletoRequest validar estructura completa
- [ ] **Testing:** Test end-to-end: crear pedido con procesos + telas + imágenes + EPP
- [ ] **Logging:** Verificar que cada etapa loguea counts

---

## 📚 REFERENCIAS

- **FormData MDN:** https://developer.mozilla.org/en-US/docs/Web/API/FormData
- **Laravel FormRequest:** https://laravel.com/docs/10.x/validation#form-request-validation
- **DB::transaction:** https://laravel.com/docs/10.x/database#database-transactions
- **Intervention Image:** https://image.intervention.io/
- **WebP compression:** https://developers.google.com/speed/webp

---

**Fin del Análisis**
