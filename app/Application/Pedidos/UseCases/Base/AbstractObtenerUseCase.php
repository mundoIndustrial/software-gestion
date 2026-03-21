<?php

namespace App\Application\Pedidos\UseCases\Base;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * AbstractObtenerUseCase
 * 
 * Clase base reutilizable para todos los Use Cases de Obtencion (queries)
 * 
 * Patrón: Template Method para estandarizar:
 * - Obtención y validación del pedido
 * - Enriquecimiento condicional de datos
 * - Respuesta estandarizada
 * 
 * Reduce ~70 lineas de código duplicado en cada Use Case
 * 
 * Antes: 4 Use Cases  80 lineas = 320 lineas
 * despues: 1 base + 4 concretas  15 lineas = 80 lineas
 * Reducción: 75% menos código
 */
abstract class AbstractObtenerUseCase
{
    protected PedidoRepository $pedidoRepository;

    public function __construct(PedidoRepository $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Template Method - Define el flujo de obtención
     * 
     * Cada subclase solo necesita sobrescribir:
     * - obtenerOpciones() - que datos incluir (prendas, epps, etc)
     * - construirRespuesta() - que estructura retornar
     */
    protected function obtenerYEnriquecer(int $pedidoId): mixed
    {
        // 1. PASO comun: Obtener y validar pedido (retorna agregado)
        $pedido = $this->obtenerPedidoValidado($pedidoId);

        // 2. PASO comun: Obtener opciones de enriquecimiento
        $opciones = $this->obtenerOpciones();

        // 3. PASO PERSONALIZABLE: Enriquecer pedido
        $datosEnriquecidos = $this->enriquecerPedido($pedido, $opciones);

        // 4. PASO PERSONALIZABLE: Construir respuesta (pasando tanto el agregado como el ID para cargar modelo si es necesario)
        return $this->construirRespuesta($datosEnriquecidos, $pedidoId);
    }

    /**
     * PASO 1 (comun): Obtener y validar que el pedido existe
     */
    private function obtenerPedidoValidado(int $pedidoId)
    {
        $pedido = $this->pedidoRepository->porId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado", 404);
        }

        return $pedido;
    }

    /**
     * PASO 2 (PERSONALIZABLE): que opciones de enriquecimiento usar
     * 
     * Subclases pueden sobrescribir para incluir/excluir datos especificos
     */
    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => false,
            'incluirEpps' => false,
            'incluirProcesos' => false,
            'incluirImagenes' => false,
        ];
    }

    /**
     * PASO 3 (comun): Enriquecer el pedido con datos opcionales
     */
    protected function enriquecerPedido($pedido, array $opciones): array
    {
        // Para obtener el estado original de BD, cargar el modelo Eloquent
        $modeloEloquent = null;
        if (method_exists($pedido, 'id') && $pedido->id()) {
            $modeloEloquent = \App\Models\PedidoProduccion::find($pedido->id());
        }
        
        $datos = [
            'id' => $pedido->id(),
            'numero' => $pedido->numero() && !$pedido->numero()->esVacio() 
                ? (string)$pedido->numero() 
                : null,
            'clienteId' => $pedido->clienteId(),
            'estado' => $modeloEloquent ? $modeloEloquent->estado : $pedido->estado()->valor(),
            'descripcion' => (string)($pedido->descripcion() ?? ''),
            'totalPrendas' => $pedido->totalPrendas(),
            'totalArticulos' => $pedido->totalArticulos(),
            'forma_de_pago' => $this->obtenerFormaDePago($pedido->id()),
        ];

        // Enriquecimiento condicional - Solo si se especifica
        if ($opciones['incluirPrendas'] ?? false) {
            $datos['prendas'] = $this->obtenerPrendas($pedido->id());
        }

        if ($opciones['incluirEpps'] ?? false) {
            $datos['epps'] = $this->obtenerEpps($pedido->id());
        }

        if ($opciones['incluirProcesos'] ?? false) {
            $datos['procesos'] = $this->obtenerProcesos($pedido->id());
        }

        if ($opciones['incluirImagenes'] ?? false) {
            $datos['imagenes'] = $this->obtenerImagenes($pedido->id());
        }

        return $datos;
    }

    /**
     * PASO 4 (PERSONALIZABLE): Construir estructura de respuesta
     * 
     * Subclases pueden retornar DTO, array, modelo, etc.
     * Recibe el array de datos enriquecidos y el ID del pedido para cargar el modelo Eloquent si lo necesita
     */
    abstract protected function construirRespuesta(array $datosEnriquecidos, $pedidoIdOModelo): mixed;

    /**
     * Obtener prendas del pedido con relaciones
     * 
     * NOTA IMPORTANTE:
     * - Filtra prendas con de_bodega=TRUE para el rol CORTADOR
     * - Los demás roles ven TODAS las prendas
     */
    protected function obtenerPrendas(int $pedidoId): array
    {
        // Obtener el usuario autenticado
        $usuario = \Illuminate\Support\Facades\Auth::user();
        $esCortador = $usuario && $usuario->hasRole('cortador');

        $esApiOperario = false;
        try {
            $esApiOperario = request()->is('api/operario/*');
        } catch (\Exception $e) {
            $esApiOperario = false;
        }

        $queryBuilder = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedidoId);

        // FILTRO: Si el usuario es CORTADOR, excluir prendas de bodega (de_bodega = TRUE)
        if ($esCortador && !$esApiOperario) {
            $queryBuilder->where('de_bodega', false);
            
            \Log::info('[AbstractObtenerUseCase::obtenerPrendas] Filtrando prendas de bodega para CORTADOR', [
                'pedido_id' => $pedidoId,
                'usuario' => $usuario->name,
            ]);
        }

        $prendas = $queryBuilder->with([
                'procesos' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'tallas',
                'variantes',
                'coloresTelas' => function ($q) {
                    $q->with(['color', 'tela', 'fotos']);
                },
                'fotos',
                'entrega' => function ($q) {
                    $q->with(['usuario:id,name']);
                }
            ])
            ->get()
            ->map(function ($prenda) {
                // Asegurar que los datos de entrega se serialicen correctamente
                $prendaArray = $prenda->toArray();
                
                if ($prenda->entrega) {
                    $prendaArray['entrega'] = [
                        'id' => $prenda->entrega->id,
                        'prenda_pedido_id' => $prenda->entrega->prenda_pedido_id,
                        'entregado' => $prenda->entrega->entregado,
                        'fecha_entrega' => $prenda->entrega->fecha_entrega,
                        'usuario_id' => $prenda->entrega->usuario_id,
                        'usuario' => $prenda->entrega->usuario ? [
                            'id' => $prenda->entrega->usuario->id,
                            'name' => $prenda->entrega->usuario->name
                        ] : null
                    ];
                }
                
                return $prendaArray;
            })
            ->toArray();
        
        // DEBUG: Verificar datos de entrega específicamente
        foreach ($prendas as $index => $prenda) {
            if (isset($prenda['entrega'])) {
                \Log::debug("[obtenerPrendas] Prenda {$index} - Datos de entrega:", [
                    'prenda_id' => $prenda['id'],
                    'entrega_existe' => !empty($prenda['entrega']),
                    'entregado' => $prenda['entrega']['entregado'] ?? null,
                    'usuario_id' => $prenda['entrega']['usuario_id'] ?? null,
                    'usuario_datos' => $prenda['entrega']['usuario'] ?? null,
                    'usuario_nombre' => $prenda['entrega']['usuario']['name'] ?? null
                ]);
            } else {
                \Log::debug("[obtenerPrendas] Prenda {$index} - Sin datos de entrega");
            }
        }
        
        \Log::debug('[obtenerPrendas] QUERY RESULT', [
            'pedido_id' => $pedidoId,
            'prendas_count' => count($prendas),
            'prendas' => $prendas
        ]);

        // Agregar telas_array a cada prenda construido desde coloresTelas
        // y asegurar que de_bodega sea incluido
        foreach ($prendas as &$prenda) {
            $telasArray = [];
            
            // DEBUG: Verificar cuál es la clave exacta en el array
            $coloresTelasKey = null;
            if (isset($prenda['colores_telas'])) {
                $coloresTelasKey = 'colores_telas';
            } elseif (isset($prenda['coloresTelas'])) {
                $coloresTelasKey = 'coloresTelas';
            }
            
            \Log::debug('[obtenerPrendas] Buscando colores_telas', [
                'prenda_id' => $prenda['id'],
                'claves_disponibles' => array_keys($prenda),
                'clave_encontrada' => $coloresTelasKey,
                'tiene_colores_telas' => isset($prenda['colores_telas']),
                'tiene_coloresTelas' => isset($prenda['coloresTelas']),
                'de_bodega' => $prenda['de_bodega'] ?? 'NO EXISTE'
            ]);
            
            if ($coloresTelasKey && isset($prenda[$coloresTelasKey]) && is_array($prenda[$coloresTelasKey])) {
                foreach ($prenda[$coloresTelasKey] as $colorTela) {
                    $telaNombre = $colorTela['tela']['nombre'] ?? 'N/A';
                    $colorNombre = $colorTela['color']['nombre'] ?? 'N/A';
                    $telaReferencia = $colorTela['tela']['referencia'] ?? 'N/A';
                    
                    // Obtener fotos del color-tela
                    $fotos = [];
                    if (isset($colorTela['fotos']) && is_array($colorTela['fotos'])) {
                        foreach ($colorTela['fotos'] as $foto) {
                            $fotos[] = [
                                'id' => $foto['id'],
                                'ruta_webp' => $foto['ruta_webp'] ?? $foto['ruta_original'] ?? '',
                                'ruta_original' => $foto['ruta_original'] ?? '',
                                'url' => $foto['url'] ?? $foto['ruta_webp'] ?? $foto['ruta_original'] ?? '',
                                'orden' => $foto['orden'] ?? 1
                            ];
                        }
                    }
                    
                    $telasArray[] = [
                        'nombre' => $telaNombre,
                        'color' => $colorNombre,
                        'referencia' => $telaReferencia,
                        'imagenes' => $fotos
                    ];
                }
                
                \Log::debug('[obtenerPrendas] telas_array construido', [
                    'prenda_id' => $prenda['id'],
                    'cantidad_telas' => count($telasArray),
                    'telas' => $telasArray
                ]);
            }
            
            $prenda['telas_array'] = $telasArray;
            
            // Asegurar que de_bodega siempre esté presente
            if (!isset($prenda['de_bodega'])) {
                $prenda['de_bodega'] = false;
            }
            
            // Agregar campo 'origen' basado en de_bodega
            // Si de_bodega es true = "bodega", si es false = "confeccion"
            $prenda['origen'] = ($prenda['de_bodega'] === true || $prenda['de_bodega'] === 1 || $prenda['de_bodega'] === '1') 
                ? 'bodega' 
                : 'confeccion';
        }

        return $prendas;
    }

    /**
     * Obtener EPPs del pedido
     */
    protected function obtenerEpps(int $pedidoId): array
    {
        $epps = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->with(['epp', 'imagenes'])
            ->get()
            ->toArray();

        return $epps;
    }

    /**
     * Obtener procesos del pedido
     */
    protected function obtenerProcesos(int $pedidoId): array
    {
        \Log::info('[AbstractObtenerUseCase] Buscando procesos del pedido', ['pedidoId' => $pedidoId]);
        
        $procesosModelos = \App\Models\PedidosProcesosPrendaDetalle::whereHas('prenda', function ($q) use ($pedidoId) {
            $q->where('pedido_produccion_id', $pedidoId);
        })
            ->with(['prenda', 'tipoProceso', 'imagenes', 'tallas'])  // Cargar tallas desde relación
            ->orderBy('created_at', 'desc')
            ->get();

        // Transformar procesos para convertir tallas al formato correcto
        $procesos = $procesosModelos->map(function ($proc) {
            $procArray = $proc->toArray();
            
            \Log::info('[AbstractObtenerUseCase] Proceso antes de transformar tallas', [
                'proceso_id' => $procArray['id'],
                'tallas_crudas' => $procArray['tallas'] ?? 'no encontradas',
                'cantidad_tallas_crudas' => isset($procArray['tallas']) ? count($procArray['tallas']) : 0
            ]);
            
            // Transformar tallas de array a objeto indexado por genero
            $tallasTransformadas = [
                'dama' => [],
                'caballero' => [],
                'unisex' => []
            ];
            
            if (isset($procArray['tallas']) && is_array($procArray['tallas'])) {
                foreach ($procArray['tallas'] as $talla) {
                    $genero = strtolower($talla['genero'] ?? 'dama');
                    if (!isset($tallasTransformadas[$genero])) {
                        $tallasTransformadas[$genero] = [];
                    }
                    $tallasTransformadas[$genero][$talla['talla']] = (int)$talla['cantidad'];
                }
            }
            
            \Log::info('[AbstractObtenerUseCase] Proceso después de transformar tallas', [
                'proceso_id' => $procArray['id'],
                'tallas_transformadas' => $tallasTransformadas,
                'cantidad_dama' => count($tallasTransformadas['dama']),
                'cantidad_caballero' => count($tallasTransformadas['caballero']),
                'cantidad_unisex' => count($tallasTransformadas['unisex'])
            ]);
            
            $procArray['tallas'] = $tallasTransformadas;
            return $procArray;
        })->toArray();

        \Log::info('[AbstractObtenerUseCase] Procesos encontrados', [
            'pedidoId' => $pedidoId,
            'cantidad' => count($procesos)
        ]);

        return $procesos;
    }

    /**
     * Obtener imagenes del pedido
     */
    protected function obtenerImagenes(int $pedidoId): array
    {
        $imagenes = \App\Models\PrendaFotoPedido::whereHas('prenda', function ($q) use ($pedidoId) {
            $q->where('pedido_produccion_id', $pedidoId);
        })
            ->get()
            ->toArray();

        return $imagenes;
    }

    /**
     * Obtener forma de pago del pedido desde el modelo Eloquent
     */
    protected function obtenerFormaDePago(int $pedidoId): ?string
    {
        $pedido = \App\Models\PedidoProduccion::find($pedidoId);
        return $pedido ? ($pedido->forma_de_pago ?? null) : null;
    }
}


