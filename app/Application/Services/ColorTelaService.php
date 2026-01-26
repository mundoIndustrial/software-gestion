<?php

namespace App\Application\Services;

use Illuminate\Support\Facades\DB;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;

/**
 * ColorTelaService
 * 
 * Servicio centralizado para gestionar Colores y Telas
 * Consolida la lógica de obtener o crear colores/telas evitando duplicación
 */
class ColorTelaService
{
    /**
     * Obtener o crear un color por nombre
     * 
     * Busca case-insensitive, si no existe, lo crea
     * 
     * @param string|null $nombreColor
     * @return int|null ID del color
     */
    public function obtenerOCrearColor(?string $nombreColor): ?int
    {
        if (empty($nombreColor)) {
            return null;
        }

        // Buscar color existente por nombre (case-insensitive)
        $color = ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower(trim($nombreColor))])
            ->where('activo', true)
            ->first();

        if ($color) {
            return $color->id;
        }

        // Si no existe, crear el color
        $color = ColorPrenda::create([
            'nombre' => trim($nombreColor),
            'codigo' => strtoupper(substr(md5($nombreColor), 0, 6)),
            'activo' => true,
        ]);

        return $color->id;
    }

    /**
     * Obtener o crear una tela por nombre
     * 
     * Busca case-insensitive, si no existe, la crea
     * 
     * @param string|null $nombreTela
     * @return int|null ID de la tela
     */
    public function obtenerOCrearTela(?string $nombreTela): ?int
    {
        if (empty($nombreTela)) {
            return null;
        }

        // Buscar tela existente por nombre (case-insensitive)
        $tela = TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower(trim($nombreTela))])
            ->where('activo', true)
            ->first();

        if ($tela) {
            return $tela->id;
        }

        // Si no existe, crear la tela
        $tela = TelaPrenda::create([
            'nombre' => trim($nombreTela),
            'referencia' => strtoupper(substr(md5($nombreTela), 0, 8)),
            'activo' => true,
        ]);

        return $tela->id;
    }

    /**
     * Obtener o crear una combinación color-tela
     * 
     * @param int $prendaId
     * @param int|null $colorId
     * @param int|null $telaId
     * @return int|null ID de la combinación
     */
    public function obtenerOCrearColorTela(int $prendaId, ?int $colorId, ?int $telaId): ?int
    {
        if (is_null($colorId) || is_null($telaId)) {
            return null;
        }

        // Verificar si ya existe esta combinación
        $existe = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->where('color_id', $colorId)
            ->where('tela_id', $telaId)
            ->first();

        if ($existe) {
            return $existe->id;
        }

        // Crear nueva combinación
        return DB::table('prenda_pedido_colores_telas')->insertGetId([
            'prenda_pedido_id' => $prendaId,
            'color_id' => $colorId,
            'tela_id' => $telaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
