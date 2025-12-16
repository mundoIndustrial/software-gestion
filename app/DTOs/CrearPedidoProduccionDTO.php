<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * DTO para solicitud de creación de pedido de producción
 * Encapsula validación de entrada
 */
class CrearPedidoProduccionDTO
{
    public function __construct(
        public readonly int $cotizacionId,
        public readonly array $prendasData, // Array de PrendaCreacionDTO
        public readonly ?string $formaDePago = null, // Forma de pago (opcional)
        public readonly ?array $logo = null, // Logo del pedido (opcional)
    ) {}

    /**
     * Factory method con validación
     */
    public static function fromRequest(array $data): self
    {
        $prendasData = [];
        
        foreach ($data['prendas'] ?? [] as $prenda) {
            $prendasData[] = PrendaCreacionDTO::fromArray(
                $prenda['index'] ?? 0,
                $prenda
            );
        }

        return new self(
            cotizacionId: $data['cotizacion_id'] ?? 0,
            prendasData: $prendasData,
            formaDePago: $data['forma_de_pago'] ?? null,
            logo: $data['logo'] ?? null,
        );
    }

    /**
     * Valida que los datos sean válidos
     */
    public function esValido(): bool
    {
        return $this->cotizacionId > 0 && count($this->prendasData) > 0;
    }

    /**
     * Obtiene solo las prendas válidas (con cantidades)
     */
    public function prendasValidas(): array
    {
        return array_filter(
            $this->prendasData,
            fn(PrendaCreacionDTO $prenda) => $prenda->esValido()
        );
    }

    /**
     * Cuenta total de prendas
     */
    public function totalPrendas(): int
    {
        return count($this->prendasValidas());
    }
}
