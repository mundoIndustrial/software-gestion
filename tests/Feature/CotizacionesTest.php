<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\PrendaCotizacion;
use App\Models\TipoCotizacion;
use App\Models\Role;
use Illuminate\Foundation\Testing\WithoutMigrations;
use Tests\TestCase;

class CotizacionesTest extends TestCase
{
    use WithoutMigrations;

    protected User $asesor;
    protected TipoCotizacion $tipoPrendaLogo;
    protected TipoCotizacion $tipoLogo;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol de asesor
        $roleAsesor = Role::create(['name' => 'asesor']);

        // Crear usuario asesor
        $this->asesor = User::create([
            'name' => 'Test Asesor',
            'email' => 'asesor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $roleAsesor->id,
        ]);

        // Crear tipos de cotización
        $this->tipoPrendaLogo = TipoCotizacion::create([
            'nombre' => 'Prenda/Logo',
            'codigo' => 'prenda_logo',
            'descripcion' => 'Cotización con prendas y logo'
        ]);

        $this->tipoLogo = TipoCotizacion::create([
            'nombre' => 'Logo',
            'codigo' => 'logo',
            'descripcion' => 'Cotización solo de logo'
        ]);
    }

    /**
     * Test: Crear cotización de Prenda/Logo
     * Debe guardar en: cotizaciones, prendas_cotizaciones, logo_cotizaciones
     */
    public function test_crear_cotizacion_prenda_logo()
    {
        $this->actingAs($this->asesor);

        $datos = [
            'tipo' => 'borrador',
            'tipo_cotizacion' => 'prenda_logo',
            'cliente' => 'Cliente Test Prenda/Logo',
            'asesora' => 'Test Asesor',
            'productos' => [
                [
                    'nombre_producto' => 'Camiseta',
                    'descripcion' => 'Camiseta blanca',
                    'tallas' => ['S', 'M', 'L'],
                    'fotos' => []
                ]
            ],
            'tecnicas' => ['Bordado', 'Estampado'],
            'imagenes' => [],
            'observaciones_tecnicas' => 'Bordado en pecho',
            'ubicaciones' => [
                [
                    'seccion' => 'Pecho',
                    'ubicaciones_seleccionadas' => ['Centro'],
                    'observaciones' => 'Centrado'
                ]
            ],
            'observaciones_generales' => [
                ['texto' => 'Observación general 1', 'tipo' => 'texto']
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datos);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $cotizacionId = $response->json('cotizacion_id');

        // Verificar que se guardó en cotizaciones
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion);
        $this->assertEquals('Cliente Test Prenda/Logo', $cotizacion->cliente);
        $this->assertEquals($this->tipoPrendaLogo->id, $cotizacion->tipo_cotizacion_id);
        $this->assertEquals('Prenda/Logo', $cotizacion->tipo_cotizacion);

        // Verificar que se guardó en prendas_cotizaciones
        $prendas = PrendaCotizacion::where('cotizacion_id', $cotizacionId)->get();
        $this->assertCount(1, $prendas);
        $this->assertEquals('Camiseta', $prendas->first()->nombre_producto);

        // Verificar que se guardó en logo_cotizaciones
        $logo = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        $this->assertNotNull($logo);
        $this->assertContains('Bordado', $logo->tecnicas);
        $this->assertEquals('Bordado en pecho', $logo->observaciones_tecnicas);

        echo "\n✅ Test Prenda/Logo PASÓ\n";
    }

    /**
     * Test: Crear cotización de Logo
     * Debe guardar en: cotizaciones, logo_cotizaciones (NO en prendas_cotizaciones)
     */
    public function test_crear_cotizacion_logo()
    {
        $this->actingAs($this->asesor);

        $datos = [
            'tipo' => 'borrador',
            'tipo_cotizacion' => 'logo',
            'cliente' => 'Cliente Test Logo',
            'asesora' => 'Test Asesor',
            'productos' => [], // SIN prendas
            'tecnicas' => ['Bordado'],
            'imagenes' => [],
            'observaciones_tecnicas' => 'Logo en pecho',
            'ubicaciones' => [
                [
                    'seccion' => 'Pecho',
                    'ubicaciones_seleccionadas' => ['Centro'],
                    'observaciones' => 'Centrado'
                ]
            ],
            'observaciones_generales' => [
                ['texto' => 'Logo personalizado', 'tipo' => 'texto']
            ]
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datos);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $cotizacionId = $response->json('cotizacion_id');

        // Verificar que se guardó en cotizaciones
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion);
        $this->assertEquals('Cliente Test Logo', $cotizacion->cliente);
        $this->assertEquals($this->tipoLogo->id, $cotizacion->tipo_cotizacion_id);
        $this->assertEquals('Solo Logo', $cotizacion->tipo_cotizacion);

        // Verificar que NO se guardó en prendas_cotizaciones
        $prendas = PrendaCotizacion::where('cotizacion_id', $cotizacionId)->get();
        $this->assertCount(0, $prendas);

        // Verificar que se guardó en logo_cotizaciones
        $logo = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        $this->assertNotNull($logo);
        $this->assertContains('Bordado', $logo->tecnicas);
        $this->assertEquals('Logo en pecho', $logo->observaciones_tecnicas);

        echo "\n✅ Test Logo PASÓ\n";
    }

    /**
     * Test: Crear cotización solo de prendas
     * Debe guardar en: cotizaciones, prendas_cotizaciones (NO en logo_cotizaciones)
     */
    public function test_crear_cotizacion_solo_prendas()
    {
        $this->actingAs($this->asesor);

        $datos = [
            'tipo' => 'borrador',
            'cliente' => 'Cliente Test Prendas',
            'asesora' => 'Test Asesor',
            'productos' => [
                [
                    'nombre_producto' => 'Pantalón',
                    'descripcion' => 'Pantalón negro',
                    'tallas' => ['28', '30', '32'],
                    'fotos' => []
                ]
            ],
            'tecnicas' => [], // SIN técnicas
            'imagenes' => [],
            'observaciones_tecnicas' => null,
            'ubicaciones' => [],
            'observaciones_generales' => []
        ];

        $response = $this->postJson('/asesores/cotizaciones/guardar', $datos);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $cotizacionId = $response->json('cotizacion_id');

        // Verificar que se guardó en cotizaciones
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion);
        $this->assertEquals('Cliente Test Prendas', $cotizacion->cliente);
        $this->assertEquals('General', $cotizacion->tipo_cotizacion);

        // Verificar que se guardó en prendas_cotizaciones
        $prendas = PrendaCotizacion::where('cotizacion_id', $cotizacionId)->get();
        $this->assertCount(1, $prendas);
        $this->assertEquals('Pantalón', $prendas->first()->nombre_producto);

        // Verificar que NO se guardó en logo_cotizaciones
        $logo = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        $this->assertNull($logo);

        echo "\n✅ Test Solo Prendas PASÓ\n";
    }

    /**
     * Test: Crear cotización de Logo desde CotizacionBordadoController
     */
    public function test_crear_cotizacion_logo_desde_bordado_controller()
    {
        $this->actingAs($this->asesor);

        $datos = [
            'cliente' => 'Cliente Bordado Controller',
            'asesora' => 'Test Asesor',
            'tecnicas' => ['Bordado'],
            'imagenes' => [],
            'observaciones_tecnicas' => 'Bordado profesional',
            'ubicaciones' => [
                [
                    'seccion' => 'Espalda',
                    'ubicaciones_seleccionadas' => ['Centro'],
                    'observaciones' => 'Centrado en espalda'
                ]
            ],
            'observaciones_generales' => [
                ['texto' => 'Bordado de alta calidad', 'tipo' => 'texto']
            ]
        ];

        $response = $this->postJson('/cotizaciones-bordado', $datos);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $cotizacionId = $response->json('cotizacion_id');

        // Verificar que se guardó en cotizaciones con tipo Logo
        $cotizacion = Cotizacion::find($cotizacionId);
        $this->assertNotNull($cotizacion);
        $this->assertEquals('Cliente Bordado Controller', $cotizacion->cliente);
        $this->assertEquals($this->tipoLogo->id, $cotizacion->tipo_cotizacion_id);

        // Verificar que se guardó en logo_cotizaciones
        $logo = LogoCotizacion::where('cotizacion_id', $cotizacionId)->first();
        $this->assertNotNull($logo);
        $this->assertContains('Bordado', $logo->tecnicas);

        echo "\n✅ Test Bordado Controller PASÓ\n";
    }
}
