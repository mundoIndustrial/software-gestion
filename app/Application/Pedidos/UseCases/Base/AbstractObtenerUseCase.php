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
            try {
                $modeloEloquent = \App\Models\PedidoProduccion::find($pedido->id());
            } catch (\Throwable $e) {
                $modeloEloquent = null;
            }
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
            try {
                $datos['prendas'] = $this->obtenerPrendas($pedido->id());
            } catch (\Throwable $e) {
                $datos['prendas'] = [];
            }
        }

        if ($opciones['incluirEpps'] ?? false) {
            try {
                $datos['epps'] = $this->obtenerEpps($pedido->id());
            } catch (\Throwable $e) {
                $datos['epps'] = [];
            }
        }

        if ($opciones['incluirProcesos'] ?? false) {
            try {
                $datos['procesos'] = $this->obtenerProcesos($pedido->id());
            } catch (\Throwable $e) {
                $datos['procesos'] = [];
            }
        }

        if ($opciones['incluirImagenes'] ?? false) {
            try {
                $datos['imagenes'] = $this->obtenerImagenes($pedido->id());
            } catch (\Throwable $e) {
                $datos['imagenes'] = [];
            }
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
        

        // Agregar telas_array a cada prenda construido desde coloresTelas
        // y asegurar que de_bodega sea incluido
        foreach ($prendas as &$prenda) {
            $telasArray = [];

            // Verificar cuál es la clave exacta en el array
            $coloresTelasKey = null;
            if (isset($prenda['colores_telas'])) {
                $coloresTelasKey = 'colores_telas';
            } elseif (isset($prenda['coloresTelas'])) {
                $coloresTelasKey = 'coloresTelas';
            }
            
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
        $procesosModelos = \App\Models\PedidosProcesosPrendaDetalle::whereHas('prenda', function ($q) use ($pedidoId) {
            $q->where('pedido_produccion_id', $pedidoId);
        })
            ->with(['prenda', 'tipoProceso', 'imagenes', 'tallas'])  // Cargar tallas desde relación
            ->orderBy('created_at', 'desc')
            ->get();

        // Transformar procesos para convertir tallas al formato correcto
        $procesos = $procesosModelos->map(function ($proc) {
            $procArray = $proc->toArray();

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

            $procArray['tallas'] = $tallasTransformadas;
            return $procArray;
        })->toArray();

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
        try {
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);
            return $pedido ? ($pedido->forma_de_pago ?? null) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}





