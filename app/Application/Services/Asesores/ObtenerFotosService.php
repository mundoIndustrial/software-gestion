<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ObtenerFotosService
 * 
 * Servicio para obtener fotos de prendas de pedido.
 * Encapsula la lÃ³gica de consulta a la nueva relaciÃ³n:
 * pedido_produccion_id â†’ prendas_pedido â†’ prenda_fotos_pedido
 */
class ObtenerFotosService
{
    /**
     * Obtener fotos de una prenda de pedido
     */
    public function obtenerFotosPrendaPedido(int $prendaPedidoId): array
    {
        \Log::info('[FOTOS] Obteniendo fotos para prenda_pedido: ' . $prendaPedidoId);

        // Obtener la prenda y verificar permisos
        $this->verificarAcceso($prendaPedidoId);

        // Obtener fotos de la base de datos
        $fotos = DB::table('prenda_fotos_pedido')
            ->where('prenda_pedido_id', $prendaPedidoId)
            ->whereNull('deleted_at')
            ->orderBy('orden', 'asc')
            ->select('ruta_webp', 'ruta_original')
            ->get();

        // Mapear URLs
        $fotosFormato = $fotos->map(fn($foto) => $this->construirUrlFoto($foto))->toArray();

        \Log::info('[FOTOS] Fotos obtenidas correctamente', [
            'prenda_pedido_id' => $prendaPedidoId,
            'cantidad' => count($fotosFormato),
        ]);

        return [
            'success' => true,
            'fotos' => $fotosFormato,
        ];
    }

    /**
     * Verificar que el usuario tiene acceso a las fotos
     */
    protected function verificarAcceso(int $prendaPedidoId): void
    {
        $prendaPedido = DB::table('prendas_pedido')->find($prendaPedidoId);

        if (!$prendaPedido) {
            throw new \Exception('Prenda no encontrada', 404);
        }

        $pedido = Pedidos::find($prendaPedido->pedido_produccion_id);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
            throw new \Exception('No tienes permiso para ver estas fotos', 403);
        }
    }

    /**
     * Construir URL de foto
     */
    protected function construirUrlFoto(object $foto): array
    {
        $ruta = $foto->ruta_webp ?? $foto->ruta_original;

        if ($ruta && str_starts_with($ruta, 'http')) {
            return [
                'ruta_webp' => $foto->ruta_webp,
                'ruta_original' => $foto->ruta_original,
                'url' => $ruta,
            ];
        }

        if ($ruta && str_starts_with($ruta, '/storage/')) {
            return [
                'ruta_webp' => $foto->ruta_webp,
                'ruta_original' => $foto->ruta_original,
                'url' => $ruta,
            ];
        }

        $url = $ruta && str_starts_with($ruta, 'storage/')
            ? '/' . $ruta
            : $ruta;

        return [
            'ruta_webp' => $foto->ruta_webp,
            'ruta_original' => $foto->ruta_original,
            'url' => $url,
        ];
    }
}

