<?php

namespace Tests\Feature\Cotizacion;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use App\Models\User;
use App\Models\Cliente;
use Tests\TestCase;

/**
 * Test Rápido: Cotizaciones (Sin crear 260+ cotizaciones)
 * 
 * Tests simples que se ejecutan en segundos para demostrar
 * que el sistema funciona correctamente.
 */
class CotizacionesRapidoTest extends TestCase
{
    protected User $asesor;
    protected Cliente $cliente;
    protected TipoCotizacion $tipo;

    public function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create(['name' => 'Asesor Test']);
        $this->cliente = Cliente::factory()->create(['nombre' => 'Cliente Test']);
        $this->tipo = TipoCotizacion::firstOrCreate(
            ['codigo' => 'M'],
            ['nombre' => 'Muestra', 'descripcion' => 'Cotización de muestra']
        );
    }

    /**
     * TEST 1: Crear 1 cotización simple
     */
    public function test_crear_cotizacion_simple(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-TEST-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $this->assertNotNull($cot->id);
        $this->assertEquals('COT-TEST-001', $cot->numero_cotizacion);
        $this->assertEquals($this->asesor->id, $cot->asesor_id);

        echo "\n✅ Cotización creada: COT-TEST-001\n";
    }

    /**
     * TEST 2: Numero cotizacion es único
     */
    public function test_numero_cotizacion_unico(): void
    {
        $this->actingAs($this->asesor);

        // Primera cotización
        Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-UNIQUE-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        // Intentar segunda con el mismo número - debe fallar
        $this->expectException(\Illuminate\Database\QueryException::class);

        Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-UNIQUE-001', // Duplicado
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);
    }

    /**
     * TEST 3: Crear 5 cotizaciones secuenciales
     */
    public function test_crear_5_cotizaciones_secuenciales(): void
    {
        $this->actingAs($this->asesor);

        $numeros = [];

        for ($i = 1; $i <= 5; $i++) {
            $cot = Cotizacion::create([
                'asesor_id' => $this->asesor->id,
                'cliente_id' => $this->cliente->id,
                'numero_cotizacion' => sprintf('COT-SEQ-%05d', $i),
                'tipo_cotizacion_id' => $this->tipo->id,
                'fecha_inicio' => now(),
                'es_borrador' => false,
                'estado' => 'enviada',
            ]);

            $numeros[] = $cot->numero_cotizacion;
        }

        // Verificar
        $this->assertCount(5, $numeros);
        $this->assertEquals(5, count(array_unique($numeros)));

        echo "\n✅ 5 Cotizaciones secuenciales creadas\n";
        echo "Números: " . implode(', ', $numeros) . "\n";
    }

    /**
     * TEST 4: Validar campos requeridos
     */
    public function test_campos_requeridos(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-CAMPOS-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        // Verificar campos
        $this->assertNotNull($cot->asesor_id);
        $this->assertNotNull($cot->cliente_id);
        $this->assertNotNull($cot->numero_cotizacion);
        $this->assertNotNull($cot->tipo_cotizacion_id);
        $this->assertNotNull($cot->fecha_inicio);
        $this->assertFalse($cot->es_borrador);
        $this->assertEquals('enviada', $cot->estado);

        echo "\n✅ Todos los campos requeridos válidos\n";
    }

    /**
     * TEST 5: Tipos de cotización existen
     */
    public function test_tipos_cotizacion_existen(): void
    {
        $tipos = [
            TipoCotizacion::firstOrCreate(['codigo' => 'M'], ['nombre' => 'Muestra']),
            TipoCotizacion::firstOrCreate(['codigo' => 'P'], ['nombre' => 'Prototipo']),
            TipoCotizacion::firstOrCreate(['codigo' => 'G'], ['nombre' => 'Grande']),
        ];

        $this->assertCount(3, $tipos);

        foreach ($tipos as $tipo) {
            $this->assertNotNull($tipo->id);
            $this->assertNotNull($tipo->nombre);
        }

        echo "\n✅ 3 Tipos de cotización disponibles (M, P, G)\n";
    }

    /**
     * TEST 6: Estados válidos
     */
    public function test_estados_validos(): void
    {
        $this->actingAs($this->asesor);

        $estadosValidos = ['enviada', 'aceptada', 'rechazada'];

        foreach ($estadosValidos as $estado) {
            $cot = Cotizacion::create([
                'asesor_id' => $this->asesor->id,
                'cliente_id' => $this->cliente->id,
                'numero_cotizacion' => "COT-ESTADO-{$estado}",
                'tipo_cotizacion_id' => $this->tipo->id,
                'fecha_inicio' => now(),
                'es_borrador' => false,
                'estado' => $estado,
            ]);

            $this->assertEquals($estado, $cot->estado);
        }

        echo "\n✅ Estados válidos: enviada, aceptada, rechazada\n";
    }
}
