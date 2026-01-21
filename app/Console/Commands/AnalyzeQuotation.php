<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeQuotation extends Command
{
    protected $signature = 'analyze:quotation {id}';
    protected $description = 'Analizar una cotización para diagnóstico de problemas';

    public function handle()
    {
        $cotizacionId = $this->argument('id');
        
        $this->info('════════════════════════════════════════════════════════');
        $this->info("ANÁLISIS DE COTIZACIÓN: $cotizacionId");
        $this->info('════════════════════════════════════════════════════════');
        $this->newLine();

        // 1. Cotización básica
        $cot = DB::table('cotizaciones')->find($cotizacionId);
        if (!$cot) {
            $this->error(" Cotización $cotizacionId no encontrada");
            return 1;
        }

        $this->info(' COTIZACIÓN ENCONTRADA:');
        $this->line("   ID: {$cot->id}");
        $this->line("   Cliente ID: {$cot->cliente_id}");
        $this->line("   Tipo: " . ($cot->tipo ?? 'NULL'));
        $this->line("   Tipo Venta: " . ($cot->tipo_venta ?? 'NULL'));
        $this->line("   Fecha: {$cot->fecha_inicio}");
        $this->newLine();

        // 2. Prendas
        $prendas = DB::table('prendas_pedido')
            ->where('cotizacion_id', $cotizacionId)
            ->get();

        $this->info(' PRENDAS GUARDADAS: ' . count($prendas));
        $this->line('────────────────────────────────────────');

        foreach ($prendas as $prenda) {
            $this->line("Prenda ID: {$prenda->id} | {$prenda->nombre_producto}");

            // Telas por prenda
            $telas = DB::table('prenda_telas')
                ->where('prenda_pedido_id', $prenda->id)
                ->get();

            $this->line("    Telas guardadas: " . count($telas));

            if ($telas->isEmpty()) {
                $this->line("       SIN TELAS");
            } else {
                foreach ($telas as $idx => $tela) {
                    $this->line("      Tela " . ($idx + 1) . ": Color={$tela->color_id}, Tela={$tela->tela_id}, Ref={$tela->referencia}");

                    // Fotos de tela (a través de prenda_pedido_colores_telas)
                    $fotos = DB::table('prenda_fotos_tela_pedido')
                        ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
                        ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prenda->id)
                        ->count();

                    $this->line("         📸 Fotos: $fotos");
                }
            }

            // Fotos de prenda
            $fotosPrenda = DB::table('fotos_prenda')
                ->where('prenda_pedido_id', $prenda->id)
                ->count();

            $this->line("   📸 Fotos de prenda: $fotosPrenda");
            $this->newLine();
        }

        // 3. Resumen
        $this->info(' RESUMEN:');
        $this->line('────────────────────────────────────────');

        $totalTelas = DB::table('prenda_telas')
            ->whereIn('prenda_pedido_id', function($q) use ($cotizacionId) {
                $q->select('id')->from('prendas_pedido')->where('cotizacion_id', $cotizacionId);
            })
            ->count();

        $this->line("Total telas guardadas en BD: $totalTelas");

        $totalFotosTelas = DB::table('prenda_fotos_tela_pedido')
            ->join('prenda_pedido_colores_telas', 'prenda_fotos_tela_pedido.prenda_pedido_colores_telas_id', '=', 'prenda_pedido_colores_telas.id')
            ->whereIn('prenda_pedido_colores_telas.prenda_pedido_id', function($q) use ($cotizacionId) {
                $q->select('id')->from('prendas_pedido')->where('cotizacion_id', $cotizacionId);
            })
            ->count();

        $this->line("Total fotos de telas: $totalFotosTelas");

        // 4. Diagnóstico
        $this->newLine();
        $this->info(' DIAGNÓSTICO:');
        $this->line('────────────────────────────────────────');

        if ($totalTelas === 0) {
            $this->error(' CRÍTICO: No hay telas guardadas');
        } else {
            $this->info(" Se guardaron $totalTelas telas");
        }

        if ($totalFotosTelas === 0) {
            $this->warn('  ADVERTENCIA: No hay fotos de telas');
        } else {
            $this->info(" Se guardaron $totalFotosTelas fotos de telas");
        }

        return 0;
    }
}
