<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalizarReciboLogoCommand extends Command
{
    protected $signature = 'debug:analizar-recibo-logo
                            {consecutivo : consecutivo_actual del recibo}
                            {--tipo=BORDADO : tipo_recibo (BORDADO|ESTAMPADO|DTF|SUBLIMADO)}';

    protected $description = 'Analiza por qué un recibo logo no aparece en /visualizador-logo/pedidos-logo.';

    public function handle(): int
    {
        $consecutivo = (int) $this->argument('consecutivo');
        $tipo = strtoupper(trim((string) $this->option('tipo')));

        $tipoProcesoId = match ($tipo) {
            'BORDADO' => 2,
            'ESTAMPADO' => 3,
            'DTF' => 4,
            'SUBLIMADO' => 5,
            default => null,
        };

        if ($tipoProcesoId === null) {
            $this->error("Tipo inválido: {$tipo}. Use BORDADO|ESTAMPADO|DTF|SUBLIMADO.");
            return self::FAILURE;
        }

        $crp = DB::table('consecutivos_recibos_pedidos')
            ->where('consecutivo_actual', $consecutivo)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipo])
            ->where('activo', 1)
            ->first();

        if (!$crp) {
            $this->warn("No existe recibo activo con consecutivo {$consecutivo} y tipo {$tipo} en consecutivos_recibos_pedidos.");
            return self::SUCCESS;
        }

        $this->info("Recibo encontrado: CRP #{$crp->id} (pedido={$crp->pedido_produccion_id}, prenda={$crp->prenda_id})");

        $procesosPrenda = DB::table('pedidos_procesos_prenda_detalles')
            ->where('prenda_pedido_id', (int) $crp->prenda_id)
            ->orderBy('id')
            ->get(['id', 'tipo_proceso_id', 'estado', 'numero_recibo', 'created_at']);

        $procesosTipo = $procesosPrenda->where('tipo_proceso_id', $tipoProcesoId)->values();

        $this->newLine();
        $this->line('Procesos de la prenda (todos):');
        $this->table(
            ['id', 'tipo_proceso_id', 'estado', 'numero_recibo', 'created_at'],
            $procesosPrenda->map(fn ($p) => [$p->id, $p->tipo_proceso_id, $p->estado, $p->numero_recibo, (string) $p->created_at])->all()
        );

        $this->newLine();
        $this->line("Procesos de la técnica {$tipo} (tipo_proceso_id={$tipoProcesoId}):");
        if ($procesosTipo->isEmpty()) {
            $this->warn('No hay procesos de esta técnica para la prenda.');
            $this->error('Diagnóstico: el recibo existe, pero NO puede salir en pedidos-logo porque falta el proceso de la técnica.');
            return self::SUCCESS;
        }

        $this->table(
            ['id', 'estado', 'numero_recibo', 'created_at'],
            $procesosTipo->map(fn ($p) => [$p->id, $p->estado, $p->numero_recibo, (string) $p->created_at])->all()
        );

        $procesoIds = $procesosTipo->pluck('id')->map(fn ($v) => (int) $v)->all();
        $areas = DB::table('prenda_areas_logo_pedido')
            ->whereIn('proceso_prenda_detalle_id', $procesoIds)
            ->whereNull('pedido_parcial_id')
            ->orderBy('id')
            ->get(['id', 'proceso_prenda_detalle_id', 'area', 'created_at']);

        $ultimaAreaPorProceso = $areas
            ->groupBy('proceso_prenda_detalle_id')
            ->map(fn ($rows) => $rows->sortByDesc('id')->first());

        $this->newLine();
        $this->line('Última área por proceso de la técnica:');
        if ($ultimaAreaPorProceso->isEmpty()) {
            $this->warn('Sin trazas en prenda_areas_logo_pedido (el listado lo toma como pendiente).');
        } else {
            $this->table(
                ['proceso_id', 'ultima_area', 'area_row_id', 'created_at'],
                $ultimaAreaPorProceso->map(fn ($r) => [$r->proceso_prenda_detalle_id, $r->area, $r->id, (string) $r->created_at])->values()->all()
            );
        }

        $noVisiblesPorEstado = $procesosTipo->filter(function ($p) {
            return !in_array((string) $p->estado, ['APROBADO', 'COMPLETADO'], true);
        });

        if ($noVisiblesPorEstado->isNotEmpty()) {
            $this->error('Diagnóstico: existe proceso de técnica, pero su estado no es APROBADO/COMPLETADO, por eso no se lista.');
            return self::SUCCESS;
        }

        $areasNoBordando = $ultimaAreaPorProceso->filter(function ($r) {
            return strtoupper(trim((string) ($r->area ?? ''))) !== 'BORDANDO';
        });

        if ($areasNoBordando->isNotEmpty()) {
            $this->warn('Diagnóstico: para usuario con rol bordador, puede ocultarse por filtro de área fija BORDANDO.');
            $this->line('Con otros roles del módulo sí puede verse.');
            return self::SUCCESS;
        }

        $this->info('Diagnóstico: el recibo cumple condiciones principales para mostrarse. Si no sale, revisar filtros de UI (search/filtros por columna/paginación).');
        return self::SUCCESS;
    }
}

