<?php

namespace App\Modules\Cotizaciones\Services;

use App\Modules\Cotizaciones\Contracts\CotizacionRepositoryInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionQueryServiceInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionCommandServiceInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionTransformerInterface;

/**
 * CotizacionFacadeService
 * 
 * Fachada que unifica el acceso a servicios de cotizaciones
 * Reduce complejidad del controlador
 * Principio: Facade Pattern para simplificar interfaz pública
 */
class CotizacionFacadeService
{
    public function __construct(
        private CotizacionQueryServiceInterface $queryService,
        private CotizacionCommandServiceInterface $commandService,
        private CotizacionRepositoryInterface $repository,
        private CotizacionTransformerInterface $transformer
    ) {}

    /**
     * Obtener todas las cotizaciones del usuario
     */
    public function getAllUserCotizaciones(int $userId)
    {
        return $this->queryService->getAllUserCotizaciones($userId);
    }

    /**
     * Obtener borradores del usuario
     */
    public function getUserDrafts(int $userId)
    {
        return $this->queryService->getUserDrafts($userId);
    }

    /**
     * Obtener cotizaciones por tipo
     */
    public function getByType(int $userId, string $type, int $page = 1, int $perPage = 15)
    {
        return $this->queryService->getByType($userId, $type, $page, $perPage);
    }

    /**
     * Transformar cotización simple
     */
    public function transform($cotizacion): array
    {
        return $this->transformer->transform($cotizacion);
    }

    /**
     * Transformar colección de cotizaciones
     */
    public function transformCollection($cotizaciones): array
    {
        return $this->transformer->transformCollection($cotizaciones);
    }

    /**
     * Crear cotización
     */
    public function create(array $data)
    {
        return $this->commandService->create($data);
    }

    /**
     * Actualizar cotización
     */
    public function update(int $id, array $data)
    {
        return $this->commandService->update($id, $data);
    }

    /**
     * Eliminar cotización
     */
    public function delete(int $id): bool
    {
        return $this->commandService->delete($id);
    }

    /**
     * Cambiar estado
     */
    public function changeState(int $id, string $newState)
    {
        return $this->commandService->changeState($id, $newState);
    }

    /**
     * Obtener por ID
     */
    public function findById(int $id)
    {
        return $this->repository->findById($id);
    }
}
