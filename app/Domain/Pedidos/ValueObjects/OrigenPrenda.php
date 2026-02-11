<?php

namespace App\Domain\Pedidos\ValueObjects;

/**
 * Value Object: OrigenPrenda
 * 
 * Representa el origen de una prenda (confección o bodega)
 * con lógica de negocio para determinar origen automático
 */
class OrigenPrenda
{
    private const CONFECCION = 'confeccion';
    private const BODEGA = 'bodega';
    
    private string $valor;
    
    public function __construct(string $valor = self::CONFECCION)
    {
        $this->validar($valor);
        $this->valor = $valor;
    }
    
    private function validar(string $valor): void
    {
        if (!in_array($valor, [self::CONFECCION, self::BODEGA])) {
            throw new \InvalidArgumentException("Origen inválido: {$valor}");
        }
    }
    
    public function valor(): string
    {
        return $this->valor;
    }
    
    public function esConfeccion(): bool
    {
        return $this->valor === self::CONFECCION;
    }
    
    public function esBodega(): bool
    {
        return $this->valor === self::BODEGA;
    }
    
    /**
     * Determina el origen basado en la cotización
     * Forza 'bodega' para tipos Reflectivo y Logo
     */
    public static function desdeCotizacion(?object $cotizacion, ?string $origenActual = null): self
    {
        if (!$cotizacion) {
            return new self($origenActual ?? self::CONFECCION);
        }
        
        $tiposQueFuerzanBodega = ['Reflectivo', 'Logo'];
        $nombreTipo = $cotizacion->tipo_cotizacion->nombre ?? $cotizacion->tipo_nombre ?? null;
        $tipoId = $cotizacion->tipo_cotizacion_id ?? null;
        
        // Verificar por nombre o por ID
        $esReflectivo = $nombreTipo === 'Reflectivo' || $tipoId === 'Reflectivo' || $tipoId === 4;
        $esLogo = $nombreTipo === 'Logo' || $tipoId === 'Logo' || $tipoId === 3;
        
        if ($esReflectivo || $esLogo) {
            return new self(self::BODEGA);
        }
        
        return new self($origenActual ?? self::CONFECCION);
    }
    
    /**
     * Convierte desde el campo de_bodega de la base de datos
     */
    public static function desdeDeBodega($deBodega): self
    {
        if ($deBodega == 1 || $deBodega === true || $deBodega === '1') {
            return new self(self::BODEGA);
        }
        
        return new self(self::CONFECCION);
    }
    
    public function equals(OrigenPrenda $otro): bool
    {
        return $this->valor === $otro->valor;
    }
    
    public function __toString(): string
    {
        return $this->valor;
    }
    
    public function toArray(): array
    {
        return [
            'origen' => $this->valor,
            'es_confeccion' => $this->esConfeccion(),
            'es_bodega' => $this->esBodega()
        ];
    }
}
