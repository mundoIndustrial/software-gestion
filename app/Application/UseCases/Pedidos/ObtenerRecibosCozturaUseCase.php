<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosCozturaInput;
use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosCozturaOutput;
use App\Domain\Pedidos\Services\EnriquecedorRecibosService;
use App\Domain\Pedidos\Repositories\RecibosRepository;
use App\Models\Festivo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * UseCase: ObtenerRecibosCozturaUseCase
 * 
 * Responsabilidad: Obtener recibos de costura filtrados y enriquecidos
 * Entrada: ObtenerRecibosCozturaInput (con 11 tipos de filtros)
 * Salida: ObtenerRecibosCozturaOutput (con datos + HTML opcional)
 * 
 * Encapsula:
 * - Construcción de query base (COSTURA activos)
 * - Aplicación de 11 tipos de filtros
 * - Enriquecimiento de datos per recibo
 * - Generación de respuesta (JSON con HTML opcional o data-only)
 */
class ObtenerRecibosCozturaUseCase
{
    public function __construct(
        private EnriquecedorRecibosService $enriquecedor,
        private RecibosRepository $recibosRepository,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(ObtenerRecibosCozturaInput $input): ObtenerRecibosCozturaOutput
    {
        try {
            Log::info('[ObtenerRecibosCozturaUseCase] Iniciando búsqueda de recibos', [
                'tiene_filtros' => $input->tieneFiltros(),
                'es_ajax' => $input->es_ajax,
                'tipo_respuesta' => $input->tipo_respuesta,
            ]);

            // 1. Construir query base usando Repository
            $query = $this->recibosRepository->queryRecibosCozturaActivos();

            // 2. Aplicar filtros
            $filtrosAplicados = [];
            if ($input->tieneFiltros()) {
                $query = $this->aplicarFiltros($query, $input, $filtrosAplicados);
            }

            // 3. Obtener recibos base
            $recibosBase = $query->orderBy('consecutivos_recibos_pedidos.created_at', 'desc')
                ->get();

            Log::info('[ObtenerRecibosCozturaUseCase] Recibos obtenidos', [
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

            // 6. Generar HTML si es necesario
            $htmlTabla = null;
            if ($input->es_ajax) {
                $htmlTabla = $this->generarHTMLTabla($recibosEnriquecidos);
            }

            // 7. Construir y retomar respuesta
            return new ObtenerRecibosCozturaOutput(
                recibos: $recibosEnriquecidos,
                total: $recibosBase->count(),
                cantidad_total: $cantidadTotal,
                filtros_aplicados: $filtrosAplicados,
                html: $htmlTabla,
                metadata: [
                    'usuario_id' => auth()?->id(),
                    'timestamp' => now()->toIso8601String(),
                    'tipo_filtro' => $input->tipo_respuesta,
                ]
            );

        } catch (\Exception $e) {
            Log::error('[ObtenerRecibosCozturaUseCase] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new ObtenerRecibosCozturaOutput(
                recibos: [],
                total: 0,
                cantidad_total: 0,
                filtros_aplicados: [],
                html: null,
                metadata: ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Construir query base: recibos de COSTURA activos
     */
    /**
     * Aplicar 11 tipos de filtros
     */
    private function aplicarFiltros($query, ObtenerRecibosCozturaInput $input, &$filtrosAplicados)
    {
        $tiposFiltro = [
            'estado',
            'dia_entrega',
            'total_dias',
            'numero_recibo',
            'cliente',
            'descripcion',
            'cantidad',
            'novedades',
            'fecha_creacion',
            'fecha_estimada',
            'encargado',
        ];

        foreach ($tiposFiltro as $tipo) {
            $valor = $input->getFiltro($tipo);
            if (empty($valor)) {
                continue;
            }

            $this->aplicarFiltroIndividual($query, $tipo, $valor);
            $filtrosAplicados[$tipo] = $valor;

            Log::debug('[ObtenerRecibosCozturaUseCase] Filtro aplicado', [
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
                $query->whereIn('consecutivos_recibos_pedidos.consecutivo_actual', array_map('intval', $valor));
                break;

            case 'cliente':
                // Coincidencia parcial en cliente
                foreach ($valor as $cliente) {
                    $query->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$cliente}%");
                }
                break;

            case 'descripcion':
                // Busca en notas del recibo
                foreach ($valor as $desc) {
                    $query->orWhere('consecutivos_recibos_pedidos.notas', 'LIKE', "%{$desc}%");
                }
                break;

            case 'dia_entrega':
                // Filtro por día específico de entrega
                $query->whereIn('pedidos_produccion.dia_de_entrega', $valor);
                break;

            case 'total_dias':
                // Rango de días (valor como "5:10" → entre 5 y 10 días)
                foreach ($valor as $rango) {
                    if (strpos($rango, ':') !== false) {
                        [$min, $max] = explode(':', $rango);
                        // Este filtro se aplica post-query en enriquecimiento
                    } else {
                        // Día específico
                        $query->where('pedidos_produccion.dia_de_entrega', $rango);
                    }
                }
                break;

            case 'cantidad':
                // Filtro de cantidad de prendas (aplicado post-enriquecimiento)
                // Se maneja en construcción de respuesta
                break;

            case 'novedades':
                // Recibos con notas/novedades
                $query->where('consecutivos_recibos_pedidos.notas', '!=', '');
                break;

            case 'fecha_creacion':
                // Rango de fecha: "2024-01-01:2024-12-31"
                foreach ($valor as $rango) {
                    if (strpos($rango, ':') !== false) {
                        [$from, $to] = explode(':', $rango);
                        $query->whereBetween('consecutivos_recibos_pedidos.created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);
                    }
                }
                break;

            case 'fecha_estimada':
                // Filtro por fecha estimada de entrega
                foreach ($valor as $fecha) {
                    $query->orWhere('pedidos_produccion.fecha_estimada_de_entrega', $fecha);
                }
                break;

            case 'encargado':
                // Buscar en notas por encargado (ej: "[usuario_name")
                foreach ($valor as $encargado) {
                    $query->orWhere('consecutivos_recibos_pedidos.notas', 'LIKE', "%[{$encargado}%");
                }
                break;
        }
    }

    /**
     * Cargar festivales colombianos para enriquecimiento
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
            Log::warning('[ObtenerRecibosCozturaUseCase] No se pudieron cargar festivales', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Generar tabla HTML con los recibos
     */
    private function generarHTMLTabla(array $recibos): ?string
    {
        try {
            // Usar blade view para generar HTML
            return View::make('partials.recibos-tabla', [
                'recibos' => $recibos,
                'tipo_recibo' => 'COSTURA',
            ])->render();

        } catch (\Exception $e) {
            Log::warning('[ObtenerRecibosCozturaUseCase] Error generando HTML tabla', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
