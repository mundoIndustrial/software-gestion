<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaFotoCot;
use App\Models\PrendaTallaCot;
use App\Models\PrendaVarianteCot;
use App\Models\LogoCotizacion;
use App\Models\LogoFoto;
use Tests\TestCase;

class VerificarGuardadoCompletoTest extends TestCase
{
    public function test_cotizacion_completa_se_guarda_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-COMPLETO-001',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Verificar cotización
        $this->assertDatabaseHas('cotizaciones', [
            'id' => $cotizacion->id,
            'numero_cotizacion' => 'COT-COMPLETO-001',
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'CAMISA DRILL',
            'descripcion' => 'Camisa de drill con bordado',
            'genero' => 'Masculino',
        ]);

        // Verificar prenda
        $this->assertDatabaseHas('prendas_cot', [
            'id' => $prenda->id,
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'CAMISA DRILL',
        ]);

        // Crear fotos de prenda
        $foto1 = PrendaFotoCot::create([
            'prenda_cot_id' => $prenda->id,
            'ruta_original' => 'storage/cotizaciones/1/prendas/foto1.jpg',
            'ruta_webp' => 'storage/cotizaciones/1/prendas/foto1.webp',
        ]);

        $foto2 = PrendaFotoCot::create([
            'prenda_cot_id' => $prenda->id,
            'ruta_original' => 'storage/cotizaciones/1/prendas/foto2.jpg',
            'ruta_webp' => 'storage/cotizaciones/1/prendas/foto2.webp',
        ]);

        // Verificar fotos
        $this->assertDatabaseHas('prenda_fotos_cot', [
            'prenda_cot_id' => $prenda->id,
            'ruta_original' => 'storage/cotizaciones/1/prendas/foto1.jpg',
        ]);

        $this->assertDatabaseHas('prenda_fotos_cot', [
            'prenda_cot_id' => $prenda->id,
            'ruta_original' => 'storage/cotizaciones/1/prendas/foto2.jpg',
        ]);

        // Crear tallas
        PrendaTallaCot::create([
            'prenda_cot_id' => $prenda->id,
            'talla' => 'XS',
            'cantidad' => 10,
        ]);

        PrendaTallaCot::create([
            'prenda_cot_id' => $prenda->id,
            'talla' => 'S',
            'cantidad' => 20,
        ]);

        // Verificar tallas
        $this->assertDatabaseHas('prenda_tallas_cot', [
            'prenda_cot_id' => $prenda->id,
            'talla' => 'XS',
            'cantidad' => 10,
        ]);

        $this->assertDatabaseHas('prenda_tallas_cot', [
            'prenda_cot_id' => $prenda->id,
            'talla' => 'S',
            'cantidad' => 20,
        ]);

        // Crear variante
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camisa Drill',
            'color' => 'Azul',
            'tipo_manga' => 'Manga Corta',
            'obs_manga' => 'Con puño elástico',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos laterales',
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Reflectivo en espalda',
            'aplica_manga' => true,
            'aplica_broche' => false,
        ]);

        // Verificar variante
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camisa Drill',
            'color' => 'Azul',
            'tiene_bolsillos' => true,
            'tiene_reflectivo' => true,
        ]);

        // Crear logo
        $logo = LogoCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'Logo Empresa',
            'tecnicas' => json_encode(['Bordado']),
            'ubicaciones' => json_encode(['Pecho']),
        ]);

        // Verificar logo
        $this->assertDatabaseHas('logo_cotizaciones', [
            'cotizacion_id' => $cotizacion->id,
            'descripcion' => 'Logo Empresa',
        ]);

        // Crear fotos de logo
        LogoFoto::create([
            'logo_cotizacion_id' => $logo->id,
            'cotizacion_id' => $cotizacion->id,
            'ruta_original' => 'storage/cotizaciones/1/logo/logo1.jpg',
            'ruta_webp' => 'storage/cotizaciones/1/logo/logo1.webp',
        ]);

        LogoFoto::create([
            'logo_cotizacion_id' => $logo->id,
            'cotizacion_id' => $cotizacion->id,
            'ruta_original' => 'storage/cotizaciones/1/logo/logo2.jpg',
            'ruta_webp' => 'storage/cotizaciones/1/logo/logo2.webp',
        ]);

        // Verificar fotos de logo
        $this->assertDatabaseHas('logo_fotos_cot', [
            'logo_cotizacion_id' => $logo->id,
            'ruta_original' => 'storage/cotizaciones/1/logo/logo1.jpg',
        ]);

        $this->assertDatabaseHas('logo_fotos_cot', [
            'logo_cotizacion_id' => $logo->id,
            'ruta_original' => 'storage/cotizaciones/1/logo/logo2.jpg',
        ]);

        // Verificar relaciones
        $cotizacionRecuperada = Cotizacion::with([
            'prendas.fotos',
            'prendas.tallas',
            'prendas.variantes',
            'logoCotizacion.fotos'
        ])->find($cotizacion->id);

        // Verificar que se cargan todas las relaciones
        $this->assertNotNull($cotizacionRecuperada);
        $this->assertEquals(1, $cotizacionRecuperada->prendas->count());
        $this->assertEquals(2, $cotizacionRecuperada->prendas[0]->fotos->count());
        $this->assertEquals(2, $cotizacionRecuperada->prendas[0]->tallas->count());
        $this->assertEquals(1, $cotizacionRecuperada->prendas[0]->variantes->count());
        $this->assertNotNull($cotizacionRecuperada->logoCotizacion);
        $this->assertEquals(2, $cotizacionRecuperada->logoCotizacion->fotos->count());

        // Verificar datos específicos
        $this->assertEquals('CAMISA DRILL', $cotizacionRecuperada->prendas[0]->nombre_producto);
        $this->assertEquals('Azul', $cotizacionRecuperada->prendas[0]->variantes[0]->color);
        $this->assertTrue((bool) $cotizacionRecuperada->prendas[0]->variantes[0]->tiene_bolsillos);
        $this->assertEquals('Logo Empresa', $cotizacionRecuperada->logoCotizacion->descripcion);
    }

    public function test_cotizacion_sin_logo_se_guarda_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización sin logo
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-SIN-LOGO-001',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'PANTALON DRILL',
            'descripcion' => 'Pantalón de drill',
            'genero' => 'Masculino',
        ]);

        // Crear talla
        PrendaTallaCot::create([
            'prenda_cot_id' => $prenda->id,
            'talla' => 'M',
            'cantidad' => 50,
        ]);

        // Crear variante
        PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Pantalón',
            'color' => 'Negro',
        ]);

        // Verificar que la cotización se guardó sin logo
        $cotizacionRecuperada = Cotizacion::with([
            'prendas.fotos',
            'prendas.tallas',
            'prendas.variantes',
            'logoCotizacion'
        ])->find($cotizacion->id);

        $this->assertNotNull($cotizacionRecuperada);
        $this->assertEquals(1, $cotizacionRecuperada->prendas->count());
        $this->assertNull($cotizacionRecuperada->logoCotizacion);
        $this->assertEquals('PANTALON DRILL', $cotizacionRecuperada->prendas[0]->nombre_producto);
    }

    public function test_multiples_prendas_se_guardan_correctamente()
    {
        // Crear usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-MULTIPLES-001',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear 3 prendas
        $prendas = [];
        for ($i = 1; $i <= 3; $i++) {
            $prenda = PrendaCot::create([
                'cotizacion_id' => $cotizacion->id,
                'nombre_producto' => "PRENDA $i",
                'descripcion' => "Descripción prenda $i",
                'genero' => 'Unisex',
            ]);

            // Crear 2 fotos por prenda
            for ($j = 1; $j <= 2; $j++) {
                PrendaFotoCot::create([
                    'prenda_cot_id' => $prenda->id,
                    'ruta_original' => "storage/cotizaciones/1/prendas/prenda$i-foto$j.jpg",
                    'ruta_webp' => "storage/cotizaciones/1/prendas/prenda$i-foto$j.webp",
                ]);
            }

            // Crear 3 tallas por prenda
            for ($j = 1; $j <= 3; $j++) {
                $tallas = ['XS', 'S', 'M'];
                PrendaTallaCot::create([
                    'prenda_cot_id' => $prenda->id,
                    'talla' => $tallas[$j - 1],
                    'cantidad' => $j * 10,
                ]);
            }

            $prendas[] = $prenda;
        }

        // Verificar que se guardaron todas las prendas
        $cotizacionRecuperada = Cotizacion::with([
            'prendas.fotos',
            'prendas.tallas'
        ])->find($cotizacion->id);

        $this->assertEquals(3, $cotizacionRecuperada->prendas->count());

        // Verificar cada prenda
        foreach ($cotizacionRecuperada->prendas as $index => $prenda) {
            $this->assertEquals("PRENDA " . ($index + 1), $prenda->nombre_producto);
            $this->assertEquals(2, $prenda->fotos->count());
            $this->assertEquals(3, $prenda->tallas->count());
        }
    }
}
