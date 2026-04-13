<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\Exceptions\JsonContieneObjetoFileException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Fachada del módulo de imágenes de pedidos.
 * Mantiene la API pública histórica delegando a servicios especializados.
 */
class PedidoImagenesService
{
    public function __construct(
        private ProcesoImagenService $procesoImagenService,
        private PedidoImagenesPrendasService $prendasService,
        private PedidoImagenesEppService $eppService,
        private PedidoImagenesColoresService $coloresService,
    ) {}

    public function crearCarpetasPedido(int $pedidoId): void
    {
        $basePath = "pedidos/{$pedidoId}";
        $carpetas = ['prenda', 'tela', 'proceso', 'epp'];

        foreach ($carpetas as $carpeta) {
            $rutaCompleta = "{$basePath}/{$carpeta}";
            if (Storage::disk('public')->exists($rutaCompleta)) {
                continue;
            }

            try {
                Storage::disk('public')->makeDirectory($rutaCompleta);
            } catch (\Exception $e) {
                Log::warning('[PedidoImagenesService] Error creando carpeta', [
                    'pedido_id' => $pedidoId,
                    'carpeta' => $rutaCompleta,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function procesarYAsignarImagenes(Request $request, int $pedidoId, array $items): void
    {
        $this->prendasService->procesarYAsignarImagenes($request, $pedidoId, $items);
    }

    public function procesarImagenesPrenda(Request $request, int $pedidoId, int $itemIdx, $prenda): void
    {
        $this->prendasService->procesarImagenesPrenda($request, $pedidoId, $itemIdx, $prenda);
    }

    public function procesarYAsignarEpps(Request $request, int $pedidoId, array $epps): void
    {
        $this->eppService->procesarYAsignarEpps($request, $pedidoId, $epps);
    }

    public function validarJsonSinFiles(array $datos, string $ruta = ''): void
    {
        foreach ($datos as $key => $valor) {
            $rutaActual = $ruta ? "{$ruta}.{$key}" : $key;

            if (is_array($valor)) {
                $this->validarJsonSinFiles($valor, $rutaActual);
            }

            if (is_object($valor)) {
                throw JsonContieneObjetoFileException::enRuta($rutaActual);
            }
        }
    }

    public function procesarImagenesDeEpps($request, int $pedidoId, array $epps): void
    {
        $this->eppService->procesarImagenesDeEpps($request, $pedidoId, $epps);
    }

    public function procesarImagenesPorTalla($request, int $pedidoId, array $prendas): int
    {
        $contadorTotal = 0;

        foreach ($prendas as $prendaIdx => $prenda) {
            $procesos = $prenda['procesos'] ?? [];
            $procesoNumerico = 0;

            foreach ($procesos as $procesoKey => $proceso) {
                $datosProceso = is_array($proceso['datos'] ?? null) ? $proceso['datos'] : $proceso;
                $modeTallas = $datosProceso['modo_tallas'] ?? $proceso['modo_tallas'] ?? 'generico';
                $datosExtendidos = $datosProceso['datosExtendidos'] ?? $datosProceso['datos_extendidos'] ?? $proceso['datosExtendidos'] ?? $proceso['datos_extendidos'] ?? null;

                if ($modeTallas === 'especifico' && !empty($datosExtendidos)) {
                    $contadorTotal += $this->procesoImagenService->procesarImagenesPorTalla(
                        $request,
                        $pedidoId,
                        $prendaIdx,
                        $procesoNumerico,
                        $procesoKey,
                        $datosExtendidos
                    );
                }
                $procesoNumerico++;
            }
        }

        return $contadorTotal;
    }

    public function procesarImagenesDeColores($request, int $pedidoId, array $prendas): void
    {
        $this->coloresService->procesarImagenesDeColores($request, $pedidoId, $prendas);
    }

    public function procesarImagenesNuevasPrendas($request, array $nuevasPrendasIds, array $items): void
    {
        $this->prendasService->procesarImagenesNuevasPrendas($request, $nuevasPrendasIds, $items);
    }

    public function procesarImagenesDeProcesos($request, int $pedidoId, array $procesos, int $prendaIndex, int $prendaId = 0): void
    {
        $this->prendasService->procesarImagenesDeProcesos($request, $pedidoId, $procesos, $prendaIndex, $prendaId);
    }
}
