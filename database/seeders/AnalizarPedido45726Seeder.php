<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Database\Seeder;

class AnalizarPedido45726Seeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo " ANÁLISIS COMPLETO: Pedido #45726\n";
        echo "========================================\n\n";

        try {
            $pedido = PedidoProduccion::where('numero_pedido', 45726)->first();
            
            if (!$pedido) {
                echo " Pedido NO encontrado\n\n";
                return;
            }
            
            echo " Pedido encontrado (ID: {$pedido->id})\n\n";

            // Prendas
            $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
            echo " PRENDAS: {$prendas->count()}\n";
            
            foreach ($prendas as $prenda) {
                echo "\n   Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
                
                // Variantes
                $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
                echo "      Variantes: {$variantes->count()}\n";
                foreach ($variantes as $var) {
                    echo "         - tipo_manga_id: " . ($var->tipo_manga_id ?: "NULL") . "\n";
                    echo "         - manga_obs: " . ($var->manga_obs ?: "VACÍO") . "\n";
                    echo "         - tipo_broche_boton_id: " . ($var->tipo_broche_boton_id ?: "NULL") . "\n";
                    echo "         - broche_boton_obs: " . ($var->broche_boton_obs ?: "VACÍO") . "\n";
                    echo "         - tiene_bolsillos: " . ($var->tiene_bolsillos ? "SÍ" : "NO") . "\n";
                    echo "         - bolsillos_obs: " . ($var->bolsillos_obs ?: "VACÍO") . "\n";
                }
                
                // Colores/Telas
                $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
                echo "      Colores/Telas: {$coloresTelas->count()}\n";
                foreach ($coloresTelas as $ct) {
                    echo "         - color_id: {$ct->color_id}, tela_id: {$ct->tela_id}\n";
                }
                
                // Fotos de Prenda
                $fotosPrenda = PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->get();
                echo "      Fotos de Prenda: {$fotosPrenda->count()}\n";
                
                // Fotos de Telas
                $allColorTelas = PrendaPedidoColorTela::whereIn('prenda_pedido_id', [$prenda->id])->pluck('id');
                $fotosTelas = PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $allColorTelas)->get();
                echo "      Fotos de Telas: {$fotosTelas->count()}\n";
            }
            
            // EPPs
            $epps = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
            echo "\n  EPPs: {$epps->count()}\n";
            foreach ($epps as $epp) {
                echo "   EPP: {$epp->nombre} (ID: {$epp->id})\n";
                $fotosEpp = PedidoEppImagen::where('pedido_epp_id', $epp->id)->get();
                echo "      Fotos: {$fotosEpp->count()}\n";
            }
            
            echo "\n========================================\n";
            echo "RESUMEN\n";
            echo "========================================\n";
            echo "Prendas: {$prendas->count()}\n";
            echo "Variantes: " . PrendaVariantePed::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count() . "\n";
            echo "Colores/Telas: " . PrendaPedidoColorTela::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count() . "\n";
            echo "Fotos Prenda: " . PrendaFotoPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count() . "\n";
            echo "Fotos Telas: " . PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', PrendaPedidoColorTela::whereIn('prenda_pedido_id', $prendas->pluck('id'))->pluck('id'))->count() . "\n";
            echo "EPPs: {$epps->count()}\n";
            echo "Fotos EPP: " . PedidoEppImagen::whereIn('pedido_epp_id', $epps->pluck('id'))->count() . "\n";
            echo "\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            throw $e;
        }
    }
}
