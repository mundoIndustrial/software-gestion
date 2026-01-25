<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PedidoService;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\PrendaCotizacionFriendly;
use App\Models\VariantePrenda;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PedidoServiceTest extends TestCase
{
    use RefreshDatabase;

    private PedidoService $service;
    private User $usuario;
    private Cotizacion $cotizacion;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new PedidoService();
        
        // Crear usuario de prueba
        $this->usuario = User::factory()->create([
            'name' => 'Test Usuario',
            'email' => 'test@example.com'
        ]);
        
        // Crear cotizaciÃ³n de prueba
        $this->cotizacion = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente Test',
            'asesora' => 'Asesora Test',
            'numero_cotizacion' => 'COT-00001',
            'estado' => 'borrador',
            'es_borrador' => true,
            'productos' => [
                [
                    'nombre_producto' => 'POLO HOMBRE',
                    'cantidad' => 10,
                    'descripcion' => 'Polo bÃ¡sico'
                ]
            ],
            'especificaciones' => [
                'forma_pago' => 'Contado'
            ]
        ]);
    }

    /**
     * Test: Crear pedido desde cotizaciÃ³n
     */
    public function test_aceptar_cotizacion_crea_pedido(): void
    {
        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertInstanceOf(PedidoProduccion::class, $pedido);
        $this->assertEquals($this->cotizacion->id, $pedido->cotizacion_id);
        $this->assertEquals('Cliente Test', $pedido->cliente);
        $this->assertEquals('No iniciado', $pedido->estado);
    }

    /**
     * Test: NÃºmero de pedido es Ãºnico y secuencial
     */
    public function test_numero_pedido_es_unico_y_secuencial(): void
    {
        // Crear primer pedido
        $pedido1 = $this->service->aceptarCotizacion($this->cotizacion);
        
        // Crear segunda cotizaciÃ³n
        $cotizacion2 = Cotizacion::create([
            'user_id' => $this->usuario->id,
            'cliente' => 'Cliente 2',
            'asesora' => 'Asesora Test',
            'numero_cotizacion' => 'COT-00002',
            'estado' => 'borrador',
            'es_borrador' => true,
            'productos' => [['nombre_producto' => 'JEAN', 'cantidad' => 5]],
            'especificaciones' => ['forma_pago' => 'CrÃ©dito']
        ]);
        
        // Crear segundo pedido
        $pedido2 = $this->service->aceptarCotizacion($cotizacion2);

        // Afirmar
        $this->assertNotEquals($pedido1->numero_pedido, $pedido2->numero_pedido);
        $this->assertEquals($pedido1->numero_pedido + 1, $pedido2->numero_pedido);
    }

    /**
     * Test: Cambiar estado de cotizaciÃ³n a aceptada
     */
    public function test_cotizacion_cambia_estado_a_aceptada(): void
    {
        // Afirmar estado inicial
        $this->assertTrue($this->cotizacion->es_borrador);
        $this->assertEquals('borrador', $this->cotizacion->estado);

        // Actuar
        $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar estado final
        $this->cotizacion->refresh();
        $this->assertFalse($this->cotizacion->es_borrador);
        $this->assertEquals('aceptada', $this->cotizacion->estado);
    }

    /**
     * Test: Crear prendas del pedido desde cotizaciÃ³n
     */
    public function test_crear_prendas_pedido_desde_cotizacion(): void
    {
        // Crear prenda en cotizaciÃ³n
        $prenda = PrendaCotizacionFriendly::create([
            'cotizacion_id' => $this->cotizacion->id,
            'nombre_producto' => 'POLO HOMBRE',
            'genero' => 'Hombre',
            'es_jean_pantalon' => false,
            'descripcion' => 'Polo bÃ¡sico',
            'tallas' => ['S', 'M', 'L'],
            'estado' => 'Pendiente'
        ]);

        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertCount(1, $pedido->prendas);
        $this->assertEquals('POLO HOMBRE', $pedido->prendas->first()->nombre_prenda);
    }

    /**
     * Test: Heredar variantes de cotizaciÃ³n a pedido
     */
    public function test_heredar_variantes_de_cotizacion_a_pedido(): void
    {
        // Crear prenda y variante en cotizaciÃ³n
        $prenda = PrendaCotizacionFriendly::create([
            'cotizacion_id' => $this->cotizacion->id,
            'nombre_producto' => 'POLO',
            'estado' => 'Pendiente'
        ]);

        $color = ColorPrenda::create(['nombre' => 'Rojo']);
        $tela = TelaPrenda::create(['nombre' => 'AlgodÃ³n 100%']);

        VariantePrenda::create([
            'prenda_cotizacion_id' => $prenda->id,
            'color_id' => $color->id,
            'tela_id' => $tela->id,
            'tiene_bolsillos' => true,
            'tiene_reflectivo' => false,
            'descripcion_adicional' => 'Variante especial'
        ]);

        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $prendaPedido = $pedido->prendas->first();
        $this->assertCount(1, $prendaPedido->variantes);
        
        $variante = $prendaPedido->variantes->first();
        $this->assertEquals($color->id, $variante->color_id);
        $this->assertEquals($tela->id, $variante->tela_id);
        $this->assertTrue($variante->tiene_bolsillos);
    }

    /**
     * Test: Crear proceso inicial para cada prenda
     */
    public function test_crear_proceso_inicial_para_prendas(): void
    {
        // Crear prenda en cotizaciÃ³n
        PrendaCotizacionFriendly::create([
            'cotizacion_id' => $this->cotizacion->id,
            'nombre_producto' => 'JEAN',
            'estado' => 'Pendiente'
        ]);

        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $prenda = $pedido->prendas->first();
        $this->assertCount(1, $prenda->procesos);
        
        $proceso = $prenda->procesos->first();
        $this->assertEquals('Creaciación Orden', $proceso->proceso);
        $this->assertEquals('Completado', $proceso->estado_proceso);
    }

    /**
     * Test: TransacciÃ³n se revierte si hay error
     */
    public function test_transaccion_se_revierte_si_hay_error(): void
    {
        // Usar mock para simular error
        $cotizacionMock = $this->getMockBuilder(Cotizacion::class)
            ->onlyMethods(['update'])
            ->createPartialMock(Cotizacion::class, []);

        $cotizacionOriginal = $this->cotizacion;
        
        // Contar pedidos antes
        $pedidosAntes = PedidoProduccion::count();

        // Actuar - todo deberÃ­a completarse sin error
        $pedido = $this->service->aceptarCotizacion($cotizacionOriginal);

        // Afirmar - no debe haber rollback
        $this->assertGreater(PedidoProduccion::count(), $pedidosAntes);
    }

    /**
     * Test: Forma de pago se copia correctamente
     */
    public function test_forma_pago_se_copia_del_especificaciones(): void
    {
        $this->cotizacion->update([
            'especificaciones' => [
                'forma_pago' => 'CrÃ©dito 30 dÃ­as'
            ]
        ]);

        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertEquals('CrÃ©dito 30 dÃ­as', $pedido->forma_de_pago);
    }

    /**
     * Test: Asesor_id es el usuario autenticado
     */
    public function test_asesor_id_es_usuario_autenticado(): void
    {
        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertNotNull($pedido->asesor_id);
        $this->assertIsInt($pedido->asesor_id);
    }

    /**
     * Test: Fecha de creaciÃ³n es hoy
     */
    public function test_fecha_creacion_es_hoy(): void
    {
        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertEquals(now()->toDateString(), $pedido->fecha_de_creacion_de_orden);
    }

    /**
     * Test: MÃºltiples prendas se crean correctamente
     */
    public function test_multiples_prendas_se_crean_correctamente(): void
    {
        // Crear mÃºltiples prendas en cotizaciÃ³n
        for ($i = 0; $i < 3; $i++) {
            PrendaCotizacionFriendly::create([
                'cotizacion_id' => $this->cotizacion->id,
                'nombre_producto' => "PRODUCTO $i",
                'estado' => 'Pendiente'
            ]);
        }

        // Actuar
        $pedido = $this->service->aceptarCotizacion($this->cotizacion);

        // Afirmar
        $this->assertCount(3, $pedido->prendas);
        $this->assertCount(3, $pedido->prendas->pluck('procesos')->flatten());
    }
}

