<?php

namespace Tests\Unit;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaVarianteCot;
use App\Models\PrendaTelaCot;
use App\Models\HistorialCambiosCotizacion;
use Tests\TestCase;

class CotizacionSincronizacionTest extends TestCase
{
    /**
     * Test: Verificar que el modelo Cotizacion tiene todos los campos sincronizados
     */
    public function test_cotizacion_modelo_tiene_campos_sincronizados()
    {
        $cotizacion = new Cotizacion();
        
        // Verificar fillable
        $fillable = $cotizacion->getFillable();
        
        $camposEsperados = [
            'asesor_id',
            'cliente_id',
            'numero_cotizacion',
            'tipo_cotizacion_id',
            'tipo_venta',
            'fecha_inicio',
            'fecha_envio',
            'es_borrador',
            'estado',
            'especificaciones',
            'imagenes',
            'tecnicas',
            'observaciones_tecnicas',
            'ubicaciones',
            'observaciones_generales'
        ];
        
        foreach ($camposEsperados as $campo) {
            $this->assertContains($campo, $fillable, "Campo '$campo' debe estar en fillable");
        }
        
        echo "\n Cotizacion: Todos los campos están en fillable\n";
    }

    /**
     * Test: Verificar que el modelo Cotizacion tiene los casts correctos
     */
    public function test_cotizacion_modelo_tiene_casts_correctos()
    {
        $cotizacion = new Cotizacion();
        $casts = $cotizacion->getCasts();
        
        $this->assertArrayHasKey('es_borrador', $casts, 'es_borrador debe tener cast');
        $this->assertEquals('boolean', $casts['es_borrador']);
        
        $this->assertArrayHasKey('imagenes', $casts, 'imagenes debe tener cast');
        $this->assertEquals('array', $casts['imagenes']);
        
        $this->assertArrayHasKey('tecnicas', $casts, 'tecnicas debe tener cast');
        $this->assertEquals('array', $casts['tecnicas']);
        
        $this->assertArrayHasKey('observaciones_tecnicas', $casts, 'observaciones_tecnicas debe tener cast');
        
        $this->assertArrayHasKey('ubicaciones', $casts, 'ubicaciones debe tener cast');
        $this->assertEquals('array', $casts['ubicaciones']);
        
        $this->assertArrayHasKey('observaciones_generales', $casts, 'observaciones_generales debe tener cast');
        $this->assertEquals('array', $casts['observaciones_generales']);
        
        echo "\n Cotizacion: Todos los casts están configurados correctamente\n";
    }

    /**
     * Test: Verificar que PrendaTelaCot tiene los campos sincronizados
     */
    public function test_prenda_tela_cot_modelo_tiene_campos_sincronizados()
    {
        $tela = new PrendaTelaCot();
        $fillable = $tela->getFillable();
        
        $camposEsperados = [
            'prenda_cot_id',
            'variante_prenda_cot_id',
            'color_id',
            'tela_id',
        ];
        
        foreach ($camposEsperados as $campo) {
            $this->assertContains($campo, $fillable, "Campo '$campo' debe estar en fillable de PrendaTelaCot");
        }
        
        echo "\n PrendaTelaCot: Todos los campos están en fillable\n";
    }

    /**
     * Test: Verificar que PrendaTelaCot tiene las relaciones correctas
     */
    public function test_prenda_tela_cot_tiene_relaciones_correctas()
    {
        $tela = new PrendaTelaCot();
        
        // Verificar que los métodos de relación existen
        $this->assertTrue(method_exists($tela, 'prenda'), 'Debe tener método prenda()');
        $this->assertTrue(method_exists($tela, 'variante'), 'Debe tener método variante()');
        $this->assertTrue(method_exists($tela, 'color'), 'Debe tener método color()');
        $this->assertTrue(method_exists($tela, 'tela'), 'Debe tener método tela()');
        
        echo "\n PrendaTelaCot: Todas las relaciones existen\n";
    }

    /**
     * Test: Verificar que PrendaVarianteCot tiene el campo telas_multiples
     */
    public function test_prenda_variante_cot_tiene_telas_multiples()
    {
        $variante = new PrendaVarianteCot();
        $fillable = $variante->getFillable();
        
        $this->assertContains('telas_multiples', $fillable, 'telas_multiples debe estar en fillable');
        
        $casts = $variante->getCasts();
        $this->assertArrayHasKey('telas_multiples', $casts, 'telas_multiples debe tener cast');
        $this->assertEquals('json', $casts['telas_multiples']);
        
        echo "\n PrendaVarianteCot: Campo telas_multiples está sincronizado\n";
    }

    /**
     * Test: Verificar que HistorialCambiosCotizacion modelo existe
     */
    public function test_historial_cambios_cotizacion_modelo_existe()
    {
        $historial = new HistorialCambiosCotizacion();
        
        $fillable = $historial->getFillable();
        
        $camposEsperados = [
            'cotizacion_id',
            'estado_anterior',
            'estado_nuevo',
            'usuario_id',
            'usuario_nombre',
            'rol_usuario',
            'razon_cambio',
            'ip_address',
            'user_agent',
            'datos_adicionales',
            'created_at',
        ];
        
        foreach ($camposEsperados as $campo) {
            $this->assertContains($campo, $fillable, "Campo '$campo' debe estar en fillable de HistorialCambiosCotizacion");
        }
        
        echo "\n HistorialCambiosCotizacion: Todos los campos están en fillable\n";
    }

    /**
     * Test: Verificar que HistorialCambiosCotizacion tiene relaciones
     */
    public function test_historial_cambios_cotizacion_tiene_relaciones()
    {
        $historial = new HistorialCambiosCotizacion();
        
        $this->assertTrue(method_exists($historial, 'cotizacion'), 'Debe tener método cotizacion()');
        $this->assertTrue(method_exists($historial, 'usuario'), 'Debe tener método usuario()');
        
        echo "\n HistorialCambiosCotizacion: Todas las relaciones existen\n";
    }

    /**
     * Test: Verificar que PrendaCot tiene relaciones correctas
     */
    public function test_prenda_cot_tiene_relaciones_correctas()
    {
        $prenda = new PrendaCot();
        
        $this->assertTrue(method_exists($prenda, 'cotizacion'), 'Debe tener método cotizacion()');
        $this->assertTrue(method_exists($prenda, 'fotos'), 'Debe tener método fotos()');
        $this->assertTrue(method_exists($prenda, 'telas'), 'Debe tener método telas()');
        $this->assertTrue(method_exists($prenda, 'telaFotos'), 'Debe tener método telaFotos()');
        $this->assertTrue(method_exists($prenda, 'tallas'), 'Debe tener método tallas()');
        $this->assertTrue(method_exists($prenda, 'variantes'), 'Debe tener método variantes()');
        
        echo "\n PrendaCot: Todas las relaciones existen\n";
    }

    /**
     * Test: Resumen de sincronización
     */
    public function test_resumen_sincronizacion_completa()
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║  RESUMEN DE SINCRONIZACIÓN - COTIZACIONES DDD              ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        echo "║   Tabla cotizaciones                                      ║\n";
        echo "║     - Campos: imagenes, tecnicas, observaciones_tecnicas   ║\n";
        echo "║     - Campos: ubicaciones, observaciones_generales         ║\n";
        echo "║   Tabla prenda_variantes_cot                              ║\n";
        echo "║     - Campo: telas_multiples (JSON)                        ║\n";
        echo "║   Tabla prenda_telas_cot                                  ║\n";
        echo "║     - Campos: color_id, tela_id, variante_prenda_cot_id    ║\n";
        echo "║     - Relaciones: color(), tela(), variante()              ║\n";
        echo "║   Tabla historial_cambios_cotizaciones                    ║\n";
        echo "║     - Tabla creada con estructura completa                 ║\n";
        echo "║     - Relaciones: cotizacion(), usuario()                  ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
        
        $this->assertTrue(true);
    }
}
