<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;

class ImportarSoloPolos extends Command
{
    protected $signature = 'importar:solo-polos 
                            {archivo? : Ruta al archivo Excel de POLOS}
                            {--limpiar : Eliminar registros existentes antes de importar}';
    
    protected $description = 'Importar solo registros de POLOS desde archivo Excel';

    public function handle()
    {
        $archivo = $this->argument('archivo') ?: resource_path('CONTROL DE PISO POLOS (Respuestas).xlsx');
        $limpiar = $this->option('limpiar');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║         IMPORTACIÓN DE REGISTRO PISO POLOS                 ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        if (!file_exists($archivo)) {
            $this->error("❌ El archivo no existe: {$archivo}");
            return 1;
        }

        $this->info("✓ Archivo: " . basename($archivo));
        $this->newLine();

        // Limpiar tabla si se solicitó
        if ($limpiar) {
            $this->warn("⚠️  ADVERTENCIA: Se eliminarán los registros de:");
            $this->line("   • registro_piso_polo");
            $this->newLine();
            
            if (!$this->confirm('¿Estás seguro de continuar?', false)) {
                $this->info('Operación cancelada.');
                return 0;
            }
            
            $this->limpiarTabla();
        }

        try {
            $procesados = $this->importarPolos($archivo);
            
            $this->newLine();
            $this->info("╔════════════════════════════════════════════════════════════╗");
            $this->info("║                    RESUMEN FINAL                           ║");
            $this->info("╚════════════════════════════════════════════════════════════╝");
            $this->newLine();
            $this->info("📊 POLOS:");
            $this->line("   ✅ Registros importados: {$procesados}");
            $this->newLine();
            $this->info("✅ IMPORTACIÓN COMPLETADA EXITOSAMENTE");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function limpiarTabla()
    {
        $this->info("🗑️  Limpiando tabla...");
        
        $count = DB::table('registro_piso_polo')->count();
        DB::table('registro_piso_polo')->truncate();
        $this->line("   ✓ registro_piso_polo ({$count} registros eliminados)");
        
        $this->newLine();
        $this->info("✅ Tabla limpiada exitosamente");
        $this->newLine();
    }

    private function importarPolos($archivo)
    {
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("📊 IMPORTANDO: REGISTRO PISO POLOS");
        $this->info("═══════════════════════════════════════════════════════════");

        $spreadsheet = IOFactory::load($archivo);
        $worksheet = $spreadsheet->getSheetByName('REGISTRO');
        
        if (!$worksheet) {
            $this->warn("⚠️  No se encontró la hoja 'REGISTRO'");
            return 0;
        }

        $datos = $worksheet->toArray();
        
        if (count($datos) < 2) {
            $this->warn("⚠️  No hay datos suficientes");
            return 0;
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
        $descartadas = 0;

        foreach ($filas as $index => $fila) {
            $filaObj = [];

            // Mapear columnas
            foreach ($mapaColumnas as $encabezado => $columnaSQL) {
                $indexCol = array_search($encabezado, $encabezados);
                $filaObj[$columnaSQL] = $indexCol !== false ? $fila[$indexCol] : null;
            }

            // Validar fila vacía
            $filaVacia = empty(array_filter($filaObj, fn($v) => !empty($v)));
            if ($filaVacia) {
                $descartadas++;
                continue;
            }

            // Validar fecha u orden de producción
            if (empty($filaObj['fecha']) && empty($filaObj['orden_produccion'])) {
                $descartadas++;
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

        $this->info("✅ Registros procesados: " . count($registros));
        $this->warn("❌ Filas descartadas: {$descartadas}");

        if (!empty($registros)) {
            // Insertar en lotes de 100 registros
            $lotes = array_chunk($registros, 100);
            $this->info("💾 Guardando en " . count($lotes) . " lotes...");
            
            foreach ($lotes as $index => $lote) {
                try {
                    DB::table('registro_piso_polo')->insert($lote);
                    $this->line("   ✓ Lote " . ($index + 1) . "/" . count($lotes) . " guardado (" . count($lote) . " registros)");
                } catch (\Exception $e) {
                    $this->error("   ❌ Error en lote " . ($index + 1) . ": " . $e->getMessage());
                }
            }
            
            $this->info("✅ Todos los registros guardados en la base de datos");
        }

        return count($registros);
    }

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
        if ($valor === null || $valor === '') return 0;
        
        $limpio = is_numeric($valor) ? $valor : str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $valor));
        $num = (int) $limpio;
        
        return $num;
    }

    private function toDecimal($valor)
    {
        if ($valor === null || $valor === '') return 0.0;
        
        $limpio = is_numeric($valor) ? $valor : str_replace(',', '.', preg_replace('/[^0-9.,\-]/', '', $valor));
        $num = (float) $limpio;
        
        return $num;
    }
}
