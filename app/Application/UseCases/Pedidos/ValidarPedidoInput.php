<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Http\Request;

/**
 * DTO: Input para Validar Pedido
 * 
 * FASE 2 - Extrae y normaliza datos para validación
 * Responsabilidades:
 * - Decodificar JSON del campo "pedido"
 * - Normalizar estructura
 * - Extraer valores necesarios para validación
 * 
 * @package App\Application\UseCases\Pedidos
 */
class ValidarPedidoInput
{
    public function __construct(
        public readonly string $pedidoJSON,
        public readonly array $datosFrontend,
        public readonly int $userId,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     * 
     * Decodifica JSON del campo "pedido" y crea el DTO
     * 
     * @param Request $request
     * @param int $userId
     * @return self
     * @throws \Exception Si JSON es inválido
     */
    public static function fromRequest(Request $request, int $userId): self
    {
        $pedidoJSON = $request->input('pedido');
        
        if (!$pedidoJSON) {
            throw new \Exception('Campo "pedido" JSON requerido');
        }

        $datosFrontend = json_decode($pedidoJSON, true);
        
        if ($datosFrontend === null) {
            throw new \Exception('JSON inválido en campo "pedido"');
        }

        return new self(
            pedidoJSON: $pedidoJSON,
            datosFrontend: $datosFrontend,
            userId: $userId,
        );
    }

    /**
     * Helper: Obtener nombre del cliente
     * 
     * @return string|null
     */
    public function getClienteNombre(): ?string
    {
        return trim($this->datosFrontend['cliente'] ?? '');
    }

    /**
     * Helper: Verificar si hay prendas
     * 
     * @return bool
     */
    public function hasPrendas(): bool
    {
        return !empty($this->datosFrontend['prendas']) 
            && is_array($this->datosFrontend['prendas']) 
            && count($this->datosFrontend['prendas']) > 0;
    }

    /**
     * Helper: Verificar si hay EPPs
     * 
     * @return bool
     */
    public function hasEpps(): bool
    {
        return !empty($this->datosFrontend['epps']) 
            && is_array($this->datosFrontend['epps']) 
            && count($this->datosFrontend['epps']) > 0;
    }

    /**
     * Helper: Verificar si hay items legacy
     * 
     * @return bool
     */
    public function hasItemsLegacy(): bool
    {
        return !empty($this->datosFrontend['items']) 
            && is_array($this->datosFrontend['items']) 
            && count($this->datosFrontend['items']) > 0;
    }

    /**
     * Helper: Verificar si hay al menos un tipo de item
     * 
     * @return bool
     */
    public function hasSomeItems(): bool
    {
        return $this->hasPrendas() || $this->hasEpps() || $this->hasItemsLegacy();
    }

    /**
     * Helper: Obtener conteo de items por tipo
     * 
     * @return array
     */
    public function getItemCounts(): array
    {
        return [
            'prendas' => $this->hasPrendas() ? count($this->datosFrontend['prendas']) : 0,
            'epps' => $this->hasEpps() ? count($this->datosFrontend['epps']) : 0,
            'items_legacy' => $this->hasItemsLegacy() ? count($this->datosFrontend['items']) : 0,
        ];
    }
}
