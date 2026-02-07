<?php

namespace App\Application\Prenda\Services;

use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Domain\Prenda\DomainServices\NormalizarDatosPrendaDomainService;
use Exception;

class ObtenerPrendaParaEdicionApplicationService
{
    public function __construct(
        private PrendaRepositoryInterface $repository,
        private NormalizarDatosPrendaDomainService $normalizarServicio
    ) {}

    /**
     * Obtiene prenda con todas sus relaciones para edición
     */
    public function ejecutar(int $prendaId): array
    {
        try {
            // 1. Obtener prenda del repositorio
            $prenda = $this->repository->porId($prendaId);

            if ($prenda === null) {
                return [
                    'exito' => false,
                    'datos' => null,
                    'errores' => ["Prenda con ID {$prendaId} no encontrada"],
                ];
            }

            // 2. Cargar relaciones (telas, procesos, variaciones ya están en el agregado)
            // El repositorio debe cargar estas relaciones

            // 3. Normalizar para frontend
            $respuesta = $this->normalizarServicio->normalizarParaFrontend($prenda);

            return $respuesta;

        } catch (Exception $e) {
            return [
                'exito' => false,
                'datos' => null,
                'errores' => ["Error al obtener prenda: {$e->getMessage()}"],
            ];
        }
    }

    /**
     * Obtiene prenda con detalle completo (incluye historial, estado, etc)
     */
    public function obtenerConDetalle(int $prendaId, array $relacionesExtra = []): array
    {
        try {
            $prenda = $this->repository->porId($prendaId);

            if ($prenda === null) {
                return [
                    'exito' => false,
                    'datos' => null,
                    'errores' => ["Prenda con ID {$prendaId} no encontrada"],
                ];
            }

            return $this->normalizarServicio->detalleCompleto($prenda, $relacionesExtra);

        } catch (Exception $e) {
            return [
                'exito' => false,
                'datos' => null,
                'errores' => ["Error al obtener detalle: {$e->getMessage()}"],
            ];
        }
    }
}
