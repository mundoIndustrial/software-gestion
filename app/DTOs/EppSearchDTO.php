<?php

namespace App\DTOs;

/**
 * DTO para consultar/listar EPP
 */
class EppSearchDTO
{
    public function __construct(
        public readonly ?string $termino = null,
        public readonly ?string $categoria = null,
        public readonly bool $soloActivos = true,
        public readonly int $pagina = 1,
        public readonly int $porPagina = 20,
    ) {}

    /**
     * Factory method desde query parameters
     */
    public static function fromRequest(array $query): self
    {
        return new self(
            termino: trim($query['q'] ?? '') ?: null,
            categoria: trim($query['categoria'] ?? '') ?: null,
            soloActivos: (bool)($query['activos'] ?? true),
            pagina: max(1, (int)($query['pagina'] ?? 1)),
            porPagina: min(100, max(1, (int)($query['por_pagina'] ?? 20))),
        );
    }
}
