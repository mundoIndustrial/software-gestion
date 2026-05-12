<?php

namespace App\Helpers;

/**
 * Helper para centralizar la lógica de botones en el dashboard del operario
 * Evita duplicación de código y mantiene consistencia en los atributos data-
 */
class DashboardBotonesHelper
{
    /**
     * Prepara los botones de visualización (Ver Recibo, Agregar Novedad, etc.)
     * según el rol del usuario y los recibos disponibles
     *
     * @param array $prenda Datos de la prenda
     * @param array $recibos Recibos asociados a la prenda
     * @param string $rolUsuario Rol del usuario autenticado
     * @return array Configuración de botones a mostrar
     */
    public static function prepararBotonesVisualizacion(array $prenda, array $recibos, string $rolUsuario): array
    {
        $botones = [];

        // Botón Ver Recibo - Disponible para todos
        $reciboParaBusqueda = collect($recibos)->first(function ($recibo) {
            return !empty($recibo['pedido_parcial_id']);
        }) ?? ($recibos[0] ?? null);

        if ($reciboParaBusqueda) {
            $botones['ver_recibo'] = [
                'numeroPedido' => $prenda['numero_pedido'],
                'prendaId' => $prenda['prenda_id'],
                'nombrePrenda' => $prenda['nombre_prenda'],
                'tipoRecibo' => $reciboParaBusqueda['tipo_recibo'] ?? 'COSTURA',
                'idParcial' => !empty($reciboParaBusqueda['pedido_parcial_id']) ? (int) $reciboParaBusqueda['pedido_parcial_id'] : null,
                'consecutivo' => $reciboParaBusqueda['consecutivo_parcial'] ?? ($reciboParaBusqueda['consecutivo_actual'] ?? $prenda['numero_pedido']),
            ];
        }

        // Botón Agregar Novedad - Disponible para todos
        $botones['agregar_novedad'] = [
            'numeroPedido' => $prenda['numero_pedido'],
            'prendaId' => $prenda['prenda_id'],
            'nombrePrenda' => $prenda['nombre_prenda'],
            'consecutivo' => $reciboParaBusqueda['consecutivo_parcial'] ?? ($reciboParaBusqueda['consecutivo_actual'] ?? $prenda['numero_pedido']),
        ];

        return $botones;
    }

    /**
     * Prepara los botones de distribución (Ver Distribución, Editar Encargados)
     * Solo para vista-costura cuando hay parciales
     *
     * @param array $prenda Datos de la prenda
     * @param array $recibo Recibo específico
     * @param bool $tieneParciales Si el recibo tiene parciales
     * @return array Configuración de botones de distribución
     */
    public static function prepararBotonesDistribucion(array $prenda, array $recibo, bool $tieneParciales): array
    {
        $botones = [];

        if (!$tieneParciales) {
            return $botones;
        }

        $tipoRecibo = strtoupper($recibo['tipo_recibo'] ?? 'COSTURA');

        $botones['ver_distribucion'] = [
            'filtro' => strtolower($tipoRecibo),
            'prendaId' => $prenda['prenda_id'],
            'reciboId' => $recibo['recibo_id'] ?? $recibo['id'] ?? null,
            'numeroRecibo' => $recibo['consecutivo_actual'] ?? $prenda['numero_pedido'],
            'tipoRecibo' => $tipoRecibo,
        ];

        $botones['editar_encargados'] = [
            'filtro' => strtolower($tipoRecibo),
            'prendaId' => $prenda['prenda_id'],
            'reciboId' => $recibo['recibo_id'] ?? $recibo['id'] ?? null,
            'pedidoId' => $prenda['pedido_id'],
            'numeroPedido' => $prenda['numero_pedido'],
            'numeroRecibo' => $recibo['consecutivo_actual'] ?? $prenda['numero_pedido'],
            'nombrePrenda' => $prenda['nombre_prenda'],
            'tipoRecibo' => $tipoRecibo,
        ];

        return $botones;
    }

    /**
     * Obtiene los atributos data- comunes para todos los botones de acción
     * Centraliza la lógica para evitar duplicación
     *
     * @param array $prenda Datos de la prenda
     * @param array $recibo Recibo específico
     * @return array Atributos data- normalizados
     */
    public static function obtenerAtributosDataComunes(array $prenda, array $recibo): array
    {
        return [
            'data-pedido-id' => $prenda['pedido_id'] ?? '',
            'data-prenda-id' => $prenda['prenda_id'] ?? '',
            'data-nombre' => $prenda['nombre_prenda'] ?? '',
            'data-numero-pedido' => $prenda['numero_pedido'] ?? '',
            'data-recibo-id' => $recibo['recibo_id'] ?? $recibo['id'] ?? '',
            'data-tipo-recibo' => strtoupper($recibo['tipo_recibo'] ?? 'COSTURA'),
            'data-recibo' => $recibo['consecutivo_actual'] ?? $prenda['numero_pedido'] ?? '',
        ];
    }
}
