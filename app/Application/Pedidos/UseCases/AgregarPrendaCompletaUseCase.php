<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class AgregarPrendaCompletaUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(AgregarPrendaCompletaDTO $dto)
    {
        Log::info('[AgregarPrendaCompletaUseCase] Iniciando agregación de prenda completa', [
            'pedido_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombrePrenda,
        ]);

        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);
        
        if (!$pedido) {
            throw new \InvalidArgumentException("Pedido {$dto->pedidoId} no encontrado");
        }

        // Crear prenda
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $dto->nombrePrenda,
            'descripcion' => $dto->descripcion,
            'origen' => $dto->origen,
            'cantidad' => 1,
        ]);

        // Guardar tallas
        if (!empty($dto->tallaJson)) {
            $this->pedidoRepository->guardarTallasDesdeJson($prenda->id, $dto->tallaJson);
        }

        // Guardar imágenes de prenda
        if (!empty($dto->imagenes)) {
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
            $this->guardarNovedad($pedido, $dto->novedad);
        }

        Log::info('[AgregarPrendaCompletaUseCase] Prenda completa agregada exitosamente', [
            'pedido_id' => $pedido->id,
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
