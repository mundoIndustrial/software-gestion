<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\User;
use App\Application\Services\PedidoPrendaService;
use App\Domain\PedidoProduccion\Services\ColorTelaService;
use App\Domain\PedidoProduccion\Services\ImagenMapperService;
use Illuminate\Database\Seeder;

class TestColorTelaSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🧪 TEST: Crear Colores y Telas Automáticamente\n";
        echo "========================================\n\n";

        try {
            // 1. Crear usuario
            echo "  Creando usuario...\n";
            $asesora = User::factory()->create([
                'name' => 'Asesora ColorTela ' . time(),
                'email' => 'asesora' . time() . '@test.com',
            ]);
            echo "  Usuario creado\n\n";

            // 2. Crear pedido
            echo "  Creando pedido...\n";
            $numeroPedido = 70000 + rand(1000, 9999);
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => 'Cliente ColorTela',
                'asesor_id' => $asesora->id,
                'forma_de_pago' => 'Contado',
                'estado' => 'pendiente',
            ]);
            echo "  Pedido creado: #{$pedido->numero_pedido}\n\n";

            // 3. Verificar que no existen los colores y telas
            echo "  Verificando que colores y telas NO existen...\n";
            $colorRojoAntes = ColorPrenda::where('nombre', 'Rojo Brillante')->first();
            $telaAlgodonAntes = TelaPrenda::where('nombre', 'Algodón Premium')->first();
            echo "   - Color 'Rojo Brillante': " . ($colorRojoAntes ? "EXISTE (ID: {$colorRojoAntes->id})" : "NO EXISTE") . "\n";
            echo "   - Tela 'Algodón Premium': " . ($telaAlgodonAntes ? "EXISTE (ID: {$telaAlgodonAntes->id})" : "NO EXISTE") . "\n\n";

            // 4. Preparar datos con nombres de colores y telas
            echo "4️⃣  Preparando datos con nombres de colores y telas...\n";
            $prendas = [
                [
                    'nombre_producto' => 'CAMISA PREMIUM',
                    'descripcion' => 'Camisa con colores y telas personalizadas',
                    'de_bodega' => 1,
                    'origen' => 'bodega',
                    'variaciones' => '{}',
                    'fotos' => [],
                    'logos' => [],
                    'procesos' => [],
                    'cantidad_talla' => [
                        'dama' => ['S' => 15, 'M' => 15],
                        'caballero' => ['M' => 10, 'L' => 10]
                    ],
                    'telas' => [
                        [
                            'tela' => 'Algodón Premium',
                            'color' => 'Rojo Brillante',
                            'referencia' => 'ALG-ROJO-PREM-001',
                            'fotos' => []
                        ],
                        [
                            'tela' => 'Poliéster Elástico',
                            'color' => 'Azul Marino',
                            'referencia' => 'POL-AZUL-ELAST-002',
                            'fotos' => []
                        ]
                    ],
                    'tipo_manga_id' => null,
                    'tipo_broche_boton_id' => null,
                    'color_id' => null,
                    'tela_id' => null,
                    'obs_manga' => '',
                    'obs_broche' => '',
                    'tiene_bolsillos' => false,
                    'tiene_reflectivo' => false
                ]
            ];
            echo "  Datos preparados con 2 telas\n\n";

            // 5. Guardar prendas
            echo "5️⃣  Guardando prendas con servicio...\n";
            $service = app(PedidoPrendaService::class);
            $service->guardarPrendasEnPedido($pedido, $prendas);
            echo "  Prendas guardadas\n\n";

            // 6. Verificar que se crearon los colores
            echo "6️⃣  Verificando que colores se crearon...\n";
            $colorRojoDespues = ColorPrenda::where('nombre', 'Rojo Brillante')->first();
            $colorAzulDespues = ColorPrenda::where('nombre', 'Azul Marino')->first();
            echo "   - Color 'Rojo Brillante': " . ($colorRojoDespues ? " CREADO (ID: {$colorRojoDespues->id})" : " NO CREADO") . "\n";
            echo "   - Color 'Azul Marino': " . ($colorAzulDespues ? " CREADO (ID: {$colorAzulDespues->id})" : " NO CREADO") . "\n\n";

            // 7. Verificar que se crearon las telas
            echo "7️⃣  Verificando que telas se crearon...\n";
            $telaAlgodonDespues = TelaPrenda::where('nombre', 'Algodón Premium')->first();
            $telaPoliesterDespues = TelaPrenda::where('nombre', 'Poliéster Elástico')->first();
            echo "   - Tela 'Algodón Premium': " . ($telaAlgodonDespues ? " CREADA (ID: {$telaAlgodonDespues->id})" : " NO CREADA") . "\n";
            echo "   - Tela 'Poliéster Elástico': " . ($telaPoliesterDespues ? " CREADA (ID: {$telaPoliesterDespues->id})" : " NO CREADA") . "\n\n";

            // 8. Verificar que se guardaron en prenda_pedido_colores_telas
            echo "8️⃣  Verificando prenda_pedido_colores_telas...\n";
            $prenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)->first();
            $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
            echo "   Registros en prenda_pedido_colores_telas: " . $coloresTelas->count() . "\n";
            foreach ($coloresTelas as $ct) {
                $color = ColorPrenda::find($ct->color_id);
                $tela = TelaPrenda::find($ct->tela_id);
                echo "      - ID: {$ct->id}\n";
                echo "        Color: {$color?->nombre} (ID: {$ct->color_id})\n";
                echo "        Tela: {$tela?->nombre} (ID: {$ct->tela_id})\n";
            }
            echo "\n";

            // 9. Resumen
            echo "========================================\n";
            echo " TEST COMPLETADO\n";
            echo "========================================\n";
            echo "RESUMEN:\n";
            echo "   Pedido: {$pedido->numero_pedido}\n";
            echo "   Prendas: 1\n";
            echo "   Colores creados: " . ($colorRojoDespues && $colorAzulDespues ? 2 : 0) . "\n";
            echo "   Telas creadas: " . ($telaAlgodonDespues && $telaPoliesterDespues ? 2 : 0) . "\n";
            echo "   Registros en prenda_pedido_colores_telas: " . $coloresTelas->count() . "\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
            throw $e;
        }
    }
}
