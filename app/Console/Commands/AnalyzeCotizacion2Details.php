<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeCotizacion2Details extends Command
{
    protected $signature = 'analyze:cot2-details';
    protected $description = 'Análisis detallado de qué se guardó en cotización 2';

    public function handle()
    {
        $this->info('════════════════════════════════════════════════════════');
        $this->info('🔍 ANÁLISIS DETALLADO: COTIZACIÓN 2');
        $this->info('════════════════════════════════════════════════════════');
        $this->newLine();

        // Obtener prendas de cotización 2
        $prendas = DB::table('prendas_cot')
            ->where('cotizacion_id', 2)
            ->get();

        $this->info('📦 PRENDAS EN COTIZACIÓN 2: ' . count($prendas));
        $this->line('────────────────────────────────────────────────────────');
        $this->newLine();

        foreach ($prendas as $prenda) {
            $this->line("🧵 PRENDA ID: {$prenda->id} | Nombre: {$prenda->nombre_producto}");
            $this->newLine();

            // 1. Fotos de la prenda
            $fotos = DB::table('prenda_fotos_cot')
                ->where('prenda_cot_id', $prenda->id)
                ->get();

            $this->line("   📸 Fotos de prenda: " . count($fotos));
            foreach ($fotos as $f) {
                $this->line("      • {$f->ruta_original}");
            }
            $this->newLine();

            // 2. Telas de la prenda
            $telas = DB::table('prenda_telas_cot')
                ->where('prenda_cot_id', $prenda->id)
                ->get();

            $this->line("   🧵 Telas: " . count($telas));
            foreach ($telas as $t) {
                $this->line("      ID {$t->id}: color_id={$t->color_id}, tela_id={$t->tela_id}");
            }
            $this->newLine();

            // 3. Tallas de la prenda
            $tallas = DB::table('prenda_tallas_cot')
                ->where('prenda_cot_id', $prenda->id)
                ->get();

            $this->line("   📏 Tallas: " . count($tallas));
            if (count($tallas) > 0) {
                $this->line("      • Tallas: " . implode(', ', $tallas->pluck('talla')->toArray()));
            }
            $this->newLine();

            // 4. Variantes
            $variantes = DB::table('prenda_variantes_cot')
                ->where('prenda_cot_id', $prenda->id)
                ->first();

            if ($variantes) {
                $this->line("   🎨 Variantes:");
                $this->line("      • Género ID: {$variantes->genero_id}");
                $this->line("      • Tipo Manga: {$variantes->tipo_manga}");
                $this->line("      • Tipo Broche ID: {$variantes->tipo_broche_id}");
                $this->line("      • Bolsillos: " . ($variantes->tiene_bolsillos ? 'SÍ' : 'NO'));
                $this->line("      • Reflectivo: " . ($variantes->tiene_reflectivo ? 'SÍ' : 'NO'));
                $this->line("      • Telas Múltiples: " . ($variantes->telas_multiples ? 'SÍ' : 'NO'));
            }
            $this->newLine();
            $this->line('────────────────────────────────────────────────────────');
            $this->newLine();
        }

        // RESUMEN
        $this->line('═════════════════════════════════════════════════════════');
        $this->info('📊 RESUMEN');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        $totalPrendas = count($prendas);
        $totalFotos = DB::table('prenda_fotos_cot')
            ->whereIn('prenda_cot_id', $prendas->pluck('id'))
            ->count();

        $totalTelas = DB::table('prenda_telas_cot')
            ->whereIn('prenda_cot_id', $prendas->pluck('id'))
            ->count();

        $totalTallas = DB::table('prenda_tallas_cot')
            ->whereIn('prenda_cot_id', $prendas->pluck('id'))
            ->count();

        $this->line("Prendas: $totalPrendas");
        $this->line("Fotos: $totalFotos");
        $this->line("Telas: $totalTelas");
        $this->line("Tallas: $totalTallas");
        $this->newLine();

        if ($totalTelas < 4) {
            $this->warn('⚠️  PROBLEMA: Se esperaban 4 telas (3 para camisa + 1 para pantalón)');
            $this->error("   Pero solo se guardaron: $totalTelas");
            $this->newLine();
            $this->line('Posibles causas:');
            $this->line('1. Las telas no llegaron al servidor en el FormData');
            $this->line('2. El servidor rechazó las telas adicionales');
            $this->line('3. Solo se guardó la primera tela');
        } else {
            $this->info(' Se guardaron todas las telas correctamente');
        }

        $this->newLine();
    }
}
