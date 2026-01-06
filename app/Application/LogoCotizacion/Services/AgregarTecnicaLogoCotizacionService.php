<?php

namespace App\Application\LogoCotizacion\Services;

use App\Domain\LogoCotizacion\Entities\TecnicaLogoCotizacion;
use App\Domain\LogoCotizacion\Entities\PrendaTecnica;
use App\Domain\LogoCotizacion\ValueObjects\TipoTecnica;
use App\Infrastructure\Repositories\LogoCotizacion\LogoCotizacionTecnicaRepository;
use InvalidArgumentException;

/**
 * AgregarTecnicaLogoCotizacionService
 * 
 * Servicio de aplicación que maneja la lógica de agregar una técnica a una cotización
 */
class AgregarTecnicaLogoCotizacionService
{
    public function __construct(
        private readonly LogoCotizacionTecnicaRepository $tecnicaRepository
    ) {
    }

    /**
     * Agregar una técnica a una cotización
     * 
     * @param int $logoCotizacionId - ID de la cotización
     * @param int $tipoTecnicaId - ID del tipo de técnica (1=Bordado, 2=Estampado, etc)
     * @param array $prendas - Array de prendas a agregar
     * @param string|null $observaciones - Observaciones técnicas
     * @param string|null $instrucciones - Instrucciones especiales
     * 
     * @return TecnicaLogoCotizacion
     * @throws InvalidArgumentException
     */
    public function ejecutar(
        int $logoCotizacionId,
        int $tipoTecnicaId,
        array $prendas,
        ?string $observaciones = null,
        ?string $instrucciones = null
    ): TecnicaLogoCotizacion {
        $this->validarDatos($logoCotizacionId, $tipoTecnicaId, $prendas);

        // Obtener el tipo de técnica
        $tipoTecnica = $this->obtenerTipoTecnica($tipoTecnicaId);

        // Crear la entidad de técnica
        $tecnica = TecnicaLogoCotizacion::crear(
            $logoCotizacionId,
            $tipoTecnica,
            $observaciones,
            $instrucciones
        );

        // Agregar las prendas
        foreach ($prendas as $prendaData) {
            $prenda = $this->crearPrenda($prendaData);
            $tecnica->agregarPrenda($prenda);
        }

        // Persistir en BD
        return $this->tecnicaRepository->save($tecnica);
    }

    /**
     * Crear una prenda desde los datos recibidos
     */
    private function crearPrenda(array $datos): PrendaTecnica
    {
        return PrendaTecnica::crear(
            $datos['nombre_prenda'] ?? throw new InvalidArgumentException('nombre_prenda requerido'),
            $datos['descripcion'] ?? throw new InvalidArgumentException('descripcion requerida'),
            $datos['ubicaciones'] ?? throw new InvalidArgumentException('ubicaciones requeridas'),
            $datos['tallas'] ?? null,
            $datos['cantidad'] ?? 1
        );
    }

    /**
     * Obtener el tipo de técnica desde la BD
     */
    private function obtenerTipoTecnica(int $id): TipoTecnica
    {
        return match ($id) {
            1 => TipoTecnica::bordado(),
            2 => TipoTecnica::estampado(),
            3 => TipoTecnica::sublimado(),
            4 => TipoTecnica::dtf(),
            default => throw new InvalidArgumentException("Tipo de técnica inválido: $id")
        };
    }

    /**
     * Validar que los datos requeridos estén presentes
     */
    private function validarDatos(int $logoCotizacionId, int $tipoTecnicaId, array $prendas): void
    {
        if ($logoCotizacionId <= 0) {
            throw new InvalidArgumentException('logoCotizacionId debe ser mayor a 0');
        }

        if ($tipoTecnicaId <= 0 || $tipoTecnicaId > 4) {
            throw new InvalidArgumentException('tipoTecnicaId inválido. Debe estar entre 1 y 4');
        }

        if (empty($prendas)) {
            throw new InvalidArgumentException('Debe haber al menos una prenda');
        }

        foreach ($prendas as $index => $prenda) {
            if (empty($prenda['nombre_prenda'] ?? null)) {
                throw new InvalidArgumentException("Prenda $index: nombre_prenda es requerido");
            }
            if (empty($prenda['descripcion'] ?? null)) {
                throw new InvalidArgumentException("Prenda $index: descripcion es requerida");
            }
            if (empty($prenda['ubicaciones'] ?? null)) {
                throw new InvalidArgumentException("Prenda $index: ubicaciones es requerida");
            }
        }
    }
}
