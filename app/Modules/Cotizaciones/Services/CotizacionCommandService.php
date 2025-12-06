<?php

namespace App\Modules\Cotizaciones\Services;

use App\Models\Cotizacion;
use App\Modules\Cotizaciones\Contracts\CotizacionRepositoryInterface;
use App\Modules\Cotizaciones\Contracts\CotizacionCommandServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * CotizacionCommandService
 * 
 * Servicio para operaciones de escritura (CRUD Write)
 * Responsabilidad única: comandos de cotizaciones
 * Principio: Single Responsibility (SRP)
 */
class CotizacionCommandService implements CotizacionCommandServiceInterface
{
    public function __construct(
        private CotizacionRepositoryInterface $repository
    ) {}

    /**
     * Crear nueva cotización
     */
    public function create(array $data): Cotizacion
    {
        return DB::transaction(function () use ($data) {
            $cotizacion = $this->repository->create($data);
            
            // Log de auditoría
            \Log::info('Cotización creada', [
                'cotizacion_id' => $cotizacion->id,
                'user_id' => $data['user_id'] ?? null,
                'cliente' => $data['cliente'] ?? null,
            ]);

            return $cotizacion;
        });
    }

    /**
     * Actualizar cotización
     */
    public function update(int $id, array $data): Cotizacion
    {
        return DB::transaction(function () use ($id, $data) {
            $this->repository->update($id, $data);
            
            $cotizacion = $this->repository->findById($id);
            
            \Log::info('Cotización actualizada', [
                'cotizacion_id' => $id,
            ]);

            return $cotizacion;
        });
    }

    /**
     * Eliminar cotización
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $deleted = $this->repository->delete($id);
            
            if ($deleted) {
                \Log::info('Cotización eliminada', [
                    'cotizacion_id' => $id,
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Cambiar estado de cotización
     */
    public function changeState(int $id, string $newState): Cotizacion
    {
        return DB::transaction(function () use ($id, $newState) {
            $cotizacion = $this->repository->findById($id);
            
            if (!$cotizacion) {
                throw new \Exception("Cotización no encontrada: {$id}");
            }

            $oldState = $cotizacion->estado;
            $this->repository->update($id, ['estado' => $newState]);
            
            \Log::info('Estado de cotización cambiado', [
                'cotizacion_id' => $id,
                'old_state' => $oldState,
                'new_state' => $newState,
            ]);

            return $this->repository->findById($id);
        });
    }

    /**
     * Convertir borrador a cotización
     */
    public function publishDraft(int $id): Cotizacion
    {
        return DB::transaction(function () use ($id) {
            $cotizacion = $this->repository->findById($id);
            
            if (!$cotizacion || !$cotizacion->es_borrador) {
                throw new \Exception("Borrador no encontrado o ya publicado: {$id}");
            }

            $this->repository->update($id, [
                'es_borrador' => false,
                'estado' => 'ENVIADA_ASESOR'
            ]);
            
            \Log::info('Borrador publicado', [
                'cotizacion_id' => $id,
            ]);

            return $this->repository->findById($id);
        });
    }
}
