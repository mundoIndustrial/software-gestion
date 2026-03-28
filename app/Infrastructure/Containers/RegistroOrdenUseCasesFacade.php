<?php

namespace App\Infrastructure\Containers;

use App\Application\Pedidos\UseCases\Orders\GetOrdersQueryUseCase;
use App\Application\Pedidos\UseCases\Orders\GetOrderImagesQueryUseCase;
use App\Application\Pedidos\UseCases\Orders\GetOrderDetailsQueryUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\GetDescripcionPrendasUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\CalcularDiasUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\CalcularDiasBatchUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\CalcularFechaEstimadaUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\GetRecibosDatosUseCase;
use App\Application\Pedidos\UseCases\RegistroOrden\GetNovedadesUseCase;

/**
 * RegistroOrdenUseCasesFacade
 * Agrupa todos los usecases de RegistroOrden y Orders
 * para reducir parámetros del constructor
 */
final class RegistroOrdenUseCasesFacade
{
    public function __construct(
        public readonly GetOrdersQueryUseCase $getOrdersQueryUseCase,
        public readonly GetOrderImagesQueryUseCase $getOrderImagesQueryUseCase,
        public readonly GetOrderDetailsQueryUseCase $getOrderDetailsQueryUseCase,
        public readonly GetSeguimientoPorPrendaUseCase $getSeguimientoPorPrendaUseCase,
        public readonly GetDescripcionPrendasUseCase $getDescripcionPrendasUseCase,
        public readonly GetConsecutivoCosturaUseCase $getConsecutivoCosturaUseCase,
        public readonly CalcularDiasUseCase $calcularDiasUseCase,
        public readonly CalcularDiasBatchUseCase $calcularDiasBatchUseCase,
        public readonly CalcularFechaEstimadaUseCase $calcularFechaEstimadaUseCase,
        public readonly GetRecibosDatosUseCase $getRecibosDatosUseCase,
        public readonly GetNovedadesUseCase $getNovedadesUseCase,
    ) {}
}


