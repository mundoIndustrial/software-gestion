<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\TipoManga;
use App\Models\TipoBroche;
use App\Models\User;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Database\Seeder;

class TestFlujoCompletoSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo " TEST: Flujo Completo Frontend → Backend\n";
        echo "========================================\n\n";

        try {
            // 1. Crear usuario
            echo "  Creando usuario...\n";
            $asesora = User::factory()->create([
                'name' => 'Asesora Flujo ' . time(),
                'email' => 'asesora' . time() . '@test.com',
            ]);
            echo "  Usuario creado\n\n";

            // 2. Crear pedido
            echo "  Creando pedido...\n";
            $numeroPedido = 80000 + rand(1000, 9999);
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => 'Cliente Flujo Completo',
                'asesor_id' => $asesora->id,
                'forma_de_pago' => 'Contado',
                'estado' => 'pendiente',
            ]);
            echo "  Pedido creado: #{$pedido->numero_pedido}\n\n";

            // 3. Simular datos del frontend
            echo "  Simulando datos enviados por el frontend...\n";
            $datosDelFrontend = [
                [
                    'nombre_producto' => 'CAMISA EJECUTIVA',
                    'descripcion' => 'Camisa de ejecutivo con detalles premium',
                    'de_bodega' => 1,
                    'origen' => 'bodega',
                    'variaciones' => '{}',
                    'fotos' => [],
                    'procesos' => [
                        'reflectivo' => [
                            'tipo' => 'reflectivo',
                            'tallas' => [
                                'dama' => ['S' => 10, 'M' => 15, 'L' => 10],
                                'caballero' => ['M' => 20, 'L' => 15, 'XL' => 10]
                            ]
                        ]
                    ],
                    'cantidad_talla' => [
                        'dama-S' => 10,
                        'dama-M' => 15,
                        'dama-L' => 10,
                        'caballero-M' => 20,
                        'caballero-L' => 15,
                        'caballero-XL' => 10
                    ],
                    'telas' => [
                        [
                            'tela' => 'Algodón Premium',
                            'color' => 'Azul Marino',
                            'referencia' => 'ALG-AZUL-PREM',
                            'fotos' => []
                        ],
                        [
                            'tela' => 'Poliéster Elástico',
                            'color' => 'Blanco',
                            'referencia' => 'POL-BLANCO-ELAST',
                            'fotos' => []
                        ]
                    ],
                    'tipo_manga_id' => null,
                    'tipo_broche_boton_id' => null,
                    'manga' => 'Larga',  // Nombre, no ID
                    'broche' => 'Botón',  // Nombre, no ID
                    'obs_manga' => 'Manga con puño reforzado',
                    'obs_broche' => 'Botones de nácar',
                    'tiene_bolsillos' => true,
                    'obs_bolsillos' => 'Dos bolsillos frontales con solapa',
                    'color_id' => null,
                    'tela_id' => null,
                ]
            ];
            echo "  Datos del frontend simulados\n";
            echo "      - Prenda: CAMISA EJECUTIVA\n";
            echo "      - Manga: Larga (nombre, no ID)\n";
            echo "      - Broche: Botón (nombre, no ID)\n";
            echo "      - Telas: 2 (con nombres, no IDs)\n";
            echo "      - Cantidad: 90 prendas totales\n\n";

            // 4. Guardar prendas usando el servicio
            echo "4️⃣  Guardando prendas con el servicio PedidoPrendaService...\n";
            $service = app(PedidoPrendaService::class);
            $service->guardarPrendasEnPedido($pedido, $datosDelFrontend);
            echo "  Prendas guardadas\n\n";

            // 5. Verificar que se crearon los tipos de manga y broche
            echo "5️⃣  Verificando que se crearon manga y broche automáticamente...\n";
            $mangaLarga = TipoManga::where('nombre', 'Larga')->first();
            $brocheBoton = TipoBroche::where('nombre', 'Botón')->first();
            echo "   - Manga 'Larga': " . ($mangaLarga ? " CREADA (ID: {$mangaLarga->id})" : " NO CREADA") . "\n";
            echo "   - Broche 'Botón': " . ($brocheBoton ? " CREADO (ID: {$brocheBoton->id})" : " NO CREADO") . "\n\n";

            // 6. Verificar que se crearon colores y telas
            echo "6️⃣  Verificando que se crearon colores y telas automáticamente...\n";
            $colorAzul = ColorPrenda::where('nombre', 'Azul Marino')->first();
            $colorBlanco = ColorPrenda::where('nombre', 'Blanco')->first();
            $telaAlgodon = TelaPrenda::where('nombre', 'Algodón Premium')->first();
            $telaPoliester = TelaPrenda::where('nombre', 'Poliéster Elástico')->first();
            echo "   - Color 'Azul Marino': " . ($colorAzul ? " CREADO (ID: {$colorAzul->id})" : " NO CREADO") . "\n";
            echo "   - Color 'Blanco': " . ($colorBlanco ? " CREADO (ID: {$colorBlanco->id})" : " NO CREADO") . "\n";
            echo "   - Tela 'Algodón Premium': " . ($telaAlgodon ? " CREADA (ID: {$telaAlgodon->id})" : " NO CREADA") . "\n";
            echo "   - Tela 'Poliéster Elástico': " . ($telaPoliester ? " CREADA (ID: {$telaPoliester->id})" : " NO CREADA") . "\n\n";

            // 7. Verificar prenda_pedido_variantes
            echo "7️⃣  Verificando prenda_pedido_variantes...\n";
            $prenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)->first();
            $variante = PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->first();
            
            if ($variante) {
                echo "  Variante encontrada (ID: {$variante->id})\n";
                echo "      - tipo_manga_id: " . ($variante->tipo_manga_id ? $variante->tipo_manga_id : "NULL") . "\n";
                echo "      - manga_obs: " . ($variante->manga_obs ?: "VACÍO") . "\n";
                echo "      - tipo_broche_boton_id: " . ($variante->tipo_broche_boton_id ? $variante->tipo_broche_boton_id : "NULL") . "\n";
                echo "      - broche_boton_obs: " . ($variante->broche_boton_obs ?: "VACÍO") . "\n";
                echo "      - tiene_bolsillos: " . ($variante->tiene_bolsillos ? "SÍ" : "NO") . "\n";
                echo "      - bolsillos_obs: " . ($variante->bolsillos_obs ?: "VACÍO") . "\n";
            } else {
                echo "    Variante NO encontrada\n";
            }
            echo "\n";

            // 8. Verificar prenda_pedido_colores_telas
            echo "8️⃣  Verificando prenda_pedido_colores_telas...\n";
            $coloresTelas = PrendaPedidoColorTela::where('prenda_pedido_id', $prenda->id)->get();
            echo "   Registros: " . $coloresTelas->count() . "\n";
            foreach ($coloresTelas as $ct) {
                $color = ColorPrenda::find($ct->color_id);
                $tela = TelaPrenda::find($ct->tela_id);
                echo "      ✓ {$color?->nombre} + {$tela?->nombre}\n";
            }
            echo "\n";

            // 9. Verificar cantidad_talla
            echo "9️⃣  Verificando cantidad_talla guardada...\n";
            $cantidadTalla = json_decode($prenda->cantidad_talla, true);
            echo "   Estructura: " . json_encode($cantidadTalla) . "\n";
            $totalPrendas = 0;
            
            // Manejar dos formatos: anidado {genero: {talla: cantidad}} o plano {genero-talla: cantidad}
            foreach ($cantidadTalla as $key => $value) {
                if (is_array($value)) {
                    // Formato anidado
                    foreach ($value as $talla => $cantidad) {
                        $totalPrendas += $cantidad;
                    }
                } else {
                    // Formato plano
                    $totalPrendas += $value;
                }
            }
            echo "   Total de prendas: {$totalPrendas}\n\n";

            // 10. Resumen final
            echo "========================================\n";
            echo " TEST COMPLETADO EXITOSAMENTE\n";
            echo "========================================\n";
            echo "RESUMEN:\n";
            echo "   Pedido: #{$pedido->numero_pedido}\n";
            echo "   Prendas: 1\n";
            echo "   Variantes: " . PrendaVariantePed::where('prenda_pedido_id', $prenda->id)->count() . "\n";
            echo "   Colores/Telas: " . $coloresTelas->count() . "\n";
            echo "   Total de prendas (cantidad_talla): {$totalPrendas}\n";
            echo "   Manga: " . ($variante->tipo_manga_id ? " GUARDADA" : " NO GUARDADA") . "\n";
            echo "   Broche: " . ($variante->tipo_broche_boton_id ? " GUARDADO" : " NO GUARDADO") . "\n";
            echo "   Bolsillos: " . ($variante->tiene_bolsillos ? " SÍ" : " NO") . "\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n";
            echo "   Stack: {$e->getTraceAsString()}\n\n";
            throw $e;
        }
    }
}
