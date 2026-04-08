<?php

namespace App\Domain\InventarioTelas\Repositories;

interface InventarioTelaRepositoryInterface
{
    /**
     * Obtener todas las telas ordenadas por categoría y nombre
     */
    public function obtenerTodas();

    /**
     * Obtener una tela por ID
     */
    public function obtenerPorId(int $id);

    /**
     * Crear una nueva tela
     */
    public function crear(array $datos);

    /**
     * Actualizar stock de una tela
     */
    public function actualizarStock(int $telaId, float $nuevoStock);

    /**
     * Registrar movimiento en historial
     */
    public function registrarMovimiento(int $telaId, int $usuarioId, string $tipoAccion, float $cantidad, float $stockAnterior, float $stockNuevo, ?string $observaciones = null);

    /**
     * Obtener historial de movimientos con datos de tela y usuario
     */
    public function obtenerHistorial(int $limite = 100);

    /**
     * Obtener estadísticas del inventario
     */
    public function obtenerEstadisticas();

    /**
     * Obtener telas más movidas en los últimos N días
     */
    public function obtenerTelasMasMovidas(int $dias = 30, int $limite = 10);

    /**
     * Obtener stock actual de todas las telas
     */
    public function obtenerStockPorTela();

    /**
     * Obtener todas las telas para filtros (id, nombre, categoría)
     */
    public function obtenerTelasParaFiltros();

    /**
     * Eliminar una tela y su historial
     */
    public function eliminar(int $telaId);
}
