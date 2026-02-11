<?php

namespace App\Domain\Bodega\Entities;

use App\Domain\Bodega\ValueObjects\EstadoPedido;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\Events\PedidoEntregado;
use App\Domain\Bodega\Events\PedidoActualizado;
use Carbon\Carbon;

/**
 * Entity Pedido - Representa un pedido en el dominio de bodega
 * Contiene la lógica de negocio y comportamiento del pedido
 */
class Pedido
{
    private int $id;
    private string $numeroPedido;
    private string $cliente;
    private ?string $asesorNombre;
    private EstadoPedido $estado;
    private ?Carbon $fechaPedido;
    private ?Carbon $fechaEstimadaEntrega;
    private ?Carbon $fechaEntregaReal;
    private string $novedades;

    public function __construct(
        int $id,
        string $numeroPedido,
        string $cliente,
        ?string $asesorNombre,
        EstadoPedido $estado,
        ?Carbon $fechaPedido = null,
        ?Carbon $fechaEstimadaEntrega = null,
        ?Carbon $fechaEntregaReal = null,
        string $novedades = ''
    ) {
        $this->id = $id;
        $this->numeroPedido = $numeroPedido;
        $this->cliente = $cliente;
        $this->asesorNombre = $asesorNombre;
        $this->estado = $estado;
        $this->fechaPedido = $fechaPedido ?? Carbon::now();
        $this->fechaEstimadaEntrega = $fechaEstimadaEntrega;
        $this->fechaEntregaReal = $fechaEntregaReal;
        $this->novedades = $novedades;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getNumeroPedido(): string
    {
        return $this->numeroPedido;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getAsesorNombre(): ?string
    {
        return $this->asesorNombre;
    }

    public function getEstado(): EstadoPedido
    {
        return $this->estado;
    }

    public function getFechaPedido(): ?Carbon
    {
        return $this->fechaPedido;
    }

    public function getFechaEstimadaEntrega(): ?Carbon
    {
        return $this->fechaEstimadaEntrega;
    }

    public function getFechaEntregaReal(): ?Carbon
    {
        return $this->fechaEntregaReal;
    }

    public function getNovedades(): string
    {
        return $this->novedades;
    }

    // Comportamiento de dominio

    /**
     * Marcar el pedido como entregado
     * @throws \LogicException si el pedido no puede ser entregado
     */
    public function entregar(): void
    {
        if (!$this->puedeSerEntregado()) {
            throw new \LogicException("El pedido {$this->numeroPedido} no puede ser entregado en su estado actual: {$this->estado->getValor()}");
        }

        $estadoAnterior = $this->estado;
        $this->estado = EstadoPedido::entregado();
        $this->fechaEntregaReal = Carbon::now();

        // Disparar evento de dominio
        DomainEventDispatcher::dispatch(new PedidoEntregado(
            $this->id,
            $this->numeroPedido,
            $estadoAnterior,
            $this->estado,
            $this->fechaEntregaReal
        ));
    }

    /**
     * Actualizar el estado del pedido
     * @throws \LogicException si la transición no es válida
     */
    public function actualizarEstado(EstadoPedido $nuevoEstado): void
    {
        if (!$this->esTransicionValida($nuevoEstado)) {
            throw new \LogicException("Transición inválida de {$this->estado->getValor()} a {$nuevoEstado->getValor()}");
        }

        $estadoAnterior = $this->estado;
        $this->estado = $nuevoEstado;

        // Disparar evento de dominio
        DomainEventDispatcher::dispatch(new PedidoActualizado(
            $this->id,
            $this->numeroPedido,
            $estadoAnterior,
            $this->estado
        ));
    }

    /**
     * Verificar si el pedido puede ser entregado
     */
    public function puedeSerEntregado(): bool
    {
        return $this->estado->estaActivo() && !$this->estado->esEntregado();
    }

    /**
     * Verificar si el pedido está en retraso
     */
    public function estaEnRetraso(): bool
    {
        if (!$this->fechaEstimadaEntrega || $this->estado->esEntregado()) {
            return false;
        }

        return Carbon::now()->greaterThan($this->fechaEstimadaEntrega);
    }

    /**
     * Obtener días de retraso
     */
    public function getDiasRetraso(): int
    {
        if (!$this->estaEnRetraso()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->fechaEstimadaEntrega);
    }

    /**
     * Verificar si una transición de estado es válida
     */
    private function esTransicionValida(EstadoPedido $nuevoEstado): bool
    {
        // Si es el mismo estado, no hay transición
        if ($this->estado->equals($nuevoEstado)) {
            return false;
        }

        // Reglas de transición de estados
        $transicionesPermitidas = [
            'NO INICIADO' => ['EN EJECUCIÓN', 'ANULADA', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS'],
            'PENDIENTE_INSUMOS' => ['NO INICIADO', 'ANULADA'],
            'PENDIENTE_SUPERVISOR' => ['NO INICIADO', 'ANULADA'],
            'EN EJECUCIÓN' => ['ENTREGADO', 'ANULADA'],
            'ENTREGADO' => [], // Estado final
            'ANULADA' => [], // Estado final
            'DEVUELTO_A_ASESORA' => ['NO INICIADO', 'ANULADA']
        ];

        $estadoActual = $this->estado->getValor();
        return in_array($nuevoEstado->getValor(), $transicionesPermitidas[$estadoActual] ?? []);
    }

    /**
     * Actualizar novedades del pedido
     */
    public function actualizarNovedades(string $novedades): void
    {
        $this->novedades = $novedades;
    }

    /**
     * Verificar si el pedido pertenece a un área específica
     */
    public function perteneceAArea(AreaBodega $area): bool
    {
        // Esta lógica podría depender de los detalles del pedido
        // Por ahora, implementación básica
        return true; // Simplificado para el ejemplo
    }

    /**
     * Obtener representación en array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero_pedido' => $this->numeroPedido,
            'cliente' => $this->cliente,
            'asesor_nombre' => $this->asesorNombre,
            'estado' => $this->estado->getValor(),
            'fecha_pedido' => $this->fechaPedido?->format('Y-m-d H:i:s'),
            'fecha_estimada_entrega' => $this->fechaEstimadaEntrega?->format('Y-m-d'),
            'fecha_entrega_real' => $this->fechaEntregaReal?->format('Y-m-d H:i:s'),
            'novedades' => $this->novedades,
            'esta_en_retraso' => $this->estaEnRetraso(),
            'dias_retraso' => $this->getDiasRetraso(),
            'puede_ser_entregado' => $this->puedeSerEntregado(),
        ];
    }

    /**
     * Factory method para crear desde datos crudos
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            $datos['id'],
            $datos['numero_pedido'],
            $datos['cliente'] ?? 'N/A',
            $datos['asesor_nombre'] ?? null,
            EstadoPedido::desdeString($datos['estado'] ?? 'NO INICIADO'),
            isset($datos['fecha_pedido']) ? Carbon::parse($datos['fecha_pedido']) : null,
            isset($datos['fecha_estimada_entrega']) ? Carbon::parse($datos['fecha_estimada_entrega']) : null,
            isset($datos['fecha_entrega_real']) ? Carbon::parse($datos['fecha_entrega_real']) : null,
            $datos['novedades'] ?? ''
        );
    }
}
