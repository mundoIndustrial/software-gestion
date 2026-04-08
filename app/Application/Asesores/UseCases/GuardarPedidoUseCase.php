<?php

namespace App\Application\Asesores\UseCases;

use Illuminate\Support\Facades\DB;
use App\Application\Pedidos\DTOs\GuardarPedidoInputDTO;
use App\Application\Pedidos\DTOs\GuardarPedidoOutputDTO;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Services\Asesores\GuardarPedidoLogoService;
use Illuminate\Support\Facades\Log;

/**
 * GuardarPedidoUseCase 
 * 
 * Responsabilidades (solo estas):
 * 1. Orquestar la lógica de guardado según el tipo de pedido
 * 2. Manejar transacciones ACID
 * 3. Delegar a servicios especializados
 * 
 */
final class GuardarPedidoUseCase
{
    public function __construct(
        private CrearProduccionPedidoUseCase $crearProduccionPedidoUseCase,
        private GuardarPedidoLogoService $guardarPedidoLogoService,
    ) {}

    /**
     * Ejecutar guardado de pedido
     * 
     * @param GuardarPedidoInputDTO $input - Todos los datos necesarios, ya validados
     * @return GuardarPedidoOutputDTO - Resultado con metadata
     * @throws \Exception - Si falla algún paso
     */
    public function ejecutar(GuardarPedidoInputDTO $input): GuardarPedidoOutputDTO
    {
        // Usar DB::transaction() que es más limpio y seguro
        return DB::transaction(function () use ($input): GuardarPedidoOutputDTO {
            Log::info('[GuardarPedidoUseCase] Iniciando guardado', [
                'tipo' => $input->tipoPedido->value(),
                'cliente_id' => $input->clienteId,
            ]);

            // Orquestación: decidir flujo según el tipo de pedido
            if ($input->tipoPedido->esLogo()) {
                return $this->guardarPedidoLogo($input);
            }

            return $this->guardarPedidoProduccion($input);
        });
    }

    /**
     * Guardar pedido de LOGO
     * 
     * Delegación: GuardarPedidoLogoService maneja toda la lógica técnica
     */
    private function guardarPedidoLogo(GuardarPedidoInputDTO $input): GuardarPedidoOutputDTO
    {
        Log::info('[GuardarPedidoUseCase] Flujo LOGO iniciado');

        // El servicio de infraestructura maneja el guardado técnico
        $logoPedidoId = $this->guardarPedidoLogoService->guardar(
            $input->datosCliente,
            $input->imagenesProcesadas ?? []
        );

        Log::info('[GuardarPedidoUseCase] Pedido LOGO guardado', [
            'logo_pedido_id' => $logoPedidoId,
        ]);

        return new GuardarPedidoOutputDTO(
            tipo: 'logo',
            id: $logoPedidoId,
            mensaje: 'Pedido de logo guardado correctamente',
            metadata: [
                'tipo_guardado' => 'logo_pedidos',
            ]
        );
    }

    /**
     * Guardar pedido de PRODUCCIÓN
     * 
     * Delegación: CrearProduccionPedidoUseCase maneja la lógica de negocio
     */
    private function guardarPedidoProduccion(GuardarPedidoInputDTO $input): GuardarPedidoOutputDTO
    {
        Log::info('[GuardarPedidoUseCase] Flujo PRODUCCIÓN iniciado');

        // Crear DTO para el siguiente UseCase
        // Adaptarse a los parámetros que CrearProduccionPedidoDTO espera
        $crearPedidoDTO = new CrearProduccionPedidoDTO(
            numeroPedido: $input->datosCliente['numero_pedido'] ?? '',
            cliente: $input->datosCliente['cliente'] ?? $input->clienteId,
            prendas: $input->productos['prendas'] ?? $input->productos,
            epps: $input->productos['epps'] ?? [],
            area: $input->datosCliente['area'] ?? null,
            estado: $input->datosCliente['estado'] ?? 'pendiente_cartera',
            asesorId: $input->datosCliente['asesor_id'] ?? null,
            clienteId: (int) $input->clienteId,
            formaDePago: $input->datosCliente['forma_de_pago'] ?? null,
        );

        // Delegar al UseCase especializado
        $pedido = $this->crearProduccionPedidoUseCase->ejecutar($crearPedidoDTO);

        Log::info('[GuardarPedidoUseCase] Pedido PRODUCCIÓN guardado', [
            'pedido_id' => $pedido->getId(),
        ]);

        return new GuardarPedidoOutputDTO(
            tipo: 'produccion',
            id: $pedido->getId(),
            mensaje: 'Pedido guardado como borrador',
            metadata: [
                'tipo_guardado' => 'pedidos_produccion',
                'estado' => 'borrador',
            ]
        );
    }
}
