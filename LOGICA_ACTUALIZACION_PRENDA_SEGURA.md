# 🎯 LÓGICA DE ACTUALIZACIÓN DE PRENDA EN PEDIDO DE PRODUCCIÓN
## Análisis Detallado y Arquitectura Segura

**Fecha**: 27 de Enero 2026  
**Objetivo**: Editar una prenda existente en un pedido sin crear/eliminar registros  
**Status**: ✅ Especificación Completa + Puntos Críticos Identificados

---

## 📊 MODELO DE DATOS ACTUAL

### Estructura Relacional

```
pedidos_produccion
    ├── id (PK)
    ├── numero_pedido
    ├── estado
    └── cantidad_total

    ↓ (1:N) hasMany prendas
    
prenda_pedido
    ├── id (PK)
    ├── pedido_produccion_id (FK)
    ├── nombre_prenda
    ├── descripcion
    ├── de_bodega (BOOLEAN)
    ├── prenda_id (FK→prendas)
    ├── cantidad (INTEGER)
    ├── observaciones
    └── deleted_at (SoftDelete)

    ├── ↓ (1:N) hasMany tallas
    │
    └─ prenda_pedido_tallas
        ├── id (PK)
        ├── prenda_pedido_id (FK)
        ├── genero (DAMA|CABALLERO|UNISEX)
        ├── talla (STRING)
        └── cantidad (INTEGER)

    ├── ↓ (1:N) hasMany variantes
    │
    └─ prenda_variantes_pedido
        ├── id (PK)
        ├── prenda_pedido_id (FK)
        ├── tipo_manga_id (FK)
        ├── tipo_broche_boton_id (FK)
        ├── tiene_bolsillos (BOOLEAN)
        └── observaciones

    ├── ↓ (1:N) hasMany coloresTelas
    │
    └─ prenda_pedido_colores_telas
        ├── id (PK)
        ├── prenda_pedido_id (FK)
        ├── color_id (FK)
        ├── tela_id (FK)
        └── referencia (STRING)

    ├── ↓ (1:N) hasMany fotos
    │
    └─ prenda_fotos_pedido
        ├── id (PK)
        ├── prenda_pedido_id (FK)
        ├── ruta_imagen
        └── tipo (referencia|marca)

    └── ↓ (1:N) hasMany procesos
        
        pedidos_procesos_prenda_detalles
            ├── id (PK)
            ├── prenda_pedido_id (FK) ← CASCADE DELETE
            ├── tipo_proceso_id (FK)
            ├── ubicaciones (JSON)
            ├── observaciones
            ├── estado (PENDIENTE|APROBADO|EN_PRODUCCION)
            ├── datos_adicionales (JSON)
            └── tallas_dama, tallas_caballero (JSON LEGACY)

            └── ↓ (1:N) hasMany tallas_proceso
                
                pedidos_procesos_prenda_tallas
                    ├── id (PK)
                    ├── proceso_prenda_detalle_id (FK)
                    ├── genero (DAMA|CABALLERO|UNISEX)
                    ├── talla (STRING)
                    └── cantidad (INTEGER)
```

### Relaciones Clave

| Tabla Padre | Tabla Hija | Relación | FK | Acción DELETE |
|---|---|---|---|---|
| pedidos_produccion | prenda_pedido | 1:N | pedido_produccion_id | CASCADE |
| prenda_pedido | prenda_pedido_tallas | 1:N | prenda_pedido_id | ❌ PROHIBIDO |
| prenda_pedido | prenda_variantes_pedido | 1:N | prenda_pedido_id | ❌ PROHIBIDO |
| prenda_pedido | prenda_pedido_colores_telas | 1:N | prenda_pedido_id | ❌ PROHIBIDO |
| prenda_pedido | pedidos_procesos_prenda_detalles | 1:N | prenda_pedido_id | CASCADE ⚠️ |
| pedidos_procesos_prenda_detalles | pedidos_procesos_prenda_tallas | 1:N | proceso_prenda_detalle_id | CASCADE |

---

## ⚠️ PROBLEMAS CRÍTICOS CON EL CÓDIGO ACTUAL

### 🔴 Problema #1: DELETE Masivo en ActualizarPrendaPedidoUseCase

**Ubicación**: `ActualizarPrendaPedidoUseCase.php` líneas 69-89

```php
private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
{
    if (is_null($dto->cantidadTalla)) {
        return;  // ✅ Correcto: si no viene, no tocar
    }

    if (empty($dto->cantidadTalla)) {
        $prenda->tallas()->delete();  // 🔴 PELIGRO: ¿Realmente vacío o no enviado?
        return;
    }

    $prenda->tallas()->delete();  // 🔴 CRÍTICO: SIEMPRE BORRA TODO
    foreach ($dto->cantidadTalla as $genero => $tallasCantidad) {
        // ... recrear desde cero
    }
}
```

**Impacto**: 
- ❌ Si alguien edita SOLO el nombre, BORRARÍA TODAS LAS TALLAS
- ❌ Viola la regla "NO eliminar registros existentes"
- ❌ No es un "merge" (mezcla) sino un "replace" (reemplazo)

**Afecta también a**:
- `actualizarVariantes()` - línea 93
- `actualizarColoresTelas()` - línea 113
- `actualizarProcesos()` - línea 133

---

### 🔴 Problema #2: Ambigüedad en el DTO

**Ubicación**: `ActualizarPrendaPedidoDTO.php` línea 33

```php
public readonly ?array $cantidadTalla = null,  // null = "no tocar", [] = "borrar todo"
```

**Conflicto Semántico**:
- ¿`null` = "no enviado" o "usuario quiere borrar"?
- ¿`[]` = "vacío intencional" o "error de parsing"?
- No hay forma de distinguir entre ambos casos

**Escenario Problema**:
```javascript
// Frontend envía solo nombre_prenda
{
  "nombre_prenda": "Polo nuevo"
  // no envía tallas
}

// Backend recibe en fromRequest():
$data['tallas'] ?? null  // ✅ Bien, queda null

// Pero si alguien envía esto:
{
  "nombre_prenda": "Polo nuevo",
  "tallas": null  // Envío explícito null
}

// Backend lo interpreta igual a "no enviado"
// PERO... ¿realmente quiso el usuario mantener tallas o borrar?
```

---

### 🔴 Problema #3: Falta de Validación de Cantidades

**Ubicación**: `ActualizarPrendaPedidoUseCase.php` (línea 69 en adelante)

```php
private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
{
    // ... no hay validación de:
    // 1. ¿Nueva cantidad < cantidad en procesos?
    // 2. ¿Total de tallas = cantidad de prenda?
    // 3. ¿Género válido?
    // 4. ¿Talla válida para ese género?
}
```

**Escenario Problema**:
```php
// Prenda tiene:
// - DAMA S: 100
// - DAMA M: 50
// Total: 150

// Proceso "bordado" usa:
// - DAMA S: 80
// - DAMA M: 50
// Total: 130

// Usuario intenta cambiar a:
// - DAMA S: 30 ← ¡MENOS QUE LO QUE USA EL PROCESO!
// - DAMA M: 50

// Sin validación → INCONSISTENCIA CRÍTICA
```

---

### 🔴 Problema #4: Modelos con Casteos Problemáticos

**Ubicación**: `PedidosProcesosPrendaDetalle.php` línea 26-31

```php
protected $casts = [
    'ubicaciones' => 'array',
    'tallas_dama' => 'array',        // ⚠️ JSON LEGACY
    'tallas_caballero' => 'array',   // ⚠️ JSON LEGACY
    'datos_adicionales' => 'array',
];
```

**Problema**: 
- El modelo tiene AMBAS fuentes de datos:
  - **Legacy**: `tallas_dama`, `tallas_caballero` (JSON en DB)
  - **Nuevo**: `pedidos_procesos_prenda_tallas` (tabla relacional)
- Si actualizas uno, el otro queda desincronizado
- ¿Qué sucede si alguien llama a `$proceso->tallas_dama = [...]` vs `$proceso->tallas()->update(...)`?

---

### 🟡 Problema #5: Relaciones en Cascada Sin Protección

**Ubicación**: `2026_01_28_add_foreign_keys_cascade_and_indexes.php` línea 32-35

```php
$table->foreign('prenda_pedido_id')
    ->references('id')
    ->on('prenda_pedido')
    ->onDelete('cascade');  // ← Si borro prenda, borro TODOS los procesos
```

**Problema**:
- Un usuario NO DEBERÍA poder borrar una prenda si tiene procesos asignados
- El `CASCADE` es una "red de seguridad", pero NO es lo ideal
- Debería ser `RESTRICT` + mensaje de error al usuario

---

## ✅ LÓGICA CORRECTA DE UPDATE PARCIAL (MERGE)

### Principios Fundamentales

1. **Si el campo NO viene en el payload → NO se toca**
2. **Si viene null explícitamente → se interpreta como "sin valor" para ese campo**
3. **Para relaciones (tallas, variantes, etc.): MERGE, no REPLACE**
4. **SIEMPRE validar antes de guardar**
5. **SIEMPRE usar transacciones**

### Arquitectura de 3 Capas

```
Frontend (JS/Form)
    ↓ (FormData con cambios detectados)
Controller (PedidosProduccionController)
    ↓ (Validación básica HTTP)
DTO (ActualizarPrendaPedidoDTO)
    ↓ (Transformación + limpieza)
UseCase (ActualizarPrendaPedidoUseCase)
    ↓ (Lógica de negocio + validaciones)
Models (PrendaPedido + relaciones)
    ↓ (Persistencia en BD)
Resultado (JSON + estado)
```

---

## 🔧 IMPLEMENTACIÓN SEGURA

### Paso 1: Marcar Campos como "Modificados"

**Solución para DTO**: Usar un array de "campos tocados"

```php
// ActualizarPrendaPedidoDTO.php
final class ActualizarPrendaPedidoDTO
{
    public readonly array $camposTocados;  // ['nombre_prenda', 'tallas', ...]
    
    public function __construct(
        public readonly int $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $deBodega = null,
        public readonly ?array $cantidadTalla = null,
        public readonly ?array $variantes = null,
        public readonly ?array $coloresTelas = null,
        public readonly ?array $procesos = null,
    ) {
        // Registrar qué campos vinieron (no null)
        $this->camposTocados = array_filter([
            $nombrePrenda !== null ? 'nombre_prenda' : null,
            $descripcion !== null ? 'descripcion' : null,
            $deBodega !== null ? 'de_bodega' : null,
            $cantidadTalla !== null ? 'tallas' : null,
            $variantes !== null ? 'variantes' : null,
            $coloresTelas !== null ? 'colores_telas' : null,
            $procesos !== null ? 'procesos' : null,
        ]);
    }

    public static function fromRequest(int $prendaId, array $data): self
    {
        $dto = new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            cantidadTalla: !empty($data['tallas']) 
                ? (is_string($data['tallas']) ? json_decode($data['tallas'], true) : $data['tallas'])
                : null,
            // ... más campos
        );
        
        return $dto;
    }

    public function fueTocado(string $campo): bool
    {
        return in_array($campo, $this->camposTocados);
    }
}
```

---

### Paso 2: UseCase con Merge Real (NO Delete)

```php
// ActualizarPrendaPedidoUseCase.php
final class ActualizarPrendaPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ActualizarPrendaPedidoDTO $dto)
    {
        \DB::beginTransaction();
        
        try {
            $prenda = PrendaPedido::find($dto->prendaId);
            $this->validarObjetoExiste($prenda, 'Prenda', $dto->prendaId);

            // 1. Actualizar SOLO campos básicos que fueron tocados
            $this->actualizarCamposBasicos($prenda, $dto);

            // 2. Actualizar relaciones CON MERGE
            if ($dto->fueTocado('tallas')) {
                $this->actualizarTallasConMerge($prenda, $dto->cantidadTalla);
            }
            
            if ($dto->fueTocado('variantes')) {
                $this->actualizarVariantesConMerge($prenda, $dto->variantes);
            }
            
            if ($dto->fueTocado('colores_telas')) {
                $this->actualizarColoresTelasConMerge($prenda, $dto->coloresTelas);
            }
            
            if ($dto->fueTocado('procesos')) {
                $this->actualizarProcesosConMerge($prenda, $dto->procesos);
            }

            $prenda->load('tallas', 'variantes', 'coloresTelas', 'procesos');
            
            \DB::commit();
            
            \Log::info('[ActualizarPrendaPedidoUseCase] ✅ Actualización exitosa', [
                'prenda_id' => $prenda->id,
                'campos_tocados' => $dto->camposTocados,
            ]);

            return $prenda;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('[ActualizarPrendaPedidoUseCase] ❌ Error', [
                'error' => $e->getMessage(),
                'prenda_id' => $dto->prendaId,
            ]);
            throw $e;
        }
    }

    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        // SOLO actualizar si fueron tocados
        if ($dto->fueTocado('nombre_prenda') && $dto->nombrePrenda !== null) {
            $prenda->nombre_prenda = $dto->nombrePrenda;
        }
        
        if ($dto->fueTocado('descripcion') && $dto->descripcion !== null) {
            $prenda->descripcion = $dto->descripcion;
        }
        
        if ($dto->fueTocado('de_bodega') && $dto->deBodega !== null) {
            $prenda->de_bodega = $dto->deBodega;
        }

        $prenda->save();
    }

    /**
     * ✅ MERGE: Actualizar tallas existentes, crear las nuevas, NO BORRAR
     */
    private function actualizarTallasConMerge(PrendaPedido $prenda, ?array $nuvasTallas): void
    {
        if (is_null($nuvasTallas)) {
            return;  // No hacer nada
        }

        // Validar antes de hacer cambios
        $this->validarTallasContraProcesos($prenda, $nuvasTallas);

        // Mapear tallas existentes por (genero, talla)
        $tallasExistentes = $prenda->tallas()
            ->get()
            ->keyBy(fn($t) => "{$t->genero}:{$t->talla}");

        $tallasNuevasKeys = [];

        foreach ($nuvasTallas as $genero => $tallasCantidad) {
            if (!is_array($tallasCantidad)) {
                continue;
            }

            foreach ($tallasCantidad as $talla => $cantidad) {
                $key = "{$genero}:{$talla}";
                $tallasNuevasKeys[] = $key;

                if ($tallasExistentes->has($key)) {
                    // 🔄 MERGE: actualizar cantidad
                    $tallasExistentes[$key]->update([
                        'cantidad' => (int) $cantidad,
                    ]);
                } else {
                    // ✨ CREAR: nueva talla
                    $prenda->tallas()->create([
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => (int) $cantidad,
                    ]);
                }
            }
        }

        // ❌ NO BORRAR: Las tallas que no vinieron en el payload se conservan
        \Log::info('[MERGE-TALLAS] Tallas actualizadas/creadas', [
            'prenda_id' => $prenda->id,
            'tallas_procesadas' => $tallasNuevasKeys,
            'tallas_conservadas' => $tallasExistentes
                ->filter(fn($_, $k) => !in_array($k, $tallasNuevasKeys))
                ->keys()
                ->toArray(),
        ]);
    }

    /**
     * 🔐 VALIDAR: Cantidad en prenda ≥ cantidad en procesos
     */
    private function validarTallasContraProcesos(PrendaPedido $prenda, array $tallasNuevas): void
    {
        // Obtener cantidad actual usada en procesos
        $procesosConTallas = $prenda->procesos()
            ->with('tallas')
            ->get();

        foreach ($procesosConTallas as $proceso) {
            foreach ($proceso->tallas as $tallaProceso) {
                $genero = $tallaProceso->genero;
                $talla = $tallaProceso->talla;
                $cantidadEnProceso = $tallaProceso->cantidad;

                // Buscar la cantidad nueva en la prenda
                $cantidadNuevaEnPrenda = $tallasNuevas[$genero][$talla] ?? null;

                if ($cantidadNuevaEnPrenda !== null && $cantidadNuevaEnPrenda < $cantidadEnProceso) {
                    throw new \InvalidArgumentException(
                        "No se puede reducir cantidad de {$genero} {$talla} a {$cantidadNuevaEnPrenda}. " .
                        "El proceso '{$proceso->tipoProceso->nombre}' usa {$cantidadEnProceso} unidades."
                    );
                }
            }
        }
    }

    /**
     * ✅ MERGE: Variantes
     */
    private function actualizarVariantesConMerge(PrendaPedido $prenda, ?array $nuevasVariantes): void
    {
        if (is_null($nuevasVariantes)) {
            return;
        }

        // Para variantes: UPSERT por campos únicos si existen
        // Si no hay identificador único, solo actualizar existentes
        $variantesExistentes = $prenda->variantes()->get();

        if ($variantesExistentes->isEmpty() && !empty($nuevasVariantes)) {
            // Crear nuevas variantes
            foreach ($nuevasVariantes as $variante) {
                $prenda->variantes()->create([
                    'tipo_manga_id' => $variante['tipo_manga_id'] ?? null,
                    'tipo_broche_boton_id' => $variante['tipo_broche_boton_id'] ?? null,
                    'tiene_bolsillos' => $variante['tiene_bolsillos'] ?? false,
                    'manga_obs' => $variante['manga_obs'] ?? null,
                    'broche_boton_obs' => $variante['broche_boton_obs'] ?? null,
                    'bolsillos_obs' => $variante['bolsillos_obs'] ?? null,
                ]);
            }
        } else if (!$variantesExistentes->isEmpty() && !empty($nuevasVariantes)) {
            // MERGE: actualizar existentes
            foreach ($variantesExistentes as $idx => $varianteExistente) {
                if (isset($nuevasVariantes[$idx])) {
                    $varianteExistente->update([
                        'tipo_manga_id' => $nuevasVariantes[$idx]['tipo_manga_id'] ?? $varianteExistente->tipo_manga_id,
                        'tipo_broche_boton_id' => $nuevasVariantes[$idx]['tipo_broche_boton_id'] ?? $varianteExistente->tipo_broche_boton_id,
                        'tiene_bolsillos' => $nuevasVariantes[$idx]['tiene_bolsillos'] ?? $varianteExistente->tiene_bolsillos,
                        'manga_obs' => $nuevasVariantes[$idx]['manga_obs'] ?? $varianteExistente->manga_obs,
                        'broche_boton_obs' => $nuevasVariantes[$idx]['broche_boton_obs'] ?? $varianteExistente->broche_boton_obs,
                        'bolsillos_obs' => $nuevasVariantes[$idx]['bolsillos_obs'] ?? $varianteExistente->bolsillos_obs,
                    ]);
                }
                // No borrar variantes que no vienen en el payload
            }
        }
    }

    /**
     * ✅ MERGE: Colores/Telas
     */
    private function actualizarColoresTelasConMerge(PrendaPedido $prenda, ?array $nuevasColoresTelas): void
    {
        if (is_null($nuevasColoresTelas)) {
            return;
        }

        // Similar a variantes: actualizar existentes, crear nuevas, NO BORRAR
        $coloresExistentes = $prenda->coloresTelas()->get();

        if ($coloresExistentes->isEmpty() && !empty($nuevasColoresTelas)) {
            foreach ($nuevasColoresTelas as $ct) {
                $prenda->coloresTelas()->create([
                    'color_id' => $ct['color_id'] ?? null,
                    'tela_id' => $ct['tela_id'] ?? null,
                    'referencia' => $ct['referencia'] ?? null,
                ]);
            }
        } else if (!$coloresExistentes->isEmpty() && !empty($nuevasColoresTelas)) {
            foreach ($coloresExistentes as $idx => $colorExistente) {
                if (isset($nuevasColoresTelas[$idx])) {
                    $colorExistente->update([
                        'color_id' => $nuevasColoresTelas[$idx]['color_id'] ?? $colorExistente->color_id,
                        'tela_id' => $nuevasColoresTelas[$idx]['tela_id'] ?? $colorExistente->tela_id,
                        'referencia' => $nuevasColoresTelas[$idx]['referencia'] ?? $colorExistente->referencia,
                    ]);
                }
            }
        }
    }

    /**
     * ❌ NO ACTUALIZAR procesos
     * 
     * Los procesos se crean automáticamente cuando se crea una prenda
     * y se editan con un endpoint SEPARADO
     */
    private function actualizarProcesosConMerge(PrendaPedido $prenda, ?array $nuevosProcesos): void
    {
        if (is_null($nuevosProcesos)) {
            return;  // No hacer nada si no viene en el payload
        }

        \Log::warning('[ActualizarPrendaPedidoUseCase] ⚠️ Intento de actualizar procesos en endpoint de prenda', [
            'prenda_id' => $prenda->id,
            'procesos_recibidos' => count($nuevosProcesos),
        ]);

        // No permitir editar procesos desde aquí
        throw new \InvalidArgumentException(
            'Los procesos no se pueden editar desde este endpoint. ' .
            'Use el endpoint específico para procesos.'
        );
    }
}
```

---

### Paso 3: DTO con Tracking

```php
// ActualizarPrendaPedidoDTO.php (MEJORADO)
final class ActualizarPrendaPedidoDTO
{
    private array $camposTocados = [];

    public function __construct(
        public readonly int $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $deBodega = null,
        public readonly ?array $cantidadTalla = null,
        public readonly ?array $variantes = null,
        public readonly ?array $coloresTelas = null,
        public readonly ?array $procesos = null,
    ) {
        $this->registrarCamposTocados();
    }

    private function registrarCamposTocados(): void
    {
        if ($this->nombrePrenda !== null) $this->camposTocados[] = 'nombre_prenda';
        if ($this->descripcion !== null) $this->camposTocados[] = 'descripcion';
        if ($this->deBodega !== null) $this->camposTocados[] = 'de_bodega';
        if ($this->cantidadTalla !== null) $this->camposTocados[] = 'tallas';
        if ($this->variantes !== null) $this->camposTocados[] = 'variantes';
        if ($this->coloresTelas !== null) $this->camposTocados[] = 'colores_telas';
        if ($this->procesos !== null) $this->camposTocados[] = 'procesos';
    }

    public function fueTocado(string $campo): bool
    {
        return in_array($campo, $this->camposTocados, true);
    }

    public function getCamposTocados(): array
    {
        return $this->camposTocados;
    }

    public static function fromRequest(int $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            cantidadTalla: !empty($data['tallas']) 
                ? (is_string($data['tallas']) ? json_decode($data['tallas'], true) : $data['tallas'])
                : null,
            variantes: !empty($data['variantes']) 
                ? (is_string($data['variantes']) ? json_decode($data['variantes'], true) : $data['variantes'])
                : null,
            coloresTelas: !empty($data['colores_telas']) 
                ? (is_string($data['colores_telas']) ? json_decode($data['colores_telas'], true) : $data['colores_telas'])
                : null,
            procesos: !empty($data['procesos']) 
                ? (is_string($data['procesos']) ? json_decode($data['procesos'], true) : $data['procesos'])
                : null,
        );
    }
}
```

---

## 🚨 PUNTOS CRÍTICOS DONDE LARAVEL SUELE ROMPER ESTO

### 1️⃣ **Casteos Automáticos en Models**

```php
// ❌ PELIGRO
protected $casts = [
    'cantidad_talla' => 'array',  // JSON → Array automático
];

// Si haces $prenda->cantidad_talla = []; Laravel lo serializa a JSON
// Si luego usas $prenda->tallas()->delete(), pierdes info
```

**Solución**: No castear datos que manejes con relaciones

```php
// ✅ SEGURO
protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    // NO castear cantidad_talla, ubicaciones, etc. si usas tablas relacionales
];
```

---

### 2️⃣ **Observers que Disparan sin Control**

```php
// Si tienes Observer en PrendaPedido...
protected static function boot()
{
    parent::boot();
    
    static::updating(function($model) {
        // ❌ Esto puede dispararse y hacer cosas inesperadas
        // cuando actualizas prenda
    });
}
```

**Solución**: Ser explícito sobre qué dispara

```php
// ✅ SEGURO
protected static function boot()
{
    parent::boot();
    
    static::updating(function($model) {
        // Solo si fue modificado campo específico
        if ($model->isDirty('nombre_prenda')) {
            // ... hacer algo
        }
    });
}
```

---

### 3️⃣ **Relaciones con load() vs with()**

```php
// ❌ PROBLEMA: Carga en dos queries
$prenda->load('tallas', 'variantes', 'procesos');

// Si hay 1000 prendas, N+1 queries

// ✅ SOLUCIÓN: Cargar al inicio
$prenda = PrendaPedido::with('tallas', 'variantes', 'procesos')
    ->find($id);
```

---

### 4️⃣ **sync() vs updateOrCreate() vs Relaciones**

```php
// ❌ NUNCA HAGAS ESTO
$prenda->tallas()->sync($data);  // ← Borra lo que no está en $data

// ❌ NI ESTO
$prenda->tallas()->updateOrCreate(
    ['genero' => $g, 'talla' => $t],
    ['cantidad' => $qty]
);  // ← Si no existe, crea; pero el DTO ya controla creación

// ✅ MEJOR: Controlar todo en el UseCase con lógica explícita
```

---

### 5️⃣ **JSON Payload vs FormData**

```javascript
// ❌ PROBLEMA: Frontend envía JSON
const payload = {
    nombre_prenda: "Polo",
    tallas: null  // Explícitamente null
};

// Backend recibe
$data['tallas'] ?? null  // ← ¿Null porque no vino o porque vino null?

// ✅ SOLUCIÓN: Marcador explícito
const payload = {
    nombre_prenda: "Polo",
    _touched_fields: ['nombre_prenda']  // Qué campos realmente cambiaron
};
```

---

### 6️⃣ **Transacciones Implícitas vs Explícitas**

```php
// ❌ Sin transacción
$prenda->save();           // Query 1
$prenda->tallas()->create(...);  // Query 2
// Si Query 2 falla, Query 1 ya se guardó

// ✅ Con transacción
DB::beginTransaction();
try {
    $prenda->save();
    $prenda->tallas()->create(...);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

### 7️⃣ **Soft Deletes y Relaciones**

```php
// Si PrendaPedido usa SoftDeletes...
protected $casts = [
    'deleted_at' => 'datetime',
];

// ❌ Cuando haces $prenda->tallas()->get()
// Laravel AUTOMÁTICAMENTE filtra soft-deleted
// a menos que uses withTrashed()

// ✅ Sé consciente de esto
$prenda->tallas()->withTrashed()->get();  // Incluye borrados
```

---

## 📋 CHECKLIST DE VALIDACIONES

Antes de actualizar una prenda, validar:

```
[ ] 1. Prenda existe en BD
[ ] 2. Pedido existe y es del usuario actual
[ ] 3. Pedido está en estado editable (NO en producción)
[ ] 4. Cada talla nueva tiene cantidad > 0
[ ] 5. Géneros son válidos (DAMA, CABALLERO, UNISEX)
[ ] 6. Tallas son válidas para el género
[ ] 7. Cantidad nueva en prenda ≥ cantidad en todos los procesos
[ ] 8. Color/Tela existen en catálogos
[ ] 9. Tipo manga/broche existen
[ ] 10. Campos texto no exceden 255 caracteres
[ ] 11. Descripción no excede 500 caracteres
[ ] 12. Ningún campo hace referencia a pedido diferente
```

---

## 🎯 FLUJO CORRECTO DE ACTUALIZACIÓN

```
1. Frontend detecta cambios (campos isDirty)
   ↓
2. Envía solo campos modificados a Controller
   ↓
3. Controller valida HTTP basics (tipos, límites)
   ↓
4. Controller crea DTO con campos marcados como "tocados"
   ↓
5. UseCase recibe DTO y valida lógica de negocio
   ↓
6. UseCase inicia transacción
   ↓
7. Para CADA campo tocado:
   - Si es campo básico: actualizar con save()
   - Si es relación: usar merge (update existentes + create nuevas)
   - NUNCA deletear registros existentes
   ↓
8. Validar consistencia (cantidad vs procesos)
   ↓
9. Si todo OK: commit
   ↓
10. Si error: rollback completo
   ↓
11. Devolver prenda actualizada con relaciones
```

---

## 🛡️ DEFENSA EN PROFUNDIDAD

### Capa 1: Controller
```php
// Validar tipos y formatos HTTP
$validated = $request->validate([
    'nombre_prenda' => 'sometimes|string|max:255',
    'tallas' => 'sometimes|array',
    'tallas.*' => 'array',
]);
```

### Capa 2: DTO
```php
// Transformar y limpiar datos
// Rastrear qué campos fueron tocados
// Parsear JSONs si es necesario
```

### Capa 3: UseCase
```php
// Lógica de negocio
// Validaciones contra procesos
// Transacciones
// Logging detallado
```

### Capa 4: Model
```php
// Relaciones bien definidas
// Casteos seguros
// Sin Observers problemáticos
```

---

## 📊 EJEMPLO DE PAYLOAD CORRECTO

### Request del Frontend

```json
{
  "nombre_prenda": "Polo Premium S2",
  "descripcion": "Nueva descripción",
  "_touched_fields": ["nombre_prenda", "descripcion"]
}
```

O con FormData para archivos:

```javascript
const formData = new FormData();
formData.append('nombre_prenda', 'Polo Premium S2');
formData.append('descripcion', 'Nueva descripción');
formData.append('_touched_fields', JSON.stringify(['nombre_prenda', 'descripcion']));
```

### Response del Backend

```json
{
  "success": true,
  "message": "Prenda actualizada correctamente",
  "data": {
    "id": 5,
    "nombre_prenda": "Polo Premium S2",
    "descripcion": "Nueva descripción",
    "cantidad": 150,
    "de_bodega": false,
    "tallas": [
      { "id": 12, "genero": "DAMA", "talla": "S", "cantidad": 100 },
      { "id": 13, "genero": "DAMA", "talla": "M", "cantidad": 50 }
    ],
    "procesos": [
      { "id": 8, "tipo_proceso": "Bordado", "estado": "PENDIENTE" }
    ]
  },
  "cambios": {
    "campos_actualizados": ["nombre_prenda", "descripcion"],
    "relaciones_sin_cambios": ["tallas", "variantes", "procesos"],
    "timestamp": "2026-01-27T14:35:22Z"
  }
}
```

---

## ⚡ RESUMEN EJECUTIVO

| Aspecto | Problema Actual | Solución |
|---|---|---|
| **Delete masivo** | `$prenda->tallas()->delete()` siempre | Usar merge: update existentes + create nuevas |
| **Ambigüedad null** | ¿null = no tocar o borrar? | Usar array `camposTocados` en DTO |
| **Sin validación** | Cantidad nueva < cantidad en procesos | Validar contra `pedidos_procesos_prenda_tallas` |
| **Casteos confusos** | JSON legacy + tabla relacional | Usar SOLO tabla relacional, remover JSON |
| **Cascades peligrosas** | `onDelete('cascade')` borra procesos | Cambiar a `RESTRICT`, manejar en código |
| **Sin transacciones** | Si fail en paso 2, paso 1 quedó guardado | Transacción DB + rollback en catch |
| **Observers ocultos** | Pueden dispararse inesperadamente | Ser explícito con `isDirty()` |
| **N+1 queries** | `load()` después de crear | Usar `with()` al inicio |

---

## 🚀 PRÓXIMOS PASOS

1. **Actualizar DTO** con `camposTocados`
2. **Reescribir UseCase** con métodos `*ConMerge()`
3. **Agregar validaciones** contra procesos
4. **Remover JSON legacy** (tallas_dama, tallas_caballero)
5. **Cambiar FK** de CASCADE a RESTRICT en procesos
6. **Agregar tests** para cada escenario
7. **Documentar en Postman** los payloads correctos
8. **Capacitar al Frontend** sobre `_touched_fields`

---

**Documento creado**: 27 de Enero 2026  
**Versión**: 1.0 - Especificación Completa  
**Estado**: ✅ Listo para implementación
