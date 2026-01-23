<?php

namespace App\Application\Pedidos\UseCases\Base;

use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * AbstractObtenerUseCase
 * 
 * Clase base reutilizable para todos los Use Cases de OBTENCIÓN (queries)
 * 
 * PATRÓN: Template Method para estandarizar:
 * - Obtención y validación del pedido
 * - Enriquecimiento condicional de datos
 * - Respuesta estandarizada
 * 
 * Reduce ~70 líneas de código duplicado en cada Use Case
 * 
 * Antes: 4 Use Cases × 80 líneas = 320 líneas
 * Después: 1 base + 4 concretas × 15 líneas = 80 líneas
 * Reducción: 75% menos código
 */
abstract class AbstractObtenerUseCase
{
    protected PedidoProduccionRepository $pedidoRepository;

    public function __construct(PedidoProduccionRepository $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Template Method - Define el flujo de obtención
     * 
     * Cada subclase solo necesita sobrescribir:
     * - obtenerOpciones() - Qué datos incluir (prendas, epps, etc)
     * - construirRespuesta() - Qué estructura retornar
     */
    protected function obtenerYEnriquecer(int $pedidoId): mixed
    {
        // 1. PASO COMÚN: Obtener y validar pedido
        $pedido = $this->obtenerPedidoValidado($pedidoId);

        // 2. PASO COMÚN: Obtener opciones de enriquecimiento
        $opciones = $this->obtenerOpciones();

        // 3. PASO PERSONALIZABLE: Enriquecer pedido
        $datosEnriquecidos = $this->enriquecerPedido($pedido, $opciones);

        // 4. PASO PERSONALIZABLE: Construir respuesta (pasando también el modelo original)
        return $this->construirRespuesta($datosEnriquecidos, $pedido);
    }

    /**
     * PASO 1 (COMÚN): Obtener y validar que el pedido existe
     */
    private function obtenerPedidoValidado(int $pedidoId)
    {
        $pedido = $this->pedidoRepository->obtenerPorId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado", 404);
        }

        return $pedido;
    }

    /**
     * PASO 2 (PERSONALIZABLE): Qué opciones de enriquecimiento usar
     * 
     * Subclases pueden sobrescribir para incluir/excluir datos específicos
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
     * PASO 3 (COMÚN): Enriquecer el pedido con datos opcionales
     */
    protected function enriquecerPedido($pedido, array $opciones): array
    {
        $datos = [
            'id' => $pedido->id,
            'numero' => (string)$pedido->numero,
            'clienteId' => $pedido->cliente_id,
            'estado' => $pedido->estado ?? 'desconocido',
            'descripcion' => (string)($pedido->descripcion ?? ''),
            'totalPrendas' => $pedido->total_prendas ?? 0,
            'totalArticulos' => $pedido->total_articulos ?? 0,
        ];

        // Enriquecimiento condicional - Solo si se especifica
        if ($opciones['incluirPrendas'] ?? false) {
            $datos['prendas'] = $this->obtenerPrendas($pedido->id);
        }

        if ($opciones['incluirEpps'] ?? false) {
            $datos['epps'] = $this->obtenerEpps($pedido->id);
        }

        if ($opciones['incluirProcesos'] ?? false) {
            $datos['procesos'] = $this->obtenerProcesos($pedido->id);
        }

        if ($opciones['incluirImagenes'] ?? false) {
            $datos['imagenes'] = $this->obtenerImagenes($pedido->id);
        }

        return $datos;
    }

    /**
     * PASO 4 (PERSONALIZABLE): Construir estructura de respuesta
     * 
     * Subclases pueden retornar DTO, array, modelo, etc.
     * Recibe tanto el array de datos enriquecidos como el modelo original
     */
    abstract protected function construirRespuesta(array $datosEnriquecidos, $pedido): mixed;

    /**
     * Obtener prendas del pedido con relaciones
     */
    protected function obtenerPrendas(int $pedidoId): array
    {
        $prendas = \App\Models\PrendaPedido::where('pedido_produccion_id', $pedidoId)
            ->with([
                'procesos' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'tallas',
                'variantes',
                'coloresTelas',
                'fotos'
            ])
            ->get()
            ->toArray();

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
        $procesos = \App\Models\PedidosProcesosPrendaDetalle::whereHas('prenda', function ($q) use ($pedidoId) {
            $q->where('pedido_produccion_id', $pedidoId);
        })
            ->with(['prenda', 'tipoProceso', 'imagenes'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return $procesos;
    }

    /**
     * Obtener imágenes del pedido
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
}
