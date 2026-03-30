<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Models\News;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Maneja la persistencia de novedades y notificaciones derivadas
 * de la agregacion manual de prendas a un pedido existente.
 */
class PrendaNovedadService
{
    public function guardarNovedad(PrendaPedido $prenda, AgregarPrendaCompletaDTO $dto): void
    {
        if (is_null($dto->novedad) || empty(trim($dto->novedad))) {
            return;
        }

        $pedido = $prenda->pedidoProduccion;
        if (!$pedido) {
            Log::warning('[PrendaNovedadService] No se encontro pedido para prenda', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        $novedadesActuales = $pedido->novedades ?? '';
        $usuarioAutenticado = Auth::user();
        $nombreAsesor = $usuarioAutenticado?->name ?? 'Sistema';
        $rolAsesor = $this->resolverRol($usuarioAutenticado);

        $nuevaNovedad = trim($dto->novedad);
        $fechaHora = now()->format('d/m/Y h:i A');
        $rolLabel = ucfirst(str_replace('_', ' ', $rolAsesor));
        $nombrePrenda = $prenda->nombre_prenda ?? 'Sin nombre';
        $novedadConInfo = "{$rolLabel}-{$nombreAsesor}-{$fechaHora} - Agrego la prenda \"{$nombrePrenda}\" - {$nuevaNovedad}";

        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : '') . $novedadConInfo;

        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        Log::info('[PrendaNovedadService] Novedad guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $dto->novedad,
            'nombre_asesor' => $nombreAsesor,
        ]);

        try {
            News::create([
                'event_type' => 'prenda_agregada',
                'table_name' => 'prendas_pedido',
                'record_id' => $prenda->id,
                'description' => "{$rolLabel} {$nombreAsesor} agrego la prenda \"{$nombrePrenda}\" al Pedido #{$pedido->numero_pedido}",
                'user_id' => $usuarioAutenticado?->id,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'prenda_agregada',
                    'prenda_id' => $prenda->id,
                    'prenda_nombre' => $nombrePrenda,
                    'pedido_id' => $pedido->id,
                    'novedad' => $nuevaNovedad,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('[PrendaNovedadService] Error creando News', [
                'error' => $e->getMessage(),
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
            ]);
        }
    }

    public function guardarNovedadModificacion(PrendaPedido $prenda, ?string $novedad): void
    {
        if (is_null($novedad) || empty(trim($novedad))) {
            return;
        }

        $pedido = $prenda->pedidoProduccion;
        if (!$pedido) {
            Log::warning('[PrendaNovedadService] No se encontro pedido para prenda', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        $novedadesActuales = $pedido->novedades ?? '';
        $usuarioAutenticado = Auth::user();
        $nombreAsesor = $usuarioAutenticado?->name ?? 'Sistema';
        $rolAsesor = $this->resolverRol($usuarioAutenticado);
        $rolLabel = ucfirst(str_replace('_', ' ', $rolAsesor));
        $nombrePrenda = $prenda->nombre_prenda ?? 'Sin nombre';

        $textoNovedad = trim($novedad);
        $fechaHora = now()->format('d/m/Y h:i A');
        $novedadConInfo = "{$rolLabel}-{$nombreAsesor}-{$fechaHora} - Modifico la prenda \"{$nombrePrenda}\" - {$textoNovedad}";
        $novedadesActualizadas = $novedadesActuales . ($novedadesActuales ? "\n\n" : '') . $novedadConInfo;

        $pedido->update([
            'novedades' => $novedadesActualizadas,
        ]);

        Log::info('[PrendaNovedadService] Novedad de modificacion guardada', [
            'prenda_id' => $prenda->id,
            'pedido_id' => $pedido->id,
            'novedad' => $textoNovedad,
            'nombre_asesor' => $nombreAsesor,
        ]);

        try {
            News::create([
                'event_type' => 'prenda_modificada',
                'table_name' => 'prendas_pedido',
                'record_id' => $prenda->id,
                'description' => "{$rolLabel} {$nombreAsesor} modifico la prenda \"{$nombrePrenda}\" en Pedido #{$pedido->numero_pedido}",
                'user_id' => $usuarioAutenticado?->id,
                'pedido' => $pedido->numero_pedido,
                'metadata' => [
                    'tipo' => 'prenda_modificada',
                    'prenda_id' => $prenda->id,
                    'prenda_nombre' => $nombrePrenda,
                    'pedido_id' => $pedido->id,
                    'novedad' => $textoNovedad,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('[PrendaNovedadService] Error creando News de modificacion', [
                'error' => $e->getMessage(),
                'prenda_id' => $prenda->id,
                'pedido_id' => $pedido->id,
            ]);
        }
    }

    private function resolverRol(?object $usuarioAutenticado): string
    {
        if ($usuarioAutenticado && method_exists($usuarioAutenticado, 'roles')) {
            return $usuarioAutenticado->roles()->first()?->name ?? 'Sistema';
        }

        return 'Sistema';
    }
}
