<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPedido;
use Illuminate\Database\Seeder;

class AnalizarImagenesPrendaSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "  ANÁLISIS: Imágenes de Prenda #45725\n";
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
                    echo "           PROBLEMA: Las imágenes de prenda NO se guardaron\n";
                } else {
                    foreach ($fotosPrenda as $foto) {
                        echo "           ID: {$foto->id}\n";
                        echo "             ruta_original: {$foto->ruta_original}\n";
                        echo "             ruta_webp: {$foto->ruta_webp}\n";
                    }
                }
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
