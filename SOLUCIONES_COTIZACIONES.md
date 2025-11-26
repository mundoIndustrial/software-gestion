# ✅ SOLUCIONES PROPUESTAS PARA COTIZACIONES

## Solución Completa Paso a Paso

---

## 1️⃣ REFACTORIZAR: Extraer métodos comunes

### Crear nuevo método privado: `processFormInputs()`

**Ubicación:** Agregar después del método `__construct()` o al final de la clase

```php
/**
 * Procesar y validar inputs del formulario
 * Extrae lógica común para evitar duplicación
 */
private function processFormInputs(Request $request): array
{
    // Recopilar todos los inputs
    $productos = $request->input('productos', []);
    $tecnicas = $request->input('tecnicas', []);
    $ubicacionesRaw = $request->input('ubicaciones', []);
    $imagenes = $request->input('imagenes', []);
    $especificacionesGenerales = $request->input('especificaciones', []);
    
    // Procesar observaciones (una sola vez)
    $observacionesGenerales = $this->processObservaciones($request);
    
    // Procesar ubicaciones
    $ubicaciones = $this->processUbicaciones($ubicacionesRaw);
    
    // Convertir especificaciones a array si es necesario
    if (!is_array($especificacionesGenerales)) {
        $especificacionesGenerales = (array) $especificacionesGenerales;
    }
    
    return [
        'productos' => $productos,
        'tecnicas' => $tecnicas,
        'ubicaciones' => $ubicaciones,
        'imagenes' => $imagenes,
        'especificaciones' => $especificacionesGenerales,
        'observaciones' => $observacionesGenerales
    ];
}

/**
 * Procesar observaciones SOLO UNA VEZ
 */
private function processObservaciones(Request $request): array
{
    $observacionesTexto = $request->input('observaciones_generales', []);
    $observacionesCheck = $request->input('observaciones_check', []);
    $observacionesValor = $request->input('observaciones_valor', []);
    
    $observacionesGenerales = [];
    
    foreach ($observacionesTexto as $index => $obs) {
        if (!empty($obs)) {
            $checkValue = $observacionesCheck[$index] ?? null;
            $tipo = ($checkValue === 'on') ? 'checkbox' : 'texto';
            $valor = ($tipo === 'texto') ? ($observacionesValor[$index] ?? '') : '';
            
            $observacionesGenerales[] = [
                'texto' => $obs,
                'tipo' => $tipo,
                'valor' => $valor
            ];
        }
    }
    
    return $observacionesGenerales;
}

/**
 * Procesar ubicaciones
 */
private function processUbicaciones(array $ubicacionesRaw): array
{
    $ubicaciones = [];
    
    if (!is_array($ubicacionesRaw)) {
        return $ubicaciones;
    }
    
    foreach ($ubicacionesRaw as $item) {
        if (is_array($item) && isset($item['seccion'])) {
            $ubicaciones[] = $item;
        } else {
            $ubicaciones[] = [
                'seccion' => 'GENERAL',
                'ubicaciones_seleccionadas' => [$item]
            ];
        }
    }
    
    return $ubicaciones;
}

/**
 * Detectar tipo de prenda (JEAN, PANTALÓN, etc.)
 */
private function detectarTipoPrenda(string $nombrePrenda): array
{
    if (empty($nombrePrenda)) {
        return [
            'esJeanPantalon' => false,
            'palabraPrincipal' => ''
        ];
    }
    
    $nombreUpper = strtoupper(trim($nombrePrenda));
    $palabraPrincipal = explode(' ', $nombreUpper)[0] ?? '';
    $esJeanPantalon = (bool)preg_match('/^JEAN|^PANTALÓ?N/', $palabraPrincipal);
    
    return [
        'esJeanPantalon' => $esJeanPantalon,
        'palabraPrincipal' => $palabraPrincipal
    ];
}
```

---

## 2️⃣ AGREGAR VALIDACIÓN

### Crear FormRequest dedicado

**Crear archivo:** `app/Http/Requests/StoreCotizacionRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCotizacionRequest extends FormRequest
{
    /**
     * Autorizar la solicitud
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Obtener las reglas de validación
     */
    public function rules(): array
    {
        return [
            'cliente' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'tipo' => 'required|in:borrador,enviada',
            'tipo_cotizacion' => 'nullable|string',
            'cotizacion_id' => 'nullable|integer|exists:cotizaciones,id',
            
            'productos' => 'required_if:tipo,enviada|array',
            'productos.*.nombre_producto' => 'required|string|max:255',
            'productos.*.descripcion' => 'nullable|string',
            'productos.*.tallas' => 'array',
            'productos.*.tallas.*' => 'string',
            'productos.*.variantes' => 'array',
            'productos.*.variantes.color' => 'nullable|string|max:100',
            'productos.*.variantes.tela' => 'nullable|string|max:100',
            'productos.*.variantes.genero' => 'nullable|string|in:hombre,mujer,niño,unisex',
            'productos.*.variantes.tipo' => 'nullable|string',
            
            'tecnicas' => 'array',
            'tecnicas.*' => 'string',
            
            'ubicaciones' => 'array',
            'ubicaciones.*' => 'string',
            
            'imagenes' => 'array',
            'imagenes.*' => 'url',
            
            'especificaciones' => 'array',
            'observaciones_generales' => 'array',
            'observaciones_check' => 'array',
            'observaciones_valor' => 'array',
        ];
    }

    /**
     * Mensajes de error personalizados
     */
    public function messages(): array
    {
        return [
            'cliente.required' => 'El nombre del cliente es requerido',
            'cliente.regex' => 'El cliente contiene caracteres no permitidos',
            'tipo.required' => 'El tipo de cotización es requerido',
            'tipo.in' => 'Tipo de cotización inválido',
            'productos.required_if' => 'Los productos son requeridos para cotizaciones enviadas',
            'productos.*.nombre_producto.required' => 'Cada producto debe tener un nombre',
        ];
    }
}
```

### Usar el FormRequest en el controller

```php
use App\Http\Requests\StoreCotizacionRequest;

public function guardar(StoreCotizacionRequest $request)
{
    try {
        $validado = $request->validated();
        
        // El resto del código continúa con $validado
        // que garantiza que los datos son correctos
```

---

## 3️⃣ AGREGAR TRANSACCIÓN COMPLETA

### Reescribir método `guardar()`

```php
/**
 * Guardar cotización o borrador (nueva o actualización)
 * 
 * @param StoreCotizacionRequest $request
 * @return \Illuminate\Http\JsonResponse
 */
public function guardar(StoreCotizacionRequest $request)
{
    $validado = $request->validated();
    
    try {
        $tipo = $validado['tipo'] ?? 'borrador';
        $cotizacionId = $validado['cotizacion_id'] ?? null;
        
        // Si existe ID, es actualización
        if ($cotizacionId) {
            return $this->actualizarBorrador($request, $cotizacionId);
        }

        // INICIAR TRANSACCIÓN
        DB::beginTransaction();
        
        try {
            // 1. Procesar inputs
            $datosFormulario = $this->processFormInputs($request);
            
            // 2. Generar número de cotización si es enviada
            $numeroCotizacion = null;
            if ($tipo === 'enviada') {
                $numeroCotizacion = $this->generarNumeroCotizacion();
            }
            
            // 3. Obtener tipo de cotización
            $tipoCotizacion = null;
            $tipoCodigo = $validado['tipo_cotizacion'] ?? null;
            if ($tipoCodigo) {
                $tipoCotizacion = \App\Models\TipoCotizacion::where('codigo', $tipoCodigo)->first();
            }
            
            // 4. Crear cotización
            $datos = [
                'user_id' => Auth::id(),
                'numero_cotizacion' => $numeroCotizacion,
                'tipo_cotizacion_id' => $tipoCotizacion?->id,
                'fecha_inicio' => now(),
                'cliente' => $validado['cliente'],
                'asesora' => auth()->user()?->name ?? 'Sin nombre',
                'es_borrador' => ($tipo === 'borrador'),
                'estado' => 'enviada',
                'fecha_envio' => ($tipo === 'enviada') ? now() : null,
                'productos' => $datosFormulario['productos'] ?? null,
                'especificaciones' => $datosFormulario['especificaciones'] ?? null,
                'imagenes' => $datosFormulario['imagenes'] ?? null,
                'tecnicas' => $datosFormulario['tecnicas'] ?? null,
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'ubicaciones' => $datosFormulario['ubicaciones'] ?? null,
                'observaciones_generales' => $datosFormulario['observaciones'] ?? null
            ];
            
            $cotizacion = Cotizacion::create($datos);
            
            // 5. Crear prendas
            if (!empty($datosFormulario['productos'])) {
                $this->crearPrendasCotizacion($cotizacion, $datosFormulario['productos']);
            }
            
            // 6. Crear logo/bordado/estampado (SOLO UNA VEZ, con datos procesados)
            $logoCotizacionData = [
                'cotizacion_id' => $cotizacion->id,
                'imagenes' => $datosFormulario['imagenes'],
                'tecnicas' => $datosFormulario['tecnicas'],
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas'),
                'ubicaciones' => $datosFormulario['ubicaciones'],
                'observaciones_generales' => $datosFormulario['observaciones']
            ];
            
            \App\Models\LogoCotizacion::create($logoCotizacionData);
            
            // 7. Crear historial
            \App\Models\HistorialCotizacion::create([
                'cotizacion_id' => $cotizacion->id,
                'tipo_cambio' => 'creacion',
                'descripcion' => 'Cotización creada',
                'usuario_id' => Auth::id(),
                'usuario_nombre' => auth()->user()?->name ?? 'Sin nombre',
                'ip_address' => request()->ip()
            ]);
            
            // CONFIRMAR TRANSACCIÓN
            DB::commit();
            
            \Log::info('Cotización creada exitosamente', [
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'tipo' => $tipo
            ]);
            
            return response()->json([
                'success' => true,
                'message' => ($tipo === 'borrador') 
                    ? 'Cotización guardada en borradores' 
                    : 'Cotización enviada correctamente',
                'cotizacion_id' => $cotizacion->id
            ]);
            
        } catch (\Exception $e) {
            // REVERTIR TODO EN CASO DE ERROR
            DB::rollBack();
            
            \Log::error('Error al crear cotización', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            throw $e;
        }
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Datos inválidos',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al guardar cotización: ' . $e->getMessage(),
            'debug' => config('app.debug') ? [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ], 500);
    }
}

/**
 * Crear prendas de una cotización
 */
private function crearPrendasCotizacion(\App\Models\Cotizacion $cotizacion, array $productos): void
{
    foreach ($productos as $index => $producto) {
        $tallas = is_array($producto['tallas'] ?? []) ? $producto['tallas'] : [];
        $nombrePrenda = $producto['nombre_producto'] ?? '';
        
        // Detectar tipo de prenda
        $tipoPrenda = $this->detectarTipoPrenda($nombrePrenda);
        
        // Obtener género de las variantes
        $genero = null;
        if (is_array($producto['variantes'] ?? null) && isset($producto['variantes']['genero'])) {
            $genero = $producto['variantes']['genero'];
        }
        
        // Crear prenda
        $prenda = \App\Models\PrendaCotizacionFriendly::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => $nombrePrenda,
            'genero' => $genero,
            'es_jean_pantalon' => $tipoPrenda['esJeanPantalon'],
            'tipo_jean_pantalon' => $tipoPrenda['esJeanPantalon'] 
                ? ($producto['variantes']['tipo'] ?? null) 
                : null,
            'descripcion' => $producto['descripcion'] ?? null,
            'tallas' => $tallas,
            'fotos' => [],
            'telas' => [],
            'estado' => 'Pendiente'
        ]);
        
        // Guardar variantes
        $this->guardarVariantesPrenda($prenda, $producto);
    }
}
```

---

## 4️⃣ REPARAR shell_exec - Escapar argumentos

### Reescribir conversión de imágenes

```php
/**
 * Convertir imagen a WebP de forma segura
 */
private function convertirImagenAWebP(
    string $rutaOriginal, 
    string $rutaTemporal
): bool {
    try {
        // Usar escapeshellarg para seguridad
        $rutaOriginalEscapada = escapeshellarg($rutaOriginal);
        $rutaTempporalEscapada = escapeshellarg($rutaTemporal);
        
        // Intentar con cwebp
        if ($this->comandoDisponible('cwebp')) {
            $comando = sprintf(
                'cwebp -q 80 %s -o %s',
                $rutaOriginalEscapada,
                $rutaTempporalEscapada
            );
            
            @shell_exec($comando . " 2>&1");
            
            if (file_exists($rutaTemporal) && filesize($rutaTemporal) > 0) {
                return true;
            }
        }
        
        // Intentar con GD
        if (extension_loaded('gd')) {
            return $this->convertirConGD($rutaOriginal, $rutaTemporal);
        }
        
        return false;
        
    } catch (\Exception $e) {
        \Log::warning('Error al convertir imagen: ' . $e->getMessage());
        return false;
    }
}

/**
 * Verificar si comando está disponible (seguro)
 */
private function comandoDisponible(string $comando): bool
{
    $comandoEscapado = escapeshellarg($comando);
    
    // Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $salida = @shell_exec("where {$comandoEscapado} 2>nul");
    } else {
        // Unix/Linux
        $salida = @shell_exec("which {$comandoEscapado} 2>/dev/null");
    }
    
    return !empty($salida);
}

/**
 * Convertir con librería GD
 */
private function convertirConGD(string $rutaOriginal, string $rutaTemporal): bool
{
    try {
        $contenidoOriginal = file_get_contents($rutaOriginal);
        $imagen = @imagecreatefromstring($contenidoOriginal);
        
        if ($imagen !== false) {
            @imagewebp($imagen, $rutaTemporal, 80);
            @imagedestroy($imagen);
            
            return file_exists($rutaTemporal) && filesize($rutaTemporal) > 0;
        }
        
        return false;
        
    } catch (\Exception $e) {
        \Log::warning('Error al convertir con GD: ' . $e->getMessage());
        return false;
    }
}
```

---

## 5️⃣ IMPLEMENTAR heredarVariantesDePrendaPedido

### Crear el método faltante

```php
/**
 * Heredar variantes de una prenda de cotización a una prenda de pedido
 * 
 * @param \App\Models\Cotizacion $cotizacion
 * @param \App\Models\PrendaPedido $prenda
 * @param int $index
 */
private function heredarVariantesDePrendaPedido(
    \App\Models\Cotizacion $cotizacion,
    \App\Models\PrendaPedido $prenda,
    int $index
): void {
    try {
        // Obtener prenda de cotización
        $prendaCotizacion = $cotizacion->prendasCotizaciones->get($index);
        
        if (!$prendaCotizacion) {
            \Log::warning('Prenda de cotización no encontrada', [
                'cotizacion_id' => $cotizacion->id,
                'index' => $index
            ]);
            return;
        }
        
        // Heredar variantes existentes
        $variantes = $prendaCotizacion->variantes;
        
        foreach ($variantes as $variante) {
            VariantePrenda::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_prenda_id' => $variante->tipo_prenda_id,
                'color_id' => $variante->color_id,
                'tela_id' => $variante->tela_id,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_broche_id' => $variante->tipo_broche_id,
                'tiene_bolsillos' => $variante->tiene_bolsillos,
                'tiene_reflectivo' => $variante->tiene_reflectivo,
                'descripcion_adicional' => $variante->descripcion_adicional,
                'cantidad_talla' => $variante->cantidad_talla
            ]);
        }
        
        \Log::info('Variantes heredadas', [
            'prenda_pedido_id' => $prenda->id,
            'cantidad_variantes' => count($variantes)
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error al heredar variantes', [
            'error' => $e->getMessage(),
            'cotizacion_id' => $cotizacion->id,
            'prenda_pedido_id' => $prenda->id
        ]);
    }
}
```

---

## 6️⃣ OPTIMIZAR LOGS

### Eliminar logs innecesarios

**ANTES (Logs innecesarios):**
```php
\Log::info('🚀 MÉTODO GUARDAR LLAMADO');
\Log::info('Guardando cotización', [...]);
\Log::info('Tipo de cotización recibido', [...]);
foreach ($observacionesCheck as $idx => $val) {
    \Log::info("Check[$idx] = " . json_encode($val)); // ← LOOP LOG
}
```

**DESPUÉS (Solo eventos importantes):**
```php
// Solo registrar eventos críticos
\Log::info('Cotización creada', [
    'id' => $cotizacion->id,
    'numero' => $cotizacion->numero_cotizacion,
    'usuario_id' => Auth::id(),
    'tipo' => $tipo
]);
```

---

## 7️⃣ CREAR AUTORIZACIÓN ADICIONAL

### Validar autorización en guardar()

```php
/**
 * Guardar cotización o borrador (nueva o actualización)
 */
public function guardar(StoreCotizacionRequest $request)
{
    $validado = $request->validated();
    
    // ✅ NUEVAS VALIDACIONES DE SEGURIDAD
    
    // Si tiene ID, verificar que sea del usuario actual
    if ($validado['cotizacion_id'] ?? null) {
        $cotizacion = Cotizacion::findOrFail($validado['cotizacion_id']);
        
        if ($cotizacion->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para actualizar esta cotización'
            ], 403);
        }
        
        // Solo se pueden actualizar borradores
        if (!$cotizacion->es_borrador) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden actualizar cotizaciones enviadas'
            ], 403);
        }
    }
    
    // Resto del código...
}
```

---

## Cambios Resumidos

| Problema | Solución | Archivo |
|----------|----------|---------|
| Duplicación | Métodos privados reutilizables | CotizacionesController |
| Observaciones 2x | Una sola vez en `processObservaciones()` | CotizacionesController |
| Sin validación | FormRequest `StoreCotizacionRequest` | nuevo archivo |
| Variables reasignadas | Eliminadas, refactorizadas | CotizacionesController |
| Sin transacción | `DB::beginTransaction()...DB::commit()` | CotizacionesController |
| shell_exec inseguro | `escapeshellarg()` | CotizacionesController |
| Método faltante | `heredarVariantesDePrendaPedido()` | CotizacionesController |
| Logs excesivos | Reducidos a eventos críticos | CotizacionesController |
| Sin autorización completa | Validaciones adicionales en `guardar()` | CotizacionesController |

