<?php

namespace App\Domain\PedidoProduccion\Validators;

use App\Domain\Shared\Validators\Validator;
use App\Models\PedidoProduccion;
use InvalidArgumentException;

/**
 * PedidoValidator - Valida datos de pedidos
 * 
 * Responsabilidad:
 * - Validar número de pedido único
 * - Validar campos requeridos
 * - Validar cantidades y formatos
 * - Integración con BD para verificaciones
 * 
 * Patrón: Strategy (implementa Validator)
 * SRP: Solo valida pedidos
 */
class PedidoValidator implements Validator
{
    /**
     * Validar todos los datos de un pedido
     * 
     * @param array $data Datos del pedido
     * @return void
     * @throws InvalidArgumentException
     */
    public function validate(array $data): void
    {
        $this->validateNumeroPedido($data['numero_pedido'] ?? null);
        $this->validateCliente($data['cliente'] ?? null);
        $this->validateFormaPago($data['forma_pago'] ?? null);
        $this->validateAsesorId($data['asesor_id'] ?? null);
        $this->validateCantidadInicial($data['cantidad_inicial'] ?? null);
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
            'numero_pedido' => $this->validateNumeroPedido($value),
            'cliente' => $this->validateCliente($value),
            'forma_pago' => $this->validateFormaPago($value),
            'asesor_id' => $this->validateAsesorId($value),
            'cantidad_inicial' => $this->validateCantidadInicial($value),
            default => throw new InvalidArgumentException("Campo no reconocido: {$field}")
        };
    }

    /**
     * Validar número de pedido
     * 
     * - No vacío
     * - Debe ser único en BD
     * - Máximo 50 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateNumeroPedido(?string $numero): void
    {
        if (empty($numero)) {
            throw new InvalidArgumentException('El número de pedido es requerido');
        }

        if (strlen($numero) > 50) {
            throw new InvalidArgumentException('El número de pedido no puede exceder 50 caracteres');
        }

        if (PedidoProduccion::where('numero_pedido', $numero)->exists()) {
            throw new InvalidArgumentException("El número de pedido '{$numero}' ya existe");
        }
    }

    /**
     * Validar cliente
     * 
     * - No vacío
     * - Máximo 255 caracteres
     * 
     * @throws InvalidArgumentException
     */
    private function validateCliente(?string $cliente): void
    {
        if (empty($cliente)) {
            throw new InvalidArgumentException('El cliente es requerido');
        }

        if (strlen($cliente) > 255) {
            throw new InvalidArgumentException('El cliente no puede exceder 255 caracteres');
        }
    }

    /**
     * Validar forma de pago
     * 
     * - No vacío
     * - Debe ser uno de los valores permitidos
     * 
     * @throws InvalidArgumentException
     */
    private function validateFormaPago(?string $formaPago): void
    {
        if (empty($formaPago)) {
            throw new InvalidArgumentException('La forma de pago es requerida');
        }

        $formasPagoPermitidas = ['contado', 'credito', 'transferencia', 'cheque'];
        
        if (!in_array($formaPago, $formasPagoPermitidas)) {
            throw new InvalidArgumentException(
                "Forma de pago inválida: {$formaPago}. Permitidas: " . implode(', ', $formasPagoPermitidas)
            );
        }
    }

    /**
     * Validar ID del asesor
     * 
     * - Debe ser numérico
     * - Debe ser positivo
     * - Asesor debe existir
     * 
     * @throws InvalidArgumentException
     */
    private function validateAsesorId(?int $asesorId): void
    {
        if (empty($asesorId)) {
            throw new InvalidArgumentException('El ID del asesor es requerido');
        }

        if ($asesorId <= 0) {
            throw new InvalidArgumentException('El ID del asesor debe ser positivo');
        }

        // Aquí se podría verificar si el asesor existe
        // if (!Asesor::find($asesorId)) {
        //     throw new InvalidArgumentException("El asesor con ID {$asesorId} no existe");
        // }
    }

    /**
     * Validar cantidad inicial
     * 
     * - Debe ser numérico
     * - Debe ser >= 0
     * 
     * @throws InvalidArgumentException
     */
    private function validateCantidadInicial(?int $cantidad): void
    {
        if ($cantidad === null) {
            throw new InvalidArgumentException('La cantidad inicial es requerida');
        }

        if ($cantidad < 0) {
            throw new InvalidArgumentException('La cantidad inicial no puede ser negativa');
        }
    }

    /**
     * Validar actualización de pedido
     * 
     * @param array $data Campos a actualizar
     * @return void
     * @throws InvalidArgumentException
     */
    public function validateUpdate(array $data): void
    {
        if (isset($data['cliente'])) {
            $this->validateCliente($data['cliente']);
        }

        if (isset($data['forma_pago'])) {
            $this->validateFormaPago($data['forma_pago']);
        }
    }
}
