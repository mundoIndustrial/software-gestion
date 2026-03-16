<?php

namespace App\Application\Pedidos\QueryHandlers;

use App\Domain\Pedidos\Contracts\PedidoRepository;
use App\Domain\Pedidos\Contracts\ImagenesEppService;
use App\Application\Pedidos\Contracts\PedidoTransformService;
use App\Application\Pedidos\Contracts\PedidoEnricherService;
use App\Application\Pedidos\DTOs\DatosEdicionDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * ObtenerDatosEdicionQueryHandler
 * 
 * Handler para obtención de datos de pedido en modo edición
 */
class ObtenerDatosEdicionQueryHandler
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private ImagenesEppService $imagenesService,
        private PedidoTransformService $transformService,
        private PedidoEnricherService $enricherService,
    ) {}

    /**
     * Ejecutar query de datos para edición
     * 
     * @throws \DomainException
     */
    public function handle(int $pedidoId): DatosEdicionDTO
    {
        try {
            // 1. Obtener pedido con relaciones
            $pedido = $this->pedidoRepository->obtenerConRelaciones(
                $pedidoId,
                ['prendas.variantes', 'prendas.procesos.tipoProceso', 'epps.epp']
            );

            if (!$pedido) {
                throw new \DomainException('Pedido no encontrado');
            }

            // 2. Transformar prendas
            $prendasTransformadas = $this->transformarPrendas($pedido);

            // 3. Transformar EPPs
            $eppsTransformados = $this->transformarEpps($pedido);

            // 4. Crear y retornar DTO
            return new DatosEdicionDTO(
                id: $pedido->id,
                numero_pedido: $pedido->numero_pedido,
                cliente: $pedido->cliente,
                prendas: $prendasTransformadas,
                epps_transformados: $eppsTransformados,
                estado: $pedido->estado,
                area: $pedido->area,
            );

        } catch (\DomainException $e) {
            Log::warning('[ObtenerDatosEdicionQueryHandler] DomainException', [
                'pedido_id' => $pedidoId,
                'message' => $e->getMessage()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[ObtenerDatosEdicionQueryHandler] Error', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
            throw new \DomainException('Error al obtener datos de edición');
        }
    }

    private function transformarPrendas($pedido): array
    {
        $prendas = [];

        foreach ($pedido->prendas as $prenda) {
            // Obtener tallas con colores
            $tallaColores = DB::table('prenda_pedido_talla_colores as ptc')
                ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                ->where('pt.prenda_pedido_id', $prenda->id)
                ->select([
                    'ptc.id', 'ptc.prenda_pedido_talla_id', 'pt.genero',
                    'pt.talla', 'ptc.tela_id', 'ptc.tela_nombre',
                    'ptc.color_id', 'ptc.color_nombre', 'ptc.cantidad'
                ])
                ->get()
                ->toArray();

            $prendas[] = array_merge(
                $prenda->toArray(),
                [
                    'talla_colores' => $tallaColores,
                ]
            );
        }

        return $prendas;
    }

    private function transformarEpps($pedido): array
    {
        $epps = [];

        if (!$pedido->epps) {
            return $epps;
        }

        foreach ($pedido->epps as $pedidoEpp) {
            $epp = $pedidoEpp->epp;

            if (!$epp) {
                continue;
            }

            $imagenes = $this->imagenesService->obtenerImagenesEpp($pedidoEpp->id);

            $epps[] = [
                'id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                'cantidad' => $pedidoEpp->cantidad ?? 0,
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                'imagenes' => $imagenes,
            ];
        }

        return $epps;
    }
}
