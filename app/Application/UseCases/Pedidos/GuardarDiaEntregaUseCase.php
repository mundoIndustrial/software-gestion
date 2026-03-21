<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\{GuardarDiaEntregaInput, GuardarDiaEntregaOutput};
use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Services\CalculadorFechaEntregaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB, Log, Broadcast};

/**
 * Use Case: GuardarDiaEntregaUseCase
 * 
 * Responsabilidad: Guardar día de entrega y opcionalmente calcular fecha estimada
 * Patrón: Use Case (Application Service)
 * 
 * Flujo:
 * 1. Validar entrada (día entre 1 y 35)
 * 2. Buscar orden (por número_pedido o id)
 * 3. Si día válido: Opcionalmente calcular fecha estimada
 * 4. Actualizar orden con transacción
 * 5. Emitir evento OrdenUpdated
 * 6. Retornar resultado
 */
class GuardarDiaEntregaUseCase
{
    public function __construct(
        private CalculadorFechaEntregaService $calculadorFechaEntrega,
    ) {}

    /**
     * Ejecutar caso de uso
     */
    public function execute(GuardarDiaEntregaInput $input): GuardarDiaEntregaOutput
    {
        Log::info('[GuardarDiaEntregaUseCase] Iniciando guardado de día de entrega', [
            'numero_pedido' => $input->numero_pedido,
            'dia_de_entrega' => $input->dia_de_entrega,
            'calcular_fecha_estimada' => $input->calcular_fecha_estimada,
        ]);

        // Validar entrada
        if (!$input->isValid()) {
            Log::warning('[GuardarDiaEntregaUseCase] Validación fallida', [
                'numero_pedido' => $input->numero_pedido,
                'mensaje' => $input->getValidationMessage(),
            ]);

            return new GuardarDiaEntregaOutput(
                numero_pedido: $input->numero_pedido,
                mensaje: $input->getValidationMessage() ?? 'Validación fallida',
            );
        }

        try {
            return DB::transaction(function () use ($input) {
                return $this->procesarGuardadoDiaEntrega($input);
            });
        } catch (\Exception $e) {
            Log::error('[GuardarDiaEntregaUseCase] Error al guardar día de entrega', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \InvalidArgumentException('Error al guardar día de entrega: ' . $e->getMessage());
        }
    }

    /**
     * Procesar guardado de día de entrega dentro de transacción
     */
    private function procesarGuardadoDiaEntrega(GuardarDiaEntregaInput $input): GuardarDiaEntregaOutput
    {
        // Buscar orden por número de pedido
        $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
            ->orWhere('id', $input->numero_pedido)
            ->first();

        if (!$orden) {
            Log::warning('[GuardarDiaEntregaUseCase] Orden no encontrada', [
                'numero_pedido' => $input->numero_pedido,
            ]);

            return new GuardarDiaEntregaOutput(
                numero_pedido: $input->numero_pedido,
                mensaje: 'Orden no encontrada',
            );
        }

        // Preparar datos para actualizar
        $datosActualizar = [];

        // Actualizar día de entrega
        if ($input->dia_de_entrega !== null) {
            $datosActualizar['dia_de_entrega'] = $input->dia_de_entrega;

            // Opcionalmente calcular fecha estimada
            if ($input->calcular_fecha_estimada && $input->dia_de_entrega > 0) {
                $fechaCreacion = $orden->fecha_de_creacion_de_orden 
                    ? Carbon::parse($orden->fecha_de_creacion_de_orden)
                    : $orden->created_at ?? Carbon::now();

                $fechaEstimada = $this->calculadorFechaEntrega->calcularConDiasHabiles(
                    $fechaCreacion,
                    $input->dia_de_entrega
                );

                $datosActualizar['fecha_estimada_de_entrega'] = $fechaEstimada->toDateString();
            }
        } else {
            // Si no se proporciona día, limpiar fecha estimada
            $datosActualizar['fecha_estimada_de_entrega'] = null;
        }

        // Actualizar orden
        $orden->update($datosActualizar);

        // Log de actividad
        Log::info('[GuardarDiaEntregaUseCase] Día de entrega guardado correctamente', [
            'numero_pedido' => $orden->numero_pedido,
            'dia_de_entrega' => $datosActualizar['dia_de_entrega'] ?? null,
            'fecha_estimada' => $datosActualizar['fecha_estimada_de_entrega'] ?? null,
        ]);

        // Emitir evento de actualización
        try {
            Broadcast::event(new \App\Events\OrdenUpdated($orden));
        } catch (\Exception $e) {
            Log::warning('[GuardarDiaEntregaUseCase] Error emitiendo evento OrdenUpdated', [
                'numero_pedido' => $orden->numero_pedido,
                'error' => $e->getMessage(),
            ]);
        }

        // Retornar resultado
        return new GuardarDiaEntregaOutput(
            numero_pedido: $orden->numero_pedido,
            mensaje: 'Día de entrega guardado correctamente',
            dia_de_entrega: $datosActualizar['dia_de_entrega'] ?? null,
            fecha_estimada_de_entrega: $datosActualizar['fecha_estimada_de_entrega'] ?? null,
            metadata: [
                'actualizado_en' => now()->toDateTimeString(),
                'usuario' => auth()->user()?->name ?? 'Sistema',
            ],
        );
    }
}
