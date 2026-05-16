<?php

namespace App\Infrastructure\Talleres\Queries;

use Illuminate\Support\Facades\DB;

class ObtenerCantidadesTotalesQuery
{
    private array $reciboIds;
    private array $prendaIds;
    private bool $esParcial;

    public function __construct(array $reciboIds, array $prendaIds = [], bool $esParcial = false)
    {
        $this->reciboIds = $reciboIds;
        $this->prendaIds = $prendaIds;
        $this->esParcial = $esParcial;
    }

    public function execute(): array
    {
        $cantidades = [];

        if ($this->esParcial && !empty($this->reciboIds)) {
            $resultados = DB::table('recibos_por_partes_tallas')
                ->whereIn('recibo_por_partes_id', $this->reciboIds)
                ->select('recibo_por_partes_id', DB::raw('SUM(cantidad) as total'))
                ->groupBy('recibo_por_partes_id')
                ->get();

            foreach ($resultados as $resultado) {
                $cantidades[$resultado->recibo_por_partes_id] = $resultado->total;
            }
        } else if (!$this->esParcial && !empty($this->prendaIds)) {
            $resultados = DB::table('prenda_pedido_tallas')
                ->whereIn('prenda_pedido_id', $this->prendaIds)
                ->select('prenda_pedido_id', DB::raw('SUM(cantidad) as total'))
                ->groupBy('prenda_pedido_id')
                ->get();

            foreach ($resultados as $resultado) {
                $cantidades[$resultado->prenda_pedido_id] = $resultado->total;
            }
        }

        return $cantidades;
    }
}
