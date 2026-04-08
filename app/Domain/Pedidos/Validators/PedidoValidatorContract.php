<?php

namespace App\Domain\Pedidos\Validators;

interface PedidoValidatorContract
{
    public function validate(array $data): void;

    public function validateField(string $field, mixed $value): void;

    public function validateUpdate(array $data): void;
}
