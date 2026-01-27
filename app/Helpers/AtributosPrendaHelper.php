<?php

namespace App\Helpers;

use App\Models\ColorPrenda;
use App\Models\TelaPrenda;

/**
 * AtributosPrendaHelper
 * 
 * Helper para transformar IDs de colores, telas y referencias a sus valores legibles
 */
class AtributosPrendaHelper
{
    /**
     * Cache para colores
     */
    private static $coloresCache = [];

    /**
     * Cache para telas
     */
    private static $telasCache = [];

    /**
     * Obtener el nombre del color a partir del ID
     * 
     * @param int|null $colorId
     * @return string
     */
    public static function obtenerNombreColor(?int $colorId): string
    {
        if (!$colorId || $colorId === 0) {
            return '';
        }

        // Verificar cache primero
        if (isset(self::$coloresCache[$colorId])) {
            return self::$coloresCache[$colorId];
        }

        try {
            $color = ColorPrenda::find($colorId);
            $nombre = $color?->nombre ?? '';
            self::$coloresCache[$colorId] = $nombre;
            return $nombre;
        } catch (\Exception $e) {
            \Log::warning("Error al obtener color con ID {$colorId}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Obtener el nombre de la tela a partir del ID
     * 
     * @param int|null $telaId
     * @return string
     */
    public static function obtenerNombreTela(?int $telaId): string
    {
        if (!$telaId || $telaId === 0) {
            return '';
        }

        // Verificar cache primero
        if (isset(self::$telasCache[$telaId]['nombre'])) {
            return self::$telasCache[$telaId]['nombre'];
        }

        try {
            $tela = TelaPrenda::find($telaId);
            $nombre = $tela?->nombre ?? '';
            
            if (!isset(self::$telasCache[$telaId])) {
                self::$telasCache[$telaId] = [];
            }
            self::$telasCache[$telaId]['nombre'] = $nombre;
            
            return $nombre;
        } catch (\Exception $e) {
            \Log::warning("Error al obtener tela con ID {$telaId}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Obtener la referencia de la tela a partir del ID
     * 
     * NOTA: La referencia ahora está en prenda_pedido_colores_telas
     * Este método devuelve vacío porque la referencia es específica por pedido
     * 
     * @param int|null $telaId
     * @return string
     */
    public static function obtenerReferenciaTela(?int $telaId): string
    {
        // La referencia ya no está en telas_prenda
        // Se encuentra en prenda_pedido_colores_telas para cada pedido
        // Este método devuelve vacío para mantener compatibilidad
        return '';
    }
            
            return $referencia;
        } catch (\Exception $e) {
            \Log::warning("Error al obtener referencia de tela con ID {$telaId}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Obtener toda la información de una tela
     * 
     * @param int|null $telaId
     * @return array
     */
    public static function obtenerTela(?int $telaId): array
    {
        if (!$telaId || $telaId === 0) {
            return [
                'id' => null,
                'nombre' => '',
                'referencia' => '',
                'descripcion' => ''
            ];
        }

        try {
            $tela = TelaPrenda::find($telaId);
            
            return [
                'id' => $tela?->id,
                'nombre' => $tela?->nombre ?? '',
                'referencia' => $tela?->referencia ?? '',
                'descripcion' => $tela?->descripcion ?? ''
            ];
        } catch (\Exception $e) {
            \Log::warning("Error al obtener tela con ID {$telaId}: " . $e->getMessage());
            return [
                'id' => $telaId,
                'nombre' => '',
                'referencia' => '',
                'descripcion' => ''
            ];
        }
    }

    /**
     * Obtener toda la información de un color
     * 
     * @param int|null $colorId
     * @return array
     */
    public static function obtenerColor(?int $colorId): array
    {
        if (!$colorId || $colorId === 0) {
            return [
                'id' => null,
                'nombre' => '',
                'codigo' => ''
            ];
        }

        try {
            $color = ColorPrenda::find($colorId);
            
            return [
                'id' => $color?->id,
                'nombre' => $color?->nombre ?? '',
                'codigo' => $color?->codigo ?? ''
            ];
        } catch (\Exception $e) {
            \Log::warning("Error al obtener color con ID {$colorId}: " . $e->getMessage());
            return [
                'id' => $colorId,
                'nombre' => '',
                'codigo' => ''
            ];
        }
    }

    /**
     * Formatear tela con referencia
     * Retorna: "Nombre Tela (Ref: XXXXX)" si tiene referencia, solo nombre si no
     * 
     * @param int|null $telaId
     * @return string
     */
    public static function formatearTela(?int $telaId): string
    {
        if (!$telaId || $telaId === 0) {
            return '';
        }

        try {
            $tela = TelaPrenda::find($telaId);
            if (!$tela) {
                return '';
            }

            $nombre = $tela->nombre ?? '';
            $referencia = $tela->referencia ?? '';

            if ($referencia) {
                return "{$nombre} (Ref: {$referencia})";
            }

            return $nombre;
        } catch (\Exception $e) {
            \Log::warning("Error al formatear tela con ID {$telaId}: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Limpiar caches
     */
    public static function limpiarCaches(): void
    {
        self::$coloresCache = [];
        self::$telasCache = [];
    }
}
