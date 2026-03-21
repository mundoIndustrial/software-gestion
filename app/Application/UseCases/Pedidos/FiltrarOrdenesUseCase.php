<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\FiltrarOrdenesInput;
use App\Application\UseCases\Pedidos\DTOs\FiltrarOrdenesOutput;
use App\Domain\Pedidos\Services\FiltroOrdenService;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: FiltrarOrdenesUseCase
 * 
 * Responsabilidad: Filtrar órdenes con criterios complejos
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Obtener criterios de filtrado
 * 2. Aplicar filtros via Domain Service
 * 3. Validar resultados
 * 4. Retornar paginado
 */
class FiltrarOrdenesUseCase
{
    public function __construct(
        private FiltroOrdenService $filtroService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(FiltrarOrdenesInput $input): FiltrarOrdenesOutput
    {
        try {
            Log::info('🔎 FiltrarOrdenesUseCase iniciado', [
                'filtros' => count($input->filters),
                'page' => $input->page,
            ]);

            // 1️⃣ Aplicar filtros
            $query = $this->filtroService->aplicarFiltros($input->filters);

            // 2️⃣ Obtener total antes de paginación
            $total = $query->count();
            $lastPage = ceil($total / $input->per_page);

            // 3️⃣ Aplicar paginación
            $ordenes = $query
                ->with('asesora')
                ->orderBy('numero_pedido', 'DESC')
                ->skip(($input->page - 1) * $input->per_page)
                ->take($input->per_page)
                ->get();

            // 4️⃣ Formatear datos
            $ordenesFormato = $ordenes->map(function($orden) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'asesora' => $orden->asesora ? $orden->asesora->name : '',
                    'estado' => $orden->estado,
                    'area' => $orden->area,
                    'forma_de_pago' => $orden->forma_de_pago,
                    'fecha_creacion' => $orden->created_at->format('Y-m-d H:i'),
                    'descripcion' => substr($orden->descripcion ?? '', 0, 50),
                    'dia_entrega' => $orden->dia_de_entrega,
                ];
            })->toArray();

            Log::info('✅ Filtrado completado', [
                'total' => $total,
                'resultados_pagina' => count($ordenesFormato),
                'page' => $input->page,
            ]);

            return new FiltrarOrdenesOutput(
                total: $total,
                page: $input->page,
                per_page: $input->per_page,
                last_page: $lastPage,
                ordenes: $ordenesFormato,
                filtros_aplicados: !empty($input->filters) ? array_keys($input->filters) : null,
                metadata: [
                    'filtros_count' => count($input->filters),
                ]
            );
        } catch (\Exception $e) {
            Log::error('❌ Error en FiltrarOrdenesUseCase: ' . $e->getMessage(), [
                'filtros' => count($input->filters),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
