<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Clientes\Services\ClienteService;
use App\Domain\Pedidos\Events\PedidoValidatedEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: Validar Pedido
 * FASE 2 - Valida pedido antes de crear
 * Responsabilidades:
 * - Validar estructura JSON recibida
 * - Validar cliente (obligatorio)
 * - Validar que hay al menos prendas, epps o items
 * - Obtener o crear cliente
 * - Retornar estado de validacion
 * Nota: Este UseCase se ejecuta ANTES de CrearPedidoCompleteUseCase
 * Permite al frontend validar datos antes de commit
 * @package App\Application\UseCases\Pedidos
 */
class ValidarPedidoUseCase
{
    public function __construct(
        private ClienteService $clienteService,
    ) {}

    /**
     * Ejecutar validacion del pedido
     * FLUJO:
     * 1. Validar estructura JSON
     * 2. Validar cliente (requerido)
     * 3. Validar que hay items (prendas, epps o items legacy)
     * 4. Obtener/crear cliente
     * 5. Retornar resultado con cliente_id
     * @param ValidarPedidoInput $input
     * @return ValidarPedidoOutput
     */
    public function ejecutar(ValidarPedidoInput $input): ValidarPedidoOutput
    {
        $output = ValidarPedidoOutput::failure(
            ['Error inesperado en validacion'],
            'Validacion no completada'
        );

        try {
            Log::info('[ValidarPedidoUseCase] Iniciando validacion', [
                'user_id' => $input->userId,
                'cliente_raw' => $input->getClienteNombre(),
                'tiene_prendas' => $input->hasPrendas(),
                'tiene_epps' => $input->hasEpps(),
                'tiene_items_legacy' => $input->hasItemsLegacy(),
            ]);

            // ====== PASO 1: Validar cliente ======
            $validarClienteResult = $this->validarCliente($input);
            if (!$validarClienteResult['success']) {
                Log::warning('[ValidarPedidoUseCase] Validacion de cliente fallida', [
                    'errores' => $validarClienteResult['errores'],
                ]);

                $output = ValidarPedidoOutput::failure(
                    $validarClienteResult['errores'],
                    'Validacion de cliente fallida'
                );
            } else {
                // ====== PASO 2: Validar items ======
                $validarItemsResult = $this->validarItems($input);
                if (!$validarItemsResult['success']) {
                    Log::warning('[ValidarPedidoUseCase] Validacion de items fallida', [
                        'errores' => $validarItemsResult['errores'],
                    ]);

                    $output = ValidarPedidoOutput::failure(
                        $validarItemsResult['errores'],
                        'Validacion de items fallida'
                    );
                } else {
                    // ====== PASO 3: Obtener/crear cliente ======
                    $clienteNombre = $input->getClienteNombre();
                    $cliente = $this->clienteService->obtenerOCrearCliente($clienteNombre);

                    Log::info('[ValidarPedidoUseCase] Validacion exitosa', [
                        'cliente_id' => $cliente->id,
                        'cliente_nombre' => $cliente->nombre,
                        'items_counts' => $input->getItemCounts(),
                    ]);

                    // ====== Domain Event: Pedido Validado ======
                    Event::dispatch(new PedidoValidatedEvent(
                        pedidoId: 0,
                        usuarioId: $input->userId,
                        validacionesPasadas: ['cliente', 'items'],
                        metadata: [
                            'cliente_id' => $cliente->id,
                            'cliente_nombre' => $cliente->nombre,
                            'items_counts' => $input->getItemCounts(),
                        ]
                    ));

                    $output = ValidarPedidoOutput::success(
                        $cliente->id,
                        'Pedido valido'
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('[ValidarPedidoUseCase] Error general', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $output = ValidarPedidoOutput::fromException($e, 'Error en validacion');
        }

        return $output;
    }

    /**
     * Validar cliente
     * Reglas:
     * - Cliente es obligatorio (no puede estar vacio)
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
     * Validar items (prendas, epps, o items legacy)
     * Reglas:
     * - Debe tener al menos UNO de:
     *   - Prendas (array no vacio)
     *   - EPPs (array no vacio)
     *   - Items legacy (array no vacio)
     * @param ValidarPedidoInput $input
     * @return array [success => bool, errores => string[]]
     */
    private function validarItems(ValidarPedidoInput $input): array
    {
        if (!$input->hasSomeItems()) {
            return [
                'success' => false,
                'errores' => ['Debe tener al menos prendas, EPPs o items'],
            ];
        }

        return [
            'success' => true,
            'errores' => [],
        ];
    }
}
