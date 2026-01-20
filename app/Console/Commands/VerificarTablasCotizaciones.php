<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerificarTablasCotizaciones extends Command
{
    protected $signature = 'db:verificar-cotizaciones';
    protected $description = 'Verifica la estructura de tablas de cotizaciones';

    public function handle()
    {
        $this->info('🔍 VERIFICANDO ESTRUCTURA DE TABLAS DE COTIZACIONES');
        $this->newLine();

        // Tablas a verificar
        $tablasEsperadas = [
            'cotizaciones',
            'prendas_cot',
            'prenda_fotos_cot',
            'prenda_telas_cot',
            'prenda_tallas_cot',
            'prenda_variantes_cot',
            'logo_cotizaciones',
        ];

        foreach ($tablasEsperadas as $tabla) {
            $this->verificarTabla($tabla);
        }

        $this->newLine();
        $this->info(' VERIFICACIÓN COMPLETADA');
    }

    private function verificarTabla($nombreTabla)
    {
        $this->line(" Tabla: <fg=cyan>{$nombreTabla}</>");

        if (!Schema::hasTable($nombreTabla)) {
            $this->error("    NO EXISTE");
            $this->newLine();
            return;
        }

        $this->info("    EXISTE");

        // Obtener columnas
        $columnas = Schema::getColumns($nombreTabla);
        $this->line("   📊 Columnas: " . count($columnas));

        foreach ($columnas as $columna) {
            $tipo = $columna['type'];
            $nullable = $columna['nullable'] ? '(nullable)' : '';
            $this->line("      • {$columna['name']}: <fg=yellow>{$tipo}</> {$nullable}");
        }

        // Obtener índices
        $indices = DB::select("SHOW INDEX FROM {$nombreTabla}");
        if (!empty($indices)) {
            $this->line("   🔑 Índices:");
            foreach ($indices as $indice) {
                $this->line("      • {$indice->Key_name} ({$indice->Column_name})");
            }
        }

        // Contar registros
        $cantidad = DB::table($nombreTabla)->count();
        $this->line("   📈 Registros: <fg=green>{$cantidad}</>");

        $this->newLine();
    }
}
