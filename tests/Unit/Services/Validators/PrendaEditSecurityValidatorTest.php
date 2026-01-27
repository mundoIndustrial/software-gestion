<?php

namespace Tests\Unit\Services\Validators;

use Tests\TestCase;
use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\Infrastructure\Services\Validators\PrendaEditSecurityValidator;

class PrendaEditSecurityValidatorTest extends TestCase
{
    /**
     * Test: Validación clase estática existe
     */
    public function test_validator_class_static_exists(): void
    {
        $this->assertTrue(class_exists(PrendaEditSecurityValidator::class));
    }

    /**
     * Test: Método validateEdit existe
     */
    public function test_validator_has_validate_edit_method(): void
    {
        $reflection = new \ReflectionClass(PrendaEditSecurityValidator::class);
        $this->assertTrue($reflection->hasMethod('validateEdit'));
    }

    /**
     * Test: Método validateCantidadChange existe
     */
    public function test_validator_has_validate_cantidad_change_method(): void
    {
        $reflection = new \ReflectionClass(PrendaEditSecurityValidator::class);
        $this->assertTrue($reflection->hasMethod('validateCantidadChange'));
    }

    /**
     * Test: Validador clase existe y es estática
     */
    public function test_validator_class_is_static(): void
    {
        $this->assertTrue(class_exists(PrendaEditSecurityValidator::class));
        $reflection = new \ReflectionClass(PrendaEditSecurityValidator::class);
        $method = $reflection->getMethod('validateEdit');
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test: DTO tiene todos los campos esperados
     */
    public function test_dto_has_all_expected_fields(): void
    {
        $dto = new EditPrendaPedidoDTO(
            nombre_prenda: 'TEST',
            descripcion: 'DESC',
            cantidad: 100,
            de_bodega: true,
            tallas: [],
            variantes: [],
            colores: [],
            telas: []
        );

        $this->assertEquals('TEST', $dto->nombre_prenda);
        $this->assertEquals('DESC', $dto->descripcion);
        $this->assertEquals(100, $dto->cantidad);
        $this->assertTrue($dto->de_bodega);
        $this->assertEquals([], $dto->tallas);
        $this->assertEquals([], $dto->variantes);
        $this->assertEquals([], $dto->colores);
        $this->assertEquals([], $dto->telas);
    }

    /**
     * Test: EditPrendaVariantePedidoDTO existe
     */
    public function test_variant_dto_class_exists(): void
    {
        $this->assertTrue(class_exists(\App\DTOs\Edit\EditPrendaVariantePedidoDTO::class));
    }
}
