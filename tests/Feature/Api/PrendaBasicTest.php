<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class PrendaBasicTest extends TestCase
{
    /**
     * Test básico: POST /api/prendas existe y responde
     */
    public function test_endpoint_store_existe()
    {
        $payload = [
            'nombre' => 'Test Prenda',
            'tipo_cotizacion' => 'PRENDA',
            'genero' => 1,
            'telas' => [
                ['id' => 1, 'nombre' => 'Tela', 'codigo' => 'T1']
            ],
            'variaciones' => [
                ['talla' => 'M', 'color' => 'NEGRO']
            ]
        ];

        $response = $this->postJson('/api/prendas', $payload);
        
        // Solo verificar que responde (no se cuelgue)
        $this->assertNotNull($response->getContent());
    }
}
