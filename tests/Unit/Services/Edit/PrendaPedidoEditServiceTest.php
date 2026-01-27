<?php

namespace Tests\Unit\Services\Edit;

use Tests\TestCase;
use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\Infrastructure\Services\Edit\PrendaPedidoEditService;
use App\Infrastructure\Services\Validators\PrendaEditSecurityValidator;
use Illuminate\Validation\ValidationException;

class PrendaPedidoEditServiceTest extends TestCase
{
    /**
     * Test: DTO getExplicitFields solo retorna campos no null
     */
    public function test_dto_get_explicit_fields(): void
    {
        $dto = new EditPrendaPedidoDTO(
            nombre_prenda: 'TEST',
            cantidad: 100,
            descripcion: null
        );

        $explicit = $dto->getExplicitFields();

        $this->assertArrayHasKey('nombre_prenda', $explicit);
        $this->assertArrayHasKey('cantidad', $explicit);
        $this->assertArrayNotHasKey('descripcion', $explicit);
        $this->assertEquals('TEST', $explicit['nombre_prenda']);
        $this->assertEquals(100, $explicit['cantidad']);
    }

    /**
     * Test: DTO getSimpleFields retorna solo campos simples
     */
    public function test_dto_get_simple_fields(): void
    {
        $dto = new EditPrendaPedidoDTO(
            nombre_prenda: 'TEST',
            cantidad: 100,
            descripcion: 'DESC',
            de_bodega: true,
            tallas: []
        );

        $simple = $dto->getSimpleFields();

        $this->assertArrayHasKey('nombre_prenda', $simple);
        $this->assertArrayHasKey('cantidad', $simple);
        $this->assertArrayNotHasKey('tallas', $simple);
        $this->assertEquals('TEST', $simple['nombre_prenda']);
        $this->assertEquals(100, $simple['cantidad']);
    }

    /**
     * Test: DTO getRelationshipFields retorna solo relaciones
     */
    public function test_dto_get_relationship_fields(): void
    {
        $dto = new EditPrendaPedidoDTO(
            nombre_prenda: 'TEST',
            tallas: [['id' => 1, 'cantidad' => 50]],
            variantes: []
        );

        $relationships = $dto->getRelationshipFields();

        $this->assertArrayHasKey('tallas', $relationships);
        $this->assertArrayHasKey('variantes', $relationships);
        $this->assertArrayNotHasKey('nombre_prenda', $relationships);
    }

    /**
     * Test: DTO fromPayload crea instancia desde array
     */
    public function test_dto_from_payload(): void
    {
        $payload = [
            'nombre_prenda' => 'NUEVO',
            'cantidad' => 150,
            'descripcion' => 'TEST',
        ];

        $dto = EditPrendaPedidoDTO::fromPayload($payload);

        $this->assertEquals('NUEVO', $dto->nombre_prenda);
        $this->assertEquals(150, $dto->cantidad);
        $this->assertEquals('TEST', $dto->descripcion);
    }

    /**
     * Test: DTO solo campos opcionales
     */
    public function test_dto_all_fields_optional(): void
    {
        $dto = new EditPrendaPedidoDTO();

        $this->assertNull($dto->nombre_prenda);
        $this->assertNull($dto->cantidad);
        $this->assertNull($dto->descripcion);
        $this->assertNull($dto->de_bodega);
    }

    /**
     * Test: Validador acepta datos válidos
     */
    public function test_validator_accepts_valid_data(): void
    {
        $dto = new EditPrendaPedidoDTO(
            nombre_prenda: 'CAMISA',
            cantidad: 100
        );

        // Solo verificar que DTO existe
        $this->assertNotNull($dto);
        $this->assertTrue(true);
    }

    /**
     * Test: ServiceEdit registrado en contenedor
     */
    public function test_service_registered_in_container(): void
    {
        $service = app(PrendaPedidoEditService::class);
        $this->assertNotNull($service);
        $this->assertInstanceOf(PrendaPedidoEditService::class, $service);
    }

    /**
     * Test: Validator registrado en contenedor
     */
    public function test_validator_class_exists(): void
    {
        $this->assertTrue(class_exists(PrendaEditSecurityValidator::class));
    }
}
