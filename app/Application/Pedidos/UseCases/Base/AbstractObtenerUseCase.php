<?php

namespace App\Application\Pedidos\UseCases\Base;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;

/**
 * AbstractObtenerUseCase
 * 
 * Clase base reutilizable para todos los Use Cases de OBTENCIÃ“N (queries)
 * 
 * PATRÃ“N: Template Method para estandarizar:
 * - Obtención y validación del pedido
 * - Enriquecimiento condicional de datos
 * - Respuesta estandarizada
 * 
 * Reduce ~70 lÃ­neas de código duplicado en cada Use Case
 * 
 * Antes: 4 Use Cases Ã— 80 lÃ­neas = 320 lÃ­neas
 * DespuÃ©s: 1 base + 4 concretas Ã— 15 lÃ­neas = 80 lÃ­neas
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
     * - obtenerOpciones() - QuÃ© datos incluir (prendas, epps, etc)
     * - construirRespuesta() - QuÃ© estructura retornar
     */
    protected function obtenerYEnriquecer(int $pedidoId): mixed
    {
        // 1. PASO COMÃšN: Obtener y validar pedido (retorna agregado)
        $pedido = $this->obtenerPedidoValidado($pedidoId);

        // 2. PASO COMÃšN: Obtener opciones de enriquecimiento
        $opciones = $this->obtenerOpciones();

        // 3. PASO PERSONALIZABLE: Enriquecer pedido
        $datosEnriquecidos = $this->enriquecerPedido($pedido, $opciones);

        // 4. PASO PERSONALIZABLE: Construir respuesta (pasando tanto el agregado como el ID para cargar modelo si es necesario)
        return $this->construirRespuesta($datosEnriquecidos, $pedidoId);
    }

    /**
     * PASO 1 (COMÃšN): Obtener y validar que el pedido existe
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
     * PASO 2 (PERSONALIZABLE): QuÃ© opciones de enriquecimiento usar
     * 
     * Subclases pueden sobrescribir para incluir/excluir datos especÃ­ficos
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
     * PASO 3 (COMÃšN): Enriquecer el pedido con datos opcionales
     */
    protected function enriquecerPedido($pedido, array $opciones): array
    {
        $datos = [
            'id' => $pedido->id(),
            'numero' => (string)$pedido->numero(),
            'clienteId' => $pedido->clienteId(),
            'estado' => $pedido->estado()->valor(),
            'descripcion' => (string)($pedido->descripcion() ?? ''),
            'totalPrendas' => $pedido->totalPrendas(),
            'totalArticulos' => $pedido->totalArticulos(),
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
        \Log::info('[AbstractObtenerUseCase] Buscando procesos del pedido', ['pedidoId' => $pedidoId]);
        
        $procesos = \App\Models\PedidosProcesosPrendaDetalle::whereHas('prenda', function ($q) use ($pedidoId) {
            $q->where('pedido_produccion_id', $pedidoId);
        })
            ->with(['prenda', 'tipoProceso', 'imagenes'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        \Log::info('[AbstractObtenerUseCase] Procesos encontrados', [
            'pedidoId' => $pedidoId,
            'cantidad' => count($procesos)
        ]);

        return $procesos;
    }

    /**
     * Obtener imÃ¡genes del pedido
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


