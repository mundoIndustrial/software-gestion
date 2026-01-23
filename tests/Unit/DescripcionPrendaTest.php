<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PrendaPedido;
use App\Helpers\DescripcionPrendaHelper;

class DescripcionPrendaTest extends TestCase
{
    /**
     * Test: Generar descripciÃ³n con template completo
     */
    public function test_generar_descripcion_template_completo()
    {
        $prenda = [
            'numero' => 1,
            'tipo' => 'Camisa Drill',
            'color' => 'Naranja',
            'tela' => 'Drill Borneo',
            'ref' => 'REF-DB-001',
            'manga' => 'Larga',
            'logo' => 'Logo bordado en espalda',
            'bolsillos' => ['Pecho', 'Espalda'],
            'reflectivos' => ['Mangas', 'PuÃ±os'],
            'otros' => ['Refuerzo en cuello', 'Costuras reforzadas'],
            'tallas' => ['S' => 50, 'M' => 50, 'L' => 50],
        ];

        $descripcion = DescripcionPrendaHelper::generarDescripcion($prenda);

        // Verificar estructura
        $this->assertStringContainsString('1: CAMISA DRILL', $descripcion);
        $this->assertStringContainsString('Color: Naranja', $descripcion);
        $this->assertStringContainsString('Tela: Drill Borneo REF-DB-001', $descripcion);
        $this->assertStringContainsString('Manga: Larga', $descripcion);
        $this->assertStringContainsString('DESCRIPCIÃ“N:', $descripcion);
        $this->assertStringContainsString('- Logo: Logo bordado en espalda', $descripcion);
        $this->assertStringContainsString('Bolsillos:', $descripcion);
        $this->assertStringContainsString('â€¢ Pecho', $descripcion);
        $this->assertStringContainsString('â€¢ Espalda', $descripcion);
        $this->assertStringContainsString('Reflectivo:', $descripcion);
        $this->assertStringContainsString('â€¢ Mangas', $descripcion);
        $this->assertStringContainsString('â€¢ PuÃ±os', $descripcion);
        $this->assertStringContainsString('Otros detalles:', $descripcion);
        $this->assertStringContainsString('â€¢ Refuerzo en cuello', $descripcion);
        $this->assertStringContainsString('TALLAS:', $descripcion);
        $this->assertStringContainsString('- S: 50', $descripcion);
        $this->assertStringContainsString('- M: 50', $descripcion);
        $this->assertStringContainsString('- L: 50', $descripcion);
    }

    /**
     * Test: Generar descripciÃ³n sin algunos datos opcionales
     */
    public function test_generar_descripcion_datos_minimos()
    {
        $prenda = [
            'numero' => 2,
            'tipo' => 'Jeans',
            'color' => 'Azul',
            'tela' => 'Denim',
            'ref' => '',
            'manga' => 'Larga',
            'logo' => '',
            'bolsillos' => [],
            'reflectivos' => [],
            'otros' => [],
            'tallas' => ['S' => 100, 'L' => 80],
        ];

        $descripcion = DescripcionPrendaHelper::generarDescripcion($prenda);

        // Verificar que contiene datos mÃ­nimos
        $this->assertStringContainsString('2: JEANS', $descripcion);
        $this->assertStringContainsString('Color: Azul', $descripcion);
        $this->assertStringContainsString('Tela: Denim', $descripcion);
        $this->assertStringContainsString('TALLAS:', $descripcion);

        // Verificar que NO contiene secciones vacÃ­as
        $this->assertStringNotContainsString('Bolsillos:', $descripcion);
        $this->assertStringNotContainsString('Reflectivo:', $descripcion);
        $this->assertStringNotContainsString('Otros detalles:', $descripcion);
    }

    /**
     * Test: Parsear lista de items con viÃ±etas
     */
    public function test_parsear_lista_items_viÃ±etas()
    {
        $text = "â€¢ Pecho\nâ€¢ Espalda\nâ€¢ Bolsillos laterales";
        $items = DescripcionPrendaHelper::parsearListaItems($text);

        $this->assertCount(3, $items);
        $this->assertContains('Pecho', $items);
        $this->assertContains('Espalda', $items);
        $this->assertContains('Bolsillos laterales', $items);
    }

    /**
     * Test: Parsear lista de items con lÃ­neas
     */
    public function test_parsear_lista_items_lineas()
    {
        $text = "Pecho\nEspalda\nBolsillos laterales";
        $items = DescripcionPrendaHelper::parsearListaItems($text);

        $this->assertCount(3, $items);
        $this->assertContains('Pecho', $items);
        $this->assertContains('Espalda', $items);
        $this->assertContains('Bolsillos laterales', $items);
    }
}

