<?php

namespace App\Domain\Pedidos\Commands;

use App\Domain\Shared\CQRS\Command;

/**
 * ActualizarVariantePrendaCommand
 * 
 * Command para actualizar la variante de una prenda en un pedido.
 * Realiza un merge de campos: solo actualiza los campos enviados, preserva el resto.
 * 
 * @param int $pedidoId - ID del pedido
 * @param int $prendaId - ID de la prenda a actualizar
 * @param int|null $tipoMangaId - Nuevo tipo de manga (null = no actualizar)
 * @param string|null $mangaObs - Nuevas observaciones de manga (null = no actualizar)
 * @param int|null $tipoBrocheBotónId - Nuevo tipo de broche/botón (null = no actualizar)
 * @param string|null $brocheBotónObs - Nuevas observaciones de broche (null = no actualizar)
 * @param bool|null $tieneBolsillos - Nuevo estado de bolsillos (null = no actualizar)
 * @param string|null $bolsillosObs - Nuevas observaciones de bolsillos (null = no actualizar)
 */
class ActualizarVariantePrendaCommand implements Command
{
    public function __construct(
        private readonly int $pedidoId,
        private readonly int $prendaId,
        private readonly ?int $tipoMangaId = null,
        private readonly ?string $mangaObs = null,
        private readonly ?int $tipoBrocheBotónId = null,
        private readonly ?string $brocheBotónObs = null,
        private readonly ?bool $tieneBolsillos = null,
        private readonly ?string $bolsillosObs = null,
    ) {
        if ($pedidoId <= 0 || $prendaId <= 0) {
            throw new \InvalidArgumentException('pedidoId y prendaId deben ser mayores a 0');
        }
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getPrendaId(): int
    {
        return $this->prendaId;
    }

    public function getTipoMangaId(): ?int
    {
        return $this->tipoMangaId;
    }

    public function getMangaObs(): ?string
    {
        return $this->mangaObs;
    }

    public function getTipoBrocheBotónId(): ?int
    {
        return $this->tipoBrocheBotónId;
    }

    public function getBrocheBotónObs(): ?string
    {
        return $this->brocheBotónObs;
    }

    public function getTieneBolsillos(): ?bool
    {
        return $this->tieneBolsillos;
    }

    public function getBolsillosObs(): ?string
    {
        return $this->bolsillosObs;
    }

    /**
     * Obtener solo los campos que tienen valor (para hacer merge)
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
     * Verificar si hay al menos un campo a actualizar
     */
    public function hayAlgunCampo(): bool
    {
        return !empty($this->getCamposActualizables());
    }
}
