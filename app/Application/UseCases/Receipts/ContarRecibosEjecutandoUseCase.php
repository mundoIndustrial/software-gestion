<?php

namespace App\Application\UseCases\Receipts;

use App\Repositories\ConsecutivoReciboPedidoRepository;
use Carbon\Carbon;

/**
 * UseCase: Contar recibos de COSTURA en ejecución en Corte no vistos por el usuario
 *
 * Responsabilidades:
 * - Delegar la query al repository (evita N+1 con JOIN)
 * - Formatear la respuesta
 */
class ContarRecibosEjecutandoUseCase
{
    public function __construct(
        private ConsecutivoReciboPedidoRepository $reciboRepository,
    ) {}

    public function execute(int $userId): array
    {
        $recibos = $this->reciboRepository->findEjecutandoEnCorteNoVistosPorUsuario($userId);

        $data = $recibos->map(fn($r) => [
            'id'            => $r->id,
            'numero_recibo' => $r->consecutivo_actual,
            'cliente'       => $r->cliente ?? '-',
            'pedido_id'     => $r->numero_pedido ?? '-',
            'fecha'         => Carbon::parse($r->created_at)->format('d/m/Y H:i'),
        ])->values()->toArray();

        return [
            'success' => true,
            'total'   => count($data),
            'recibos' => $data,
        ];
    }
}
