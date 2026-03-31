<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Entities\ProcesoPrendaImagen;
use App\Domain\Procesos\Exceptions\SubirImagenProcesoException;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository;

class SubirImagenProcesoService
{
    private ProcesoPrendaImagenRepository $imagenRepository;
    private ProcesoPrendaDetalleRepository $procesoRepository;

    public function __construct(
        ProcesoPrendaImagenRepository $imagenRepository,
        ProcesoPrendaDetalleRepository $procesoRepository
    ) {
        $this->imagenRepository = $imagenRepository;
        $this->procesoRepository = $procesoRepository;
    }

    /**
     * Subir imagen a un proceso existente
     * @throws SubirImagenProcesoException
     */
    public function ejecutar(
        int $procesoPrendaDetalleId,
        string $rutaArchivo,
        string $nombreOriginal,
        string $tipoMime,
        string $hashMd5,
        string $descripcion = null,
        bool $esPrincipal = false
    ): ProcesoPrendaImagen {
        // Validar que el proceso existe
        $proceso = $this->procesoRepository->obtenerPorId($procesoPrendaDetalleId);
        if (!$proceso) {
            throw SubirImagenProcesoException::procesoNoExiste($procesoPrendaDetalleId);
        }

        // Validar que no exista una imagen con el mismo hash (duplicado)
        $imagenDuplicada = $this->imagenRepository->obtenerPorHash($hashMd5);
        if ($imagenDuplicada) {
            throw SubirImagenProcesoException::imagenDuplicada();
        }

        // Obtener el siguiente orden
        $orden = $this->imagenRepository->obtenerProximoOrden($procesoPrendaDetalleId);

        // Si es principal, marcar otras como no principal
        if ($esPrincipal) {
            $this->imagenRepository->marcarOtraComoPrincipal($procesoPrendaDetalleId, null);
        }

        // Crear la entidad de imagen
        $imagen = new ProcesoPrendaImagen(
            id: null,
            procesoPrendaDetalleId: $procesoPrendaDetalleId,
            ruta: $rutaArchivo,
            nombreOriginal: $nombreOriginal,
            tipoMime: $tipoMime,
            orden: $orden,
            esPrincipal: $esPrincipal,
            descripcion: $descripcion
        );

        // Persistir
        return $this->imagenRepository->guardar($imagen);
    }
}
