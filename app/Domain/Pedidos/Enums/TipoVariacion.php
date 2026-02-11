<?php

namespace App\Domain\Pedidos\Enums;

enum TipoVariacion: string
{
    case MANGA = 'manga';
    case BROCHE = 'broche';
    case BOLILLOS = 'bolsillos';
    case REFLECTIVO = 'reflectivo';
    
    public function getNombre(): string
    {
        return match($this) {
            self::MANGA => 'Manga',
            self::BROCHE => 'Broche/Botón',
            self::BOLILLOS => 'Bolsillos',
            self::REFLECTIVO => 'Reflectivo'
        };
    }
    
    public function getCampoFormulario(): string
    {
        return match($this) {
            self::MANGA => 'manga',
            self::BROCHE => 'broche',
            self::BOLILLOS => 'bolsillos',
            self::REFLECTIVO => 'reflectivo'
        };
    }
    
    public function getCampoInput(): string
    {
        return match($this) {
            self::MANGA => 'manga-input',
            self::BROCHE => 'broche-input',
            self::BOLILLOS => 'bolsillos-obs',
            self::REFLECTIVO => 'reflectivo-obs'
        };
    }
    
    public function getCampoObservacion(): string
    {
        return match($this) {
            self::MANGA => 'manga-obs',
            self::BROCHE => 'broche-obs',
            self::BOLILLOS => 'bolsillos-obs',
            self::REFLECTIVO => 'reflectivo-obs'
        };
    }
    
    public function getCheckboxId(): string
    {
        return match($this) {
            self::MANGA => 'aplica-manga',
            self::BROCHE => 'aplica-broche',
            self::BOLILLOS => 'aplica-bolsillos',
            self::REFLECTIVO => 'aplica-reflectivo'
        };
    }
    
    /**
     * Detectar tipo de variación basado en la estructura de datos
     */
    public static function detectarDesdeDatos(array $datos): array
    {
        $tipos = [];
        
        // Detectar manga
        if (isset($datos['manga']) || isset($datos['tipo_manga']) || isset($datos['tipo_manga_id'])) {
            $tipos[] = self::MANGA;
        }
        
        // Detectar broche
        if (isset($datos['broche']) || isset($datos['broche_boton']) || isset($datos['tipo_broche']) || isset($datos['tipo_broche_id'])) {
            $tipos[] = self::BROCHE;
        }
        
        // Detectar bolsillos
        if (isset($datos['bolsillos']) || isset($datos['obs_bolsillos'])) {
            $tipos[] = self::BOLILLOS;
        }
        
        // Detectar reflectivo
        if (isset($datos['reflectivo']) || isset($datos['obs_reflectivo'])) {
            $tipos[] = self::REFLECTIVO;
        }
        
        return $tipos;
    }
}
