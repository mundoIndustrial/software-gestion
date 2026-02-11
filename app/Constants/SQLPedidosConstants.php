<?php

namespace App\Constants;

/**
 * Constantes de consultas SQL para el sistema de pedidos
 * Centraliza todas las consultas SQL para mejor mantenimiento
 */
class SQLPedidosConstants
{
    // ==================== IMÁGENES ====================
    
    /**
     * Obtener imágenes de EPP desde pedido_epp_imagenes
     */
    const IMAGENES_EPP = [
        'table' => 'pedido_epp_imagenes',
        'select' => ['ruta_web', 'ruta_original', 'principal', 'orden'],
        'conditions' => [
            'where' => 'pedido_epp_id = ?',
            'order_by' => 'orden ASC'
        ]
    ];

    /**
     * Obtener fotos de prenda desde prenda_fotos_pedido
     */
    const FOTOS_PRENDA = [
        'table' => 'prenda_fotos_pedido',
        'select' => ['ruta_webp'],
        'conditions' => [
            'where' => 'prenda_pedido_id = ? AND deleted_at IS NULL',
            'order_by' => 'orden ASC'
        ]
    ];

    /**
     * Obtener fotos de tela desde prenda_fotos_tela_pedido
     */
    const FOTOS_TELA = [
        'table' => 'prenda_fotos_tela_pedido',
        'select' => ['ruta_webp', 'ruta_original'],
        'conditions' => [
            'where' => 'prenda_pedido_colores_telas_id = ? AND deleted_at IS NULL',
            'order_by' => 'orden ASC'
        ]
    ];

    // ==================== COLORES Y TELAS ====================
    
    /**
     * Obtener colores y telas de prenda con joins
     */
    const COLORES_TELAS_PRENDA = [
        'table' => 'prenda_pedido_colores_telas',
        'joins' => [
            'colores_prenda' => 'prenda_pedido_colores_telas.color_id = colores_prenda.id',
            'telas_prenda' => 'prenda_pedido_colores_telas.tela_id = telas_prenda.id'
        ],
        'select' => [
            'prenda_pedido_colores_telas.id as color_tela_id',
            'prenda_pedido_colores_telas.referencia',
            'colores_prenda.nombre as color_nombre',
            'colores_prenda.codigo as color_codigo',
            'telas_prenda.nombre as tela_nombre',
            'telas_prenda.referencia as tela_referencia'
        ],
        'conditions' => [
            'where' => 'prenda_pedido_colores_telas.prenda_pedido_id = ?'
        ]
    ];

    // ==================== TIPOS DE COMPONENTES ====================
    
    /**
     * Obtener nombre de tipo de manga
     */
    const NOMBRE_TIPO_MANGA = [
        'table' => 'tipos_manga',
        'select' => ['nombre'],
        'conditions' => [
            'where' => 'id = ?'
        ]
    ];

    /**
     * Obtener nombre de tipo de broche/botón
     */
    const NOMBRE_TIPO_BROCHE = [
        'table' => 'tipos_broche_boton',
        'select' => ['nombre'],
        'conditions' => [
            'where' => 'id = ?'
        ]
    ];

    // ==================== MÉTODOS DE AYUDA ====================
    
    /**
     * Construir consulta base para imágenes de EPP
     */
    public static function construirConsultaImagenesEPP(int $pedidoEppId): string
    {
        $const = self::IMAGENES_EPP;
        return "SELECT " . implode(', ', $const['select']) . 
               " FROM {$const['table']} " .
               "WHERE {$const['conditions']['where']} " .
               "ORDER BY {$const['conditions']['order_by']}";
    }

    /**
     * Construir consulta base para fotos de prenda
     */
    public static function construirConsultaFotosPrenda(int $prendaPedidoId): string
    {
        $const = self::FOTOS_PRENDA;
        return "SELECT " . implode(', ', $const['select']) . 
               " FROM {$const['table']} " .
               "WHERE {$const['conditions']['where']} " .
               "ORDER BY {$const['conditions']['order_by']}";
    }

    /**
     * Construir consulta base para fotos de tela
     */
    public static function construirConsultaFotosTela(int $colorTelaId): string
    {
        $const = self::FOTOS_TELA;
        return "SELECT " . implode(', ', $const['select']) . 
               " FROM {$const['table']} " .
               "WHERE {$const['conditions']['where']} " .
               "ORDER BY {$const['conditions']['order_by']}";
    }

    /**
     * Construir consulta para colores y telas
     */
    public static function construirConsultaColoresTelas(int $prendaPedidoId): string
    {
        $const = self::COLORES_TELAS_PRENDA;
        $select = implode(', ', $const['select']);
        $joins = [];
        
        foreach ($const['joins'] as $table => $condition) {
            $joins[] = "JOIN {$table} ON {$condition}";
        }
        
        return "SELECT {$select} " .
               "FROM {$const['table']} " .
               implode(' ', $joins) . " " .
               "WHERE {$const['conditions']['where']}";
    }

    /**
     * Construir consulta para nombre de tipo de manga
     */
    public static function construirConsultaNombreManga(int $tipoMangaId): string
    {
        $const = self::NOMBRE_TIPO_MANGA;
        return "SELECT " . implode(', ', $const['select']) . 
               " FROM {$const['table']} " .
               "WHERE {$const['conditions']['where']}";
    }

    /**
     * Construir consulta para nombre de tipo de broche
     */
    public static function construirConsultaNombreBroche(int $tipoBrocheId): string
    {
        $const = self::NOMBRE_TIPO_BROCHE;
        return "SELECT " . implode(', ', $const['select']) . 
               " FROM {$const['table']} " .
               "WHERE {$const['conditions']['where']}";
    }
}
