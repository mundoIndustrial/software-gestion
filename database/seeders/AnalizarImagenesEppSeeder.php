<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use Illuminate\Database\Seeder;

class AnalizarImagenesEppSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🖼️  ANÁLISIS: Imágenes de EPP #45725\n";
        echo "========================================\n\n";

        try {
            // 1. Buscar el pedido
            echo "1️⃣  Buscando pedido #45725...\n";
            $pedido = PedidoProduccion::where('numero_pedido', 45725)->first();
            
            if (!$pedido) {
                echo "    Pedido NO encontrado\n\n";
                return;
            }
            
            echo "    Pedido encontrado (ID: {$pedido->id})\n\n";

            // 2. Obtener EPPs
            echo "2️⃣  Obteniendo EPPs del pedido...\n";
            $epps = PedidoEpp::where('pedido_produccion_id', $pedido->id)->get();
            echo "   EPPs: {$epps->count()}\n\n";

            if ($epps->isEmpty()) {
                echo "    NO HAY EPPs EN ESTE PEDIDO\n\n";
                return;
            }

            foreach ($epps as $epp) {
                echo "   🛡️  EPP: {$epp->nombre} (ID: {$epp->id})\n";
                
                // 3. Verificar fotos de EPP
                echo "\n      Fotos de EPP:\n";
                $fotosEpp = PedidoEppImagen::where('pedido_epp_id', $epp->id)->get();
                echo "          Encontradas: {$fotosEpp->count()}\n";
                
                if ($fotosEpp->isEmpty()) {
                    echo "           NO HAY FOTOS DE EPP\n";
                    echo "          ⚠️  PROBLEMA: Las imágenes de EPP NO se guardaron\n";
                } else {
                    foreach ($fotosEpp as $foto) {
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
