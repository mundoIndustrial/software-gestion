<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\User;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Database\Seeder;

class TestGuardadoDatosSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🧪 TEST: Crear Pedido con Variantes, Colores, Telas e Imágenes\n";
        echo "========================================\n\n";

        try {
            // 1. Crear usuario
            echo "  Creando usuario...\n";
            $asesora = User::factory()->create([
                'name' => 'Asesora Test ' . time(),
                'email' => 'asesora' . time() . '@test.com',
            ]);
            echo "  Usuario creado: {$asesora->name} (ID: {$asesora->id})\n\n";

            // 2. Crear pedido
            echo "  Creando pedido...\n";
            $pedido = PedidoProduccion::create([
                'numero_pedido' => 99999,
                'cliente' => 'Cliente Test',
                'asesor_id' => $asesora->id,
                'forma_de_pago' => 'Contado',
                'estado' => 'pendiente',
            ]);
            echo "  Pedido creado: #{$pedido->numero_pedido} (ID: {$pedido->id})\n\n";

            // 3. Preparar datos
            echo "  Preparando datos de prendas...\n";
            $prendas = [
                [
                    'nombre_producto' => 'CAMISA POLO',
                    'descripcion' => 'Camisa de prueba',
                    'de_bodega' => 1,
                    'origen' => 'bodega',
                    'variaciones' => '{}',
                    'fotos' => [],
                    'logos' => [],
                    'procesos' => [
                        'reflectivo' => [
                            'tipo' => 'reflectivo',
                            'tallas' => [
                                'dama' => ['S' => 10, 'M' => 10, 'L' => 10],
                                'caballero' => ['M' => 5, 'L' => 5, 'XL' => 5]
                            ]
                        ]
                    ],
                    'cantidad_talla' => [
                        'dama' => ['S' => 10, 'M' => 10, 'L' => 10],
                        'caballero' => ['M' => 5, 'L' => 5, 'XL' => 5]
                    ],
                    'telas' => [
                        [
                            'tela' => 'Algodón 100%',
                            'color' => 'Rojo',
                            'referencia' => 'ALG-ROJO-001',
                            'fotos' => [
                                [
                                    'ruta_original' => '/storage/telas/algodon-rojo-1.jpg',
                                    'ruta_webp' => '/storage/telas/algodon-rojo-1.webp',
                                    'orden' => 1
                                ]
                            ]
                        ],
                        [
                            'tela' => 'Poliéster',
                            'color' => 'Azul',
                            'referencia' => 'POL-AZUL-002',
                            'fotos' => [
                                [
                                    'ruta_original' => '/storage/telas/poliester-azul-1.jpg',
                                    'ruta_webp' => '/storage/telas/poliester-azul-1.webp',
                                    'orden' => 1
                                ]
                            ]
                        ]
                    ],
                    'tipo_manga_id' => null,
                    'tipo_broche_boton_id' => null,
                    'color_id' => null,
                    'tela_id' => null,
                    'obs_manga' => '',
                    'obs_broche' => '',
                    'obs_bolsillos' => '',
                    'obs_reflectivo' => '',
                    'tiene_bolsillos' => false,
                    'tiene_reflectivo' => false
                ]
            ];
            echo "  Datos preparados\n\n";

            // 4. Guardar prendas
            echo "4️⃣  Guardando prendas...\n";
            $service = app(PedidoPrendaService::class);
            $service->guardarPrendasEnPedido($pedido, $prendas);
            echo "  Prendas guardadas\n\n";

            // 5. Verificar prenda
            echo "5️⃣  Verificando prenda...\n";
            $prenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)->first();
            if (!$prenda) {
                throw new \Exception('La prenda no se creó');
            }
            echo "  Prenda: ID {$prenda->id}, Nombre: {$prenda->nombre_prenda}\n\n";

            // 6. Verificar variantes
            echo "6️⃣  Verificando variantes...\n";
            $variantes = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->get();
            echo "  Variantes: {$variantes->count()}\n";
            if ($variantes->count() === 0) {
                echo "     ADVERTENCIA: No hay variantes guardadas\n";
            }
            echo "\n";

            // 7. Verificar colores/telas
            echo "7️⃣  Verificando colores/telas...\n";
            $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
            echo "  Colores/Telas: {$coloresTelas->count()}\n";
            if ($coloresTelas->count() === 0) {
                echo "     ADVERTENCIA: No hay colores/telas guardados\n";
            }
            echo "\n";

            // 8. Verificar fotos de telas
            echo "8️⃣  Verificando fotos de telas...\n";
            $fotosTelas = PrendaFotoTelaPedido::whereIn(
                'prenda_pedido_colores_telas_id',
                $coloresTelas->pluck('id')
            )->get();
            echo "  Fotos de Telas: {$fotosTelas->count()}\n";
            if ($fotosTelas->count() === 0) {
                echo "     ADVERTENCIA: No hay fotos de telas guardadas\n";
            }
            echo "\n";

            // Resumen
            echo "========================================\n";
            echo " TEST COMPLETADO\n";
            echo "========================================\n";
            echo "RESUMEN:\n";
            echo "   Pedido: {$pedido->numero_pedido}\n";
            echo "   Prendas: 1\n";
            echo "   Variantes: {$variantes->count()}\n";
            echo "   Colores/Telas: {$coloresTelas->count()}\n";
            echo "   Fotos de Telas: {$fotosTelas->count()}\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
            throw $e;
        }
    }
}
