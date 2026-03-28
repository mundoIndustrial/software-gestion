<?php

namespace App\Application\Pedidos\UseCases;

use Illuminate\Http\Request;

/**
 * DTO de entrada para CrearPedidoCompleteUseCase
 * 
 * Encapsula los datos necesarios para crear un pedido completo
 * Decodifica el JSON y normaliza los datos
 */
class CrearPedidoInput
{
    /**
     * @param array $datosFrontend Datos decodificados del JSON "pedido"
     * @param Request $request Request HTTP con archivos
     * @param int $usuarioId ID del usuario autenticado
     */
    public function __construct(
        public readonly array $datosFrontend,
        public readonly Request $request,
        public readonly int $usuarioId
    ) {}

    /**
     * Factory method para crear desde Request
     * 
     * @param Request $request
     * @param int $usuarioId
     * @return self
     * @throws \Exception
     */
    public static function fromRequest(Request $request, int $usuarioId): self
    {
        $pedidoJSON = $request->input('pedido');
        if (!$pedidoJSON) {
            throw new \Exception('Campo "pedido" JSON requerido');
        }

        $datosFrontend = json_decode($pedidoJSON, true);
        if (!$datosFrontend) {
            throw new \Exception('JSON inválido en campo "pedido"');
        }

        return new self($datosFrontend, $request, $usuarioId);
    }

    /**
     * Get cliente name
     */
    public function getClienteNombre(): string
    {
        return trim($this->datosFrontend['cliente'] ?? '');
    }

    /**
     * Get orden de compra
     */
    public function getOrdenCompra(): ?string
    {
        return trim($this->datosFrontend['orden_compra'] ?? '') ?: null;
    }

    /**
     * Has prendas
     */
    public function hasPrendas(): bool
    {
        return !empty($this->datosFrontend['prendas']) && count($this->datosFrontend['prendas']) > 0;
    }

    /**
     * Has EPPs
     */
    public function hasEpps(): bool
    {
        return !empty($this->datosFrontend['epps']) && count($this->datosFrontend['epps']) > 0;
    }

    /**
     * Get prendas
     */
    public function getPrendas(): array
    {
        return $this->datosFrontend['prendas'] ?? [];
    }

    /**
     * Get EPPs
     */
    public function getEpps(): array
    {
        return $this->datosFrontend['epps'] ?? [];
    }

    /**
     * Get borrador pedido ID to convert (if editing an existing borrador)
     */
    public function getBorradorPedidoId(): ?int
    {
        $id = $this->datosFrontend['borrador_pedido_id'] ?? null;
        return ($id && $id > 0) ? (int) $id : null;
    }
}

