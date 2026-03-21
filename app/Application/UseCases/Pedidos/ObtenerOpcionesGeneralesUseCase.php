<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerOpcionesGeneralesOutput;
use App\Domain\Pedidos\Services\FiltroOrdenService;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ObtenerOpcionesGeneralesUseCase
 * 
 * Responsabilidad: Obtener todas las opciones disponibles para filtros
 * Patrón: Application Service (UseCase)
 * 
 * Flujo:
 * 1. Obtener todas las opciones
 * 2. Validar resultados
 * 3. Retornar DTO
 */
class ObtenerOpcionesGeneralesUseCase
{
    public function __construct(
        private FiltroOrdenService $filtroService,
    ) {}

    /**
     * Ejecutar caso de uso
     * 
     * @throws \Exception
     */
    public function execute(): ObtenerOpcionesGeneralesOutput
    {
        try {
            Log::info('📋 ObtenerOpcionesGeneralesUseCase iniciado');

            // 1️⃣ Obtener todas las opciones
            $opciones = $this->filtroService->obtenerOpcionesGenerales();

            Log::info('✅ Opciones generales obtenidas', [
                'campos' => count($opciones),
            ]);

            return new ObtenerOpcionesGeneralesOutput(
                estados: $opciones['estados'] ?? [],
                areas: $opciones['areas'] ?? [],
                clientes: $opciones['clientes'] ?? [],
                asesores: $opciones['asesores'] ?? [],
                formas_pago: $opciones['formas_pago'] ?? [],
                encargados: $opciones['encargados'] ?? [],
                dias_entrega: $opciones['dias_entrega'] ?? [],
                metadata: [
                    'generated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('❌ Error en ObtenerOpcionesGeneralesUseCase: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
