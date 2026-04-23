<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\ColorPrenda;
use App\Models\PrendaPedido;
use App\Models\TelaPrenda;

final class PrendaColoresTelasUpdaterService
{
    /**
     * @param mixed $coloresTelasPayload
     */
    public function actualizarColoresTelas(PrendaPedido $prenda, $coloresTelasPayload): void
    {
        if (is_null($coloresTelasPayload)) {
            return;
        }

        if (empty($coloresTelasPayload)) {
            // Guard rail defensivo:
            // Evita borrar todos los colores/telas por un payload vacio accidental
            // durante guardado de borrador con multiples prendas.
            \Log::warning('[PrendaColoresTelasUpdaterService] colores_telas vacio - se omite borrado defensivamente', [
                'prenda_id' => $prenda->id,
                'colores_telas_actuales' => $prenda->coloresTelas()->count(),
            ]);
            return;
        }

        $idsPersistidos = [];

        foreach ($coloresTelasPayload as $colorTela) {
            if (!is_array($colorTela)) {
                continue;
            }

            $colorId = $colorTela['color_id'] ?? null;
            $telaId = $colorTela['tela_id'] ?? null;
            $referencia = $colorTela['referencia'] ?? null;
            $id = $colorTela['id'] ?? null;

            $colorNombre = $colorTela['color_nombre'] ?? $colorTela['color'] ?? null;
            $telaNombre = $colorTela['tela_nombre'] ?? $colorTela['tela'] ?? null;

            if ($colorNombre && !$colorId) {
                $colorId = $this->obtenerOCrearColor($colorNombre);
            }

            if ($telaNombre && !$telaId) {
                $telaId = $this->obtenerOCrearTela($telaNombre);
            }

            if (!$telaId) {
                \Log::debug('[PrendaColoresTelasUpdaterService] Tela sin tela_id, saltando', [
                    'prenda_id' => $prenda->id,
                    'color_tela' => $colorTela,
                ]);
                continue;
            }

            if ($id) {
                $colorTelaExistente = $prenda->coloresTelas()->where('id', $id)->first();
                if ($colorTelaExistente) {
                    $colorTelaExistente->update([
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                        'referencia' => $referencia,
                    ]);
                    $idsPersistidos[] = $colorTelaExistente->id;
                }
                continue;
            }

            $query = $prenda->coloresTelas()->where('tela_id', $telaId);
            if ($colorId) {
                $query->where('color_id', $colorId);
            }

            $existente = $query->first();
            if ($existente) {
                if ($referencia !== null && $existente->referencia !== $referencia) {
                    $existente->update(['referencia' => $referencia]);
                }
                $idsPersistidos[] = $existente->id;
                continue;
            }

            $nueva = $prenda->coloresTelas()->create([
                'color_id' => $colorId ?: null,
                'tela_id' => $telaId,
                'referencia' => $referencia,
            ]);
            $idsPersistidos[] = $nueva->id;
        }

        $prenda->coloresTelas()
            ->whereNotIn('id', $idsPersistidos)
            ->delete();
    }

    private function obtenerOCrearColor(string $nombreColor): ?int
    {
        $color = ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombreColor)])
            ->where('activo', true)
            ->first();

        if ($color) {
            return $color->id;
        }

        $color = ColorPrenda::create([
            'nombre' => $nombreColor,
            'codigo' => strtoupper(substr(md5($nombreColor), 0, 6)),
            'activo' => true,
        ]);

        return $color->id;
    }

    private function obtenerOCrearTela(string $nombreTela): ?int
    {
        $tela = TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombreTela)])
            ->where('activo', true)
            ->first();

        if ($tela) {
            return $tela->id;
        }

        $tela = TelaPrenda::create([
            'nombre' => $nombreTela,
            'referencia' => strtoupper(substr(md5($nombreTela), 0, 8)),
            'activo' => true,
        ]);

        return $tela->id;
    }
}
