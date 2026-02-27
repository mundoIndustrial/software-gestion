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
        ?string $observaciones = null,
        ?int $usuarioId = null,
        ?Carbon $fechaDespacho = null,
    ) {
        $this->pedidoId = $pedidoId;
        $this->tipoItem = $tipoItem;
        $this->itemId = $itemId;
        $this->tallaId = $tallaId;
        $this->genero = $genero;          //  Agregar género
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
        ?string $observaciones = null,
        ?int $usuarioId = null,
    ): self {
        // Validaciones del dominio
        if (!in_array($tipoItem, ['prenda', 'epp'])) {
            throw new \InvalidArgumentException("Tipo de ítem inválido: {$tipoItem}");
        }

        $instancia = new self(
            $pedidoId,
            $tipoItem,
            $itemId,
            $tallaId,
            $genero,                      //  Agregar género
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
            'observaciones' => $this->observaciones,
            'fecha_despacho' => $this->fechaDespacho,
            'usuario_id' => $this->usuarioId,
        ];
    }
}
