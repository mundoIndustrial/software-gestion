<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO: PrendaEditadaDTO
 * 
 * Contiene los datos de una prenda preparados para edición en el frontend
 */
class PrendaEditadaDTO
{
    private array $datos;
    
    public function __construct(array $datos)
    {
        $this->datos = $datos;
        $this->validar();
    }
    
    private function validar(): void
    {
        if (!isset($this->datos['id'])) {
            throw new \InvalidArgumentException('El ID de la prenda es requerido');
        }
        
        if (!isset($this->datos['nombre_prenda'])) {
            throw new \InvalidArgumentException('El nombre de la prenda es requerido');
        }
        
        if (!isset($this->datos['origen'])) {
            throw new \InvalidArgumentException('El origen de la prenda es requerido');
        }
    }
    
    // Getters para propiedades principales
    public function id(): ?int { return $this->datos['id'] ?? null; }
    public function nombre(): string { return $this->datos['nombre_prenda'] ?? ''; }
    public function descripcion(): string { return $this->datos['descripcion'] ?? ''; }
    public function origen(): string { return $this->datos['origen'] ?? 'confeccion'; }
    public function esBodega(): bool { return $this->datos['origen'] === 'bodega'; }
    public function esConfeccion(): bool { return $this->datos['origen'] === 'confeccion'; }
    
    // Getters para colecciones
    public function telas(): array { return $this->datos['telasAgregadas'] ?? []; }
    public function imagenes(): array { return $this->datos['imagenes'] ?? []; }
    public function variantes(): array { return $this->datos['variantes'] ?? []; }
    public function procesos(): array { return $this->datos['procesos'] ?? []; }
    public function tallas(): array { return $this->datos['tallas'] ?? []; }
    
    // Getters para relaciones
    public function cotizacionId(): ?int { return $this->datos['cotizacion_id'] ?? null; }
    public function prendaId(): ?int { return $this->datos['prenda_id'] ?? null; }
    
    // Métodos de utilidad
    public function tieneTelas(): bool { return !empty($this->telas()); }
    public function tieneImagenes(): bool { return !empty($this->imagenes()); }
    public function tieneVariantes(): bool { return !empty($this->variantes()); }
    public function tieneProcesos(): bool { return !empty($this->procesos()); }
    public function tieneTallas(): bool { return !empty($this->tallas()); }
    
    public function cantidadTelas(): int { return count($this->telas()); }
    public function cantidadImagenes(): int { return count($this->imagenes()); }
    public function cantidadVariantes(): int { return count($this->variantes()); }
    public function cantidadProcesos(): int { return count($this->procesos()); }
    
    /**
     * Verifica si es una prenda de cotización (Reflectivo/Logo)
     */
    public function esPrendaCotizacion(): bool
    {
        return !empty($this->cotizacionId()) && !empty($this->prendaId());
    }
    
    /**
     * Obtiene telas que no tienen referencia
     */
    public function telasSinReferencia(): array
    {
        return array_filter($this->telas(), function($tela) {
            return empty($tela['referencia'] ?? '');
        });
    }
    
    /**
     * Obtiene telas que tienen imágenes
     */
    public function telasConImagenes(): array
    {
        return array_filter($this->telas(), function($tela) {
            return !empty($tela['imagenes'] ?? []);
        });
    }
    
    /**
     * Convierte a array para respuesta JSON
     */
    public function toArray(): array
    {
        return $this->datos;
    }
    
    /**
     * Convierte a JSON
     */
    public function toJson(): string
    {
        return json_encode($this->datos);
    }
    
    /**
     * Crea desde array crudo (validando estructura)
     */
    public static function desdeArray(array $datos): self
    {
        // Asegurar campos requeridos con valores por defecto
        $datosProcesados = array_merge([
            'id' => null,
            'nombre_prenda' => '',
            'descripcion' => '',
            'origen' => 'confeccion',
            'telasAgregadas' => [],
            'imagenes' => [],
            'variantes' => [],
            'procesos' => [],
            'tallas' => [],
            'cotizacion_id' => null,
            'prenda_id' => null
        ], $datos);
        
        return new self($datosProcesados);
    }
    
    /**
     * Obtiene resumen para logging
     */
    public function getResumen(): array
    {
        return [
            'id' => $this->id(),
            'nombre' => $this->nombre(),
            'origen' => $this->origen(),
            'es_cotizacion' => $this->esPrendaCotizacion(),
            'cantidad_telas' => $this->cantidadTelas(),
            'cantidad_imagenes' => $this->cantidadImagenes(),
            'tiene_variantes' => $this->tieneVariantes(),
            'tiene_procesos' => $this->tieneProcesos(),
            'tiene_tallas' => $this->tieneTallas(),
            'telas_sin_referencia' => count($this->telasSinReferencia())
        ];
    }
}
