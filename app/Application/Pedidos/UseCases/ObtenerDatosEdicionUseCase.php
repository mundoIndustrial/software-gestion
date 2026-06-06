<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\ObtenerDatosEdicionUseCaseContract;

use App\Application\Pedidos\DTOs\ObtenerDatosEdicionResponse;
use App\Application\Pedidos\Exceptions\ObtenerDatosEdicionException;
use App\Application\Pedidos\Services\PrendaTransformadorService;
use App\Application\Pedidos\Services\EppTransformadorService;
use App\Application\Services\Pedidos\MapearPedidoEdicionService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerDatosEdicionUseCase
 * Caso de uso para obtener datos completos de un pedido para edificacion.
 * Responsabilidades:
 * - Obtener el pedido con todas sus relaciones
 * - Enriquecer prendas y sus variantes
 * - Transformar EPPs con imagenes
 * - Preparar datos para formulario de edificacion
 * Orquesta los servicios de trasformacion.
 */
class ObtenerDatosEdicionUseCase implements ObtenerDatosEdicionUseCaseContract
{
    private PrendaTransformadorService $prendaTransformador;
    private EppTransformadorService $eppTransformador;

    public function __construct(
        PrendaTransformadorService $prendaTransformador,
        EppTransformadorService $eppTransformador,
        private MapearPedidoEdicionService $mapearPedidoEdicionService
    ) {
        $this->prendaTransformador = $prendaTransformador;
        $this->eppTransformador = $eppTransformador;
    }

    /**
     * Ejecuta el caso de uso
     * @param int $id ID del pedido
     * @return ObtenerDatosEdicionResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Si no existe
     */
    public function ejecutar(int $id): ObtenerDatosEdicionResponse
    {
        try {
            // 1. Obtener el pedido con relaciones necesarias
            $pedido = $this->obtenerPedido($id);

            // 2. Enriquecer prendas (variantes, talla_colores)
            $this->prendaTransformador->enriquecerPrendas($pedido->prendas);

            // 3. Mapear pedido para modo edición usando la estructura normalizada del frontend
            $pedidoMapeado = $this->mapearPedidoEdicionService->mapearPedidoParaEdicion($pedido);

            // 4. Transformar EPPs para compatibilidad adicional
            $eppsList = $this->eppTransformador->transformarEpps($pedido->epps ?? []);

            // 5. Convertir a array y reemplazar relaciones crudas por la estructura mapeada
            $datosRespuesta = $pedido->toArray();
            $datosRespuesta['prendas'] = $pedidoMapeado['prendas'] ?? [];
            $datosRespuesta['epps'] = $pedidoMapeado['epps'] ?? [];
            $datosRespuesta['cliente_nombre'] = $pedidoMapeado['cliente_nombre'] ?? '';
            $datosRespuesta['procesos'] = $this->extraerProcesosDesdePrendas($datosRespuesta['prendas']);
            $datosRespuesta['epps_transformados'] = $eppsList;

            Log::info('[ObtenerDatosEdicionUseCase] Datos de edicficacion obtenidos', [
                'pedido_id' => $pedido->id,
                'prendas_count' => count($pedido->prendas ?? []),
                'epps_count' => count($eppsList)
            ]);

            return new ObtenerDatosEdicionResponse($datosRespuesta);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ObtenerDatosEdicionUseCase] Pedido no encontrado', ['pedido_id' => $id]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[ObtenerDatosEdicionUseCase] Error inesperado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw ObtenerDatosEdicionException::fromThrowable($e);
        }
    }

    /**
     * Obtiene el pedido con todas las relaciones necesarias para edificacion
     */
    private function obtenerPedido(int $id): PedidoProduccion
    {
        return PedidoProduccion::with([
            'prendas.variantes',
            'prendas.coloresTelas.fotos',
            'prendas.procesos.tipoProceso',
            'prendas.procesos.tallas.coloresAsignados',
            'prendas.procesos.tallas.imagenes',
            'prendas.procesos.imagenes',
            'prendas.fotos',
            'prendas.telaFotos',
            'prendas.tallas.coloresAsignados',
            'epps.epp',
            'asesor:id,name',
            'cliente:id,nombre'
        ])->findOrFail($id);
    }

    private function extraerProcesosDesdePrendas(array $prendas): array
    {
        $procesos = [];

        foreach ($prendas as $prenda) {
            if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
                continue;
            }

            foreach ($prenda['procesos'] as $proceso) {
                $procesos[] = $proceso;
            }
        }

        return $procesos;
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {ObtenerDatosEdicionUseCase}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}




