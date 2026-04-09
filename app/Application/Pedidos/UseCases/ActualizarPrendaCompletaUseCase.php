<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ActualizarPrendaCompletaUseCaseContract;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\Services\PrendaPostUpdateHydrationService;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Domain\Pedidos\Repositories\PrendaPedidoReadRepository;
use App\Domain\Pedidos\Repositories\PrendaPedidoTallaReadRepository;
use App\Domain\Pedidos\Services\PrendaTransformerServiceContract;
use App\Infrastructure\Services\Pedidos\PrendaAsignacionesColoresUpdaterService;
use App\Infrastructure\Services\Pedidos\PrendaColoresTelasUpdaterService;
use App\Infrastructure\Services\Pedidos\PrendaFotosTelasUpdaterService;
use App\Infrastructure\Services\Pedidos\PrendaImagenDeletionService;
use App\Infrastructure\Services\Pedidos\PrendaProcesosUpdaterService;
use App\Infrastructure\Services\Pedidos\PrendaVariantesUpdaterService;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Schema;

/**
 * Use Case para actualizar una prenda y fotos
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * Responsabilidades:
 * - Validar prenda existe  TRAIT
 * - Actualizar registro en prendas_pedido (nombre, descripción, de_bodega)
 * - Actualizar fotos de referencia (prenda_fotos_pedido)
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Actualizar variantes  ActualizarVariantePrendaUseCase
 * - Actualizar colores y telas  ActualizarColorTelaUseCase
 * - Actualizar tallas  ActualizarTallaPrendaUseCase
 * - Actualizar procesos  ActualizarProcesoPrendaUseCase
 * Antes: 70 lineas | despues: ~60 lineas | Reducción: ~14%
 */
final class ActualizarPrendaCompletaUseCase implements ActualizarPrendaCompletaUseCaseContract
{
    use ManejaPedidosUseCase;

    public function __construct(
        private readonly PrendaPostUpdateHydrationService $postUpdateHydrationService,
        private readonly PrendaTransformerServiceContract $prendaTransformerService,
        private readonly PrendaImagenDeletionService $prendaImagenDeletionService,
        private readonly PrendaVariantesUpdaterService $prendaVariantesUpdaterService,
        private readonly PrendaColoresTelasUpdaterService $prendaColoresTelasUpdaterService,
        private readonly PrendaAsignacionesColoresUpdaterService $prendaAsignacionesColoresUpdaterService,
        private readonly PrendaFotosTelasUpdaterService $prendaFotosTelasUpdaterService,
        private readonly PrendaProcesosUpdaterService $prendaProcesosUpdaterService,
        private readonly PrendaPedidoTallaReadRepository $prendaPedidoTallaReadRepository,
        private readonly PrendaPedidoReadRepository $prendaPedidoReadRepository,
        private readonly PedidoProduccionReadService $pedidoProduccionReadService,
    ) {
    }

    public function ejecutar(ActualizarPrendaCompletaDTO $dto): PrendaPedido
    {
        // CENTRALIZADO: Validar prenda existe (trait)
        $prenda = $this->prendaPedidoReadRepository->obtenerPorId($dto->prendaId);
        
        $this->validarObjetoExiste(
            $prenda,
            'Prenda',
            $dto->prendaId
        );

        \Log::info('[ActualizarPrendaCompletaUseCase] Iniciando actualizacion', [
            'prenda_id' => $dto->prendaId,
            'tiene_variantes' => !is_null($dto->variantes),
            'tiene_colores_telas' => !is_null($dto->coloresTelas),
            'tiene_fotos_telas' => !is_null($dto->fotosTelas),
            'tiene_fotos_telas_procesadas' => !empty($dto->fotosTelasProcesadas),
            'cantidad_fotos_telas_procesadas' => is_array($dto->fotosTelasProcesadas) ? count($dto->fotosTelasProcesadas) : 0,
            'tiene_fotos' => !is_null($dto->fotos),
            'tiene_tallas' => !is_null($dto->cantidadTalla),
            'tiene_imagenes_a_eliminar' => !is_null($dto->imagenesAEliminar),
            'cantidad_imagenes_a_eliminar' => is_array($dto->imagenesAEliminar) ? count($dto->imagenesAEliminar) : 0,
        ]);

        // 1. Actualizar campos basicos
        $this->actualizarCamposBasicos($prenda, $dto);

        // 2. Actualizar fotos de referencia
        $this->actualizarFotos($prenda, $dto);
        // 2.5. Eliminar imágenes marcadas para eliminación
        if (!is_null($dto->imagenesAEliminar)) {
            $this->prendaImagenDeletionService->eliminarImagenes($prenda, $dto->imagenesAEliminar);
        }
        // 3. Actualizar tallas
        $this->actualizarTallas($prenda, $dto);

        // 3.5. Actualizar asignaciones de colores por talla (prenda_pedido_talla_colores)
        $this->prendaAsignacionesColoresUpdaterService->actualizarAsignacionesColores(
            $prenda,
            $dto->asignacionesColores,
            $dto->fotosColorProcesadas
        );

        // 4. Actualizar variantes (manga, broche, bolsillos)
        $this->prendaVariantesUpdaterService->actualizarVariantes($prenda, $dto->variantes);

        // 5. Actualizar colores y telas
        $this->prendaColoresTelasUpdaterService->actualizarColoresTelas($prenda, $dto->coloresTelas);

        // 6. Actualizar fotos de telas
        $this->prendaFotosTelasUpdaterService->actualizarFotosTelas(
            $prenda,
            $dto->fotosTelas,
            $dto->fotosTelasProcesadas
        );

        // 7. Actualizar procesos y sus imagenes
        $this->prendaProcesosUpdaterService->actualizarProcesos(
            $prenda,
            $dto->procesos,
            $dto->fotosProcesoNuevo ?? [],
            $dto->fotosProcesoTallasNuevo ?? []
        );
        // 8. Guardar novedad en pedido_produccion
        // 9. Mantener campo explícito de tipo de flujo de tallas sincronizado
        $this->sincronizarTipoFlujoTallasPersistido($prenda);
        
        // Tocar el updated_at del pedido padre (modificar prenda = actualización del pedido)
        $prenda->pedidoProduccion()->touch();
        
        // DESELECCIONAR PEDIDO para todos los supervisores cuando se actualiza una prenda
        // Esto asegura que cuando se recargue la tabla el pedido no esté marcado
        $this->pedidoProduccionReadService->deselectOrderForAllUsers($prenda->pedido_produccion_id);
        
        return $this->postUpdateHydrationService->hidratarParaRespuesta($prenda);
    }

    public function transformarPrendaParaFactura(PrendaPedido $prenda): array
    {
        return $this->prendaTransformerService->transformarPrendaParaFactura($prenda);
    }

    private function sincronizarTipoFlujoTallasPersistido(PrendaPedido $prenda): void
    {
        if (!Schema::hasColumn('prendas_pedido', 'tipo_flujo_tallas')) {
            return;
        }

        $tieneColoresPorTalla = $prenda->tallas()->whereHas('coloresAsignados')->exists();
        $tieneTallas = $prenda->tallas()->exists();

        $tipo = 'sin_tallas';
        if ($tieneColoresPorTalla) {
            $tipo = 'talla_color';
        } elseif ($tieneTallas) {
            $tipo = 'normal';
        }

        if (($prenda->tipo_flujo_tallas ?? null) !== $tipo) {
            $prenda->tipo_flujo_tallas = $tipo;
            $prenda->save();
        }
    }

    private function actualizarCamposBasicos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        $datosActualizar = [];
        
        if ($dto->nombrePrenda !== null) {
            $datosActualizar['nombre_prenda'] = $dto->nombrePrenda;
        }
        
        if ($dto->descripcion !== null) {
            $datosActualizar['descripcion'] = $dto->descripcion;
        }
        
        if ($dto->deBodega !== null) {
            $datosActualizar['de_bodega'] = $dto->deBodega;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Actualizando campos básicos', [
            'prenda_id' => $prenda->id,
            'datos_a_actualizar' => $datosActualizar,
            'de_bodega_tipo' => gettype($dto->deBodega),
            'de_bodega_valor' => $dto->deBodega,
            'de_bodega_es_null' => is_null($dto->deBodega),
        ]);

        if (!empty($datosActualizar)) {
            $prenda->update($datosActualizar);
            // Recargar desde BD para asegurar que tiene el valor guardado
            $prenda->refresh();
            
            \Log::info('[ActualizarPrendaCompletaUseCase] Campos básicos actualizados', [
                'prenda_id' => $prenda->id,
                'de_bodega_guardado_en_bd' => $prenda->de_bodega,
                'de_bodega_tipo' => gettype($prenda->de_bodega),
                'de_bodega_entero' => (int) $prenda->de_bodega,
            ]);
        }
    }

    private function actualizarFotos(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        //  DEBUG: Log detallado de lo que se recibe
        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarFotos - Iniciando', [
            'prenda_id' => $prenda->id,
            'dto->fotos' => $dto->fotos,
            'es_null' => is_null($dto->fotos),
            'es_empty' => empty($dto->fotos),
            'cantidad_fotos' => is_array($dto->fotos) ? count($dto->fotos) : 'N/A'
        ]);

        // Patron SELECTIVO: Si es null, NO tocar (es actualizacion parcial)
        if (is_null($dto->fotos)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] fotos es NULL - NO MODIFICAR imagenes existentes');
            return;
        }

        if (empty($dto->fotos)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] fotos es array VACIO - ELIMINAR todas las imagenes', [
                'prenda_id' => $prenda->id,
                'fotosActuales' => $prenda->fotos()->count()
            ]);
            $prenda->fotos()->delete();
            return;
        }

        $fotosExistentes = $prenda->fotos()->get()->keyBy(function ($f) {
            return $f->ruta_original;
        });

        $fotosExistentesPorWebp = $prenda->fotos()->get()->keyBy(function ($f) {
            return $f->ruta_webp;
        });

        $fotosNuevas = $this->construirFotosNuevas($dto->fotos);
        $this->insertarFotosNuevas($prenda, $fotosNuevas, $fotosExistentes, $fotosExistentesPorWebp);
    }

    private function construirFotosNuevas(array $fotos): array
    {
        $fotosNuevas = [];

        foreach ($fotos as $idx => $foto) {
            [$ruta, $rutaWebp] = $this->extraerRutasFoto($foto);
            if (!$ruta) {
                continue;
            }

            // Normalizar: quitar prefijo /storage/ si existe (BD guarda sin el)
            $rutaNorm = preg_replace('#^/storage/#', '', $ruta);
            $rutaWebpNorm = $rutaWebp ? preg_replace('#^/storage/#', '', $rutaWebp) : null;

            $fotosNuevas[$rutaNorm] = [
                'ruta_original' => $rutaNorm,
                'ruta_webp' => $rutaWebpNorm,
                'orden' => $idx + 1,
            ];
        }

        return $fotosNuevas;
    }

    private function extraerRutasFoto(mixed $foto): array
    {
        if (is_string($foto)) {
            $ruta = $foto;
            return [$ruta, $ruta ? $this->generarRutaWebp($ruta) : null];
        } elseif (is_array($foto)) {
            $ruta = $foto['ruta_original'] ?? $foto['url'] ?? $foto['ruta'] ?? $foto['path'] ?? null;
            $rutaWebp = $foto['ruta_webp'] ?? ($ruta ? $this->generarRutaWebp($ruta) : null);
            return [$ruta, $rutaWebp];
        }

        return [null, null];
    }

    private function insertarFotosNuevas(
        PrendaPedido $prenda,
        array $fotosNuevas,
        $fotosExistentes,
        $fotosExistentesPorWebp
    ): void {
        foreach ($fotosNuevas as $ruta => $datosFoto) {
            if (!isset($fotosExistentes[$ruta]) && !isset($fotosExistentesPorWebp[$ruta])) {
                $prenda->fotos()->create($datosFoto);
            }
        }
    }
    private function actualizarTallas(PrendaPedido $prenda, ActualizarPrendaCompletaDTO $dto): void
    {
        // Solo actualizar si se proporcionan tallas
        if (is_null($dto->cantidadTalla)) {
            \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - NO tallas provided (null)', [
                'prenda_id' => $prenda->id
            ]);
            return;
        }

        if (empty($dto->cantidadTalla)) {
            // Si viene vacio, eliminar todas las tallas y sus colores asociados
            \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Empty array, deleting all', [
                'prenda_id' => $prenda->id
            ]);

            $this->eliminarTodasLasTallasYColores($prenda);
            return;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Iniciando', [
            'prenda_id' => $prenda->id,
            'dto_cantidad_talla' => $dto->cantidadTalla
        ]);

        $tallasExistentes = $prenda->tallas()->get()->keyBy(function ($t) {
            return "{$t->genero}_{$t->talla}";
        });

        [$tallasNuevas, $generosConDatos] = $this->construirTallasNuevas($dto->cantidadTalla);

        \Log::info('[ActualizarPrendaCompletaUseCase] Tallas nuevas preparadas (selectivo)', [
            'prenda_id' => $prenda->id,
            'cantidad' => count($tallasNuevas),
            'generos_con_datos' => $generosConDatos,
            'tallas_nuevas' => $tallasNuevas
        ]);

        $this->eliminarTallasNoIncluidas($tallasExistentes, $tallasNuevas);
        $this->actualizarOInsertarTallas($prenda, $tallasExistentes, $tallasNuevas);
        $this->registrarEstadoFinalTallas($prenda);
    }

    private function eliminarTodasLasTallasYColores(PrendaPedido $prenda): void
    {
        // IMPORTANTE: Eliminar primero todos los colores asociados
        $tallas = $prenda->tallas()->get();
        $totalColoresEliminados = 0;

        foreach ($tallas as $talla) {
            $coloresEliminados = $talla->coloresAsignados()->delete();
            $totalColoresEliminados += $coloresEliminados;
        }

        \Log::info('[ActualizarPrendaCompletaUseCase] Colores eliminados antes de eliminar tallas', [
            'prenda_id' => $prenda->id,
            'tallas_procesadas' => $tallas->count(),
            'total_colores_eliminados' => $totalColoresEliminados
        ]);

        $prenda->tallas()->delete();
    }

    private function construirTallasNuevas(array $cantidadTalla): array
    {
        $tallasNuevas = [];
        $generosConDatos = [];

        foreach ($cantidadTalla as $genero => $tallasCantidad) {
            if ($this->debeOmitirGenero($genero, $tallasCantidad)) {
                continue;
            }

            $tallasReales = $this->filtrarTallasReales($tallasCantidad);
            if (empty($tallasReales)) {
                \Log::debug('[ActualizarPrendaCompletaUseCase] Genero vacio, preservando existentes', ['genero' => $genero]);
                continue;
            }

            $generoNormalizado = strtoupper($genero);
            $generosConDatos[] = $generoNormalizado;
            $esSobremedida = !empty($tallasCantidad['_es_sobremedida']);

            foreach ($tallasReales as $talla => $cantidad) {
                $key = $generoNormalizado . "_{$talla}";
                $tallasNuevas[$key] = [
                    'genero' => $generoNormalizado,
                    'talla' => $talla,
                    'cantidad' => (int) $cantidad,
                    'es_sobremedida' => (int) $esSobremedida,
                ];
            }
        }

        return [$tallasNuevas, $generosConDatos];
    }

    private function debeOmitirGenero(string $genero, mixed $tallasCantidad): bool
    {
        if (strpos($genero, '_') === 0) {
            return true;
        }

        if (!is_array($tallasCantidad)) {
            return true;
        }

        return false;
    }

    private function filtrarTallasReales(array $tallasCantidad): array
    {
        return array_filter($tallasCantidad, function ($key) {
            return strpos((string) $key, '_') !== 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    private function eliminarTallasNoIncluidas($tallasExistentes, array $tallasNuevas): void
    {
        // FULL REPLACE: Cualquier talla existente que NO este en el nuevo set se elimina.
        foreach ($tallasExistentes as $key => $tallaRecord) {
            if (isset($tallasNuevas[$key])) {
                continue;
            }

            $this->eliminarTallaConColores($key, $tallaRecord);
        }
    }

    private function eliminarTallaConColores(string $key, mixed $tallaRecord): void
    {
        \Log::debug('[ActualizarPrendaCompletaUseCase] Eliminando talla no presente en payload', [
            'key' => $key,
            'talla_id' => $tallaRecord->id,
            'genero' => $tallaRecord->genero,
            'colores_asociados_count' => $tallaRecord->coloresAsignados()->count()
        ]);

        // IMPORTANTE: Eliminar primero los colores asociados (cascade manual)
        try {
            $coloresEliminados = $tallaRecord->coloresAsignados()->delete();
            \Log::debug('[ActualizarPrendaCompletaUseCase] Colores eliminados en cascada', [
                'talla_id' => $tallaRecord->id,
                'colores_eliminados' => $coloresEliminados
            ]);

            $tallaEliminada = $tallaRecord->delete();
            \Log::debug('[ActualizarPrendaCompletaUseCase] Talla eliminada', [
                'talla_id' => $tallaRecord->id,
                'talla_eliminada' => $tallaEliminada,
                'key' => $key
            ]);

            $tallaDespuesDeEliminar = $this->prendaPedidoTallaReadRepository
                ->obtenerPorId((int) $tallaRecord->id);
            \Log::debug('[ActualizarPrendaCompletaUseCase] Verificacion post-eliminacion', [
                'talla_id' => $tallaRecord->id,
                'existe_despues_de_eliminar' => $tallaDespuesDeEliminar !== null,
                'datos_despues' => $tallaDespuesDeEliminar
            ]);
        } catch (\Exception $e) {
            \Log::error('[ActualizarPrendaCompletaUseCase] Error al eliminar talla', [
                'talla_id' => $tallaRecord->id,
                'key' => $key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function actualizarOInsertarTallas(PrendaPedido $prenda, $tallasExistentes, array $tallasNuevas): void
    {
        foreach ($tallasNuevas as $key => $dataTalla) {
            if (isset($tallasExistentes[$key])) {
                $tallasExistentes[$key]->update([
                    'cantidad' => $dataTalla['cantidad'],
                    'es_sobremedida' => $dataTalla['es_sobremedida'],
                ]);
                continue;
            }

            $prenda->tallas()->create($dataTalla);
        }
    }

    private function registrarEstadoFinalTallas(PrendaPedido $prenda): void
    {
        $tallasFinales = $prenda->tallas()->get();
        \Log::info('[ActualizarPrendaCompletaUseCase] actualizarTallas - Completado', [
            'prenda_id' => $prenda->id,
            'total_tallas' => $tallasFinales->count(),
            'tallas_restantes' => $tallasFinales->map(function ($t) {
                return [
                    'id' => $t->id,
                    'genero' => $t->genero,
                    'talla' => $t->talla,
                    'cantidad' => $t->cantidad,
                    'key' => $t->genero . '_' . $t->talla
                ];
            })->toArray()
        ]);
    }
    private function generarRutaWebp(string $rutaOriginal): string
    {
        // Reemplazar extensión por .webp
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }
}
