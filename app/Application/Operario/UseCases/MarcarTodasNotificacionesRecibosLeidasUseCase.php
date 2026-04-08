<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboNotificacionesRepository;
use Illuminate\Support\Facades\Auth;

class MarcarTodasNotificacionesRecibosLeidasUseCase
{
    public function __construct(
        private readonly ReciboNotificacionesRepository $notificaciones,
    ) {}

    /**
     * @return array{success:bool,status:int,message:string,total?:int}
     */
    public function execute(string $tipoRecibo): array
    {
        $usuario = Auth::user();
        $tipoRecibo = strtoupper(trim($tipoRecibo));

        $areaFiltro = null;
        $soloAsignados = false;
        $encargadoNormalizado = strtolower(trim((string) ($usuario->name ?? '')));

        if ($usuario->hasRole('cortador')) {
            $areaFiltro = 'Corte';
            $soloAsignados = true;
        } elseif ($usuario->hasRole('costurero')) {
            $areaFiltro = 'Costura';
            $soloAsignados = true;
        }

        $total = $this->notificaciones->marcarTodasLeidas(
            userId: (int) $usuario->id,
            tipoRecibo: $tipoRecibo,
            areaFiltro: $areaFiltro,
            encargadoNormalizado: $encargadoNormalizado,
            soloAsignadosAlEncargado: $soloAsignados,
            fecha: now()
        );

        return [
            'success' => true,
            'status' => 200,
            'message' => 'Notificaciones marcadas como leídas',
            'total' => (int) $total,
        ];
    }
}

