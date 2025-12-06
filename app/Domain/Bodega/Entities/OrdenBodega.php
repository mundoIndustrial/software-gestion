<?php

namespace App\Domain\Bodega\Entities;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\ValueObjects\FormaPagoBodega;
use App\Domain\Bodega\Events\OrdenBodegaCreada;
use App\Domain\Bodega\Events\PrendaBodegaAgregada;
use App\Domain\Bodega\Events\OrdenBodegaActualizada;
use Carbon\Carbon;

/**
 * Aggregate Root: OrdenBodega
 * 
 * Representa una orden en el sistema de bodega con toda su información
 * incluyendo prendas, estado, fechas y procesos.
 */
final class OrdenBodega
{
    private NumeroPedidoBodega $numeroPedido;
    private EstadoBodega $estado;
    private string $cliente;
    private AreaBodega $area;
    private Carbon $fechaCreacion;
    private ?string $encargado;
    private ?FormaPagoBodega $formaPago;
    private string $descripcion;
    private array $prendas = [];
    private int $cantidadTotal = 0;
    private array $eventos = [];
    private array $procesosRegistrados = [];

    private function __construct(
        NumeroPedidoBodega $numeroPedido,
        string $cliente,
        Carbon $fechaCreacion
    ) {
        $this->numeroPedido = $numeroPedido;
        $this->cliente = $cliente;
        $this->fechaCreacion = $fechaCreacion;
        $this->estado = EstadoBodega::noIniciado();
        $this->area = AreaBodega::creacionOrden();
        $this->descripcion = '';
    }

    public static function crear(
        NumeroPedidoBodega $numeroPedido,
        string $cliente,
        Carbon $fechaCreacion
    ): self {
        $orden = new self($numeroPedido, $cliente, $fechaCreacion);
        $orden->registrarEvento(new OrdenBodegaCreada($numeroPedido, $cliente));
        return $orden;
    }

    public function agregarPrenda(PrendaBodega $prenda): void
    {
        $this->prendas[] = $prenda;
        $this->cantidadTotal += $prenda->cantidadTotal();
        $this->registrarEvento(new PrendaBodegaAgregada($this->numeroPedido, $prenda));
    }

    public function cambiarEstado(EstadoBodega $nuevoEstado): void
    {
        if (!$this->estado->puedeTransicionarA($nuevoEstado)) {
            throw new \InvalidArgumentException(
                "No se puede transicionar de {$this->estado} a {$nuevoEstado}"
            );
        }

        $estadoAnterior = $this->estado;
        $this->estado = $nuevoEstado;
        $this->registrarEvento(new OrdenBodegaActualizada($this->numeroPedido, $estadoAnterior, $nuevoEstado));
    }

    public function cambiarArea(AreaBodega $nuevaArea): void
    {
        if (!$this->area->valor() === $nuevaArea->valor()) {
            $this->area = $nuevaArea;
            $this->registrarEvento(new OrdenBodegaActualizada($this->numeroPedido, $this->estado, $this->estado));
        }
    }

    public function establecerEncargado(?string $encargado): void
    {
        $this->encargado = !empty($encargado) ? trim($encargado) : null;
    }

    public function establecerFormaPago(?FormaPagoBodega $formaPago): void
    {
        $this->formaPago = $formaPago;
    }

    public function actualizarDescripcion(string $descripcion): void
    {
        $this->descripcion = trim($descripcion);
    }

    public function registrarProceso(string $proceso, Carbon $fecha): void
    {
        $this->procesosRegistrados[$proceso] = [
            'fecha' => $fecha,
            'timestamp' => $fecha->timestamp
        ];
    }

    public function puedeSerCancelada(): bool
    {
        return !$this->estado->esAnulada();
    }

    public function cancelar(): void
    {
        if (!$this->puedeSerCancelada()) {
            throw new \InvalidArgumentException('Esta orden ya está anulada');
        }

        $this->cambiarEstado(EstadoBodega::anulada());
    }

    // Getters
    public function numeroPedido(): NumeroPedidoBodega
    {
        return $this->numeroPedido;
    }

    public function estado(): EstadoBodega
    {
        return $this->estado;
    }

    public function cliente(): string
    {
        return $this->cliente;
    }

    public function area(): AreaBodega
    {
        return $this->area;
    }

    public function fechaCreacion(): Carbon
    {
        return $this->fechaCreacion;
    }

    public function encargado(): ?string
    {
        return $this->encargado;
    }

    public function formaPago(): ?FormaPagoBodega
    {
        return $this->formaPago;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    public function prendas(): array
    {
        return $this->prendas;
    }

    public function cantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function procesosRegistrados(): array
    {
        return $this->procesosRegistrados;
    }

    public function obtenerEventos(): array
    {
        return $this->eventos;
    }

    public function limpiarEventos(): void
    {
        $this->eventos = [];
    }

    private function registrarEvento(object $evento): void
    {
        $this->eventos[] = $evento;
    }

    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numeroPedido->valor(),
            'estado' => $this->estado->valor(),
            'cliente' => $this->cliente,
            'area' => $this->area->valor(),
            'fecha_creacion' => $this->fechaCreacion->toDateString(),
            'encargado' => $this->encargado,
            'forma_pago' => $this->formaPago?->valor(),
            'descripcion' => $this->descripcion,
            'prendas' => array_map(fn($p) => $p->toArray(), $this->prendas),
            'cantidad_total' => $this->cantidadTotal,
            'procesos' => $this->procesosRegistrados,
        ];
    }
}
