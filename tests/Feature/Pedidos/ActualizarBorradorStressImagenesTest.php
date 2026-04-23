<?php

namespace Tests\Feature\Pedidos;

use App\Application\Pedidos\UseCases\ActualizarBorradorInput;
use App\Application\Pedidos\UseCases\ActualizarBorradorUseCase;
use App\Models\PedidoProduccion;
use App\Models\PedidosProcessImagenes;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\TipoProceso;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActualizarBorradorStressImagenesTest extends TestCase
{
    use DatabaseTransactions;

    private ActualizarBorradorUseCase $useCase;
    private User $asesor;
    private TipoProceso $tipoProceso;

    protected function setUp(): void
    {
        parent::setUp();

        if (Config::get('database.default') !== 'mysql') {
            $this->markTestSkipped('Esta suite de estrés requiere MySQL real.');
        }

        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('La base de datos de testing no tiene el esquema migrado (falta tabla users).');
        }

        Storage::fake('public');

        $this->asesor = User::factory()->create();
        $this->tipoProceso = TipoProceso::query()->create([
            'nombre' => 'Bordado Test ' . uniqid(),
            'slug' => 'bordado-test-' . uniqid(),
            'descripcion' => 'Proceso para pruebas',
            'activo' => true,
        ]);

        $this->useCase = app(ActualizarBorradorUseCase::class);
    }

    public function test_estres_100_actualizaciones_preservan_imagenes_prenda_tela_y_proceso(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            [
                'pedido' => $pedido,
                'prenda_id' => $prendaId,
                'imagen_prenda_id' => $imagenPrendaId,
                'imagen_tela_id' => $imagenTelaId,
                'imagen_proceso_id' => $imagenProcesoId,
                'color_tela_id' => $colorTelaId,
                'proceso_id' => $procesoId,
            ] = $this->crearEscenarioBase($i);

            $payload = $this->buildPayloadPreservacion(
                $prendaId,
                $imagenPrendaId,
                $colorTelaId,
                $imagenTelaId,
                $procesoId
            );

            $output = $this->ejecutarActualizacion($pedido, $payload);

            $this->assertTrue($output->success, "Fallo actualizando pedido {$pedido->id}");

            $this->assertNotNull(
                PrendaFotoPedido::query()->whereKey($imagenPrendaId)->whereNull('deleted_at')->first(),
                "Imagen de prenda eliminada por error en pedido {$pedido->id}"
            );
            $this->assertNotNull(
                PrendaFotoTelaPedido::query()->whereKey($imagenTelaId)->whereNull('deleted_at')->first(),
                "Imagen de tela eliminada por error en pedido {$pedido->id}"
            );
            $this->assertNotNull(
                PedidosProcessImagenes::query()->whereKey($imagenProcesoId)->whereNull('deleted_at')->first(),
                "Imagen de proceso eliminada por error en pedido {$pedido->id}"
            );
        }
    }

    public function test_edicion_fuerte_con_altas_y_bajas_controladas_de_imagenes(): void
    {
        [
            'pedido' => $pedido,
            'prenda_id' => $prendaId,
            'imagen_prenda_id' => $imagenPrendaId,
            'imagen_tela_id' => $imagenTelaId,
            'imagen_proceso_id' => $imagenProcesoId,
            'color_tela_id' => $colorTelaId,
            'proceso_id' => $procesoId,
        ] = $this->crearEscenarioBase(999);

        // Edit 1: agregar una imagen nueva de cada tipo.
        $payloadEdit1 = $this->buildPayloadPreservacion(
            $prendaId,
            $imagenPrendaId,
            $colorTelaId,
            $imagenTelaId,
            $procesoId
        );

        $filesEdit1 = [
            'prenda_existente_0_imagenes' => [UploadedFile::fake()->image('prenda-nueva.jpg')],
            'prenda_existente_0_fotos_tela' => [UploadedFile::fake()->image('tela-nueva.jpg')],
            'prenda_existente_0_fotosProcesoNuevo_0' => [UploadedFile::fake()->image('proceso-nuevo.jpg')],
        ];

        $output = $this->ejecutarActualizacion($pedido, $payloadEdit1, $filesEdit1);
        $this->assertTrue($output->success);

        $pedido->refresh();
        $prenda = $pedido->prendas()->findOrFail($prendaId);
        $proceso = $prenda->procesos()->findOrFail($procesoId);

        $this->assertSame(2, $prenda->fotos()->whereNull('deleted_at')->count());
        $this->assertSame(2, $prenda->fotosTelas()->whereNull('prenda_fotos_tela_pedido.deleted_at')->count());
        $this->assertSame(2, $proceso->imagenes()->whereNull('deleted_at')->count());

        // Edit 2: eliminar 1 imagen de prenda y 1 de proceso.
        $imagenPrendaNueva = $prenda->fotos()->where('id', '!=', $imagenPrendaId)->firstOrFail();
        $imagenProcesoNueva = $proceso->imagenes()->where('id', '!=', $imagenProcesoId)->firstOrFail();

        $payloadEdit2 = [
            'cliente' => "Cliente {$pedido->id}",
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Edit 2',
            'epps' => [],
            'prendas_existentes' => [
                [
                    'prenda_id' => $prendaId,
                    'nombre_prenda' => "Prenda {$prendaId}",
                    'descripcion' => 'Edit 2',
                    'imagenes_existentes' => [
                        [
                            'id' => $imagenPrendaNueva->id,
                            'ruta_original' => $imagenPrendaNueva->ruta_original,
                            'ruta_webp' => $imagenPrendaNueva->ruta_webp,
                        ],
                    ],
                    'imagenes_a_eliminar' => [
                        ['id' => $imagenPrendaId],
                        ['id' => $imagenProcesoId],
                    ],
                    'fotos_telas' => [
                        [
                            'id' => $imagenTelaId,
                            'prenda_pedido_colores_telas_id' => $colorTelaId,
                            'ruta_original' => PrendaFotoTelaPedido::query()->findOrFail($imagenTelaId)->ruta_original,
                            'ruta_webp' => PrendaFotoTelaPedido::query()->findOrFail($imagenTelaId)->ruta_webp,
                        ],
                        [
                            'id' => $prenda->fotosTelas()
                                ->where('prenda_fotos_tela_pedido.id', '!=', $imagenTelaId)
                                ->firstOrFail()->id,
                            'prenda_pedido_colores_telas_id' => $colorTelaId,
                            'ruta_original' => $prenda->fotosTelas()
                                ->where('prenda_fotos_tela_pedido.id', '!=', $imagenTelaId)
                                ->firstOrFail()->ruta_original,
                            'ruta_webp' => $prenda->fotosTelas()
                                ->where('prenda_fotos_tela_pedido.id', '!=', $imagenTelaId)
                                ->firstOrFail()->ruta_webp,
                        ],
                    ],
                    'procesos' => [
                        [
                            'id' => $procesoId,
                            'tipo_proceso_id' => $this->tipoProceso->id,
                            'ubicaciones' => ['pecho'],
                            'observaciones' => 'Edit 2',
                            'estado' => 'PENDIENTE',
                        ],
                    ],
                ],
            ],
        ];

        $output = $this->ejecutarActualizacion($pedido, $payloadEdit2);
        $this->assertTrue($output->success);

        $prenda->refresh();
        $proceso->refresh();

        $this->assertSame(1, $prenda->fotos()->whereNull('deleted_at')->count());
        $this->assertSame(2, $prenda->fotosTelas()->whereNull('prenda_fotos_tela_pedido.deleted_at')->count());
        $this->assertSame(1, $proceso->imagenes()->whereNull('deleted_at')->count());
    }

    private function crearEscenarioBase(int $seed): array
    {
        $pedido = PedidoProduccion::factory()->create([
            'asesor_id' => $this->asesor->id,
            'estado' => 'BORRADOR',
            'cliente' => "Cliente {$seed}",
            'forma_de_pago' => 'Contado',
        ]);

        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => "Prenda {$seed}",
            'descripcion' => 'Base',
            'de_bodega' => 0,
        ]);

        $imagenPrenda = $prenda->fotos()->create([
            'ruta_original' => "pedidos/{$pedido->id}/prendas/base-{$seed}.jpg",
            'ruta_webp' => "pedidos/{$pedido->id}/prendas/base-{$seed}.webp",
            'orden' => 1,
        ]);

        $colorTela = $prenda->coloresTelas()->create([
            'color_id' => null,
            'tela_id' => null,
            'referencia' => "ref-{$seed}",
        ]);

        $imagenTela = $colorTela->fotos()->create([
            'ruta_original' => "pedidos/{$pedido->id}/telas/base-{$seed}.jpg",
            'ruta_webp' => "pedidos/{$pedido->id}/telas/base-{$seed}.webp",
            'orden' => 1,
        ]);

        $proceso = $prenda->procesos()->create([
            'tipo_proceso_id' => $this->tipoProceso->id,
            'ubicaciones' => ['pecho'],
            'observaciones' => 'Base',
            'estado' => 'PENDIENTE',
        ]);

        $imagenProceso = $proceso->imagenes()->create([
            'ruta_original' => "pedidos/{$pedido->id}/procesos/base-{$seed}.jpg",
            'ruta_webp' => "pedidos/{$pedido->id}/procesos/base-{$seed}.webp",
            'orden' => 1,
            'es_principal' => 1,
        ]);

        return [
            'pedido' => $pedido,
            'prenda_id' => $prenda->id,
            'imagen_prenda_id' => $imagenPrenda->id,
            'imagen_tela_id' => $imagenTela->id,
            'imagen_proceso_id' => $imagenProceso->id,
            'color_tela_id' => $colorTela->id,
            'proceso_id' => $proceso->id,
        ];
    }

    private function buildPayloadPreservacion(
        int $prendaId,
        int $imagenPrendaId,
        int $colorTelaId,
        int $imagenTelaId,
        int $procesoId
    ): array {
        $fotoPrenda = PrendaFotoPedido::query()->findOrFail($imagenPrendaId);
        $fotoTela = PrendaFotoTelaPedido::query()->findOrFail($imagenTelaId);

        return [
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'Contado',
            'observaciones' => 'Actualizacion de prueba',
            'epps' => [],
            'prendas_existentes' => [
                [
                    'prenda_id' => $prendaId,
                    'nombre_prenda' => "Prenda {$prendaId}",
                    'descripcion' => 'Actualizada',
                    'imagenes_existentes' => [
                        [
                            'id' => $fotoPrenda->id,
                            'ruta_original' => $fotoPrenda->ruta_original,
                            'ruta_webp' => $fotoPrenda->ruta_webp,
                        ],
                    ],
                    'fotos_telas' => [
                        [
                            'id' => $fotoTela->id,
                            'prenda_pedido_colores_telas_id' => $colorTelaId,
                            'ruta_original' => $fotoTela->ruta_original,
                            'ruta_webp' => $fotoTela->ruta_webp,
                        ],
                        [
                            'prenda_pedido_colores_telas_id' => $colorTelaId,
                        ],
                    ],
                    'procesos' => [
                        [
                            'id' => $procesoId,
                            'tipo_proceso_id' => $this->tipoProceso->id,
                            'ubicaciones' => ['pecho'],
                            'observaciones' => 'Actualizado',
                            'estado' => 'PENDIENTE',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function ejecutarActualizacion(PedidoProduccion $pedido, array $payload, array $files = []): mixed
    {
        $request = Request::create('/', 'POST', [
            'pedido' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ], [], $files);

        $input = new ActualizarBorradorInput(
            pedidoId: $pedido->id,
            asesorId: $this->asesor->id,
            request: $request,
            pedidoJSON: (string) $request->input('pedido'),
            datosFrontend: $payload
        );

        return $this->useCase->ejecutar($input);
    }
}
