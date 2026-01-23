<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar variantes a una prenda
 * 
 * Maneja campos de prenda_pedido_variantes:
 * - tipo_manga_id: referencia a tipos_manga
 * - tipo_broche_boton_id: referencia a tipos_broche_boton
 * - manga_obs: observaciones de manga
 * - broche_boton_obs: observaciones de broche/botÃ³n
 * - tiene_bolsillos: boolean
 * - bolsillos_obs: observaciones de bolsillos
 */
final class AgregarVariantePrendaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly ?int $tipoMangaId = null,
        public readonly ?int $tipoBrocheBotonId = null,
        public readonly ?string $mangaObs = null,
        public readonly ?string $brocheBotonObs = null,
        public readonly bool $tieneBolsillos = false,
        public readonly ?string $bolsillosObs = null,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            tipoMangaId: $data['tipo_manga_id'] ?? null,
            tipoBrocheBotonId: $data['tipo_broche_boton_id'] ?? null,
            mangaObs: $data['manga_obs'] ?? null,
            brocheBotonObs: $data['broche_boton_obs'] ?? null,
            tieneBolsillos: $data['tiene_bolsillos'] ?? false,
            bolsillosObs: $data['bolsillos_obs'] ?? null,
        );
    }
}

