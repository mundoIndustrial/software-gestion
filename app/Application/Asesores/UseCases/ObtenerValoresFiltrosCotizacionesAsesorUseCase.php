<?php

namespace App\Application\Asesores\UseCases;

use App\Application\Cotizacion\Handlers\Queries\ListarCotizacionesHandler;
use App\Application\Cotizacion\Queries\ListarCotizacionesQuery;

final class ObtenerValoresFiltrosCotizacionesAsesorUseCase
{
    public function __construct(
        private readonly ListarCotizacionesHandler $listarCotizacionesHandler
    ) {
    }

    public function ejecutar(int $asesorId): array
    {
        $query = ListarCotizacionesQuery::crear(
            usuarioId: $asesorId,
            pagina: 1,
            porPagina: 500
        );

        $cotizaciones = $this->listarCotizacionesHandler->handle($query);

        $tiposMap = [
            'PL' => 'Combinada',
            'L' => 'Logo',
            'RF' => 'Reflectivo',
        ];

        $fechas = [];
        $codigos = [];
        $clientes = [];
        $tipos = [];
        $estados = [];

        foreach ($cotizaciones as $cotizacion) {
            $data = method_exists($cotizacion, 'toArray')
                ? $cotizacion->toArray()
                : (array) $cotizacion;

            if (!empty($data['created_at'])) {
                try {
                    $fechas[] = (new \DateTimeImmutable((string) $data['created_at']))->format('d/m/Y');
                } catch (\Exception $e) {
                }
            }

            if (!empty($data['numero_cotizacion'])) {
                $codigos[] = (string) $data['numero_cotizacion'];
            }

            if (!empty($data['cliente'])) {
                $clientes[] = (string) $data['cliente'];
            }

            $tipoCodigo = (string) ($data['tipo'] ?? 'PL');
            $tipos[] = $tiposMap[$tipoCodigo] ?? $tipoCodigo;

            if (!empty($data['estado'])) {
                $estados[] = (string) $data['estado'];
            }
        }

        return [
            'total' => count($cotizaciones),
            'fechas' => array_values(array_unique(array_filter($fechas))),
            'codigos' => array_values(array_unique(array_filter($codigos))),
            'clientes' => array_values(array_unique(array_filter($clientes))),
            'tipos' => array_values(array_unique(array_filter($tipos))),
            'estados' => array_values(array_unique(array_filter($estados))),
        ];
    }
}
