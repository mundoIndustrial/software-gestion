<?php

namespace App\Application\Operario\DTOs;

readonly class ReciboCommandResultDTO
{
    public function __construct(
        public bool $success,
        public string $message,
        public int $statusCode = 200,
        public ?array $data = null,
    ) {}
}
