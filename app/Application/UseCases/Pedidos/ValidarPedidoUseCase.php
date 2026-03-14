<?php

namespace App\Application\UseCases\Pedidos;

use Illuminate\Support\Facades\Log;
use App\Domain\Clientes\Services\ClienteService;

/**
 * UseCase: Validar Pedido
 * 
 * FASE 2 - Valida pedido antes de crear
 * Responsabilidades:
 * - Validar estructura JSON recibida
 * - Validar cliente (obligatorio)
 * - Validar que hay al menos prendas, épps o items
 * - Obtener o crear cliente
 * - Retornar estado de validación
 * 
 * Nota: Este UseCase se ejecuta ANTES de CrearPedidoCompleteUseCase
 * Permite al frontend validar datos antes de commit
 * 
 * @package App\Application\UseCases\Pedidos
 */
class ValidarPedidoUseCase
{
    public function __construct(
        private ClienteService $clienteService,
    ) {}

    /**
     * Ejecutar validación del pedido
     * 
     * FLUJO:
     * 1. Validar estructura JSON
     * 2. Validar cliente (requerido)
     * 3. Validar que hay items (prendas, épps o items legacy)
     * 4. Obtener/crear cliente
     * 5. Retornar resultado con cliente_id
     * 
     * @param ValidarPedidoInput $input
     * @return ValidarPedidoOutput
     */
    public function ejecutar(ValidarPedidoInput $input): ValidarPedidoOutput
    {
        try {
            Log::info('[ValidarPedidoUseCase] Iniciando validación', [
                'user_id' => $input->userId,
                'cliente_raw' => $input->getClienteNombre(),
                'tiene_prendas' => $input->hasPrendas(),
                'tiene_epps' => $input->hasEpps(),
                'tiene_items_legacy' => $input->hasItemsLegacy(),
            ]);

            // ====== PASO 1: Validar cliente ======
            $validarClienteResult = $this->validarCliente($input);
            if (!$validarClienteResult['success']) {
                Log::warning('[ValidarPedidoUseCase] Validación de cliente fallida', [
                    'errores' => $validarClienteResult['errores'],
                ]);

                return ValidarPedidoOutput::failure(
                    $validarClienteResult['errores'],
                    'Validación de cliente fallida'
                );
            }

            // ====== PASO 2: Validar items ======
            $validarItemsResult = $this->validarItems($input);
            if (!$validarItemsResult['success']) {
                Log::warning('[ValidarPedidoUseCase] Validación de items fallida', [
                    'errores' => $validarItemsResult['errores'],
                ]);

                return ValidarPedidoOutput::failure(
                    $validarItemsResult['errores'],
                    'Validación de items fallida'
                );
            }

            // ====== PASO 3: Obtener/crear cliente ======
            $clienteNombre = $input->getClienteNombre();
            $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

            Log::info('[ValidarPedidoUseCase] Validación exitosa', [
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombre,
                'items_counts' => $input->getItemCounts(),
            ]);

            return ValidarPedidoOutput::success(
                $cliente->id,
                'Pedido válido'
            );

        } catch (\Exception $e) {
            Log::error('[ValidarPedidoUseCase] Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ValidarPedidoOutput::fromException($e, 'Error en validación');
        }
    }

    /**
     * Validar cliente
     * 
     * Reglas:
     * - Cliente es obligatorio (no puede estar vacío)
     * 
     * @param ValidarPedidoInput $input
     * @return array [success => bool, errores => string[]]
     */
    private function validarCliente(ValidarPedidoInput $input): array
    {
        $clienteNombre = $input->getClienteNombre();

        if (empty($clienteNombre)) {
            return [
                'success' => false,
                'errores' => ['Cliente es obligatorio'],
            ];
        }

        return [
            'success' => true,
            'errores' => [],
        ];
    }

    /**
     * Validar items (prendas, épps, o items legacy)
     * 
     * Reglas:
     * - Debe tener al menos UNO de:
     *   - Prendas (array no vacío)
     *   - EPPs (array no vacío)
     *   - Items legacy (array no vacío)
     * 
     * @param ValidarPedidoInput $input
     * @return array [success => bool, errores => string[]]
     */
    private function validarItems(ValidarPedidoInput $input): array
    {
        if (!$input->hasSomeItems()) {
            return [
                'success' => false,
                'errores' => ['Debe tener al menos prendas, EPPs o ítems'],
            ];
        }

        return [
            'success' => true,
            'errores' => [],
        ];
    }
}
