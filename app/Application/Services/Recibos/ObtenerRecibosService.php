<?php

namespace App\Application\Services\Recibos;

use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Repositories\AsesoresRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ObtenerRecibosService
{
    protected PedidoProduccionRepository $pedidoProduccionRepository;
    protected AsesoresRepository $asesoresRepository;

    public function __construct(
        PedidoProduccionRepository $pedidoProduccionRepository,
        AsesoresRepository $asesoresRepository
    ) {
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->asesoresRepository = $asesoresRepository;
    }

    /**
     * Obtener recibo de un pedido específico
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtenerRecibo(int $pedidoId): array
    {
        Log::info(' [RECIBO] ===== INICIANDO OBTENER RECIBO =====', [
            'pedido_id' => $pedidoId,
            'metodo' => 'obtenerRecibo',
            'clase' => get_class($this)
        ]);

        Log::info(' [RECIBO] Obteniendo recibo para pedido: ' . $pedidoId);

        // Verificar permisos
        if (!$this->esDelAsesor($pedidoId)) {
            throw new \Exception('No tienes permiso para ver este recibo', 403);
        }

        // Obtener datos del recibo
        $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($pedidoId);

        if (empty($datos)) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        Log::info(' [RECIBO] Datos básicos obtenidos del repositorio', [
            'pedido_id' => $pedidoId,
            'tiene_prendas' => isset($datos['prendas']),
            'cantidad_prendas' => isset($datos['prendas']) ? count($datos['prendas']) : 0
        ]);

        // Agregar ancho y metraje a cada prenda
        Log::info('[RECIBO] Iniciando búsqueda de ancho/metraje para prendas', [
            'pedido_id' => $pedidoId,
            'total_prendas' => isset($datos['prendas']) ? count($datos['prendas']) : 0
        ]);

        // DEBUG: Verificar qué datos existen en la tabla para este pedido
        $datosExistentes = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedidoId)->get();
        Log::info('[RECIBO] Datos existentes en tabla pedido_ancho_metraje', [
            'pedido_id_buscado' => $pedidoId,
            'datos_encontrados' => $datosExistentes->toArray(),
            'cantidad' => $datosExistentes->count()
        ]);

        if (isset($datos['prendas']) && is_array($datos['prendas'])) {
            foreach ($datos['prendas'] as $index => &$prenda) {
                $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
                
                Log::info('[RECIBO] Procesando prenda', [
                    'index' => $index,
                    'prenda_id' => $prendaId,
                    'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                    'id_field' => $prenda['id'] ?? 'null',
                    'prenda_pedido_id_field' => $prenda['prenda_pedido_id'] ?? 'null'
                ]);
                
                if ($prendaId) {
                    // Buscar ancho y metraje para esta prenda específica
                    $anchoMetraje = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_pedido_id', $prendaId)
                        ->first();
                    
                    Log::info('[RECIBO] Resultado búsqueda ancho/metraje', [
                        'pedido_id' => $pedidoId,
                        'prenda_id' => $prendaId,
                        'encontrado' => $anchoMetraje ? 'SI' : 'NO',
                        'sql_generada' => 'pedido_produccion_id = ' . $pedidoId . ' AND prenda_pedido_id = ' . $prendaId
                    ]);
                    
                    if ($anchoMetraje) {
                        $prenda['ancho_metraje'] = [
                            'ancho' => $anchoMetraje->ancho,
                            'metraje' => $anchoMetraje->metraje,
                            'prenda_id' => $anchoMetraje->prenda_pedido_id
                        ];
                        
                        Log::info('[RECIBO] Ancho/Metraje encontrado para prenda', [
                            'pedido_id' => $pedidoId,
                            'prenda_id' => $prendaId,
                            'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                            'ancho' => $anchoMetraje->ancho,
                            'metraje' => $anchoMetraje->metraje
                        ]);
                    } else {
                        $prenda['ancho_metraje'] = null;
                        
                        Log::info('[RECIBO] No hay ancho/metraje para prenda', [
                            'pedido_id' => $pedidoId,
                            'prenda_id' => $prendaId,
                            'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                        ]);
                    }
                } else {
                    Log::warning('[RECIBO] Prenda sin ID válido', [
                        'index' => $index,
                        'prenda' => $prenda
                    ]);
                }
            }
        } else {
            Log::warning('[RECIBO] No hay prendas en los datos', [
                'datos' => array_keys($datos)
            ]);
        }

        Log::info(' [RECIBO] Datos obtenidos correctamente', [
            'pedido_id' => $pedidoId,
            'prendas' => count($datos['prendas'] ?? []),
            'procesos' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
        ]);

        return $datos;
    }

    /**
     * Obtener lista de recibos con filtros
     */
    public function listarRecibos(array $filtros = []): LengthAwarePaginator
    {
        Log::info(' [RECIBOS] Listando recibos', ['filtros' => $filtros]);

        $pedidos = $this->asesoresRepository->obtenerPedidosProduccion($filtros);

        Log::info(' [RECIBOS] Listado completado', [
            'cantidad' => $pedidos->count(),
            'total' => $pedidos->total()
        ]);

        return $pedidos;
    }

    /**
     * Obtener resumen de un recibo
     */
    public function obtenerResumen(int $pedidoId): array
    {
        Log::info(' [RECIBO-RESUMEN] Obteniendo resumen para pedido: ' . $pedidoId);

        $datos = $this->obtenerRecibo($pedidoId);

        $resumen = [
            'numero_pedido' => $datos['numero_pedido'] ?? 'N/A',
            'cliente' => $datos['cliente'] ?? 'Sin especificar',
            'fecha_creacion' => $datos['fecha_creacion'] ?? date('d/m/Y'),
            'total_prendas' => collect($datos['prendas'] ?? [])->count(),
            'total_procesos' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? [])),
            'estado' => $datos['estado'] ?? 'Desconocido',
            'forma_pago' => $datos['forma_pago'] ?? 'No especificado',
        ];

        Log::info(' [RECIBO-RESUMEN] Resumen generado', [
            'numero_pedido' => $resumen['numero_pedido'],
            'total_prendas' => $resumen['total_prendas'],
            'total_procesos' => $resumen['total_procesos']
        ]);

        return $resumen;
    }

    /**
     * Obtener detalles de procesos de una prenda especÃ­fica
     */
    public function obtenerProcesosPrenda(int $pedidoId, int $prendaId): array
    {
        Log::info(' [RECIBO-PROCESOS] Obteniendo procesos', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId
        ]);

        $datos = $this->obtenerRecibo($pedidoId);
        
        $prendas = collect($datos['prendas'] ?? [])
            ->where('id', $prendaId)
            ->first();

        if (!$prendas) {
            throw new \Exception('Prenda no encontrada en el recibo', 404);
        }

        Log::info(' [RECIBO-PROCESOS] Procesos obtenidos', [
            'prenda_id' => $prendaId,
            'procesos_count' => count($prendas['procesos'] ?? [])
        ]);

        return [
            'prenda' => $prendas,
            'pedido_numero' => $datos['numero_pedido'],
            'cliente' => $datos['cliente'],
        ];
    }

    /**
     * Obtener estados disponibles para filtro
     */
    public function obtenerEstadosDisponibles(): array
    {
        Log::info('ðŸ·ï¸ [RECIBO] Obteniendo estados disponibles');

        $estados = $this->asesoresRepository->obtenerEstados();

        Log::info(' [RECIBO] Estados disponibles', ['count' => count($estados)]);

        return $estados;
    }

    /**
     * Verificar si el pedido pertenece al asesor autenticado
     */
    private function esDelAsesor(int $pedidoId): bool
    {
        return $this->asesoresRepository->esDelAsesor($pedidoId);
    }

    /**
     * Exportar recibo a array para vistas
     */
    public function exportarParaVista(int $pedidoId): array
    {
        Log::info('ðŸ“¤ [RECIBO-EXPORT] Exportando para vista: ' . $pedidoId);

        $recibo = $this->obtenerRecibo($pedidoId);
        $resumen = $this->obtenerResumen($pedidoId);

        return array_merge($recibo, ['resumen' => $resumen]);
    }
}

