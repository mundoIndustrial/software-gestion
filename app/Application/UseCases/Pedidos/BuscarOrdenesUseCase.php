<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\BuscarOrdenesInput;
use App\Application\UseCases\Pedidos\DTOs\BuscarOrdenesOutput;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: BuscarOrdenesUseCase
 * 
 * Responsabilidad: Buscar órdenes en tiempo real (dropdown/tabla)
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Validar búsqueda
 * 2. Ejecutar búsqueda por número_pedido o cliente
 * 3. Retornar resultados formateados
 */
class BuscarOrdenesUseCase
{
    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(BuscarOrdenesInput $input): BuscarOrdenesOutput
    {
        try {
            Log::info('🔍 BuscarOrdenesUseCase iniciado', [
                'search' => $input->search,
                'isTableSearch' => $input->isTableSearch,
            ]);

            // 1️⃣ Validar entrada
            $errores = $input->validar();
            if (!empty($errores)) {
                throw new \InvalidArgumentException($errores[0]);
            }

            // 2️⃣ Buscar por número de pedido o cliente
            $query = PedidoProduccion::where('numero_pedido', 'LIKE', '%' . $input->search . '%')
                ->orWhere('cliente', 'LIKE', '%' . $input->search . '%');

            // 3️⃣ Si es búsqueda de tabla, retornar con todos los campos y paginación
            if ($input->isTableSearch) {
                $ordenes = $query
                    ->with('asesora')
                    ->orderBy('numero_pedido', 'DESC')
                    ->paginate($input->limit, ['*'], 'page', $input->page);

                $data = $ordenes->map(function($orden) {
                    return [
                        'id' => $orden->id,
                        'numero_pedido' => $orden->numero_pedido,
                        'cliente' => $orden->cliente,
                        'asesora' => $orden->asesora ? $orden->asesora->name : '',
                        'estado' => $orden->estado,
                        'area' => $orden->area,
                        'fecha_creacion' => $orden->created_at->format('Y-m-d H:i'),
                        'descripcion' => substr($orden->descripcion ?? '', 0, 50),
                    ];
                })->toArray();

                return BuscarOrdenesOutput::fromPaginator($ordenes, $data);
            }

            // 4️⃣ Si es búsqueda dropdown, retornar solo lo necesario (sin paginación)
            $ordenes = $query
                ->select('id', 'numero_pedido', 'cliente', 'estado', 'area')
                ->limit($input->limit)
                ->get()
                ->map(function($orden) {
                    return [
                        'id' => $orden->id,
                        'numero_pedido' => $orden->numero_pedido,
                        'cliente' => $orden->cliente,
                        'estado' => $orden->estado,
                        'area' => $orden->area,
                    ];
                })
                ->toArray();

            Log::info('✅ Búsqueda completada', [
                'search' => $input->search,
                'resultados' => count($ordenes),
            ]);

            return new BuscarOrdenesOutput(
                total: count($ordenes),
                page: $input->page,
                per_page: $input->limit,
                last_page: ceil(count($ordenes) / $input->limit),
                data: $ordenes,
                metadata: [
                    'search_term' => $input->search,
                ]
            );
        } catch (\Exception $e) {
            Log::error('❌ Error en BuscarOrdenesUseCase: ' . $e->getMessage(), [
                'search' => $input->search,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
