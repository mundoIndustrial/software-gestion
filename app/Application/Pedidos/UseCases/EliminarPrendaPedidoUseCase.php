<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\UseCases\EliminarPrendaPedidoUseCaseContract;

use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTallaColor;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Use Case para eliminar una prenda de un pedido
 *
 * Maneja la eliminación en cascada de forma atómica:
 * novedades, fotos de prenda, fotos de telas/colores, variantes, tallas,
 * procesos e imágenes de procesos, consecutivos de recibo,
 * detalles de bodega y notas de bodega.
 */
final class EliminarPrendaPedidoUseCase implements EliminarPrendaPedidoUseCaseContract
{
    public function ejecutar(int $pedidoId, int $prendaId, string $motivo): array
    {
        $prenda = PrendaPedido::findOrFail($prendaId);
        $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'Sin nombre';
        $pedido = PedidoProduccion::findOrFail($pedidoId);

        DB::beginTransaction();

        $mensaje = "[ELIMINADA PRENDA] {$nombrePrenda} - Motivo: {$motivo}";
        $pedido->novedades = $pedido->novedades
            ? $pedido->novedades . "\n\n" . $mensaje
            : $mensaje;
        $pedido->save();

        // Fotos de prenda
        $prendaFotos = PrendaFotoPedido::where('prenda_pedido_id', $prendaId)->get();
        foreach ($prendaFotos as $foto) {
            if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
                Storage::disk('public')->delete($foto->ruta_original);
            }
            if ($foto->ruta_webp && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                Storage::disk('public')->delete($foto->ruta_webp);
            }
            $foto->delete();
        }

        Log::info('[EliminarPrendaPedidoUseCase] Imágenes de prenda eliminadas', [
            'cantidad' => $prendaFotos->count(),
            'prenda_id' => $prendaId,
        ]);

        // Fotos de telas/colores
        $colorTelasIds = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->pluck('id');

        if ($colorTelasIds->isNotEmpty()) {
            $telasFotos = PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $colorTelasIds)->get();
            foreach ($telasFotos as $foto) {
                if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
                    Storage::disk('public')->delete($foto->ruta_original);
                }
                if ($foto->ruta_webp && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                    Storage::disk('public')->delete($foto->ruta_webp);
                }
                $foto->delete();
            }
        }

        DB::table('prenda_pedido_colores_telas')->where('prenda_pedido_id', $prendaId)->delete();

        // Tallas y variantes
        $tallasIds = DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->pluck('id');

        DB::table('prenda_pedido_talla_colores')
            ->whereIn('prenda_pedido_talla_id', $tallasIds)
            ->delete();

        DB::table('prenda_pedido_tallas')->where('prenda_pedido_id', $prendaId)->delete();
        DB::table('prenda_pedido_variantes')->where('prenda_pedido_id', $prendaId)->delete();

        // Recopilar IDs de talla_color de procesos (necesarios para bodega_notas)
        $tallaColorIds = DB::table('pedidos_procesos_prenda_talla_colores as tc')
            ->join('pedidos_procesos_prenda_tallas as t', 't.id', '=', 'tc.pedidos_procesos_prenda_talla_id')
            ->join('pedidos_procesos_prenda_detalles as d', 'd.id', '=', 't.proceso_prenda_detalle_id')
            ->where('d.prenda_pedido_id', $prendaId)
            ->pluck('tc.id');

        // Procesos, imágenes de procesos, tallas y colores de procesos
        $procesos = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prendaId)->get();
        foreach ($procesos as $proceso) {
            if ($proceso->imagenes) {
                foreach ($proceso->imagenes as $imagen) {
                    if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                        Storage::disk('public')->delete($imagen->ruta_original);
                    }
                    if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                        Storage::disk('public')->delete($imagen->ruta_webp);
                    }
                    $imagen->delete();
                }
            }
            if ($proceso->tallas) {
                foreach ($proceso->tallas as $talla) {
                    PedidosProcesosPrendaTallaColor::where('pedidos_procesos_prenda_talla_id', $talla->id)->delete();
                    $talla->delete();
                }
            }
            $proceso->delete();
        }

        Log::info('[EliminarPrendaPedidoUseCase] Procesos eliminados', [
            'cantidad' => $procesos->count(),
            'prenda_id' => $prendaId,
        ]);

        // Consecutivos de recibo
        ConsecutivoReciboPedido::where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->delete();

        // Detalles de bodega (soft delete via update)
        DB::table('bodega_detalles_talla')
            ->where('prenda_id', $prendaId)
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // Notas de bodega vinculadas a los talla_color
        if ($tallaColorIds->isNotEmpty()) {
            DB::table('bodega_notas')
                ->whereIn('talla_color_id', $tallaColorIds)
                ->delete();
        }

        $prenda->delete();

        Log::info('[EliminarPrendaPedidoUseCase] Prenda eliminada', [
            'prenda_id' => $prendaId,
            'nombre' => $nombrePrenda,
            'pedido_id' => $pedidoId,
        ]);

        DB::commit();

        return [
            'success' => true,
            'message' => 'Prenda eliminada correctamente',
            'prenda_id' => $prendaId,
            'prenda_nombre' => $nombrePrenda,
            'motivo_registrado' => $motivo,
            'pedido_id' => $pedidoId,
        ];
    }
}





