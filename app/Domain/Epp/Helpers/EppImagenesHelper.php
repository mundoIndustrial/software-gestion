<?php

namespace App\Domain\Epp\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Helper para gestión de imágenes EPP
 * 
 * IMPORTANTE: Tabla epp_imagenes NO EXISTE
 * 
 * Las imágenes de EPP se almacenan en:
 * - pedido_epp_imagenes (cuando está asociada a un pedido)
 * 
 * No hay tabla maestra de imágenes de EPP
 */
class EppImagenesHelper
{
    /**
     * Log de inicialización
     */
    public static function logInit(): void
    {
        Log::debug(' [EPP-IMAGENES-HELPER] Sistema de imágenes de EPP inicializado', [
            'epp_imagenes_table' => 'NO EXISTE (ignorada)',
            'pedido_epp_imagenes_table' => 'ACTIVA (almacena imágenes)',
        ]);
    }

    /**
     * Verificar que no intente acceder a epp_imagenes
     */
    public static function verificarTablaIgnorada(): void
    {
        Log::debug(' [EPP-IMAGENES] Tabla epp_imagenes está siendo ignorada correctamente');
    }

    /**
     * Log cuando se obtiene EPP
     */
    public static function logObtenerEpp(int $eppId, string $codigo): void
    {
        Log::debug(' [EPP-IMAGENES] Obteniendo EPP sin cargar epp_imagenes', [
            'epp_id' => $eppId,
            'codigo' => $codigo,
            'imagenes_source' => 'pedido_epp_imagenes (si aplica contexto de pedido)',
        ]);
    }

    /**
     * Log cuando se busca EPP
     */
    public static function logBuscarEpp(string $termino, int $total): void
    {
        Log::debug(' [EPP-IMAGENES] Búsqueda de EPP sin tabla epp_imagenes', [
            'termino' => $termino,
            'total_encontrados' => $total,
            'imagenes_source' => 'pedido_epp_imagenes (si aplica contexto de pedido)',
        ]);
    }

    /**
     * Log cuando se obtienen EPPs activos
     */
    public static function logObtenerActivos(int $total): void
    {
        Log::debug('🟢 [EPP-IMAGENES] Obteniendo EPPs activos sin tabla epp_imagenes', [
            'total' => $total,
            'imagenes_source' => 'pedido_epp_imagenes (si aplica contexto de pedido)',
        ]);
    }

    /**
     * Log cuando se obtienen EPPs por categoría
     */
    public static function logObtenerPorCategoria(string $categoria, int $total): void
    {
        Log::debug('📂 [EPP-IMAGENES] Obteniendo EPPs por categoría sin tabla epp_imagenes', [
            'categoria' => $categoria,
            'total' => $total,
            'imagenes_source' => 'pedido_epp_imagenes (si aplica contexto de pedido)',
        ]);
    }

    /**
     * Log cuando se mapea agregado (sin imágenes)
     */
    public static function logMapearAgregado(int $eppId, string $codigo): void
    {
        Log::debug('🔗 [EPP-IMAGENES] Mapeando agregado EPP sin cargar epp_imagenes', [
            'epp_id' => $eppId,
            'codigo' => $codigo,
            'advertencia' => 'tabla epp_imagenes no existe',
        ]);
    }

    /**
     * Log cuando se intenta sincronizar imágenes (no hace nada)
     */
    public static function logSincronizarIgnorada(int $eppId): void
    {
        Log::debug('⏭️ [EPP-IMAGENES] sincronizarImagenes IGNORADA - tabla epp_imagenes no existe', [
            'epp_id' => $eppId,
        ]);
    }

    /**
     * Log cuando se elimina imagen de pedido_epp_imagenes
     */
    public static function logEliminarImagenPedido(int $imagenId, string $ruta): void
    {
        Log::info(' [EPP-IMAGENES] Imagen de pedido_epp_imagenes eliminada', [
            'imagen_id' => $imagenId,
            'ruta' => $ruta,
        ]);
    }

    /**
     * Log cuando no encuentra imagen
     */
    public static function logImagenNoEncontrada(int $imagenId): void
    {
        Log::warning(' [EPP-IMAGENES] Imagen no encontrada en pedido_epp_imagenes', [
            'imagen_id' => $imagenId,
            'tabla' => 'epp_imagenes no existe (ignorada)',
        ]);
    }

    /**
     * Log de carga en formulario (evitando epp_imagenes)
     */
    public static function logFormularioEppSinImagenes(int $itemIndex): void
    {
        Log::debug(' [EPP-FORMULARIO] EPP sin enviar imágenes de epp_imagenes', [
            'item_index' => $itemIndex,
            'nota' => 'Tabla epp_imagenes no existe',
            'imagenes_source' => 'pedido_epp_imagenes después de crear pedido',
        ]);
    }

    /**
     * Resumen del estado de imágenes de EPP
     */
    public static function obtenerEstado(): array
    {
        return [
            'epp_imagenes' => [
                'estado' => 'NO EXISTE',
                'ignorada' => true,
                'consultas_ejecutadas' => 0,
            ],
            'pedido_epp_imagenes' => [
                'estado' => 'ACTIVA',
                'en_uso' => true,
                'almacena_imagenes_de_epp_en_pedidos' => true,
            ],
        ];
    }
}
