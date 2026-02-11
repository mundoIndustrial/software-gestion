<?php

namespace App\Domain\Pedidos\Enums;

enum TipoTalla: string
{
    case LETRA = 'letra';
    case NUMERO = 'numero';
    case MIXTO = 'mixto';
    
    public function getNombre(): string
    {
        return match($this) {
            self::LETRA => 'Letra',
            self::NUMERO => 'Número',
            self::MIXTO => 'Mixto',
        };
    }
    
    /**
     * Detectar tipo de talla basado en las tallas proporcionadas
     */
    public static function detectarTipo(array $tallas): self
    {
        $tieneLetras = false;
        $tieneNumeros = false;
        
        foreach ($tallas as $talla => $cantidad) {
            if ($cantidad > 0) {
                if (preg_match('/^[A-Z]+$/i', $talla)) {
                    $tieneLetras = true;
                } elseif (is_numeric($talla)) {
                    $tieneNumeros = true;
                }
            }
        }
        
        if ($tieneLetras && $tieneNumeros) {
            return self::MIXTO;
        } elseif ($tieneNumeros) {
            return self::NUMERO;
        } else {
            return self::LETRA;
        }
    }
}
