<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboNotificacionesRepository;
use Illuminate\Support\Facades\Auth;

class MarcarNotificacionReciboLeidaUseCase
{
    public function __construct(
        private readonly ReciboNotificacionesRepository $notificaciones,
    ) {}

    /**
     * @return array{success:bool,status:int,message:string,recibo_id?:int}
     */
    public function execute(int $reciboId, string $tipoRecibo): array
    {
        $usuario = Auth::user();
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        if (!$this->notificaciones->existeRecibo((int) $reciboId, $tipoRecibo)) {
            return [
                'success' => false,
                'status' => 404,
                'message' => 'Recibo no encontrado',
            ];
        }

        $this->notificaciones->marcarLeida(
            userId: (int) $usuario->id,
            reciboId: (int) $reciboId,
            tipoRecibo: $tipoRecibo,
            fecha: now()
        );

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Notificación marcada como leída',
            'recibo_id' => (int) $reciboId,
        ];
    }
}

