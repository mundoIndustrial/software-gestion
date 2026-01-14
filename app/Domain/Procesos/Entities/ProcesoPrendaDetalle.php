<?php

namespace App\Domain\Procesos\Entities;

use App\Domain\Shared\Entity;

/**
 * Entity: ProcesoPrendaDetalle
 * 
 * Representa la configuración de un proceso específico para una prenda
 * Contiene ubicaciones, observaciones, tallas e imagen del proceso
 */
class ProcesoPrendaDetalle extends Entity
{
    protected $prendaPedidoId;
    protected $tipoProcesoId;
    protected $ubicaciones;
    protected $observaciones;
    protected $tallasDama;
    protected $tallasCalabrero;
    protected $estado;
    protected $notasRechazo;
    protected $fechaAprobacion;
    protected $aprobadoPor;
    protected $datosAdicionales;

    public const ESTADO_PENDIENTE = 'PENDIENTE';
    public const ESTADO_EN_REVISION = 'EN_REVISION';
    public const ESTADO_APROBADO = 'APROBADO';
    public const ESTADO_EN_PRODUCCION = 'EN_PRODUCCION';
    public const ESTADO_COMPLETADO = 'COMPLETADO';
    public const ESTADO_RECHAZADO = 'RECHAZADO';

    private static $estadosValidos = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_REVISION,
        self::ESTADO_APROBADO,
        self::ESTADO_EN_PRODUCCION,
        self::ESTADO_COMPLETADO,
        self::ESTADO_RECHAZADO,
    ];

    public function __construct(
        ?int $id,
        int $prendaPedidoId,
        int $tipoProcesoId,
        array $ubicaciones,
        ?string $observaciones = null,
        ?array $tallasDama = null,
        ?array $tallasCalabrero = null,
        string $estado = self::ESTADO_PENDIENTE,
        ?string $notasRechazo = null,
        ?\DateTime $fechaAprobacion = null,
        ?int $aprobadoPor = null,
        ?array $datosAdicionales = null
    ) {
        parent::__construct($id);
        $this->prendaPedidoId = $prendaPedidoId;
        $this->tipoProcesoId = $tipoProcesoId;
        $this->ubicaciones = $ubicaciones;
        $this->observaciones = $observaciones;
        $this->tallasDama = $tallasDama;
        $this->tallasCalabrero = $tallasCalabrero;
        $this->estado = $estado;
        $this->notasRechazo = $notasRechazo;
        $this->fechaAprobacion = $fechaAprobacion;
        $this->aprobadoPor = $aprobadoPor;
        $this->datosAdicionales = $datosAdicionales;
    }

    public function getPrendaPedidoId(): int
    {
        return $this->prendaPedidoId;
    }

    public function getTipoProcesoId(): int
    {
        return $this->tipoProcesoId;
    }

    public function getUbicaciones(): array
    {
        return $this->ubicaciones;
    }

    public function setUbicaciones(array $ubicaciones): void
    {
        $this->ubicaciones = $ubicaciones;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(?string $observaciones): void
    {
        $this->observaciones = $observaciones;
    }

    public function getTallasDama(): ?array
    {
        return $this->tallasDama;
    }

    public function setTallasDama(?array $tallasDama): void
    {
        $this->tallasDama = $tallasDama;
    }

    public function getTallasCalabrero(): ?array
    {
        return $this->tallasCalabrero;
    }

    public function setTallasCalabrero(?array $tallasCalabrero): void
    {
        $this->tallasCalabrero = $tallasCalabrero;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function puedeSerEditado(): bool
    {
        return !in_array($this->estado, [
            self::ESTADO_APROBADO,
            self::ESTADO_EN_PRODUCCION,
            self::ESTADO_COMPLETADO,
        ]);
    }

    public function aprobar(int $usuarioId): void
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new \DomainException('Solo se pueden aprobar procesos en estado PENDIENTE');
        }

        $this->estado = self::ESTADO_APROBADO;
        $this->aprobadoPor = $usuarioId;
        $this->fechaAprobacion = new \DateTime();
        $this->notasRechazo = null;
    }

    public function rechazar(string $notas): void
    {
        if ($this->estado !== self::ESTADO_PENDIENTE) {
            throw new \DomainException('Solo se pueden rechazar procesos en estado PENDIENTE');
        }

        $this->estado = self::ESTADO_RECHAZADO;
        $this->notasRechazo = $notas;
        $this->aprobadoPor = null;
        $this->fechaAprobacion = null;
    }

    public function enviarAProduccion(): void
    {
        if ($this->estado !== self::ESTADO_APROBADO) {
            throw new \DomainException('El proceso debe estar aprobado para ir a producción');
        }

        $this->estado = self::ESTADO_EN_PRODUCCION;
    }

    public function marcarCompletado(): void
    {
        if ($this->estado !== self::ESTADO_EN_PRODUCCION) {
            throw new \DomainException('El proceso debe estar en producción para marcarlo completado');
        }

        $this->estado = self::ESTADO_COMPLETADO;
    }

    public function getNotasRechazo(): ?string
    {
        return $this->notasRechazo;
    }

    public function getFechaAprobacion(): ?\DateTime
    {
        return $this->fechaAprobacion;
    }

    public function getAprobadoPor(): ?int
    {
        return $this->aprobadoPor;
    }

    public function getDatosAdicionales(): ?array
    {
        return $this->datosAdicionales;
    }

    public function setDatosAdicionales(?array $datos): void
    {
        $this->datosAdicionales = $datos;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'prenda_pedido_id' => $this->prendaPedidoId,
            'tipo_proceso_id' => $this->tipoProcesoId,
            'ubicaciones' => $this->ubicaciones,
            'observaciones' => $this->observaciones,
            'tallas_dama' => $this->tallasDama,
            'tallas_caballero' => $this->tallasCalabrero,
            'estado' => $this->estado,
            'notas_rechazo' => $this->notasRechazo,
            'fecha_aprobacion' => $this->fechaAprobacion,
            'aprobado_por' => $this->aprobadoPor,
            'datos_adicionales' => $this->datosAdicionales,
        ];
    }
}
