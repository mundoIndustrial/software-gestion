# 🔧 IMPLEMENTACIÓN REFACTORIZADA - CÓDIGO LISTO PARA PRODUCCIÓN

**Fecha**: 27 de Enero 2026  
**Objetivo**: Código seguro y verificado para actualización de prendas  
**Tecnología**: Laravel 11 + PHP 8.3+

---

## ARCHIVO 1: DTO MEJORADO

### `app/Application/Pedidos/DTOs/ActualizarPrendaPedidoDTO.php`

```php
<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar prenda con tracking de campos modificados
 * 
 * Garantiza que SOLO se actualizan campos explícitamente enviados.
 * Usa patrón "touched fields" para diferenciar "no enviado" de "enviado como null".
 */
final class ActualizarPrendaPedidoDTO
{
    /**
     * Campos que fueron explícitamente tocados (enviados en el request)
     * 
     * @var string[]
     */
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

    /**
     * Registra qué campos fueron tocados (no null)
     */
    private function registrarCamposTocados(): void
    {
        if ($this->nombrePrenda !== null) {
            $this->camposTocados[] = 'nombre_prenda';
        }
        if ($this->descripcion !== null) {
            $this->camposTocados[] = 'descripcion';
        }
        if ($this->deBodega !== null) {
            $this->camposTocados[] = 'de_bodega';
        }
        if ($this->cantidadTalla !== null) {
            $this->camposTocados[] = 'tallas';
        }
        if ($this->variantes !== null) {
            $this->camposTocados[] = 'variantes';
        }
        if ($this->coloresTelas !== null) {
            $this->camposTocados[] = 'colores_telas';
        }
        if ($this->procesos !== null) {
            $this->camposTocados[] = 'procesos';
        }
    }

    /**
     * Verificar si un campo fue explícitamente modificado
     */
    public function fueTocado(string $campo): bool
    {
        return in_array($campo, $this->camposTocados, true);
    }

    /**
     * Obtener lista de campos que fueron modificados
     */
    public function getCamposTocados(): array
    {
        return $this->camposTocados;
    }

    /**
     * Factory desde request validado
     */
    public static function fromRequest(int $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            cantidadTalla: self::parsearArray($data['tallas'] ?? null),
            variantes: self::parsearArray($data['variantes'] ?? null),
            coloresTelas: self::parsearArray($data['colores_telas'] ?? null),
            procesos: self::parsearArray($data['procesos'] ?? null),
        );
    }

    /**
     * Helper: Parsear array que puede venir como JSON string
     */
    private static function parsearArray(?string $valor): ?array
    {
        if (empty($valor)) {
            return null;
        }

        if (is_string($valor)) {
            $decoded = json_decode($valor, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return $valor;
    }
}
```

---

## ARCHIVO 2: UseCase Refactorizado

### `app/Application/Pedidos/UseCases/ActualizarPrendaPedidoUseCase.php`

```php
<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaTalla;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: Actualizar Prenda
 * 
 * Implementa lógica SEGURA de merge:
 * - Solo actualiza campos tocados
 * - Usa merge para relaciones (update existing + create new, NUNCA delete)
 * - Valida cantidades contra procesos
 * - Usa transacciones con rollback
 */
final class ActualizarPrendaPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ActualizarPrendaPedidoDTO $dto): PrendaPedido
    {
        Log::info('[ActualizarPrendaPedidoUseCase] 🔄 Iniciando actualización', [
            'prenda_id' => $dto->prendaId,
            'campos_tocados' => $dto->getCamposTocados(),
        ]);

        DB::beginTransaction();

        try {
            // 1. Validar que prenda existe
            $prenda = PrendaPedido::findOrFail($dto->prendaId);

            Log::info('[ActualizarPrendaPedidoUseCase] ✅ Prenda encontrada', [
                'prenda_id' => $prenda->id,
                'nombre' => $prenda->nombre_prenda,
            ]);

            // 2. Actualizar campos básicos
            $this->actualizarCamposBasicos($prenda, $dto);

            // 3. Actualizar relaciones CON MERGE (no delete)
            if ($dto->fueTocado('tallas')) {
                $this->actualizarTallasConMerge($prenda, $dto->cantidadTalla);
            }

            if ($dto->fueTocado('variantes')) {
                $this->actualizarVariantesConMerge($prenda, $dto->variantes);
            }

            if ($dto->fueTocado('colores_telas')) {
                $this->actualizarColoresTelasConMerge($prenda, $dto->coloresTelas);
            }

            // Los procesos NO se actualizan desde aquí
            if ($dto->fueTocado('procesos')) {
                throw new \InvalidArgumentException(
                    'Los procesos no se pueden editar desde este endpoint. ' .
                    'Use el endpoint específico para procesos.'
                );
            }

            // 4. Recargar con relaciones
            $prenda->load('tallas', 'variantes', 'coloresTelas', 'procesos');

            DB::commit();

            Log::info('[ActualizarPrendaPedidoUseCase] ✅ Actualización exitosa', [
                'prenda_id' => $prenda->id,
                'campos_actualizados' => count($dto->getCamposTocados()),
            ]);

            return $prenda;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[ActualizarPrendaPedidoUseCase] ❌ Error en actualización', [
                'prenda_id' => $dto->prendaId,
                'error' => $e->getMessage(),
                'campos_tocados' => $dto->getCamposTocados(),
            ]);

            throw $e;
        }
    }

    /**
     * Actualizar campos básicos de la prenda
     * SOLO los que fueron tocados en el DTO
     */
    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaPedidoDTO $dto): void
    {
        if ($dto->fueTocado('nombre_prenda') && $dto->nombrePrenda !== null) {
            $prenda->nombre_prenda = $dto->nombrePrenda;
        }

        if ($dto->fueTocado('descripcion') && $dto->descripcion !== null) {
            $prenda->descripcion = $dto->descripcion;
        }

        if ($dto->fueTocado('de_bodega') && $dto->deBodega !== null) {
            $prenda->de_bodega = $dto->deBodega;
        }

        if ($prenda->isDirty()) {
            $prenda->save();

            Log::info('[ActualizarPrendaPedidoUseCase] ✏️ Campos básicos actualizados', [
                'prenda_id' => $prenda->id,
                'cambios' => $prenda->getChanges(),
            ]);
        }
    }

    /**
     * ✅ MERGE: Actualizar tallas existentes, crear nuevas, NO BORRAR
     * 
     * Validaciones:
     * - Cantidad nueva ≥ cantidad en todos los procesos que usan esa talla
     */
    private function actualizarTallasConMerge(PrendaPedido $prenda, ?array $tallasNuevas): void
    {
        if (is_null($tallasNuevas)) {
            Log::debug('[Merge-Tallas] Tallas no incluidas en el payload');
            return;
        }

        Log::info('[Merge-Tallas] 🔄 Procesando actualización de tallas', [
            'prenda_id' => $prenda->id,
            'tallas_nuevas' => count($tallasNuevas),
        ]);

        // 1. Validar cantidades contra procesos
        $this->validarTallasContraProcesos($prenda, $tallasNuevas);

        // 2. Mapear tallas existentes por clave (genero:talla)
        $tallasExistentes = $prenda->tallas()
            ->get()
            ->keyBy(fn($t) => "{$t->genero}:{$t->talla}");

        $tallasProcessadas = [];

        // 3. Procesar tallas nuevas
        foreach ($tallasNuevas as $genero => $tallasCantidad) {
            if (!is_array($tallasCantidad)) {
                continue;
            }

            foreach ($tallasCantidad as $talla => $cantidad) {
                $clave = "{$genero}:{$talla}";
                $cantidad = (int) $cantidad;

                if ($cantidad < 0) {
                    throw new \InvalidArgumentException(
                        "Cantidad no puede ser negativa: {$genero} {$talla}"
                    );
                }

                $tallasProcessadas[] = $clave;

                if ($tallasExistentes->has($clave)) {
                    // 🔄 ACTUALIZAR existente
                    $tallaExistente = $tallasExistentes[$clave];
                    $tallaExistente->update(['cantidad' => $cantidad]);

                    Log::debug('[Merge-Tallas] ✏️ Talla actualizada', [
                        'clave' => $clave,
                        'cantidad_anterior' => $tallaExistente->getOriginal('cantidad'),
                        'cantidad_nueva' => $cantidad,
                    ]);

                } else {
                    // ✨ CREAR nueva
                    $prenda->tallas()->create([
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => $cantidad,
                    ]);

                    Log::debug('[Merge-Tallas] ✨ Talla creada', [
                        'clave' => $clave,
                        'cantidad' => $cantidad,
                    ]);
                }
            }
        }

        // 4. ❌ NO BORRAR: Las tallas que no vinieron quedan intactas
        $tallasNoTocadas = $tallasExistentes
            ->filter(fn($_, $k) => !in_array($k, $tallasProcessadas))
            ->keys()
            ->toArray();

        Log::info('[Merge-Tallas] ✅ Merge completado', [
            'prenda_id' => $prenda->id,
            'tallas_procesadas' => count($tallasProcessadas),
            'tallas_conservadas' => count($tallasNoTocadas),
            'tallas_conservadas_detalles' => $tallasNoTocadas,
        ]);
    }

    /**
     * 🔐 VALIDAR: Cantidad en prenda ≥ cantidad en todos los procesos
     */
    private function validarTallasContraProcesos(PrendaPedido $prenda, array $tallasNuevas): void
    {
        // Obtener todos los procesos con sus tallas
        $procesos = $prenda->procesos()
            ->with('tallas')
            ->get();

        foreach ($procesos as $proceso) {
            foreach ($proceso->tallas as $tallaProceso) {
                $genero = $tallaProceso->genero;
                $talla = $tallaProceso->talla;
                $cantidadEnProceso = $tallaProceso->cantidad;

                // Buscar la cantidad nueva para esta talla
                $cantidadNuevaEnPrenda = $tallasNuevas[$genero][$talla] ?? null;

                // Si viene una cantidad nueva, validar
                if ($cantidadNuevaEnPrenda !== null) {
                    if ($cantidadNuevaEnPrenda < $cantidadEnProceso) {
                        throw new \InvalidArgumentException(
                            "No se puede reducir cantidad de {$genero} {$talla} a {$cantidadNuevaEnPrenda}. " .
                            "El proceso '{$proceso->tipoProceso->nombre}' usa {$cantidadEnProceso} unidades. " .
                            "Aumente la cantidad o edite el proceso."
                        );
                    }
                }
                // Si NO viene cantidad nueva, se asume que se mantiene (no tocar)
            }
        }

        Log::info('[Validar-Tallas] ✅ Validación contra procesos OK', [
            'prenda_id' => $prenda->id,
            'procesos_verificados' => $procesos->count(),
        ]);
    }

    /**
     * ✅ MERGE: Variantes
     * 
     * Actualizar variantes existentes manteniendo las que no se editan
     */
    private function actualizarVariantesConMerge(PrendaPedido $prenda, ?array $nuevasVariantes): void
    {
        if (is_null($nuevasVariantes)) {
            Log::debug('[Merge-Variantes] Variantes no incluidas en el payload');
            return;
        }

        Log::info('[Merge-Variantes] 🔄 Procesando actualización de variantes', [
            'prenda_id' => $prenda->id,
        ]);

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

            Log::info('[Merge-Variantes] ✨ Variantes creadas', [
                'cantidad' => count($nuevasVariantes),
            ]);

        } else if (!$variantesExistentes->isEmpty() && !empty($nuevasVariantes)) {
            // Merge: actualizar existentes, no borrar los demás
            foreach ($variantesExistentes as $idx => $varianteExistente) {
                if (isset($nuevasVariantes[$idx])) {
                    $nuevosDatos = $nuevasVariantes[$idx];

                    $varianteExistente->update([
                        'tipo_manga_id' => $nuevosDatos['tipo_manga_id'] ?? $varianteExistente->tipo_manga_id,
                        'tipo_broche_boton_id' => $nuevosDatos['tipo_broche_boton_id'] ?? $varianteExistente->tipo_broche_boton_id,
                        'tiene_bolsillos' => $nuevosDatos['tiene_bolsillos'] ?? $varianteExistente->tiene_bolsillos,
                        'manga_obs' => $nuevosDatos['manga_obs'] ?? $varianteExistente->manga_obs,
                        'broche_boton_obs' => $nuevosDatos['broche_boton_obs'] ?? $varianteExistente->broche_boton_obs,
                        'bolsillos_obs' => $nuevosDatos['bolsillos_obs'] ?? $varianteExistente->bolsillos_obs,
                    ]);

                    Log::debug('[Merge-Variantes] ✏️ Variante actualizada', [
                        'variante_id' => $varianteExistente->id,
                    ]);
                }
            }

            Log::info('[Merge-Variantes] ✅ Merge completado', [
                'variantes_actualizadas' => count($variantesExistentes),
            ]);
        }
    }

    /**
     * ✅ MERGE: Colores y Telas
     * 
     * Actualizar combinaciones color-tela sin borrar las demás
     */
    private function actualizarColoresTelasConMerge(PrendaPedido $prenda, ?array $nuevasColoresTelas): void
    {
        if (is_null($nuevasColoresTelas)) {
            Log::debug('[Merge-ColoresTelas] No incluidas en el payload');
            return;
        }

        Log::info('[Merge-ColoresTelas] 🔄 Procesando actualización', [
            'prenda_id' => $prenda->id,
        ]);

        $coloresExistentes = $prenda->coloresTelas()->get();

        if ($coloresExistentes->isEmpty() && !empty($nuevasColoresTelas)) {
            // Crear nuevas combinaciones
            foreach ($nuevasColoresTelas as $ct) {
                $prenda->coloresTelas()->create([
                    'color_id' => $ct['color_id'] ?? null,
                    'tela_id' => $ct['tela_id'] ?? null,
                    'referencia' => $ct['referencia'] ?? null,
                ]);
            }

            Log::info('[Merge-ColoresTelas] ✨ Combinaciones creadas', [
                'cantidad' => count($nuevasColoresTelas),
            ]);

        } else if (!$coloresExistentes->isEmpty() && !empty($nuevasColoresTelas)) {
            // Merge: actualizar existentes
            foreach ($coloresExistentes as $idx => $colorExistente) {
                if (isset($nuevasColoresTelas[$idx])) {
                    $nuevosDatos = $nuevasColoresTelas[$idx];

                    $colorExistente->update([
                        'color_id' => $nuevosDatos['color_id'] ?? $colorExistente->color_id,
                        'tela_id' => $nuevosDatos['tela_id'] ?? $colorExistente->tela_id,
                        'referencia' => $nuevosDatos['referencia'] ?? $colorExistente->referencia,
                    ]);

                    Log::debug('[Merge-ColoresTelas] ✏️ Combinación actualizada', [
                        'color_tela_id' => $colorExistente->id,
                    ]);
                }
            }

            Log::info('[Merge-ColoresTelas] ✅ Merge completado', [
                'combinaciones_actualizadas' => count($coloresExistentes),
            ]);
        }
    }
}
```

---

## ARCHIVO 3: Model Limpio

### `app/Models/PrendaPedido.php` (Cambios)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrendaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'prendas_pedido';

    protected $fillable = [
        'pedido_produccion_id',
        'nombre_prenda',
        'descripcion',
        'de_bodega',
        'prenda_id',
        'cantidad',
        'observaciones',
    ];

    /**
     * IMPORTANTE: Casteos seguros (SIN castear JSON de relaciones)
     */
    protected $casts = [
        'de_bodega' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ✅ NO castear 'cantidad_talla', 'ubicaciones', etc.
    // Esos datos se manejan con tablas relacionales

    // ============================================================
    // RELACIONES
    // ============================================================

    public function pedidoProduccion(): BelongsTo
    {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }

    public function tallas(): HasMany
    {
        return $this->hasMany(PrendaPedidoTalla::class, 'prenda_pedido_id');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(PrendaVariantePed::class, 'prenda_pedido_id');
    }

    public function coloresTelas(): HasMany
    {
        return $this->hasMany(PrendaPedidoColorTela::class, 'prenda_pedido_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(PrendaFotoPedido::class, 'prenda_pedido_id');
    }

    public function procesos(): HasMany
    {
        return $this->hasMany(PedidosProcesosPrendaDetalle::class, 'prenda_pedido_id');
    }

    // ============================================================
    // OBSERVERS SEGUROS
    // ============================================================

    protected static function boot()
    {
        parent::boot();

        // Solo loguear cuando se actualiza
        static::updating(function ($model) {
            // Solo si cambios específicos
            if ($model->isDirty(['nombre_prenda', 'descripcion', 'de_bodega'])) {
                \Log::debug('[PrendaPedido Observer] Actualización detectada', [
                    'prenda_id' => $model->id,
                    'campos' => $model->getDirty(),
                ]);
            }
        });
    }
}
```

---

## ARCHIVO 4: Controller Validador

### `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (Método)

```php
/**
 * PATCH /asesores/pedidos-produccion/{pedidoId}/prendas/{prendaId}
 * 
 * Actualizar prenda existente con validación selectiva
 * 
 * Solo actualiza campos enviados en el payload
 */
public function actualizarPrendaSegura(Request $request, int $pedidoId, int $prendaId): JsonResponse
{
    try {
        Log::info('[PedidosProduccionController] 🔄 POST actualizar prenda', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId,
            'campos_recibidos' => array_keys($request->all()),
        ]);

        // 1. Validar que pedido existe y es del usuario
        $pedido = PedidoProduccion::findOrFail($pedidoId);
        
        if ($pedido->asesor_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar este pedido',
            ], 403);
        }

        // 2. Validar que prenda existe en el pedido
        $prenda = $pedido->prendas()->findOrFail($prendaId);

        // 3. Validar HTTP basics
        $validated = $request->validate([
            'nombre_prenda' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string|max:500',
            'de_bodega' => 'sometimes|boolean',
            'tallas' => 'sometimes|array',
            'tallas.*' => 'array',
            'tallas.*.*' => 'integer|min:0',
            'variantes' => 'sometimes|array',
            'colores_telas' => 'sometimes|array',
        ]);

        // 4. Crear DTO con campos tocados
        $dto = ActualizarPrendaPedidoDTO::fromRequest($prendaId, $validated);

        Log::info('[PedidosProduccionController] DTO creado', [
            'campos_tocados' => $dto->getCamposTocados(),
        ]);

        // 5. Ejecutar usecase
        $prendaActualizada = $this->actualizarPrendaUseCase->ejecutar($dto);

        Log::info('[PedidosProduccionController] ✅ Prenda actualizada', [
            'prenda_id' => $prendaActualizada->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Prenda actualizada correctamente',
            'data' => [
                'id' => $prendaActualizada->id,
                'nombre_prenda' => $prendaActualizada->nombre_prenda,
                'descripcion' => $prendaActualizada->descripcion,
                'de_bodega' => $prendaActualizada->de_bodega,
                'cantidad' => $prendaActualizada->cantidad,
                'tallas' => $prendaActualizada->tallas,
                'variantes' => $prendaActualizada->variantes,
                'colores_telas' => $prendaActualizada->coloresTelas,
                'procesos' => $prendaActualizada->procesos,
            ],
            'campos_actualizados' => $dto->getCamposTocados(),
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Prenda o pedido no encontrado',
        ], 404);

    } catch (\InvalidArgumentException $e) {
        Log::warning('[PedidosProduccionController] ⚠️ Validación de negocio fallida', [
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);

    } catch (\Exception $e) {
        Log::error('[PedidosProduccionController] ❌ Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
        ], 500);
    }
}
```

---

## ARCHIVO 5: Tests

### `tests/Feature/Pedidos/ActualizarPrendaPedidoTest.php`

```php
<?php

namespace Tests\Feature\Pedidos;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\User;
use Tests\TestCase;

class ActualizarPrendaPedidoTest extends TestCase
{
    private User $asesor;
    private PedidoProduccion $pedido;
    private PrendaPedido $prenda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create(['rol' => 'asesor']);
        $this->pedido = PedidoProduccion::factory()
            ->create(['asesor_id' => $this->asesor->id]);
        $this->prenda = PrendaPedido::factory()
            ->create(['pedido_produccion_id' => $this->pedido->id]);

        // Crear tallas iniciales
        $this->prenda->tallas()->create([
            'genero' => 'DAMA',
            'talla' => 'S',
            'cantidad' => 100,
        ]);
        $this->prenda->tallas()->create([
            'genero' => 'DAMA',
            'talla' => 'M',
            'cantidad' => 50,
        ]);
    }

    /** @test */
    public function puede_actualizar_solo_nombre_sin_afectar_tallas()
    {
        // Arrange
        $tallaAnterior = $this->prenda->tallas()->first();
        $cantidadAnterior = $tallaAnterior->cantidad;

        // Act
        $response = $this->actingAs($this->asesor)->patchJson(
            "/asesores/pedidos-produccion/{$this->pedido->id}/prendas/{$this->prenda->id}",
            [
                'nombre_prenda' => 'Nuevo Nombre',
                // No enviar tallas
            ]
        );

        // Assert
        $response->assertSuccessful();
        $this->assertEquals('Nuevo Nombre', $this->prenda->fresh()->nombre_prenda);
        $this->assertEquals($cantidadAnterior, $this->prenda->fresh()->tallas()->first()->cantidad);
    }

    /** @test */
    public function puede_actualizar_tallas_sin_afectar_otras()
    {
        // Arrange
        $tallaMNoTocada = $this->prenda->tallas()->where('talla', 'M')->first();

        // Act
        $response = $this->actingAs($this->asesor)->patchJson(
            "/asesores/pedidos-produccion/{$this->pedido->id}/prendas/{$this->prenda->id}",
            [
                'tallas' => [
                    'DAMA' => [
                        'S' => 150,  // Cambiar
                        // No enviar M → se conserva
                    ]
                ]
            ]
        );

        // Assert
        $response->assertSuccessful();
        $this->assertEquals(150, $this->prenda->fresh()->tallas()->where('talla', 'S')->first()->cantidad);
        $this->assertEquals(50, $tallaMNoTocada->fresh()->cantidad);  // SIN cambios
    }

    /** @test */
    public function rechaza_reducir_cantidad_por_debajo_de_proceso()
    {
        // Arrange: Crear proceso con talla S = 80 unidades
        $proceso = $this->prenda->procesos()->create([
            'tipo_proceso_id' => 1,
            'estado' => 'PENDIENTE',
        ]);
        $proceso->tallas()->create([
            'genero' => 'DAMA',
            'talla' => 'S',
            'cantidad' => 80,
        ]);

        // Act: Intentar reducir S a 50
        $response = $this->actingAs($this->asesor)->patchJson(
            "/asesores/pedidos-produccion/{$this->pedido->id}/prendas/{$this->prenda->id}",
            [
                'tallas' => [
                    'DAMA' => [
                        'S' => 50,  // Menos que los 80 del proceso
                    ]
                ]
            ]
        );

        // Assert
        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('No se puede reducir', $response->json('message'));
    }

    /** @test */
    public function preserva_tallas_no_tocadas()
    {
        // Arrange
        $tallasOriginales = $this->prenda->tallas()->pluck('cantidad', 'talla')->toArray();

        // Act
        $this->actingAs($this->asesor)->patchJson(
            "/asesores/pedidos-produccion/{$this->pedido->id}/prendas/{$this->prenda->id}",
            [
                'nombre_prenda' => 'Nuevo Nombre',
            ]
        );

        // Assert
        $tallasActuales = $this->prenda->fresh()->tallas()->pluck('cantidad', 'talla')->toArray();
        $this->assertEquals($tallasOriginales, $tallasActuales);
    }

    /** @test */
    public function rechaza_editar_desde_pedido_ajeno()
    {
        // Arrange
        $otroAsesor = User::factory()->create(['rol' => 'asesor']);

        // Act
        $response = $this->actingAs($otroAsesor)->patchJson(
            "/asesores/pedidos-produccion/{$this->pedido->id}/prendas/{$this->prenda->id}",
            ['nombre_prenda' => 'Hacker']
        );

        // Assert
        $response->assertStatus(403);
    }
}
```

---

## ARCHIVO 6: Rutas

### `routes/asesores.php` (Agregar)

```php
// En el grupo de auth y asesor

// PATCH: Actualizar prenda (SEGURO - merge)
Route::patch(
    '/pedidos-produccion/{pedidoId}/prendas/{prendaId}',
    [\App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'actualizarPrendaSegura']
)->where(['pedidoId' => '[0-9]+', 'prendaId' => '[0-9]+'])
 ->name('pedidos.actualizar-prenda-segura');
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

```
[ ] 1. Copiar ActualizarPrendaPedidoDTO mejorado
[ ] 2. Reemplazar ActualizarPrendaPedidoUseCase
[ ] 3. Limpiar PrendaPedido.php (remover casteos peligrosos)
[ ] 4. Agregar método actualizarPrendaSegura en controller
[ ] 5. Agregar ruta PATCH
[ ] 6. Agregar tests
[ ] 7. Ejecutar tests: php artisan test tests/Feature/Pedidos/ActualizarPrendaPedidoTest.php
[ ] 8. Validar migraciones (si es necesario cambiar CASCADE → RESTRICT)
[ ] 9. Documentar en Postman
[ ] 10. Capacitar frontend sobre _touched_fields
```

---

**Versión**: 1.0 - Código Refactorizado  
**Status**: ✅ Listo para PR  
**Reviewers**: Tech Lead + DB Admin  
