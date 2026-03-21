<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosReflectivoInput;
use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosReflectivoOutput;
use App\Domain\Pedidos\Services\EnriquecedorRecibosService;
use App\Domain\Pedidos\Repositories\RecibosRepository;
use App\Models\Festivo;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ObtenerRecibosReflectivoUseCase
 * 
 * Responsabilidad: Obtener recibos de REFLECTIVO filtrados y enriquecidos
 * Entrada: ObtenerRecibosReflectivoInput (con 4 tipos de filtros)
 * Salida: ObtenerRecibosReflectivoOutput (con datos enriquecidos)
 * 
 * Variante simplificada de ObtenerRecibosCozturaUseCase:
 * - Filtra por tipo_recibo = 'REFLECTIVO'
 * - Solo 4 filtros básicos (vs 11 para COSTURA)
 * - Reusa EnriquecedorRecibosService para consistencia
 * - Retorna JSON o view data
 */
class ObtenerRecibosReflectivoUseCase
{
    public function __construct(
        private EnriquecedorRecibosService $enriquecedor,
        private RecibosRepository $recibosRepository,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(ObtenerRecibosReflectivoInput $input): ObtenerRecibosReflectivoOutput
    {
        try {
            Log::info('[ObtenerRecibosReflectivoUseCase] Iniciando búsqueda de recibos REFLECTIVO', [
                'tiene_filtros' => $input->tieneFiltros(),
                'es_ajax' => $input->es_ajax,
                'tipo_respuesta' => $input->tipo_respuesta,
            ]);

            // 1. Construir query base para REFLECTIVO usando Repository
            $query = $this->recibosRepository->queryRecibosReflectivoActivos();

            // 2. Aplicar filtros (solo 4 tipos)
            $filtrosAplicados = [];
            if ($input->tieneFiltros()) {
                $query = $this->aplicarFiltros($query, $input, $filtrosAplicados);
            }

            // 3. Obtener recibos base
            $recibosBase = $query->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
                ->get();

            Log::info('[ObtenerRecibosReflectivoUseCase] Recibos obtenidos', [
                'cantidad' => $recibosBase->count(),
            ]);

            // 4. Cargar festivales para enriquecimiento
            $festivosSet = $this->cargarFestivos();

            // 5. Enriquecer recibos
            $recibosEnriquecidos = [];
            $cantidadTotal = 0;

            foreach ($recibosBase as $recibo) {
                $reciboEnriquecido = $this->enriquecedor->enriquecerRecibo($recibo, $festivosSet);
                $recibosEnriquecidos[] = $reciboEnriquecido;
                $cantidadTotal += $reciboEnriquecido['cantidad_total'];
            }

            // 6. Construir y retornar respuesta
            return new ObtenerRecibosReflectivoOutput(
                recibos: $recibosEnriquecidos,
                total: $recibosBase->count(),
                cantidad_total: $cantidadTotal,
                filtros_aplicados: $filtrosAplicados,
                metadata: [
                    'usuario_id' => auth()?->id(),
                    'timestamp' => now()->toIso8601String(),
                    'tipo_filtro' => $input->tipo_respuesta,
                ]
            );

        } catch (\Exception $e) {
            Log::error('[ObtenerRecibosReflectivoUseCase] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ObtenerRecibosReflectivoOutput(
                recibos: [],
                total: 0,
                cantidad_total: 0,
                filtros_aplicados: [],
                metadata: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Construir query base: recibos de REFLECTIVO activos
     */
    /**
     * Aplicar 4 tipos de filtros básicos
     */
    private function aplicarFiltros($query, ObtenerRecibosReflectivoInput $input, &$filtrosAplicados)
    {
        $tiposFiltro = [
            'estado',
            'numero_recibo',
            'cliente',
            'dia_entrega',
        ];

        foreach ($tiposFiltro as $tipo) {
            $valor = $input->getFiltro($tipo);
            if (empty($valor)) {
                continue;
            }

            $this->aplicarFiltroIndividual($query, $tipo, $valor);
            $filtrosAplicados[$tipo] = $valor;

            Log::debug('[ObtenerRecibosReflectivoUseCase] Filtro aplicado', [
                'tipo' => $tipo,
                'valor' => $valor,
            ]);
        }

        return $query;
    }

    /**
     * Aplicar un filtro individual
     */
    private function aplicarFiltroIndividual($query, string $tipo, $valor)
    {
        if (is_string($valor)) {
            $valor = [$valor];
        }
        $valor = array_filter($valor); // Eliminar valores vacíos

        if (empty($valor)) {
            return;
        }

        switch ($tipo) {
            case 'estado':
                $query->whereIn('consecutivos_recibos_pedidos.estado', $valor);
                break;

            case 'numero_recibo':
                // Buscar por número consecutivo
                $query->whereIn('consecutivos_recibos_pedidos.consecutivo_actual', array_map('intval', $valor));
                break;

            case 'cliente':
                // Coincidencia parcial en cliente
                $query->whereIn('pedidos_produccion.cliente', $valor);
                break;

            case 'dia_entrega':
                // Filtro por día específico de entrega
                $query->whereIn('pedidos_produccion.dia_de_entrega', $valor);
                break;
        }
    }

    /**
     * Cargar festivales colombianos
     */
    private function cargarFestivos(): array
    {
        try {
            $festivos = Festivo::active()
                ->pluck('fecha')
                ->map(fn($f) => $f->format('Y-m-d'))
                ->toArray();

            return array_flip($festivos); // ['Y-m-d' => true, ...]

        } catch (\Exception $e) {
            Log::warning('[ObtenerRecibosReflectivoUseCase] No se pudieron cargar festivales', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
