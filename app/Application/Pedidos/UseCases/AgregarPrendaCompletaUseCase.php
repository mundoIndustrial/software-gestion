<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Models\PrendaPedido;

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

        // 3. Agregar tallas si existen
        if (!empty($dto->tallas)) {
            foreach ($dto->tallas as $talla) {
                $prenda->tallas()->create([
                    'genero' => $talla['genero'],
                    'talla' => $talla['talla'],
                    'cantidad' => $talla['cantidad'] ?? 0,
                ]);
            }
        }

        // 4. Guardar novedad en pedidos_produccion.novedades
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


