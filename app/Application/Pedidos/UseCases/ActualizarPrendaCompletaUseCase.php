<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class ActualizarPrendaCompletaUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(ActualizarPrendaCompletaDTO $dto)
    {
        Log::info('[ActualizarPrendaCompletaUseCase] Iniciando actualización de prenda completa', [
            'pedido_id' => $dto->pedidoId,
            'prenda_id' => $dto->prendaId,
        ]);

        $prenda = PrendaPedido::find($dto->prendaId);
        
        if (!$prenda) {
            throw new \InvalidArgumentException("Prenda {$dto->prendaId} no encontrada");
        }

        // Actualizar campos básicos
        $prenda->nombre_prenda = $dto->nombrePrenda;
        $prenda->descripcion = $dto->descripcion;
        $prenda->save();

        // Guardar tallas
        if (!empty($dto->tallaJson)) {
            $this->pedidoRepository->guardarTallasDesdeJson($prenda->id, $dto->tallaJson);
        }

        // Actualizar imágenes
        if (!empty($dto->imagenes)) {
            // Eliminar antiguas
            DB::table('prenda_fotos_pedido')
                ->where('prenda_pedido_id', $prenda->id)
                ->delete();

            // Insertar nuevas
            foreach ($dto->imagenes as $orden => $ruta) {
                DB::table('prenda_fotos_pedido')->insert([
                    'prenda_pedido_id' => $prenda->id,
                    'ruta_webp' => $ruta,
                    'ruta_original' => $ruta,
                    'orden' => $orden + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Guardar novedad en el pedido
        if ($dto->novedad) {
            $pedido = PedidoProduccion::find($dto->pedidoId);
            if ($pedido) {
                $this->guardarNovedad($pedido, $dto->novedad);
            }
        }

        Log::info('[ActualizarPrendaCompletaUseCase] Prenda completa actualizada exitosamente', [
            'prenda_id' => $prenda->id,
        ]);

        return $prenda;
    }

    private function guardarNovedad(PedidoProduccion $pedido, string $novedad): void
    {
        $novedadesActuales = !empty($pedido->novedades) ? $pedido->novedades . "\n" : '';
        $timestamp = now()->format('Y-m-d H:i:s');
        $usuario = auth()->user()->name ?? 'Sistema';
        $novedadesNuevas = $novedadesActuales . "[{$timestamp}] {$usuario}: {$novedad}";
        
        $pedido->update(['novedades' => $novedadesNuevas]);
    }
}
