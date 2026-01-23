<?php

namespace Tests\Feature\LogoCotizacion;

use App\Models\LogoCotizacion;
use App\Models\TipoLogoCotizacion;
use Tests\TestCase;

class LogoCotizacionTecnicaControllerTest extends TestCase
{
    protected $logoCotizacion;

    public function setUp(): void
    {
        parent::setUp();
        $this->logoCotizacion = LogoCotizacion::factory()->create();
    }

    /** @test */
    public function puede_obtener_tipos_disponibles()
    {
        // Act
        $response = $this->getJson('/api/logo-cotizacion-tecnicas/tipos-disponibles');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'nombre', 'codigo', 'color', 'icono']
            ]
        ]);
        $response->assertJsonCount(4, 'data'); // 4 tipos
    }

    /** @test */
    public function puede_agregar_tecnica_a_cotizacion()
    {
        // Arrange
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'BORDADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Bordado de calidad premium',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Bordado frontal en pecho',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M', 'L', 'XL'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'logoCotizacionId',
                'tipoTecnicaId',
            ]
        ]);
    }

    /** @test */
    public function agregar_tecnica_requiere_logoCotizacionId()
    {
        // Arrange
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'ESTAMPADO')->first();
        
        $payload = [
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Estampado',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Estampado',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logoCotizacionId');
    }

    /** @test */
    public function agregar_tecnica_requiere_tipoTecnicaId()
    {
        // Arrange
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'observaciones' => 'Estampado',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Estampado',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tipoTecnicaId');
    }

    /** @test */
    public function agregar_tecnica_requiere_prendas()
    {
        // Arrange
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'SUBLIMADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Sublimado de calidad',
            'prendas' => [],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function puede_obtener_tecnicas_de_cotizacion()
    {
        // Arrange - Agregar una tÃ©cnica primero
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'BORDADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Prueba',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Prueba',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Act
        $response = $this->getJson("/api/logo-cotizacion-tecnicas/cotizacion/{$this->logoCotizacion->id}");

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'logoCotizacionId',
                    'tipoTecnicaId',
                    'prendas',
                ]
            ]
        ]);
    }

    /** @test */
    public function puede_eliminar_tecnica()
    {
        // Arrange - Agregar una tÃ©cnica primero
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'DTF')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'DTF de prueba',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'DTF',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['L'],
                ],
            ],
        ];

        $createdResponse = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);
        $tecnicaId = $createdResponse->json('data.id');

        // Act
        $response = $this->deleteJson("/api/logo-cotizacion-tecnicas/{$tecnicaId}");

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function puede_actualizar_observaciones()
    {
        // Arrange - Agregar una tÃ©cnica primero
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'BORDADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'ObservaciÃ³n original',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Bordado',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        $createdResponse = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);
        $tecnicaId = $createdResponse->json('data.id');

        // Act
        $updatePayload = [
            'observaciones' => 'ObservaciÃ³n actualizada',
        ];
        
        $response = $this->patchJson(
            "/api/logo-cotizacion-tecnicas/{$tecnicaId}/observaciones",
            $updatePayload
        );

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function retorna_error_si_cotizacion_no_existe()
    {
        // Arrange
        $payload = [
            'logoCotizacionId' => 99999,
            'tipoTecnicaId' => 1,
            'observaciones' => 'Prueba',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => 'Prueba',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function puede_agregar_prenda_sin_descripcion()
    {
        // Arrange
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'ESTAMPADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Estampado',
            'prendas' => [
                [
                    'nombre_prenda' => 'CAMISETA',
                    'descripcion' => '',
                    'ubicaciones' => ['PECHO'],
                    'tallas' => ['M'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(201);
    }

    /** @test */
    public function puede_agregar_prenda_con_multiples_ubicaciones()
    {
        // Arrange
        $tipoTecnica = TipoLogoCotizacion::where('codigo', 'BORDADO')->first();
        
        $payload = [
            'logoCotizacionId' => $this->logoCotizacion->id,
            'tipoTecnicaId' => $tipoTecnica->id,
            'observaciones' => 'Bordado en mÃºltiples ubicaciones',
            'prendas' => [
                [
                    'nombre_prenda' => 'SUDADERA',
                    'descripcion' => 'Bordado en pecho y espalda',
                    'ubicaciones' => ['PECHO', 'ESPALDA'],
                    'tallas' => ['M', 'L', 'XL'],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/logo-cotizacion-tecnicas/agregar', $payload);

        // Assert
        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
    }
}

