<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerOpcionesColumnaInput;
use App\Application\UseCases\Pedidos\DTOs\ObtenerOpcionesColumnaOutput;
use App\Domain\Pedidos\Services\FiltroOrdenService;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ObtenerOpcionesColumnaUseCase
 * 
 * Responsabilidad: Obtener opciones disponibles para una columna específica
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Validar columna
 * 2. Obtener opciones con búsqueda y paginación
 * 3. Retornar resultado paginado
 */
class ObtenerOpcionesColumnaUseCase
{
    public function __construct(
        private FiltroOrdenService $filtroService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(ObtenerOpcionesColumnaInput $input): ObtenerOpcionesColumnaOutput
    {
        try {
            Log::info('🎯 ObtenerOpcionesColumnaUseCase iniciado', [
                'column' => $input->column,
                'search' => $input->search,
            ]);

            // 1️⃣ Validar columna
            if (!$input->columnasValida()) {
                throw new \InvalidArgumentException("Columna no válida: {$input->column}");
            }

            // 2️⃣ Obtener opciones
            $resultado = $this->filtroService->obtenerOpcionesColumna(
                columna: $input->column,
                busqueda: $input->search,
                pagina: $input->page,
                limite: $input->limit
            );

            Log::info('✅ Opciones obtenidas', [
                'column' => $input->column,
                'total' => $resultado['total'],
            ]);

            return new ObtenerOpcionesColumnaOutput(
                column: $input->column,
                total: $resultado['total'],
                page: $resultado['page'],
                limit: $resultado['limit'],
                last_page: $resultado['last_page'],
                opciones: $resultado['opciones'],
                metadata: [
                    'search_used' => !empty($input->search),
                ]
            );
        } catch (\Exception $e) {
            Log::error('❌ Error en ObtenerOpcionesColumnaUseCase: ' . $e->getMessage(), [
                'column' => $input->column,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
