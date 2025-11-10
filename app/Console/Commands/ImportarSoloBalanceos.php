<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportarSoloBalanceos extends Command
{
    protected $signature = 'importar:solo-balanceos 
                            {archivo? : Ruta al archivo Excel de BALANCEO}
                            {--limpiar : Eliminar balanceos existentes antes de importar}';
    
    protected $description = 'Importar solo los balanceos desde archivo Excel';

    private $totalOperaciones = 0;
    private $totalBalanceos = 0;

    public function handle()
    {
        $archivo = $this->argument('archivo') ?: resource_path('clasico (1).xlsx');
        $limpiar = $this->option('limpiar');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║           IMPORTACIÓN DE BALANCEOS DESDE EXCEL             ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        if (!file_exists($archivo)) {
            $this->error("❌ El archivo no existe: {$archivo}");
            return 1;
        }

        $this->info("✓ Archivo: " . basename($archivo));
        $this->newLine();

        // Limpiar tablas si se solicitó
        if ($limpiar) {
            $this->warn("⚠️  ADVERTENCIA: Se eliminarán los balanceos existentes:");
            $this->line("   • operaciones_balanceo");
            $this->line("   • balanceos");
            $this->line("   • prendas");
            $this->newLine();
            
            if (!$this->confirm('¿Estás seguro de continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
            
            $this->limpiarTablas();
        }

        try {
            $this->importarBalanceos($archivo);
            
            $this->newLine();
            $this->info("╔════════════════════════════════════════════════════════════╗");
            $this->info("║                    RESUMEN FINAL                           ║");
            $this->info("╚════════════════════════════════════════════════════════════╝");
            $this->newLine();
            $this->info("📊 BALANCEOS:");
            $this->line("   ✅ Balanceos importados: {$this->totalBalanceos}");
            $this->line("   ✅ Operaciones procesadas: {$this->totalOperaciones}");
            $this->newLine();
            $this->info("✅ IMPORTACIÓN COMPLETADA EXITOSAMENTE");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function limpiarTablas()
    {
        $this->info("🗑️  Limpiando tablas de balanceos...");
        $this->newLine();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        $countOperaciones = DB::table('operaciones_balanceo')->count();
        DB::table('operaciones_balanceo')->truncate();
        $this->line("   ✓ operaciones_balanceo ({$countOperaciones} registros eliminados)");
        
        $countBalanceos = DB::table('balanceos')->count();
        DB::table('balanceos')->truncate();
        $this->line("   ✓ balanceos ({$countBalanceos} registros eliminados)");
        
        $countPrendas = DB::table('prendas')->count();
        DB::table('prendas')->truncate();
        $this->line("   ✓ prendas ({$countPrendas} registros eliminados)");
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->newLine();
        $this->info("✅ Tablas limpiadas exitosamente");
        $this->newLine();
    }

    private function importarBalanceos($archivo)
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 IMPORTANDO: BALANCEOS (CLASICO)");
        $this->info("═══════════════════════════════════════════════════════════");

        $spreadsheet = IOFactory::load($archivo);
        $totalHojas = $spreadsheet->getSheetCount();
        
        $this->info("📄 Hojas encontradas: {$totalHojas}");
        $this->newLine();

        for ($i = 0; $i < $totalHojas; $i++) {
            $worksheet = $spreadsheet->getSheet($i);
            $nombreHoja = $worksheet->getTitle();
            $sheet = $worksheet->toArray();
            
            $this->line(str_repeat('-', 60));
            $this->info("📄 Procesando hoja: {$nombreHoja}");
            
            $this->procesarHojaBalanceo($sheet, $nombreHoja);
        }

        $this->newLine();
    }

    private function procesarHojaBalanceo($sheet, $nombreHoja)
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

            // Solo saltar si contiene 'total' o 'meta' en la operación
            if (!empty($operacion) && (stripos($operacion, 'total') !== false || stripos($operacion, 'meta') !== false)) {
                continue;
            }

            $sam = is_numeric($samRaw) ? (float) $samRaw : (float) str_replace(',', '.', preg_replace('/[^0-9.,]/', '', $samRaw));

            // Solo saltar si SAM es 0 o negativo (filas inválidas)
            if ($sam <= 0) {
                continue;
            }

            // Manejar letra vacía: si está vacía o no existe, usar auto-incremento
            $letraValue = $colLetra !== null && isset($row[$colLetra]) ? trim($row[$colLetra]) : '';
            $letra = !empty($letraValue) ? $letraValue : $letraActual++;
            
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
                'operacion' => !empty($operacion) ? $operacion : null,
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

            $this->line("   💾 Prenda creada: {$nombreFinal}");

            $balanceo = Balanceo::create([
                'prenda_id' => $prenda->id,
                'version' => '1.0',
                'total_operarios' => 10,
                'turnos' => 1,
                'horas_por_turno' => 8.0,
                'porcentaje_eficiencia' => 90.00,
                'activo' => true,
            ]);

            $this->line("   💾 Balanceo creado");

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

            $this->line("   💾 " . count($operaciones) . " operaciones creadas");

            $balanceo->calcularMetricas();

            DB::commit();
            
            $this->totalBalanceos++;
            $this->totalOperaciones += count($operaciones);
            
            $this->info("   ✅ Balanceo guardado exitosamente");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("   ❌ Error al guardar: " . $e->getMessage());
        }
    }
}
