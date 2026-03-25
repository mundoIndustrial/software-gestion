<?php

namespace App\Application\UseCases\Receipts;

use App\Repositories\ReciboUsuarioVistoRepository;

/**
 * UseCase: Marcar un recibo como visto por el usuario actual
 *
 * Responsabilidades:
 * - Verificar que el recibo exista y sea del tipo correcto
 * - Delegar la inserción al repository (idempotente via insertOrIgnore)
 */
class MarcarReciboVistoUseCase
{
    public function __construct(
        private ReciboUsuarioVistoRepository $vistoRepository,
    ) {}

    public function execute(int $reciboId, int $userId, string $tipoRecibo = 'COSTURA'): ?array
    {
        $recibo = $this->vistoRepository->findRecibo($reciboId, $tipoRecibo);

        if (!$recibo) {
            return null;
        }

        $this->vistoRepository->marcarVisto($reciboId, $userId, $tipoRecibo);

        \Log::info('Recibo marcado como visto', [
            'recibo_id'     => $reciboId,
            'user_id'       => $userId,
            'numero_recibo' => $recibo->consecutivo_actual,
        ]);

        return [
            'success'   => true,
            'message'   => 'Recibo marcado como visto',
            'recibo_id' => $reciboId,
        ];
    }
}
