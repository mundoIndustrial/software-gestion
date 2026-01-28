<?php

namespace App\Application\Pedidos\DTOs;

use InvalidArgumentException;

/**
 * AnularProduccionPedidoDTO
 * 
 * DTO para anular un pedido de producción
 * Incluye información del usuario para registrar en novedades
 */
class AnularProduccionPedidoDTO
{
    public string $id;
    public string $razon;
    public string $nombreUsuario;
    public string $rolUsuario;

    public function __construct(
        string $id,
        string $razon,
        string $nombreUsuario = 'Sistema',
        string $rolUsuario = 'Sin rol'
    ) {
        $this->id = trim($id);
        $this->razon = trim($razon);
        $this->nombreUsuario = trim($nombreUsuario);
        $this->rolUsuario = trim($rolUsuario);

        $this->validar();
    }

    public static function fromRequest(string $id, array $datos): self
    {
        return new self(
            $id,
            $datos['razon'] ?? '',
            $datos['nombreUsuario'] ?? 'Sistema',
            $datos['rolUsuario'] ?? 'Sin rol'
        );
    }

    /**
     * Construir la novedad en el formato especificado: NOMBRE-ROL-FECHATIME-MOTIVO
     */
    public function construirNovedad(): string
    {
        $fechaActual = now()->format('d/m/Y H:i');
        return "{$this->nombreUsuario}-{$this->rolUsuario}-{$fechaActual}- {$this->razon}";
    }

    private function validar(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException("ID de pedido es requerido");
        }

        if (empty($this->razon)) {
            throw new InvalidArgumentException("Razón de anulación es requerida");
        }

        if (strlen($this->razon) > 500) {
            throw new InvalidArgumentException("Razón no puede exceder 500 caracteres");
        }
    }
}

