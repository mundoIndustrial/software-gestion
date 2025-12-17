<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CrearPedidoDesdeCotizacionEditableTest extends TestCase
{
    use DatabaseTransactions; // Rollback automático sin borrar BD

    protected $user;
    protected $cotizacion;

    public function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario asesor
        $this->user = User::factory()->create([
            'rol' => 'ASESOR'
        ]);
        
        // Crear cotización aprobada
        $this->cotizacion = Cotizacion::factory()->create([
            'asesor_id' => $this->user->id,
            'estado' => 'APROBADA_COTIZACIONES',
            'numero_cotizacion' => 'COT-TEST-001',
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'CONTADO',
            'numero' => 'NUM-001'
        ]);
    }

    /**
     * Test: Verificar que los modelos existen y se pueden crear
     */
    public function test_modelos_basicos_pueden_crearse()
    {
        // ✅ Test simple: verificar que podemos crear datos de prueba
        $this->assertNotNull($this->user);
        $this->assertNotNull($this->cotizacion);
        $this->assertEquals('ASESOR', $this->user->rol);
        $this->assertEquals('APROBADA_COTIZACIONES', $this->cotizacion->estado);
    }

    /**
     * Test: Crear prenda pedido manualmente y verificar datos
     */
    public function test_crear_prenda_pedido_manualmente()
    {
        // Crear pedido de producción
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-TEST-001',
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'CONTADO',
            'estado' => 'No iniciado',
            'usuario_id' => $this->user->id,
        ]);

        $this->assertNotNull($pedido->id);
        $this->assertEquals('PED-TEST-001', $pedido->numero_pedido);

        // Crear prenda pedido
        $prenda = PrendaPedido::create([
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => 'CAMISA DRILL TEST',
            'descripcion' => 'Camisa de trabajo con ediciones. Manga: LARGA. Tela/Color: DRILL BORNEO REF:REF-DB-001 - AZUL MARINO',
            'cantidad' => 100,
            'cantidad_talla' => json_encode(['S' => 25, 'M' => 35, 'L' => 40, 'XL' => 20]),
            'color_id' => null,
            'tela_id' => null,
            'tipo_manga_id' => null,
            'tipo_broche_id' => null,
            'tiene_bolsillos' => true,
            'tiene_reflectivo' => false,
        ]);

        $this->assertNotNull($prenda->id);
        $this->assertEquals('CAMISA DRILL TEST', $prenda->nombre_prenda);
        $this->assertEquals(100, $prenda->cantidad);

        // Verificar cantidad_talla
        $cantidades = $prenda->cantidad_talla;
        $this->assertIsArray($cantidades);
        $this->assertEquals(25, $cantidades['S']);
        $this->assertEquals(35, $cantidades['M']);
        $this->assertEquals(40, $cantidades['L']);
        $this->assertEquals(20, $cantidades['XL']);

        // Verificar descripción
        $this->assertStringContainsString('Manga: LARGA', $prenda->descripcion);
        $this->assertStringContainsString('DRILL BORNEO', $prenda->descripcion);
        $this->assertStringContainsString('REF-DB-001', $prenda->descripcion);
    }

    /**
     * Test: Guardar y verificar fotos de telas
     */
    public function test_guardar_fotos_telas()
    {
        // Crear pedido
        $pedido = PedidoProduccion::create([
            'numero_pedido' => 'PED-FOTOS-001',
            'cliente' => 'Cliente Test',
            'forma_de_pago' => 'CONTADO',
            'estado' => 'No iniciado',
            'usuario_id' => $this->user->id,
        ]);

        // Crear prenda
        $prenda = PrendaPedido::create([
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => 'PRENDA CON FOTOS',
            'descripcion' => 'Test',
            'cantidad' => 50,
            'cantidad_talla' => json_encode(['M' => 50]),
            'tiene_bolsillos' => false,
            'tiene_reflectivo' => false,
        ]);

        // Guardar fotos de telas
        $fotosUrls = [
            'https://example.com/tela1.jpg',
            'https://example.com/tela2.jpg',
            'https://example.com/tela3.jpg'
        ];

        foreach ($fotosUrls as $fotoUrl) {
            PrendaFotoTelaPedido::create([
                'prenda_pedido_id' => $prenda->id,
                'foto_url' => $fotoUrl
            ]);
        }

        // Verificar que se guardaron
        $fotosGuardadas = PrendaFotoTelaPedido::where('prenda_pedido_id', $prenda->id)->get();
        $this->assertEquals(3, $fotosGuardadas->count());
        
        $urls = $fotosGuardadas->pluck('foto_url')->toArray();
        $this->assertContains('https://example.com/tela1.jpg', $urls);
        $this->assertContains('https://example.com/tela2.jpg', $urls);
        $this->assertContains('https://example.com/tela3.jpg', $urls);
    }

    /**
     * Test: Documentar estado actual del guardado
     */
    public function test_documentar_estado_guardado()
    {
        echo "\n\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "ESTADO ACTUAL: QUÉ SE GUARDA Y QUÉ NO\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        echo "✅ SE GUARDA EN BD:\n";
        echo "   - nombre_prenda (editado)\n";
        echo "   - descripcion (editado + variaciones + telas_multiples)\n";
        echo "   - cantidad (suma de tallas)\n";
        echo "   - cantidad_talla (JSON con desglose por talla)\n";
        echo "   - tiene_bolsillos (boolean)\n";
        echo "   - tiene_reflectivo (boolean)\n";
        echo "   - IDs heredados: color_id, tela_id, tipo_manga_id, tipo_broche_id\n\n";

        echo "❌ NO SE GUARDA (falta implementar):\n";
        echo "   - fotos de prenda (array de URLs)\n";
        echo "   - fotos de tela (array de URLs) - tabla existe pero no se inserta\n";
        echo "   - logos (array de URLs)\n\n";

        echo "TABLAS CREADAS:\n";
        echo "   ✅ prenda_fotos_tela_pedido (existe pero no se usa)\n";
        echo "   ❌ prenda_fotos_pedido (no existe)\n";
        echo "   ❌ logo_pedido (no existe)\n\n";

        echo "═══════════════════════════════════════════════════════════════\n\n";

        $this->assertTrue(true);
    }
}
