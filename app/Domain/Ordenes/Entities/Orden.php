<?php

namespace App\Domain\Ordenes\Entities;

use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;
use App\Domain\Ordenes\ValueObjects\FormaPago;
use App\Domain\Ordenes\ValueObjects\Area;
use App\Domain\Ordenes\Events\OrdenCreada;
use App\Domain\Ordenes\Events\PrendaAgregada;
use App\Domain\Ordenes\Events\OrdenActualizada;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregate Root: Orden
 * 
 * Responsable de:
 * - Mantener invariantes de la orden
 * - Gestionar colección de prendas
 * - Emitir eventos de dominio
 * - Validar cambios de estado
 */
class Orden
{
    private NumeroOrden $numeroOrden;
    private EstadoOrden $estado;
    private string $cliente;
    private FormaPago $formaPago;
    private Area $area;
    private Carbon $fechaCreacion;
    private Carbon $fechaUltimaModificacion;
    private Collection $prendas;
    private Collection $eventos;

    private int $totalCantidad = 0;
    private float $totalEntregado = 0;

    private function __construct(
        NumeroOrden $numeroOrden,
        string $cliente,
        FormaPago $formaPago,
        Area $area
    ) {
        $this->numeroOrden = $numeroOrden;
        $this->cliente = $cliente;
        $this->formaPago = $formaPago;
        $this->area = $area;
        $this->estado = EstadoOrden::borrador();
        $this->fechaCreacion = Carbon::now();
        $this->fechaUltimaModificacion = Carbon::now();
        $this->prendas = collect();
        $this->eventos = collect();
    }

    /**
     * Crear nueva orden (Factory Method)
     */
    public static function crear(
        NumeroOrden $numeroOrden,
        string $cliente,
        FormaPago $formaPago,
        Area $area
    ): self {
        $orden = new self($numeroOrden, $cliente, $formaPago, $area);
        $orden->eventos->push(new OrdenCreada($orden));
        return $orden;
    }

    /**
     * Agregar prenda a la orden
     */
    public function agregarPrenda(Prenda $prenda): void
    {
        // Validar invariante: no duplicados
        if ($this->prendas->contains(fn($p) => $p->getNombrePrenda() === $prenda->getNombrePrenda())) {
            throw new \DomainException('La prenda ya existe en la orden');
        }

        $this->prendas->push($prenda);
        $this->recalcularTotales();
        $this->eventos->push(new PrendaAgregada($this, $prenda));
    }

    /**
     * Cambiar estado de la orden
     */
    public function cambiarEstado(EstadoOrden $nuevoEstado): void
    {
        if (!$this->puedeTransicionarA($nuevoEstado)) {
            throw new \DomainException(
                "No se puede pasar de {$this->estado->toString()} a {$nuevoEstado->toString()}"
            );
        }

        $estadoAnterior = $this->estado;
        $this->estado = $nuevoEstado;
        $this->fechaUltimaModificacion = Carbon::now();

        $this->eventos->push(new OrdenActualizada($this, $estadoAnterior, $nuevoEstado));
    }

    /**
     * Validar transición de estado permitida
     */
    private function puedeTransicionarA(EstadoOrden $nuevoEstado): bool
    {
        // Máquina de estados: Borrador -> Aprobada -> EnProduccion -> Completada
        $transiciones = [
            'Borrador' => ['Aprobada'],
            'Aprobada' => ['EnProduccion', 'Cancelada'],
            'EnProduccion' => ['Completada', 'Cancelada'],
            'Completada' => [],
            'Cancelada' => [],
        ];

        $transicionesPermitidas = $transiciones[$this->estado->toString()] ?? [];
        return in_array($nuevoEstado->toString(), $transicionesPermitidas);
    }

    /**
     * Recalcular totales de la orden
     */
    private function recalcularTotales(): void
    {
        $this->totalCantidad = $this->prendas->sum(fn($p) => $p->getCantidadTotal());
        $this->totalEntregado = $this->prendas->sum(fn($p) => $p->getCantidadEntregada());
    }

    /**
     * Aprobar orden (solo desde Borrador)
     */
    public function aprobar(): void
    {
        if (!$this->estado->esBorrador()) {
            throw new \DomainException('Solo se pueden aprobar órdenes en borrador');
        }

        if ($this->prendas->isEmpty()) {
            throw new \DomainException('La orden debe tener al menos una prenda');
        }

        $this->cambiarEstado(EstadoOrden::aprobada());
    }

    /**
     * Iniciar producción
     */
    public function iniciarProduccion(): void
    {
        if (!$this->estado->esAprobada()) {
            throw new \DomainException('Solo se pueden iniciar órdenes aprobadas');
        }

        $this->cambiarEstado(EstadoOrden::enProduccion());
    }

    /**
     * Completar orden
     */
    public function completar(): void
    {
        if (!$this->estado->esEnProduccion()) {
            throw new \DomainException('Solo se pueden completar órdenes en producción');
        }

        if ($this->totalEntregado < $this->totalCantidad) {
            throw new \DomainException('Todas las prendas deben estar entregadas');
        }

        $this->cambiarEstado(EstadoOrden::completada());
    }

    /**
     * Cancelar orden
     */
    public function cancelar(): void
    {
        if ($this->estado->esCompletada() || $this->estado->esCancelada()) {
            throw new \DomainException('No se puede cancelar una orden completada o cancelada');
        }

        $this->cambiarEstado(EstadoOrden::cancelada());
    }

    // ===== GETTERS (Acceso de solo lectura) =====

    public function getNumeroPedido(): NumeroOrden
    {
        return $this->numeroOrden;
    }

    public function getEstado(): EstadoOrden
    {
        return $this->estado;
    }

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getFormaPago(): FormaPago
    {
        return $this->formaPago;
    }

    public function getArea(): Area
    {
        return $this->area;
    }

    public function getFechaCreacion(): Carbon
    {
        return $this->fechaCreacion;
    }

    public function getFechaUltimaModificacion(): Carbon
    {
        return $this->fechaUltimaModificacion;
    }

    public function getPrendas(): Collection
    {
        return $this->prendas->clone();
    }

    public function getTotalCantidad(): int
    {
        return $this->totalCantidad;
    }

    public function getTotalEntregado(): float
    {
        return $this->totalEntregado;
    }

    public function getTotalPendiente(): float
    {
        return $this->totalCantidad - $this->totalEntregado;
    }

    public function getPorcentajeCompletado(): float
    {
        if ($this->totalCantidad === 0) {
            return 0;
        }
        return round(($this->totalEntregado / $this->totalCantidad) * 100, 2);
    }

    public function estaCompleta(): bool
    {
        return $this->estado->esCompletada();
    }

    public function estaEnProduccion(): bool
    {
        return $this->estado->esEnProduccion();
    }

    /**
     * Obtener eventos de dominio generados
     */
    public function getEventos(): Collection
    {
        return $this->eventos->clone();
    }

    /**
     * Limpiar eventos después de persistirlos
     */
    public function clearEventos(): void
    {
        $this->eventos = collect();
    }
}
