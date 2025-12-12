<?php

namespace App\Domain\Operario\Entities;

use App\Domain\Operario\ValueObjects\TipoOperario;
use App\Domain\Operario\ValueObjects\AreaOperario;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregate Root: Operario
 * 
 * Responsable de:
 * - Mantener invariantes del operario (cortador/costurero)
 * - Gestionar asignaciones de pedidos
 * - Validar cambios de estado
 * - Emitir eventos de dominio
 */
class Operario
{
    private int $id;
    private string $nombre;
    private string $email;
    private TipoOperario $tipo;
    private AreaOperario $area;
    private bool $activo;
    private Carbon $fechaCreacion;
    private Collection $pedidosAsignados;
    private Collection $eventos;

    private function __construct(
        int $id,
        string $nombre,
        string $email,
        TipoOperario $tipo,
        AreaOperario $area
    ) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->tipo = $tipo;
        $this->area = $area;
        $this->activo = true;
        $this->fechaCreacion = Carbon::now();
        $this->pedidosAsignados = collect();
        $this->eventos = collect();
    }

    /**
     * Crear nuevo operario (Factory Method)
     */
    public static function crear(
        int $id,
        string $nombre,
        string $email,
        TipoOperario $tipo,
        AreaOperario $area
    ): self {
        return new self($id, $nombre, $email, $tipo, $area);
    }

    /**
     * Reconstruir operario desde persistencia
     */
    public static function reconstruir(
        int $id,
        string $nombre,
        string $email,
        string $tipo,
        string $area,
        bool $activo,
        Carbon $fechaCreacion
    ): self {
        $operario = new self(
            $id,
            $nombre,
            $email,
            TipoOperario::from($tipo),
            AreaOperario::from($area)
        );
        $operario->activo = $activo;
        $operario->fechaCreacion = $fechaCreacion;
        return $operario;
    }

    /**
     * Asignar pedido al operario
     */
    public function asignarPedido(int $numeroPedido, string $cliente, int $cantidad): void
    {
        if (!$this->activo) {
            throw new \DomainException('No se puede asignar pedidos a un operario inactivo');
        }

        $this->pedidosAsignados->push([
            'numero_pedido' => $numeroPedido,
            'cliente' => $cliente,
            'cantidad' => $cantidad,
            'fecha_asignacion' => Carbon::now(),
        ]);
    }

    /**
     * Desactivar operario
     */
    public function desactivar(): void
    {
        $this->activo = false;
    }

    /**
     * Activar operario
     */
    public function activar(): void
    {
        $this->activo = true;
    }

    // ===== GETTERS =====

    public function getId(): int
    {
        return $this->id;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTipo(): TipoOperario
    {
        return $this->tipo;
    }

    public function getArea(): AreaOperario
    {
        return $this->area;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function getFechaCreacion(): Carbon
    {
        return $this->fechaCreacion;
    }

    public function getPedidosAsignados(): Collection
    {
        return $this->pedidosAsignados->clone();
    }

    public function getTotalPedidosAsignados(): int
    {
        return $this->pedidosAsignados->count();
    }

    public function getEventos(): Collection
    {
        return $this->eventos->clone();
    }

    public function clearEventos(): void
    {
        $this->eventos = collect();
    }
}
