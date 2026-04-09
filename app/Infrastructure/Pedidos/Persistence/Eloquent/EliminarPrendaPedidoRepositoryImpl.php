<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\EliminarPrendaPedidoRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PedidosProcessImagenes;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PrendaPedido;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class EliminarPrendaPedidoRepositoryImpl implements EliminarPrendaPedidoRepository
{
    public function eliminarDePedido(int $pedidoId, int $prendaId, string $motivo): array
    {
        return DB::transaction(function () use ($pedidoId, $prendaId, $motivo): array {
            $prenda = PrendaPedido::query()
                ->where('id', $prendaId)
                ->where('pedido_produccion_id', $pedidoId)
                ->firstOrFail();

            $pedido = PedidoProduccion::findOrFail($pedidoId);
            $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'Sin nombre';

            $this->registrarNovedadEliminacion($pedido, $nombrePrenda, $motivo);
            $this->eliminarFotosPrenda($prendaId);
            $this->eliminarFotosTelasYColores($prendaId);
            $this->eliminarTallasYVariantes($prendaId);

            $tallaColorIds = $this->obtenerTallaColorIdsDeProcesos($prendaId);
            $this->eliminarProcesosDePrenda($prendaId);
            $this->eliminarConsecutivosRecibo($pedidoId, $prendaId);
            $this->softDeleteDetallesBodega($pedidoId, $prendaId);
            $this->eliminarNotasBodegaPorTallaColor($tallaColorIds);

            $prenda->delete();

            Log::info('[EliminarPrendaPedidoRepository] Prenda eliminada', [
                'prenda_id' => $prendaId,
                'nombre' => $nombrePrenda,
                'pedido_id' => $pedidoId,
            ]);

            return [
                'success' => true,
                'message' => 'Prenda eliminada correctamente',
                'prenda_id' => $prendaId,
                'prenda_nombre' => $nombrePrenda,
                'motivo_registrado' => $motivo,
                'pedido_id' => $pedidoId,
            ];
        });
    }

    private function registrarNovedadEliminacion(PedidoProduccion $pedido, string $nombrePrenda, string $motivo): void
    {
        if ($this->esPedidoBorrador($pedido)) {
            return;
        }

        $mensaje = "[ELIMINADA PRENDA] {$nombrePrenda} - Motivo: {$motivo}";
        $pedido->novedades = $pedido->novedades
            ? $pedido->novedades . "\n\n" . $mensaje
            : $mensaje;
        $pedido->save();
    }

    private function esPedidoBorrador(PedidoProduccion $pedido): bool
    {
        if ($pedido->numero_pedido === null) {
            return true;
        }

        return strtolower((string) $pedido->estado) === 'borrador';
    }

    private function eliminarFotosPrenda(int $prendaId): void
    {
        $fotos = PrendaFotoPedido::query()
            ->where('prenda_pedido_id', $prendaId)
            ->get(['id', 'ruta_original', 'ruta_webp']);

        $this->eliminarArchivosFotos($fotos);
        PrendaFotoPedido::where('prenda_pedido_id', $prendaId)->delete();

        Log::info('[EliminarPrendaPedidoRepository] Imagenes de prenda eliminadas', [
            'cantidad' => $fotos->count(),
            'prenda_id' => $prendaId,
        ]);
    }

    private function eliminarFotosTelasYColores(int $prendaId): void
    {
        $colorTelasIds = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->pluck('id');

        if ($colorTelasIds->isNotEmpty()) {
            $fotosTelas = PrendaFotoTelaPedido::query()
                ->whereIn('prenda_pedido_colores_telas_id', $colorTelasIds)
                ->get(['id', 'ruta_original', 'ruta_webp']);

            $this->eliminarArchivosFotos($fotosTelas);
            PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $colorTelasIds)->delete();
        }

        DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->delete();
    }

    private function eliminarTallasYVariantes(int $prendaId): void
    {
        $tallasIds = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->pluck('id');

        if ($tallasIds->isNotEmpty()) {
            DB::table('prenda_pedido_talla_colores')
                ->whereIn('prenda_pedido_talla_id', $tallasIds)
                ->delete();
        }

        DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->delete();

        DB::table('prenda_pedido_variantes')
            ->where('prenda_pedido_id', $prendaId)
            ->delete();
    }

    private function obtenerTallaColorIdsDeProcesos(int $prendaId): Collection
    {
        return DB::table('pedidos_procesos_prenda_talla_colores as tc')
            ->join('pedidos_procesos_prenda_tallas as t', 't.id', '=', 'tc.pedidos_procesos_prenda_talla_id')
            ->join('pedidos_procesos_prenda_detalles as d', 'd.id', '=', 't.proceso_prenda_detalle_id')
            ->where('d.prenda_pedido_id', $prendaId)
            ->pluck('tc.id');
    }

    private function eliminarProcesosDePrenda(int $prendaId): void
    {
        $procesoIds = DB::table('pedidos_procesos_prenda_detalles')
            ->where('prenda_pedido_id', $prendaId)
            ->pluck('id');

        if ($procesoIds->isEmpty()) {
            return;
        }

        $tallaIds = DB::table('pedidos_procesos_prenda_tallas')
            ->whereIn('proceso_prenda_detalle_id', $procesoIds)
            ->pluck('id');

        $this->eliminarImagenesDeProcesos($procesoIds, $tallaIds);

        if ($tallaIds->isNotEmpty()) {
            DB::table('pedidos_procesos_prenda_talla_colores')
                ->whereIn('pedidos_procesos_prenda_talla_id', $tallaIds)
                ->delete();

            DB::table('pedidos_procesos_prenda_tallas')
                ->whereIn('id', $tallaIds)
                ->delete();
        }

        PedidosProcesosPrendaDetalle::whereIn('id', $procesoIds)->delete();

        Log::info('[EliminarPrendaPedidoRepository] Procesos eliminados', [
            'cantidad' => $procesoIds->count(),
            'prenda_id' => $prendaId,
        ]);
    }

    private function eliminarImagenesDeProcesos(Collection $procesoIds, Collection $tallaIds): void
    {
        $imagenes = PedidosProcessImagenes::query()
            ->where(function ($query) use ($procesoIds, $tallaIds): void {
                $query->whereIn('proceso_prenda_detalle_id', $procesoIds);
                if ($tallaIds->isNotEmpty()) {
                    $query->orWhereIn('proceso_prenda_talla_id', $tallaIds);
                }
            })
            ->get(['id', 'ruta_original', 'ruta_webp']);

        $this->eliminarArchivosFotos($imagenes);

        PedidosProcessImagenes::query()
            ->where(function ($query) use ($procesoIds, $tallaIds): void {
                $query->whereIn('proceso_prenda_detalle_id', $procesoIds);
                if ($tallaIds->isNotEmpty()) {
                    $query->orWhereIn('proceso_prenda_talla_id', $tallaIds);
                }
            })
            ->delete();
    }

    private function eliminarConsecutivosRecibo(int $pedidoId, int $prendaId): void
    {
        ConsecutivoReciboPedido::where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->delete();
    }

    private function softDeleteDetallesBodega(int $pedidoId, int $prendaId): void
    {
        DB::table('bodega_detalles_talla')
            ->where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }

    private function eliminarNotasBodegaPorTallaColor(Collection $tallaColorIds): void
    {
        if ($tallaColorIds->isEmpty()) {
            return;
        }

        DB::table('bodega_notas')
            ->whereIn('talla_color_id', $tallaColorIds)
            ->delete();
    }

    private function eliminarArchivosFotos(Collection $fotos): void
    {
        foreach ($fotos as $foto) {
            $this->eliminarArchivoSiExiste((string) ($foto->ruta_original ?? ''));

            $rutaWebp = (string) ($foto->ruta_webp ?? '');
            if ($rutaWebp !== '' && $rutaWebp !== (string) ($foto->ruta_original ?? '')) {
                $this->eliminarArchivoSiExiste($rutaWebp);
            }
        }
    }

    private function eliminarArchivoSiExiste(string $ruta): void
    {
        if ($ruta === '') {
            return;
        }

        if (!Storage::disk('public')->exists($ruta)) {
            return;
        }

        Storage::disk('public')->delete($ruta);
    }
}
