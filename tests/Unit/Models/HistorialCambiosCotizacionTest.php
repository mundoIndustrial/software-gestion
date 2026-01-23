<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Cotizacion;
use App\Models\HistorialCambiosCotizacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HistorialCambiosCotizacionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear historial de cambios
     */
    public function test_crear_historial_cambios()
    {
        $cotizacion = Cotizacion::factory()->create();
        $usuario = User::factory()->create();

        $historial = HistorialCambiosCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'estado_anterior' => 'BORRADOR',
            'estado_nuevo' => 'ENVIADA_CONTADOR',
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->name,
            'rol_usuario' => 'asesor',
            'razon_cambio' => 'EnvÃ­o a contador',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'datos_adicionales' => ['cliente' => 'XYZ'],
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('historial_cambios_cotizaciones', [
            'cotizacion_id' => $cotizacion->id,
            'estado_nuevo' => 'ENVIADA_CONTADOR',
        ]);
    }

    /**
     * Test: RelaciÃ³n con cotizaciÃ³n
     */
    public function test_relacion_con_cotizacion()
    {
        $cotizacion = Cotizacion::factory()->create();
        $historial = HistorialCambiosCotizacion::factory()->create([
            'cotizacion_id' => $cotizacion->id,
        ]);

        $this->assertEquals($cotizacion->id, $historial->cotizacion->id);
    }

    /**
     * Test: RelaciÃ³n con usuario
     */
    public function test_relacion_con_usuario()
    {
        $usuario = User::factory()->create();
        $historial = HistorialCambiosCotizacion::factory()->create([
            'usuario_id' => $usuario->id,
        ]);

        $this->assertEquals($usuario->id, $historial->usuario->id);
    }

    /**
     * Test: JSON datos_adicionales
     */
    public function test_json_datos_adicionales()
    {
        $datos = ['numero_cotizacion' => 1001, 'cliente' => 'XYZ'];
        
        $historial = HistorialCambiosCotizacion::factory()->create([
            'datos_adicionales' => $datos,
        ]);

        $this->assertEquals($datos, $historial->datos_adicionales);
        $this->assertEquals(1001, $historial->datos_adicionales['numero_cotizacion']);
    }

    /**
     * Test: Timestamp created_at se guarda
     */
    public function test_timestamp_created_at()
    {
        $historial = HistorialCambiosCotizacion::factory()->create();

        $this->assertNotNull($historial->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $historial->created_at);
    }
}

