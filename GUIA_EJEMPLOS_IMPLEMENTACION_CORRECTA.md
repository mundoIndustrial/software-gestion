# 📚 GUÍA DE IMPLEMENTACIÓN CORRECTA - Ejemplos Prácticos

##  Objetivo

Proporcionar ejemplos código **CORRECTO** e **INCORRECTO** para que cualquier futura implementación respete el modelo de las 7 tablas transaccionales.

---

## 1️⃣ CREAR UNA PRENDA

###  INCORRECTO (Inventar columnas)

```php
//  MAL: Intentar guardar imágenes en prendas_pedido
PrendaPedido::create([
    'pedido_produccion_id' => $pedidoId,
    'nombre_prenda' => 'Camiseta',
    'imagenes_path' => '/storage/prendas/...',  //  NO EXISTE
    'imagenes' => [...],                        //  NO EXISTE
    'procesos' => [...],                        //  NO EXISTE
    'variantes' => [...],                       //  NO EXISTE
]);
```

###  CORRECTO (Separar en sus tablas)

```php
// 1. Crear prenda (SOLO datos de prenda)
$prenda = PrendaPedido::create([
    'pedido_produccion_id' => $pedidoId,
    'nombre_prenda' => 'Camiseta',
    'descripcion' => 'Camiseta de drill...',
    'cantidad_talla' => json_encode(['XS' => 2, 'S' => 3]),  // JSON
    'genero' => json_encode(['Dama', 'Caballero']),           // JSON
    'de_bodega' => true,
]);

// 2. Guardar imágenes (en su tabla)
if ($imagenes) {
    foreach ($imagenes as $idx => $imagen) {
        PrendaFotoPedido::create([
            'prenda_pedido_id' => $prenda->id,
            'ruta_original' => $imagen['original'],
            'ruta_webp' => $imagen['webp'],
            'orden' => $idx,
        ]);
    }
}

// 3. Guardar variantes (en su tabla)
if ($variantes) {
    PrendaPedidoVariante::create([
        'prenda_pedido_id' => $prenda->id,
        'tipo_manga_id' => $variantes['manga_id'],
        'tipo_broche_boton_id' => $variantes['broche_id'],
        'manga_obs' => $variantes['obs_manga'],
        'tiene_bolsillos' => $variantes['bolsillos'],
        'bolsillos_obs' => $variantes['obs_bolsillos'],
    ]);
}

// 4. Guardar telas (en su tabla)
if ($telas) {
    foreach ($telas as $tela) {
        PrendaPedidoColorTela::create([
            'prenda_pedido_id' => $prenda->id,
            'color_id' => $tela['color_id'],
            'tela_id' => $tela['tela_id'],
        ]);
    }
}

// 5. Guardar procesos (en su tabla)
if ($procesos) {
    foreach ($procesos as $proceso) {
        $detalle = PedidoProcesoPrendaDetalle::create([
            'prenda_pedido_id' => $prenda->id,
            'tipo_proceso_id' => $proceso['tipo_id'],
            'ubicaciones' => json_encode($proceso['ubicaciones']),
            'observaciones' => $proceso['obs'],
            'estado' => 'PENDIENTE',
        ]);
        
        // Guardar imágenes del proceso
        if ($proceso['imagenes']) {
            foreach ($proceso['imagenes'] as $idx => $img) {
                PedidoProcesoimagen::create([
                    'proceso_prenda_detalle_id' => $detalle->id,
                    'ruta_original' => $img['original'],
                    'ruta_webp' => $img['webp'],
                    'orden' => $idx,
                    'es_principal' => $idx === 0,
                ]);
            }
        }
    }
}
```

---

## 2️⃣ ACTUALIZAR UNA PRENDA

###  INCORRECTO (Guardar todo en prendas_pedido)

```php
//  MAL: Intentar actualizar imágenes en prenda
PrendaPedido::find($prendaId)->update([
    'nombre_prenda' => 'Nuevo nombre',
    'imagenes' => [...],           //  NO EXISTE
    'imagenes_path' => '...',      //  NO EXISTE
]);
```

###  CORRECTO (Actualizar cada tabla)

```php
// 1. Actualizar datos de prenda
PrendaPedido::where('id', $prendaId)->update([
    'nombre_prenda' => 'Nuevo nombre',
    'descripcion' => 'Nueva descripción',
    'cantidad_talla' => json_encode($tallas),
    'genero' => json_encode($generos),
]);

// 2. Actualizar imágenes (eliminar antiguas, insertar nuevas)
PrendaFotoPedido::where('prenda_pedido_id', $prendaId)->delete();
foreach ($imagenes as $idx => $imagen) {
    PrendaFotoPedido::create([
        'prenda_pedido_id' => $prendaId,
        'ruta_original' => $imagen['original'],
        'ruta_webp' => $imagen['webp'],
        'orden' => $idx,
    ]);
}

// 3. Actualizar variantes
PrendaPedidoVariante::where('prenda_pedido_id', $prendaId)->delete();
if ($variantes) {
    PrendaPedidoVariante::create([
        'prenda_pedido_id' => $prendaId,
        'tipo_manga_id' => $variantes['manga_id'],
        // ... resto de campos
    ]);
}
```

---

## 3️⃣ OBTENER DATOS DE UNA PRENDA

###  INCORRECTO (Asumir columnas que no existen)

```php
//  MAL: Esto falla porque imagenes_path no existe
$prenda = PrendaPedido::with('imagenes_path')
    ->find($prendaId);

echo $prenda->imagenes_path;  // Error: Unknown column
```

###  CORRECTO (Consultar desde sus tablas)

```php
// 1. Obtener prenda
$prenda = PrendaPedido::find($prendaId);

// 2. Obtener imágenes (desde su tabla)
$imagenes = PrendaFotoPedido::where('prenda_pedido_id', $prendaId)
    ->orderBy('orden')
    ->get();

// 3. Obtener variantes (desde su tabla)
$variantes = PrendaPedidoVariante::where('prenda_pedido_id', $prendaId)
    ->with('tipoManga', 'tipoBroche')
    ->first();

// 4. Obtener telas (desde su tabla)
$telas = PrendaPedidoColorTela::where('prenda_pedido_id', $prendaId)
    ->with('color', 'tela')
    ->get();

// 5. Obtener procesos (desde su tabla)
$procesos = PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $prendaId)
    ->with('tipoProceso', 'imagenes')
    ->get();

// Compilar respuesta
return [
    'prenda' => $prenda,
    'imagenes' => $imagenes,
    'variantes' => $variantes,
    'telas' => $telas,
    'procesos' => $procesos,
];
```

---

## 4️⃣ CONSULTAR CON JOINS (RAW SQL)

###  INCORRECTO (Seleccionar columnas inexistentes)

```php
//  MAL: Estos campos no existen
$prendas = DB::table('prendas_pedido')
    ->select('prendas_pedido.*', 'imagenes_path', 'procesos')  //  NO EXISTEN
    ->get();
```

###  CORRECTO (Seleccionar solo lo que existe)

```php
// OPCIÓN 1: Consultas separadas (más legible)
$prendas = DB::table('prendas_pedido')
    ->where('pedido_produccion_id', $pedidoId)
    ->get();

foreach ($prendas as $prenda) {
    $prenda->imagenes = PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->get();
    $prenda->variantes = PrendaPedidoVariante::where('prenda_pedido_id', $prenda->id)->get();
    $prenda->procesos = PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $prenda->id)->get();
}

// OPCIÓN 2: Query única con JOINs (menos queries, más compleja)
$prendas = DB::table('prendas_pedido as p')
    ->leftJoin('prenda_fotos_pedido as pfp', 'p.id', '=', 'pfp.prenda_pedido_id')
    ->leftJoin('prenda_pedido_variantes as ppv', 'p.id', '=', 'ppv.prenda_pedido_id')
    ->select(
        'p.id',
        'p.nombre_prenda',
        'p.descripcion',
        'p.cantidad_talla',
        'pfp.ruta_webp as imagen_ruta',
        'ppv.tipo_manga_id'
    )
    ->where('p.pedido_produccion_id', $pedidoId)
    ->get();

// OPCIÓN 3: Eloquent con relaciones (más limpio)
$prendas = PrendaPedido::where('pedido_produccion_id', $pedidoId)
    ->with('imagenes', 'variantes', 'telas', 'procesos')
    ->get();
```

---

## 5️⃣ SOFT DELETES (Importante)

###  INCORRECTO (No respetar soft deletes)

```php
//  MAL: Obtiene también registros eliminados
$imagenes = DB::table('prenda_fotos_pedido')
    ->where('prenda_pedido_id', $prendaId)
    ->get();  // Esto incluye deleted_at != null
```

###  CORRECTO (Respetar soft deletes)

```php
// Opción 1: Verificar explícitamente
$imagenes = DB::table('prenda_fotos_pedido')
    ->where('prenda_pedido_id', $prendaId)
    ->where('deleted_at', null)  //  Filtrar eliminados
    ->get();

// Opción 2: Usando Eloquent (automático)
$imagenes = PrendaFotoPedido::where('prenda_pedido_id', $prendaId)
    ->get();  //  Eloquent respeta SoftDeletes automáticamente

// Opción 3: Obtener incluidos los eliminados
$imagenes = PrendaFotoPedido::withTrashed()
    ->where('prenda_pedido_id', $prendaId)
    ->get();

// Opción 4: Solo los eliminados
$imagenes = PrendaFotoPedido::onlyTrashed()
    ->where('prenda_pedido_id', $prendaId)
    ->get();
```

---

## 6️⃣ PARSING DE JSON FIELDS

###  INCORRECTO (No verificar tipo)

```php
//  MAL: Asume que siempre es string
$tallas = json_decode($prenda->cantidad_talla, true);  // Puede fallar si es array
```

###  CORRECTO (Verificar tipo primero)

```php
// Forma defensiva
$tallas = [];
if ($prenda->cantidad_talla) {
    if (is_array($prenda->cantidad_talla)) {
        $tallas = $prenda->cantidad_talla;  // Ya es array
    } else if (is_string($prenda->cantidad_talla)) {
        $tallas = json_decode($prenda->cantidad_talla, true) ?? [];  // Parse si string
    }
}

// O con casting automático (en Model)
class PrendaPedido extends Model {
    protected $casts = [
        'cantidad_talla' => 'array',
        'genero' => 'array',
    ];
}

// Entonces siempre es array
$tallas = $prenda->cantidad_talla;  //  Siempre array, nunca string
```

---

## 7️⃣ RELACIONES EN ELOQUENT MODELS

###  INCORRECTO (Nombres de relaciones confusos)

```php
class PrendaPedido extends Model {
    //  Nombres confusos
    public function images() {
        return $this->hasMany(PrendaFotoPedido::class);
    }
    
    public function procs() {
        return $this->hasMany(PedidoProcesoPrendaDetalle::class);
    }
}
```

###  CORRECTO (Nombres consistentes con tabla)

```php
class PrendaPedido extends Model {
    //  Nombres claros = nombre singular de la relación
    public function imagenes() {
        return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
    }
    
    public function variante() {
        return $this->hasOne(PrendaPedidoVariante::class, 'prenda_pedido_id');
    }
    
    public function telas() {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id');
    }
    
    public function procesos() {
        return $this->hasMany(PedidoProcesoPrendaDetalle::class, 'prenda_pedido_id');
    }
}

class PrendaPedidoVariante extends Model {
    public function tipoManga() {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }
    
    public function tipoBroche() {
        return $this->belongsTo(TipoBroche::class, 'tipo_broche_boton_id');
    }
}

class PedidoProcesoPrendaDetalle extends Model {
    public function tipoProceso() {
        return $this->belongsTo(TipoProceso::class, 'tipo_proceso_id');
    }
    
    public function imagenes() {
        return $this->hasMany(PedidoProcesoimagen::class, 'proceso_prenda_detalle_id');
    }
}
```

---

## 8️⃣ VALIDACIÓN DE DATOS

###  INCORRECTO (No validar estructura)

```php
//  MAL: Confiar en los datos del usuario
$prenda = PrendaPedido::create($request->all());
```

###  CORRECTO (Validar antes de persistir)

```php
// Validación en Controller o Request
$validated = $request->validate([
    'nombre_prenda' => 'required|string|max:500',
    'descripcion' => 'nullable|string',
    'cantidad_talla' => 'required|array',
    'cantidad_talla.*' => 'integer|min:1',
    'genero' => 'required|array',
    'genero.*' => 'string',
    'de_bodega' => 'boolean',
    'imagenes' => 'array',
    'imagenes.*.original' => 'string',
    'imagenes.*.webp' => 'string',
    'variantes' => 'nullable|array',
    'telas' => 'nullable|array',
    'procesos' => 'nullable|array',
]);

// Entonces usar datos validados
$prenda = PrendaPedido::create($validated);
```

---

## 9️⃣ ELIMINAR UNA PRENDA (Con Cascada)

###  INCORRECTO (No eliminar relaciones)

```php
//  MAL: Solo elimina prenda, deja imágenes huérfanas
PrendaPedido::find($prendaId)->delete();
```

###  CORRECTO (Cascada de eliminaciones)

```php
// Opción 1: Cascada explícita
$prenda = PrendaPedido::find($prendaId);

// Soft delete de todo (mejor para auditoría)
PrendaFotoPedido::where('prenda_pedido_id', $prendaId)->delete();
PrendaPedidoVariante::where('prenda_pedido_id', $prendaId)->delete();
PrendaPedidoColorTela::where('prenda_pedido_id', $prendaId)->delete();

$procesosIds = PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $prendaId)->pluck('id');
PedidoProcesoimagen::whereIn('proceso_prenda_detalle_id', $procesosIds)->delete();
PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $prendaId)->delete();

$prenda->delete();

// Opción 2: Usar relaciones con cascada (en Migration)
Schema::create('prenda_pedido_variantes', function (Blueprint $table) {
    // ...
    $table->foreign('prenda_pedido_id')
        ->references('id')
        ->on('prendas_pedido')
        ->onDelete('cascade');  // ← Elimina automáticamente
});

// Entonces solo necesitas:
$prenda->delete();  //  Elimina cascada automáticamente
```

---

## 🔟 HELPERS Y UTILS

### Normalizar rutas de imágenes

```php
function normalizarRutaImagen($ruta) {
    $ruta = str_replace('\\', '/', $ruta);
    
    if (strpos($ruta, '/storage/') === 0) {
        return $ruta;  // Ya está normalizada
    }
    
    if (strpos($ruta, 'storage/') === 0) {
        return '/' . $ruta;
    }
    
    if (strpos($ruta, '/') !== 0) {
        return '/storage/' . $ruta;
    }
    
    return $ruta;
}

// Uso
$rutaWebp = normalizarRutaImagen($foto->ruta_webp);  //  Siempre /storage/...
```

### Compilar datos de prenda completos

```php
function compilarDatosPrenda($prendaId) {
    return [
        'prenda' => PrendaPedido::find($prendaId),
        'imagenes' => PrendaFotoPedido::where('prenda_pedido_id', $prendaId)
            ->orderBy('orden')->get(),
        'variantes' => PrendaPedidoVariante::where('prenda_pedido_id', $prendaId)
            ->with('tipoManga', 'tipoBroche')->get(),
        'telas' => PrendaPedidoColorTela::where('prenda_pedido_id', $prendaId)
            ->with('color', 'tela')->get(),
        'procesos' => PedidoProcesoPrendaDetalle::where('prenda_pedido_id', $prendaId)
            ->with('tipoProceso', 'imagenes')->get(),
    ];
}
```

---

##  CHECKLIST FINAL

Antes de hacer cualquier cambio, verificar:

- [ ] ¿Qué tabla tiene realmente este campo?
- [ ] ¿Necesito guardar en múltiples tablas?
- [ ] ¿Estoy respetando soft deletes?
- [ ] ¿Estoy parseando JSON correctamente?
- [ ] ¿Estoy usando la tabla correcta para cada dato?
- [ ] ¿He eliminado columnas inventadas?
- [ ] ¿He testeado en BD antes de deployar?

---

##  CONCLUSIÓN

-  7 tablas transaccionales = 7 propósitos diferentes
-  Cada tabla responsable de su datos
-  JOINs a catálogos solo para referencias
-  Respeta soft deletes siempre
-  Parsea JSON defensivamente
-  Consulta desde tabla correcta

**Resultado:**  **NUNCA MÁS** "Unknown column 'imagenes_path'"

