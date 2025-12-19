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
        public readonly ?string $cliente = null,
        public readonly ?int $clienteId = null,
        public readonly ?string $descripcion = null,
        public readonly ?string $formaDePago = null,
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

        // Normalizar forma_de_pago: si es array, tomar el primer elemento
        $formaDePago = $data['forma_de_pago'] ?? null;
        
        \Log::info('🔍 [CrearPedidoProduccionDTO] Datos recibidos en fromRequest', [
            'forma_de_pago_raw' => $formaDePago,
            'es_array' => is_array($formaDePago),
            'tipo' => gettype($formaDePago),
        ]);
        
        if (is_array($formaDePago)) {
            $formaDePago = (isset($formaDePago[0]) && !empty($formaDePago[0])) ? $formaDePago[0] : null;
        }

        \Log::info('🔍 [CrearPedidoProduccionDTO] Forma de pago normalizada', [
            'forma_de_pago_final' => $formaDePago,
            'tipo_final' => gettype($formaDePago),
        ]);

        return new self(
            cotizacionId: $data['cotizacion_id'] ?? 0,
            prendasData: $prendasData,
            cliente: $data['cliente'] ?? null,
            clienteId: $data['cliente_id'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            formaDePago: $formaDePago,
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

    /**
     * Determina si es un pedido LOGO (sin prendas, solo logo)
     */
    public function esLogoPedido(): bool
    {
        // Es LOGO si: no hay prendas válidas Y hay logo data
        return count($this->prendasValidas()) === 0 && !empty($this->logo);
    }
}