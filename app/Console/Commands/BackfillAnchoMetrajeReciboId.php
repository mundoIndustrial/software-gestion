<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAnchoMetrajeReciboId extends Command
{
    protected $signature = 'insumos:backfill-ancho-metraje-recibo-id
        {--table=all : Tabla objetivo: all|ancho|metraje}
        {--dry-run : Solo reporta, no actualiza}
        {--report= : Ruta CSV para exportar ambiguos y sin match}';

    protected $description = 'Completa consecutivo_recibo_id en pedido_ancho_general y pedido_metraje_color usando consecutivos_recibos_pedidos';

    public function handle(): int
    {
        if (!$this->validarColumnas()) {
            return self::FAILURE;
        }

        $tableOption = strtolower(trim((string) $this->option('table')));
        $dryRun = (bool) $this->option('dry-run');

        $tables = match ($tableOption) {
            'ancho' => ['pedido_ancho_general'],
            'metraje' => ['pedido_metraje_color'],
            'all', '' => ['pedido_ancho_general', 'pedido_metraje_color'],
            default => null,
        };

        if ($tables === null) {
            $this->error('Opción inválida para --table. Usa: all, ancho o metraje.');
            return self::FAILURE;
        }

        $this->info('Iniciando backfill de consecutivo_recibo_id');
        $this->line('Modo: ' . ($dryRun ? 'DRY RUN' : 'EJECUCIÓN'));

        $globalStats = [
            'evaluated' => 0,
            'updated' => 0,
            'ambiguous' => 0,
            'missing' => 0,
            'skipped' => 0,
        ];
        $reportRows = [];

        foreach ($tables as $table) {
            $stats = $this->procesarTabla($table, $dryRun, $reportRows);
            foreach ($stats as $key => $value) {
                $globalStats[$key] += $value;
            }
        }

        $this->newLine();
        $this->info('Resumen global');
        $this->table(
            ['evaluados', 'actualizados', 'ambiguos', 'sin match', 'saltados'],
            [[
                $globalStats['evaluated'],
                $globalStats['updated'],
                $globalStats['ambiguous'],
                $globalStats['missing'],
                $globalStats['skipped'],
            ]]
        );

        $reportPath = trim((string) $this->option('report'));
        if ($reportPath !== '') {
            $this->exportarReporteCsv($reportPath, $reportRows);
        } elseif (!empty($reportRows)) {
            $this->newLine();
            $this->warn('Se detectaron filas ambiguas o sin match.');
            $this->table(
                ['tipo', 'tabla', 'fila_id', 'pedido_id', 'prenda_id', 'prenda_bodega_id', 'numero_recibo', 'match_ids'],
                collect($reportRows)->take(20)->map(fn ($row) => [
                    $row['issue_type'],
                    $row['table'],
                    $row['row_id'],
                    $row['pedido_produccion_id'],
                    $row['prenda_pedido_id'],
                    $row['prenda_bodega_id'],
                    $row['numero_recibo'],
                    $row['match_ids'],
                ])->all()
            );
            if (count($reportRows) > 20) {
                $this->line('Mostrando solo los primeros 20 conflictos. Usa --report=archivo.csv para exportarlos todos.');
            }
        }

        return self::SUCCESS;
    }

    private function validarColumnas(): bool
    {
        $faltantes = [];

        if (!Schema::hasColumn('pedido_ancho_general', 'consecutivo_recibo_id')) {
            $faltantes[] = 'pedido_ancho_general.consecutivo_recibo_id';
        }
        if (!Schema::hasColumn('pedido_metraje_color', 'consecutivo_recibo_id')) {
            $faltantes[] = 'pedido_metraje_color.consecutivo_recibo_id';
        }

        if (!empty($faltantes)) {
            $this->error('Faltan columnas requeridas: ' . implode(', ', $faltantes));
            return false;
        }

        return true;
    }

    private function procesarTabla(string $table, bool $dryRun, array &$reportRows): array
    {
        $stats = [
            'evaluated' => 0,
            'updated' => 0,
            'ambiguous' => 0,
            'missing' => 0,
            'skipped' => 0,
        ];

        $label = $table === 'pedido_ancho_general' ? 'ANCHO' : 'METRAJE';
        $this->newLine();
        $this->info("Procesando {$label} ({$table})");

        DB::table($table)
            ->select([
                'id',
                'pedido_produccion_id',
                'prenda_pedido_id',
                'prenda_bodega_id',
                'numero_recibo',
                'consecutivo_recibo_id',
            ])
            ->whereNull('consecutivo_recibo_id')
            ->whereNotNull('numero_recibo')
            ->orderBy('id')
            ->chunkById(200, function (Collection $rows) use ($table, $dryRun, &$stats) {
                foreach ($rows as $row) {
                    $stats['evaluated']++;

                    $numeroRecibo = (int) ($row->numero_recibo ?? 0);
                    if ($numeroRecibo <= 0) {
                        $stats['skipped']++;
                        continue;
                    }

                    $matchIds = $this->resolverReciboIds($row);

                    if ($matchIds->count() === 0) {
                        $stats['missing']++;
                        $reportRows[] = $this->buildReportRow($table, $row, 'missing', []);
                        continue;
                    }

                    if ($matchIds->count() > 1) {
                        $stats['ambiguous']++;
                        $reportRows[] = $this->buildReportRow($table, $row, 'ambiguous', $matchIds->all());
                        $this->warn(sprintf(
                            '[%s:%d] Ambiguo para numero_recibo=%s -> IDs [%s]',
                            $table,
                            (int) $row->id,
                            $numeroRecibo,
                            $matchIds->implode(', ')
                        ));
                        continue;
                    }

                    $reciboId = (int) $matchIds->first();
                    if (!$dryRun) {
                        DB::table($table)
                            ->where('id', (int) $row->id)
                            ->update([
                                'consecutivo_recibo_id' => $reciboId,
                                'updated_at' => now(),
                            ]);
                    }

                    $stats['updated']++;
                }
            }, 'id');

        $this->table(
            ['tabla', 'evaluados', 'actualizados', 'ambiguos', 'sin match', 'saltados'],
            [[
                $table,
                $stats['evaluated'],
                $stats['updated'],
                $stats['ambiguous'],
                $stats['missing'],
                $stats['skipped'],
            ]]
        );

        return $stats;
    }

    private function resolverReciboIds(object $row): Collection
    {
        $query = DB::table('consecutivos_recibos_pedidos')
            ->select('id')
            ->where('consecutivo_actual', (int) $row->numero_recibo);

        $prendaBodegaId = (int) ($row->prenda_bodega_id ?? 0);
        $pedidoProduccionId = (int) ($row->pedido_produccion_id ?? 0);
        $prendaPedidoId = (int) ($row->prenda_pedido_id ?? 0);

        if ($prendaBodegaId > 0) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
        } else {
            if ($pedidoProduccionId > 0) {
                $query->where('pedido_produccion_id', $pedidoProduccionId);
            }
            if ($prendaPedidoId > 0) {
                $query->where('prenda_id', $prendaPedidoId);
            }
        }

        return $query
            ->orderByDesc('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function buildReportRow(string $table, object $row, string $issueType, array $matchIds): array
    {
        return [
            'issue_type' => $issueType,
            'table' => $table,
            'row_id' => (int) ($row->id ?? 0),
            'pedido_produccion_id' => (int) ($row->pedido_produccion_id ?? 0),
            'prenda_pedido_id' => (int) ($row->prenda_pedido_id ?? 0),
            'prenda_bodega_id' => (int) ($row->prenda_bodega_id ?? 0),
            'numero_recibo' => (string) ($row->numero_recibo ?? ''),
            'match_ids' => implode('|', $matchIds),
        ];
    }

    private function exportarReporteCsv(string $reportPath, array $reportRows): void
    {
        $directory = dirname($reportPath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $handle = fopen($reportPath, 'w');
        if ($handle === false) {
            $this->error("No se pudo escribir el reporte en: {$reportPath}");
            return;
        }

        fputcsv($handle, [
            'issue_type',
            'table',
            'row_id',
            'pedido_produccion_id',
            'prenda_pedido_id',
            'prenda_bodega_id',
            'numero_recibo',
            'match_ids',
        ]);

        foreach ($reportRows as $row) {
            fputcsv($handle, [
                $row['issue_type'],
                $row['table'],
                $row['row_id'],
                $row['pedido_produccion_id'],
                $row['prenda_pedido_id'],
                $row['prenda_bodega_id'],
                $row['numero_recibo'],
                $row['match_ids'],
            ]);
        }

        fclose($handle);
        $this->info('Reporte exportado: ' . $reportPath);
    }
}
