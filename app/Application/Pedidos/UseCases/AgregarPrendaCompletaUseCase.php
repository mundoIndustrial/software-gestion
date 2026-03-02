<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Models\PrendaPedido;
use App\Models\TipoProceso;

/**
 * Use Case para agregar una prenda al pedido con fotos y tallas
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Responsabilidades:
 * - Validar pedido existe  TRAIT
 * - Crear registro en prendas_pedido
 * - Crear fotos de referencia (prenda_fotos_pedido)
 * - Crear tallas y cantidades (prenda_pedido_tallas)
 * 
 * Responsabilidades SEPARADAS en otros Use Cases:
 * - Agregar variantes â†’ AgregarVariantePrendaUseCase
 * - Agregar colores y telas â†’ AgregarColorTelaUseCase
 * - Agregar procesos â†’ AgregarProcesoPrendaUseCase
 * 
 * Antes: 58 lÃ­neas | DespuÃ©s: ~45 lÃ­neas | Reducción: ~22%
 */
final class AgregarPrendaCompletaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function execute(AgregarPrendaCompletaDTO $dto): PrendaPedido
    {
        // CENTRALIZADO: Validar pedido existe (trait)
        $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

        // 1. Crear prenda base
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombre_prenda,
            'descripcion' => $dto->descripcion,
            'de_bodega' => $dto->de_bodega,
        ]);

        // 2. Agregar fotos: nuevas + existentes
        $fotos = [];
        
        // Agregar fotos nuevas
        if (!empty($dto->imagenes)) {
            foreach ($dto->imagenes as $orden => $rutaOriginal) {
                $fotos[$rutaOriginal] = [
                    'ruta_original' => $rutaOriginal,
                    'ruta_webp' => $this->generarRutaWebp($rutaOriginal),
                    'orden' => $orden + 1,
                ];
            }
        }
        
        // Agregar imágenes existentes que deben preservarse
        if (!empty($dto->imagenesExistentes)) {
            foreach ($dto->imagenesExistentes as $imagenExistente) {
                if (is_array($imagenExistente) && isset($imagenExistente['previewUrl'])) {
                    $ruta = $imagenExistente['previewUrl'];
                    if (!isset($fotos[$ruta])) {
                        $fotos[$ruta] = [
                            'ruta_original' => $ruta,
                            'ruta_webp' => $this->generarRutaWebp($ruta),
                            'orden' => count($fotos) + 1,
                        ];
                    }
                }
            }
        }
        
        // Guardar todas las fotos combinadas
        if (!empty($fotos)) {
            foreach ($fotos as $datosFoto) {
                $prenda->fotos()->create($datosFoto);
            }
        }

        // 3. Agregar tallas si existen (cantidad_talla viene como estructura relacional)
        // Estructura: { "DAMA": {"S": 20, "M": 15}, "CABALLERO": {"20": 10}, ... }
        if (!empty($dto->cantidad_talla)) {
            foreach ($dto->cantidad_talla as $genero => $tallasDelGenero) {
                if (!is_array($tallasDelGenero) || empty($tallasDelGenero)) {
                    continue;
                }
                foreach ($tallasDelGenero as $talla => $cantidad) {
                    $cantidadInt = (int)($cantidad ?? 0);
                    if ($cantidadInt > 0) {
                        $prenda->tallas()->create([
                            'genero' => $genero,
                            'talla' => (string)$talla,
                            'cantidad' => $cantidadInt,
                        ]);
                    }
                }
            }
        }

        // 4. Agregar variantes si existen (manga, broche/botón, bolsillos)
        if (!empty($dto->variantes) && is_array($dto->variantes)) {
            $variantes = $dto->variantes;
            $varianteData = [
                'tipo_manga_id'        => $variantes['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variantes['tipo_broche_boton_id'] ?? $variantes['tipo_broche_id'] ?? null,
                'manga_obs'            => $variantes['obs_manga'] ?? $variantes['manga_obs'] ?? null,
                'broche_boton_obs'     => $variantes['obs_broche'] ?? $variantes['broche_boton_obs'] ?? null,
                'tiene_bolsillos'      => $variantes['tiene_bolsillos'] ?? false,
                'bolsillos_obs'        => $variantes['obs_bolsillos'] ?? $variantes['bolsillos_obs'] ?? null,
            ];

            $prenda->variantes()->create($varianteData);

            \Log::info('[AgregarPrendaCompletaUseCase] Variante creada', [
                'prenda_id' => $prenda->id,
                'variante' => $varianteData,
            ]);
        }

        // 5. Agregar procesos si existen (bordado, estampado, reflectivo, etc.)
        if (!empty($dto->procesos) && is_array($dto->procesos)) {
            foreach ($dto->procesos as $procesoIdx => $proceso) {
                // Resolver tipo_proceso_id: puede venir directo o buscarse por slug/nombre
                $tipoProceso = $proceso['tipo_proceso_id'] ?? null;

                if (!$tipoProceso && isset($proceso['tipo'])) {
                    $tipoProcesoModel = TipoProceso::where('slug', strtolower($proceso['tipo']))
                        ->orWhere('nombre', $proceso['tipo'])
                        ->first();
                    if ($tipoProcesoModel) {
                        $tipoProceso = $tipoProcesoModel->id;
                    } else {
                        \Log::warning('[AgregarPrendaCompletaUseCase] Tipo de proceso no encontrado', [
                            'tipo_buscado' => $proceso['tipo'],
                            'prenda_id' => $prenda->id,
                        ]);
                        continue;
                    }
                }

                if (!$tipoProceso) continue;

                // Decodificar ubicaciones
                $ubicaciones = $proceso['ubicaciones'] ?? null;
                if (is_string($ubicaciones)) {
                    $ubicaciones = json_decode($ubicaciones, true);
                }

                $procesoCreado = $prenda->procesos()->create([
                    'tipo_proceso_id' => $tipoProceso,
                    'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
                    'observaciones' => $proceso['observaciones'] ?? '',
                    'estado' => $proceso['estado'] ?? 'PENDIENTE',
                ]);

                // Crear tallas del proceso si existen
                if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                    foreach ($proceso['tallas'] as $genero => $tallas) {
                        if (!is_array($tallas)) continue;
                        foreach ($tallas as $talla => $cantidad) {
                            if ((int)$cantidad > 0) {
                                $procesoCreado->tallas()->create([
                                    'genero' => strtoupper($genero),
                                    'talla' => strtoupper((string)$talla),
                                    'cantidad' => (int)$cantidad,
                                ]);
                            }
                        }
                    }
                }

                // Agregar fotos del proceso si existen
                if (!empty($dto->fotosProcesoNuevo) && isset($dto->fotosProcesoNuevo[$procesoIdx])) {
                    foreach ($dto->fotosProcesoNuevo[$procesoIdx] as $rutasFoto) {
                        $procesoCreado->imagenes()->create([
                            'ruta_original' => $rutasFoto['ruta_original'] ?? null,
                            'ruta_webp' => $rutasFoto['ruta_webp'] ?? $rutasFoto['ruta_original'] ?? null,
                            'orden' => 1,
                        ]);
                    }
                }

                \Log::info('[AgregarPrendaCompletaUseCase] Proceso creado', [
                    'prenda_id' => $prenda->id,
                    'proceso_id' => $procesoCreado->id,
                    'tipo_proceso_id' => $tipoProceso,
                    'tipo' => $proceso['tipo'] ?? 'N/A',
                ]);
            }
        }

        // 6. Guardar novedad en pedidos_produccion.novedades
        $this->guardarNovedad($prenda, $dto);

        return $prenda;
    }

    private function generarRutaWebp(string $rutaOriginal): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
    }

    private function guardarNovedad(PrendaPedido $prenda, AgregarPrendaCompletaDTO $dto): void
    {
        if (is_null($dto->novedad) || empty(trim($dto->novedad))) {
            return;
        }

        $pedido = $prenda->pedidoProduccion;
        if (!$pedido) {
            \Log::warning('[AgregarPrendaCompletaUseCase] No se encontró pedido para prenda', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        $novedadesActuales = $pedido->novedades ?? '';

        $usuarioAutenticado = \Auth::user();
        $nombreAsesor = $usuarioAutenticado ? $usuarioAutenticado->name : 'Sistema';

        // Obtener el primer rol del usuario (usando Spatie Laravel-permission)
        if ($usuarioAutenticado && method_exists($usuarioAutenticado, 'roles')) {
            $rolAsesor = $usuarioAutenticado->roles()->first()?->name ?? 'Sistema';
        } else {
            $rolAsesor = 'Sistema';
        }

        $nuevaNovedad = trim($dto->novedad);
        $fechaHora = now()->format('d/m/Y h:i A');
        $rolLabel = ucfirst(str_replace('_', ' ', $rolAsesor));
        $novedadConInfo = "{$rolLabel}-{$nombreAsesor}-{$fechaHora} - {$nuevaNovedad}";

        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : "") . $novedadConInfo;

        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        \Log::info('[AgregarPrendaCompletaUseCase] Novedad guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $dto->novedad,
            'nombre_asesor' => $nombreAsesor,
        ]);
    }
}


