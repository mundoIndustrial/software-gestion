<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prenda;
use App\Models\Balanceo;
use App\Models\OperacionBalanceo;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportarBalanceosExcel extends Command
{
    protected $signature = 'balanceo:importar-excel {archivo} {--dry-run : Simular sin guardar en BD}';
    protected $description = 'Importar balanceos desde archivo Excel';

    public function handle()
    {
        $archivo = $this->argument('archivo');
        $dryRun = $this->option('dry-run');

        if (!file_exists($archivo)) {
            $this->error("❌ El archivo no existe: {$archivo}");
            return 1;
        }

        $this->info("📂 Leyendo archivo: {$archivo}");
        
        try {
            // Leer el archivo Excel usando PhpSpreadsheet
            $spreadsheet = IOFactory::load($archivo);
            
            $totalHojas = $spreadsheet->getSheetCount();
            $this->info("📊 Hojas encontradas: " . $totalHojas);
            
            // Procesar cada hoja como un balanceo diferente
            for ($i = 0; $i < $totalHojas; $i++) {
                $worksheet = $spreadsheet->getSheet($i);
                $nombreHoja = $worksheet->getTitle();
                $sheet = $worksheet->toArray();
                
                $this->line("\n" . str_repeat('=', 60));
                $this->info("📄 Procesando hoja: {$nombreHoja}");
                $this->procesarHoja($sheet, $dryRun, $nombreHoja);
            }

            if ($dryRun) {
                $this->warn("\n⚠️  Modo DRY-RUN: No se guardó nada en la base de datos");
                $this->info("💡 Ejecuta sin --dry-run para guardar los datos");
            } else {
                $this->info("\n✅ Importación completada exitosamente");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function procesarHoja($sheet, $dryRun, $nombreHoja = null)
    {
        // Usar el nombre de la hoja como nombre de la prenda
        $nombrePrenda = $nombreHoja ?? 'Prenda Importada ' . date('Y-m-d H:i:s');
        $descripcion = $nombrePrenda;
        // Generar referencia única con microtime para evitar duplicados
        // Limpiar caracteres especiales de la referencia
        $refBase = substr($nombrePrenda, 0, 15);
        // Intentar convertir caracteres especiales, si falla usar solo alfanuméricos
        $refBase = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $refBase) ?: $refBase;
        $refBase = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $refBase));
        // Si quedó vacío, usar un nombre genérico
        if (empty($refBase)) {
            $refBase = 'PRENDA';
        }
        $referencia = 'REF-' . strtoupper($refBase) . '-' . uniqid();
        $tipo = 'pantalon'; // Por defecto (valores válidos: camisa, pantalon, polo, chaqueta, vestido, otro)
        $totalOperarios = 10;
        $turnos = 1;
        $horasPorTurno = 8.0;

        $this->info("👕 Prenda: {$nombrePrenda}");
        $this->info("📝 Referencia: {$referencia}");
        $this->info("👥 Operarios: {$totalOperarios} | Turnos: {$turnos} | Horas: {$horasPorTurno}");

        // Buscar la fila de encabezados de operaciones
        // Encabezados esperados: LETRA, OPERACIÓN, PRECEDENCIA, MAQUINA, SAM, OPERARIO, OP, SECCIÓN
        $headerRow = null;
        $startRow = null;

        for ($i = 0; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            if (empty($row)) continue;

            // Buscar la fila que contiene "LETRA" y "SAM" (columnas mínimas requeridas)
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
            $this->warn("⚠️  No se encontraron encabezados de operaciones en esta hoja");
            return;
        }

        // Mapear columnas según los encabezados exactos
        // LETRA, OPERACIÓN, PRECEDENCIA, MAQUINA, SAM, OPERARIO, OP, SECCIÓN
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
            $this->error("❌ No se encontró la columna SAM");
            return;
        }

        // Si no hay columna OPERACIÓN, buscar cualquier columna con datos de texto
        if ($colOperacion === null) {
            // Buscar la primera columna después de LETRA que tenga texto
            for ($i = ($colLetra ?? 0) + 1; $i < count($headerRow); $i++) {
                if ($i !== $colSam && $i !== $colMaquina && $i !== $colOperario) {
                    $colOperacion = $i;
                    break;
                }
            }
        }

        $this->info("📋 Columnas detectadas:");
        $this->line("   LETRA: " . ($colLetra !== null ? "Col " . ($colLetra + 1) : "Auto"));
        $this->line("   OPERACIÓN: " . ($colOperacion !== null ? "Col " . ($colOperacion + 1) : "No encontrada"));
        $this->line("   SAM: Col " . ($colSam + 1));
        $this->line("   PRECEDENCIA: " . ($colPrecedencia !== null ? "Col " . ($colPrecedencia + 1) : "N/A"));
        $this->line("   MAQUINA: " . ($colMaquina !== null ? "Col " . ($colMaquina + 1) : "N/A"));
        $this->line("   OPERARIO: " . ($colOperario !== null ? "Col " . ($colOperario + 1) : "N/A"));
        $this->line("   OP: " . ($colOp !== null ? "Col " . ($colOp + 1) : "N/A"));
        $this->line("   SECCIÓN: " . ($colSeccion !== null ? "Col " . ($colSeccion + 1) : "N/A"));

        // Leer operaciones
        $operaciones = [];
        $letraActual = 'A';

        for ($i = $startRow; $i < count($sheet); $i++) {
            $row = $sheet[$i];
            
            if (empty($row) || !isset($row[$colOperacion]) || !isset($row[$colSam])) {
                continue;
            }

            $operacion = trim($row[$colOperacion] ?? '');
            $sam = $row[$colSam] ?? 0;

            // Saltar filas vacías o totales
            if (empty($operacion) || stripos($operacion, 'total') !== false) {
                continue;
            }

            // Limpiar SAM (remover caracteres no numéricos excepto punto y coma)
            $sam = preg_replace('/[^0-9.,]/', '', $sam);
            $sam = str_replace(',', '.', $sam);
            $sam = (float) $sam;

            // Permitir SAM = 0 (no saltar operaciones con tiempo 0)
            // Solo saltar si SAM es negativo (error de datos)
            if ($sam < 0) {
                continue;
            }

            $letra = $colLetra !== null && isset($row[$colLetra]) ? trim($row[$colLetra]) : $letraActual++;
            $precedencia = $colPrecedencia !== null && isset($row[$colPrecedencia]) ? trim($row[$colPrecedencia]) : '';
            $maquina = $colMaquina !== null && isset($row[$colMaquina]) ? trim($row[$colMaquina]) : '';
            $operario = $colOperario !== null && isset($row[$colOperario]) ? trim($row[$colOperario]) : null;
            $op = $colOp !== null && isset($row[$colOp]) ? trim($row[$colOp]) : null;
            $seccion = $colSeccion !== null && isset($row[$colSeccion]) ? strtoupper(trim($row[$colSeccion])) : 'OTRO';

            // Limpiar valores N/A
            if ($precedencia === 'N/A' || $precedencia === 'n/a') $precedencia = '';
            if ($maquina === 'N/A' || $maquina === 'n/a') $maquina = '';
            
            // Validar que la sección sea válida (DEL, TRAS, ENS, OTRO)
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
            $this->warn("⚠️  No se encontraron operaciones válidas");
            return;
        }

        $samTotal = array_sum(array_column($operaciones, 'sam'));
        $this->info("✅ Operaciones encontradas: " . count($operaciones));
        $this->info("⏱️  SAM Total: " . round($samTotal, 1));

        // Mostrar primeras 3 operaciones como muestra
        $this->line("\n📝 Muestra de operaciones:");
        foreach (array_slice($operaciones, 0, 3) as $op) {
            $this->line("   {$op['letra']}: {$op['operacion']} - SAM: {$op['sam']}");
        }
        if (count($operaciones) > 3) {
            $this->line("   ... y " . (count($operaciones) - 3) . " más");
        }

        if ($dryRun) {
            $this->warn("\n⚠️  DRY-RUN: No se guardó en la base de datos");
            return;
        }

        // Guardar en base de datos
        DB::beginTransaction();
        try {
            // Verificar si ya existe una prenda con este nombre
            $nombreFinal = $nombrePrenda;
            $contador = 1;
            
            while (Prenda::where('nombre', $nombreFinal)->exists()) {
                $contador++;
                $nombreFinal = $nombrePrenda . " (v{$contador})";
            }
            
            if ($contador > 1) {
                $this->warn("\n⚠️  La prenda '{$nombrePrenda}' ya existe");
                $this->info("   Creando como: '{$nombreFinal}'");
            }
            
            // Crear prenda
            $prenda = Prenda::create([
                'nombre' => $nombreFinal,
                'descripcion' => $descripcion,
                'referencia' => $referencia,
                'tipo' => $tipo,
                'activo' => true,
            ]);

            $this->info("\n💾 Prenda creada: ID {$prenda->id}");

            // Crear balanceo
            $balanceo = Balanceo::create([
                'prenda_id' => $prenda->id,
                'version' => '1.0',
                'total_operarios' => $totalOperarios,
                'turnos' => $turnos,
                'horas_por_turno' => $horasPorTurno,
                'activo' => true,
            ]);

            $this->info("💾 Balanceo creado: ID {$balanceo->id}");

            // Crear operaciones
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

            $this->info("💾 Operaciones creadas: " . count($operaciones));

            // Calcular métricas
            $balanceo->calcularMetricas();
            $balanceo->refresh();

            $this->info("\n📊 Métricas calculadas:");
            $this->line("   SAM Total: " . round($balanceo->sam_total, 1));
            $this->line("   Meta Teórica: " . $balanceo->meta_teorica);
            $this->line("   Meta Real (90%): " . round($balanceo->meta_real, 2));
            $this->line("   Meta Sugerida (85%): " . $balanceo->meta_sugerida_85);

            DB::commit();
            $this->info("\n✅ Balanceo importado exitosamente");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Error al guardar: " . $e->getMessage());
            throw $e;
        }
    }
}
