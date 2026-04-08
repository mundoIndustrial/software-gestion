<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

class PrendaPedidoDescriptionFormatter
{
    public function formatDetailed(PrendaPedido $prenda, int $index = 1): string
    {
        try {
            $lineas = [];

            $obsBolsillos = null;
            $obsBroche = null;
            $obsReflectivo = null;

            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $variante = $prenda->variantes->first();
                $obsArray = $variante->descripcion_adicional ? explode(' | ', $variante->descripcion_adicional) : [];

                foreach ($obsArray as $obs) {
                    if (strpos($obs, 'Bolsillos:') === 0) {
                        $obsBolsillos = trim(str_replace('Bolsillos:', '', $obs));
                    } elseif (strpos($obs, 'Broche:') === 0) {
                        $obsBroche = trim(str_replace('Broche:', '', $obs));
                    } elseif (strpos($obs, 'Reflectivo:') === 0) {
                        $obsReflectivo = trim(str_replace('Reflectivo:', '', $obs));
                    }
                }
            }

            if ($prenda->descripcion) {
                $lineas[] = trim($prenda->descripcion);
            }

            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $variante = $prenda->variantes->first();

                if ($obsReflectivo || ($variante->tiene_reflectivo && $variante->obs_reflectivo)) {
                    $texto = $obsReflectivo ?? $variante->obs_reflectivo;
                    $lineas[] = "<br><strong>Reflectivo:</strong> " . trim((string) $texto);
                }

                if ($obsBolsillos || ($variante->tiene_bolsillos && $variante->obs_bolsillos)) {
                    $texto = $obsBolsillos ?? $variante->obs_bolsillos;
                    $lineas[] = "<br><strong>Bolsillos:</strong> " . trim((string) $texto);
                }

                if ($variante->tipo_broche_id) {
                    $nombreBroche = 'Botón';
                    if ($variante->broche) {
                        $nombreBroche = $variante->broche->nombre ?? 'Botón';
                    }

                    $texto = $obsBroche ?? ($variante->aplica_broche ? $variante->obs_broche : null);
                    if ($texto) {
                        $lineas[] = "<br><strong>{$nombreBroche}:</strong> " . trim((string) $texto);
                    }
                }
            }

            if ($prenda->relationLoaded('tallas') && $prenda->tallas && $prenda->tallas->count() > 0) {
                $tallasInfo = [];
                $tallasPorGenero = $prenda->tallas->groupBy('genero');

                foreach ($tallasPorGenero as $genero => $tallaRecords) {
                    $tallaTexto = [];
                    foreach ($tallaRecords as $tallaRecord) {
                        $tallaTexto[] = "{$tallaRecord->talla} ({$tallaRecord->cantidad})";
                    }
                    $tallasInfo[] = "{$genero}: " . implode(", ", $tallaTexto);
                }

                if (!empty($tallasInfo)) {
                    $lineas[] = "<br><strong>Tallas:</strong> " . implode(" | ", $tallasInfo);
                }
            }

            return implode("", $lineas);
        } catch (\Exception $e) {
            Log::error('Error generando descripción para PrendaPedido', [
                'prenda_pedido_id' => $prenda->id,
                'error' => $e->getMessage(),
                'index' => $index,
            ]);

            return "DESCRIPCION: " . ($prenda->descripcion ? trim($prenda->descripcion) : 'Sin descripción');
        }
    }
}
