<?php

namespace App\Infrastructure\Talleres\Queries;

use Illuminate\Support\Facades\DB;

class ObtenerEntregasPorTallaQuery
{
    private array $reciboIds;
    private bool $esParcial;

    public function __construct(array $reciboIds, bool $esParcial = false)
    {
        $this->reciboIds = $reciboIds;
        $this->esParcial = $esParcial;
    }

    public function execute(): array
    {
        if (empty($this->reciboIds)) {
            return [];
        }

        $entregas = [];

        if ($this->esParcial) {
            // Para recibos parciales, necesitamos obtener el numero_recibo_parcial
            $resultados = DB::table('entrega_recibo_costura as erc')
                ->join('recibo_por_partes as rpp', 'erc.recibo_parcial_id', '=', 'rpp.id')
                ->whereIn('erc.recibo_parcial_id', $this->reciboIds)
                ->groupBy('rpp.consecutivo_parcial', 'erc.talla')
                ->select(
                    'rpp.consecutivo_parcial as numero_recibo',
                    'erc.talla',
                    DB::raw('SUM(erc.cantidad_entregada) as total')
                )
                ->get();

            foreach ($resultados as $resultado) {
                $clave = $resultado->numero_recibo . '|' . $resultado->talla;
                $entregas[$clave] = $resultado->total;
            }
        } else {
            // Para recibos normales, necesitamos obtener el numero_recibo
            $resultados = DB::table('entrega_recibo_costura as erc')
                ->join('consecutivos_recibos_pedidos as crp', 'erc.consecutivo_recibo_id', '=', 'crp.id')
                ->whereIn('erc.consecutivo_recibo_id', $this->reciboIds)
                ->groupBy('crp.consecutivo_actual', 'erc.talla')
                ->select(
                    'crp.consecutivo_actual as numero_recibo',
                    'erc.talla',
                    DB::raw('SUM(erc.cantidad_entregada) as total')
                )
                ->get();

            foreach ($resultados as $resultado) {
                $clave = $resultado->numero_recibo . '|' . $resultado->talla;
                $entregas[$clave] = $resultado->total;
            }
        }

        return $entregas;
    }
}
