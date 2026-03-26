<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Support\Facades\DB;

/**
 * Persiste logos asociados a una prenda de pedido.
 */
class PrendaLogoPersistenceService
{
    public function guardarLogos(int $prendaId, array $logos): void
    {
        foreach ($logos as $index => $logo) {
            DB::table('prenda_fotos_logo_pedido')->insert([
                'prenda_pedido_id' => $prendaId,
                'ruta_original' => $logo['ruta_original'] ?? $logo['url'] ?? null,
                'ruta_webp' => $logo['ruta_webp'] ?? null,
                'ruta_miniatura' => $logo['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ubicacion' => $logo['ubicacion'] ?? null,
                'ancho' => $logo['ancho'] ?? null,
                'alto' => $logo['alto'] ?? null,
                'tamano' => $logo['tamano'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
