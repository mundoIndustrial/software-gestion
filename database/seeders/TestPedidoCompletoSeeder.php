<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPedidoCompletoSeeder extends Seeder
{
    public function run()
    {
        Log::info('=== INICIANDO TEST DE PEDIDO COMPLETO ===');
        
        // Obtener el último pedido creado
        $ultimoPedido = DB::table('pedidos_produccion')
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$ultimoPedido) {
            Log::error('No hay pedidos en la BD');
            return;
        }
        
        $pedidoId = $ultimoPedido->id;
        $numeroPedido = $ultimoPedido->numero_pedido;
        
        Log::info("Analizando pedido #{$numeroPedido} (ID: {$pedidoId})");
        
        // 1. VERIFICAR PRENDAS
        $prendas = DB::table('prendas_pedido')
            ->where('pedido_produccion_id', $pedidoId)
            ->get();
        
        Log::info(" PRENDAS: {$prendas->count()} prendas encontradas");
        
        foreach ($prendas as $prenda) {
            Log::info("  - Prenda #{$prenda->id}: {$prenda->nombre_prenda}");
            
            // 2. VERIFICAR VARIANTES
            $variantes = DB::table('prenda_pedido_variantes')
                ->where('prenda_pedido_id', $prenda->id)
                ->get();
            
            Log::info("     VARIANTES: {$variantes->count()} variantes");
            foreach ($variantes as $var) {
                Log::info("      - Manga ID: {$var->tipo_manga_id}, Broche ID: {$var->tipo_broche_boton_id}");
                Log::info("        Obs Manga: {$var->manga_obs}");
                Log::info("        Obs Broche: {$var->broche_boton_obs}");
                Log::info("        Bolsillos: {$var->tiene_bolsillos}, Obs: {$var->bolsillos_obs}");
            }
            
            // 3. VERIFICAR IMÁGENES DE PRENDA
            $fotosPrend = DB::table('prenda_fotos_pedido')
                ->where('prenda_pedido_id', $prenda->id)
                ->get();
            
            Log::info("     IMÁGENES DE PRENDA: {$fotosPrend->count()} fotos");
            foreach ($fotosPrend as $foto) {
                Log::info("      - Foto #{$foto->id}: {$foto->ruta_original}");
            }
            
            // 4. VERIFICAR TELAS
            $telas = DB::table('prenda_pedido_colores_telas')
                ->where('prenda_pedido_id', $prenda->id)
                ->join('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                ->join('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                ->select('prenda_pedido_colores_telas.id', 'telas_prenda.nombre as tela_nombre', 'colores_prenda.nombre as color_nombre', 'prenda_pedido_colores_telas.referencia')
                ->get();
            
            Log::info("     TELAS: {$telas->count()} telas");
            foreach ($telas as $tela) {
                Log::info("      - Tela #{$tela->id}: {$tela->tela_nombre} ({$tela->color_nombre}) - Ref: {$tela->referencia}");
                
                // 5. VERIFICAR IMÁGENES DE TELAS
                $fotosTelas = DB::table('prenda_fotos_tela_pedido')
                    ->where('prenda_pedido_colores_telas_id', $tela->id)
                    ->get();
                
                Log::info("         IMÁGENES DE TELA: {$fotosTelas->count()} fotos");
                foreach ($fotosTelas as $foto) {
                    Log::info("          - Foto #{$foto->id}: {$foto->ruta_original}");
                }
            }
            
            // 6. VERIFICAR PROCESOS
            $procesos = DB::table('pedidos_procesos_prenda_detalles')
                ->where('prenda_pedido_id', $prenda->id)
                ->join('tipos_procesos', 'pedidos_procesos_prenda_detalles.tipo_proceso_id', '=', 'tipos_procesos.id')
                ->select('pedidos_procesos_prenda_detalles.*', 'tipos_procesos.nombre as tipo_nombre')
                ->get();
            
            Log::info("     PROCESOS: {$procesos->count()} procesos");
            foreach ($procesos as $proc) {
                Log::info("      - Proceso #{$proc->id}: {$proc->tipo_nombre}");
                Log::info("        Ubicaciones: {$proc->ubicaciones}");
                Log::info("        Observaciones: {$proc->observaciones}");
                
                // 7. VERIFICAR IMÁGENES DE PROCESOS
                $fotosProc = DB::table('pedidos_procesos_imagenes')
                    ->where('proceso_prenda_detalle_id', $proc->id)
                    ->get();
                
                Log::info("         IMÁGENES DE PROCESO: {$fotosProc->count()} fotos");
                foreach ($fotosProc as $foto) {
                    Log::info("          - Foto #{$foto->id}: {$foto->ruta_original}");
                }
            }
        }
        
        // 8. VERIFICAR EPPs
        $epps = DB::table('pedido_epp')
            ->where('pedido_produccion_id', $pedidoId)
            ->get();
        
        Log::info(" EPPs: {$epps->count()} EPPs encontrados");
        foreach ($epps as $epp) {
            $nombreEpp = $epp->epp_id ? "EPP #{$epp->epp_id}" : "EPP desconocido";
            Log::info("  - Pedido EPP #{$epp->id}: {$nombreEpp} (Cantidad: {$epp->cantidad})");
            
            // 9. VERIFICAR IMÁGENES DE EPP
            $fotosEpp = DB::table('pedido_epp_imagenes')
                ->where('pedido_epp_id', $epp->id)
                ->get();
            
            Log::info("     IMÁGENES DE EPP: {$fotosEpp->count()} fotos");
            foreach ($fotosEpp as $foto) {
                Log::info("      - Foto #{$foto->id}: {$foto->ruta_original} (Web: {$foto->ruta_web})");
            }
        }
        
        // RESUMEN FINAL
        Log::info('=== RESUMEN FINAL ===');
        Log::info("Pedido #{$numeroPedido}:");
        Log::info("  - Prendas: {$prendas->count()}");
        Log::info("  - Variantes: " . DB::table('prenda_pedido_variantes')->whereIn('prenda_pedido_id', $prendas->pluck('id'))->count());
        Log::info("  - Imágenes de prenda: " . DB::table('prenda_fotos_pedido')->whereIn('prenda_pedido_id', $prendas->pluck('id'))->count());
        Log::info("  - Telas: " . DB::table('prenda_pedido_colores_telas')->where('prenda_pedido_id', $pedidoId)->count());
        Log::info("  - Imágenes de telas: " . DB::table('prenda_fotos_tela_pedido')->whereIn('prenda_pedido_colores_telas_id', DB::table('prenda_pedido_colores_telas')->where('prenda_pedido_id', $pedidoId)->pluck('id'))->count());
        Log::info("  - Procesos: " . DB::table('pedidos_procesos_prenda_detalles')->whereIn('prenda_pedido_id', $prendas->pluck('id'))->count());
        Log::info("  - Imágenes de procesos: " . DB::table('pedidos_procesos_imagenes')->whereIn('proceso_prenda_detalle_id', DB::table('pedidos_procesos_prenda_detalles')->whereIn('prenda_pedido_id', $prendas->pluck('id'))->pluck('id'))->count());
        Log::info("  - EPPs: {$epps->count()}");
        Log::info("  - Imágenes de EPP: " . DB::table('pedido_epp_imagenes')->whereIn('pedido_epp_id', $epps->pluck('id'))->count());
        
        Log::info('=== TEST COMPLETADO ===');
    }
}
