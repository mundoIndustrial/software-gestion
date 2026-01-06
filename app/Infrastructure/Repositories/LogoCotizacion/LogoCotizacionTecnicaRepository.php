<?php

namespace App\Infrastructure\Repositories\LogoCotizacion;

use App\Domain\LogoCotizacion\Entities\TecnicaLogoCotizacion;
use App\Domain\LogoCotizacion\ValueObjects\TipoTecnica;
use App\Models\LogoCotizacionTecnica as LogoCotizacionTecnicaModel;
use App\Models\LogoCotizacionTecnicaPrenda as LogoCotizacionTecnicaPrendaModel;

/**
 * LogoCotizacionTecnicaRepository - Persiste TecnicaLogoCotizacion en BD
 */
class LogoCotizacionTecnicaRepository
{
    /**
     * Guardar una técnica de logo cotización
     */
    public function save(TecnicaLogoCotizacion $tecnica): TecnicaLogoCotizacion
    {
        $model = LogoCotizacionTecnicaModel::updateOrCreate(
            [
                'logo_cotizacion_id' => $tecnica->logoCotizacionId(),
                'tipo_logo_cotizacion_id' => $tecnica->tipo()->id(),
            ],
            [
                'observaciones_tecnica' => $tecnica->observacionesTecnica(),
                'instrucciones_especiales' => $tecnica->instruccionesEspeciales(),
                'orden' => $tecnica->orden(),
                'activo' => $tecnica->esActiva(),
            ]
        );

        // Guardar prendas
        if ($tecnica->tienePrendas()) {
            foreach ($tecnica->prendas() as $prenda) {
                LogoCotizacionTecnicaPrendaModel::create([
                    'logo_cotizacion_tecnica_id' => $model->id,
                    'nombre_prenda' => $prenda->nombrePrenda(),
                    'descripcion' => $prenda->descripcion(),
                    'ubicaciones' => $prenda->ubicaciones(),
                    'tallas' => $prenda->tallas(),
                    'cantidad' => $prenda->cantidad(),
                    'especificaciones' => $prenda->especificaciones(),
                    'color_hilo' => $prenda->colorHilo(),
                    'puntos_estimados' => $prenda->puntosEstimados(),
                    'activo' => $prenda->esActiva(),
                ]);
            }
        }

        return $this->findById($model->id);
    }

    /**
     * Obtener una técnica por ID
     */
    public function findById(int $id): ?TecnicaLogoCotizacion
    {
        $model = LogoCotizacionTecnicaModel::with('tipo', 'prendas')
            ->find($id);

        if (!$model) {
            return null;
        }

        return $this->toDomain($model);
    }

    /**
     * Obtener técnicas de una cotización
     */
    public function findByLogoCotizacionId(int $logoCotizacionId): array
    {
        $models = LogoCotizacionTecnicaModel::with('tipo', 'prendas')
            ->where('logo_cotizacion_id', $logoCotizacionId)
            ->orderBy('orden')
            ->get();

        return $models->map(fn($model) => $this->toDomain($model))->toArray();
    }

    /**
     * Mapear modelo Eloquent a Entity de dominio
     */
    private function toDomain(LogoCotizacionTecnicaModel $model): TecnicaLogoCotizacion
    {
        $tipoTecnica = new TipoTecnica(
            $model->tipo->id,
            $model->tipo->nombre,
            $model->tipo->codigo,
            $model->tipo->color,
            $model->tipo->icono
        );

        $tecnica = new TecnicaLogoCotizacion(
            $model->id,
            $model->logo_cotizacion_id,
            $tipoTecnica,
            [],
            $model->observaciones_tecnica,
            $model->instrucciones_especiales,
            $model->orden
        );

        return $tecnica;
    }

    /**
     * Eliminar una técnica
     */
    public function delete(int $id): bool
    {
        return (bool) LogoCotizacionTecnicaModel::destroy($id);
    }
}
