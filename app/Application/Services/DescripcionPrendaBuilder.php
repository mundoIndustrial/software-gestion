<?php

namespace App\Application\Services;

use App\Repositories\PrendaPedidoTallaRepository;
use Illuminate\Support\Facades\Log;

/**
 * DescripcionPrendaBuilder
 *
 * Construye la descripción textual detallada de una prenda en formato recibo.
 * Centraliza la lógica que antes vivía inline en el controller.
 */
class DescripcionPrendaBuilder
{
    public function __construct(
        private PrendaPedidoTallaRepository $tallaRepository,
    ) {}

    /**
     * Genera la descripción de una prenda para impresión de recibo.
     *
     * @param  object  $prenda       Instancia del modelo PrendaPedido con relaciones cargadas
     * @param  int     $indexPrenda  Número de orden de la prenda en el pedido
     * @return string|null
     */
    public function build(object $prenda, int $indexPrenda = 1): ?string
    {
        try {
            $lineas = [];
            $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'SIN NOMBRE';
            $lineas[] = "PRENDA {$indexPrenda}: {$nombrePrenda}";

            // Color y tela (primera combinación)
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                $primerColorTela = $prenda->coloresTelas->first();
                $tela  = $primerColorTela->tela  ? ($primerColorTela->tela->nombre  ?? $primerColorTela->tela)  : '-';
                $color = $primerColorTela->color ? ($primerColorTela->color->nombre ?? $primerColorTela->color) : '-';
                $ref   = $primerColorTela->tela  ? ($primerColorTela->tela->referencia ?? '') : '';

                $lineas[] = "TELA: {$tela} / COLOR: {$color}" . ($ref ? " (REF: {$ref})" : '');
            }

            // Primera variante: manga, observaciones, bolsillos, broche
            $primerVariante = ($prenda->variantes && $prenda->variantes->count() > 0)
                ? $prenda->variantes->first()
                : null;

            if ($primerVariante) {
                if ($primerVariante->manga) {
                    $lineas[] = "MANGA: " . strtoupper($primerVariante->manga);
                }
                if ($primerVariante->manga_obs) {
                    $lineas[] = "OBS. MANGA: {$primerVariante->manga_obs}";
                }
                if ($primerVariante->bolsillos_obs) {
                    $lineas[] = "BOLSILLOS: {$primerVariante->bolsillos_obs}";
                }
                if ($primerVariante->broche) {
                    $lineas[] = "BROCHE: " . strtoupper($primerVariante->broche);
                    if ($primerVariante->broche_obs) {
                        $lineas[] = "OBS. BROCHE: {$primerVariante->broche_obs}";
                    }
                }
            }

            // Tallas (talla-color tiene prioridad sobre tallas normales)
            $tallasSummary = [];
            $tallasPorColor = $this->tallaRepository->getTallasPorColor((int) $prenda->id);

            if ($tallasPorColor->count() > 0) {
                foreach ($tallasPorColor as $tallaColor) {
                    if ($tallaColor->cantidad > 0) {
                        $tallasSummary[] = "{$tallaColor->talla}:{$tallaColor->cantidad}-" . strtoupper($tallaColor->color_nombre);
                    }
                }
            } elseif ($prenda->tallas && $prenda->tallas->count() > 0) {
                foreach ($prenda->tallas as $talla) {
                    $cantidad = $talla->cantidad ?? 0;
                    if ($cantidad > 0) {
                        $tallasSummary[] = "{$talla->talla}: {$cantidad}";
                    }
                }
            }

            if (!empty($tallasSummary)) {
                $lineas[] = "TALLAS: " . implode(", ", $tallasSummary);
            }

            $descripcionFinal = implode(" | ", $lineas);

            Log::debug("[DescripcionPrendaBuilder] descripcion generada", [
                'prenda_id'            => $prenda->id,
                'prenda_nombre'        => $nombrePrenda,
                'lineas_cantidad'      => count($lineas),
                'descripcion_longitud' => strlen($descripcionFinal),
                'descripcion_preview'  => substr($descripcionFinal, 0, 150),
            ]);

            return $descripcionFinal;
        } catch (\Exception $e) {
            Log::error("[DescripcionPrendaBuilder] Error generando descripcion", [
                'error'     => $e->getMessage(),
                'prenda_id' => $prenda->id ?? 'unknown',
            ]);
            return null;
        }
    }
}
