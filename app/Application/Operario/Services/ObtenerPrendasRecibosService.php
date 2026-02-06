<?php

namespace App\Application\Operario\Services;

use App\Models\User;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Support\Collection;

/**
 * Service: ObtenerPrendasRecibosService
 * 
 * Obtiene las prendas con sus recibos de costura para un operario
 */
class ObtenerPrendasRecibosService
{
    /**
     * Obtener prendas con recibos de costura del operario
     */
    public function obtenerPrendasConRecibos(User $usuario): Collection
    {
        // Obtener tipo de operario
        $tipoOperario = $this->obtenerTipoOperario($usuario);

        // Determinar tipos de recibo según el rol
        $tiposRecibo = ['COSTURA', 'COSTURA-BODEGA'];
        if ($tipoOperario === 'costura-reflectivo') {
            // Para costura-reflectivo, SOLO mostrar COSTURA y REFLECTIVO (sin COSTURA-BODEGA)
            $tiposRecibo = ['COSTURA', 'REFLECTIVO'];
        }

        // Obtener todos los recibos de costura activos con relaciones (incluyendo procesos)
        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', $tiposRecibo)
            ->with(['prenda', 'prenda.pedidoProduccion', 'prenda.procesosPrenda', 'pedido', 'pedido.prendas'])
            ->orderBy('created_at', 'desc')
            ->get();

        \Log::info('🔍 [ObtenerPrendasRecibosService] Recibos encontrados', [
            'total_recibos' => $recibos->count(),
            'tipos_buscados' => $tiposRecibo,
            'prenda_ids' => $recibos->pluck('prenda_id')->toArray()
        ]);

        // Agrupar por prenda (o por pedido si es REFLECTIVO sin prenda_id)
        $prendasAgrupadas = $recibos->groupBy(function ($recibo) {
            // Si tiene prenda_id, agrupar por prenda
            if ($recibo->prenda_id) {
                return 'prenda_' . $recibo->prenda_id;
            }
            // Si no tiene prenda_id (REFLECTIVO), agrupar por pedido
            return 'pedido_' . $recibo->pedido_produccion_id;
        })->flatMap(function ($recibosDelaPrenda) use ($tipoOperario) {
            $primeRecibo = $recibosDelaPrenda->first();
            
            // Obtener la prenda
            if ($primeRecibo->prenda_id && $primeRecibo->prenda) {
                $prenda = $primeRecibo->prenda;
                $pedido = $prenda->pedidoProduccion;
            } else if ($primeRecibo->pedido_produccion_id && $primeRecibo->pedido) {
                // Para REFLECTIVO sin prenda_id, obtener la primera prenda del pedido
                $pedido = $primeRecibo->pedido;
                $prenda = $pedido->prendas->first();
            } else {
                \Log::info('🔍 [Filtro 0] No se pudo obtener prenda o pedido');
                return [];
            }
            
            // Validar que prenda y pedido existan
            if (!$prenda || !$pedido) {
                \Log::info('🔍 [Filtro 1] Prenda o pedido no existe');
                return [];
            }
            
            // Filtrar por estado "En Ejecución" y área "costura"
            if ($pedido->estado !== 'En Ejecución' || strtolower($pedido->area) !== 'costura') {
                \Log::info('🔍 [Filtro 2] Estado o área no coinciden', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado' => $pedido->estado,
                    'area' => $pedido->area
                ]);
                return [];
            }
            
            // Verificar que la prenda tenga un proceso con encargado "costura-reflectivo"
            $procesosCount = $prenda->procesosPrenda ? $prenda->procesosPrenda->count() : 0;
            \Log::info('🔍 [Filtro 3] Verificando procesos', [
                'numero_pedido' => $pedido->numero_pedido,
                'nombre_prenda' => $prenda->nombre_prenda,
                'procesos_count' => $procesosCount
            ]);
            
            if ($prenda->procesosPrenda) {
                $prenda->procesosPrenda->each(function ($proc) {
                    \Log::info('🔍 Proceso encontrado', ['encargado' => $proc->encargado]);
                });
            }
            
            $tieneProcesoCosturaReflectivo = $prenda->procesosPrenda && $prenda->procesosPrenda->contains(function ($proceso) {
                return strtolower($proceso->encargado) === 'costura-reflectivo';
            });
            
            if (!$tieneProcesoCosturaReflectivo) {
                \Log::info('🔍 [Filtro 4] No tiene proceso costura-reflectivo', [
                    'numero_pedido' => $pedido->numero_pedido
                ]);
                return [];
            }
            
            \Log::info('✅ [Prenda Aprobada]', ['numero_pedido' => $pedido->numero_pedido]);

            // Separar recibos por tipo - crear una entrada para cada tipo
            $recibosPorTipo = $recibosDelaPrenda->groupBy('tipo_recibo');
            
            $resultados = [];
            foreach ($recibosPorTipo as $tipoRecibo => $recibosDelTipo) {
                // Para REFLECTIVO, validar que esté APROBADO
                if (strtoupper($tipoRecibo) === 'REFLECTIVO') {
                    $detalleAprobado = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)
                        ->where('estado', 'APROBADO')
                        ->first();
                    
                    if (!$detalleAprobado) {
                        \Log::info('🔍 [Filtro REFLECTIVO] No aprobado', ['prenda_id' => $prenda->id]);
                        continue; // Skip REFLECTIVO si no está aprobado
                    }
                }
                
                $resultados[] = [
                    'prenda_id' => $prenda->id,
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => $prenda->de_bodega,
                    'recibos' => $recibosDelTipo->map(function ($recibo) {
                        return [
                            'id' => $recibo->id,
                            'tipo_recibo' => $recibo->tipo_recibo,
                            'consecutivo_actual' => $recibo->consecutivo_actual,
                            'consecutivo_inicial' => $recibo->consecutivo_inicial,
                            'notas' => $recibo->notas,
                            'creado_en' => $recibo->created_at,
                        ];
                    })->toArray(),
                    'total_recibos' => $recibosDelTipo->count(),
                    'fecha_creacion' => $prenda->created_at,
                ];
            }
            
            return $resultados;
        })->values();

        return $prendasAgrupadas;
    }

    /**
     * Obtener tipo de operario del usuario
     */
    private function obtenerTipoOperario(User $usuario): string
    {
        if ($usuario->hasRole('cortador')) {
            return 'cortador';
        }

        if ($usuario->hasRole('costurero')) {
            return 'costurero';
        }

        if ($usuario->hasRole('bodeguero')) {
            return 'bodeguero';
        }

        if ($usuario->hasRole('costura-reflectivo')) {
            return 'costura-reflectivo';
        }

        return 'desconocido';
    }
}
