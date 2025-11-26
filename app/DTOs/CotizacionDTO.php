<?php

namespace App\DTOs;

/**
 * Data Transfer Object para datos de cotización
 * 
 * Encapsula los datos procesados del formulario
 * para transferencia entre capas sin exponer detalles internos
 */
class CotizacionDTO
{
    public function __construct(
        public string $cliente,
        public string $tipo = 'borrador',
        public ?string $tipoCotizacion = null,
        public ?int $cotizacionId = null,
        public array $productos = [],
        public array $tecnicas = [],
        public array $ubicaciones = [],
        public array $imagenes = [],
        public array $especificaciones = [],
        public array $observaciones = [],
        public ?string $observacionesTecnicas = null,
        public ?string $numeroCotizacion = null,
    ) {}

    /**
     * Crear DTO desde array de datos validados
     * 
     * @param array $datosValidados Datos del FormRequest::validated()
     * @return self
     */
    public static function fromValidated(array $datosValidados): self
    {
        return new self(
            cliente: $datosValidados['cliente'] ?? '',
            tipo: $datosValidados['tipo'] ?? 'borrador',
            tipoCotizacion: $datosValidados['tipo_cotizacion'] ?? null,
            cotizacionId: $datosValidados['cotizacion_id'] ?? null,
            productos: $datosValidados['productos'] ?? [],
            tecnicas: $datosValidados['tecnicas'] ?? [],
            ubicaciones: $datosValidados['ubicaciones'] ?? [],
            imagenes: $datosValidados['imagenes'] ?? [],
            especificaciones: $datosValidados['especificaciones'] ?? [],
            observaciones: $datosValidados['observaciones'] ?? [],
            observacionesTecnicas: $datosValidados['observaciones_tecnicas'] ?? null,
            numeroCotizacion: $datosValidados['numero_cotizacion'] ?? null,
        );
    }

    /**
     * Obtener datos para crear cotización
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'cliente' => $this->cliente,
            'tipo' => $this->tipo,
            'tipo_cotizacion' => $this->tipoCotizacion,
            'cotizacion_id' => $this->cotizacionId,
            'productos' => $this->productos,
            'tecnicas' => $this->tecnicas,
            'ubicaciones' => $this->ubicaciones,
            'imagenes' => $this->imagenes,
            'especificaciones' => $this->especificaciones,
            'observaciones' => $this->observaciones,
            'observaciones_tecnicas' => $this->observacionesTecnicas,
            'numero_cotizacion' => $this->numeroCotizacion,
        ];
    }

    /**
     * Validar que los datos requeridos están presentes
     * 
     * @return bool
     */
    public function isValido(): bool
    {
        if (empty($this->cliente)) {
            return false;
        }
        
        if ($this->tipo === 'enviada' && empty($this->productos)) {
            return false;
        }
        
        return true;
    }

    /**
     * Obtener mensajes de validación
     * 
     * @return array
     */
    public function getErroresValidacion(): array
    {
        $errores = [];
        
        if (empty($this->cliente)) {
            $errores[] = 'El cliente es requerido';
        }
        
        if ($this->tipo === 'enviada' && empty($this->productos)) {
            $errores[] = 'Los productos son requeridos para cotizaciones enviadas';
        }
        
        return $errores;
    }

    /**
     * Verificar si es una actualización
     * 
     * @return bool
     */
    public function esActualizacion(): bool
    {
        return $this->cotizacionId !== null;
    }

    /**
     * Verificar si es un borrador
     * 
     * @return bool
     */
    public function esBorrador(): bool
    {
        return $this->tipo === 'borrador';
    }

    /**
     * Obtener solo los datos de productos
     * 
     * @return array
     */
    public function getProductos(): array
    {
        return $this->productos;
    }

    /**
     * Obtener solo los datos de logo
     * 
     * @return array
     */
    public function getDatosLogo(): array
    {
        return [
            'imagenes' => $this->imagenes,
            'tecnicas' => $this->tecnicas,
            'observaciones_tecnicas' => $this->observacionesTecnicas,
            'ubicaciones' => $this->ubicaciones,
            'observaciones' => $this->observaciones,
        ];
    }
}
