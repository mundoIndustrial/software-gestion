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
        int $pedidoId,
        int $numeroRecibo,
        string $novedadTexto,
        string $tipoNovedad,
        int $usuarioId,
        ?array $prendasIds = null
    ): array {
        $pedido = PedidoProduccion::findOrFail($pedidoId);
        
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
