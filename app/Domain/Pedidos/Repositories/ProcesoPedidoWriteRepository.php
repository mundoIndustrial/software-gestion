<?php

namespace App\Domain\Pedidos\Repositories;

interface ProcesoPedidoWriteRepository
{
    /**
     * Busca un proceso legacy por pedido y nombre.
     *
     * @return array{id:int, proceso:string}|null
     */
    public function obtenerProcesoLegacyPorPedidoYNombre(int $numeroPedido, string $nombreProceso): ?array;

    /**
     * Actualiza un proceso legacy existente.
     */
    public function actualizarProcesoLegacy(int $procesoId, string $fechaInicio, ?string $encargado, string $estadoProceso): void;

    /**
     * Crea un proceso legacy y retorna su ID.
     */
    public function crearProcesoLegacy(int $numeroPedido, string $nombreProceso, string $fechaInicio, ?string $encargado, string $estadoProceso): int;

    /**
     * Verifica si existe un proceso en la tabla nueva para el pedido indicado.
     */
    public function existeProcesoNuevoEnPedido(int $procesoId, int $numeroPedido): bool;

    /**
     * Elimina un proceso de la tabla nueva junto con sus dependencias.
     */
    public function eliminarProcesoNuevoConDependencias(int $procesoId): void;

    /**
     * Obtiene el nombre del proceso legacy (tabla procesos_prenda) por id/pedido.
     */
    public function obtenerNombreProcesoLegacy(int $procesoId, int $numeroPedido): ?string;

    /**
     * Cuenta procesos legacy distintos (por area) para un pedido.
     */
    public function contarProcesosLegacyDistintos(int $numeroPedido): int;

    /**
     * Elimina todos los registros legacy asociados a un area de proceso.
     */
    public function eliminarProcesoLegacyPorNombre(int $numeroPedido, string $nombreProceso): void;
}
