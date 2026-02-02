<?php

namespace App\Domain\Pedidos\Agregado;

use App\Domain\Shared\AggregateRoot;
use App\Domain\Pedidos\ValueObjects\NumeroPedido;
use App\Domain\Pedidos\ValueObjects\Estado;
use App\Domain\Pedidos\Entities\PrendaPedido;
use Illuminate\Support\Collection;

/**
 * Agregado RaÃ­z: PedidoAggregate
 * 
 * Encapsula toda la lógica del pedido
 * - Crea pedidos vÃ¡lidos
 * - Gestiona estado
 * - Valida cambios
 * - Dispara eventos
 */
class PedidoAggregate extends AggregateRoot
{
    private NumeroPedido $numero;
    private ?int $clienteId;
    private Estado $estado;
    private string $descripcion;
    private ?string $observaciones;
    private Collection $prendas;
    private \DateTime $fechaCreacion;
    private ?\DateTime $fechaActualizacion;

    private function __construct(
        ?int $id,
        NumeroPedido $numero,
        ?int $clienteId,
        Estado $estado,
        string $descripcion,
        Collection $prendas,
        \DateTime $fechaCreacion,
        ?string $observaciones = null,
        ?\DateTime $fechaActualizacion = null
    ) {
        parent::__construct($id);
        $this->numero = $numero;
        $this->clienteId = $clienteId;
        $this->estado = $estado;
        $this->descripcion = $descripcion;
        $this->prendas = $prendas;
        $this->observaciones = $observaciones;
        $this->fechaCreacion = $fechaCreacion;
        $this->fechaActualizacion = $fechaActualizacion ?? $fechaCreacion;
    }

    public static function crear(
        int $clienteId,
        string $descripcion,
        array $prendasData,
        ?string $observaciones = null
    ): self {
        if ($clienteId <= 0) {
            throw new \InvalidArgumentException('Cliente ID invÃ¡lido');
        }

        if (empty($descripcion)) {
            throw new \InvalidArgumentException('Descripción es requerida');
        }

        if (empty($prendasData)) {
            throw new \InvalidArgumentException('Pedido debe tener al menos una prenda');
        }

        $numeroGenerado = NumeroPedido::generar();
        $estadoInicial = Estado::inicial();
        $prendas = collect();
        
        foreach ($prendasData as $prendaData) {
            $prenda = new PrendaPedido(
                id: null,
                pedidoId: 0,
                prendaId: $prendaData['prenda_id'],
                descripcion: $prendaData['descripcion'],
                cantidad: $prendaData['cantidad'],
                tallas: $prendaData['tallas'],
                observaciones: $prendaData['observaciones'] ?? null
            );
            $prendas->push($prenda);
        }

        return new self(
            id: null,
            numero: $numeroGenerado,
            clienteId: $clienteId,
            estado: $estadoInicial,
            descripcion: $descripcion,
            prendas: $prendas,
            fechaCreacion: new \DateTime(),
            observaciones: $observaciones
        );
    }

    public static function reconstruir(
        int $id,
        NumeroPedido $numero,
        ?int $clienteId,
        Estado $estado,
        string $descripcion,
        array $prendas,
        \DateTime $fechaCreacion,
        ?string $observaciones = null,
        ?\DateTime $fechaActualizacion = null
    ): self {
        return new self(
            id: $id,
            numero: $numero,
            clienteId: $clienteId,
            estado: $estado,
            descripcion: $descripcion,
            prendas: collect($prendas),
            fechaCreacion: $fechaCreacion,
            observaciones: $observaciones,
            fechaActualizacion: $fechaActualizacion
        );
    }

    public function confirmar(): void
    {
        if ($this->estado->esFinal()) {
            throw new \DomainException('No se puede confirmar un pedido en estado final');
        }

        $nuevoEstado = Estado::desde(Estado::CONFIRMADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    public function iniciarProduccion(): void
    {
        $nuevoEstado = Estado::desde(Estado::EN_PRODUCCION);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    public function completar(): void
    {
        $nuevoEstado = Estado::desde(Estado::COMPLETADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    public function cancelar(): void
    {
        $nuevoEstado = Estado::desde(Estado::CANCELADO);
        $this->estado->transicionarA($nuevoEstado);
        $this->estado = $nuevoEstado;
        $this->fechaActualizacion = new \DateTime();
    }

    /**
     * Anular un pedido (solo cambia estado a CANCELADO)
     * La razón se agrega mediante agregarNovedad() en el Use Case
     */
    public function anular(string $razon = ''): void
    {
        $this->cancelar();
    }

    public function actualizarDescripcion(string $nuevaDescripcion): void
    {
        if ($this->estado->esFinal()) {
            throw new \DomainException('No se puede editar pedido en estado final');
        }

        $this->descripcion = $nuevaDescripcion;
        $this->fechaActualizacion = new \DateTime();
    }

    public function agregarObservaciones(string $observaciones): void
    {
        $this->observaciones = $observaciones;
        $this->fechaActualizacion = new \DateTime();
    }

    /**
     * Agregar una observación/novedad al registro existente
     * Concatena con los registros previos usando saltos de línea
     */
    public function agregarNovedad(string $novedad): void
    {
        if (!empty($this->observaciones)) {
            $this->observaciones = $this->observaciones . "\n\n" . $novedad;
        } else {
            $this->observaciones = $novedad;
        }
        $this->fechaActualizacion = new \DateTime();
    }

    // Getters
    public function id(): ?int { return $this->id; }
    public function numero(): NumeroPedido { return $this->numero; }
    public function clienteId(): ?int { return $this->clienteId; }
    public function estado(): Estado { return $this->estado; }
    public function descripcion(): string { return $this->descripcion; }
    public function observaciones(): ?string { return $this->observaciones; }
    public function prendas(): Collection { return $this->prendas; }
    public function fechaCreacion(): \DateTime { return $this->fechaCreacion; }
    public function fechaActualizacion(): \DateTime { return $this->fechaActualizacion; }

    public function totalPrendas(): int
    {
        return $this->prendas->count();
    }

    public function totalArticulos(): int
    {
        return $this->prendas->sum(fn(PrendaPedido $p) => $p->cantidad());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => (string)$this->numero,
            'cliente_id' => $this->clienteId,
            'estado' => $this->estado->valor(),
            'descripcion' => $this->descripcion,
            'observaciones' => $this->observaciones,
            'fecha_creacion' => $this->fechaCreacion->format('Y-m-d H:i:s'),
            'fecha_actualizacion' => $this->fechaActualizacion->format('Y-m-d H:i:s'),
        ];
    }

    public function prendasArray(): array
    {
        return $this->prendas->map(fn(PrendaPedido $p) => $p->toArray())->toArray();
    }
}

