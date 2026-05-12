<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseReflectivoOrder extends Command
{
    protected $signature = 'diagnose:reflectivo-order
        {--ui= : Lista de consecutivos en el orden visual del dashboard. Ej: 84,43,88}
        {--direction=desc : Direccion esperada segun created_at: asc|desc}
        {--limit=100 : Limite de filas a listar cuando no se envia --ui}';

    protected $description = 'Diagnostica si el orden visual de recibos REFLECTIVO coincide con consecutivos_recibos_pedidos.created_at';

    public function handle(): int
    {
        $direction = strtolower((string) $this->option('direction'));
        if (!in_array($direction, ['asc', 'desc'], true)) {
            $this->error("Direccion invalida: {$direction}. Usa asc o desc.");
            return self::FAILURE;
        }

        $uiRaw = trim((string) $this->option('ui'));
        if ($uiRaw !== '') {
            return $this->diagnoseUiSequence($uiRaw, $direction);
        }

        return $this->showTopReflectivo($direction);
    }

    private function diagnoseUiSequence(string $uiRaw, string $direction): int
    {
        $uiList = collect(explode(',', $uiRaw))
            ->map(fn($v) => (int) trim($v))
            ->filter(fn($v) => $v > 0)
            ->values();

        if ($uiList->isEmpty()) {
            $this->error('La lista de --ui esta vacia o invalida.');
            return self::FAILURE;
        }

        $rows = DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->select(
                'crp.id',
                'crp.consecutivo_actual',
                'crp.created_at',
                'crp.notas',
                'crp.pedido_produccion_id',
                'pp.numero_pedido',
                'pp.cliente'
            )
            ->whereRaw('UPPER(TRIM(crp.tipo_recibo)) = ?', ['REFLECTIVO'])
            ->where('crp.activo', 1)
            ->whereIn('crp.consecutivo_actual', $uiList->all())
            ->orderBy('crp.created_at', 'desc')
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No se encontraron recibos REFLECTIVO activos para los consecutivos indicados.');
            return self::SUCCESS;
        }

        $picked = [];
        foreach ($uiList as $cons) {
            $candidate = $rows
                ->where('consecutivo_actual', $cons)
                ->sortByDesc('created_at')
                ->first();

            if ($candidate) {
                $picked[] = $candidate;
            }
        }

        if (empty($picked)) {
            $this->warn('No se pudo mapear la lista visual con filas de base de datos.');
            return self::SUCCESS;
        }

        $table = [];
        foreach ($picked as $index => $row) {
            $table[] = [
                'ui_pos' => $index + 1,
                'consecutivo' => $row->consecutivo_actual,
                'recibo_id' => $row->id,
                'created_at' => (string) $row->created_at,
                'pedido' => $row->numero_pedido,
                'cliente' => $row->cliente,
            ];
        }

        $this->info("Diagnostico con secuencia visual UI (direction esperada: {$direction})");
        $this->table(['ui_pos', 'consecutivo', 'recibo_id', 'created_at', 'pedido', 'cliente'], $table);

        $errors = [];
        for ($i = 1; $i < count($picked); $i++) {
            $prev = $picked[$i - 1];
            $curr = $picked[$i];

            $prevTs = strtotime((string) $prev->created_at);
            $currTs = strtotime((string) $curr->created_at);

            $isOk = $direction === 'desc'
                ? $prevTs >= $currTs
                : $prevTs <= $currTs;

            if (!$isOk) {
                $errors[] = [
                    'pos_prev' => $i,
                    'prev' => $prev->consecutivo_actual . ' @ ' . $prev->created_at,
                    'pos_curr' => $i + 1,
                    'curr' => $curr->consecutivo_actual . ' @ ' . $curr->created_at,
                ];
            }
        }

        if (empty($errors)) {
            $this->info('OK: la secuencia visual SI respeta el orden por created_at.');
        } else {
            $this->error('Se detectaron inversiones respecto a created_at:');
            $this->table(['pos_prev', 'prev', 'pos_curr', 'curr'], $errors);
        }

        return self::SUCCESS;
    }

    private function showTopReflectivo(string $direction): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $rows = DB::table('consecutivos_recibos_pedidos as crp')
            ->leftJoin('pedidos_produccion as pp', 'pp.id', '=', 'crp.pedido_produccion_id')
            ->select(
                'crp.id',
                'crp.consecutivo_actual',
                'crp.created_at',
                'crp.area',
                'crp.estado',
                'pp.numero_pedido',
                'pp.cliente'
            )
            ->whereRaw('UPPER(TRIM(crp.tipo_recibo)) = ?', ['REFLECTIVO'])
            ->where('crp.activo', 1)
            ->orderBy('crp.created_at', $direction)
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            $this->warn('No hay recibos REFLECTIVO activos.');
            return self::SUCCESS;
        }

        $this->info("Top reflectivo ordenado por created_at {$direction}");
        $this->table(
            ['id', 'consecutivo', 'created_at', 'area', 'estado', 'pedido', 'cliente'],
            $rows->map(fn($r) => [
                'id' => $r->id,
                'consecutivo' => $r->consecutivo_actual,
                'created_at' => (string) $r->created_at,
                'area' => $r->area,
                'estado' => $r->estado,
                'pedido' => $r->numero_pedido,
                'cliente' => $r->cliente,
            ])->all()
        );

        return self::SUCCESS;
    }
}

