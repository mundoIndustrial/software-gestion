<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnosticarReciboLogoCommand extends Command
{
    protected $signature = 'debug:recibo-logo
                            {consecutivo : consecutivo_actual a analizar}
                            {--tipo=BORDADO : tipo_recibo (BORDADO|ESTAMPADO|DTF|SUBLIMADO)}';

    protected $description = 'Diagnostica un recibo logo cruzando consecutivos_recibos_pedidos, procesos y prenda_areas_logo_pedido.';

    public function handle(): int
    {
        $consecutivo = (int) $this->argument('consecutivo');
        $tipo = strtoupper((string) $this->option('tipo'));

        $tiposValidos = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'];
        if (!in_array($tipo, $tiposValidos, true)) {
            $this->error("Tipo inválido: {$tipo}. Válidos: " . implode(', ', $tiposValidos));
            return self::FAILURE;
        }

        $this->info("Diagnóstico recibo logo");
        $this->line("consecutivo_actual: {$consecutivo}");
        $this->line("tipo_recibo: {$tipo}");
        $this->newLine();

        $crps = DB::table('consecutivos_recibos_pedidos')
            ->where('consecutivo_actual', $consecutivo)
            ->whereRaw('UPPER(tipo_recibo) = ?', [$tipo])
            ->orderBy('id')
            ->get();

        if ($crps->isEmpty()) {
            $this->warn('No se encontraron registros en consecutivos_recibos_pedidos para ese consecutivo y tipo.');
            return self::SUCCESS;
        }

        $this->info('1) Filas en consecutivos_recibos_pedidos');
        $this->table(
            ['id', 'pedido_produccion_id', 'prenda_id', 'tipo_recibo', 'estado', 'area', 'activo', 'created_at'],
            $crps->map(fn ($r) => [
                $r->id,
                $r->pedido_produccion_id,
                $r->prenda_id,
                $r->tipo_recibo,
                $r->estado,
                $r->area,
                $r->activo,
                (string) $r->created_at,
            ])->all()
        );

        $tipoProcesoId = match ($tipo) {
            'BORDADO' => 2,
            'ESTAMPADO' => 3,
            'DTF' => 4,
            'SUBLIMADO' => 5,
        };

        foreach ($crps as $crp) {
            $this->newLine();
            $this->info("2) Análisis para CRP #{$crp->id} (prenda_id={$crp->prenda_id}, pedido_produccion_id={$crp->pedido_produccion_id})");

            $procesosTipo = DB::table('pedidos_procesos_prenda_detalles as ppd')
                ->where('ppd.prenda_pedido_id', (int) $crp->prenda_id)
                ->where('ppd.tipo_proceso_id', $tipoProcesoId)
                ->orderBy('ppd.id')
                ->get(['ppd.id', 'ppd.prenda_pedido_id', 'ppd.tipo_proceso_id', 'ppd.numero_recibo', 'ppd.estado', 'ppd.created_at']);

            $this->line("2.1 Procesos de la técnica {$tipo} en la prenda");
            if ($procesosTipo->isEmpty()) {
                $this->warn('No hay procesos de esta técnica para la prenda.');
            } else {
                $this->table(
                    ['proceso_id', 'prenda_pedido_id', 'tipo_proceso_id', 'numero_recibo', 'estado', 'created_at'],
                    $procesosTipo->map(fn ($p) => [
                        $p->id,
                        $p->prenda_pedido_id,
                        $p->tipo_proceso_id,
                        $p->numero_recibo,
                        $p->estado,
                        (string) $p->created_at,
                    ])->all()
                );
            }

            $areasPrenda = DB::table('prenda_areas_logo_pedido')
                ->where('prenda_pedido_id', (int) $crp->prenda_id)
                ->orderBy('id')
                ->get(['id', 'prenda_pedido_id', 'proceso_prenda_detalle_id', 'pedido_parcial_id', 'area', 'created_at']);

            $this->line('2.2 Historial completo en prenda_areas_logo_pedido (por prenda)');
            if ($areasPrenda->isEmpty()) {
                $this->warn('No hay historial en prenda_areas_logo_pedido para esa prenda.');
            } else {
                $this->table(
                    ['id', 'prenda_id', 'proceso_id', 'parcial_id', 'area', 'created_at'],
                    $areasPrenda->map(fn ($a) => [
                        $a->id,
                        $a->prenda_pedido_id,
                        $a->proceso_prenda_detalle_id,
                        $a->pedido_parcial_id,
                        $a->area,
                        (string) $a->created_at,
                    ])->all()
                );
            }

            if ($procesosTipo->isNotEmpty()) {
                $procesoIds = $procesosTipo->pluck('id')->map(fn ($v) => (int) $v)->all();

                $areasSoloTipo = DB::table('prenda_areas_logo_pedido')
                    ->where('prenda_pedido_id', (int) $crp->prenda_id)
                    ->whereIn('proceso_prenda_detalle_id', $procesoIds)
                    ->orderBy('id')
                    ->get(['id', 'proceso_prenda_detalle_id', 'area', 'created_at']);

                $this->line('2.3 Historial de áreas solo de esta técnica (por proceso_prenda_detalle_id)');
                if ($areasSoloTipo->isEmpty()) {
                    $this->warn('No hay áreas registradas para los procesos de esta técnica.');
                } else {
                    $this->table(
                        ['id', 'proceso_id', 'area', 'created_at'],
                        $areasSoloTipo->map(fn ($a) => [
                            $a->id,
                            $a->proceso_prenda_detalle_id,
                            $a->area,
                            (string) $a->created_at,
                        ])->all()
                    );

                    $ultimasPorProceso = $areasSoloTipo
                        ->groupBy('proceso_prenda_detalle_id')
                        ->map(fn ($rows) => $rows->sortByDesc('id')->first())
                        ->values();

                    $this->line('2.4 Última área por proceso (esta es la referencia correcta por técnica)');
                    $this->table(
                        ['proceso_id', 'ultima_area', 'area_row_id', 'created_at', 'excluir_reporte'],
                        $ultimasPorProceso->map(function ($u) {
                            $area = strtoupper((string) ($u->area ?? ''));
                            $excluir = in_array($area, ['ENTREGADO', 'ANULADO'], true) ? 'SI' : 'NO';
                            return [
                                $u->proceso_prenda_detalle_id,
                                $u->area,
                                $u->id,
                                (string) $u->created_at,
                                $excluir,
                            ];
                        })->all()
                    );
                }
            }
        }

        $this->newLine();
        $this->info('Diagnóstico finalizado.');
        return self::SUCCESS;
    }
}

