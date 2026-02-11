<?php

namespace App\Application\Bodega\CQRS\Commands;

use App\Domain\Bodega\ValueObjects\EstadoPedido;

/**
 * Command para actualizar el estado de un pedido
 * Representa la intención de cambiar el estado de un pedido
 */
class ActualizarEstadoPedidoCommand implements CommandInterface
{
    private string $commandId;
    private int $pedidoId;
    private EstadoPedido $nuevoEstado;
    private ?string $motivo;
    private ?int $usuarioId;
    private \DateTime $ejecutadoEn;

    public function __construct(
        int $pedidoId,
        EstadoPedido $nuevoEstado,
        ?string $motivo = null,
        ?int $usuarioId = null
    ) {
        $this->commandId = uniqid('cmd_actualizar_estado_', true);
        $this->pedidoId = $pedidoId;
        $this->nuevoEstado = $nuevoEstado;
        $this->motivo = $motivo;
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

    public function getNuevoEstado(): EstadoPedido
    {
        return $this->nuevoEstado;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
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
            'nuevo_estado' => $this->nuevoEstado->getValor(),
            'motivo' => $this->motivo,
            'usuario_id' => $this->usuarioId,
            'ejecutado_en' => $this->ejecutadoEn->format('Y-m-d H:i:s'),
            'tipo' => 'actualizar_estado_pedido'
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

        // Validar que el estado no sea nulo
        if (!$this->nuevoEstado) {
            throw new \InvalidArgumentException('El nuevo estado es requerido');
        }
    }
}
