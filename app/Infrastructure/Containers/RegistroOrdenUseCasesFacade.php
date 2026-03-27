<?php

namespace App\Infrastructure\Containers;

use App\Application\UseCases\Orders\GetOrdersQueryUseCase;
use App\Application\UseCases\Orders\GetOrderImagesQueryUseCase;
use App\Application\UseCases\Orders\GetOrderDetailsQueryUseCase;
use App\Application\UseCases\RegistroOrden\GetSeguimientoPorPrendaUseCase;
use App\Application\UseCases\RegistroOrden\GetDescripcionPrendasUseCase;
use App\Application\UseCases\RegistroOrden\GetConsecutivoCosturaUseCase;
use App\Application\UseCases\RegistroOrden\CalcularDiasUseCase;
use App\Application\UseCases\RegistroOrden\CalcularDiasBatchUseCase;
use App\Application\UseCases\RegistroOrden\CalcularFechaEstimadaUseCase;
use App\Application\UseCases\RegistroOrden\GetRecibosDatosUseCase;
use App\Application\UseCases\RegistroOrden\GetNovedadesUseCase;

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
