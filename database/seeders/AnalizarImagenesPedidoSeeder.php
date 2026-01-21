<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaFotoPedido;
use Illuminate\Database\Seeder;

class AnalizarImagenesPedidoSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "  ANÁLISIS: Imágenes del Pedido #45725\n";
        echo "========================================\n\n";

        try {
            // 1. Buscar el pedido
            echo "  Buscando pedido #45725...\n";
            $pedido = PedidoProduccion::where('numero_pedido', 45725)->first();
            
            if (!$pedido) {
                echo "    Pedido NO encontrado\n\n";
                return;
            }
            
            echo "    Pedido encontrado (ID: {$pedido->id})\n\n";

            // 2. Obtener prendas
            echo "  Obteniendo prendas...\n";
            $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
            echo "   Prendas: {$prendas->count()}\n\n";

            foreach ($prendas as $prenda) {
                echo "    Prenda: {$prenda->nombre_prenda} (ID: {$prenda->id})\n";
                
                // 3. Verificar fotos de prenda
                echo "\n        Fotos de Prenda:\n";
                $fotosPrenda = PrendaFotoPedido::where('prenda_pedido_id', $prenda->id)->get();
                echo "          Encontradas: {$fotosPrenda->count()}\n";
                
                if ($fotosPrenda->isEmpty()) {
                    echo "           NO HAY FOTOS DE PRENDA\n";
                } else {
                    foreach ($fotosPrenda as $foto) {
                        echo "          - ID: {$foto->id}\n";
                        echo "            ruta_original: {$foto->ruta_original}\n";
                        echo "            ruta_webp: {$foto->ruta_webp}\n";
                    }
                }
                
                // 4. Verificar fotos de telas
                echo "\n      4️⃣  Fotos de Telas:\n";
                
                // Obtener IDs de color-tela para esta prenda
                $colorTelas = \App\Models\PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->pluck('id');
                
                if ($colorTelas->isEmpty()) {
                    echo "           NO HAY COMBINACIONES COLOR-TELA\n";
                } else {
                    $fotosTelas = PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $colorTelas)->get();
                    echo "          Encontradas: {$fotosTelas->count()}\n";
                    
                    if ($fotosTelas->isEmpty()) {
                        echo "           NO HAY FOTOS DE TELAS\n";
                    } else {
                        foreach ($fotosTelas as $foto) {
                            echo "          - ID: {$foto->id}\n";
                            echo "            prenda_pedido_colores_telas_id: {$foto->prenda_pedido_colores_telas_id}\n";
                            echo "            ruta_original: {$foto->ruta_original}\n";
                            echo "            ruta_webp: {$foto->ruta_webp}\n";
                        }
                    }
                }
            }

            // 5. Resumen
            echo "\n========================================\n";
            echo "📊 RESUMEN DE IMÁGENES\n";
            echo "========================================\n";
            
            $totalFotosPrenda = PrendaFotoPedido::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count();
            
            // Obtener todas las combinaciones color-tela de las prendas
            $allColorTelas = \App\Models\PrendaPedidoColorTela::whereIn('prenda_pedido_id', $prendas->pluck('id'))->pluck('id');
            $totalFotosTelas = PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $allColorTelas)->count();
            
            echo "Fotos de Prenda: {$totalFotosPrenda}\n";
            echo "Fotos de Telas: {$totalFotosTelas}\n";
            
            if ($totalFotosPrenda === 0) {
                echo "\n PROBLEMA: No hay fotos de prenda guardadas\n";
            }
            
            if ($totalFotosTelas === 0) {
                echo "\n PROBLEMA: No hay fotos de telas guardadas\n";
                echo "   Esto significa que las imágenes de telas NO se guardaron\n";
            }
            
            echo "\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
            throw $e;
        }
    }
}
