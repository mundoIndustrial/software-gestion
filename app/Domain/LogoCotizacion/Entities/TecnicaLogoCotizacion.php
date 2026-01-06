<?php

namespace App\Domain\LogoCotizacion\Entities;

use App\Domain\LogoCotizacion\ValueObjects\TipoTecnica;
use DateTime;

/**
 * TecnicaLogoCotizacion - Aggregate Root que representa una técnica dentro de una cotización
 * 
 * Una técnica agrupa:
 * - El tipo de técnica (Bordado, Estampado, Sublimado, DTF)
 * - Las prendas asociadas a esa técnica
 * - Las observaciones específicas de la técnica
 */
final class TecnicaLogoCotizacion
{
    private int $id;
    private int $logoCotizacionId;
    private TipoTecnica $tipo;
    /** @var PrendaTecnica[] */
    private array $prendas;
    private ?string $observacionesTecnica;
    private ?string $instruccionesEspeciales;
    private int $orden;
    private bool $activo;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        int $id,
        int $logoCotizacionId,
        TipoTecnica $tipo,
        array $prendas = [],
        ?string $observacionesTecnica = null,
        ?string $instruccionesEspeciales = null,
        int $orden = 0
    ) {
        $this->id = $id;
        $this->logoCotizacionId = $logoCotizacionId;
        $this->tipo = $tipo;
        $this->prendas = $prendas;
        $this->observacionesTecnica = $observacionesTecnica;
        $this->instruccionesEspeciales = $instruccionesEspeciales;
        $this->orden = $orden;
        $this->activo = true;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public static function crear(
        int $logoCotizacionId,
        TipoTecnica $tipo,
        ?string $observacionesTecnica = null,
        ?string $instruccionesEspeciales = null
    ): self {
        return new self(0, $logoCotizacionId, $tipo, [], $observacionesTecnica, $instruccionesEspeciales);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function logoCotizacionId(): int
    {
        return $this->logoCotizacionId;
    }

    public function tipo(): TipoTecnica
    {
        return $this->tipo;
    }

    /**
     * @return PrendaTecnica[]
     */
    public function prendas(): array
    {
        return $this->prendas;
    }

    public function observacionesTecnica(): ?string
    {
        return $this->observacionesTecnica;
    }

    public function instruccionesEspeciales(): ?string
    {
        return $this->instruccionesEspeciales;
    }

    public function orden(): int
    {
        return $this->orden;
    }

    public function esActiva(): bool
    {
        return $this->activo;
    }

    public function agregarPrenda(PrendaTecnica $prenda): void
    {
        $this->prendas[] = $prenda;
        $this->updatedAt = new DateTime();
    }

    public function eliminarPrenda(int $prendaId): void
    {
        $this->prendas = array_filter(
            $this->prendas,
            fn(PrendaTecnica $p) => $p->id() !== $prendaId
        );
        $this->updatedAt = new DateTime();
    }

    public function actualizarObservaciones(?string $observaciones): void
    {
        $this->observacionesTecnica = $observaciones;
        $this->updatedAt = new DateTime();
    }

    public function actualizarInstrucciones(?string $instrucciones): void
    {
        $this->instruccionesEspeciales = $instrucciones;
        $this->updatedAt = new DateTime();
    }

    public function activar(): void
    {
        $this->activo = true;
        $this->updatedAt = new DateTime();
    }

    public function desactivar(): void
    {
        $this->activo = false;
        $this->updatedAt = new DateTime();
    }

    public function tienePrendas(): bool
    {
        return !empty($this->prendas);
    }

    public function contarPrendas(): int
    {
        return count($this->prendas);
    }
}
