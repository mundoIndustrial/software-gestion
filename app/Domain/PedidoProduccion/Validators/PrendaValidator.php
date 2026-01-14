<?php

namespace App\Domain\PedidoProduccion\Validators;

use App\Domain\Shared\Validators\Validator;
use InvalidArgumentException;

/**
 * PrendaValidator - Valida datos de prendas
 * 
 * Responsabilidad:
 * - Validar campos de prenda
 * - Validar tipos de prenda (sin_cotizacion, reflectivo)
 * - Validar cantidades y dimensiones
 * 
 * Patrón: Strategy (implementa Validator)
 * SRP: Solo valida prendas
 */
class PrendaValidator implements Validator
{
    private const TIPOS_PERMITIDOS = [
        'sin_cotizacion',
        'reflectivo'
    ];

    /**
     * Validar todos los datos de una prenda
     * 
     * @param array $data Datos de la prenda
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(array $data): void
    {
        $this->validateNombrePrenda($data['nombre_prenda'] ?? null);
        $this->validateCantidad($data['cantidad'] ?? null);
        $this->validateTipo($data['tipo'] ?? null);
        $this->validateTipoManga($data['tipo_manga'] ?? null);
        $this->validateTipoBroche($data['tipo_broche'] ?? null);
        $this->validateColor($data['color_id'] ?? null);
        $this->validateTela($data['tela_id'] ?? null);
    }

    /**
     * Validar un campo específico
     * 
     * @param string $field
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateField(string $field, mixed $value): void
    {
        match ($field) {
            'nombre_prenda' => $this->validateNombrePrenda($value),
            'cantidad' => $this->validateCantidad($value),
            'tipo' => $this->validateTipo($value),
            'tipo_manga' => $this->validateTipoManga($value),
            'tipo_broche' => $this->validateTipoBroche($value),
            'color_id' => $this->validateColor($value),
            'tela_id' => $this->validateTela($value),
            default => throw new InvalidArgumentException("Campo no reconocido: {$field}")
        };
    }

    /**
     * Validar nombre de prenda
     * 
     * - No vacío
     * - Máximo 255 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateNombrePrenda(?string $nombre): void
    {
        if (empty($nombre)) {
            throw new InvalidArgumentException('El nombre de la prenda es requerido');
        }

        if (strlen($nombre) > 255) {
            throw new InvalidArgumentException('El nombre de la prenda no puede exceder 255 caracteres');
        }
    }

    /**
     * Validar cantidad
     * 
     * - Debe ser numérico
     * - Debe ser > 0
     * 
     * @throws InvalidArgumentException
     */
    private function validateCantidad(?int $cantidad): void
    {
        if ($cantidad === null) {
            throw new InvalidArgumentException('La cantidad es requerida');
        }

        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0');
        }
    }

    /**
     * Validar tipo de prenda
     * 
     * - No vacío
     * - Debe ser uno de los tipos permitidos
     * 
     * @throws InvalidArgumentException
     */
    private function validateTipo(?string $tipo): void
    {
        if (empty($tipo)) {
            throw new InvalidArgumentException('El tipo de prenda es requerido');
        }

        if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
            throw new InvalidArgumentException(
                "Tipo de prenda inválido: '{$tipo}'. Permitidos: " . 
                implode(', ', self::TIPOS_PERMITIDOS)
            );
        }
    }

    /**
     * Validar tipo de manga
     * 
     * - No vacío
     * - Máximo 100 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateTipoManga(?string $tipoManga): void
    {
        if (empty($tipoManga)) {
            throw new InvalidArgumentException('El tipo de manga es requerido');
        }

        if (strlen($tipoManga) > 100) {
            throw new InvalidArgumentException('El tipo de manga no puede exceder 100 caracteres');
        }
    }

    /**
     * Validar tipo de broche
     * 
     * - No vacío
     * - Máximo 100 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateTipoBroche(?string $tipoBroche): void
    {
        if (empty($tipoBroche)) {
            throw new InvalidArgumentException('El tipo de broche es requerido');
        }

        if (strlen($tipoBroche) > 100) {
            throw new InvalidArgumentException('El tipo de broche no puede exceder 100 caracteres');
        }
    }

    /**
     * Validar ID de color
     * 
     * - Debe ser numérico
     * - Debe ser positivo
     * 
     * @throws InvalidArgumentException
     */
    private function validateColor(?int $colorId): void
    {
        if ($colorId === null) {
            throw new InvalidArgumentException('El color es requerido');
        }

        if ($colorId <= 0) {
            throw new InvalidArgumentException('El ID del color debe ser positivo');
        }
    }

    /**
     * Validar ID de tela
     * 
     * - Debe ser numérico
     * - Debe ser positivo
     * 
     * @throws InvalidArgumentException
     */
    private function validateTela(?int $telaId): void
    {
        if ($telaId === null) {
            throw new InvalidArgumentException('La tela es requerida');
        }

        if ($telaId <= 0) {
            throw new InvalidArgumentException('El ID de la tela debe ser positivo');
        }
    }

    /**
     * Obtener tipos permitidos
     * 
     * @return array
     */
    public function getTiposPermitidos(): array
    {
        return self::TIPOS_PERMITIDOS;
    }

    /**
     * Validar datos para agregar prenda a pedido
     * 
     * @param array $prendaData Datos de la prenda
     * @param string $tipo Tipo: 'sin_cotizacion' o 'reflectivo'
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateAgregarAlPedido(array $prendaData, string $tipo): void
    {
        $this->validateTipo($tipo);
        
        // Validar campos básicos
        $this->validateNombrePrenda($prendaData['nombre_prenda'] ?? null);
        $this->validateCantidad($prendaData['cantidad'] ?? null);
        $this->validateTipoManga($prendaData['tipo_manga'] ?? null);
        $this->validateTipoBroche($prendaData['tipo_broche'] ?? null);
        $this->validateColor($prendaData['color_id'] ?? null);
        $this->validateTela($prendaData['tela_id'] ?? null);
    }
}
