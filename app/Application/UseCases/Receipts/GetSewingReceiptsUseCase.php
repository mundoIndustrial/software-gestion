<?php

namespace App\Application\UseCases\Receipts;

use App\Repositories\ConsecutivoReciboPedidoRepository;
use App\Application\Services\ReceiptEnricherService;
use Illuminate\Http\Request;

/**
 * UseCase: Obtener recibos de costura avanzados con filtrado
 * 
 * Responsabilidades:
 * - Orquestar obtención y enriquecimiento de recibos
 * - Aplicar filtros
 * - Formatear respuesta
 */
class GetSewingReceiptsUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $recibosRepository,
        private ReceiptEnricherService $enricher
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(Request $request): array
    {
        $filtros = $request->all();
        $perPage = 25; // Máximo 25 registros por página

        // Obtener recibos del repositorio (con filtros aplicados y paginación)
        $recibosCostura = $this->recibosRepository->getConFiltros('COSTURA', $filtros, $perPage);

        // Verificar si es paginación o colección
        $esPaginado = $recibosCostura instanceof \Illuminate\Pagination\LengthAwarePaginator;

        if ($esPaginado) {
            // Enriquecer solo los items de la página actual
            $recibosItems = $recibosCostura->getCollection()->toArray();
            $recibosConInfo = $this->enricher->enriquecer($recibosItems);
            $recibosCostura->setCollection(collect($recibosConInfo));

            $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

            return [
                'recibos' => $recibosCostura,
                'total' => $recibosCostura->total(),
                'total_cantidad' => $totalCantidad,
                'filtros_aplicados' => $filtros
            ];
        }

        // Comportamiento original sin paginación
        $recibosConInfo = $this->enricher->enriquecer($recibosCostura->toArray());
        $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

        return [
            'recibos' => $recibosConInfo,
            'total' => count($recibosConInfo),
            'total_cantidad' => $totalCantidad,
            'filtros_aplicados' => $filtros
        ];
    }



    /**
     * Calcular cantidad total de todos los recibos
     */
    private function calcularCantidadTotal(array $recibos): int
    {
        return array_reduce($recibos, function($carry, $recibo) {
            return $carry + ($recibo['cantidad_total'] ?? 0);
        }, 0);
    }
}
