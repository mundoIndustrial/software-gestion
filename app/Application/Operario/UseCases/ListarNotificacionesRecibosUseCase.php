<?php

namespace App\Application\Operario\UseCases;

use App\Domain\Operario\Repositories\ReciboNotificacionesRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ListarNotificacionesRecibosUseCase
{
    public function __construct(
        private readonly ReciboNotificacionesRepository $notificaciones,
    ) {}

    /**
     * @return array{items: array<int, array{id:int,numero_recibo:int,cliente:string,fecha:string,tipo_recibo:string}>}
     */
    public function execute(Request $request): array
    {
        $usuario = Auth::user();

        $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', 'COSTURA')));
        $sinceRaw = trim((string) $request->query('since', ''));
        $limit = (int) $request->query('limit', 50);
        if ($limit <= 0) {
            $limit = 50;
        }
        $limit = min($limit, 200);

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

        $sinceDt = null;
        if ($usuario->hasRole('administrador-costura')) {
            if ($sinceRaw !== '') {
                try {
                    $sinceDt = \Carbon\Carbon::parse($sinceRaw);
                } catch (\Exception $e) {
                    $sinceDt = now()->subMinutes(5);
                }
            } else {
                $sinceDt = now()->subMinutes(5);
            }
        }

        $recibos = $this->notificaciones->listarNoVistas(
            userId: (int) $usuario->id,
            tipoRecibo: $tipoRecibo,
            limit: $limit,
            since: $sinceDt,
            areaFiltro: $areaFiltro,
            encargadoNormalizado: $encargadoNormalizado,
            soloAsignadosAlEncargado: $soloAsignados
        );

        $items = $recibos->map(function ($recibo) {
            return [
                'id' => (int) $recibo->id,
                'numero_recibo' => (int) ($recibo->consecutivo_actual ?? 0),
                'cliente' => (string) ($recibo->pedido->cliente ?? '-'),
                'fecha' => $recibo->updated_at ? $recibo->updated_at->format('d/m/Y H:i') : ($recibo->created_at ? $recibo->created_at->format('d/m/Y H:i') : ''),
                'tipo_recibo' => (string) ($recibo->tipo_recibo ?? ''),
            ];
        })->values()->all();

        return ['items' => $items];
    }
}

