<?php

namespace App\Application\UseCases\Receipts;

use App\Repositories\ConsecutivoReciboPedidoRepository;
use App\Application\Services\ReceiptEnricherService;
use Illuminate\Http\Request;

/**
 * UseCase: Obtener recibos de reflectivo avanzados con filtrado
 * 
 * Responsabilidades:
 * - Orquestar obtención y enriquecimiento de recibos
 * - Aplicar filtros
 * - Formatear respuesta
 */
class GetReflectiveReceiptsUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $recibosRepository,
        private ReceiptEnricherService $enricher
    ) {}


    public function execute(Request $request): array
    {
        $filtros = $this->procesarFiltros($request);
        $perPage = 25; // Máximo 25 registros por página

        $recibosReflectivo = $this->recibosRepository->getConFiltros('REFLECTIVO', $filtros, $perPage);

        // Verificar si es paginación o colección
        $esPaginado = $recibosReflectivo instanceof \Illuminate\Pagination\LengthAwarePaginator;

        if ($esPaginado) {
            // Enriquecer solo los items de la página actual
            $recibosItems = $recibosReflectivo->getCollection()->toArray();
            $recibosConInfo = $this->enricher->enriquecer($recibosItems);
            $recibosReflectivo->setCollection(collect($recibosConInfo));

            $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

            return [
                'recibos' => $recibosReflectivo,
                'total' => $recibosReflectivo->total(),
                'total_cantidad' => $totalCantidad,
                'filtros_aplicados' => $filtros
            ];
        }

        // Comportamiento original sin paginación
        $recibosConInfo = $this->enricher->enriquecer($recibosReflectivo->toArray());
        $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

        return [
            'recibos' => $recibosConInfo,
            'total' => count($recibosConInfo),
            'total_cantidad' => $totalCantidad,
            'filtros_aplicados' => $filtros
        ];
    }

    /**
     * Procesar filtros del request para convertir strings separados por coma en arrays
     */
    private function procesarFiltros(Request $request): array
    {
        $filtros = [];
        $camposFiltro = ['estado', 'area', 'total_dias', 'numero_recibo', 'cliente', 'descripcion', 'cantidad', 'novedades', 'fecha_creacion', 'fecha_estimada'];

        foreach ($camposFiltro as $campo) {
            if ($request->has($campo) && !empty($request->input($campo))) {
                $valor = $request->input($campo);
                // Si es un string separado por comas, convertir a array
                if (is_string($valor)) {
                    $filtros[$campo] = array_map('trim', explode(',', $valor));
                } else {
                    $filtros[$campo] = $valor;
                }
            }
        }

        // Agregar término de búsqueda
        if ($request->has('search') && !empty($request->input('search'))) {
            $filtros['search'] = $request->input('search');
        }

        return $filtros;
    }

  
    private function calcularCantidadTotal(array $recibos): int
    {
        return array_reduce($recibos, function($carry, $recibo) {
            return $carry + ($recibo['cantidad_total'] ?? 0);
        }, 0);
    }
}
