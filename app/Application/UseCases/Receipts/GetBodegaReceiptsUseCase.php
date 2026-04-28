<?php

namespace App\Application\UseCases\Receipts;

use App\Repositories\ConsecutivoReciboPedidoRepository;
use App\Application\Services\ReceiptEnricherService;
use Illuminate\Http\Request;

class GetBodegaReceiptsUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $recibosRepository,
        private ReceiptEnricherService $enricher
    ) {}

    public function execute(Request $request): array
    {
        $filtros = $this->procesarFiltros($request);
        $perPage = 25;

        // Fuente independiente para bodega
        $recibosBodega = $this->recibosRepository->getConFiltros('BODEGA', $filtros, $perPage);

        $esPaginado = $recibosBodega instanceof \Illuminate\Pagination\LengthAwarePaginator;

        if ($esPaginado) {
            $recibosItems = $recibosBodega->getCollection()->toArray();
            $recibosConInfo = $this->enricher->enriquecer($recibosItems);
            $recibosBodega->setCollection(collect($recibosConInfo));

            $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

            return [
                'recibos' => $recibosBodega,
                'total' => $recibosBodega->total(),
                'total_cantidad' => $totalCantidad,
                'filtros_aplicados' => $filtros,
            ];
        }

        $recibosConInfo = $this->enricher->enriquecer($recibosBodega->toArray());
        $totalCantidad = $this->calcularCantidadTotal($recibosConInfo);

        return [
            'recibos' => $recibosConInfo,
            'total' => count($recibosConInfo),
            'total_cantidad' => $totalCantidad,
            'filtros_aplicados' => $filtros,
        ];
    }

    private function procesarFiltros(Request $request): array
    {
        // En bodega NO se filtra por cliente
        $filtros = [];
        $camposFiltro = ['estado', 'area', 'total_dias', 'numero_recibo', 'descripcion', 'cantidad', 'novedades', 'fecha_creacion', 'fecha_estimada', 'encargado'];

        foreach ($camposFiltro as $campo) {
            if ($request->has($campo) && !empty($request->input($campo))) {
                $valor = $request->input($campo);
                $filtros[$campo] = is_string($valor)
                    ? array_map('trim', explode(',', $valor))
                    : $valor;
            }
        }

        if ($request->has('search') && !empty($request->input('search'))) {
            $filtros['search'] = $request->input('search');
        }

        return $filtros;
    }

    private function calcularCantidadTotal(array $recibos): int
    {
        return array_reduce($recibos, function ($carry, $recibo) {
            return $carry + ($recibo['cantidad_total'] ?? 0);
        }, 0);
    }
}

