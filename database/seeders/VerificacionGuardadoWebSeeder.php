<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use Illuminate\Database\Seeder;

class VerificacionGuardadoWebSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo " VERIFICACIÓN: Últimos Pedidos Guardados\n";
        echo "========================================\n\n";

        try {
            // 1. Obtener últimos 3 pedidos
            echo "  Últimos 3 pedidos creados:\n";
            $pedidos = PedidoProduccion::latest()->limit(3)->get();
            
            if ($pedidos->isEmpty()) {
                echo "    No hay pedidos en la BD\n\n";
                return;
            }

            foreach ($pedidos as $idx => $pedido) {
                echo "   [{$idx}] Pedido #{$pedido->numero_pedido} (ID: {$pedido->id})\n";
                echo "       - Cliente: {$pedido->cliente}\n";
                echo "       - Estado: {$pedido->estado}\n";
                echo "       - Creado: {$pedido->created_at}\n";
                
                // Verificar prendas
                $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
                echo "       - Prendas: {$prendas->count()}\n";
                
                foreach ($prendas as $prenda) {
                    echo "         • {$prenda->nombre_prenda}\n";
                    
                    // Verificar variantes
                    $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
                    echo "           - Variantes: {$variantes->count()}\n";
                    
                    // Verificar colores/telas
                    $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
                    echo "           - Colores/Telas: {$coloresTelas->count()}\n";
                    
                    foreach ($coloresTelas as $ct) {
                        $color = ColorPrenda::find($ct->color_id);
                        $tela = TelaPrenda::find($ct->tela_id);
                        echo "             ✓ {$color?->nombre} + {$tela?->nombre}\n";
                    }
                    
                    // Verificar fotos de telas
                    $fotosTelas = PrendaFotoTelaPedido::whereIn(
                        'prenda_pedido_colores_telas_id',
                        $coloresTelas->pluck('id')
                    )->get();
                    echo "           - Fotos de Telas: {$fotosTelas->count()}\n";
                }
                
                echo "\n";
            }

            // 2. Estadísticas generales
            echo "  Estadísticas Generales:\n";
            $totalPedidos = PedidoProduccion::count();
            $totalPrendas = PrendaPedido::count();
            $totalVariantes = PrendaVariantePed::count();
            $totalColoresTelas = PrendaPedidoColorTela::count();
            $totalFotosTelas = PrendaFotoTelaPedido::count();
            $totalColores = ColorPrenda::where('activo', true)->count();
            $totalTelas = TelaPrenda::where('activo', true)->count();
            
            echo "   - Pedidos totales: {$totalPedidos}\n";
            echo "   - Prendas totales: {$totalPrendas}\n";
            echo "   - Variantes totales: {$totalVariantes}\n";
            echo "   - Colores/Telas totales: {$totalColoresTelas}\n";
            echo "   - Fotos de Telas totales: {$totalFotosTelas}\n";
            echo "   - Colores activos: {$totalColores}\n";
            echo "   - Telas activas: {$totalTelas}\n\n";

            // 3. Resumen
            echo "========================================\n";
            if ($totalPedidos > 0 && $totalPrendas > 0) {
                echo " DATOS GUARDÁNDOSE CORRECTAMENTE\n";
            } else {
                echo "  VERIFICAR GUARDADO DE DATOS\n";
            }
            echo "========================================\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
        }
    }
}
