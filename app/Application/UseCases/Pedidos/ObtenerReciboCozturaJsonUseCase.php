<?php

namespace App\Application\UseCases\Pedidos;

use App\Domain\Pedidos\Repositories\RecibosRepository;
use App\Domain\Pedidos\Services\EnriquecedorRecibosService;
use App\Models\Festivo;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * UseCase: ObtenerReciboCozturaJsonUseCase
 * 
 * Responsabilidad: Obtener datos de un recibo COSTURA específico como JSON
 * Entrada: ID del recibo
 * Salida: JSON con datos del recibo enriquecidos (días calculados, nombre prenda, cliente)
 * 
 * Endpoint: GET /api/recibos-costura/{reciboId}
 */
class ObtenerReciboCozturaJsonUseCase
{
    public function __construct(
        private RecibosRepository $recibosRepository,
        private EnriquecedorRecibosService $enriquecedor,
    ) {}

    /**
     * Ejecutar el caso de uso
     */
    public function execute(int $reciboId): array
    {
        try {
            // 1. Obtener recibo del repository
            $recibo = $this->recibosRepository->obtenerReciboCostura($reciboId);
            
            if (!$recibo) {
                return [
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                    'http_code' => 404,
                ];
            }

            // 2. Cargar festivales para enriquecimiento
            $festivosSet = $this->cargarFestivos();

            // 3. Enriquecer recibo
            $reciboEnriquecido = $this->enriquecedor->enriquecerRecibo($recibo, $festivosSet);

            // 4. Obtener nombre de prenda (primera prenda del pedido si no la tiene el recibo)
            $nombrePrenda = $reciboEnriquecido['nombre_prenda'] ?? 'Sin prendas';
            if (empty($nombrePrenda) || $nombrePrenda === 'Sin prendas') {
                try {
                    $pedido = PedidoProduccion::find($reciboEnriquecido['pedido_produccion_id']);
                    if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                        $primeraPrenda = $pedido->prendas->first();
                        $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
                    }
                } catch (\Exception $e) {
                    Log::warning('[ObtenerReciboCozturaJsonUseCase] Error obteniendo nombre prenda', [
                        'recibo_id' => $reciboId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 5. Construir respuesta
            return [
                'success' => true,
                'recibo' => [
                    'id' => $reciboEnriquecido['id'],
                    'consecutivo_actual' => $reciboEnriquecido['consecutivo_actual'],
                    'pedido_produccion_id' => $reciboEnriquecido['pedido_produccion_id'],
                    'prenda_id' => $reciboEnriquecido['prenda_id'],
                    'tipo_recibo' => $reciboEnriquecido['tipo_recibo'],
                    'estado' => $reciboEnriquecido['estado'],
                    'area' => $reciboEnriquecido['area'],
                    'dias_calculados' => $reciboEnriquecido['dias_calculados'],
                    'nombre_prenda' => $nombrePrenda,
                    'cliente' => $reciboEnriquecido['pedido_info']['cliente'] ?? '',
                    'numero_pedido' => $reciboEnriquecido['pedido_info']['numero_pedido'] ?? '',
                    'fecha_creacion' => $reciboEnriquecido['pedido_info']['fecha_creacion_orden'] ?? '-',
                    'created_at' => $reciboEnriquecido['created_at'],
                ],
                'http_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('[ObtenerReciboCozturaJsonUseCase] Error', [
                'recibo_id' => $reciboId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Error interno',
                'http_code' => 500,
            ];
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
            Log::warning('[ObtenerReciboCozturaJsonUseCase] No se pudieron cargar festivales', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
