<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportarTodoExcel extends Command
{
    protected $signature = 'importar:todo-excel 
                            {--polo= : Ruta al archivo Excel de POLOS}
                            {--produccion= : Ruta al archivo Excel de PRODUCCION}
                            {--balanceo= : Ruta al archivo Excel de BALANCEO}
                            {--dry-run : Simular sin guardar en BD}
                            {--limpiar : Eliminar todos los registros antes de importar}';
    
    protected $description = 'Importar todos los archivos Excel de forma masiva (POLOS, PRODUCCION y BALANCEO)';

    private $stats = [
        'polos' => ['procesados' => 0, 'descartados' => 0],
        'produccion' => ['procesados' => 0, 'descartados' => 0],
        'balanceos' => ['procesados' => 0, 'descartados' => 0],
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limpiar = $this->option('limpiar');

        $archivoPolo = $this->option('polo') ?: resource_path('CONTROL DE PISO POLOS (Respuestas) .xlsx');
        $archivoProduccion = $this->option('produccion') ?: resource_path('CONTROL DE PISO PRODUCCION (respuestas) (1).xlsx');
        $archivoBalanceo = $this->option('balanceo') ?: resource_path('clasico (1).xlsx');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║     IMPORTACIÓN MASIVA DE DATOS DESDE EXCEL               ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Verificar archivos
        $archivos = [
            'POLOS' => $archivoPolo,
            'PRODUCCION' => $archivoProduccion,
            'BALANCEO' => $archivoBalanceo,
        ];

        foreach ($archivos as $nombre => $ruta) {
            if (!file_exists($ruta)) {
                $this->warn("⚠️  Archivo {$nombre} no encontrado: {$ruta}");
            } else {
                $this->info("✓ {$nombre}: " . basename($ruta));
            }
        }
        $this->newLine();

        // Limpiar tablas si se solicitó
        if ($limpiar && !$dryRun) {
            $this->warn("⚠️  ADVERTENCIA: Se eliminarán TODOS los registros existentes.");
            
            if (!$this->confirm('¿Estás seguro de continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
            
            $this->limpiarTablas();
        }

        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se guardará nada en la base de datos\n");
        }

        // Importar cada archivo
        try {
            if (file_exists($archivoPolo)) {
                $this->importarPolos($archivoPolo, $dryRun);
            }

            if (file_exists($archivoProduccion)) {
                $this->importarProduccion($archivoProduccion, $dryRun);
            }

            if (file_exists($archivoBalanceo)) {
                $this->importarBalanceos($archivoBalanceo, $dryRun);
            }

            // Mostrar resumen final
            $this->mostrarResumen($dryRun);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function limpiarTablas()
    {
        $this->info("🗑️  Limpiando tablas...");
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('registro_piso_polo')->truncate();
        DB::table('registro_piso_produccion')->truncate();
        DB::table('operaciones_balanceo')->truncate();
        DB::table('balanceos')->truncate();
        DB::table('prendas')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $this->info("✓ Tablas limpiadas\n");
    }

    private function importarPolos($archivo, $dryRun)
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 IMPORTANDO: REGISTRO PISO POLOS");
        $this->info("═══════════════════════════════════════════════════════════");

        $spreadsheet = IOFactory::load($archivo);
        $worksheet = $spreadsheet->getSheetByName('REGISTRO');
        
        if (!$worksheet) {
            $this->warn("⚠️  No se encontró la hoja 'REGISTRO'");
            return;
        }

        $datos = $worksheet->toArray();
        
        if (count($datos) < 2) {
            $this->warn("⚠️  No hay datos suficientes");
            return;
        }

        $encabezados = array_map(fn($h) => strtoupper(trim($h ?? '')), $datos[0]);
        $filas = array_slice($datos, 1);

        // Mapeo de columnas
        $mapaColumnas = [
            'FECHA' => 'fecha',
            'MODULO' => 'modulo',
            'ORDEN DE PRODUCCIÓN' => 'orden_produccion',
            'HORA' => 'hora',
            'TIEMPO DE CICLO' => 'tiempo_ciclo',
            'PORCIÓN DE TIEMPO' => 'porcion_tiempo',
            'CANTIDAD PRODUCIDA' => 'cantidad',
            'PARADAS PROGRAMADAS' => 'paradas_programadas',
            'PARADAS NO PROGRAMADAS' => 'paradas_no_programadas',
            'TIEMPO DE PARADA NO PROGRAMADA' => 'tiempo_parada_no_programada',
            'NÚMERO DE OPERARIOS' => 'numero_operarios',
            'TIEMPO PARA PROG' => 'tiempo_para_programada',
            'TIEMPO DISP' => 'tiempo_disponible',
            'META' => 'meta',
            'EFICIENCIA' => 'eficiencia'
        ];

        $registros = [];
        $descartadas = [];

        foreach ($filas as $index => $fila) {
            $numeroFila = $index + 2;
            $filaObj = [];

            // Mapear columnas
            foreach ($mapaColumnas as $encabezado => $columnaSQL) {
                $indexCol = array_search($encabezado, $encabezados);
                $filaObj[$columnaSQL] = $indexCol !== false ? $fila[$indexCol] : null;
            }

            // Validar fila vacía
            $filaVacia = empty(array_filter($filaObj, fn($v) => !empty($v)));
            if ($filaVacia) {
                $descartadas[] = "Fila {$numeroFila}: Fila completamente vacía";
                continue;
            }

            // Validar fecha u orden de producción
            if (empty($filaObj['fecha']) && empty($filaObj['orden_produccion'])) {
                $descartadas[] = "Fila {$numeroFila}: Sin fecha ni orden de producción";
                continue;
            }

            $registros[] = [
                'fecha' => $this->formatearFecha($filaObj['fecha']),
                'modulo' => $filaObj['modulo'] ?? '',
                'orden_produccion' => $filaObj['orden_produccion'] ?? '',
                'hora' => $filaObj['hora'] ?? '',
                'tiempo_ciclo' => $this->toDecimal($filaObj['tiempo_ciclo']),
                'porcion_tiempo' => $this->toDecimal($filaObj['porcion_tiempo']),
                'cantidad' => $this->toInt($filaObj['cantidad']),
                'paradas_programadas' => $filaObj['paradas_programadas'] ?? '',
                'paradas_no_programadas' => $filaObj['paradas_no_programadas'] ?? '',
                'tiempo_parada_no_programada' => $this->toDecimal($filaObj['tiempo_parada_no_programada']),
                'numero_operarios' => $this->toInt($filaObj['numero_operarios']),
                'tiempo_para_programada' => $this->toDecimal($filaObj['tiempo_para_programada']),
                'tiempo_disponible' => $this->toDecimal($filaObj['tiempo_disponible']),
                'meta' => $this->toDecimal($filaObj['meta']),
                'eficiencia' => $this->toDecimal($filaObj['eficiencia']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->stats['polos']['procesados'] = count($registros);
        $this->stats['polos']['descartados'] = count($descartadas);

        $this->info("✅ Registros procesados: " . count($registros));
        $this->warn("❌ Filas descartadas: " . count($descartadas));

        if (!$dryRun && !empty($registros)) {
            DB::table('registro_piso_polo')->insert($registros);
            $this->info("💾 Registros guardados en la base de datos");
        }

        $this->newLine();
    }

    private function importarProduccion($archivo, $dryRun)
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 IMPORTANDO: REGISTRO PISO PRODUCCION");
        $this->info("═══════════════════════════════════════════════════════════");

        $spreadsheet = IOFactory::load($archivo);
        $worksheet = $spreadsheet->getSheetByName('REGISTRO');
        
        if (!$worksheet) {
            $this->warn("⚠️  No se encontró la hoja 'REGISTRO'");
            return;
        }

        $datos = $worksheet->toArray();
        
        if (count($datos) < 2) {
            $this->warn("⚠️  No hay datos suficientes");
            return;
        }

        $encabezados = array_map(fn($h) => strtoupper(trim($h ?? '')), $datos[0]);
        $filas = array_slice($datos, 1);

        // Mapeo de columnas para producción
        $mapaColumnas = [
            'FECHA' => 'fecha',
            'MODULO' => 'modulo',
            'ORDEN DE PRODUCCIÓN' => 'orden_produccion',
            'HORA' => 'hora',
            'TIEMPO DE CICLO' => 'tiempo_ciclo',
            'PORCIÓN DE TIEMPO' => 'porcion_tiempo',
            'CANTIDAD PRODUCIDA' => 'cantidad',
            'PARADAS PROGRAMADAS' => 'paradas_programadas',
            'PARADAS NO PROGRAMADAS' => 'paradas_no_programadas',
            'TIEMPO DE PARADA NO PROGRAMADA' => 'tiempo_parada_no_programada',
            'NÚMERO DE OPERARIOS' => 'numero_operarios',
            'TIEMPO PARA PROG' => 'tiempo_para_programada',
            'TIEMPO DISP' => 'tiempo_disponible',
            'META' => 'meta',
            'EFICIENCIA' => 'eficiencia'
        ];

        $registros = [];
        $descartadas = [];

        foreach ($filas as $index => $fila) {
            $numeroFila = $index + 2;
            $filaObj = [];

            // Mapear columnas
            foreach ($mapaColumnas as $encabezado => $columnaSQL) {
                $indexCol = array_search($encabezado, $encabezados);
                $filaObj[$columnaSQL] = $indexCol !== false ? $fila[$indexCol] : null;
            }

            // Validar fila vacía
            $filaVacia = empty(array_filter($filaObj, fn($v) => !empty($v)));
            if ($filaVacia) {
                $descartadas[] = "Fila {$numeroFila}: Fila completamente vacía";
                continue;
            }

            // Validar fecha u orden de producción
            if (empty($filaObj['fecha']) && empty($filaObj['orden_produccion'])) {
                $descartadas[] = "Fila {$numeroFila}: Sin fecha ni orden de producción";
                continue;
            }

            $registros[] = [
                'fecha' => $this->formatearFecha($filaObj['fecha']),
                'modulo' => $filaObj['modulo'] ?? '',
                'orden_produccion' => $filaObj['orden_produccion'] ?? '',
                'hora' => $filaObj['hora'] ?? '',
                'tiempo_ciclo' => $this->toDecimal($filaObj['tiempo_ciclo']),
                'porcion_tiempo' => $this->toDecimal($filaObj['porcion_tiempo']),
                'cantidad' => $this->toInt($filaObj['cantidad']),
                'paradas_programadas' => $filaObj['paradas_programadas'] ?? '',
                'paradas_no_programadas' => $filaObj['paradas_no_programadas'] ?? '',
                'tiempo_parada_no_programada' => $this->toDecimal($filaObj['tiempo_parada_no_programada']),
                'numero_operarios' => $this->toInt($filaObj['numero_operarios']),
                'tiempo_para_programada' => $this->toDecimal($filaObj['tiempo_para_programada']),
                'tiempo_disponible' => $this->toDecimal($filaObj['tiempo_disponible']),
                'meta' => $this->toDecimal($filaObj['meta']),
                'eficiencia' => $this->toDecimal($filaObj['eficiencia']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $this->stats['produccion']['procesados'] = count($registros);
        $this->stats['produccion']['descartados'] = count($descartadas);

        $this->info("✅ Registros procesados: " . count($registros));
        $this->warn("❌ Filas descartadas: " . count($descartadas));

        if (!$dryRun && !empty($registros)) {
            DB::table('registro_piso_produccion')->insert($registros);
            $this->info("💾 Registros guardados en la base de datos");
        }

        $this->newLine();
    }

    private function importarBalanceos($archivo, $dryRun)
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 IMPORTANDO: BALANCEOS (CLASICO)");
        $this->info("═══════════════════════════════════════════════════════════");

        $spreadsheet = IOFactory::load($archivo);
        $totalHojas = $spreadsheet->getSheetCount();
        
        $this->info("📄 Hojas encontradas: {$totalHojas}");

        for ($i = 0; $i < $totalHojas; $i++) {
            $worksheet = $spreadsheet->getSheet($i);
            $nombreHoja = $worksheet->getTitle();
            $sheet = $worksheet->toArray();
            
            $this->line("\n" . str_repeat('-', 60));
            $this->info("📄 Procesando hoja: {$nombreHoja}");
            
            $this->procesarHojaBalanceo($sheet, $dryRun, $nombreHoja);
        }

        $this->newLine();
    }

    private function procesarHojaBalanceo($sheet, $dryRun, $nombreHoja)
    {
        $nombrePrenda = $nombreHoja ?? 'Prenda Importada ' . date('Y-m-d H:i:s');
        $refBase = substr($nombrePrenda, 0, 15);
        $refBase = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $refBase) ?: $refBase;
        $refBase = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $refBase));
        $refBase = empty($refBase) ? 'PRENDA' : $refBase;
        $referencia = 'REF-' . strtoupper($refBase) . '-' . uniqid();

        // Buscar encabezados
        $headerRow = null;
        $startRow = null;

        for ($i = 0; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            if (empty($row)) continue;

            $hasLetra = false;
            $hasSam = false;

            foreach ($row as $cell) {
                $cellUpper = strtoupper(trim($cell ?? ''));
                if ($cellUpper === 'LETRA') $hasLetra = true;
                if ($cellUpper === 'SAM') $hasSam = true;
            }

            if ($hasLetra && $hasSam) {
                $headerRow = $row;
                $startRow = $i + 1;
                break;
            }
        }

        if (!$headerRow || !$startRow) {
            $this->warn("   ⚠️  No se encontraron encabezados de operaciones");
            return;
        }

        // Mapear columnas
        $colLetra = null;
        $colOperacion = null;
        $colPrecedencia = null;
        $colMaquina = null;
        $colSam = null;
        $colOperario = null;
        $colOp = null;
        $colSeccion = null;

        foreach ($headerRow as $index => $header) {
            $headerUpper = strtoupper(trim($header ?? ''));
            
            if ($headerUpper === 'LETRA') $colLetra = $index;
            if ($headerUpper === 'OPERACIÓN' || $headerUpper === 'OPERACION') $colOperacion = $index;
            if ($headerUpper === 'PRECEDENCIA') $colPrecedencia = $index;
            if ($headerUpper === 'MAQUINA' || $headerUpper === 'MÁQUINA') $colMaquina = $index;
            if ($headerUpper === 'SAM') $colSam = $index;
            if ($headerUpper === 'OPERARIO') $colOperario = $index;
            if ($headerUpper === 'OP') $colOp = $index;
            if ($headerUpper === 'SECCIÓN' || $headerUpper === 'SECCION') $colSeccion = $index;
        }

        if ($colSam === null) {
            $this->error("   ❌ No se encontró la columna SAM");
            return;
        }

        // Leer operaciones
        $operaciones = [];
        $letraActual = 'A';

        for ($i = $startRow; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            
            if (empty($row) || !isset($row[$colOperacion]) || !isset($row[$colSam])) {
                continue;
            }

            $operacion = trim($row[$colOperacion] ?? '');
            $samRaw = $row[$colSam] ?? 0;

            if (empty($operacion) || stripos($operacion, 'total') !== false || stripos($operacion, 'meta') !== false) {
                continue;
            }
            
            if (is_numeric($samRaw) && $samRaw > 500) {
                continue;
            }

            $sam = is_numeric($samRaw) ? (float) $samRaw : (float) str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $samRaw));

            if ($sam < 0) {
                continue;
            }

            $letra = $colLetra !== null && isset($row[$colLetra]) ? trim($row[$colLetra]) : $letraActual++;
            $precedencia = $colPrecedencia !== null && isset($row[$colPrecedencia]) ? trim($row[$colPrecedencia]) : '';
            $maquina = $colMaquina !== null && isset($row[$colMaquina]) ? trim($row[$colMaquina]) : '';
            $operario = $colOperario !== null && isset($row[$colOperario]) ? trim($row[$colOperario]) : null;
            $op = $colOp !== null && isset($row[$colOp]) ? trim($row[$colOp]) : null;
            $seccion = $colSeccion !== null && isset($row[$colSeccion]) ? strtoupper(trim($row[$colSeccion])) : 'OTRO';

            if ($precedencia === 'N/A' || $precedencia === 'n/a') $precedencia = '';
            if ($maquina === 'N/A' || $maquina === 'n/a') $maquina = '';
            
            $seccionesValidas = ['DEL', 'TRAS', 'ENS', 'OTRO'];
            if (!in_array($seccion, $seccionesValidas)) {
                $seccion = 'OTRO';
            }

            $operaciones[] = [
                'letra' => $letra,
                'operacion' => $operacion,
                'precedencia' => $precedencia,
                'maquina' => $maquina,
                'sam' => $sam,
                'operario' => $operario,
                'op' => $op,
                'seccion' => strtoupper($seccion),
            ];
        }

        if (empty($operaciones)) {
            $this->warn("   ⚠️  No se encontraron operaciones válidas");
            return;
        }

        $samTotal = array_sum(array_column($operaciones, 'sam'));
        $this->info("   ✅ Operaciones: " . count($operaciones) . " | SAM Total: " . round($samTotal, 1));

        $this->stats['balanceos']['procesados'] += count($operaciones);

        if ($dryRun) {
            return;
        }

        // Guardar en base de datos
        DB::beginTransaction();
        try {
            $nombreFinal = $nombrePrenda;
            $contador = 1;
            
            while (Prenda::where('nombre', $nombreFinal)->exists()) {
                $contador++;
                $nombreFinal = $nombrePrenda . " (v{$contador})";
            }
            
            $prenda = Prenda::create([
                'nombre' => $nombreFinal,
                'descripcion' => $nombrePrenda,
                'referencia' => $referencia,
                'tipo' => 'pantalon',
                'activo' => true,
            ]);

            $balanceo = Balanceo::create([
                'prenda_id' => $prenda->id,
                'version' => '1.0',
                'total_operarios' => 10,
                'turnos' => 1,
                'horas_por_turno' => 8.0,
                'activo' => true,
            ]);

            $orden = 0;
            foreach ($operaciones as $opData) {
                OperacionBalanceo::create([
                    'balanceo_id' => $balanceo->id,
                    'letra' => $opData['letra'],
                    'operacion' => $opData['operacion'],
                    'precedencia' => $opData['precedencia'] ?: null,
                    'maquina' => $opData['maquina'] ?: null,
                    'sam' => $opData['sam'],
                    'operario' => $opData['operario'],
                    'op' => $opData['op'] ?: null,
                    'seccion' => $opData['seccion'],
                    'orden' => $orden++,
                ]);
            }

            $balanceo->calcularMetricas();

            DB::commit();
            $this->info("   💾 Balanceo guardado: {$nombreFinal}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("   ❌ Error al guardar: " . $e->getMessage());
        }
    }

    private function mostrarResumen($dryRun)
    {
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║                    RESUMEN FINAL                           ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("📊 POLOS:");
        $this->line("   ✅ Procesados: " . $this->stats['polos']['procesados']);
        $this->line("   ❌ Descartados: " . $this->stats['polos']['descartados']);
        
        $this->newLine();
        $this->info("📊 PRODUCCION:");
        $this->line("   ✅ Procesados: " . $this->stats['produccion']['procesados']);
        $this->line("   ❌ Descartados: " . $this->stats['produccion']['descartados']);
        
        $this->newLine();
        $this->info("📊 BALANCEOS:");
        $this->line("   ✅ Operaciones procesadas: " . $this->stats['balanceos']['procesados']);

        $this->newLine();
        
        if ($dryRun) {
            $this->warn("⚠️  MODO DRY-RUN: No se guardó nada en la base de datos");
            $this->info("💡 Ejecuta sin --dry-run para guardar los datos");
        } else {
            $this->info("✅ IMPORTACIÓN COMPLETADA EXITOSAMENTE");
        }
    }

    // Funciones auxiliares
    private function formatearFecha($fecha)
    {
        if ($fecha instanceof \DateTime) {
            return $fecha->format('Y-m-d');
        }
        
        $s = $fecha ? trim($fecha) : "";
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return $s;
        }
        
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $s, $matches)) {
            $dia = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $mes = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $anio = $matches[3];
            return "{$anio}-{$mes}-{$dia}";
        }
        
        return $s ?: null;
    }

    private function toInt($valor)
    {
        if ($valor === null || $valor === '') return null;
        
        $limpio = is_numeric($valor) ? $valor : str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $valor));
        $num = (int) $limpio;
        
        return $num;
    }

    private function toDecimal($valor)
    {
        if ($valor === null || $valor === '') return null;
        
        $limpio = is_numeric($valor) ? $valor : str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $valor));
        $num = (float) $limpio;
        
        return $num;
    }
}
