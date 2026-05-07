<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseReflectivoButton extends Command
{
    protected $signature = 'diagnose:reflectivo-button {--page=1} {--per-page=10}';

    protected $description = 'Diagnostica por que aparece el boton enviar-produccion-reflectivo en la tabla de Insumos';

    public function handle(): int
    {
        $page = max(1, (int) $this->option('page'));
        $perPage = max(1, (int) $this->option('per-page'));
        $offset = ($page - 1) * $perPage;

        $rows = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as pp', 'crp.pedido_produccion_id', '=', 'pp.id')
            ->select(
                'crp.id',
                'crp.consecutivo_actual',
                'crp.estado as recibo_estado',
                'crp.area as recibo_area',
                'pp.area as pedido_area',
                'pp.numero_pedido',
                'pp.cliente',
                'crp.created_at'
            )
            ->whereRaw('UPPER(TRIM(crp.tipo_recibo)) = ?', ['REFLECTIVO'])
            ->whereNotNull('crp.consecutivo_actual')
            ->orderBy('crp.consecutivo_actual', 'desc')
            ->orderByRaw('COALESCE(crp.ultima_actividad, crp.created_at) DESC')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No se encontraron filas reflectivo para esa pagina.');
            return self::SUCCESS;
        }

        $this->info("Diagnostico reflectivo - pagina {$page}, per-page {$perPage}");
        $this->newLine();

        $out = [];
        foreach ($rows as $row) {
            $estado = trim((string) ($row->recibo_estado ?? ''));
            $reciboArea = trim((string) ($row->recibo_area ?? ''));

            // Condicion actual en la vista (table-partial):
            // $esGestionReflectivo && !in_array($estado, ['En Ejecución', 'En Ejecucion']) && $orden->area === 'Insumos'
            // En transformador: $orden->area = $recibo->recibo_area ?? $recibo->pedido_area
            $isEnEjecucion = in_array($estado, ['En Ejecución', 'En Ejecucion'], true);
            $areaMatchExact = ((string) ($row->recibo_area ?? '')) === 'Insumos';
            $showButtonByCurrentRule = !$isEnEjecucion && $areaMatchExact;

            $out[] = [
                'recibo_id' => $row->id,
                'consecutivo' => $row->consecutivo_actual,
                'estado' => $estado,
                'recibo_area' => $reciboArea,
                'pedido_area' => trim((string) ($row->pedido_area ?? '')),
                'show_btn' => $showButtonByCurrentRule ? 'SI' : 'NO',
                'pedido' => $row->numero_pedido,
            ];
        }

        $this->table(
            ['recibo_id', 'consecutivo', 'estado', 'recibo_area', 'pedido_area', 'show_btn', 'pedido'],
            $out
        );

        $this->newLine();
        $this->line('Resumen:');
        $this->line('- show_btn = SI significa que, con la regla actual de Blade, el boton se muestra.');
        $this->line('- show_btn = NO significa que no deberia mostrarse.');

        return self::SUCCESS;
    }
}

