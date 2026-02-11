<?php

namespace App\Application\Bodega\CQRS\Commands;

/**
 * Command para entregar un pedido
 * Representa la intención de cambiar el estado de un pedido a entregado
 */
class EntregarPedidoCommand implements CommandInterface
{
    private string $commandId;
    private int $pedidoId;
    private ?string $observaciones;
    private ?int $usuarioId;
    private \DateTime $ejecutadoEn;

    public function __construct(
        int $pedidoId,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ) {
        $this->commandId = uniqid('cmd_entregar_', true);
        $this->pedidoId = $pedidoId;
        $this->observaciones = $observaciones;
        $this->usuarioId = $usuarioId ?? auth()->id();
        $this->ejecutadoEn = new \DateTime();
    }

    public function getCommandId(): string
    {
        return $this->commandId;
    }

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function getUsuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function getEjecutadoEn(): \DateTime
    {
        return $this->ejecutadoEn;
    }

    public function toArray(): array
    {
        return [
            'command_id' => $this->commandId,
            'pedido_id' => $this->pedidoId,
            'observaciones' => $this->observaciones,
            'usuario_id' => $this->usuarioId,
            'ejecutado_en' => $this->ejecutadoEn->format('Y-m-d H:i:s'),
            'tipo' => 'entregar_pedido'
        ];
    }

    public function validate(): void
    {
        if ($this->pedidoId <= 0) {
            throw new \InvalidArgumentException('El ID del pedido debe ser un número positivo');
        }

        if (empty($this->usuarioId)) {
            throw new \InvalidArgumentException('Se requiere un usuario para ejecutar esta acción');
        }
    }
}
