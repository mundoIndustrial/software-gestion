<?php

namespace App\Domain\Pedidos\Validators;

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
 * PatrÃ³n: Strategy (implementa Validator)
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
        $this->validateTipoBrocheBoton($data['tipo_broche'] ?? null);
        $this->validateColor($data['color_id'] ?? null);
        $this->validateTela($data['tela_id'] ?? null);
    }

    /**
     * Validar un campo especÃ­fico
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
            'tipo_broche' => $this->validateTipoBrocheBoton($value),
            'color_id' => $this->validateColor($value),
            'tela_id' => $this->validateTela($value),
            default => throw new InvalidArgumentException("Campo no reconocido: {$field}")
        };
    }

    /**
     * Validar nombre de prenda
     * 
     * - No vacÃ­o
     * - MÃ¡ximo 255 caracteres
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
     * - Debe ser numÃ©rico
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
     * - No vacÃ­o
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
                "Tipo de prenda invÃ¡lido: '{$tipo}'. Permitidos: " . 
                implode(', ', self::TIPOS_PERMITIDOS)
            );
        }
    }

    /**
     * Validar tipo de manga
     * 
     * - No vacÃ­o
     * - MÃ¡ximo 100 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateTipoManga(?string $tipoManga): void
    {
        // Allow null for new prendas - can be specified later
        if ($tipoManga === null || $tipoManga === '') {
            return;
        }

        if (strlen($tipoManga) > 100) {
            throw new InvalidArgumentException('El tipo de manga no puede exceder 100 caracteres');
        }
    }

    /**
     * Validar tipo de broche/botÃ³n
     * 
     * - Opcional (puede ser null)
     * - MÃ¡ximo 100 caracteres si se especifica
     * 
     * @throws InvalidArgumentException
     */
    private function validateTipoBrocheBoton(?string $tipoBrocheBoton): void
    {
        // Allow null for new prendas - can be specified later
        if ($tipoBrocheBoton === null || $tipoBrocheBoton === '') {
            return;
        }

        if (strlen($tipoBrocheBoton) > 100) {
            throw new InvalidArgumentException('El tipo de broche/botÃ³n no puede exceder 100 caracteres');
        }
    }

    /**
     * Validar ID de color
     * 
     * - Opcional (puede ser null para nuevas prendas)
     * - Debe ser positivo si se especifica
     * 
     * @throws InvalidArgumentException
     */
    private function validateColor(?int $colorId): void
    {
        // Allow null for new prendas - can be specified later
        if ($colorId === null) {
            return;
        }

        if ($colorId <= 0) {
            throw new InvalidArgumentException('El ID del color debe ser positivo');
        }
    }

    /**
     * Validar ID de tela
     * 
     * - Opcional (puede ser null para nuevas prendas)
     * - Debe ser positivo si se especifica
     * 
     * @throws InvalidArgumentException
     */
    private function validateTela(?int $telaId): void
    {
        // Allow null for new prendas - can be specified later
        if ($telaId === null) {
            return;
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
        
        // Calcular cantidad total desde cantidad_talla si existe
        $cantidad = null;
        if (isset($prendaData['cantidad'])) {
            $cantidad = $prendaData['cantidad'];
        } elseif (isset($prendaData['cantidad_talla']) && is_array($prendaData['cantidad_talla'])) {
            // Sumar todas las cantidades de las tallas
            $cantidad = 0;
            foreach ($prendaData['cantidad_talla'] as $genero => $tallas) {
                if (is_array($tallas)) {
                    $cantidad += array_sum($tallas);
                }
            }
        }
        
        $this->validateCantidad($cantidad);
        $this->validateTipoManga($prendaData['tipo_manga'] ?? null);
        $this->validateTipoBrocheBoton($prendaData['tipo_broche'] ?? null);
        $this->validateColor($prendaData['color_id'] ?? null);
        $this->validateTela($prendaData['tela_id'] ?? null);
    }
}

