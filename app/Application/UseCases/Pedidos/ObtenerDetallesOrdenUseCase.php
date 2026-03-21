<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerDetallesOrdenOutput;
use App\Models\PedidoProduccion;
use App\Services\RegistroOrdenPrendaService;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ObtenerDetallesOrdenUseCase
 * 
 * Responsabilidad: Obtener detalles completos de una orden con todas sus relaciones
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Obtener orden con relaciones
 * 2. Enriquecer con datos calculados (cantidades, entregas)
 * 3. Obtener prendas relacionadas
 * 4. Retornar resultado
 */
class ObtenerDetallesOrdenUseCase
{
    public function __construct(
        private RegistroOrdenPrendaService $prendaService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(int $numeroPedido): ObtenerDetallesOrdenOutput
    {
        try {
            Log::info('📖 ObtenerDetallesOrdenUseCase iniciado', ['numero_pedido' => $numeroPedido]);

            // 1️⃣ Obtener orden con relaciones
            $orden = PedidoProduccion::with('asesora')
                ->where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            // 2️⃣ Obtener nombre de asesora
            $asesoraName = $orden->asesora ? $orden->asesora->name : '';

            // 3️⃣ Calcular cantidades
            $totalCantidad = 0;
            try {
                if ($orden->prendas && $orden->prendas->count() > 0) {
                    $totalCantidad = $orden->prendas->sum('cantidad') ?? 0;
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Error calculando cantidad: ' . $e->getMessage());
                $totalCantidad = 0;
            }

            // 4️⃣ Calcular entregas
            $totalEntregado = 0;
            try {
                $entregas = $this->prendaService->getEntregas($numeroPedido);
                $totalEntregado = isset($entregas['total_entregado']) ? $entregas['total_entregado'] : 0;
            } catch (\Exception $e) {
                Log::warning('⚠️ Error calculando entregas: ' . $e->getMessage());
                $totalEntregado = 0;
            }

            // 5️⃣ Obtener prendas
            $prendas = [];
            try {
                $prendas = $this->prendaService->getPrendasArray($numeroPedido);
            } catch (\Exception $e) {
                Log::warning('⚠️ Error obteniendo prendas: ' . $e->getMessage());
                $prendas = [];
            }

            Log::info('✅ Detalles de orden obtenidos', [
                'numero_pedido' => $numeroPedido,
                'prendas_count' => count($prendas),
            ]);

            return new ObtenerDetallesOrdenOutput(
                id: $orden->id,
                numero_pedido: $orden->numero_pedido,
                cliente: $orden->cliente ?? '',
                asesora: $asesoraName,
                estado: $orden->estado ?? 'Pendiente',
                descripcion: $orden->descripcion ?? '',
                forma_de_pago: $orden->forma_de_pago ?? '',
                novedades: $orden->novedades,
                area: $orden->area,
                numero_recibo: $orden->numero_recibo,
                cantidad: $totalCantidad,
                total_entregado: $totalEntregado,
                prendas: $prendas,
                metadata: [
                    'created_at' => $orden->created_at,
                    'updated_at' => $orden->updated_at,
                ]
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ Orden no encontrada', ['numero_pedido' => $numeroPedido]);
            throw new \Exception("Orden {$numeroPedido} no encontrada", 404);
        } catch (\Exception $e) {
            Log::error('❌ Error en ObtenerDetallesOrdenUseCase: ' . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
