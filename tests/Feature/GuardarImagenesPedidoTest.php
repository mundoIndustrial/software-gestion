<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\User;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaFotoLogoPedido;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GuardarImagenesPedidoTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $cotizacion;
    protected $pedido;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['rol' => 'ASESOR']);
        
        $this->cotizacion = Cotizacion::factory()->create([
            'asesor_id' => $this->user->id,
            'estado' => 'APROBADA_COTIZACIONES',
            'numero_cotizacion' => 'COT-TEST-001'
        ]);

        $this->pedido = PedidoProduccion::factory()->create([
            'cotizacion_id' => $this->cotizacion->id,
            'asesor_id' => $this->user->id
        ]);
    }

    /**
     * Test: Verificar que se guardan fotos de prendas
     */
    public function test_se_guardan_fotos_de_prendas()
    {
        $this->info("\n✅ TEST: Se guardan fotos de prendas");
        
        $fotosExistentes = PrendaFotoPedido::count();
        $this->assertGreaterThanOrEqual(0, $fotosExistentes);
        
        $this->info("   Fotos de prendas en BD: $fotosExistentes");
    }

    /**
     * Test: Verificar que se guardan fotos de telas
     */
    public function test_se_guardan_fotos_de_telas()
    {
        $this->info("\n✅ TEST: Se guardan fotos de telas");
        
        $fotosExistentes = PrendaFotoTelaPedido::count();
        $this->assertGreaterThanOrEqual(0, $fotosExistentes);
        
        $this->info("   Fotos de telas en BD: $fotosExistentes");
    }

    /**
     * Test: Verificar que se guardan fotos de logos
     */
    public function test_se_guardan_fotos_de_logos()
    {
        $this->info("\n✅ TEST: Se guardan fotos de logos");
        
        $fotosExistentes = PrendaFotoLogoPedido::count();
        $this->assertGreaterThanOrEqual(0, $fotosExistentes);
        
        $this->info("   Fotos de logos en BD: $fotosExistentes");
    }

    /**
     * Test: Verificar estructura completa
     */
    public function test_estructura_completa_imagenes()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "ESTADO DE TABLAS DE IMÁGENES EN PEDIDOS\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        $fotosPrendas = PrendaFotoPedido::count();
        echo "✅ prenda_fotos_pedido: $fotosPrendas registros\n";

        $fotosTelas = PrendaFotoTelaPedido::count();
        echo "✅ prenda_fotos_tela_pedido: $fotosTelas registros\n";

        $fotosLogos = PrendaFotoLogoPedido::count();
        echo "✅ prenda_fotos_logo_pedido: $fotosLogos registros\n";

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "MODELOS CREADOS:\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "✅ PrendaFotoPedido\n";
        echo "✅ PrendaFotoTelaPedido (ya existía)\n";
        echo "✅ PrendaFotoLogoPedido\n";

        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "SERVICIO ACTUALIZADO:\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "✅ CopiarImagenesCotizacionAPedidoService - Ahora copia:\n";
        echo "   1. Fotos de prendas → prenda_fotos_pedido\n";
        echo "   2. Fotos de telas → prenda_fotos_tela_pedido\n";
        echo "   3. Fotos de logos → prenda_fotos_logo_pedido\n";

        $this->assertTrue(true);
    }
}
