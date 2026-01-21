<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use Illuminate\Database\Seeder;

class AnalizarLogsPedidoSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🔍 ANÁLISIS: Pedido #45725\n";
        echo "========================================\n\n";

        try {
            // 1. Buscar el pedido
            echo "  Buscando pedido #45725...\n";
            $pedido = PedidoProduccion::where('numero_pedido', 45725)->first();
            
            if (!$pedido) {
                echo "    Pedido NO encontrado en BD\n\n";
                return;
            }
            
            echo "    Pedido encontrado (ID: {$pedido->id})\n";
            echo "      - Cliente: {$pedido->cliente}\n";
            echo "      - Estado: {$pedido->estado}\n";
            echo "      - Creado: {$pedido->created_at}\n\n";

            // 2. Verificar prendas
            echo "  Verificando prendas del pedido...\n";
            $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
            echo "      Prendas encontradas: {$prendas->count()}\n";
            
            if ($prendas->isEmpty()) {
                echo "    NO HAY PRENDAS GUARDADAS\n\n";
                return;
            }

            foreach ($prendas as $idx => $prenda) {
                $numPrenda = $idx + 1;
                echo "\n    Prenda #{$numPrenda} (ID: {$prenda->id})\n";
                echo "      - Nombre: {$prenda->nombre_prenda}\n";
                echo "      - Descripción: {$prenda->descripcion}\n";
                echo "      - Cantidad Talla: {$prenda->cantidad_talla}\n";
                
                // 3. Verificar variantes
                echo "\n        Variantes:\n";
                $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
                echo "          Encontradas: {$variantes->count()}\n";
                
                if ($variantes->isEmpty()) {
                    echo "           NO HAY VARIANTES\n";
                } else {
                    foreach ($variantes as $var) {
                        echo "          - ID: {$var->id}\n";
                        echo "            tipo_manga_id: " . ($var->tipo_manga_id ?: "NULL") . "\n";
                        echo "            manga_obs: " . ($var->manga_obs ?: "VACÍO") . "\n";
                        echo "            tipo_broche_boton_id: " . ($var->tipo_broche_boton_id ?: "NULL") . "\n";
                        echo "            broche_boton_obs: " . ($var->broche_boton_obs ?: "VACÍO") . "\n";
                        echo "            tiene_bolsillos: " . ($var->tiene_bolsillos ? "SÍ" : "NO") . "\n";
                        echo "            bolsillos_obs: " . ($var->bolsillos_obs ?: "VACÍO") . "\n";
                    }
                }
                
                // 4. Verificar colores/telas
                echo "\n      4️⃣  Colores/Telas:\n";
                $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
                echo "          Encontradas: {$coloresTelas->count()}\n";
                
                if ($coloresTelas->isEmpty()) {
                    echo "           NO HAY COLORES/TELAS\n";
                } else {
                    foreach ($coloresTelas as $ct) {
                        echo "          - ID: {$ct->id}\n";
                        echo "            color_id: {$ct->color_id}\n";
                        echo "            tela_id: {$ct->tela_id}\n";
                    }
                }
            }

            // 5. Resumen
            echo "\n========================================\n";
            echo "📊 RESUMEN DEL PEDIDO #45725\n";
            echo "========================================\n";
            echo "Prendas: {$prendas->count()}\n";
            
            $totalVariantes = PrendaVariantePed::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count();
            $totalColoresTelas = PrendaPedidoColorTela::whereIn('prenda_pedido_id', $prendas->pluck('id'))->count();
            
            echo "Variantes: {$totalVariantes}\n";
            echo "Colores/Telas: {$totalColoresTelas}\n";
            
            if ($totalVariantes === 0) {
                echo "\n PROBLEMA: No hay variantes guardadas\n";
                echo "   Esto significa que los datos de manga, broche y bolsillos NO se guardaron\n";
            }
            
            if ($totalColoresTelas === 0) {
                echo "\n PROBLEMA: No hay colores/telas guardados\n";
                echo "   Esto significa que los datos de telas NO se guardaron\n";
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
