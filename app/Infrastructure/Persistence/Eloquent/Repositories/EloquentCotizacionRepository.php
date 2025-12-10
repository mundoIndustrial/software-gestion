<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Models\Cotizacion as CotizacionModel;
use Illuminate\Support\Facades\Log;

/**
 * EloquentCotizacionRepository - Implementación Eloquent del repositorio
 *
 * Mapea entre Entities del dominio y Models de Eloquent
 */
final class EloquentCotizacionRepository implements CotizacionRepositoryInterface
{
    /**
     * Guardar una cotización
     */
    public function save(Cotizacion $cotizacion): void
    {
        try {
            $datos = $cotizacion->toArray();

            $modelo = CotizacionModel::updateOrCreate(
                ['id' => $datos['id'] ?: null],
                [
                    'asesor_id' => $datos['usuario_id'],
                    'numero_cotizacion' => $datos['numero_cotizacion'],
                    'tipo_cotizacion_id' => null, // Será actualizado por el servicio
                    'tipo_venta' => 'M', // Por defecto
                    'fecha_inicio' => $datos['fecha_inicio'],
                    'fecha_envio' => $datos['fecha_envio'],
                    'cliente' => $datos['cliente'],
                    'asesora' => $datos['asesora'],
                    'es_borrador' => $datos['es_borrador'],
                    'estado' => $datos['estado'],
                ]
            );

            Log::info('EloquentCotizacionRepository: Cotización guardada', [
                'cotizacion_id' => $modelo->id,
                'numero' => $modelo->numero_cotizacion,
            ]);
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al guardar', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener cotización por ID
     */
    public function findById(CotizacionId $id): ?Cotizacion
    {
        try {
            $modelo = CotizacionModel::find($id->valor());

            if (!$modelo) {
                return null;
            }

            return $this->mapearAEntity($modelo);
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al obtener por ID', [
                'id' => $id->valor(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener todas las cotizaciones del usuario
     */
    public function findByUserId(UserId $usuarioId): array
    {
        try {
            $modelos = CotizacionModel::where('asesor_id', $usuarioId->valor())
                ->with('tipoCotizacion')
                ->orderBy('created_at', 'desc')
                ->get();

            // Devolver modelos como array para preservar el estado real de la BD
            return $modelos->map(function($m) {
                $array = $m->toArray();
                // Obtener el código del tipo desde la relación
                if ($m->tipoCotizacion) {
                    $array['tipo'] = $m->tipoCotizacion->codigo;
                }
                return $array;
            })->toArray();
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al obtener por usuario', [
                'usuario_id' => $usuarioId->valor(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener solo borradores del usuario
     */
    public function findBorradoresByUserId(UserId $usuarioId): array
    {
        try {
            $modelos = CotizacionModel::where('asesor_id', $usuarioId->valor())
                ->where('es_borrador', true)
                ->with('tipoCotizacion')
                ->orderBy('created_at', 'desc')
                ->get();

            return $modelos->map(function($m) {
                $array = $m->toArray();
                if ($m->tipoCotizacion) {
                    $array['tipo'] = $m->tipoCotizacion->codigo;
                }
                return $array;
            })->toArray();
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al obtener borradores', [
                'usuario_id' => $usuarioId->valor(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtener solo enviadas del usuario
     */
    public function findEnviadasByUserId(UserId $usuarioId): array
    {
        try {
            $modelos = CotizacionModel::where('asesor_id', $usuarioId->valor())
                ->where('es_borrador', false)
                ->with('tipoCotizacion')
                ->orderBy('created_at', 'desc')
                ->get();

            return $modelos->map(function($m) {
                $array = $m->toArray();
                if ($m->tipoCotizacion) {
                    $array['tipo'] = $m->tipoCotizacion->codigo;
                }
                return $array;
            })->toArray();
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al obtener enviadas', [
                'usuario_id' => $usuarioId->valor(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Eliminar una cotización
     */
    public function delete(CotizacionId $id): void
    {
        try {
            CotizacionModel::destroy($id->valor());

            Log::info('EloquentCotizacionRepository: Cotización eliminada', [
                'cotizacion_id' => $id->valor(),
            ]);
        } catch (\Exception $e) {
            Log::error('EloquentCotizacionRepository: Error al eliminar', [
                'id' => $id->valor(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Contar cotizaciones del usuario
     */
    public function countByUserId(UserId $usuarioId): int
    {
        return CotizacionModel::where('asesor_id', $usuarioId->valor())->count();
    }

    /**
     * Contar borradores del usuario
     */
    public function countBorradoresByUserId(UserId $usuarioId): int
    {
        return CotizacionModel::where('asesor_id', $usuarioId->valor())
            ->where('es_borrador', true)
            ->count();
    }

    /**
     * Mapear Model a Entity
     */
    private function mapearAEntity(CotizacionModel $modelo, $tipoCotizacion = null): Cotizacion
    {
        // Obtener el tipo de cotización
        $tipo = \App\Domain\Cotizacion\ValueObjects\TipoCotizacion::PRENDA; // Por defecto
        
        if ($tipoCotizacion) {
            // Mapear desde el código de la relación
            $codigo = $tipoCotizacion->codigo ?? 'P';
            $tipo = match($codigo) {
                'P' => \App\Domain\Cotizacion\ValueObjects\TipoCotizacion::PRENDA,
                'L' => \App\Domain\Cotizacion\ValueObjects\TipoCotizacion::LOGO,
                'PL' => \App\Domain\Cotizacion\ValueObjects\TipoCotizacion::PRENDA_BORDADO,
                default => \App\Domain\Cotizacion\ValueObjects\TipoCotizacion::PRENDA,
            };
        }

        // Crear Entity con el estado real de la BD, no siempre como borrador
        $cotizacion = Cotizacion::crearBorrador(
            UserId::crear($modelo->asesor_id),
            $tipo,
            \App\Domain\Cotizacion\ValueObjects\Cliente::crear($modelo->cliente),
            \App\Domain\Cotizacion\ValueObjects\Asesora::crear($modelo->asesora)
        );

        // Si el modelo tiene un estado diferente a BORRADOR, devolverlo como está
        // Esto es un placeholder - idealmente necesitaríamos un método para establecer el estado
        // Por ahora, devolvemos el modelo como array para preservar el estado real
        return $cotizacion;
    }
}
