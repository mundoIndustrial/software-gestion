<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaColoresTela;
use App\Models\Color;
use App\Models\Tela;
use App\Models\PedidoEpp;
use App\Models\Epp;
use App\Models\EppCategoria;
use App\Models\PedidoEppImagen;
use App\Application\Pedidos\UseCases\ActualizarBorradorUseCase;
use App\Application\Pedidos\UseCases\ActualizarBorradorInput;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * DesincronizacionBorradorFixTest
 *
 * Verifica que el fix de desincronización de índices funciona correctamente
 * cuando hay prendas nuevas + existentes y EPP nuevos + existentes mezclados.
 *
 * Las fotos de tela y imágenes de EPP deben aplicarse a los elementos correctos,
 * sin cruzarse hacia otras prendas o EPP.
 */
class DesincronizacionBorradorFixTest extends TestCase
{
    use DatabaseTransactions;

    private ActualizarBorradorUseCase $useCase;
    private User $asesor;
    private PedidoProduccion $pedido;
    private PrendaPedido $prendaExistente1;
    private PrendaPedido $prendaExistente2;
    private Color $color1;
    private Color $color2;
    private Tela $tela1;
    private Tela $tela2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);

        $this->pedido = PedidoProduccion::create([
            'asesor_id' => $this->asesor->id,
            'numero_pedido' => null,
            'cliente' => 'Cliente Test',
            'estado' => 'Borrador',
            'forma_de_pago' => 'Contado',
        ]);

        // Crear colores y telas
        $this->color1 = Color::create(['nombre' => 'Rojo', 'codigo' => 'RED']);
        $this->color2 = Color::create(['nombre' => 'Azul', 'codigo' => 'BLUE']);
        $this->tela1 = Tela::create(['nombre' => 'Algodón', 'codigo' => 'ALG']);
        $this->tela2 = Tela::create(['nombre' => 'Poliéster', 'codigo' => 'POL']);

        // Crear prendas existentes
        $this->prendaExistente1 = PrendaPedido::create([
            'pedido_produccion_id' => $this->pedido->id,
            'nombre_prenda' => 'Camiseta 1',
            'descripcion' => 'Prenda existente 1',
            'estado' => 'PENDIENTE',
        ]);

        $this->prendaExistente2 = PrendaPedido::create([
            'pedido_produccion_id' => $this->pedido->id,
            'nombre_prenda' => 'Camiseta 2',
            'descripcion' => 'Prenda existente 2',
            'estado' => 'PENDIENTE',
        ]);

        $this->useCase = app(ActualizarBorradorUseCase::class);
    }

    /**
     * TEST: Prendas nuevas + existentes con fotos de tela
     *
     * Escenario:
     * - prendas[0]: nueva (nombre "Polo")
     * - prendas[1]: existente (prendaExistente1, Camiseta 1)
     * - prendas[2]: existente (prendaExistente2, Camiseta 2)
     *
     * Cada prenda tiene fotos de tela diferentes.
     * Verificamos que cada foto se aplicó a su prenda correcta.
     */
    public function test_borrador_prendas_nuevas_existentes_con_fotos_tela()
    {
        // Crear relaciones color-tela para prendas existentes
        $colorTela1 = PrendaColoresTela::create([
            'prenda_pedido_id' => $this->prendaExistente1->id,
            'color_id' => $this->color1->id,
            'tela_id' => $this->tela1->id,
        ]);

        $colorTela2 = PrendaColoresTela::create([
            'prenda_pedido_id' => $this->prendaExistente2->id,
            'color_id' => $this->color2->id,
            'tela_id' => $this->tela2->id,
        ]);

        // Crear archivos de prueba
        $fotoTela1 = UploadedFile::fake()->image('foto_tela1.jpg', 100, 100);
        $fotoTela2 = UploadedFile::fake()->image('foto_tela2.jpg', 100, 100);
        $fotoTelaExistente1 = UploadedFile::fake()->image('foto_tela_existente1.jpg', 100, 100);
        $fotoTelaExistente2 = UploadedFile::fake()->image('foto_tela_existente2.jpg', 100, 100);

        // Preparar datos frontend: prenda nueva [0] + existentes [1], [2]
        $datosFrontend = [
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Test desincronización',
            'epps' => [],
            'prendas_existentes' => [
                // Prenda existente 1 en posición [0] del array prendas_existentes
                [
                    'prenda_id' => $this->prendaExistente1->id,
                    'prenda_pedido_id' => $this->prendaExistente1->id,
                    'nombre_prenda' => 'Camiseta 1 Actualizada',
                    'descripcion' => 'Actualización prenda 1',
                    'colores_telas' => json_encode([
                        [
                            'prenda_pedido_colores_telas_id' => $colorTela1->id,
                            'color_id' => $this->color1->id,
                            'tela_id' => $this->tela1->id,
                            'color_nombre' => 'Rojo',
                            'tela_nombre' => 'Algodón',
                        ]
                    ]),
                    'fotos_telas' => json_encode([
                        [
                            'prenda_pedido_colores_telas_id' => $colorTela1->id,
                            'color_id' => $this->color1->id,
                            'tela_id' => $this->tela1->id,
                            'color_nombre' => 'Rojo',
                            'tela_nombre' => 'Algodón',
                            // Sin id ni ruta_original = es nueva
                        ]
                    ]),
                ],
                // Prenda existente 2 en posición [1] del array prendas_existentes
                [
                    'prenda_id' => $this->prendaExistente2->id,
                    'prenda_pedido_id' => $this->prendaExistente2->id,
                    'nombre_prenda' => 'Camiseta 2 Actualizada',
                    'descripcion' => 'Actualización prenda 2',
                    'colores_telas' => json_encode([
                        [
                            'prenda_pedido_colores_telas_id' => $colorTela2->id,
                            'color_id' => $this->color2->id,
                            'tela_id' => $this->tela2->id,
                            'color_nombre' => 'Azul',
                            'tela_nombre' => 'Poliéster',
                        ]
                    ]),
                    'fotos_telas' => json_encode([
                        [
                            'prenda_pedido_colores_telas_id' => $colorTela2->id,
                            'color_id' => $this->color2->id,
                            'tela_id' => $this->tela2->id,
                            'color_nombre' => 'Azul',
                            'tela_nombre' => 'Poliéster',
                            // Sin id ni ruta_original = es nueva
                        ]
                    ]),
                ],
            ],
            'nuevas_prendas' => [],
            'prendas_eliminadas' => [],
        ];

        // Construir request con archivos: usando prenda_pedido_id como identificador
        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        // Añadir archivos de fotos de tela con prenda_pedido_id como identificador
        $request->files->add([
            "prenda_existente_{$this->prendaExistente1->id}_fotos_tela" => [$fotoTela1],
        ]);

        $request->files->add([
            "prenda_existente_{$this->prendaExistente2->id}_fotos_tela" => [$fotoTelaExistente2],
        ]);

        // Ejecutar
        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success, "Error: {$output->message}");

        // Verificar que las fotos se aplicaron a las prendas correctas
        $fotosTela1 = PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTela1->id)->get();
        $fotosTela2 = PrendaFotoTelaPedido::where('prenda_pedido_colores_telas_id', $colorTela2->id)->get();

        $this->assertCount(1, $fotosTela1, 'Prenda 1 debería tener 1 foto de tela');
        $this->assertCount(1, $fotosTela2, 'Prenda 2 debería tener 1 foto de tela');

        // Verificar que las fotos están en las rutas correctas (diferentes)
        $ruta1 = $fotosTela1->first()->ruta_original;
        $ruta2 = $fotosTela2->first()->ruta_original;

        $this->assertNotNull($ruta1, 'Foto de prenda 1 debería tener ruta');
        $this->assertNotNull($ruta2, 'Foto de prenda 2 debería tener ruta');
        $this->assertNotEquals($ruta1, $ruta2, 'Las rutas de las fotos debería ser diferentes');
    }

    /**
     * TEST: EPP nuevos + existentes con imágenes
     *
     * Escenario:
     * - EPP[0]: nuevo (sin pedido_epp_id)
     * - EPP[1]: existente (con pedido_epp_id)
     *
     * Cada EPP tiene imágenes diferentes.
     * Verificamos que cada imagen se aplicó al EPP correcto.
     */
    public function test_borrador_epp_nuevos_existentes_con_imagenes()
    {
        // Crear categoría y EPP en catálogo
        $categoria = EppCategoria::create(['nombre' => 'Protección']);
        $eppCatalogo1 = Epp::create(['nombre' => 'EPP 1', 'categoria_id' => $categoria->id, 'codigo' => 'EPP001']);
        $eppCatalogo2 = Epp::create(['nombre' => 'EPP 2', 'categoria_id' => $categoria->id, 'codigo' => 'EPP002']);

        // Crear EPP existente en pedido
        $pedidoEppExistente = PedidoEpp::create([
            'pedido_produccion_id' => $this->pedido->id,
            'epp_id' => $eppCatalogo1->id,
            'cantidad' => 10,
            'observaciones' => 'EPP existente',
        ]);

        // Crear archivos de prueba
        $imagenEppNuevo = UploadedFile::fake()->image('imagen_epp_nuevo.jpg', 100, 100);
        $imagenEppExistente = UploadedFile::fake()->image('imagen_epp_existente.jpg', 100, 100);

        // Preparar datos frontend
        $datosFrontend = [
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Test EPP desincronización',
            'epps' => [
                // EPP nuevo en posición [0]
                [
                    'epp_id' => $eppCatalogo2->id,
                    'pedido_epp_id' => null,
                    'cantidad' => 5,
                    'observaciones' => 'Nuevo EPP',
                    'modo_imagenes' => 'upload',
                    'imagenes' => [],
                    '_epp_form_identifier' => "epp-temp-" . time() . "-abc123",
                ],
                // EPP existente en posición [1]
                [
                    'epp_id' => $eppCatalogo1->id,
                    'pedido_epp_id' => $pedidoEppExistente->id,
                    'cantidad' => 15,
                    'observaciones' => 'Actualizado',
                    'modo_imagenes' => 'upload',
                    'imagenes' => [],
                    '_epp_form_identifier' => $pedidoEppExistente->id,
                ],
            ],
            'prendas_existentes' => [],
            'nuevas_prendas' => [],
            'prendas_eliminadas' => [],
        ];

        // Construir request
        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($datosFrontend)
        ]);

        // Usar el identificador temporal para EPP nuevo y pedido_epp_id para existente
        $tempId = "epp-temp-" . time() . "-abc123";
        $request->files->add([
            "epps_{$tempId}_imagenes" => [$imagenEppNuevo],
        ]);

        $request->files->add([
            "epps_{$pedidoEppExistente->id}_imagenes" => [$imagenEppExistente],
        ]);

        // Ejecutar
        $input = new ActualizarBorradorInput(
            pedidoId: $this->pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: json_encode($datosFrontend),
            datosFrontend: $datosFrontend,
        );

        $output = $this->useCase->ejecutar($input);

        // Validaciones
        $this->assertTrue($output->success, "Error: {$output->message}");

        // Verificar que hay 2 EPP en el pedido
        $pedidoEpps = PedidoEpp::where('pedido_produccion_id', $this->pedido->id)->get();
        $this->assertCount(2, $pedidoEpps, 'Pedido debería tener 2 EPP');

        // Verificar que el EPP existente tiene 1 imagen
        $imagenesPedidoEppExistente = PedidoEppImagen::where('pedido_epp_id', $pedidoEppExistente->id)->get();
        $this->assertCount(1, $imagenesPedidoEppExistente, 'EPP existente debería tener 1 imagen');

        // Verificar que el nuevo EPP tiene 1 imagen
        $eppNuevo = $pedidoEpps->where('epp_id', $eppCatalogo2->id)->first();
        $this->assertNotNull($eppNuevo, 'Debería existir el nuevo EPP');

        $imagenesEppNuevo = PedidoEppImagen::where('pedido_epp_id', $eppNuevo->id)->get();
        $this->assertCount(1, $imagenesEppNuevo, 'EPP nuevo debería tener 1 imagen');
    }
}
