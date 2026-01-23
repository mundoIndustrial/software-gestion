<?php

namespace Tests\Feature\Cotizacion;

use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\PrendaTallaCot;
use App\Models\PrendaTelaCot;
use App\Models\PrendaFotoCot;
use Tests\TestCase;

/**
 * Test Suite: ValidaciÃ³n de Campos e Integridad
 * 
 *  NOTA: No usa RefreshDatabase para preservar datos existentes
 * 
 * Este archivo complementa CotizacionesCompleteTest.php con validaciones
 * especÃ­ficas de campos, constraints y validaciones de negocio.
 */
class CotizacionesIntegrityTest extends TestCase
{

    protected User $asesor;
    protected Cliente $cliente;
    protected TipoCotizacion $tipo;

    public function setUp(): void
    {
        parent::setUp();

        $this->asesor = User::factory()->create();
        $this->cliente = Cliente::factory()->create();
        $this->tipo = TipoCotizacion::firstOrCreate(
            ['codigo' => 'M'],
            ['nombre' => 'Muestra']
        );
    }

    /**
     * TEST 1: Validar que numero_cotizacion es UNIQUE
     * 
     * Crea dos cotizaciones e intenta asignar el mismo nÃºmero.
     * Debe fallar o generar excepciÃ³n.
     */
    public function test_numero_cotizacion_debe_ser_unico(): void
    {
        $this->actingAs($this->asesor);

        // Crear primera cotizaciÃ³n
        $cot1 = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-UNIQUE-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $this->assertNotNull($cot1->id);

        // Intentar crear segunda cotizaciÃ³n con el mismo nÃºmero
        $this->expectException(\Illuminate\Database\QueryException::class);

        $cot2 = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-UNIQUE-001', // Mismo nÃºmero
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);
    }

    /**
     * TEST 2: Validar que tipo_cotizacion_id es FK vÃ¡lida
     */
    public function test_tipo_cotizacion_id_debe_ser_valido(): void
    {
        $this->actingAs($this->asesor);

        // FK invÃ¡lido
        $this->expectException(\Illuminate\Database\QueryException::class);

        Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-TEST-001',
            'tipo_cotizacion_id' => 99999, // No existe
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);
    }

    /**
     * TEST 3: Validar que asesor_id es FK vÃ¡lida
     */
    public function test_asesor_id_debe_ser_valido(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Cotizacion::create([
            'asesor_id' => 99999, // Usuario no existe
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-TEST-002',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);
    }

    /**
     * TEST 4: Validar que prendas se pueden eliminar en cascada
     */
    public function test_eliminar_cotizacion_elimina_prendas_en_cascada(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-CASCADE-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cot->id,
            'nombre_producto' => 'Prenda Test',
            'descripcion' => 'Test',
            'cantidad' => 100,
        ]);

        $prendaId = $prenda->id;

        // Verificar que existe
        $this->assertNotNull(PrendaCot::find($prendaId));

        // Eliminar cotizaciÃ³n
        $cot->delete();

        // Verificar que la prenda se eliminÃ³ (soft delete o cascada)
        $this->assertNull(PrendaCot::withoutTrashed()->find($prendaId));
    }

    /**
     * TEST 5: Validar estructura JSON de campos JSON
     */
    public function test_campos_json_deben_tener_estructura_valida(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-JSON-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
            'especificaciones' => [
                'material' => 'algodÃ³n',
                'calidad' => 'premium',
            ],
            'imagenes' => [
                'storage/img1.jpg',
                'storage/img2.jpg',
            ],
            'ubicaciones' => [
                'pecho',
                'espalda',
            ],
        ]);

        // Verificar que los datos se guardaron correctamente
        $this->assertIsArray($cot->especificaciones);
        $this->assertIsArray($cot->imagenes);
        $this->assertIsArray($cot->ubicaciones);
        $this->assertEquals('algodÃ³n', $cot->especificaciones['material']);
        $this->assertCount(2, $cot->imagenes);
    }

    /**
     * TEST 6: Validar tallas vÃ¡lidas en PrendaTallaCot
     */
    public function test_tallas_validas(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-TALLAS-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $prenda = PrendaCot::create([
            'cotizacion_id' => $cot->id,
            'nombre_producto' => 'Camisa',
            'descripcion' => 'Test',
            'cantidad' => 100,
        ]);

        // Tallas vÃ¡lidas
        $tallasValidas = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];

        foreach ($tallasValidas as $talla) {
            $prendaTalla = PrendaTallaCot::create([
                'prenda_cot_id' => $prenda->id,
                'talla' => $talla,
                'cantidad' => 10,
            ]);

            $this->assertEquals($talla, $prendaTalla->talla);
        }

        $this->assertCount(9, $prenda->tallas);
    }

    /**
     * TEST 7: Validar que fotos de prenda se guardan correctamente
     */
    public function test_fotos_prenda_estructura_completa(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-FOTOS-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $prenda = PrendaCot::create([
            'cotizacion_id' => $cot->id,
            'nombre_producto' => 'Camisa',
            'descripcion' => 'Test',
            'cantidad' => 100,
        ]);

        // Crear 5 fotos
        for ($i = 1; $i <= 5; $i++) {
            $foto = PrendaFotoCot::create([
                'prenda_cot_id' => $prenda->id,
                'ruta_original' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}.jpg",
                'ruta_webp' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}.webp",
                'ruta_miniatura' => "storage/prendas/prenda_{$prenda->id}_foto_{$i}_thumb.jpg",
                'orden' => $i,
                'ancho' => 1920,
                'alto' => 1080,
                'tamaÃ±o' => 524288,
            ]);

            $this->assertNotNull($foto->id);
            $this->assertEquals($i, $foto->orden);
            $this->assertTrue(str_contains($foto->ruta_original, 'storage/prendas'));
        }

        // Verificar que todas las fotos se crearon
        $this->assertCount(5, $prenda->fotos);

        // Verificar ordenamiento
        $fotosOrdenadas = $prenda->fotos()->orderBy('orden')->get();
        for ($i = 0; $i < 5; $i++) {
            $this->assertEquals($i + 1, $fotosOrdenadas[$i]->orden);
        }
    }

    /**
     * TEST 8: Validar telas_multiples JSON en variante
     */
    public function test_telas_multiples_json_structure(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-TELAS-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $prenda = PrendaCot::create([
            'cotizacion_id' => $cot->id,
            'nombre_producto' => 'Camisa',
            'descripcion' => 'Test',
            'cantidad' => 100,
        ]);

        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'camisa',
            'genero_id' => 1,
            'color' => 'Azul',
            'telas_multiples' => [
                [
                    'color' => 'Azul Marino',
                    'nombre_tela' => 'AlgodÃ³n 100%',
                    'referencia' => 'ALG-001',
                    'url_imagen' => 'storage/telas/algodon.jpg',
                ],
                [
                    'color' => 'Blanco',
                    'nombre_tela' => 'PoliÃ©ster',
                    'referencia' => 'POL-002',
                    'url_imagen' => 'storage/telas/polyester.jpg',
                ],
            ],
        ]);

        // Verificar JSON guardado correctamente
        $this->assertIsArray($variante->telas_multiples);
        $this->assertCount(2, $variante->telas_multiples);
        $this->assertEquals('Azul Marino', $variante->telas_multiples[0]['color']);
        $this->assertEquals('PoliÃ©ster', $variante->telas_multiples[1]['nombre_tela']);
    }

    /**
     * TEST 9: Validar estado enum de cotizaciÃ³n
     */
    public function test_estado_cotizacion_valores_validos(): void
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
    }

    /**
     * TEST 10: Validar que es_borrador boolean funciona correctamente
     */
    public function test_es_borrador_boolean_field(): void
    {
        $this->actingAs($this->asesor);

        // Borrador
        $cotBorrador = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => null, // Los borradores no tienen nÃºmero
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'enviada',
        ]);

        $this->assertTrue($cotBorrador->es_borrador);
        $this->assertNull($cotBorrador->numero_cotizacion);

        // Enviada
        $cotEnviada = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-ENVIADA-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'fecha_envio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        $this->assertFalse($cotEnviada->es_borrador);
        $this->assertNotNull($cotEnviada->numero_cotizacion);
        $this->assertNotNull($cotEnviada->fecha_envio);
    }

    /**
     * TEST 11: Validar relaciÃ³n One-to-Many: CotizaciÃ³n -> Prendas
     */
    public function test_relacion_cotizacion_prendas(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => 'COT-RELACION-001',
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => false,
            'estado' => 'enviada',
        ]);

        // Crear 3 prendas
        for ($i = 1; $i <= 3; $i++) {
            PrendaCot::create([
                'cotizacion_id' => $cot->id,
                'nombre_producto' => "Prenda $i",
                'descripcion' => "DescripciÃ³n $i",
                'cantidad' => 100 * $i,
            ]);
        }

        // Verificar relaciÃ³n
        $this->assertCount(3, $cot->prendas);
        $this->assertEquals('Prenda 1', $cot->prendas[0]->nombre_producto);
        $this->assertEquals(100, $cot->prendas[0]->cantidad);
    }

    /**
     * TEST 12: Validar que numero_cotizacion no es requerido para borradores
     */
    public function test_numero_cotizacion_opcional_en_borrador(): void
    {
        $this->actingAs($this->asesor);

        $cot = Cotizacion::create([
            'asesor_id' => $this->asesor->id,
            'cliente_id' => $this->cliente->id,
            'numero_cotizacion' => null, // Permitido en borradores
            'tipo_cotizacion_id' => $this->tipo->id,
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'enviada',
        ]);

        $this->assertNull($cot->numero_cotizacion);
        $this->assertTrue($cot->es_borrador);

        // Actualizar a enviada - debe asignar nÃºmero
        $cot->numero_cotizacion = 'COT-ASIGNADO-001';
        $cot->es_borrador = false;
        $cot->save();

        $this->assertNotNull($cot->numero_cotizacion);
        $this->assertFalse($cot->es_borrador);
    }
}

