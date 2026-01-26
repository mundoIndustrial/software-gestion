<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ActualizarVariantePrendaDTO
 * 
 * DTO para actualizar la variante de una prenda en un pedido.
 * Soporta actualización parcial (merge) de campos.
 * 
 * @param int $pedidoId - ID del pedido
 * @param int $prendaId - ID de la prenda
 * @param int|null $tipoMangaId - ID del tipo de manga (opcional)
 * @param string|null $mangaObs - Observaciones de manga (opcional)
 * @param int|null $tipoBrocheBotónId - ID del tipo de broche (opcional)
 * @param string|null $brocheBotónObs - Observaciones de broche (opcional)
 * @param bool|null $tieneBolsillos - Si tiene bolsillos (opcional)
 * @param string|null $bolsillosObs - Observaciones de bolsillos (opcional)
 */
final class ActualizarVariantePrendaDTO
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $prendaId,
        public readonly ?int $tipoMangaId = null,
        public readonly ?string $mangaObs = null,
        public readonly ?int $tipoBrocheBotónId = null,
        public readonly ?string $brocheBotónObs = null,
        public readonly ?bool $tieneBolsillos = null,
        public readonly ?string $bolsillosObs = null,
    ) {}

    /**
     * Crear DTO desde un request HTTP
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            pedidoId: (int) ($data['pedido_id'] ?? $data['pedidoId'] ?? 0),
            prendaId: (int) ($data['prenda_id'] ?? $data['prendaId'] ?? 0),
            tipoMangaId: isset($data['tipo_manga_id']) ? (int) $data['tipo_manga_id'] : null,
            mangaObs: $data['manga_obs'] ?? null,
            tipoBrocheBotónId: isset($data['tipo_broche_boton_id']) ? (int) $data['tipo_broche_boton_id'] : null,
            brocheBotónObs: $data['broche_boton_obs'] ?? null,
            tieneBolsillos: isset($data['tiene_bolsillos']) ? (bool) $data['tiene_bolsillos'] : null,
            bolsillosObs: $data['bolsillos_obs'] ?? null,
        );
    }

    /**
     * Obtener array de campos a actualizar (solo los no-null)
     */
    public function getCamposActualizables(): array
    {
        $campos = [];

        if ($this->tipoMangaId !== null) {
            $campos['tipo_manga_id'] = $this->tipoMangaId;
        }

        if ($this->mangaObs !== null) {
            $campos['manga_obs'] = $this->mangaObs;
        }

        if ($this->tipoBrocheBotónId !== null) {
            $campos['tipo_broche_boton_id'] = $this->tipoBrocheBotónId;
        }

        if ($this->brocheBotónObs !== null) {
            $campos['broche_boton_obs'] = $this->brocheBotónObs;
        }

        if ($this->tieneBolsillos !== null) {
            $campos['tiene_bolsillos'] = $this->tieneBolsillos;
        }

        if ($this->bolsillosObs !== null) {
            $campos['bolsillos_obs'] = $this->bolsillosObs;
        }

        return $campos;
    }

    /**
     * Verificar si hay algún campo a actualizar
     */
    public function hayAlgunCampo(): bool
    {
        return !empty($this->getCamposActualizables());
    }
}
