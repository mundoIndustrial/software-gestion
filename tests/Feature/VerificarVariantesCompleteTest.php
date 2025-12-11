<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\TipoBroche;
use Tests\TestCase;

class VerificarVariantesCompleteTest extends TestCase
{

    public function test_variante_guarda_todos_los_campos_correctamente()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-001',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'Camiseta Test',
            'descripcion' => 'Descripción test',
            'genero' => 'Masculino',
        ]);

        // Crear tipo de broche
        $tipoBroche = TipoBroche::firstOrCreate(
            ['nombre' => 'Botón'],
            ['nombre' => 'Botón']
        );

        // Crear variante con todos los campos
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camiseta Básica',
            'es_jean_pantalon' => false,
            'tipo_jean_pantalon' => null,
            'genero_id' => 1,
            'color' => 'Azul',
            'tipo_manga_id' => 1,
            'tipo_broche_id' => $tipoBroche->id,
            'obs_broche' => 'Botón de 2 agujeros',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos laterales con cierre',
            'aplica_manga' => true,
            'tipo_manga' => 'Manga Corta',
            'obs_manga' => 'Manga con puño elástico',
            'aplica_broche' => true,
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Reflectivo en espalda',
            'descripcion_adicional' => 'Manga: Manga con puño elástico | Bolsillos: Bolsillos laterales con cierre | Broche: Botón de 2 agujeros | Reflectivo: Reflectivo en espalda',
        ]);

        // Verificar que la variante se guardó correctamente
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'id' => $variante->id,
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camiseta Básica',
            'es_jean_pantalon' => false,
            'genero_id' => 1,
            'color' => 'Azul',
            'tipo_broche_id' => $tipoBroche->id,
            'obs_broche' => 'Botón de 2 agujeros',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos laterales con cierre',
            'aplica_manga' => true,
            'tipo_manga' => 'Manga Corta',
            'obs_manga' => 'Manga con puño elástico',
            'aplica_broche' => true,
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Reflectivo en espalda',
        ]);

        // Verificar que se puede recuperar la variante con todos los datos
        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        
        $this->assertEquals('Camiseta Básica', $varianteRecuperada->tipo_prenda);
        $this->assertEquals('Azul', $varianteRecuperada->color);
        $this->assertTrue((bool) $varianteRecuperada->tiene_bolsillos);
        $this->assertEquals('Bolsillos laterales con cierre', $varianteRecuperada->obs_bolsillos);
        $this->assertTrue((bool) $varianteRecuperada->aplica_manga);
        $this->assertEquals('Manga Corta', $varianteRecuperada->tipo_manga);
        $this->assertEquals('Manga con puño elástico', $varianteRecuperada->obs_manga);
        $this->assertEquals($tipoBroche->id, $varianteRecuperada->tipo_broche_id);
        $this->assertEquals('Botón de 2 agujeros', $varianteRecuperada->obs_broche);
        $this->assertTrue((bool) $varianteRecuperada->tiene_reflectivo);
        $this->assertEquals('Reflectivo en espalda', $varianteRecuperada->obs_reflectivo);
    }

    public function test_variante_sin_manga_guarda_aplica_manga_false()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-002',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'Camiseta Sin Manga',
            'descripcion' => 'Descripción test',
            'genero' => 'Masculino',
        ]);

        // Crear variante sin manga
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camiseta Sin Manga',
            'color' => 'Rojo',
            'aplica_manga' => false,
            'tipo_manga' => null,
            'obs_manga' => null,
            'tiene_bolsillos' => false,
            'obs_bolsillos' => null,
            'tiene_reflectivo' => false,
            'obs_reflectivo' => null,
        ]);

        // Verificar que aplica_manga es false
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'id' => $variante->id,
            'aplica_manga' => false,
            'tipo_manga' => null,
            'obs_manga' => null,
        ]);

        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        $this->assertFalse((bool) $varianteRecuperada->aplica_manga);
    }

    public function test_variante_con_bolsillos_guarda_tiene_bolsillos_true()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-003',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'Pantalón Con Bolsillos',
            'descripcion' => 'Descripción test',
            'genero' => 'Masculino',
        ]);

        // Crear variante con bolsillos
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Pantalón',
            'color' => 'Negro',
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos traseros con botón',
        ]);

        // Verificar que tiene_bolsillos es true
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'id' => $variante->id,
            'tiene_bolsillos' => true,
            'obs_bolsillos' => 'Bolsillos traseros con botón',
        ]);

        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        $this->assertTrue((bool) $varianteRecuperada->tiene_bolsillos);
        $this->assertEquals('Bolsillos traseros con botón', $varianteRecuperada->obs_bolsillos);
    }

    public function test_variante_con_reflectivo_guarda_tiene_reflectivo_true()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-004',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'Chaleco Reflectivo',
            'descripcion' => 'Descripción test',
            'genero' => 'Unisex',
        ]);

        // Crear variante con reflectivo
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Chaleco',
            'color' => 'Naranja',
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Reflectivo en frente y espalda',
        ]);

        // Verificar que tiene_reflectivo es true
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'id' => $variante->id,
            'tiene_reflectivo' => true,
            'obs_reflectivo' => 'Reflectivo en frente y espalda',
        ]);

        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        $this->assertTrue((bool) $varianteRecuperada->tiene_reflectivo);
        $this->assertEquals('Reflectivo en frente y espalda', $varianteRecuperada->obs_reflectivo);
    }

    public function test_variante_con_broche_guarda_tipo_broche_id_y_observaciones()
    {
        // Crear usuario y autenticarse
        $user = User::factory()->create();
        $this->actingAs($user);

        // Crear cotización
        $cotizacion = Cotizacion::create([
            'asesor_id' => $user->id,
            'cliente_id' => 1,
            'numero_cotizacion' => 'COT-005',
            'tipo_cotizacion_id' => 1,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'es_borrador' => true,
            'estado' => 'BORRADOR',
        ]);

        // Crear prenda
        $prenda = PrendaCot::create([
            'cotizacion_id' => $cotizacion->id,
            'nombre_producto' => 'Camisa Con Broche',
            'descripcion' => 'Descripción test',
            'genero' => 'Masculino',
        ]);

        // Crear tipo de broche
        $tipoBroche = TipoBroche::firstOrCreate(
            ['nombre' => 'Broche Magnético'],
            ['nombre' => 'Broche Magnético']
        );

        // Crear variante con broche
        $variante = PrendaVarianteCot::create([
            'prenda_cot_id' => $prenda->id,
            'tipo_prenda' => 'Camisa',
            'color' => 'Blanco',
            'tipo_broche_id' => $tipoBroche->id,
            'obs_broche' => 'Broche magnético de neodimio',
            'aplica_broche' => true,
        ]);

        // Verificar que tipo_broche_id y obs_broche se guardaron
        $this->assertDatabaseHas('prenda_variantes_cot', [
            'id' => $variante->id,
            'tipo_broche_id' => $tipoBroche->id,
            'obs_broche' => 'Broche magnético de neodimio',
            'aplica_broche' => true,
        ]);

        $varianteRecuperada = PrendaVarianteCot::find($variante->id);
        $this->assertEquals($tipoBroche->id, $varianteRecuperada->tipo_broche_id);
        $this->assertEquals('Broche magnético de neodimio', $varianteRecuperada->obs_broche);
        $this->assertTrue((bool) $varianteRecuperada->aplica_broche);
    }
}
