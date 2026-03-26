<?php

namespace App\Application\Pedidos\Validators;

use App\Domain\Shared\Validators\Validator;
use App\Models\PedidoProduccion;
use InvalidArgumentException;

/**
 * Valida datos de pedidos con reglas que dependen del estado persistido.
 */
class PedidoValidator implements Validator
{
    public function validate(array $data): void
    {
        $this->validateNumeroPedido($data['numero_pedido'] ?? null);
        $this->validateCliente($data['cliente'] ?? null);
        $this->validateFormaPago($data['forma_pago'] ?? null);
        $this->validateAsesorId($data['asesor_id'] ?? null);
        $this->validateCantidadInicial($data['cantidad_inicial'] ?? null);
    }

    public function validateField(string $field, mixed $value): void
    {
        match ($field) {
            'numero_pedido' => $this->validateNumeroPedido($value),
            'cliente' => $this->validateCliente($value),
            'forma_pago' => $this->validateFormaPago($value),
            'asesor_id' => $this->validateAsesorId($value),
            'cantidad_inicial' => $this->validateCantidadInicial($value),
            default => throw new InvalidArgumentException("Campo no reconocido: {$field}"),
        };
    }

    private function validateNumeroPedido(?string $numero): void
    {
        if (empty($numero)) {
            throw new InvalidArgumentException('El numero de pedido es requerido');
        }

        if (strlen($numero) > 50) {
            throw new InvalidArgumentException('El numero de pedido no puede exceder 50 caracteres');
        }

        if (PedidoProduccion::where('numero_pedido', $numero)->exists()) {
            throw new InvalidArgumentException("El numero de pedido '{$numero}' ya existe");
        }
    }

    private function validateCliente(?string $cliente): void
    {
        if (empty($cliente)) {
            throw new InvalidArgumentException('El cliente es requerido');
        }

        if (strlen($cliente) > 255) {
            throw new InvalidArgumentException('El cliente no puede exceder 255 caracteres');
        }
    }

    private function validateFormaPago(?string $formaPago): void
    {
        if (empty($formaPago)) {
            throw new InvalidArgumentException('La forma de pago es requerida');
        }

        $formaPagoNormalizada = strtolower(trim($formaPago));
        $formasPagoPermitidas = ['contado', 'credito', 'transferencia', 'cheque'];

        if (!in_array($formaPagoNormalizada, $formasPagoPermitidas)) {
            throw new InvalidArgumentException(
                "Forma de pago invalida: {$formaPago}. Permitidas: " . implode(', ', $formasPagoPermitidas)
            );
        }
    }

    private function validateAsesorId(?int $asesorId): void
    {
        if (empty($asesorId)) {
            throw new InvalidArgumentException('El ID del asesor es requerido');
        }

        if ($asesorId <= 0) {
            throw new InvalidArgumentException('El ID del asesor debe ser positivo');
        }
    }

    private function validateCantidadInicial(?int $cantidad): void
    {
        if ($cantidad === null) {
            throw new InvalidArgumentException('La cantidad inicial es requerida');
        }

        if ($cantidad < 0) {
            throw new InvalidArgumentException('La cantidad inicial no puede ser negativa');
        }
    }

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
