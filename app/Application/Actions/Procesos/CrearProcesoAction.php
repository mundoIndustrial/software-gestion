<?php

namespace App\Application\Actions\Procesos;

use App\Domain\Procesos\Services\CrearProcesoPrendaService;
use App\Domain\Procesos\Services\SubirImagenProcesoService;
use App\Domain\Procesos\Repositories\TipoProcesoRepository;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;
use App\Domain\Procesos\Repositories\ProcesoPrendaImagenRepository;
use App\DTOs\CrearProcesoPrendaDTO;
use Illuminate\Support\Facades\Storage;

/**
 * Application Action: CrearProcesoAction
 * 
 * Use case para crear un nuevo proceso para una prenda
 * Orquesta la interacción entre controllers, domain services e infrastructure
 */
class CrearProcesoAction
{
    public function __construct(
        private CrearProcesoPrendaService $crearProcesoService,
        private SubirImagenProcesoService $subirImagenService,
        private TipoProcesoRepository $tipoProcesoRepository,
        private ProcesoPrendaDetalleRepository $procesoRepository,
        private ProcesoPrendaImagenRepository $imagenRepository,
    ) {}

    /**
     * Ejecutar el use case
     * 
     * @throws \DomainException Si hay error en la lógica de negocio
     * @throws \Exception Si hay error en la persistencia
     */
    public function ejecutar(CrearProcesoPrendaDTO $dto)
    {
        // Validar que tipo de proceso existe
        $tipoProceso = $this->tipoProcesoRepository->obtenerPorId($dto->tipoProcesoId);
        if (!$tipoProceso) {
            throw new \DomainException("Tipo de proceso no encontrado");
        }

        // Ejecutar domain service para crear el proceso
        $proceso = $this->crearProcesoService->ejecutar(
            prendaId: $dto->prendaId,
            tipoProcesoId: $dto->tipoProcesoId,
            ubicaciones: $dto->ubicaciones,
            observaciones: $dto->observaciones,
            tallasDama: $dto->tallasDama,
            tallasCalabrero: $dto->tallasCalabrero,
            datosAdicionales: $dto->datosAdicionales
        );

        // Guardar imagen(es) si existen
        if ($dto->imagenBase64) {
            $this->guardarImagenProceso($proceso->getId(), $dto->imagenBase64, $dto->prendaId, esPrincipal: true);
        }

        return $proceso;
    }

    /**
     * Guardar imagen en la tabla pedidos_procesos_imagenes
     */
    private function guardarImagenProceso(int $procesoId, string $imagenBase64, int $prendaId, bool $esPrincipal = false): void
    {
        try {
            // Obtener el pedido_id desde la prenda
            $prenda = \App\Models\PrendaPedido::find($prendaId);
            if (!$prenda) {
                throw new \Exception("Prenda no encontrada");
            }
            $pedidoId = $prenda->pedido_produccion_id;

            // Decodificar base64
            $imagenBinaria = base64_decode($imagenBase64);
            if (!$imagenBinaria) {
                throw new \Exception("No se pudo decodificar la imagen");
            }

            // Detectar tipo MIME
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $tipoMime = finfo_buffer($finfo, $imagenBinaria);
            finfo_close($finfo);

            // Validar tipo MIME
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($tipoMime, $tiposPermitidos)) {
                throw new \Exception("Tipo de imagen no permitido: $tipoMime");
            }

            // Generar nombre de archivo
            $extension = match($tipoMime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => 'jpg'
            };

            $nombreArchivo = "proceso-{$procesoId}-prenda-{$prendaId}-" . time() . ".{$extension}";

            // Guardar archivo en estructura pedidos/{pedido_id}/procesos/
            $ruta = Storage::disk('public')->put("pedidos/{$pedidoId}/procesos/{$nombreArchivo}", $imagenBinaria);

            // Calcular hash MD5 para detectar duplicados
            $hashMd5 = md5($imagenBinaria);

            // Obtener dimensiones de imagen si es posible
            $imagenInfo = getimagesizefromstring($imagenBinaria);
            $ancho = $imagenInfo[0] ?? null;
            $alto = $imagenInfo[1] ?? null;

            // Usar domain service para guardar imagen
            $this->subirImagenService->ejecutar(
                procesoPrendaDetalleId: $procesoId,
                rutaArchivo: "pedidos/{$pedidoId}/procesos/{$nombreArchivo}",
                nombreOriginal: $nombreArchivo,
                tipoMime: $tipoMime,
                tamaño: strlen($imagenBinaria),
                ancho: $ancho ?? 0,
                alto: $alto ?? 0,
                hashMd5: $hashMd5,
                descripcion: null,
                esPrincipal: $esPrincipal
            );

        } catch (\Exception $e) {
            throw new \Exception("Error al guardar imagen: " . $e->getMessage());
        }
    }
}
