<?php

namespace App\Domain\Pedidos\Enums;

enum TipoProceso: string
{
    case BORDADO = 'bordado';
    case SERIGRAFIA = 'serigrafia';
    case IMPRESION = 'impresion';
    case SUBLIMACION = 'sublimacion';
    case VINILO = 'vinilo';
    case REFLECTIVO = 'reflectivo';
    case LOGO = 'logo';
    case COSTURA = 'costura';
    case CORTE = 'corte';
    case OTRO = 'otro';
    
    public function getNombre(): string
    {
        return match($this) {
            self::BORDADO => 'Bordado',
            self::SERIGRAFIA => 'Serigrafía',
            self::IMPRESION => 'Impresión',
            self::SUBLIMACION => 'Sublimación',
            self::VINILO => 'Vinilo',
            self::REFLECTIVO => 'Reflectivo',
            self::LOGO => 'Logo',
            self::COSTURA => 'Costura',
            self::CORTE => 'Corte',
            self::OTRO => 'Otro'
        };
    }
    
    public function getSlug(): string
    {
        return match($this) {
            self::BORDADO => 'bordado',
            self::SERIGRAFIA => 'serigrafia',
            self::IMPRESION => 'impresion',
            self::SUBLIMACION => 'sublimacion',
            self::VINILO => 'vinilo',
            self::REFLECTIVO => 'reflectivo',
            self::LOGO => 'logo',
            self::COSTURA => 'costura',
            self::CORTE => 'corte',
            self::OTRO => 'otro'
        };
    }
    
    public function getCheckboxId(): string
    {
        return 'checkbox-' . $this->getSlug();
    }
    
    public function getCampoTipo(): string
    {
        return match($this) {
            self::BORDADO => 'Bordado',
            self::SERIGRAFIA => 'Serigrafía',
            self::IMPRESION => 'Impresión',
            self::SUBLIMACION => 'Sublimación',
            self::VINILO => 'Vinilo',
            self::REFLECTIVO => 'Reflectivo',
            self::LOGO => 'Logo',
            self::COSTURA => 'Costura',
            self::CORTE => 'Corte',
            self::OTRO => 'Otro'
        };
    }
    
    /**
     * Detectar tipo de proceso basado en el nombre o slug
     */
    public static function detectarDesdeNombre(string $nombre): ?self
    {
        $nombreNormalizado = strtolower(trim($nombre));
        
        return match($nombreNormalizado) {
            'bordado' => self::BORDADO,
            'serigrafia', 'serigrafía' => self::SERIGRAFIA,
            'impresion', 'impresión' => self::IMPRESION,
            'sublimacion', 'sublimación' => self::SUBLIMACION,
            'vinilo' => self::VINILO,
            'reflectivo' => self::REFLECTIVO,
            'logo' => self::LOGO,
            'costura' => self::COSTURA,
            'corte' => self::CORTE,
            default => self::OTRO
        };
    }
    
    /**
     * Detectar tipo de proceso desde múltiples campos
     */
    public static function detectarDesdeDatos(array $datos): ?self
    {
        // Prioridad 1: slug
        if (isset($datos['slug'])) {
            return self::detectarDesdeNombre($datos['slug']);
        }
        
        // Prioridad 2: tipo
        if (isset($datos['tipo'])) {
            return self::detectarDesdeNombre($datos['tipo']);
        }
        
        // Prioridad 3: tipo_proceso
        if (isset($datos['tipo_proceso'])) {
            return self::detectarDesdeNombre($datos['tipo_proceso']);
        }
        
        // Prioridad 4: nombre
        if (isset($datos['nombre'])) {
            return self::detectarDesdeNombre($datos['nombre']);
        }
        
        // Prioridad 5: nombre_proceso
        if (isset($datos['nombre_proceso'])) {
            return self::detectarDesdeNombre($datos['nombre_proceso']);
        }
        
        return null;
    }
    
    /**
     * Verificar si el proceso requiere ubicaciones específicas
     */
    public function requiereUbicaciones(): bool
    {
        return match($this) {
            self::BORDADO, self::SERIGRAFIA, self::IMPRESION => true,
            default => false
        };
    }
    
    /**
     * Verificar si el proceso soporta imágenes
     */
    public function soportaImagenes(): bool
    {
        return match($this) {
            self::BORDADO, self::SERIGRAFIA, self::IMPRESION, self::SUBLIMACION => true,
            default => false
        };
    }
    
    /**
     * Verificar si el proceso requiere tallas específicas
     */
    public function requiereTallas(): bool
    {
        return match($this) {
            self::BORDADO, self::SERIGRAFIA, self::IMPRESION => true,
            default => false
        };
    }
}
