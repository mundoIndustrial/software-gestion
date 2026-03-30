<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;

final class PrendaVariantesUpdaterService
{
    /**
     * @param mixed $variantesPayload
     */
    public function actualizarVariantes(PrendaPedido $prenda, $variantesPayload): void
    {
        if (is_null($variantesPayload)) {
            \Log::info('[PrendaVariantesUpdaterService] Variantes = null, NO SE TOCAN las existentes');
            return;
        }

        if (empty($variantesPayload)) {
            \Log::info('[PrendaVariantesUpdaterService] Variantes vacio, ELIMINANDO todas');
            $prenda->variantes()->delete();
            return;
        }

        $variantes = $variantesPayload;

        // Frontend puede enviar objeto plano o array de variantes.
        if (is_array($variantes) && !empty($variantes) && !isset($variantes[0]) && !is_array(reset($variantes))) {
            $variantes = [[
                'tipo_manga_id'        => $variantes['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variantes['tipo_broche_boton_id'] ?? $variantes['tipo_broche_id'] ?? null,
                'manga_obs'            => $variantes['obs_manga'] ?? $variantes['manga_obs'] ?? null,
                'broche_boton_obs'     => $variantes['obs_broche'] ?? $variantes['broche_boton_obs'] ?? null,
                'tiene_bolsillos'      => $variantes['tiene_bolsillos'] ?? false,
                'bolsillos_obs'        => $variantes['obs_bolsillos'] ?? $variantes['bolsillos_obs'] ?? null,
            ]];
        }

        \Log::info('[PrendaVariantesUpdaterService] Variantes recibidas (normalizadas)', [
            'prenda_id' => $prenda->id,
            'cantidad' => count($variantes),
        ]);

        $varianteExistente = $prenda->variantes()->first();

        foreach ($variantes as $variante) {
            if (!is_array($variante)) {
                continue;
            }

            $upd = [];
            if (array_key_exists('tipo_manga_id', $variante))        $upd['tipo_manga_id'] = $variante['tipo_manga_id'];
            if (array_key_exists('tipo_broche_boton_id', $variante)) $upd['tipo_broche_boton_id'] = $variante['tipo_broche_boton_id'];
            if (array_key_exists('manga_obs', $variante))            $upd['manga_obs'] = $variante['manga_obs'];
            if (array_key_exists('broche_boton_obs', $variante))     $upd['broche_boton_obs'] = $variante['broche_boton_obs'];
            if (array_key_exists('tiene_bolsillos', $variante))      $upd['tiene_bolsillos'] = $variante['tiene_bolsillos'];
            if (array_key_exists('bolsillos_obs', $variante))        $upd['bolsillos_obs'] = $variante['bolsillos_obs'];

            if (empty($upd)) {
                continue;
            }

            $upd['updated_at'] = now();

            if ($varianteExistente) {
                \DB::table('prenda_pedido_variantes')
                    ->where('id', $varianteExistente->id)
                    ->update($upd);
                continue;
            }

            $upd['prenda_pedido_id'] = $prenda->id;
            $upd['created_at'] = now();
            $nuevoId = \DB::table('prenda_pedido_variantes')->insertGetId($upd);
            $varianteExistente = PrendaVariantePed::find($nuevoId);
        }
    }
}

