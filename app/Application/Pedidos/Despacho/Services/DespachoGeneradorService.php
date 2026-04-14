<?php

namespace App\Application\Pedidos\Despacho\Services;

use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Application\Pedidos\Despacho\DTOs\FilaDespachoDTO;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * DespachoGeneradorService
 * Domain Service que encapsula la lógica de negocio para generar
 * la estructura de despacho a partir de un pedido.
 * Responsabilidades:
 * - Obtener prendas con tallas
 * - Obtener EPP
 * - Unificar en una estructura común (FilaDespachoDTO)
 * - Validar que los datos sean consistentes
 */
class DespachoGeneradorService
{
    public function __construct(
        private ObtenerPedidoUseCase $obtenerPedidoUseCase
    ) {}
    /**
     * Generar todas las filas de despacho (prendas + EPP)
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarFilasDespacho(PedidoProduccion $pedido): Collection
    {
        $filas = collect();

        // Agregar prendas con tallas
        $this->agregarPrendas($pedido, $filas);

        // Agregar EPP
        $this->agregarEpp($pedido, $filas);

        return $filas;
    }

    /**
     * Generar filas solo de prendas
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarPrendas(PedidoProduccion $pedido): Collection
    {
        $filas = collect();
        $this->agregarPrendas($pedido, $filas);
        return $filas;
    }

    /**
     * Generar filas solo de EPP
     * @param PedidoProduccion $pedido
     * @return Collection<FilaDespachoDTO>
     */
    public function generarEpp(PedidoProduccion $pedido): Collection
    {
        $filas = collect();
        $this->agregarEpp($pedido, $filas);
        return $filas;
    }

    /**
     * Agregar prendas con tallas a la colección de filas
     */
    private function agregarPrendas(PedidoProduccion $pedido, Collection $filas): void
    {
        try {
            // Obtener datos COMPLETOS del pedido (con procesos, variantes, etc)
            $datos = $this->obtenerPedidoUseCase->ejecutar($pedido->id);
            
            if (!isset($datos->prendas) || !is_array($datos->prendas)) {
                Log::warning('[DespachoGenerador] No se encontraron prendas en ObtenerPedidoUseCase', [
                    'pedido_id' => $pedido->id
                ]);
                return;
            }

            foreach ($datos->prendas as $prendaEnriquecida) {
                // La prenda ya viene con procesos, variantes, manga, broche, bolsillos desde ObtenerPedidoUseCase
                $variantes = $prendaEnriquecida['variantes'] ?? [];
                
                if (count($variantes) > 0) {
                    // Una fila por variante (que representa cada TALLA)
                    foreach ($variantes as $variante) {
                        $talla = $variante['talla'] ?? '—';
                        $cantidad = $variante['cantidad'] ?? 0;
                        $tallaId = $variante['talla_id'] ?? null;  //  Obtener ID de la talla
                        $genero = $variante['genero'] ?? null;  //  Obtener género
                        
                        $filas->push(new FilaDespachoDTO(
                            tipo: 'prenda',
                            id: $prendaEnriquecida['id'] ?? null,
                            tallaId: $tallaId,  //  Usar ID de la talla
                            descripcion: "{$prendaEnriquecida['nombre_prenda']} - {$talla}",
                            cantidadTotal: (int)$cantidad,
                            talla: $talla,
                            genero: $genero,  //  Usar género de la talla
                            objetoPrenda: $prendaEnriquecida,  //  Datos completos con procesos, variantes, etc
                            objetoTalla: null,
                            objetoEpp: null,
                        ));
                    }
                } else {
                    // Fallback: Sin variantes
                    $filas->push(new FilaDespachoDTO(
                        tipo: 'prenda',
                        id: $prendaEnriquecida['id'] ?? null,
                        tallaId: null,
                        descripcion: $prendaEnriquecida['nombre_prenda'] ?? 'Prenda sin nombre',
                        cantidadTotal: (int)($prendaEnriquecida['cantidad_total'] ?? 0),
                        talla: '—',
                        genero: null,
                        objetoPrenda: $prendaEnriquecida,
                        objetoTalla: null,
                        objetoEpp: null,
                    ));
                }
            }
        } catch (\Exception $e) {
            Log::error('[DespachoGenerador] Error al obtener prendas completas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: usar datos básicos del modelo
            $this->agregarPrendasFallback($pedido, $filas);
        }
    }
    
    /**
     * Fallback: Agregar prendas sin datos completos (si ObtenerPedidoUseCase falla)
     */
    private function agregarPrendasFallback(PedidoProduccion $pedido, Collection $filas): void
    {
        $prendas = $pedido->prendas()
            ->with(['prendaPedidoTallas'])
            ->get();

        foreach ($prendas as $prenda) {
            $tallas = $prenda->prendaPedidoTallas()->get();

            if ($tallas->count() > 0) {
                foreach ($tallas as $talla) {
                    $filas->push(new FilaDespachoDTO(
                        tipo: 'prenda',
                        id: $talla->id,
                        tallaId: $talla->id,
                        descripcion: "{$prenda->nombre_prenda} - {$talla->genero}",
                        cantidadTotal: $talla->cantidad,
                        talla: $talla->talla,
                        genero: $talla->genero,
                        objetoPrenda: $prenda->toArray(),
                        objetoTalla: $talla->toArray(),
                        objetoEpp: null,
                    ));
                }
            } else {
                $filas->push(new FilaDespachoDTO(
                    tipo: 'prenda',
                    id: $prenda->id,
                    tallaId: null,
                    descripcion: $prenda->nombre_prenda,
                    cantidadTotal: $prenda->cantidad ?? 0,
                    talla: '—',
                    genero: null,
                    objetoPrenda: $prenda->toArray(),
                    objetoTalla: null,
                    objetoEpp: null,
                ));
            }
        }
    }

    /**
     * Agregar EPP a la colección de filas
     */
    private function agregarEpp(PedidoProduccion $pedido, Collection $filas): void
    {
        $epps = $pedido->epps()
            ->withTrashed()
            ->with(['epp', 'homologadoDe.epp', 'homologaciones.epp'])
            ->get();

        \Log::info('[DESPACHO-EPP-BULK] Total de EPPs a procesar', [
            'total' => $epps->count(),
        ]);

        foreach ($epps as $pedidoEpp) {
            \Log::info('[DESPACHO-EPP-ITEM] ============ PROCESANDO EPP ============', [
                'epp_id' => $pedidoEpp->id,
                'nombre' => $pedidoEpp->epp->nombre_completo ?? 'N/A',
                'cantidad' => $pedidoEpp->cantidad,
                'homologado_de' => $pedidoEpp->homologado_de,
                'homologaciones_cargadas' => $pedidoEpp->relationLoaded('homologaciones'),
                'homologaciones_count' => $pedidoEpp->homologaciones ? count($pedidoEpp->homologaciones) : 0,
            ]);
            
            $descripcion = $pedidoEpp->epp->nombre_completo;
                if (!empty($pedidoEpp->epp->codigo)) {
                    $descripcion .= " ({$pedidoEpp->epp->codigo})";
                }
                
            // Construir historial de homologaciones
            $historialHomologaciones = [];
            $tieneHistorial = false;
            
            // Si este EPP es una homologación, obtener el historial completo
            if ($pedidoEpp->homologado_de !== null) {
                \Log::info('[DESPACHO-EPP-ITEM]  EPP ES UNA HOMOLOGACIÓN', [
                    'epp_id' => $pedidoEpp->id,
                    'homologado_de' => $pedidoEpp->homologado_de,
                ]);
                
                $tieneHistorial = true;
                
                // Buscar el EPP original usando queries directas con withTrashed()
                $original = $pedidoEpp;
                $intentos = 0;
                $maxIntentos = 100;
                
                while ($original && $original->homologado_de !== null && $intentos < $maxIntentos) {
                    $intentos++;
                    \Log::debug('[DESPACHO-EPP-ITEM] Buscando original', [
                        'actual_id' => $original->id,
                        'homologado_de' => $original->homologado_de,
                        'intento' => $intentos,
                    ]);
                    
                    // Query directa con withTrashed() para encontrar el padre (que podría estar soft-deleted)
                    $original = PedidoEpp::withTrashed()
                        ->with('epp')
                        ->find($original->homologado_de);
                    
                    if (!$original) {
                        \Log::warning('[DESPACHO-EPP-ITEM] homologado_de no encontrado', [
                            'buscado_id' => $pedidoEpp->homologado_de,
                        ]);
                        break;
                    }
                }
                
                if (!$original) {
                    \Log::error('[DESPACHO-EPP-ITEM] ❌ Original es null', ['epp_id' => $pedidoEpp->id]);
                    $tieneHistorial = false;
                } else if ($intentos >= $maxIntentos) {
                    \Log::warning('[DESPACHO-EPP-ITEM]  Loop infinito detectado', ['epp_id' => $pedidoEpp->id]);
                    $tieneHistorial = false;
                } else {
                    \Log::info('[DESPACHO-EPP-ITEM]  Original encontrado', [
                        'original_id' => $original->id,
                        'intentos' => $intentos,
                    ]);
                    
                    // Obtener todas las versiones a partir del original
                    $todasLasVersiones = $this->obtenerTodasLasVersiones($original);
                    
                    \Log::info('[DESPACHO-EPP-ITEM]  Versiones obtenidas', [
                        'total' => $todasLasVersiones->count(),
                        'versiones_ids' => $todasLasVersiones->pluck('id')->toArray(),
                    ]);
                    
                    foreach ($todasLasVersiones as $version) {
                        $historialHomologaciones[] = [
                            'pedido_epp_id' => $version->id,
                            'epp_id' => $version->epp_id,
                            'epp_nombre' => $version->epp->nombre_completo ?? $version->epp->nombre ?? 'N/A',
                            'cantidad' => $version->cantidad,
                            'fecha_creacion' => $version->created_at?->format('Y-m-d H:i') ?? 'N/A',
                            'observaciones' => $version->observaciones ?? 'N/A',
                            'deleted_at' => $version->deleted_at?->format('Y-m-d H:i') ?? null,
                            'es_original' => $version->id === $original->id,
                        ];
                    }
                    
                    \Log::info('[DESPACHO-EPP-ITEM]  HISTORIAL CONSTRUIDO FINAL', [
                        'epp_id' => $pedidoEpp->id,
                        'versiones_en_historial' => count($historialHomologaciones),
                        'historial_json' => json_encode($historialHomologaciones),
                    ]);
                }
            } else {
                \Log::info('[DESPACHO-EPP-ITEM] ℹ️ EPP sin homologación (es el ORIGINAL)', [
                    'epp_id' => $pedidoEpp->id,
                ]);
            }
            
            // IMPORTANTE: Si este EPP tiene versiones más nuevas que lo reemplazan, NO lo agregues como fila
            // Solo mostrar la versión FINAL (que puede tener historial de versiones anteriores)
            $tieneVersionesMasNuevas = PedidoEpp::withTrashed()
                ->where('homologado_de', $pedidoEpp->id)
                ->exists();
            
            if ($tieneVersionesMasNuevas) {
                \Log::info('[DESPACHO-EPP-ITEM] ↩️ Saltando EPP versión antigua (tiene homologaciones más nuevas)', [
                    'epp_id' => $pedidoEpp->id,
                ]);
                continue;
            }
            
            // Si el EPP está eliminado (deleted_at IS NOT NULL) y no tiene versiones más nuevas
            // (es decir, no fue homologado a otro EPP), entonces no debe aparecer en la vista
            if ($pedidoEpp->deleted_at !== null && !$tieneVersionesMasNuevas) {
                \Log::info('[DESPACHO-EPP-ITEM] 🗑️ EPP eliminado sin homologación - No se mostrará', [
                    'epp_id' => $pedidoEpp->id,
                    'deleted_at' => $pedidoEpp->deleted_at,
                ]);
                continue;
            }
                
                $filaDTO = new FilaDespachoDTO(
                    tipo: 'epp',
                    id: $pedidoEpp->id,
                    tallaId: null,
                    descripcion: $descripcion,
                    cantidadTotal: $pedidoEpp->cantidad,
                    talla: '—',
                    genero: null,
                    objetoPrenda: null,
                    objetoTalla: null,
                    objetoEpp: $pedidoEpp->toArray(),
                    tiene_historial: $tieneHistorial && count($historialHomologaciones) > 0,
                    historial_homologaciones: (count($historialHomologaciones) > 0) ? $historialHomologaciones : null,
                );
                
                \Log::info('[DESPACHO-EPP-ITEM] FilaDTO creada', [
                    'tiene_historial' => $filaDTO->tiene_historial,
                    'historial_count' => $filaDTO->historial_homologaciones ? count($filaDTO->historial_homologaciones) : 0,
                ]);
                
                $filas->push($filaDTO);
        }
    }
    
    /**
     * Obtener todas las versiones de un EPP usando QUERY DIRECTA a BD
     * IMPORTANTE: Incluir withTrashed() para obtener registros soft-deleted
     */
    private function obtenerTodasLasVersiones(PedidoEpp $original): Collection
    {
        \Log::info('[DESPACHO-EPP-QUERY] Obteniendo todas las versiones del original', [
            'original_id' => $original->id,
        ]);
        
        // Query directa para obtener el árbol completo de homologaciones
        $versiones = collect();
        $cola = [$original->id];
        $visitados = [];
        
        while (!empty($cola)) {
            $pedidoEppId = array_shift($cola);
            
            if (isset($visitados[$pedidoEppId])) {
                continue;
            }
            $visitados[$pedidoEppId] = true;
            
            // Cargar desde BD - IMPORTANTE: incluir withTrashed() para soft-deleted records
            $actual = PedidoEpp::withTrashed()->with('epp')->find($pedidoEppId);
            
            if (!$actual) {
                \Log::warning('[DESPACHO-EPP-QUERY] EPP no encontrado', ['id' => $pedidoEppId]);
                continue;
            }
            
            \Log::debug('[DESPACHO-EPP-QUERY] Visitando EPP', [
                'id' => $actual->id,
                'nombre' => $actual->epp->nombre_completo ?? 'N/A',
                'deleted_at' => $actual->deleted_at,
            ]);
            
            $versiones->push($actual);
            
            // Query para buscar todos los EPPs que tienen homologado_de = este EPP
            // IMPORTANTE: usar withTrashed() para incluir registros eliminados
            $homologacionesDirectas = PedidoEpp::withTrashed()
                ->where('homologado_de', $pedidoEppId)
                ->pluck('id');
            
            \Log::debug('[DESPACHO-EPP-QUERY] Homologaciones encontradas para', [
                'epp_id' => $pedidoEppId,
                'count' => $homologacionesDirectas->count(),
                'ids' => $homologacionesDirectas->toArray(),
            ]);
            
            foreach ($homologacionesDirectas as $homId) {
                if (!isset($visitados[$homId])) {
                    array_push($cola, $homId);
                }
            }
        }
        
        \Log::info('[DESPACHO-EPP-QUERY] Búsqueda completada', [
            'total_versiones' => $versiones->count(),
        ]);
        
        return $versiones;
    }
}
