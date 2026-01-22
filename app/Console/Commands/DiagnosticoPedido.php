<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class DiagnosticoPedido extends Command
{
    protected $signature = 'test:diagnostico {numero_pedido=45767}';
    protected $description = 'Diagnóstico detallado de datos en BD para un pedido';

    public function handle()
    {
        $numeroPedido = $this->argument('numero_pedido');
        
        $this->info("=== DIAGNÓSTICO DE PEDIDO ===\n");
        $this->info("Número de Pedido: $numeroPedido\n");

        try {
            // Buscar pedido
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$orden) {
                $this->error(" Pedido no encontrado");
                return;
            }
            
            $this->line(" Pedido encontrado (ID: " . $orden->id . ")\n");
            
            // Revisar prendas
            $this->line("--- PRENDAS ---");
            $prendas = DB::table('prendas_pedido')
                ->where('pedido_produccion_id', $orden->id)
                ->get();
            
            $this->line("Total de prendas: " . $prendas->count());
            
            foreach ($prendas as $prenda) {
                $this->line("\nPrenda ID: " . $prenda->id . " - " . $prenda->nombre_prenda);
                $this->line("  cantidad_talla: " . $prenda->cantidad_talla);
                $this->line("  genero: " . $prenda->genero);
                
                // Revisar fotos de prenda
                $fotos = DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                $this->line("  Fotos de prenda: " . $fotos->count());
                foreach ($fotos as $foto) {
                    $this->line("    - ruta_webp: " . $foto->ruta_webp);
                    $this->line("    - ruta_original: " . $foto->ruta_original);
                }
                
                // Revisar colores y telas
                $coloresTelas = DB::table('prenda_pedido_colores_telas')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                $this->line("  Colores-Telas: " . $coloresTelas->count());
                foreach ($coloresTelas as $ct) {
                    $this->line("    - color_id: " . $ct->color_id . ", tela_id: " . $ct->tela_id);
                    
                    // Obtener nombres con tabla correcta
                    $color = DB::table('colores_prenda')->find($ct->color_id);
                    $tela = DB::table('telas_prenda')->find($ct->tela_id);
                    $this->line("      Color: " . ($color?->nombre ?? 'N/A'));
                    $this->line("      Tela: " . ($tela?->nombre ?? 'N/A'));
                }
                
                // Revisar fotos de tela
                $fotosTela = DB::table('prenda_fotos_tela_pedido')
                    ->whereIn('prenda_pedido_colores_telas_id', 
                        DB::table('prenda_pedido_colores_telas')
                            ->where('prenda_pedido_id', $prenda->id)
                            ->pluck('id'))
                    ->get();
                $this->line("  Fotos de tela: " . $fotosTela->count());
                foreach ($fotosTela as $ft) {
                    $this->line("    - ruta_webp: " . $ft->ruta_webp);
                    $this->line("    - ruta_original: " . $ft->ruta_original);
                }
                
                // Revisar variantes
                $variantes = DB::table('prenda_pedido_variantes')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                $this->line("  Variantes: " . $variantes->count());
                foreach ($variantes as $var) {
                    $this->line("    - ID: " . $var->id);
                    $this->line("      tipo_manga_id: " . $var->tipo_manga_id);
                    $this->line("      tipo_broche_boton_id: " . $var->tipo_broche_boton_id);
                    $this->line("      manga_obs: " . $var->manga_obs);
                    $this->line("      broche_boton_obs: " . $var->broche_boton_obs);
                    $this->line("      tiene_bolsillos: " . $var->tiene_bolsillos);
                    $this->line("      bolsillos_obs: " . $var->bolsillos_obs);
                }
                
                // Revisar procesos
                $procesos = DB::table('pedidos_procesos_prenda_detalles')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                $this->line("  Procesos: " . $procesos->count());
                foreach ($procesos as $proc) {
                    $this->line("    - ID: " . $proc->id);
                    $this->line("      tipo_proceso_id: " . $proc->tipo_proceso_id);
                    
                    // Obtener nombre del tipo de proceso
                    $tipoProceso = DB::table('tipo_procesos')->find($proc->tipo_proceso_id);
                    $this->line("      Tipo: " . ($tipoProceso?->nombre ?? 'N/A'));
                    
                    $this->line("      ubicaciones: " . $proc->ubicaciones);
                    $this->line("      observaciones: " . $proc->observaciones);
                    
                    // Revisar imágenes del proceso
                    $imagenes = DB::table('pedidos_procesos_imagenes')
                        ->where('proceso_prenda_detalle_id', $proc->id)
                        ->get();
                    $this->line("      Imágenes: " . $imagenes->count());
                    foreach ($imagenes as $img) {
                        $this->line("        - ruta_webp: " . $img->ruta_webp);
                        $this->line("        - ruta_original: " . $img->ruta_original);
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error(" ERROR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }

        $this->info("\n=== FIN DE DIAGNÓSTICO ===");
    }
}
