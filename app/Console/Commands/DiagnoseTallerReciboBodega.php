<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseTallerReciboBodega extends Command
{
    protected $signature = 'diagnose:taller-recibo-bodega
        {--recibo-id= : ID del registro en recibo_por_partes}
        {--numero= : Consecutivo parcial a revisar}
        {--pedido= : ID del pedido_produccion, opcional}
        {--tipo=CORTE-PARA-BODEGA : Tipo de recibo a revisar}';

    protected $description = 'Diagnostica por que un recibo CORTE-PARA-BODEGA sale con descripcion N/A';

    public function handle(): int
    {
        $reciboId = (int) $this->option('recibo-id');
        $numero = trim((string) $this->option('numero'));
        $pedidoId = (int) $this->option('pedido');
        $tipo = strtoupper(trim((string) $this->option('tipo')));

        if ($reciboId <= 0 && $numero === '') {
            $this->error('Debes pasar --recibo-id o --numero.');
            return self::FAILURE;
        }

        $this->info("Diagnostico taller bodega | tipo={$tipo} | recibo_id=" . ($reciboId > 0 ? $reciboId : 'N/A') . " | numero=" . ($numero !== '' ? $numero : 'N/A'));

        $query = DB::table('recibo_por_partes as rpp')
            ->leftJoin('pedidos_produccion as ppro', 'rpp.pedido_produccion_id', '=', 'ppro.id')
            ->leftJoin('clientes', 'ppro.cliente_id', '=', 'clientes.id')
            ->leftJoin('consecutivos_recibos_pedidos as crp_base', function ($join) {
                $join->on('rpp.consecutivo_original', '=', 'crp_base.consecutivo_actual')
                    ->where('crp_base.tipo_recibo', '=', 'CORTE-PARA-BODEGA')
                    ->whereColumn('crp_base.prenda_bodega_id', 'rpp.prenda_pedido_id');
            })
            ->leftJoin('prenda_bodega as pb', 'crp_base.prenda_bodega_id', '=', 'pb.id')
            ->leftJoin('prendas_pedido as pp', 'rpp.prenda_pedido_id', '=', 'pp.id')
            ->select(
                'rpp.id',
                'rpp.pedido_produccion_id',
                'rpp.prenda_pedido_id',
                'rpp.consecutivo_original',
                'rpp.consecutivo_parcial',
                'rpp.tipo_recibo',
                'ppro.numero_pedido',
                'clientes.nombre as cliente_pedido',
                'crp_base.id as crp_base_id',
                'crp_base.prenda_bodega_id',
                'pb.nombre as prenda_bodega_nombre',
                'pb.descripcion as prenda_bodega_descripcion',
                'pp.nombre_prenda as prenda_pedido_nombre',
                'pp.descripcion as prenda_pedido_descripcion'
            );

        if ($reciboId > 0) {
            $query->where('rpp.id', $reciboId);
        } else {
            $query->whereRaw('TRIM(CAST(rpp.consecutivo_parcial AS CHAR)) = ?', [$numero]);
        }

        if ($pedidoId > 0) {
            $query->where('rpp.pedido_produccion_id', $pedidoId);
        }

        $row = $query->first();

        if (!$row) {
            $this->error('No se encontro el recibo con esos filtros.');
            return self::FAILURE;
        }

        $this->line('1) Fila origen en recibo_por_partes / joins');
        $this->table(
            ['campo', 'valor'],
            [
                ['rpp.id', (string) $row->id],
                ['rpp.tipo_recibo', (string) ($row->tipo_recibo ?? '')],
                ['rpp.pedido_produccion_id', (string) ($row->pedido_produccion_id ?? '')],
                ['rpp.prenda_pedido_id', (string) ($row->prenda_pedido_id ?? '')],
                ['rpp.consecutivo_original', (string) ($row->consecutivo_original ?? '')],
                ['rpp.consecutivo_parcial', (string) ($row->consecutivo_parcial ?? '')],
                ['pedido.numero_pedido', (string) ($row->numero_pedido ?? '')],
                ['cliente.pedido', (string) ($row->cliente_pedido ?? '')],
            ]
        );

        $this->line('2) Resolucion de crp_base -> prenda_bodega');
        $this->table(
            ['campo', 'valor'],
            [
                ['crp_base.id', (string) ($row->crp_base_id ?? '')],
                ['crp_base.prenda_bodega_id', (string) ($row->prenda_bodega_id ?? '')],
                ['pb.nombre', $row->prenda_bodega_nombre !== null ? (string) $row->prenda_bodega_nombre : 'NULL'],
                ['pb.descripcion', $row->prenda_bodega_descripcion !== null ? (string) $row->prenda_bodega_descripcion : 'NULL'],
            ]
        );

        $candidatosCrp = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'CORTE-PARA-BODEGA')
            ->where('consecutivo_actual', $row->consecutivo_original)
            ->where('prenda_bodega_id', $row->prenda_pedido_id)
            ->get(['id', 'pedido_produccion_id', 'prenda_id', 'prenda_bodega_id', 'consecutivo_actual', 'tipo_recibo', 'area']);

        $this->line('2.1) Candidatos exactos en consecutivos_recibos_pedidos');
        if ($candidatosCrp->isEmpty()) {
            $this->warn('No hay filas exactas en consecutivos_recibos_pedidos para consecutivo_original + prenda_bodega_id + tipo_recibo.');
        } else {
            $this->table(
                ['id', 'pedido_id', 'prenda_id', 'prenda_bodega_id', 'numero', 'tipo', 'area'],
                $candidatosCrp->map(fn ($r) => [
                    $r->id,
                    $r->pedido_produccion_id,
                    $r->prenda_id,
                    $r->prenda_bodega_id,
                    $r->consecutivo_actual,
                    $r->tipo_recibo,
                    $r->area,
                ])->all()
            );
        }

        $this->line('3) Comparacion con prenda_pedido');
        $this->table(
            ['campo', 'valor'],
            [
                ['pp.nombre_prenda', $row->prenda_pedido_nombre !== null ? (string) $row->prenda_pedido_nombre : 'NULL'],
                ['pp.descripcion', $row->prenda_pedido_descripcion !== null ? (string) $row->prenda_pedido_descripcion : 'NULL'],
            ]
        );

        $descripcionBodega = trim((string) ($row->prenda_bodega_descripcion ?? ''));
        if ($descripcionBodega === '') {
            $this->warn('La descripcion de prenda_bodega esta vacia o NULL para este recibo.');
        } else {
            $this->info('La descripcion de prenda_bodega SI tiene contenido.');
        }

        if (empty($row->crp_base_id)) {
            $this->warn('No hubo match en crp_base. Eso obliga al query a caer en N/A o en datos del pedido base.');
        }

        return self::SUCCESS;
    }
}
