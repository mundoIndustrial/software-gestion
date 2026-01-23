<?php

namespace Tests\Unit\Helpers;

use App\Helpers\DescripcionPrendaHelper;
use PHPUnit\Framework\TestCase;

class DescripcionPrendaHelperTest extends TestCase
{
    /**
     * Test que verifica la generaciÃ³n completa de descripciÃ³n con todos los datos
     */
    public function test_generar_descripcion_completa()
    {
        $datos = [
            'numero' => 1,
            'tipo' => 'CAMISA DRILL',
            'color' => 'NARANJA',
            'tela' => 'DRILL BORNEO',
            'ref' => 'REF:REF-DB-001',
            'manga' => 'LARGA',
            'obs_manga' => 'Con puÃ±os ajustables',
            'logo' => 'Logo bordado en espalda',
            'bolsillos' => [
                'BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO "TRANSPORTE" BOLSILLO IZQUIERDO "ANI"'
            ],
            'broche' => 'BOTÃ“N',
            'reflectivos' => [
                'REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO Y UNA LINEA A LA ALTURA DEL OMBLIGO'
            ],
            'otros' => [],
            'tallas' => [
                'S' => 50,
                'M' => 50,
                'L' => 50,
                'XL' => 50,
            ]
        ];

        // Generar descripciÃ³n
        $descripcion = DescripcionPrendaHelper::generarDescripcion($datos);

        // Verificar que la descripciÃ³n contiene todos los elementos
        $this->assertStringContainsString('PRENDA 1: CAMISA DRILL', $descripcion);
        $this->assertStringContainsString('Color:', $descripcion);
        $this->assertStringContainsString('NARANJA', $descripcion);
        $this->assertStringContainsString('Tela:', $descripcion);
        $this->assertStringContainsString('DRILL BORNEO', $descripcion);
        $this->assertStringContainsString('REF-DB-001', $descripcion);
        $this->assertStringContainsString('Manga:', $descripcion);
        $this->assertStringContainsString('LARGA', $descripcion);
        $this->assertStringContainsString('Con puÃ±os ajustables', $descripcion);
        $this->assertStringContainsString('Bolsillos:', $descripcion);
        $this->assertStringContainsString('Reflectivo:', $descripcion);
        $this->assertStringContainsString('BOTÃ“N:', $descripcion);
        $this->assertStringContainsString('TALLAS:', $descripcion);

        // Verificar que NO contiene "SI" como item
        $this->assertStringNotContainsString('â€¢ SI', $descripcion);

        echo "\n DescripciÃ³n generada correctamente:\n";
        echo $descripcion;
    }

    /**
     * Test que verifica que la limpieza de "SI" funciona correctamente
     */
    public function test_limpiar_si_de_lista()
    {
        $datos = [
            'numero' => 1,
            'tipo' => 'PANTALON',
            'color' => 'AZUL',
            'tela' => 'DRILL',
            'ref' => '',
            'manga' => '',
            'obs_manga' => '',
            'logo' => '',
            'bolsillos' => ['SI', 'Bolsillo delantero izquierdo'],
            'broche' => 'CREMALLERA',
            'reflectivos' => ['SI', 'Reflectivo gris 2"'],
            'otros' => ['SI', 'Otro detalle'],
            'tallas' => ['32' => 40, '34' => 10],
        ];

        $descripcion = DescripcionPrendaHelper::generarDescripcion($datos);

        // No deberÃ­a contener "â€¢ SI" como item separado
        $this->assertStringNotContainsString('â€¢ SI', $descripcion);
        
        // DeberÃ­a contener los items sin "SI"
        $this->assertStringContainsString('â€¢ Bolsillo delantero izquierdo', $descripcion);
        $this->assertStringContainsString('â€¢ Reflectivo gris 2"', $descripcion);

        echo "\n Limpieza de 'SI' funcionando correctamente:\n";
        echo $descripcion;
    }

    /**
     * Test que verifica que los subtÃ­tulos sean dinÃ¡micos segÃºn el broche
     */
    public function test_subtitulos_dinamicos()
    {
        $datos = [
            'numero' => 1,
            'tipo' => 'CAMISA',
            'color' => '',
            'tela' => '',
            'ref' => '',
            'manga' => '',
            'obs_manga' => '',
            'logo' => '',
            'bolsillos' => [],
            'broche' => 'VELCRO',
            'reflectivos' => [],
            'otros' => [],
            'tallas' => [],
        ];

        $descripcion = DescripcionPrendaHelper::generarDescripcion($datos);

        // El subtÃ­tulo debe ser "VELCRO:" no "BROCHE:"
        $this->assertStringContainsString('VELCRO:', $descripcion);
        $this->assertStringNotContainsString('BROCHE:', $descripcion);

        echo "\n SubtÃ­tulos dinÃ¡micos funcionando:\n";
        echo $descripcion;
    }

    /**
     * Test que verifica el formato correcto con mÃºltiples prendas
     */
    public function test_formato_multiples_prendas()
    {
        $datos1 = [
            'numero' => 1,
            'tipo' => 'CAMISA',
            'color' => 'ROJO',
            'tela' => 'POLIESTER',
            'ref' => 'REF-001',
            'manga' => 'CORTA',
            'obs_manga' => '',
            'logo' => '',
            'bolsillos' => ['Bolsillo pecho'],
            'broche' => 'BOTÃ“N',
            'reflectivos' => [],
            'otros' => [],
            'tallas' => ['M' => 100, 'L' => 100],
        ];

        $datos2 = [
            'numero' => 2,
            'tipo' => 'PANTALON',
            'color' => 'NEGRO',
            'tela' => 'DRILL',
            'ref' => 'REF-002',
            'manga' => '',
            'obs_manga' => '',
            'logo' => '',
            'bolsillos' => [],
            'broche' => 'CREMALLERA',
            'reflectivos' => ['Reflectivo gris'],
            'otros' => [],
            'tallas' => ['32' => 50, '34' => 50],
        ];

        $desc1 = DescripcionPrendaHelper::generarDescripcion($datos1);
        $desc2 = DescripcionPrendaHelper::generarDescripcion($datos2);

        $this->assertStringContainsString('PRENDA 1:', $desc1);
        $this->assertStringContainsString('PRENDA 2:', $desc2);
        $this->assertStringContainsString('BOTÃ“N:', $desc1);
        $this->assertStringContainsString('CREMALLERA:', $desc2);

        echo "\n MÃºltiples prendas con formato correcto:\n";
        echo $desc1;
        echo "\n\n";
        echo $desc2;
    }
}

