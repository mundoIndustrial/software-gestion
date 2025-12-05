<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\PrendaPedido;
use App\Helpers\DescripcionPrendaHelper;

class DescripcionPrendaTest extends TestCase
{
    /**
     * Test: Generar descripción con template completo
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
            'reflectivos' => ['Mangas', 'Puños'],
            'otros' => ['Refuerzo en cuello', 'Costuras reforzadas'],
            'tallas' => ['S' => 50, 'M' => 50, 'L' => 50],
        ];

        $descripcion = DescripcionPrendaHelper::generarDescripcion($prenda);

        // Verificar estructura
        $this->assertStringContainsString('1: CAMISA DRILL', $descripcion);
        $this->assertStringContainsString('Color: Naranja', $descripcion);
        $this->assertStringContainsString('Tela: Drill Borneo REF-DB-001', $descripcion);
        $this->assertStringContainsString('Manga: Larga', $descripcion);
        $this->assertStringContainsString('DESCRIPCIÓN:', $descripcion);
        $this->assertStringContainsString('- Logo: Logo bordado en espalda', $descripcion);
        $this->assertStringContainsString('Bolsillos:', $descripcion);
        $this->assertStringContainsString('• Pecho', $descripcion);
        $this->assertStringContainsString('• Espalda', $descripcion);
        $this->assertStringContainsString('Reflectivo:', $descripcion);
        $this->assertStringContainsString('• Mangas', $descripcion);
        $this->assertStringContainsString('• Puños', $descripcion);
        $this->assertStringContainsString('Otros detalles:', $descripcion);
        $this->assertStringContainsString('• Refuerzo en cuello', $descripcion);
        $this->assertStringContainsString('TALLAS:', $descripcion);
        $this->assertStringContainsString('- S: 50', $descripcion);
        $this->assertStringContainsString('- M: 50', $descripcion);
        $this->assertStringContainsString('- L: 50', $descripcion);
    }

    /**
     * Test: Generar descripción sin algunos datos opcionales
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

        // Verificar que contiene datos mínimos
        $this->assertStringContainsString('2: JEANS', $descripcion);
        $this->assertStringContainsString('Color: Azul', $descripcion);
        $this->assertStringContainsString('Tela: Denim', $descripcion);
        $this->assertStringContainsString('TALLAS:', $descripcion);

        // Verificar que NO contiene secciones vacías
        $this->assertStringNotContainsString('Bolsillos:', $descripcion);
        $this->assertStringNotContainsString('Reflectivo:', $descripcion);
        $this->assertStringNotContainsString('Otros detalles:', $descripcion);
    }

    /**
     * Test: Parsear lista de items con viñetas
     */
    public function test_parsear_lista_items_viñetas()
    {
        $text = "• Pecho\n• Espalda\n• Bolsillos laterales";
        $items = DescripcionPrendaHelper::parsearListaItems($text);

        $this->assertCount(3, $items);
        $this->assertContains('Pecho', $items);
        $this->assertContains('Espalda', $items);
        $this->assertContains('Bolsillos laterales', $items);
    }

    /**
     * Test: Parsear lista de items con líneas
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
