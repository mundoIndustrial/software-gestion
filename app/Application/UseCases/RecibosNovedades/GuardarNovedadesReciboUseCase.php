<?php

namespace App\Application\UseCases\RecibosNovedades;

use App\Infrastructure\Repositories\NovedadesReciboRepository;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Support\Facades\DB;

class GuardarNovedadesReciboUseCase
{
    public function __construct(
        private readonly NovedadesReciboRepository $repository
    ) {}

    public function execute(
        int|string $pedidoId,
        int $numeroRecibo,
        string $novedadTexto,
        string $tipoNovedad,
        int $usuarioId,
        ?array $prendasIds = null
    ): array {
        $pedido = PedidoProduccion::query()
            ->when(is_numeric((string) $pedidoId), function ($query) use ($pedidoId) {
                $query->where('id', (int) $pedidoId)
                    ->orWhere('numero_pedido', (int) $pedidoId);
            }, function ($query) use ($pedidoId) {
                $query->where('numero_pedido', trim((string) $pedidoId));
            })
            ->first();

        if (!$pedido) {
            throw new \RuntimeException('Pedido no encontrado');
        }
        
        // Si no se especifican prendas, buscar la prenda específica del recibo
        if (empty($prendasIds)) {
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('consecutivo_actual', $numeroRecibo)
                ->where('activo', 1)
                ->first();
            
            if ($recibo && $recibo->prenda_id) {
                $prendasIds = [$recibo->prenda_id];
            } else {
                // Fallback: usar la primera prenda
                $primeraPrenda = $pedido->prendas->first();
                $prendasIds = $primeraPrenda ? [$primeraPrenda->id] : [];
            }
        }
        
        DB::beginTransaction();
        
        try {
            $novedadesCreadas = [];
            
            foreach ($prendasIds as $prendaId) {
                $novedad = $this->repository->crear(
                    $prendaId,
                    $numeroRecibo,
                    $novedadTexto,
                    $tipoNovedad,
                    $usuarioId
                );
                
                $novedadesCreadas[] = $novedad;
            }
            
            DB::commit();
            
            return [
                'novedades_creadas' => count($novedadesCreadas),
                'prendas_afectadas' => $prendasIds
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
