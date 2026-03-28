<?php

namespace App\Application\Pedidos\Validators;

use App\Domain\Pedidos\Validators\PedidoValidatorContract;
use App\Domain\Shared\Validators\Validator;

/**
 * Valida datos de pedidos con reglas que dependen del estado persistido.
 */
class PedidoValidator implements Validator
{
    public function __construct(private readonly PedidoValidatorContract $validator)
    {
    }

    public function validate(array $data): void
    {
        $this->validator->validate($data);
    }

    public function validateField(string $field, mixed $value): void
    {
        $this->validator->validateField($field, $value);
    }

    public function validateUpdate(array $data): void
    {
        $this->validator->validateUpdate($data);
    }
}
