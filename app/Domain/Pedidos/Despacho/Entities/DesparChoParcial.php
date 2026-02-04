<?php

namespace App\Domain\Pedidos\Despacho\Entities;

use Carbon\Carbon;

/**
 * DesparChoParcial (Entidad del Domain)
 * 
 * Representa un registro de despacho parcial de un ítem (prenda o EPP)
 * en el dominio del negocio.
 * 
 * Responsabilidades:
 * - Mantener la lógica de parciales
 * - Calcular totales despachados
 * - Validar transiciones de estado
 */
class DesparChoParcial
{
    private int $id;
    private int $pedidoId;
    private string $tipoItem;          // 'prenda' | 'epp'
    private int $itemId;
    private ?int $tallaId;             // ID de talla (nullable para EPP)
    private ?string $genero;           // Género (DAMA, CABALLERO, UNISEX)
    private int $pendienteInicial;
    private int $parcial1;
    private int $pendiente1;
    private int $parcial2;
    private int $pendiente2;
    private int $parcial3;
    private int $pendiente3;
    private ?string $observaciones;
    private Carbon $fechaDespacho;
    private ?int $usuarioId;
    private Carbon $createdAt;
    private ?Carbon $updatedAt;
    private ?Carbon $deletedAt;

    /**
     * Constructor privado (usar factory methods)
     */
    private function __construct(
        int $pedidoId,
        string $tipoItem,
        int $itemId,
        ?int $tallaId = null,
        ?string $genero = null,          //  Agregar género
        int $pendienteInicial = 0,
        int $parcial1 = 0,
        int $pendiente1 = 0,
        int $parcial2 = 0,
        int $pendiente2 = 0,
        int $parcial3 = 0,
        int $pendiente3 = 0,
        ?string $observaciones = null,
        ?int $usuarioId = null,
        ?Carbon $fechaDespacho = null,
    ) {
        $this->pedidoId = $pedidoId;
        $this->tipoItem = $tipoItem;
        $this->itemId = $itemId;
        $this->tallaId = $tallaId;
        $this->genero = $genero;          //  Agregar género
        $this->pendienteInicial = $pendienteInicial;
        $this->parcial1 = $parcial1;
        $this->pendiente1 = $pendiente1;
        $this->parcial2 = $parcial2;
        $this->pendiente2 = $pendiente2;
        $this->parcial3 = $parcial3;
        $this->pendiente3 = $pendiente3;
        $this->observaciones = $observaciones;
        $this->usuarioId = $usuarioId;
        $this->fechaDespacho = $fechaDespacho ?? now();
        $this->createdAt = now();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Factory method: Crear nuevo despacho parcial
     */
    public static function crear(
        int $pedidoId,
        string $tipoItem,
        int $itemId,
        ?int $tallaId = null,
        ?string $genero = null,          //  Agregar género
        int $pendienteInicial = 0,
        int $parcial1 = 0,
        int $pendiente1 = 0,
        int $parcial2 = 0,
        int $pendiente2 = 0,
        int $parcial3 = 0,
        int $pendiente3 = 0,
        ?string $observaciones = null,
        ?int $usuarioId = null,
    ): self {
        // Validaciones del dominio
        if (!in_array($tipoItem, ['prenda', 'epp'])) {
            throw new \InvalidArgumentException("Tipo de ítem inválido: {$tipoItem}");
        }

        if ($pendienteInicial < 0 || $parcial1 < 0 || $parcial2 < 0 || $parcial3 < 0) {
            throw new \InvalidArgumentException("Los valores no pueden ser negativos");
        }

        if ($pendiente1 < 0 || $pendiente2 < 0 || $pendiente3 < 0) {
            throw new \InvalidArgumentException("Los pendientes no pueden ser negativos");
        }

        $instancia = new self(
            $pedidoId,
            $tipoItem,
            $itemId,
            $tallaId,
            $genero,                      //  Agregar género
            $pendienteInicial,
            $parcial1,
            $pendiente1,
            $parcial2,
            $pendiente2,
            $parcial3,
            $pendiente3,
            $observaciones,
            $usuarioId,
        );

        return $instancia;
    }

    /**
     * Factory method: Reconstruir desde BD
     */
    public static function reconstruir(
        int $id,
        int $pedidoId,
        string $tipoItem,
        int $itemId,
        ?int $tallaId = null,
        ?string $genero = null,          //  Agregar género
        int $pendienteInicial = 0,
        int $parcial1 = 0,
        int $pendiente1 = 0,
        int $parcial2 = 0,
        int $pendiente2 = 0,
        int $parcial3 = 0,
        int $pendiente3 = 0,
        ?string $observaciones = null,
        ?int $usuarioId = null,
        Carbon $fechaDespacho = null,
        Carbon $createdAt = null,
        ?Carbon $updatedAt = null,
        ?Carbon $deletedAt = null,
    ): self {
        $instancia = new self(
            $pedidoId,
            $tipoItem,
            $itemId,
            $tallaId,
            $genero,                      //  Agregar género
            $pendienteInicial,
            $parcial1,
            $pendiente1,
            $parcial2,
            $pendiente2,
            $parcial3,
            $pendiente3,
            $observaciones,
            $usuarioId,
            $fechaDespacho,
        );

        $instancia->id = $id;
        $instancia->createdAt = $createdAt;
        $instancia->updatedAt = $updatedAt;
        $instancia->deletedAt = $deletedAt;

        return $instancia;
    }

    /**
     * Agregar cantidad a un parcial específico
     */
    public function agregarAlParcial(int $numeroParcial, int $cantidad): void
    {
        if ($numeroParcial < 1 || $numeroParcial > 3) {
            throw new \InvalidArgumentException("El número de parcial debe ser 1, 2 o 3");
        }

        if ($cantidad < 0) {
            throw new \InvalidArgumentException("La cantidad no puede ser negativa");
        }

        match ($numeroParcial) {
            1 => $this->parcial1 += $cantidad,
            2 => $this->parcial2 += $cantidad,
            3 => $this->parcial3 += $cantidad,
        };

        $this->updatedAt = now();
    }

    /**
     * Obtener total despachado
     */
    public function obtenerTotalDespachado(): int
    {
        return $this->parcial1 + $this->parcial2 + $this->parcial3;
    }

    /**
     * Verificar si está completamente despachado
     */
    public function estaCompletamenteDispachado(int $cantidadTotal): bool
    {
        return $this->obtenerTotalDespachado() >= $cantidadTotal;
    }

    /**
     * Actualizar observaciones
     */
    public function actualizarObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
        $this->updatedAt = now();
    }

    // ============ GETTERS ============

    public function id(): int
    {
        return $this->id;
    }

    public function pedidoId(): int
    {
        return $this->pedidoId;
    }

    public function tipoItem(): string
    {
        return $this->tipoItem;
    }

    public function itemId(): int
    {
        return $this->itemId;
    }

    public function tallaId(): ?int
    {
        return $this->tallaId;
    }

    public function genero(): ?string
    {
        return $this->genero;
    }

    public function pendienteInicial(): int
    {
        return $this->pendienteInicial;
    }

    public function parcial1(): int
    {
        return $this->parcial1;
    }

    public function pendiente1(): int
    {
        return $this->pendiente1;
    }

    public function parcial2(): int
    {
        return $this->parcial2;
    }

    public function pendiente2(): int
    {
        return $this->pendiente2;
    }

    public function parcial3(): int
    {
        return $this->parcial3;
    }

    public function pendiente3(): int
    {
        return $this->pendiente3;
    }

    public function observaciones(): ?string
    {
        return $this->observaciones;
    }

    public function fechaDespacho(): Carbon
    {
        return $this->fechaDespacho;
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function createdAt(): Carbon
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?Carbon
    {
        return $this->deletedAt;
    }

    public function convertirAArray(): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedidoId,
            'tipo_item' => $this->tipoItem,
            'item_id' => $this->itemId,
            'talla_id' => $this->tallaId,
            'pendiente_inicial' => $this->pendienteInicial,
            'parcial_1' => $this->parcial1,
            'pendiente_1' => $this->pendiente1,
            'parcial_2' => $this->parcial2,
            'pendiente_2' => $this->pendiente2,
            'parcial_3' => $this->parcial3,
            'pendiente_3' => $this->pendiente3,
            'observaciones' => $this->observaciones,
            'fecha_despacho' => $this->fechaDespacho,
            'usuario_id' => $this->usuarioId,
            'total_despachado' => $this->obtenerTotalDespachado(),
        ];
    }
}
