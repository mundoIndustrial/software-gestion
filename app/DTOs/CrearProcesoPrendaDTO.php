<?php

namespace App\DTOs;

/**
 * DTO: CrearProcesoPrendaDTO
 * 
 * Transferencia de datos para crear un nuevo proceso en una prenda
 */
class CrearProcesoPrendaDTO
{
    public function __construct(
        public int $prendaId,
        public int $tipoProcesoId,
        public array $ubicaciones,
        public ?string $observaciones = null,
        public ?array $tallasDama = null,
        public ?array $tallasCalabrero = null,
        public ?string $imagenBase64 = null,
        public ?string $nombreImagen = null,
        public ?array $datosAdicionales = null,
    ) {}

    public static function fromRequest(array $data, int $prendaId): self
    {
        return new self(
            prendaId: $prendaId,
            tipoProcesoId: $data['tipo_proceso_id'],
            ubicaciones: $data['ubicaciones'],
            observaciones: $data['observaciones'] ?? null,
            tallasDama: $data['tallas_dama'] ?? null,
            tallasCalabrero: $data['tallas_caballero'] ?? null,
            imagenBase64: $data['imagen'] ?? null,
            nombreImagen: $data['nombre_imagen'] ?? null,
            datosAdicionales: $data['datos_adicionales'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'prenda_id' => $this->prendaId,
            'tipo_proceso_id' => $this->tipoProcesoId,
            'ubicaciones' => $this->ubicaciones,
            'observaciones' => $this->observaciones,
            'tallas_dama' => $this->tallasDama,
            'tallas_caballero' => $this->tallasCalabrero,
            'imagen_base64' => $this->imagenBase64,
            'nombre_imagen' => $this->nombreImagen,
            'datos_adicionales' => $this->datosAdicionales,
        ];
    }
}
