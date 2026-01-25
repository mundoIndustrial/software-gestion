<?php

namespace App\Repositories;

use App\Domain\Procesos\Entities\ProcesoPrendaImagen;
use App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository;
use App\Models\ProcesoPrendaImagen as ProcesoPrendaImagenModel;
use Illuminate\Database\Eloquent\Collection;

class EloquentProcesoPrendaImagenRepository implements ProcesoPrendaImagenRepository
{
    public function obtenerPorId(int $id): ?ProcesoPrendaImagen
    {
        $model = ProcesoPrendaImagenModel::find($id);

        if (!$model) {
            return null;
        }

        return $this->mapearAEntity($model);
    }

    public function obtenerPorProceso(int $procesoPrendaDetalleId): array
    {
        $modelos = ProcesoPrendaImagenModel::porProceso($procesoPrendaDetalleId)
            ->ordenado()
            ->get();

        return $modelos->map(fn($modelo) => $this->mapearAEntity($modelo))->toArray();
    }

    public function obtenerPrincipal(int $procesoPrendaDetalleId): ?ProcesoPrendaImagen
    {
        $modelo = ProcesoPrendaImagenModel::porProceso($procesoPrendaDetalleId)
            ->principal()
            ->first();

        if (!$modelo) {
            return null;
        }

        return $this->mapearAEntity($modelo);
    }

    public function obtenerPorHash(string $hash): ?ProcesoPrendaImagen
    {
        $modelo = ProcesoPrendaImagenModel::porHash($hash)->first();

        if (!$modelo) {
            return null;
        }

        return $this->mapearAEntity($modelo);
    }

    public function guardar(ProcesoPrendaImagen $imagen): ProcesoPrendaImagen
    {
        $modelo = new ProcesoPrendaImagenModel([
            'proceso_prenda_detalle_id' => $imagen->getProcesoPrendaDetalleId(),
            'ruta_original' => $imagen->getNombreOriginal(),
            'ruta_webp' => $imagen->getRuta(),
            'orden' => $imagen->getOrden(),
            'es_principal' => $imagen->getEsPrincipal(),
        ]);

        $modelo->save();

        $imagen->setId($modelo->id);

        return $imagen;
    }

    public function actualizar(ProcesoPrendaImagen $imagen): ProcesoPrendaImagen
    {
        $modelo = ProcesoPrendaImagenModel::findOrFail($imagen->getId());

        $modelo->update([
            'ruta_original' => $imagen->getNombreOriginal(),
            'ruta_webp' => $imagen->getRuta(),
            'orden' => $imagen->getOrden(),
            'es_principal' => $imagen->getEsPrincipal(),
        ]);

        return $imagen;
    }

    public function eliminar(int $id): bool
    {
        $modelo = ProcesoPrendaImagenModel::findOrFail($id);
        return $modelo->delete();
    }

    public function obtenerProximoOrden(int $procesoPrendaDetalleId): int
    {
        $ultimoOrden = ProcesoPrendaImagenModel::porProceso($procesoPrendaDetalleId)
            ->max('orden') ?? 0;

        return $ultimoOrden + 1;
    }

    public function marcarOtraComoPrincipal(int $procesoPrendaDetalleId, ?int $imagenIdAMarcar = null): void
    {
        // Desmarcar todas las imágenes principales del proceso
        ProcesoPrendaImagenModel::porProceso($procesoPrendaDetalleId)
            ->principal()
            ->update(['es_principal' => false]);

        // Si se proporciona un ID de imagen válido, marcar como principal
        if ($imagenIdAMarcar !== null && $imagenIdAMarcar > 0) {
            $modelo = ProcesoPrendaImagenModel::findOrFail($imagenIdAMarcar);
            $modelo->update(['es_principal' => true]);
        }
    }

    private function mapearAEntity(ProcesoPrendaImagenModel $modelo): ProcesoPrendaImagen
    {
        $entidad = new ProcesoPrendaImagen(
            id: $modelo->id,
            procesoPrendaDetalleId: $modelo->proceso_prenda_detalle_id,
            ruta: $modelo->ruta_webp,
            nombreOriginal: $modelo->ruta_original,
            tipoMime: null,
            tamaño: 0,
            ancho: 0,
            alto: 0,
            hashMd5: null,
            orden: $modelo->orden,
            esPrincipal: $modelo->es_principal,
            descripcion: null
        );

        return $entidad;
    }
}
